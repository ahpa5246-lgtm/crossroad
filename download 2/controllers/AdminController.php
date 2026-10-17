<?php
declare(strict_types=1);

class AdminController
{
    private PDO $db;
    public function __construct() { require_admin(); $this->db = Database::getInstance(); }

    /**
     * Permission gate — super_admin bypasses all checks.
     * Pass 'super_admin_only' to restrict to super_admin only.
     */
    private function checkPerm(string $perm): void
    {
        $u = auth();
        if (!$u) redirect('/auth/login');
        if ($u['role'] === 'super_admin') return; // full access
        if ($perm === 'super_admin_only') {
            redirect('/admin', ['error' => 'هذه الميزة للمشرف الأعلى فقط.']);
        }
        $perms = json_decode($u['permissions'] ?? '[]', true);
        if (!is_array($perms) || !in_array($perm, $perms, true)) {
            Logger::warning('access.denied', "محاولة وصول بدون صلاحية: {$perm}", ['uid' => $u['id']]);
            redirect('/admin', ['error' => 'ليس لديك صلاحية لهذه العملية.']);
        }
    }

    // ── Dashboard ─────────────────────────────────────────────
    public function dashboard(): void {
        $s = [
            'users'       => (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'submissions' => (int)$this->db->query("SELECT COUNT(*) FROM form_submissions")->fetchColumn(),
            'pending'     => (int)$this->db->query("SELECT COUNT(*) FROM form_submissions WHERE status='pending'")->fetchColumn(),
            'accepted'    => (int)$this->db->query("SELECT COUNT(*) FROM form_submissions WHERE status='accepted'")->fetchColumn(),
            'open_forms'  => (int)$this->db->query("SELECT COUNT(*) FROM application_forms WHERE status='open'")->fetchColumn(),
            'news'        => (int)$this->db->query("SELECT COUNT(*) FROM news WHERE is_published=1")->fetchColumn(),
            'challenges'  => (int)$this->db->query("SELECT COUNT(*) FROM challenges WHERE status='published'")->fetchColumn(),
            'certs'       => (int)$this->db->query("SELECT COUNT(*) FROM certificates WHERE is_valid=1")->fetchColumn(),
            'completions' => (int)$this->db->query("SELECT COUNT(*) FROM challenge_attempts WHERE passed=1")->fetchColumn(),
        ];

        // Users who passed challenges but have NO certificate yet — need admin action
        $pendingCerts = $this->db->query("
            SELECT ca.user_id, ca.challenge_id, ca.percentage, ca.completed_at,
                   u.display_name, u.username, u.telegram_username,
                   c.title as challenge_title
            FROM   challenge_attempts ca
            JOIN   users u ON ca.user_id = u.id
            JOIN   challenges c ON ca.challenge_id = c.id
            WHERE  ca.passed = 1
              AND  NOT EXISTS (
                SELECT 1 FROM certificates cert
                WHERE cert.user_id = ca.user_id
                  AND cert.challenge_id = ca.challenge_id
                  AND cert.is_valid = 1
              )
            ORDER BY ca.completed_at DESC
            LIMIT 20
        ")->fetchAll();

        $logs = $this->db->query("SELECT sl.*, u.username FROM system_logs sl LEFT JOIN users u ON sl.user_id=u.id ORDER BY sl.created_at DESC LIMIT 12")->fetchAll();
        render('admin.dashboard', [
            'stats'        => $s,
            'pendingCerts' => $pendingCerts,
            'logs'         => $logs,
            'pageTitle'    => 'لوحة التحكم',
        ], 'admin');
    }

    // ── Content ───────────────────────────────────────────────
    public function content(): void {
        $settings = $this->db->query("SELECT key, value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        render('admin.content', ['settings' => $settings, 'pageTitle' => 'المحتوى'], 'admin');
    }
    public function contentSave(): void {
        verify_csrf();
        $allowed = ['site_name','hero_title','hero_subtitle','hero_cta_text','footer_text','contact_email',
                    'social_twitter','social_telegram','contact_text','achievements_title','social_instagram','social_linkedin','terms_content',
                    'cta_title','cta_text','carousel_title','rules_title','about_title_label','cert_signer_name','cert_signer_title'];
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO settings (key,value,updated_at) VALUES (?,?,datetime('now'))");
        foreach ($allowed as $k) {
            if (array_key_exists($k, $_POST)) $stmt->execute([$k, trim((string)$_POST[$k])]);
        }
        // Free-text sections: title + body, any number
        if (isset($_POST['sec_title']) && is_array($_POST['sec_title'])) {
            $titles = $_POST['sec_title'];
            $bodies = is_array($_POST['sec_body'] ?? null) ? $_POST['sec_body'] : [];
            $out = [];
            foreach ($titles as $i => $t) {
                $title = trim((string)$t);
                $body  = trim((string)($bodies[$i] ?? ''));
                if ($title === '' && $body === '') continue;
                $out[] = ['title' => mb_substr($title, 0, 120), 'body' => mb_substr($body, 0, 8000)];
                if (count($out) >= 20) break;
            }
            $stmt->execute(['content_sections', json_encode($out, JSON_UNESCAPED_UNICODE)]);
        }
        // Rules (القوانين)
        if (isset($_POST['rule_title']) && is_array($_POST['rule_title'])) {
            $rb = is_array($_POST['rule_body'] ?? null) ? $_POST['rule_body'] : [];
            $rules = [];
            foreach ($_POST['rule_title'] as $i => $rt) {
                $rt = trim((string)$rt); $rbody = trim((string)($rb[$i] ?? ''));
                if ($rt === '' && $rbody === '') continue;
                $rules[] = ['title' => mb_substr($rt, 0, 120), 'body' => mb_substr($rbody, 0, 4000)];
                if (count($rules) >= 60) break;
            }
            $stmt->execute(['rules_items', json_encode($rules, JSON_UNESCAPED_UNICODE)]);
        }
        // Navigation menu
        if (isset($_POST['nav_label']) && is_array($_POST['nav_label'])) {
            $nu = is_array($_POST['nav_url'] ?? null) ? $_POST['nav_url'] : [];
            $nav = [];
            foreach ($_POST['nav_label'] as $i => $nl) {
                $nl = mb_substr(trim((string)$nl), 0, 40); $nurl = trim((string)($nu[$i] ?? ''));
                if ($nl === '' || $nurl === '') continue;
                if (!preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $nurl)) $nurl = '/' . ltrim($nurl, '/');
                $nav[] = ['label' => $nl, 'url' => mb_substr($nurl, 0, 300)];
                if (count($nav) >= 12) break;
            }
            $stmt->execute(['nav_items', json_encode($nav, JSON_UNESCAPED_UNICODE)]);
        }
        // About-card portrait
        $ai = handle_upload('about_image', 'about');
        if ($ai) {
            $oldAi = setting('about_image', '');
            $stmt->execute(['about_image', $ai]);
            if ($oldAi !== '') { $f = ROOT_PATH . $oldAi; if (is_file($f) && str_contains($f, '/public/uploads/about/')) @unlink($f); }
        } elseif (!empty($_POST['about_image_remove'])) {
            $oldAi = setting('about_image', '');
            $stmt->execute(['about_image', '']);
            if ($oldAi !== '') { $f = ROOT_PATH . $oldAi; if (is_file($f) && str_contains($f, '/public/uploads/about/')) @unlink($f); }
        }
        Logger::info('admin.content', 'تحديث المحتوى', ['by' => auth()['username']]);
        redirect('/admin/content', ['success' => 'تم حفظ المحتوى.']);
    }

    // ── Forms ─────────────────────────────────────────────────
    public function forms(): void {
        $forms = $this->db->query("
            SELECT af.*,
              (SELECT COUNT(*) FROM form_questions  WHERE form_id=af.id) q_count,
              (SELECT COUNT(*) FROM form_submissions WHERE form_id=af.id) s_count,
              (SELECT COUNT(*) FROM form_submissions WHERE form_id=af.id AND status='pending') pending_c,
              (SELECT COUNT(*) FROM form_submissions WHERE form_id=af.id AND status='accepted') accepted_c
            FROM application_forms af ORDER BY status DESC, sort_order, updated_at DESC
        ")->fetchAll();
        render('admin.forms', ['forms' => $forms, 'pageTitle' => 'النماذج'], 'admin');
    }
    public function formNew(): void { $allForms = $this->db->query("SELECT id, title FROM application_forms ORDER BY title")->fetchAll(); render('admin.form_edit', ['form' => null, 'questions' => [], 'allForms' => $allForms, 'pageTitle' => 'نموذج جديد'], 'admin'); }
    public function formEdit(): void {
        $id = (int)($_GET['id'] ?? 0);
        $s  = $this->db->prepare("SELECT * FROM application_forms WHERE id=? LIMIT 1"); $s->execute([$id]); $form = $s->fetch();
        if (!$form) redirect('/admin/forms');
        $q = $this->db->prepare("SELECT * FROM form_questions WHERE form_id=? ORDER BY sort_order"); $q->execute([$id]);
        render('admin.form_edit', ['form' => $form, 'questions' => $q->fetchAll(), 'pageTitle' => 'تعديل: '.$form['title']], 'admin');
    }
    public function formSave(): void {
        verify_csrf();
        $id = (int)($_POST['form_id'] ?? 0); $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? ''); $status = in_array($_POST['status']??'',['open','closed'])?$_POST['status']:'closed';
        $prereq = (int)($_POST['prerequisite_form_id'] ?? 0) ?: null;
        if (!$title) redirect('/admin/forms', ['error' => 'العنوان مطلوب.']);
        $cover = handle_upload('cover_image', 'covers'); $uid = auth()['id'];
        if ($id) {
            $sql = "UPDATE application_forms SET title=?,description=?,status=?,prerequisite_form_id=?,updated_at=datetime('now')";
            $p   = [$title,$desc,$status,$prereq];
            if ($cover) { $sql .= ',cover_image=?'; $p[] = $cover; }
            $sql .= ' WHERE id=?'; $p[] = $id;
            $this->db->prepare($sql)->execute($p);
        } else {
            $this->db->prepare("INSERT INTO application_forms (title,description,status,prerequisite_form_id,cover_image,created_by) VALUES (?,?,?,?,?,?)")
                     ->execute([$title,$desc,$status,$prereq,$cover??'',$uid]);
            $id = (int)$this->db->lastInsertId();
        }
        Logger::info('admin.form', "حفظ نموذج #{$id}");
        redirect('/admin/forms/edit?id='.$id, ['success' => 'تم الحفظ.']);
    }
    public function formDelete(): void {
        verify_csrf();
        $id    = (int)($_POST['id'] ?? 0);
        // Use prepared statement — never interpolate IDs
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM form_submissions WHERE form_id=?");
        $countStmt->execute([$id]);
        $count = (int)$countStmt->fetchColumn();
        if ($count > 0) {
            $this->db->prepare("UPDATE application_forms SET status='closed' WHERE id=?")->execute([$id]);
            redirect('/admin/forms', ['success' => 'تم إغلاق النموذج (له تقديمات، لا يمكن حذفه).']);
        }
        $this->db->prepare("DELETE FROM application_forms WHERE id=?")->execute([$id]);
        Logger::info('admin.form', "حذف نموذج #{$id}", ['by' => auth()['username']]);
        redirect('/admin/forms', ['success' => 'تم الحذف.']);
    }
    public function formToggle(): void {
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $s  = $this->db->prepare("SELECT status FROM application_forms WHERE id=? LIMIT 1"); $s->execute([$id]); $f = $s->fetch();
        if (!$f) redirect('/admin/forms');
        $new = $f['status']==='open'?'closed':'open';
        $this->db->prepare("UPDATE application_forms SET status=?,updated_at=datetime('now') WHERE id=?")->execute([$new,$id]);
        redirect('/admin/forms', ['success' => 'تم تغيير الحالة إلى '.($new==='open'?'مفتوح':'مغلق').'.']);
    }
    public function questionSave(): void {
        verify_csrf();
        $fid   = (int)($_POST['form_id'] ?? 0);
        $qid   = (int)($_POST['q_id'] ?? 0);
        $label = trim($_POST['label'] ?? '');
        $type  = $_POST['type'] ?? 'short_text';
        $ph    = trim($_POST['placeholder'] ?? '');
        $req   = isset($_POST['is_required']) ? 1 : 0;
        $valid = ['short_text','long_text','number','phone','email','age','date','radio','checkbox','dropdown'];
        if (!$label || !in_array($type, $valid, true)) json_out(['error' => 'بيانات غير صالحة.'], 422);

        // Parse options for choice-type fields (radio/checkbox/dropdown)
        $choiceTypes = ['radio','checkbox','dropdown'];
        $optionsJson = null;
        if (in_array($type, $choiceTypes, true)) {
            $rawOpts = $_POST['options'] ?? '';
            // Options come as newline-separated text
            $lines = array_values(array_filter(array_map('trim', explode("\n", $rawOpts))));
            if (count($lines) < 2) json_out(['error' => 'يجب إدخال خيارين على الأقل (كل خيار في سطر).'], 422);
            // Sanitise: max 50 chars per option, max 30 options
            $lines = array_slice(array_map(fn($l) => mb_substr($l, 0, 100), $lines), 0, 30);
            $optionsJson = json_encode($lines, JSON_UNESCAPED_UNICODE);
        }

        if ($qid) {
            $this->db->prepare("UPDATE form_questions SET label=?,type=?,placeholder=?,options=?,is_required=? WHERE id=? AND form_id=?")
                     ->execute([$label, $type, $ph, $optionsJson, $req, $qid, $fid]);
        } else {
            // Use prepared statement — no raw $fid interpolation
            $maxStmt = $this->db->prepare("SELECT COALESCE(MAX(sort_order),0) FROM form_questions WHERE form_id=?");
            $maxStmt->execute([$fid]);
            $max = (int)$maxStmt->fetchColumn();
            $this->db->prepare("INSERT INTO form_questions (form_id,label,type,placeholder,options,is_required,sort_order) VALUES (?,?,?,?,?,?,?)")
                     ->execute([$fid, $label, $type, $ph, $optionsJson, $req, $max + 1]);
        }
        json_out(['success' => true]);
    }
    public function questionReorder(): void {
        verify_csrf(); $fid = (int)($_POST['form_id']??0);
        $s = $this->db->prepare("UPDATE form_questions SET sort_order=? WHERE id=? AND form_id=?");
        foreach (($_POST['order']??[]) as $i => $qid) $s->execute([$i,(int)$qid,$fid]);
        json_out(['success' => true]);
    }
    public function questionDelete(): void {
        verify_csrf();
        $this->db->prepare("DELETE FROM form_questions WHERE id=? AND form_id=?")->execute([(int)($_POST['q_id']??0),(int)($_POST['form_id']??0)]);
        json_out(['success' => true]);
    }

    // ── Submissions ───────────────────────────────────────────
    public function submissions(): void {
        $formId = (int)($_GET['form_id']??0); $sf = $_GET['status']??'';
        $where = 'WHERE 1=1'; $p = [];
        if ($formId) { $where .= ' AND fs.form_id=?'; $p[] = $formId; }
        if ($sf && in_array($sf,['pending','accepted','rejected'],true)) { $where .= ' AND fs.status=?'; $p[] = $sf; }
        $stmt = $this->db->prepare("
            SELECT fs.*, af.title form_title, af.status form_status,
                   u.display_name, u.username, u.email user_email, u.telegram_username
            FROM form_submissions fs
            LEFT JOIN application_forms af ON fs.form_id=af.id
            LEFT JOIN users u ON fs.user_id=u.id
            {$where} ORDER BY fs.submitted_at DESC
        ");
        $stmt->execute($p);
        $forms = $this->db->query("SELECT id,title,status FROM application_forms ORDER BY title")->fetchAll();
        render('admin.submissions', ['submissions' => $stmt->fetchAll(), 'forms' => $forms, 'formId' => $formId, 'statusFilt' => $sf, 'pageTitle' => 'الطلبات'], 'admin');
    }
    public function accepted(): void {
        $formId = (int)($_GET['form_id'] ?? 0);
        if ($formId) {
            $stmt = $this->db->prepare("
                SELECT fs.*, af.title form_title, u.display_name, u.username, u.email user_email, u.telegram_username
                FROM form_submissions fs
                LEFT JOIN application_forms af ON fs.form_id=af.id
                LEFT JOIN users u ON fs.user_id=u.id
                WHERE fs.status='accepted' AND fs.form_id=? ORDER BY fs.reviewed_at DESC
            ");
            $stmt->execute([$formId]);
        } else {
            $stmt = $this->db->query("
                SELECT fs.*, af.title form_title, u.display_name, u.username, u.email user_email, u.telegram_username
                FROM form_submissions fs
                LEFT JOIN application_forms af ON fs.form_id=af.id
                LEFT JOIN users u ON fs.user_id=u.id
                WHERE fs.status='accepted' ORDER BY fs.reviewed_at DESC
            ");
        }
        $forms = $this->db->query("SELECT id,title FROM application_forms ORDER BY title")->fetchAll();
        render('admin.accepted', ['submissions' => $stmt->fetchAll(), 'forms' => $forms, 'formId' => $formId, 'pageTitle' => 'المقبولون'], 'admin');
    }
    public function submissionReview(): void {
        verify_csrf();
        $sid    = (int)($_POST['submission_id']??0);
        $status = in_array($_POST['status']??'',['pending','accepted','rejected'],true)?$_POST['status']:'pending';
        $note   = trim($_POST['admin_note']??'');
        $uid    = auth()['id'];
        $this->db->prepare("UPDATE form_submissions SET status=?,admin_note=?,reviewed_by=?,reviewed_at=datetime('now') WHERE id=?")
                 ->execute([$status,$note,$uid,$sid]);
        Logger::info('admin.review', "مراجعة #{$sid}: {$status}", ['by' => auth()['username']]);
        $isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
               || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        if (!$isAjax) {
            $msg = $status === 'accepted' ? 'تم قبول الطلب.' : ($status === 'rejected' ? 'تم رفض الطلب.' : 'تم تحديث الحالة.');
            redirect('/admin/submissions/view?id=' . $sid, ['success' => $msg]);
        }
        json_out(['success' => true, 'status' => $status]);
    }
    public function submissionsExport(): void {
        $formId = (int)($_GET['form_id'] ?? 0);
        // Use prepared statement — never interpolate $formId into query
        if ($formId) {
            $stmt = $this->db->prepare("
                SELECT fs.id, af.title form_title, u.display_name, u.username, u.email user_email, u.telegram_username,
                       fs.status, fs.answers, fs.submitted_at, fs.admin_note
                FROM form_submissions fs
                LEFT JOIN application_forms af ON fs.form_id=af.id
                LEFT JOIN users u ON fs.user_id=u.id
                WHERE fs.form_id=? ORDER BY fs.submitted_at DESC
            ");
            $stmt->execute([$formId]);
        } else {
            $stmt = $this->db->query("
                SELECT fs.id, af.title form_title, u.display_name, u.username, u.email user_email, u.telegram_username,
                       fs.status, fs.answers, fs.submitted_at, fs.admin_note
                FROM form_submissions fs
                LEFT JOIN application_forms af ON fs.form_id=af.id
                LEFT JOIN users u ON fs.user_id=u.id
                ORDER BY fs.submitted_at DESC
            ");
        }
        $rows = $stmt->fetchAll();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="submissions_'.date('Ymd').'.csv"');
        header('X-Content-Type-Options: nosniff');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        $out = fopen('php://output', 'w');
        fputcsv($out, ['#','النموذج','الاسم','المستخدم','البريد','التيليغرام','الحالة','التاريخ','ملاحظة','الإجابات']);

        // CSV injection prevention: prefix formula-trigger characters with a tab
        $sanitizeCsv = static function (mixed $v): string {
            $s = (string)($v ?? '');
            // Excel/LibreOffice interpret cells starting with =, +, -, @ as formulas
            if ($s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                $s = "\t" . $s;
            }
            return $s;
        };

        foreach ($rows as $r) {
            $ans  = json_decode($r['answers'], true) ?? [];
            $flat = implode(' | ', array_map(
                fn($a) => ($a['label'] ?? '') . ': ' . (is_array($a['value'] ?? '') ? implode(', ', $a['values'] ?? []) : ($a['value'] ?? '')),
                array_filter($ans, fn($a) => isset($a['label']))
            ));
            fputcsv($out, array_map($sanitizeCsv, [
                $r['id'], $r['form_title'], $r['display_name'] ?? 'زائر',
                $r['username'] ?? '—', $r['user_email'] ?? '—',
                $r['telegram_username'] ?? '—', $r['status'],
                $r['submitted_at'], $r['admin_note'] ?? '', $flat,
            ]));
        }
        fclose($out); exit;
    }

    // ── News ──────────────────────────────────────────────────
    public function news(): void {
        render('admin.news', ['news' => $this->db->query("SELECT * FROM news ORDER BY created_at DESC")->fetchAll(), 'pageTitle' => 'الفعاليات'], 'admin');
    }
    public function newsNew(): void { render('admin.news_edit', ['item' => null, 'pageTitle' => 'فعالية جديدة'], 'admin'); }
    public function newsEdit(): void {
        $id = (int)($_GET['id']??0); $s = $this->db->prepare("SELECT * FROM news WHERE id=? LIMIT 1"); $s->execute([$id]); $item = $s->fetch();
        if (!$item) redirect('/admin/news');
        render('admin.news_edit', ['item' => $item, 'pageTitle' => 'تعديل: '.$item['title']], 'admin');
    }
    public function newsSave(): void {
        verify_csrf();
        $id  = (int)($_POST['news_id']??0);
        $title = trim($_POST['title']??'');
        $desc  = trim($_POST['description']??'');
        $pub   = isset($_POST['publish']) ? 1 : 0;
        $cat   = trim($_POST['category'] ?? '');
        if (!$title||!$desc) redirect('/admin/news', ['error' => 'العنوان والمحتوى مطلوبان.']);
        $cover = handle_upload('cover_image','news'); $uid = auth()['id'];
        if ($id) {
            $sql = "UPDATE news SET title=?,description=?,is_published=?,category=?,updated_at=datetime('now')";
            $p   = [$title,$desc,$pub,$cat];
            if ($pub) $sql .= ",published_at=COALESCE(published_at,datetime('now'))";
            if ($cover) { $sql .= ",cover_image=?"; $p[] = $cover; }
            $sql .= " WHERE id=?"; $p[] = $id;
            $this->db->prepare($sql)->execute($p);
        } else {
            $this->db->prepare("INSERT INTO news (title,description,is_published,category,cover_image,created_by".($pub?",published_at":"").") VALUES (?,?,?,?,?,?".($pub?",datetime('now')":"").")")
                     ->execute([$title,$desc,$pub,$cat,$cover??'',$uid]);
        }
        Logger::info('admin.news', "حفظ فعالية: {$title}");
        redirect('/admin/news', ['success' => 'تم الحفظ.']);
    }
    public function newsDelete(): void {
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $this->db->prepare("DELETE FROM news WHERE id=?")->execute([$id]);
        Logger::info('admin.news', "حذف فعالية #{$id}", ['by' => auth()['username']]);
        redirect('/admin/news', ['success' => 'تم الحذف.']);
    }

    // ── ACHIEVEMENTS (الإنجازات) ──────────────────────────────────
    public function achievements(): void {
        $rows = $this->db->query("SELECT * FROM achievements ORDER BY sort_order ASC, id DESC")->fetchAll();
        foreach ($rows as &$r) { $im = json_decode((string)$r['images'], true); $r['images'] = is_array($im) ? array_values($im) : []; }
        $settings = $this->db->query("SELECT key, value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        render('admin.achievements', ['items' => $rows, 'settings' => $settings, 'pageTitle' => 'الإنجازات'], 'admin');
    }

    /** Upload several files from <input name="images[]">; returns the stored paths. */
    private function uploadMany(string $field, string $subdir, int $max = 12): array {
        $out = [];
        if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'] ?? null)) return $out;
        $n = count($_FILES[$field]['name']);
        for ($i = 0; $i < $n && count($out) < $max; $i++) {
            if (($_FILES[$field]['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
            $_FILES['__one'] = [
                'name' => $_FILES[$field]['name'][$i], 'type' => $_FILES[$field]['type'][$i], 'tmp_name' => $_FILES[$field]['tmp_name'][$i],
                'error' => $_FILES[$field]['error'][$i], 'size' => $_FILES[$field]['size'][$i],
            ];
            $p = handle_upload('__one', $subdir);
            if ($p) $out[] = $p;
        }
        unset($_FILES['__one']);
        return $out;
    }

    public function achievementSave(): void {
        verify_csrf();
        if (isset($_POST['page_title'])) {
            $this->db->prepare("INSERT OR REPLACE INTO settings (key,value,updated_at) VALUES ('achievements_title',?,datetime('now'))")
                     ->execute([mb_substr(trim((string)$_POST['page_title']), 0, 80)]);
        }
        if (isset($_POST['carousel_title'])) {
            $this->db->prepare("INSERT OR REPLACE INTO settings (key,value,updated_at) VALUES ('carousel_title',?,datetime('now'))")
                     ->execute([mb_substr(trim((string)$_POST['carousel_title']), 0, 80)]);
        }
        if (isset($_POST['title'])) {
            $id    = (int)($_POST['id'] ?? 0);
            $title = mb_substr(trim((string)$_POST['title']), 0, 160);
            $body  = mb_substr(trim((string)($_POST['body'] ?? '')), 0, 8000);
            $when  = mb_substr(trim((string)($_POST['when_text'] ?? '')), 0, 60);
            $order = (int)($_POST['sort_order'] ?? 0);
            $vis   = isset($_POST['is_visible']) ? 1 : 0;
            if ($title === '') redirect('/admin/achievements', ['error' => 'اكتب عنوان الإنجاز.']);
            $existing = [];
            if ($id > 0) {
                $cur = (string)$this->db->query("SELECT images FROM achievements WHERE id=" . $id)->fetchColumn();
                $ex = json_decode($cur, true); $existing = is_array($ex) ? array_values($ex) : [];
                foreach ((array)($_POST['remove_img'] ?? []) as $rm) {
                    $k = array_search((string)$rm, $existing, true);
                    if ($k !== false) {
                        $f = ROOT_PATH . $existing[$k];
                        if (is_file($f) && str_contains($f, '/public/uploads/achievements/')) @unlink($f);
                        unset($existing[$k]);
                    }
                }
                $existing = array_values($existing);
            }
            $imgs = array_slice(array_merge($existing, $this->uploadMany('images', 'achievements')), 0, 12);
            $json = json_encode($imgs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($id > 0) {
                $this->db->prepare("UPDATE achievements SET title=?,body=?,when_text=?,images=?,sort_order=?,is_visible=? WHERE id=?")->execute([$title,$body,$when,$json,$order,$vis,$id]);
            } else {
                $this->db->prepare("INSERT INTO achievements (title,body,when_text,images,sort_order,is_visible) VALUES (?,?,?,?,?,?)")->execute([$title,$body,$when,$json,$order,$vis]);
            }
        }
        redirect('/admin/achievements', ['success' => 'تم الحفظ.']);
    }

    public function achievementDelete(): void {
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $cur = (string)$this->db->query("SELECT images FROM achievements WHERE id=" . $id)->fetchColumn();
        foreach ((array)json_decode($cur, true) as $im) { $f = ROOT_PATH . $im; if (is_file($f) && str_contains($f, '/public/uploads/achievements/')) @unlink($f); }
        $this->db->prepare("DELETE FROM achievements WHERE id=?")->execute([$id]);
        redirect('/admin/achievements', ['success' => 'تم الحذف.']);
    }

    // ── ARTICLES (المقالات / المجلة) ────────────────────────────
    public function articles(): void {
        $this->checkPerm('manage_content');
        $rows = $this->db->query("SELECT a.id,a.title,a.description,a.cover_image,a.created_at,u.display_name,u.username,
                (SELECT COUNT(*) FROM article_likes l WHERE l.article_id=a.id) AS likes
                FROM articles a LEFT JOIN users u ON u.id=a.author_id ORDER BY a.created_at DESC")->fetchAll();
        render('admin.articles', ['articles' => $rows, 'pageTitle' => 'المقالات'], 'admin');
    }

    public function articleEdit(): void {
        $this->checkPerm('manage_content');
        $id = (int)($_GET['id'] ?? 0);
        $a = null;
        if ($id > 0) {
            $st = $this->db->prepare("SELECT * FROM articles WHERE id=? LIMIT 1"); $st->execute([$id]);
            $a = $st->fetch() ?: null;
            if (!$a) redirect('/admin/articles', ['error' => 'المقال غير موجود.']);
        }
        $authors = $this->db->query("SELECT id,display_name,username FROM users WHERE is_active=1 AND (is_volunteer=1 OR role IN ('admin','super_admin')) ORDER BY display_name")->fetchAll();
        render('admin.article_edit', ['a' => $a, 'authors' => $authors, 'pageTitle' => $a ? 'تعديل مقال' : 'مقال جديد'], 'admin');
    }

    public function articleSave(): void {
        verify_csrf(); $this->checkPerm('manage_content');
        $id     = (int)($_POST['id'] ?? 0);
        $title  = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 160);
        $desc   = mb_substr(trim((string)($_POST['description'] ?? '')), 0, 240);
        $body   = mb_substr(trim((string)($_POST['body'] ?? '')), 0, 30000);
        $author = (int)($_POST['author_id'] ?? 0);
        $back   = $id ? '/admin/articles/edit?id=' . $id : '/admin/articles/edit';
        if ($title === '' || $body === '') redirect($back, ['error' => 'العنوان ونص المقال مطلوبان.']);
        $chk = $this->db->prepare("SELECT 1 FROM users WHERE id=?"); $chk->execute([$author]);
        if (!$chk->fetchColumn()) $author = (int)auth()['id'];
        if ($desc === '') $desc = mb_substr(trim(preg_replace('/\s+/u', ' ', str_replace(['**', "\n"], ['', ' '], $body))), 0, 200);
        $cover = handle_upload('cover', 'magazine');
        if ($id > 0) {
            $st = $this->db->prepare("SELECT cover_image FROM articles WHERE id=?"); $st->execute([$id]);
            $old = $st->fetchColumn();
            if ($old === false) redirect('/admin/articles', ['error' => 'المقال غير موجود.']);
            $new = $cover ?: (string)$old;
            if (!empty($_POST['remove_cover']) && !$cover) $new = '';
            $this->db->prepare("UPDATE articles SET author_id=?,title=?,description=?,cover_image=?,body=?,updated_at=datetime('now') WHERE id=?")
                     ->execute([$author, $title, $desc, $new, $body, $id]);
            if ((string)$old !== '' && (string)$old !== $new) $this->unlinkArticleCover((string)$old);
        } else {
            $this->db->prepare("INSERT INTO articles (author_id,title,description,cover_image,body) VALUES (?,?,?,?,?)")
                     ->execute([$author, $title, $desc, (string)$cover, $body]);
            $id = (int)$this->db->lastInsertId();
        }
        Logger::info('admin.article', "مقال #{$id}", ['by' => auth()['username']]);
        redirect('/admin/articles', ['success' => 'تم حفظ المقال.']);
    }

    public function articleDelete(): void {
        verify_csrf(); $this->checkPerm('manage_content');
        $id = (int)($_POST['id'] ?? 0);
        $st = $this->db->prepare("SELECT cover_image FROM articles WHERE id=?"); $st->execute([$id]);
        $cover = $st->fetchColumn();
        $this->db->prepare("DELETE FROM article_likes WHERE article_id=?")->execute([$id]);
        $this->db->prepare("DELETE FROM articles WHERE id=?")->execute([$id]);
        if ($cover) $this->unlinkArticleCover((string)$cover);
        Logger::info('admin.article', "حذف مقال #{$id}", ['by' => auth()['username']]);
        redirect('/admin/articles', ['success' => 'تم حذف المقال.']);
    }

    private function unlinkArticleCover(string $rel): void {
        $f = ROOT_PATH . $rel;
        if (is_file($f) && str_contains($f, '/public/uploads/magazine/')) @unlink($f);
    }

    // ── CAMPAIGNS (carousel) ─────────────────────────────────────
    public function campaigns(): void {
        $items = $this->db->query("SELECT * FROM campaigns ORDER BY sort_order ASC, id DESC")->fetchAll();
        $settings = $this->db->query("SELECT key, value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        render('admin.campaigns', ['items' => $items, 'settings' => $settings, 'pageTitle' => 'الحملات'], 'admin');
    }

    public function campaignSave(): void {
        verify_csrf();
        if (isset($_POST['carousel_title'])) {
            $this->db->prepare("INSERT OR REPLACE INTO settings (key,value,updated_at) VALUES ('carousel_title',?,datetime('now'))")
                     ->execute([mb_substr(trim((string)$_POST['carousel_title']), 0, 80)]);
        }
        if (isset($_POST['title'])) {
            $id    = (int)($_POST['id'] ?? 0);
            $title = mb_substr(trim((string)$_POST['title']), 0, 120);
            $link  = trim((string)($_POST['link'] ?? ''));
            if ($link !== '' && !preg_match('#^(https?://|/)#i', $link)) $link = '';
            $order = (int)($_POST['sort_order'] ?? 0);
            $vis   = isset($_POST['is_visible']) ? 1 : 0;
            $img   = handle_upload('image', 'campaigns');
            if ($id > 0) {
                $old = (string)$this->db->query("SELECT image FROM campaigns WHERE id=" . $id)->fetchColumn();
                if (!$img) $img = $old;
                $this->db->prepare("UPDATE campaigns SET title=?,image=?,link=?,sort_order=?,is_visible=? WHERE id=?")->execute([$title,$img,$link,$order,$vis,$id]);
                if ($old !== '' && $old !== $img) { $f = ROOT_PATH . $old; if (is_file($f) && str_contains($f, '/public/uploads/campaigns/')) @unlink($f); }
            } else {
                if (!$img) redirect('/admin/campaigns', ['error' => 'ارفع صورة للحملة.']);
                $this->db->prepare("INSERT INTO campaigns (title,image,link,sort_order,is_visible) VALUES (?,?,?,?,?)")->execute([$title,$img,$link,$order,$vis]);
            }
        }
        redirect('/admin/campaigns', ['success' => 'تم الحفظ.']);
    }

    public function campaignDelete(): void {
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $old = (string)$this->db->query("SELECT image FROM campaigns WHERE id=" . $id)->fetchColumn();
        $this->db->prepare("DELETE FROM campaigns WHERE id=?")->execute([$id]);
        if ($old !== '') { $f = ROOT_PATH . $old; if (is_file($f) && str_contains($f, '/public/uploads/campaigns/')) @unlink($f); }
        redirect('/admin/campaigns', ['success' => 'تم الحذف.']);
    }

    // ── TEAM (الإدارة العليا) ───────────────────────────────────
    public function team(): void {
        $members = $this->db->query("SELECT * FROM team_members ORDER BY sort_order ASC, id ASC")->fetchAll();
        $settings = $this->db->query("SELECT key, value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        render('admin.team', ['members' => $members, 'settings' => $settings, 'pageTitle' => 'الإدارة العليا'], 'admin');
    }

    public function teamSave(): void {
        verify_csrf();
        // Section title (optional, saved with the page)
        if (isset($_POST['team_title'])) {
            $this->db->prepare("INSERT OR REPLACE INTO settings (key,value,updated_at) VALUES ('team_title',?,datetime('now'))")
                     ->execute([mb_substr(trim((string)$_POST['team_title']), 0, 80)]);
        }
        if (isset($_POST['name'])) {
            $id    = (int)($_POST['id'] ?? 0);
            $name  = mb_substr(trim((string)$_POST['name']), 0, 80);
            $role  = mb_substr(trim((string)($_POST['role'] ?? '')), 0, 80);
            $bio   = mb_substr(trim((string)($_POST['bio'] ?? '')), 0, 400);
            if ($name === '' && $role === '') redirect('/admin/team', ['error' => 'اكتب اسم أو منصب.']);
            $links = [];
            foreach (['facebook','twitter','instagram','discord','telegram'] as $k) {
                $v = trim((string)($_POST[$k] ?? ''));
                $links[$k] = ($v !== '' && preg_match('#^https?://#i', $v)) ? mb_substr($v, 0, 300) : '';
            }
            $order = (int)($_POST['sort_order'] ?? 0);
            $vis   = isset($_POST['is_visible']) ? 1 : 0;
            $avatar = handle_upload('avatar', 'team');
            if ($id > 0) {
                $old = (string)$this->db->query("SELECT avatar FROM team_members WHERE id=" . $id)->fetchColumn();
                if (!empty($_POST['remove_avatar']) && !$avatar) $avatar = '';
                elseif (!$avatar) $avatar = $old;
                $this->db->prepare("UPDATE team_members SET name=?,role=?,bio=?,avatar=?,facebook=?,twitter=?,instagram=?,discord=?,telegram=?,sort_order=?,is_visible=? WHERE id=?")
                     ->execute([$name,$role,$bio,$avatar,$links['facebook'],$links['twitter'],$links['instagram'],$links['discord'],$links['telegram'],$order,$vis,$id]);
                if ($old !== '' && $old !== $avatar) { $f = ROOT_PATH . $old; if (is_file($f) && str_contains($f, '/public/uploads/team/')) @unlink($f); }
            } else {
                $this->db->prepare("INSERT INTO team_members (name,role,bio,avatar,facebook,twitter,instagram,discord,telegram,sort_order,is_visible) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
                     ->execute([$name,$role,$bio,(string)$avatar,$links['facebook'],$links['twitter'],$links['instagram'],$links['discord'],$links['telegram'],$order,$vis]);
            }
            Logger::info('admin.team', 'حفظ عضو فريق', ['by' => auth()['username']]);
        }
        redirect('/admin/team', ['success' => 'تم الحفظ.']);
    }

    public function teamDelete(): void {
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $old = (string)$this->db->query("SELECT avatar FROM team_members WHERE id=" . $id)->fetchColumn();
        $this->db->prepare("DELETE FROM team_members WHERE id=?")->execute([$id]);
        if ($old !== '') { $f = ROOT_PATH . $old; if (is_file($f) && str_contains($f, '/public/uploads/team/')) @unlink($f); }
        redirect('/admin/team', ['success' => 'تم الحذف.']);
    }

    // ── CHALLENGES ────────────────────────────────────────────
    public function challenges(): void {
        $chs = $this->db->query("
            SELECT c.*,
              (SELECT COUNT(*) FROM challenge_questions WHERE challenge_id=c.id) q_count,
              (SELECT COUNT(*) FROM challenge_attempts  WHERE challenge_id=c.id AND status='completed') completions,
              (SELECT COUNT(*) FROM challenge_attempts  WHERE challenge_id=c.id AND passed=1) passes
            FROM challenges c ORDER BY c.created_at DESC
        ")->fetchAll();
        render('admin.challenges', ['challenges' => $chs, 'pageTitle' => 'التحديات'], 'admin');
    }

    public function challengeNew(): void {
        render('admin.challenge_edit', ['ch' => null, 'questions' => [], 'optsByQ' => [], 'pageTitle' => 'تحدي جديد'], 'admin');
    }

    public function challengeEdit(): void {
        $id = (int)($_GET['id']??0);
        $s  = $this->db->prepare("SELECT * FROM challenges WHERE id=? LIMIT 1"); $s->execute([$id]); $ch = $s->fetch();
        if (!$ch) redirect('/admin/challenges');
        $qStmt = $this->db->prepare("SELECT cq.*, GROUP_CONCAT(co.option_text||'|||'||co.is_correct||'|||'||co.sort_order ORDER BY co.sort_order) opts FROM challenge_questions cq LEFT JOIN challenge_options co ON co.question_id=cq.id WHERE cq.challenge_id=? GROUP BY cq.id ORDER BY cq.sort_order");
        $qStmt->execute([$id]);
        $questions = $qStmt->fetchAll();
        // Load options separately for the editor
        $qids = array_column($questions,'id');
        $optsByQ = [];
        if ($qids) {
            $ph = implode(',',array_fill(0,count($qids),'?'));
            $oStmt = $this->db->prepare("SELECT * FROM challenge_options WHERE question_id IN ({$ph}) ORDER BY sort_order");
            $oStmt->execute($qids);
            foreach ($oStmt->fetchAll() as $o) $optsByQ[$o['question_id']][] = $o;
        }
        render('admin.challenge_edit', ['ch' => $ch, 'questions' => $questions, 'optsByQ' => $optsByQ, 'pageTitle' => 'تعديل: '.$ch['title']], 'admin');
    }

    public function challengeSave(): void {
        verify_csrf();
        $id       = (int)($_POST['challenge_id']??0);
        $title    = trim($_POST['title']??'');
        $desc     = trim($_POST['description']??'');
        $cat      = trim($_POST['category']??'عام');
        $passing  = max(1, min(100, (int)($_POST['passing_score']??80)));
        $limit    = (int)($_POST['time_limit']??0) ?: null;
        $status   = in_array($_POST['status']??'',['draft','published','archived'],true)?$_POST['status']:'draft';
        $rand     = isset($_POST['randomize'])?1:0;
        if (!$title) redirect('/admin/challenges', ['error' => 'العنوان مطلوب.']);

        $cover = handle_upload('cover_image','challenges'); $uid = auth()['id'];
        if ($id) {
            $sql = "UPDATE challenges SET title=?,description=?,category=?,passing_score=?,time_limit=?,status=?,randomize=?,updated_at=datetime('now')";
            $p   = [$title,$desc,$cat,$passing,$limit,$status,$rand];
            if ($cover) { $sql .= ',cover_image=?'; $p[] = $cover; }
            $sql .= ' WHERE id=?'; $p[] = $id;
            $this->db->prepare($sql)->execute($p);
        } else {
            $this->db->prepare("INSERT INTO challenges (title,description,category,passing_score,time_limit,status,randomize,cover_image,created_by) VALUES (?,?,?,?,?,?,?,?,?)")
                     ->execute([$title,$desc,$cat,$passing,$limit,$status,$rand,$cover??'',$uid]);
            $id = (int)$this->db->lastInsertId();
        }
        Logger::info('admin.challenge', "حفظ تحدي #{$id}: {$title}");
        redirect('/admin/challenges/edit?id='.$id, ['success' => 'تم حفظ التحدي.']);
    }

    public function challengeQuestionSave(): void {
        verify_csrf();
        $cid     = (int)($_POST['challenge_id']??0);
        $qid     = (int)($_POST['q_id']??0);
        $question = trim($_POST['question']??'');
        $points  = max(1, (int)($_POST['points']??1));
        $options = $_POST['options'] ?? [];
        $corrects = $_POST['correct'] ?? [];

        if (!$question) json_out(['error' => 'نص السؤال مطلوب.'], 422);
        if (count($options) < 2) json_out(['error' => 'يجب إضافة خيارين على الأقل.'], 422);
        if (empty($corrects)) json_out(['error' => 'يجب تحديد إجابة صحيحة واحدة على الأقل.'], 422);

        if ($qid) {
            $this->db->prepare("UPDATE challenge_questions SET question=?,points=? WHERE id=? AND challenge_id=?")
                     ->execute([$question,$points,$qid,$cid]);
            $this->db->prepare("DELETE FROM challenge_options WHERE question_id=?")->execute([$qid]);
        } else {
            $maxStmt = $this->db->prepare("SELECT COALESCE(MAX(sort_order),0) FROM challenge_questions WHERE challenge_id=?");
            $maxStmt->execute([$cid]);
            $max = (int)$maxStmt->fetchColumn();
            $this->db->prepare("INSERT INTO challenge_questions (challenge_id,question,points,sort_order) VALUES (?,?,?,?)")
                     ->execute([$cid,$question,$points,$max+1]);
            $qid = (int)$this->db->lastInsertId();
        }

        $oStmt = $this->db->prepare("INSERT INTO challenge_options (question_id,option_text,is_correct,sort_order) VALUES (?,?,?,?)");
        foreach (array_values($options) as $i => $optText) {
            $txt = trim((string)$optText);
            if ($txt === '') continue;
            $isCorrect = in_array((string)$i, (array)$corrects, true) ? 1 : 0;
            $oStmt->execute([$qid, $txt, $isCorrect, $i]);
        }
        json_out(['success' => true, 'q_id' => $qid]);
    }

    public function challengeQuestionDelete(): void {
        verify_csrf();
        $qid = (int)($_POST['q_id']??0); $cid = (int)($_POST['challenge_id']??0);
        $this->db->prepare("DELETE FROM challenge_questions WHERE id=? AND challenge_id=?")->execute([$qid,$cid]);
        json_out(['success' => true]);
    }

    public function challengeQuestionReorder(): void {
        verify_csrf();
        $cid = (int)($_POST['challenge_id']??0);
        $s   = $this->db->prepare("UPDATE challenge_questions SET sort_order=? WHERE id=? AND challenge_id=?");
        foreach (($_POST['order']??[]) as $i => $qid) $s->execute([$i,(int)$qid,$cid]);
        json_out(['success' => true]);
    }

    public function challengeDelete(): void {
        verify_csrf();
        $id = (int)($_POST['id'] ?? 0);
        $this->db->prepare("DELETE FROM challenges WHERE id=?")->execute([$id]);
        Logger::info('admin.challenge', "حذف تحدي #{$id}", ['by' => auth()['username']]);
        redirect('/admin/challenges', ['success' => 'تم الحذف.']);
    }

    public function challengeCompletions(): void {
        $id  = (int)($_GET['id'] ?? 0);
        $ch  = null;
        if ($id) {
            $s = $this->db->prepare("SELECT * FROM challenges WHERE id=? LIMIT 1");
            $s->execute([$id]); $ch = $s->fetch();
        }
        // Use parameterized query — no raw interpolation
        $sql = "SELECT ca.*, u.display_name, u.username, u.email, u.telegram_username, c.title ch_title
                FROM challenge_attempts ca
                JOIN users u ON ca.user_id=u.id
                JOIN challenges c ON ca.challenge_id=c.id
                WHERE ca.passed=1" . ($id ? " AND ca.challenge_id=?" : "") . "
                ORDER BY ca.completed_at DESC";
        $stmt = $this->db->prepare($sql);
        $id ? $stmt->execute([$id]) : $stmt->execute([]);
        $chs = $this->db->query("SELECT id,title FROM challenges ORDER BY title")->fetchAll();
        render('admin.challenge_completions', ['completions' => $stmt->fetchAll(), 'challenges' => $chs, 'filterCh' => $ch, 'filterId' => $id, 'pageTitle' => 'متممو التحديات'], 'admin');
    }

    // ── Users ─────────────────────────────────────────────────
    public function users(): void {
        render('admin.users', ['users' => $this->db->query("SELECT id,display_name,username,telegram_username,email,role,is_active,two_fa_enabled,last_login_at,created_at FROM users ORDER BY created_at DESC")->fetchAll(), 'pageTitle' => 'المستخدمون'], 'admin');
    }
    public function userRoleUpdate(): void {
        verify_csrf(); require_super_admin();
        $id = (int)($_POST['user_id']??0); $role = in_array($_POST['role']??'',['user','moderator','admin','super_admin'],true)?$_POST['role']:'user';
        $me = auth();
        if ($id === (int)$me['id']) redirect('/admin/users', ['error' => 'لا يمكنك تغيير دورك الخاص.']);
        $this->db->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role,$id]);
        Logger::info('admin.users', "تغيير دور #{$id} إلى {$role}", ['by' => $me['username']]);
        redirect('/admin/users', ['success' => 'تم التحديث.']);
    }
    public function userToggle(): void {
        verify_csrf(); require_super_admin();
        $id = (int)($_POST['user_id']??0); $me = auth();
        if ($id === (int)$me['id']) redirect('/admin/users', ['error' => 'لا يمكنك تعطيل حسابك.']);
        $this->db->prepare("UPDATE users SET is_active=1-is_active WHERE id=?")->execute([$id]);
        Logger::info('admin.users', "تبديل حالة المستخدم #{$id}", ['by' => $me['username']]);
        redirect('/admin/users', ['success' => 'تم تحديث الحالة.']);
    }

    public function userEditForm(): void {
        require_super_admin();
        $id = (int)($_GET['id'] ?? 0);
        $s  = $this->db->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
        $s->execute([$id]); $u = $s->fetch();
        if (!$u) redirect('/admin/users', ['error' => 'المستخدم غير موجود.']);
        $me = auth();
        if ($u['id'] === (int)$me['id']) redirect('/admin/users', ['error' => 'استخدم صفحة حسابك الشخصي.']);
        render('admin.user_edit', ['u' => $u, 'pageTitle' => 'تعديل: ' . $u['display_name']], 'admin');
    }

    public function userEdit(): void {
        verify_csrf(); require_super_admin();
        $id    = (int)($_POST['user_id'] ?? 0);
        $me    = auth();
        if ($id === (int)$me['id']) redirect('/admin/users', ['error' => 'استخدم صفحة حسابك الشخصي.']);

        $name  = trim($_POST['display_name'] ?? '');
        $uname = trim(strtolower($_POST['username'] ?? ''));
        $tg    = trim($_POST['telegram_username'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $role  = in_array($_POST['role']??'',['user','moderator','admin','super_admin'],true) ? $_POST['role'] : 'user';

        if (mb_strlen($name) < 2) redirect('/admin/users/edit?id='.$id, ['error' => 'الاسم قصير جدا.']);
        if (!preg_match('/^[a-z0-9_]{3,30}$/', $uname)) redirect('/admin/users/edit?id='.$id, ['error' => 'اسم المستخدم غير صالح.']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) redirect('/admin/users/edit?id='.$id, ['error' => 'البريد الإلكتروني غير صالح.']);

        // Check uniqueness
        $chk = $this->db->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id!=? LIMIT 1");
        $chk->execute([$uname, $email, $id]);
        if ($chk->fetch()) redirect('/admin/users/edit?id='.$id, ['error' => 'اسم المستخدم أو البريد مستخدم من حساب آخر.']);

        $tgFormatted = $tg ? (str_starts_with($tg,'@') ? $tg : '@'.$tg) : null;
        $this->db->prepare("UPDATE users SET display_name=?,username=?,telegram_username=?,email=?,role=?,updated_at=datetime('now') WHERE id=?")
                 ->execute([$name, $uname, $tgFormatted, $email, $role, $id]);
        Logger::info('admin.users', "تعديل بيانات المستخدم #{$id}", ['by' => $me['username']]);
        redirect('/admin/users', ['success' => 'تم تحديث بيانات المستخدم.']);
    }

    public function userPassword(): void {
        verify_csrf(); require_super_admin();
        $id  = (int)($_POST['user_id'] ?? 0);
        $me  = auth();
        if ($id === (int)$me['id']) redirect('/admin/users', ['error' => 'غير كلمة مرورك من صفحة حسابك.']);

        $pass    = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($pass) < 8) redirect('/admin/users/edit?id='.$id, ['error' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.']);
        if (!preg_match('/[A-Z]/', $pass)) redirect('/admin/users/edit?id='.$id, ['error' => 'يجب أن تحتوي كلمة المرور على حرف كبير.']);
        if (!preg_match('/[0-9]/', $pass)) redirect('/admin/users/edit?id='.$id, ['error' => 'يجب أن تحتوي كلمة المرور على رقم.']);
        if ($pass !== $confirm) redirect('/admin/users/edit?id='.$id, ['error' => 'كلمتا المرور غير متطابقتين.']);

        $this->db->prepare("UPDATE users SET password=?,updated_at=datetime('now') WHERE id=?")
                 ->execute([password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]), $id]);
        // Invalidate all sessions for this user for security
        $this->db->prepare("DELETE FROM sessions WHERE user_id=?")->execute([$id]);
        Logger::warning('admin.users', "تغيير كلمة مرور المستخدم #{$id}", ['by' => $me['username']]);
        redirect('/admin/users', ['success' => 'تم تغيير كلمة المرور وإلغاء كل جلساته.']);
    }

    public function userDelete(): void {
        verify_csrf(); require_super_admin();
        $id  = (int)($_POST['user_id'] ?? 0);
        $me  = auth();
        if ($id === (int)$me['id']) redirect('/admin/users', ['error' => 'لا يمكنك حذف حسابك.']);

        // Check if user exists
        $s = $this->db->prepare("SELECT role,username FROM users WHERE id=? LIMIT 1");
        $s->execute([$id]); $target = $s->fetch();
        if (!$target) redirect('/admin/users', ['error' => 'المستخدم غير موجود.']);

        // Don't allow deleting other super_admins (only self-delete by super_admin possible, blocked above)
        if ($target['role'] === 'super_admin') redirect('/admin/users', ['error' => 'لا يمكن حذف حساب مشرف أعلى.']);

        // Soft approach: delete sessions, anonymize personal data, mark deleted
        // Hard delete: remove user (DB cascades handle related data)
        $this->db->prepare("DELETE FROM sessions WHERE user_id=?")->execute([$id]);
        $this->db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        Logger::warning('admin.users', "حذف المستخدم #{$id}: {$target['username']}", ['by' => $me['username']]);
        redirect('/admin/users', ['success' => 'تم حذف المستخدم نهائيا.']);
    }

    public function submissionDelete(): void {
        verify_csrf(); require_admin();
        $id  = (int)($_POST['submission_id'] ?? 0);
        $me  = auth();
        // Any admin/moderator can delete submissions
        $s = $this->db->prepare("SELECT id, form_id FROM form_submissions WHERE id=? LIMIT 1");
        $s->execute([$id]); $sub = $s->fetch();
        if (!$sub) redirect('/admin/submissions', ['error' => 'الطلب غير موجود.']);

        $this->db->prepare("DELETE FROM form_submissions WHERE id=?")->execute([$id]);
        Logger::warning('admin.submissions', "حذف تقديم #{$id}", ['by' => $me['username']]);
        redirect('/admin/submissions', ['success' => 'تم حذف التقديم نهائيا.']);
    }

    // ── Certificates ──────────────────────────────────────────
    public function certificates(): void {
        $certs = $this->db->query("SELECT c.*,u.display_name,u.email,u.telegram_username,af.title form_title,ch.title challenge_title,co.title course_title FROM certificates c JOIN users u ON c.user_id=u.id LEFT JOIN application_forms af ON c.form_id=af.id LEFT JOIN challenges ch ON c.challenge_id=ch.id LEFT JOIN courses co ON c.course_id=co.id ORDER BY c.issued_at DESC")->fetchAll();
        $users = $this->db->query("SELECT id,display_name,username,email FROM users WHERE is_active=1 ORDER BY display_name")->fetchAll();
        $forms = $this->db->query("SELECT id,title FROM application_forms ORDER BY title")->fetchAll();
        $chs   = $this->db->query("SELECT id,title FROM challenges ORDER BY title")->fetchAll();
        $courses = $this->db->query("SELECT id,title FROM courses ORDER BY title")->fetchAll();
        render('admin.certificates', ['certs' => $certs, 'users' => $users, 'forms' => $forms, 'challenges' => $chs, 'courses' => $courses, 'pageTitle' => 'الشهادات'], 'admin');
    }
    public function certificateIssue(): void {
        $uid  = (int)($_GET['user_id']??0);
        $fid  = (int)($_GET['form_id']??0);
        $sid  = (int)($_GET['sub_id']??0);
        $chid = (int)($_GET['challenge_id']??0);
        $coid = (int)($_GET['course_id']??0);

        // Auto-fill title from challenge or form name
        $preTitle = '';
        if ($coid) {
            $s = $this->db->prepare("SELECT title FROM courses WHERE id=? LIMIT 1");
            $s->execute([$coid]);
            $row = $s->fetch();
            if ($row) $preTitle = 'شهادة إتمام كورس: ' . $row['title'];
        } elseif ($chid) {
            $s = $this->db->prepare("SELECT title FROM challenges WHERE id=? LIMIT 1");
            $s->execute([$chid]);
            $row = $s->fetch();
            if ($row) $preTitle = 'شهادة إتمام تحدي: ' . $row['title'];
        } elseif ($fid) {
            $s = $this->db->prepare("SELECT title FROM application_forms WHERE id=? LIMIT 1");
            $s->execute([$fid]);
            $row = $s->fetch();
            if ($row) $preTitle = 'شهادة إتمام برنامج: ' . $row['title'];
        }

        render('admin.certificate_issue', [
            'preUserId'     => $uid,
            'preFormId'     => $fid,
            'preSubId'      => $sid,
            'preChallengeId'=> $chid,
            'preCourseId'   => $coid,
            'preTitle'      => $preTitle,
            'users'         => $this->db->query("SELECT id,display_name,username,email FROM users WHERE is_active=1 ORDER BY display_name")->fetchAll(),
            'forms'         => $this->db->query("SELECT id,title FROM application_forms ORDER BY title")->fetchAll(),
            'challenges'    => $this->db->query("SELECT id,title FROM challenges ORDER BY title")->fetchAll(),
            'pageTitle'     => 'إصدار شهادة',
        ], 'admin');
    }
    public function certificateSave(): void {
        verify_csrf();
        $uid   = (int)($_POST['user_id']??0); $fid = (int)($_POST['form_id']??0)?:null;
        $sid   = (int)($_POST['submission_id']??0)?:null; $chid = (int)($_POST['challenge_id']??0)?:null;
        $title = trim($_POST['title']??''); $desc = trim($_POST['description']??'');
        $exp   = trim($_POST['expires_at']??'')?:null;
        if (!$uid || !$title) redirect('/admin/certificates', ['error' => 'المستخدم والعنوان مطلوبان.']);
        $token = bin2hex(random_bytes(24));
        $this->db->prepare("INSERT INTO certificates (token,user_id,form_id,challenge_id,submission_id,title,description,issued_by,expires_at) VALUES (?,?,?,?,?,?,?,?,?)")
                 ->execute([$token,$uid,$fid,$chid,$sid,$title,$desc,auth()['id'],$exp]);
        Logger::info('admin.cert', "إصدار شهادة للمستخدم #{$uid}: {$title}");
        notify($uid, 'certificate', 'حصلت على شهادة جديدة', $title, '/account/certificates');
        notify_certificate_email($uid, $title, $desc, $token);
        $appUrl = rtrim(Config::get('APP_URL', ''), '/');
        $viewUrl = $appUrl . '/certificates/view?token=' . $token;
        redirect('/admin/certificates', ['cert_issued' => $viewUrl, 'cert_name' => $title]);
    }
    public function certificateRevoke(): void {
        verify_csrf(); require_admin();
        $id = (int)($_POST['id'] ?? 0);
        $this->db->prepare("UPDATE certificates SET is_valid=0 WHERE id=?")->execute([$id]);
        Logger::info('admin.cert', "إلغاء شهادة #{$id}", ['by' => auth()['username']]);
        redirect('/admin/certificates', ['success' => 'تم الإلغاء.']);
    }


    // ── Logs ─────────────────────────────────────────────────
    public function logs(): void {
        $level = $_GET['level']??''; $search = trim($_GET['search']??'');
        $where = 'WHERE 1=1'; $p = [];
        if ($level && in_array($level,['debug','info','warning','error','critical'],true)) { $where .= ' AND sl.level=?'; $p[] = $level; }
        if ($search) { $where .= ' AND (sl.action LIKE ? OR sl.message LIKE ?)'; $p[] = "%{$search}%"; $p[] = "%{$search}%"; }
        $stmt = $this->db->prepare("SELECT sl.*,u.username FROM system_logs sl LEFT JOIN users u ON sl.user_id=u.id {$where} ORDER BY sl.created_at DESC LIMIT 500");
        $stmt->execute($p);
        render('admin.logs', ['logs' => $stmt->fetchAll(), 'level' => $level, 'search' => $search, 'pageTitle' => 'السجلات'], 'admin');
    }
    public function logsClear(): void {
        verify_csrf(); require_super_admin();
        $days = max(1, (int)($_POST['days']??30));
        $this->db->prepare("DELETE FROM system_logs WHERE datetime(created_at) < datetime('now',?)")->execute(["-{$days} days"]);
        redirect('/admin/logs', ['success' => "تم حذف السجلات الأقدم من {$days} يوم."]);
    }

    // ── Settings ─────────────────────────────────────────────
    public function settings(): void {
        render('admin.settings', ['settings' => $this->db->query("SELECT key, value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR), 'pageTitle' => 'الإعدادات'], 'admin');
    }

    public function testSmtp(): void {
        require_admin(); verify_csrf();
        header('Content-Type: application/json');
        $me = auth();
        $email = $me['email'] ?? '';
        if (!$email) { echo json_encode(['success'=>false,'message'=>'لا يوجد بريد للحساب الحالي.']); exit; }
        try {
            $mailer = new Mailer();
            $mailer->send($email, $me['display_name']??'Admin', 'اختبار SMTP — مفترق الطرق',
                '<p dir="rtl" style="font-family:Tahoma,Arial">الاتصال بخادم البريد يعمل بنجاح!</p>',
                'الاتصال بخادم البريد يعمل بنجاح.');
            Logger::info('admin.smtp', "اختبار SMTP ناجح إلى {$email}");
            echo json_encode(['success'=>true,'message'=>"تم الإرسال إلى {$email}"]);
        } catch (Throwable $e) {
            Logger::error('admin.smtp', 'فشل اختبار SMTP: ' . $e->getMessage());
            echo json_encode(['success'=>false,'message'=>'فشل: ' . $e->getMessage()]);
        }
        exit;
    }

    public function settingsSave(): void {
        verify_csrf();
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO settings (key,value,updated_at) VALUES (?,?,datetime('now'))");

        // General text fields — keys/passwords are managed via .env ONLY
        $textFields = ['site_name','contact_email','footer_text','site_logo','site_logo_dark',
                       'maintenance_message','maintenance_msg','site_mode','hero_subtitle'];
        foreach ($textFields as $k) {
            if (array_key_exists($k, $_POST)) {
                $stmt->execute([$k, trim($_POST[$k])]);
            }
        }

        // Checkbox flags
        $boolFlags = ['disable_login','disable_register','disable_apply',
                      'disable_challenges','disable_certificates','disable_news',
                      'maintenance_mode','email_otp_register','email_otp_reset'];
        foreach ($boolFlags as $k) {
            if (in_array($k, ['email_otp_register','email_otp_reset'])) {
                if (array_key_exists($k, $_POST)) {
                    $stmt->execute([$k, isset($_POST[$k]) ? '1' : '0']);
                }
            } else {
                $stmt->execute([$k, isset($_POST[$k]) ? '1' : '0']);
            }
        }

        // Sync site_mode → maintenance_mode
        if (isset($_POST['site_mode'])) {
            $stmt->execute(['maintenance_mode', $_POST['site_mode'] === 'maintenance' ? '1' : '0']);
        }

        Logger::info('admin.settings', 'تحديث الإعدادات', ['by' => auth()['username']]);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT']??'', 'json')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'تم الحفظ.']);
            exit;
        }
        redirect('/admin/settings', ['success' => 'تم الحفظ.']);
    }

    public function upload(): void {
        require_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        verify_csrf();
        header('Content-Type: application/json');

        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'فشل رفع الملف.']); exit;
        }
        $maxBytes = (int)Config::get('UPLOAD_MAX_MB', 3) * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            echo json_encode(['error' => 'الحجم يتجاوز الحد المسموح (' . Config::get('UPLOAD_MAX_MB',3) . ' MB).']); exit;
        }
        $info = @getimagesize($file['tmp_name']);
        if (!$info || !in_array($info['mime'], ['image/jpeg','image/png','image/webp','image/gif'], true)) {
            echo json_encode(['error' => 'يسمح فقط بالصور (JPG/PNG/WebP/GIF).']); exit;
        }
        // Sanitise filename — no path traversal
        $ext  = match($info['mime']) {
            'image/png'  => 'png', 'image/webp' => 'webp',
            'image/gif'  => 'gif', default      => 'jpg',
        };
        $name = 'upload_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dir  = ROOT_PATH . '/public/uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
            echo json_encode(['error' => 'فشل حفظ الملف.']); exit;
        }
        Logger::info('admin.upload', "رفع ملف: {$name}", ['by' => auth()['username']]);
        echo json_encode(['ok' => true, 'url' => BASE_URL . '/public/uploads/' . $name]);
        exit;
    }

    public function logoUpload(): void {
        require_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        verify_csrf();
        header('Content-Type: application/json');

        $slot = ($_POST['slot'] ?? 'light') === 'dark' ? 'dark' : 'light';
        $settingKey = $slot === 'dark' ? 'site_logo_dark' : 'site_logo';

        $file = $_FILES['logo'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'فشل رفع الشعار.']); exit;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['error' => 'الحجم يتجاوز 2 MB.']); exit;
        }
        $info = @getimagesize($file['tmp_name']);
        if (!$info || !in_array($info['mime'], ['image/jpeg','image/png','image/webp'], true)) {
            echo json_encode(['error' => 'يسمح بـ PNG/JPG/WebP فقط.']); exit;
        }
        $ext = match($info['mime']) { 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg' };
        $dir = ROOT_PATH . '/public/uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $name = 'logo_' . $slot . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
            echo json_encode(['error' => 'فشل حفظ الشعار.']); exit;
        }
        $url = BASE_URL . '/public/uploads/' . $name;
        $oldUrl = setting($settingKey, '');
        $this->db->prepare("INSERT OR REPLACE INTO settings (key,value,updated_at) VALUES (?,?,datetime('now'))")
                 ->execute([$settingKey, $url]);
        $this->deleteLogoFile($oldUrl, $url);
        Logger::info('admin.logo', "رفع شعار {$slot}: {$name}", ['by' => auth()['username']]);
        echo json_encode(['ok' => true, 'url' => $url, 'slot' => $slot]);
        exit;
    }

    /** Delete an old logo file only if no other slot still uses it. */
    private function deleteLogoFile(string $oldUrl, string $keepUrl = ''): void {
        if ($oldUrl === '' || $oldUrl === $keepUrl) return;
        $other = setting('site_logo', '') === $oldUrl || setting('site_logo_dark', '') === $oldUrl;
        if ($other) return;
        $p = brand_logo_local_path($oldUrl);
        if ($p && is_file($p)) @unlink($p);
    }

    public function logoRemove(): void {
        require_admin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        verify_csrf();
        header('Content-Type: application/json');
        $slot = ($_POST['slot'] ?? 'light') === 'dark' ? 'dark' : 'light';
        $key  = $slot === 'dark' ? 'site_logo_dark' : 'site_logo';
        $old  = setting($key, '');
        $this->db->prepare("INSERT OR REPLACE INTO settings (key,value,updated_at) VALUES (?,?,datetime('now'))")->execute([$key, '']);
        $this->deleteLogoFile($old);
        Logger::info('admin.logo', "حذف شعار {$slot}", ['by' => auth()['username']]);
        echo json_encode(['ok' => true]);
        exit;
    }

    public function certificateQr(): void {
        require_auth();
        $token = trim($_GET['token'] ?? '');
        if (!preg_match('/^[a-f0-9]{48}$/', $token)) redirect('/admin/certificates', ['error' => 'رمز غير صالح.']);
        redirect('/certificates/download-pdf?token=' . rawurlencode($token));
    }

    // ── Accept All submissions ────────────────────────────────
    public function acceptAll(): void {
        verify_csrf(); $this->checkPerm('manage_forms');
        $fid = (int)($_POST['form_id'] ?? 0);
        if (!$fid) redirect('/admin/submissions', ['error' => 'فورم غير محدد.']);
        $stmt = $this->db->prepare("UPDATE form_submissions SET status='accepted',reviewed_by=?,reviewed_at=datetime('now') WHERE form_id=? AND status='pending'");
        $stmt->execute([(int)auth()['id'], $fid]);
        $n = $stmt->rowCount();
        Logger::info('admin.accept_all', "قبول {$n} طلب من فورم #{$fid}", ['by' => auth()['username']]);
        redirect('/admin/submissions?form_id=' . $fid, ['success' => "تم قبول {$n} طلب."]);
    }

    public function promoteForm(): void {
        require_super_admin();
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
        $stmt->execute([(int)($_GET['id'] ?? 0)]);
        $user = $stmt->fetch();
        if (!$user) redirect('/admin/users', ['error' => 'المستخدم غير موجود.']);
        render('admin.promote', ['pageTitle' => 'صلاحيات: ' . ($user['display_name'] ?? ''), 'user' => $user], 'admin');
    }

    public function promoteSubmit(): void {
        verify_csrf(); require_super_admin();
        $uid  = (int)($_POST['user_id'] ?? 0);
        $role = trim($_POST['role'] ?? 'user');
        if (!in_array($role, ['user', 'moderator', 'admin', 'super_admin'], true)) $role = 'user';
        $allP = ['manage_users', 'manage_content', 'manage_forms', 'manage_challenges', 'manage_certificates', 'run_sql'];
        $perms = array_values(array_filter($allP, function ($p) { return !empty($_POST['perm_' . $p]); }));
        $perms[] = 'view_logs';
        $perms = array_unique($perms);
        $vol = !empty($_POST['is_volunteer']) ? 1 : 0;
        $this->db->prepare("UPDATE users SET role=?, permissions=?, is_volunteer=?, updated_at=datetime('now') WHERE id=?")
                 ->execute([$role, json_encode($perms), $vol, $uid]);
        Logger::info('admin.promote', "تغيير دور ID {$uid} → {$role}", ['by' => auth()['username']]);
        redirect('/admin/users', ['success' => 'تم تحديث الصلاحيات.']);
    }

    // ── Create Admin ──────────────────────────────────────────
    public function adminCreateForm(): void {
        require_super_admin();
        render('admin.admin_create', ['pageTitle' => 'إضافة مشرف'], 'admin');
    }

    public function adminCreate(): void {
        verify_csrf(); require_super_admin();
        $name  = trim($_POST['display_name'] ?? '');
        $uname = strtolower(trim($_POST['username'] ?? ''));
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $tg    = trim($_POST['telegram_username'] ?? '');
        $allP  = ['manage_users', 'manage_content', 'manage_forms', 'manage_challenges', 'manage_certificates', 'run_sql'];
        $perms = array_values(array_filter($allP, function ($p) { return !empty($_POST['perm_' . $p]); }));
        $perms[] = 'view_logs';
        $perms = array_unique($perms);
        $errs = [];
        if (mb_strlen($name) < 2) $errs[] = 'الاسم قصير.';
        if (!preg_match('/^[a-z0-9_]{3,30}$/', $uname)) $errs[] = 'اسم المستخدم غير صالح.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errs[] = 'البريد غير صالح.';
        if (strlen($pass) < 8 || !preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]/', $pass)) $errs[] = 'كلمة المرور ضعيفة (8+ حروف، حرف كبير، رقم).';
        if (!$errs) {
            $chk = $this->db->prepare("SELECT id FROM users WHERE username=? OR email=? LIMIT 1");
            $chk->execute([$uname, $email]);
            if ($chk->fetch()) $errs[] = 'المستخدم أو البريد موجود مسبقا.';
        }
        if ($errs) redirect('/admin/admins/new', ['error' => implode(' | ', $errs)]);
        $tgF = $tg ? (str_starts_with($tg, '@') ? $tg : '@' . $tg) : null;
        $this->db->prepare("INSERT INTO users (display_name,username,telegram_username,email,password,role,permissions) VALUES (?,?,?,?,?,?,?)")
                 ->execute([$name, $uname, $tgF, $email, password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]), 'admin', json_encode($perms)]);
        Logger::info('admin.create', "إنشاء مشرف: {$uname}", ['by' => auth()['username']]);
        redirect('/admin/users', ['success' => "تم إنشاء المشرف «{$uname}» بنجاح."]);
    }

    // ── SQL Query ─────────────────────────────────────────────
    public function sqlQuery(): void {
        require_super_admin();
        render('admin.sql_query', ['pageTitle' => 'SQL Query', 'result' => null, 'query' => '', 'error' => null, 'affected' => null], 'admin');
    }

    public function sqlExecute(): void {
        verify_csrf(); require_super_admin();
        // Hard rate-limit: max 30 SQL executions per hour per IP — prevents brute exfil
        if (!rate_limit('sql_exec', 30, 3600)) {
            Logger::warning('admin.sql', 'SQL rate limited', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
            redirect('/admin/sql', ['error' => 'حد التنفيذ مؤقتا. انتظر قبل المحاولة مجددا.']);
        }
        $sql = trim($_POST['sql'] ?? '');
        $result = null; $error = null; $affected = null;
        if ($sql) {
            try {
                $st = $this->db->prepare($sql);
                $st->execute();
                if (preg_match('/^\s*(SELECT|PRAGMA|EXPLAIN)/i', $sql)) {
                    $result = $st->fetchAll();
                } else {
                    $affected = $st->rowCount();
                }
                Logger::warning('admin.sql', 'SQL executed', ['by' => auth()['username'], 'sql' => substr($sql, 0, 200)]);
            } catch (\PDOException $e) {
                $error = $e->getMessage();
            }
        }
        render('admin.sql_query', ['pageTitle' => 'SQL Query', 'result' => $result, 'query' => $sql, 'error' => $error, 'affected' => $affected], 'admin');
    }

    // ── Bulk Users ────────────────────────────────────────────
    public function bulkUserForm(): void {
        $this->checkPerm('manage_users');
        render('admin.bulk_users', ['pageTitle' => 'إنشاء مستخدمين جماعي'], 'admin');
    }

    public function bulkUserCreate(): void {
        verify_csrf(); $this->checkPerm('manage_users');
        $raw = trim($_POST['users_csv'] ?? '');
        if (!$raw) redirect('/admin/users/bulk-form', ['error' => 'لا توجد بيانات.']);
        $lines = preg_split('/\r?\n/', $raw);
        $created = 0; $errors = [];
        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (!$line || $line[0] === '#') continue;
            $parts = str_getcsv($line);
            $uname = strtolower(trim($parts[0] ?? ''));
            $email = strtolower(trim($parts[1] ?? ''));
            $dname = trim($parts[2] ?? $uname);
            $pass  = trim($parts[3] ?? bin2hex(random_bytes(6)));
            if (!preg_match('/^[a-z0-9_]{3,30}$/', $uname)) { $errors[] = "صف " . ($i + 1) . ": username غير صالح"; continue; }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "صف " . ($i + 1) . ": بريد غير صالح"; continue; }
            try {
                $this->db->prepare("INSERT INTO users (display_name,username,email,password,role) VALUES (?,?,?,?,?)")
                         ->execute([$dname, $uname, $email, password_hash($pass, PASSWORD_BCRYPT, ['cost' => 10]), 'user']);
                $created++;
            } catch (\PDOException $e) {
                $errors[] = "صف " . ($i + 1) . ": {$uname} موجود مسبقا";
            }
        }
        $msg = "تم إنشاء {$created} مستخدم.";
        if ($errors) $msg .= ' أخطاء: ' . implode('; ', array_slice($errors, 0, 5));
        redirect('/admin/users', ['success' => $msg]);
    }

    // ── Bulk Certificates ─────────────────────────────────────
    public function bulkCertForm(): void {
        $this->checkPerm('manage_certificates');
        $forms      = $this->db->query("SELECT id,title FROM application_forms ORDER BY created_at DESC")->fetchAll();
        $challenges = $this->db->query("SELECT id,title FROM challenges ORDER BY created_at DESC")->fetchAll();
        render('admin.bulk_cert', ['pageTitle' => 'إصدار شهادات جماعية', 'forms' => $forms, 'challenges' => $challenges], 'admin');
    }

    public function bulkCertificate(): void {
        verify_csrf(); $this->checkPerm('manage_certificates');
        $title        = trim($_POST['title'] ?? '');
        $desc         = trim($_POST['description'] ?? '');
        $challenge_id = (int)($_POST['challenge_id'] ?? 0) ?: null;
        $rawIds       = trim($_POST['user_ids'] ?? '');
        if (!$title) redirect('/admin/certs/bulk-form', ['error' => 'العنوان مطلوب.']);
        $ids = array_values(array_unique(array_filter(array_map('intval', preg_split('/[\s,]+/', $rawIds)))));
        if (empty($ids)) redirect('/admin/certs/bulk-form', ['error' => 'لا يوجد مستخدمون.']);
        if (count($ids) > 500) redirect('/admin/certs/bulk-form', ['error' => 'الحد الأقصى 500.']);
        $stmt = $this->db->prepare("INSERT INTO certificates (user_id,challenge_id,title,description,token,is_valid,issued_at) VALUES (?,?,?,?,?,1,datetime('now'))");
        $issued = 0;
        foreach ($ids as $uid) {
            $ck = $this->db->prepare("SELECT id FROM users WHERE id=? LIMIT 1");
            $ck->execute([$uid]);
            if (!$ck->fetch()) continue;
            try {
                $certToken = bin2hex(random_bytes(24));
                $stmt->execute([$uid, $challenge_id, $title, $desc, $certToken]);
                notify($uid, 'certificate', 'حصلت على شهادة جديدة', $title, '/account/certificates');
                notify_certificate_email($uid, $title, $desc, $certToken);
                $issued++;
            } catch (\Throwable $e) {}
        }
        Logger::info('admin.bulk_cert', "إصدار {$issued} شهادة", ['by' => auth()['username'], 'title' => $title]);
        redirect('/admin/certificates', ['success' => "تم إصدار {$issued} شهادة."]);
    }

    public function usersForForm(): void {
        require_admin();
        $fid    = (int)($_GET['form_id'] ?? 0);
        $status = ($_GET['status'] ?? 'accepted') === 'all' ? 'all' : 'accepted';
        header('Content-Type: application/json');
        if (!$fid) { echo '[]'; exit; }
        $where = ($status === 'all') ? '' : "AND fs.status='accepted'";
        $rows = $this->db->prepare("SELECT DISTINCT u.id,u.display_name,u.username FROM form_submissions fs JOIN users u ON fs.user_id=u.id WHERE fs.form_id=? {$where} ORDER BY u.display_name");
        $rows->execute([$fid]);
        echo json_encode($rows->fetchAll(\PDO::FETCH_ASSOC));
        exit;
    }

    public function rejectAll(): void {
        verify_csrf(); $this->checkPerm('manage_forms');
        $fid = (int)($_POST['form_id'] ?? 0);
        if (!$fid) redirect('/admin/submissions', ['error' => 'فورم غير محدد.']);
        $stmt = $this->db->prepare("UPDATE form_submissions SET status='rejected',reviewed_by=?,reviewed_at=datetime('now') WHERE form_id=? AND status='pending'");
        $stmt->execute([(int)auth()['id'], $fid]);
        // BUG FIX: capture rowCount() ONCE before redirect — calling it twice is redundant and
        // rowCount() behaviour after redirect/exit can differ across PDO drivers
        $rejected = $stmt->rowCount();
        Logger::info('admin.reject_all', "رفض {$rejected} طلب من فورم #{$fid}", ['by' => auth()['username']]);
        redirect('/admin/submissions?form_id=' . $fid, ['success' => "تم رفض {$rejected} طلب."]);
    }

    // ── aiReviewAll ───────────────────────────────────────────
    public function aiReviewAll(): void {
        verify_csrf(); $this->checkPerm('manage_forms');
        header('Content-Type: application/json');
        $fid = (int)($_POST['form_id'] ?? 0);
        if (!$fid) { echo json_encode(['error' => 'فورم غير محدد.']); exit; }
        $stmt = $this->db->prepare("SELECT fs.*, u.display_name, af.title AS form_title FROM form_submissions fs JOIN users u ON fs.user_id=u.id JOIN application_forms af ON fs.form_id=af.id WHERE fs.form_id=? AND fs.status='pending' AND (fs.ai_review IS NULL OR fs.ai_review='')");
        $stmt->execute([$fid]);
        $subs = $stmt->fetchAll();
        if (empty($subs)) { echo json_encode(['ok' => true, 'done' => 0, 'message' => 'لا توجد طلبات معلقة بدون مراجعة AI.']); exit; }
        $qs = $this->db->prepare("SELECT label FROM form_questions WHERE form_id=? ORDER BY sort_order");
        $qs->execute([$fid]);
        $questions = $qs->fetchAll();
        try {
            require_once ROOT_PATH . '/helpers/OllamaClient.php';
            $ai = OllamaClient::fromSettings();
        } catch (Throwable $e) { echo json_encode(['error' => 'فشل Ollama: ' . $e->getMessage()]); exit; }
        $upd = $this->db->prepare("UPDATE form_submissions SET ai_review=? WHERE id=?");
        $done = 0; $errors = 0;
        foreach ($subs as $sub) {
            $answers = json_decode($sub['answers'] ?? '{}', true) ?: [];
            $text = "التقديم على: {$sub['form_title']}\nالمتقدم: {$sub['display_name']}\n\n";
            foreach ($questions as $q) { $ans = $answers[$q['label']] ?? '—'; if (is_array($ans)) $ans = implode(', ', $ans); $text .= "{$q['label']}: {$ans}\n"; }
            $prompt = "أنت مساعد إداري يراجع طلبات تطوعية. راجع هذا الطلب وأعط: 1. تقييم (من 10) 2. نقاط قوة 3. نقاط ضعف 4. توصية: قبول/رفض/مراجعة\n\n{$text}";
            try { $upd->execute([$ai->chat($prompt, 0.3, 800), $sub['id']]); $done++; } catch (Throwable $e) { Logger::error('ai_review_all', $e->getMessage()); $errors++; }
        }
        echo json_encode(['ok' => true, 'done' => $done, 'errors' => $errors, 'message' => "تمت مراجعة {$done} طلب." . ($errors ? " ({$errors} فشل)" : '')]);
        exit;
    }

    // ── aiReview ──────────────────────────────────────────────
    public function aiReview(): void {
        verify_csrf(); $this->checkPerm('manage_forms');
        header('Content-Type: application/json');
        $sid = (int)($_POST['submission_id'] ?? 0);
        if (!$sid) { echo json_encode(['error' => 'معرف غير صالح']); exit; }
        $sub = $this->db->prepare("SELECT fs.*, u.display_name, af.title AS form_title FROM form_submissions fs JOIN users u ON fs.user_id=u.id JOIN application_forms af ON fs.form_id=af.id WHERE fs.id=? LIMIT 1");
        $sub->execute([$sid]); $sub = $sub->fetch();
        if (!$sub) { echo json_encode(['error' => 'الطلب غير موجود']); exit; }
        $qs = $this->db->prepare("SELECT label FROM form_questions WHERE form_id=? ORDER BY sort_order");
        $qs->execute([$sub['form_id']]);
        $answers = json_decode($sub['answers'] ?? '{}', true) ?: [];
        $text = "التقديم على: {$sub['form_title']}\nالمتقدم: {$sub['display_name']}\n\n";
        foreach ($qs->fetchAll() as $q) { $ans = $answers[$q['label']] ?? '—'; if (is_array($ans)) $ans = implode(', ', $ans); $text .= "{$q['label']}: {$ans}\n"; }
        $prompt = "أنت مساعد إداري يراجع طلبات تطوعية. راجع هذا الطلب وأعط: 1. تقييم (من 10) 2. نقاط قوة 3. نقاط ضعف 4. توصية: قبول/رفض/مراجعة\n\n{$text}";
        try {
            require_once ROOT_PATH . '/helpers/OllamaClient.php';
            $aiText = OllamaClient::fromSettings()->chat($prompt, 0.3, 800);
        } catch (Throwable $e) { echo json_encode(['error' => 'فشل Ollama: ' . $e->getMessage()]); exit; }
        $this->db->prepare("UPDATE form_submissions SET ai_review=? WHERE id=?")->execute([$aiText, $sid]);
        Logger::info('admin.ai_review', "AI review #{$sid}", ['by' => auth()['username']]);
        echo json_encode(['ok' => true, 'review' => $aiText]);
        exit;
    }

    // ── aiPing ────────────────────────────────────────────────
    public function aiPing(): void {
        verify_csrf(); $this->checkPerm('manage_forms');
        header('Content-Type: application/json');
        try {
            require_once ROOT_PATH . '/helpers/OllamaClient.php';
            OllamaClient::fromSettings()->chat('ping', 0.0, 5);
            echo json_encode(['ok' => true, 'message' => 'Ollama متصل · ' . setting('ollama_model','llama3.2')]);
        } catch (Throwable $e) { echo json_encode(['ok' => false, 'error' => $e->getMessage()]); }
        exit;
    }

    // ── features ──────────────────────────────────────────────
    public function features(): void {
        require_admin(); $this->checkPerm('super_admin_only');
        render('admin.feature_flags', ['pageTitle' => 'التحكم بالخصائص'], 'admin');
    }
    public function featureToggle(): void {
        verify_csrf(); $this->checkPerm('super_admin_only');
        header('Content-Type: application/json');
        $key = trim($_POST['key'] ?? '');
        $val = (int)($_POST['value'] ?? 0);
        $allowed = ['maintenance_mode','feature_login','feature_register','feature_apply','feature_challenges','feature_news','feature_ai_review'];
        if (!in_array($key, $allowed, true)) {
            echo json_encode(['success' => false, 'error' => 'مفتاح غير صالح.']); exit;
        }
        $this->db->prepare("INSERT INTO settings(key,value) VALUES(?,?) ON CONFLICT(key) DO UPDATE SET value=excluded.value,updated_at=datetime('now')")->execute([$key, $val]);
        Logger::info('admin.feature_toggle', "تغيير {$key} → {$val}", ['by' => auth()['username']]);
        echo json_encode(['success' => true]);
        exit;
    }

    // ── announcements ─────────────────────────────────────────
    public function announcements(): void {
        require_admin();
        $rows = $this->db->query("SELECT a.*, u.display_name FROM admin_announcements a LEFT JOIN users u ON a.created_by=u.id ORDER BY a.is_pinned DESC, a.created_at DESC")->fetchAll();
        render('admin.announcements', ['announcements' => $rows, 'pageTitle' => 'الإعلانات'], 'admin');
    }
    public function announcementSave(): void {
        verify_csrf(); require_admin();
        $title = trim($_POST['title'] ?? ''); $body = trim($_POST['body'] ?? '');
        $type  = in_array($_POST['type']??'info', ['info','warning','plan','alert']) ? $_POST['type'] : 'info';
        $pin   = isset($_POST['is_pinned']) ? 1 : 0;
        if (!$title || !$body) redirect('/admin/announcements', ['error' => 'العنوان والمحتوى مطلوبان.']);
        $this->db->prepare("INSERT INTO admin_announcements(title,body,type,is_pinned,created_by) VALUES(?,?,?,?,?)")->execute([$title,$body,$type,$pin,auth()['id']]);
        redirect('/admin/announcements', ['success' => 'تم النشر.']);
    }
    public function announcementDelete(): void {
        verify_csrf(); require_admin();
        $this->db->prepare("DELETE FROM admin_announcements WHERE id=?")->execute([(int)($_POST['id']??0)]);
        redirect('/admin/announcements', ['success' => 'تم الحذف.']);
    }

    // ── submissionView (fix 404) ───────────────────────────────
    public function submissionView(): void {
        require_admin(); $this->checkPerm('manage_forms');
        $id  = (int)($_GET['id'] ?? 0);
        $sub = $this->db->prepare("SELECT fs.*, u.display_name, u.username, u.email, u.telegram_username, af.title AS form_title FROM form_submissions fs JOIN users u ON fs.user_id=u.id JOIN application_forms af ON fs.form_id=af.id WHERE fs.id=? LIMIT 1");
        $sub->execute([$id]); $submission = $sub->fetch();
        if (!$submission) redirect('/admin/submissions', ['error' => 'الطلب غير موجود.']);
        $qs = $this->db->prepare("SELECT * FROM form_questions WHERE form_id=? ORDER BY sort_order");
        $qs->execute([$submission['form_id']]);
        render('admin.submission_view', ['submission' => $submission, 'questions' => $qs->fetchAll(), 'pageTitle' => 'طلب #'.$id], 'admin');
    }
}
