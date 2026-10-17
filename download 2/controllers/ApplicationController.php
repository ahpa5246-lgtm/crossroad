<?php declare(strict_types=1);
class ApplicationController {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }
    public function index(): void {
        $uid   = auth() ? (int)auth()['id'] : 0;
        $forms = $this->db->query("SELECT * FROM application_forms WHERE status='open' ORDER BY sort_order,updated_at DESC")->fetchAll();
        // Add prerequisite met flag
        foreach ($forms as &$f) {
            if (!empty($f['prerequisite_form_id']) && $uid > 0) {
                $pck = $this->db->prepare("SELECT id FROM form_submissions WHERE form_id=? AND user_id=? AND status='accepted' LIMIT 1");
                $pck->execute([$f['prerequisite_form_id'], $uid]);
                $f['prerequisite_met'] = (bool)$pck->fetch();
            } else {
                $f['prerequisite_met'] = true;
            }
        }
        unset($f);
        render('home.apply', ['forms' => $forms, 'pageTitle' => 'التقديم']);
    }
    public function show(): void {
        $id  = (int)($_GET['id'] ?? 0);
        $uid = auth() ? (int)auth()['id'] : 0;
        $s   = $this->db->prepare("SELECT * FROM application_forms WHERE id=? AND status='open' LIMIT 1");
        $s->execute([$id]); $form = $s->fetch();
        if (!$form) redirect('/apply', ['error' => 'النموذج غير متاح.']);
        if ($uid === 0) {   // guest: read-only preview of the form, applying needs an account
            $q = $this->db->prepare("SELECT * FROM form_questions WHERE form_id=? ORDER BY sort_order");
            $q->execute([$id]);
            render('home.form', ['form' => $form, 'questions' => $q->fetchAll(), 'pageTitle' => $form['title'], 'guestPreview' => true]);
            return;
        }
        // Prerequisite check
        if (!empty($form['prerequisite_form_id'])) {
            $pck = $this->db->prepare("SELECT id FROM form_submissions WHERE form_id=? AND user_id=? AND status='accepted' LIMIT 1");
            $pck->execute([$form['prerequisite_form_id'], $uid]);
            if (!$pck->fetch()) redirect('/apply', ['error' => 'هذا الفورم يتطلب قبولك في برنامج سابق.']);
        }
        // Duplicate submission check — show static "already applied" page
        $dup = $this->db->prepare("SELECT id FROM form_submissions WHERE form_id=? AND user_id=? LIMIT 1");
        $dup->execute([$id, $uid]);
        if ($dup->fetch()) {
            render('home.form', [
                'form'        => $form,
                'questions'   => [],
                'pageTitle'   => $form['title'],
                'alreadyApplied' => true,
            ]);
            return;
        }
        $q = $this->db->prepare("SELECT * FROM form_questions WHERE form_id=? ORDER BY sort_order");
        $q->execute([$id]);
        render('home.form', ['form' => $form, 'questions' => $q->fetchAll(), 'pageTitle' => $form['title']]);
    }
    public function submit(): void {
        require_auth();
        verify_csrf();
        $id = (int)($_POST['form_id'] ?? 0);
        $s  = $this->db->prepare("SELECT * FROM application_forms WHERE id=? AND status='open' LIMIT 1");
        $s->execute([$id]); $form = $s->fetch();
        if (!$form) json_out(['error' => 'النموذج غير متاح.'], 422);

        // Duplicate check — hard block before anything else
        $uid = (int)auth()['id'];
        $dupChk = $this->db->prepare("SELECT id FROM form_submissions WHERE form_id=? AND user_id=? LIMIT 1");
        $dupChk->execute([$id, $uid]);
        if ($dupChk->fetch()) {
            json_out(['error' => 'أنت مقدم بالفعل على هذا البرنامج.', 'already_applied' => true], 409);
        }

        // reCAPTCHA v2 verification
        $recaptchaSecret = Config::get('RECAPTCHA_SECRET_KEY', '');
        if ($recaptchaSecret) {
            $recaptchaToken = $_POST['g-recaptcha-response'] ?? '';
            if (!$recaptchaToken) {
                json_out(['error' => 'يرجى إكمال التحقق من CAPTCHA.'], 422);
            }
            $verifyCtx = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query(['secret' => $recaptchaSecret, 'response' => $recaptchaToken, 'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '']),
                'timeout' => 8,
            ]]);
            $verifyRes = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $verifyCtx);
            if (!$verifyRes) {
                // If Google unreachable, fail open (don't block user) but log it
                Logger::warning('recaptcha.verify', 'تعذر التحقق من Google reCAPTCHA', ['form_id' => $id]);
            } else {
                $verifyJson = json_decode($verifyRes, true);
                if (empty($verifyJson['success'])) {
                    json_out(['error' => 'فشل التحقق من CAPTCHA. حاول مجددا.'], 422);
                }
            }
        }

        if (!rate_limit('apply_'.$id, 3, 3600)) json_out(['error' => 'لقد قدمت مؤخرا. انتظر قليلا.'], 429);

        $q = $this->db->prepare("SELECT * FROM form_questions WHERE form_id=? ORDER BY sort_order");
        $q->execute([$id]); $questions = $q->fetchAll();

        $choiceTypes = ['radio', 'checkbox', 'dropdown'];
        $answers = []; $errors = [];

        foreach ($questions as $qu) {
            $type = $qu['type'];

            // Multi-select: values come as array
            if ($type === 'checkbox') {
                $rawVals = $_POST['q_' . $qu['id']] ?? [];
                if (!is_array($rawVals)) $rawVals = [];
                // Validate against allowed options
                $allowed = json_decode($qu['options'] ?? '[]', true) ?: [];
                $vals    = array_values(array_intersect($rawVals, $allowed));
                $val     = implode(', ', $vals);
                if ($qu['is_required'] && empty($vals)) {
                    $errors[] = $qu['label'] . ' مطلوب.';
                    continue;
                }
                $answers[$qu['id']] = ['label' => $qu['label'], 'type' => $type, 'value' => $val, 'values' => $vals];
                continue;
            }

            // Single-value fields
            $val = trim($_POST['q_' . $qu['id']] ?? '');

            if ($qu['is_required'] && $val === '') {
                $errors[] = $qu['label'] . ' مطلوب.';
                continue;
            }

            // Validate choice fields against allowed options
            if (in_array($type, ['radio', 'dropdown'], true) && $val !== '') {
                $allowed = json_decode($qu['options'] ?? '[]', true) ?: [];
                if (!in_array($val, $allowed, true)) {
                    $errors[] = $qu['label'] . ': قيمة غير صالحة.';
                    continue;
                }
            }

            switch ($type) {
                case 'email':  if ($val && !filter_var($val, FILTER_VALIDATE_EMAIL)) $errors[] = $qu['label'].': بريد غير صالح.'; break;
                case 'phone':  if ($val && !preg_match('/^\+?[\d\s\-]{7,20}$/', $val)) $errors[] = $qu['label'].': رقم غير صالح.'; break;
                case 'number': if ($val && !is_numeric($val)) $errors[] = $qu['label'].': يجب أن يكون رقما.'; break;
                case 'age':    if ($val && ((int)$val < 1 || (int)$val > 120)) $errors[] = $qu['label'].': عمر غير صالح.'; break;
            }
            $answers[$qu['id']] = ['label' => $qu['label'], 'type' => $type, 'value' => $val];
        }

        // Include telegram if user logged in
        $user = auth();
        if ($user && $user['telegram_username']) {
            $answers['_telegram'] = ['label' => 'معرف التيليغرام', 'type' => 'text', 'value' => $user['telegram_username']];
        }
        if ($errors) json_out(['errors' => $errors], 422);

        $this->db->prepare("INSERT INTO form_submissions (form_id,user_id,answers) VALUES (?,?,?)")
                 ->execute([$id, $uid, json_encode($answers, JSON_UNESCAPED_UNICODE)]);
        Logger::info('form.submit', "تقديم جديد #{$id}", ['uid' => $uid]);
        json_out(['success' => true, 'message' => 'تم إرسال طلبك بنجاح! سنراجعه ونبلغك بالنتيجة عبر الإشعارات وعلى التيليغرام. تتابع حالته من صفحة حسابي.']);
    }
}
