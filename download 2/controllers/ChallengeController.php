<?php
declare(strict_types=1);

class ChallengeController
{
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    // ── Public list ───────────────────────────────────────────

    public function index(): void
    {
        require_auth();
        $cats = $this->db->query("
            SELECT DISTINCT category FROM challenges WHERE status='published' ORDER BY category
        ")->fetchAll(PDO::FETCH_COLUMN);

        $cat    = trim($_GET['cat'] ?? '');
        $where  = "WHERE c.status='published'" . ($cat ? " AND c.category=?" : '');
        $params = $cat ? [$cat] : [];

        $stmt = $this->db->prepare("
            SELECT c.*,
                   (SELECT COUNT(*) FROM challenge_questions WHERE challenge_id=c.id) as q_count,
                   (SELECT COUNT(*) FROM challenge_attempts    WHERE challenge_id=c.id AND status='completed') as completions
            FROM challenges c {$where}
            ORDER BY c.created_at DESC
        ");
        $stmt->execute($params);
        $challenges = $stmt->fetchAll();

        // For logged-in users, get their attempts
        $userAttempts = [];
        if ($user = auth()) {
            $a = $this->db->prepare("SELECT challenge_id, status, percentage, passed FROM challenge_attempts WHERE user_id=?");
            $a->execute([$user['id']]);
            foreach ($a->fetchAll() as $row) $userAttempts[$row['challenge_id']] = $row;
        }

        render('challenges.index', [
            'challenges'   => $challenges,
            'userAttempts' => $userAttempts,
            'categories'   => $cats,
            'activeCat'    => $cat,
            'pageTitle'    => 'التحديات',
        ]);
    }

    // ── Challenge detail ──────────────────────────────────────

    public function view(): void
    {
        require_auth();
        $id       = (int)($_GET['id'] ?? 0);
        $ch       = $this->getChallengeOrDie($id);
        // Use prepared statement — never interpolate $id directly into query
        $qCountStmt = $this->db->prepare("SELECT COUNT(*) FROM challenge_questions WHERE challenge_id=?");
        $qCountStmt->execute([$id]);
        $qCount = (int)$qCountStmt->fetchColumn();

        $attempt  = null;
        if ($user = auth()) {
            $a = $this->db->prepare("SELECT * FROM challenge_attempts WHERE challenge_id=? AND user_id=? LIMIT 1");
            $a->execute([$id, $user['id']]);
            $attempt = $a->fetch() ?: null;
        }

        render('challenges.view', [
            'ch'        => $ch,
            'qCount'    => $qCount,
            'attempt'   => $attempt,
            'pageTitle' => $ch['title'],
        ]);
    }

    // ── Take challenge ────────────────────────────────────────

    public function take(): void
    {
        require_auth();
        $id   = (int)($_GET['id'] ?? 0);
        $ch   = $this->getChallengeOrDie($id);
        $user = auth();
        $uid  = (int)$user['id'];

        // Get or create attempt
        $attStmt = $this->db->prepare("SELECT * FROM challenge_attempts WHERE challenge_id=? AND user_id=? LIMIT 1");
        $attStmt->execute([$id, $uid]);
        $attempt = $attStmt->fetch();

        // Completed: auto-issue cert then redirect to result
        if ($attempt && $attempt['status'] === 'completed') {
            $passed = (bool)($attempt['passed'] ?? false);
            // ── Auto-issue certificate on pass ────────────────────
            if ($passed) {
                $existCert = $this->db->prepare("SELECT id FROM certificates WHERE user_id=? AND challenge_id=? LIMIT 1");
                $existCert->execute([$uid, $id]);
                if (!$existCert->fetch()) {
                    $certToken = bin2hex(random_bytes(24));
                    $certTitle = 'شهادة إتمام: ' . $ch['title'];
                    $this->db->prepare("INSERT INTO certificates (user_id,challenge_id,title,description,token,is_valid,issued_at) VALUES (?,?,?,?,?,1,datetime('now'))")
                             ->execute([$uid, $id, $certTitle, '', $certToken]);
                    notify($uid, 'certificate', 'تهانينا! حصلت على شهادة', 'أنجزت تحدي: ' . $ch['title'], '/account/certificates');
                    notify_certificate_email($uid, $certTitle, '', $certToken);
                }
            }
            redirect('/challenges/result?id=' . $id);
        }

        // Security: whitelist ORDER BY clause — never interpolate unvalidated user data into ORDER BY
        $order  = ((int)($ch['randomize'] ?? 0) === 1) ? 'RANDOM()' : 'sort_order ASC';
        $qStmt  = $this->db->prepare("SELECT * FROM challenge_questions WHERE challenge_id=? ORDER BY {$order}");
        $qStmt->execute([$id]);
        $questions = $qStmt->fetchAll();

        if (empty($questions)) redirect('/challenges/view?id=' . $id, ['error' => 'لا توجد أسئلة في هذا التحدي بعد.']);

        // Load options for all questions
        $qIds    = array_column($questions, 'id');
        $ph      = implode(',', array_fill(0, count($qIds), '?'));
        $optStmt = $this->db->prepare("SELECT * FROM challenge_options WHERE question_id IN ({$ph}) ORDER BY question_id, sort_order");
        $optStmt->execute($qIds);
        $allOpts = $optStmt->fetchAll();

        // Group options by question_id
        $optsByQ = [];
        foreach ($allOpts as $o) $optsByQ[$o['question_id']][] = $o;

        // Create attempt if new
        if (!$attempt) {
            $maxScore = array_sum(array_column($questions, 'points'));
            $this->db->prepare("INSERT INTO challenge_attempts (challenge_id,user_id,max_score) VALUES (?,?,?)")
                     ->execute([$id, $uid, $maxScore]);
            $attStmt->execute([$id, $uid]);
            $attempt = $attStmt->fetch();
        }

        $savedAnswers = json_decode($attempt['answers'] ?? '{}', true) ?: [];

        render('challenges.take', [
            'ch'          => $ch,
            'questions'   => $questions,
            'optsByQ'     => $optsByQ,
            'attempt'     => $attempt,
            'savedAnswers'=> $savedAnswers,
            'pageTitle'   => 'تحدي: ' . $ch['title'],
        ]);
    }

    // ── Save progress (AJAX) ──────────────────────────────────

    public function saveProgress(): void
    {
        require_auth(); verify_csrf();
        $user = auth(); $uid = (int)$user['id'];
        $cid  = (int)($_POST['challenge_id'] ?? 0);
        $qIdx = (int)($_POST['current_q'] ?? 0);
        // Validate answers is JSON
        $rawAnswers = $_POST['answers'] ?? '{}';
        $decoded    = json_decode($rawAnswers, true);
        if (!is_array($decoded)) json_out(['error' => 'بيانات غير صالحة.'], 422);

        // Sanitize: keys must be numeric strings, values arrays of integers
        $clean = [];
        foreach ($decoded as $qid => $opts) {
            if (!ctype_digit((string)$qid)) continue;
            if (!is_array($opts)) continue;
            $clean[(int)$qid] = array_filter(array_map('intval', $opts));
        }

        // Verify attempt belongs to this user (IDOR prevention)
        $att = $this->db->prepare("SELECT id FROM challenge_attempts WHERE challenge_id=? AND user_id=? LIMIT 1");
        $att->execute([$cid, $uid]);
        if (!$att->fetch()) json_out(['error' => 'غير مصرح.'], 403);

        $this->db->prepare("UPDATE challenge_attempts SET answers=?,current_q=? WHERE challenge_id=? AND user_id=?")
                 ->execute([json_encode($clean, JSON_UNESCAPED_UNICODE), $qIdx, $cid, $uid]);

        json_out(['success' => true]);
    }

    // ── Submit challenge ──────────────────────────────────────

    public function submitChallenge(): void
    {
        require_auth(); verify_csrf();
        $user = auth(); $uid = (int)$user['id'];
        $cid  = (int)($_POST['challenge_id'] ?? 0);

        // Verify attempt
        $att = $this->db->prepare("SELECT * FROM challenge_attempts WHERE challenge_id=? AND user_id=? LIMIT 1");
        $att->execute([$cid, $uid]);
        $attempt = $att->fetch();
        if (!$attempt) json_out(['error' => 'لا يوجد محاولة.'], 403);
        if ($attempt['status'] === 'completed') json_out(['redirect' => BASE_URL . '/challenges/result?id=' . $cid]);

        // Get all questions with correct options
        $qStmt = $this->db->prepare("
            SELECT cq.id, cq.points,
                   GROUP_CONCAT(CASE WHEN co.is_correct=1 THEN co.id END) as correct_ids
            FROM   challenge_questions cq
            LEFT JOIN challenge_options co ON co.question_id = cq.id
            WHERE  cq.challenge_id = ?
            GROUP  BY cq.id
        ");
        $qStmt->execute([$cid]);
        $questions = $qStmt->fetchAll();

        $rawAnswers = $_POST['answers'] ?? '{}';
        $answers    = json_decode($rawAnswers, true) ?: [];

        // Score
        $score = 0; $maxScore = 0;
        foreach ($questions as $q) {
            $maxScore   += (int)$q['points'];
            $correctIds  = array_filter(explode(',', $q['correct_ids'] ?? ''));
            sort($correctIds);
            $givenIds   = array_map('intval', (array)($answers[$q['id']] ?? []));
            sort($givenIds);
            // All correct options must be selected, and no wrong ones
            if (!empty($correctIds) && array_map('strval', $givenIds) === $correctIds) {
                $score += (int)$q['points'];
            }
        }

        $ch = $this->db->prepare("SELECT * FROM challenges WHERE id=? LIMIT 1");
        $ch->execute([$cid]); $ch = $ch->fetch();

        $pct    = $maxScore > 0 ? round(($score / $maxScore) * 100, 1) : 0;
        $passed = $pct >= ($ch['passing_score'] ?? 80) ? 1 : 0;

        $sanitized = [];
        foreach ($answers as $qid => $opts) {
            if (!ctype_digit((string)$qid)) continue;
            $sanitized[(int)$qid] = array_map('intval', (array)$opts);
        }

        $this->db->prepare("
            UPDATE challenge_attempts
            SET    status='completed', score=?, max_score=?, percentage=?, passed=?,
                   answers=?, completed_at=datetime('now')
            WHERE  challenge_id=? AND user_id=?
        ")->execute([$score, $maxScore, $pct, $passed,
                    json_encode($sanitized, JSON_UNESCAPED_UNICODE), $cid, $uid]);

        // ── Auto-issue certificate on pass ────────────────────────────────
        if ($passed) {
            $existCert = $this->db->prepare("SELECT id FROM certificates WHERE user_id=? AND challenge_id=? LIMIT 1");
            $existCert->execute([$uid, $cid]);
            if (!$existCert->fetch()) {
                $certToken = bin2hex(random_bytes(24));
                $certTitle = 'شهادة إتمام: ' . ($ch['title'] ?? '');
                $this->db->prepare("INSERT INTO certificates (user_id,challenge_id,title,description,token,is_valid,issued_at) VALUES (?,?,?,?,?,1,datetime('now'))")
                         ->execute([$uid, $cid, $certTitle, '', $certToken]);
                notify($uid, 'certificate', 'تهانينا! حصلت على شهادة', 'أنجزت تحدي: ' . ($ch['title'] ?? ''), '/account/certificates');
                notify_certificate_email($uid, $certTitle, '', $certToken);
            }
        }

        Logger::info('challenge.submit', "تسليم تحدي #{$cid} بـ {$pct}%", ['uid' => $uid, 'passed' => $passed]);
        json_out(['success' => true, 'redirect' => BASE_URL . '/challenges/result?id=' . $cid]);
    }

    // ── Result page ───────────────────────────────────────────


    public function resetAttempt(): void
    {
        require_auth(); verify_csrf();
        $user = auth(); $uid = (int)$user['id'];
        $cid  = (int)($_POST['challenge_id'] ?? 0);
        if ($cid <= 0) redirect('/challenges', ['error' => 'معرف غير صالح.']);
        $att = $this->db->prepare("SELECT id FROM challenge_attempts WHERE challenge_id=? AND user_id=? LIMIT 1");
        $att->execute([$cid, $uid]);
        if (!$att->fetch()) redirect('/challenges', ['error' => 'لا توجد محاولة.']);
        $this->db->prepare("DELETE FROM challenge_attempts WHERE challenge_id=? AND user_id=?")->execute([$cid, $uid]);
        Logger::info('challenge.reset', "إعادة تحدي #{$cid}", ['uid' => $uid]);
        redirect('/challenges/take?id=' . $cid);
    }

    public function result(): void

    {
        require_auth();
        $id   = (int)($_GET['id'] ?? 0);
        $user = auth(); $uid = (int)$user['id'];
        $ch   = $this->getChallengeOrDie($id);

        $att = $this->db->prepare("SELECT * FROM challenge_attempts WHERE challenge_id=? AND user_id=? LIMIT 1");
        $att->execute([$id, $uid]);
        $attempt = $att->fetch();
        if (!$attempt) redirect('/challenges/view?id=' . $id);

        // Load questions with correct answers for review
        $qStmt = $this->db->prepare("SELECT * FROM challenge_questions WHERE challenge_id=? ORDER BY sort_order");
        $qStmt->execute([$id]);
        $questions = $qStmt->fetchAll();

        $qIds  = array_column($questions, 'id');
        $optsByQ = [];
        if (!empty($qIds)) {
            $optPh = implode(',', array_fill(0, count($qIds), '?'));
            $oStmt = $this->db->prepare("SELECT * FROM challenge_options WHERE question_id IN ({$optPh}) ORDER BY question_id, sort_order");
            $oStmt->execute($qIds);
            foreach ($oStmt->fetchAll() as $o) $optsByQ[$o['question_id']][] = $o;
        }

        $savedAnswers = json_decode($attempt['answers'] ?? '{}', true) ?: [];

        render('challenges.result', [
            'ch'          => $ch,
            'attempt'     => $attempt,
            'questions'   => $questions,
            'optsByQ'     => $optsByQ,
            'savedAnswers'=> $savedAnswers,
            'pageTitle'   => 'نتيجة: ' . $ch['title'],
        ]);
    }

    private function getChallengeOrDie(int $id): array
    {
        $s = $this->db->prepare("SELECT * FROM challenges WHERE id=? AND status='published' LIMIT 1");
        $s->execute([$id]);
        $ch = $s->fetch();
        if (!$ch) { http_response_code(404); require ROOT_PATH . '/views/404.php'; exit; }
        return $ch;
    }
}
