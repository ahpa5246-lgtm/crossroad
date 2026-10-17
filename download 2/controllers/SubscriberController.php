<?php declare(strict_types=1);
class SubscriberController {
    public function subscribe(): void {
        verify_csrf();
        $email = trim(strtolower($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['error' => 'بريد غير صالح.'], 422);
        if (is_disposable_email($email)) json_out(['error' => 'يرجى استخدام بريد حقيقي.'], 422);
        if (!rate_limit('subscribe', 3, 3600)) json_out(['error' => 'محاولات كثيرة.'], 429);
        $db = Database::getInstance();
        $db->prepare("INSERT OR IGNORE INTO email_subscribers (email) VALUES (?)")->execute([$email]);
        Logger::info('subscriber.new', "مشترك: {$email}");
        json_out(['success' => true, 'message' => 'تم الاشتراك بنجاح!']);
    }
}
