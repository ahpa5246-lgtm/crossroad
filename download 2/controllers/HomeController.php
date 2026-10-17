<?php declare(strict_types=1);
class HomeController {
    public function index(): void {
        $db   = Database::getInstance();
        $news = $db->query("SELECT * FROM news WHERE is_published=1 ORDER BY published_at DESC LIMIT 3")->fetchAll();
        $openForms = $db->query("SELECT * FROM application_forms WHERE status='open' ORDER BY sort_order, updated_at DESC LIMIT 1")->fetchAll();
        $stats = [
            'members'    => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'open_forms' => (int)$db->query("SELECT COUNT(*) FROM application_forms WHERE status='open'")->fetchColumn(),
            'subs'       => (int)$db->query("SELECT COUNT(*) FROM form_submissions")->fetchColumn(),
            'challenges' => (int)$db->query("SELECT COUNT(*) FROM challenges WHERE status='published'")->fetchColumn(),
        ];
        render('home.index', ['news' => $news, 'activeForm' => $openForms[0] ?? null, 'stats' => $stats, 'pageTitle' => setting('site_name')]);
    }
    public function achievements(): void { render('home.achievements', ['items' => achievements_list(), 'pageTitle' => 'الإنجازات']); }
    public function team(): void { render('home.team', ['members' => team_members(), 'pageTitle' => setting('team_title', 'الإدارة العليا')]); }
    public function contact(): void { render('home.contact', ['links' => contact_links(), 'pageTitle' => 'تواصل معنا']); }
    public function terms(): void { render('home.terms', ['pageTitle' => 'الشروط والأحكام']); }
    public function install(): void { render('home.install', ['pageTitle' => 'تثبيت التطبيق']); }
    public function guide2fa(): void { render('home.guide_2fa', ['pageTitle' => 'دليل التحقق بخطوتين']); }
    public function offline(): void {
        readfile(ROOT_PATH . '/views/offline.html');
    }

}