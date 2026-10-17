<?php
declare(strict_types=1);
class NewsController {
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    public function index(): void {
        $search   = trim($_GET['q'] ?? '');
        $category = trim($_GET['cat'] ?? '');
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 12;
        $offset   = ($page - 1) * $perPage;

        $where  = "WHERE is_published=1";
        $params = [];

        if ($search !== '') {
            $where   .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        if ($category !== '') {
            $where   .= " AND category=?";
            $params[] = $category;
        }

        // Total count for pagination
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM news {$where}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));

        // Fetch page
        $stmt = $this->db->prepare("SELECT * FROM news {$where} ORDER BY published_at DESC, created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([...$params, $perPage, $offset]);
        $news = $stmt->fetchAll();

        // All categories for filter
        try {
            $cats = $this->db->query("SELECT DISTINCT category FROM news WHERE is_published=1 AND category IS NOT NULL AND category!='' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable) { $cats = []; }

        render('home.news', [
            'news'        => $news,
            'categories'  => $cats,
            'activecat'   => $category,
            'search'      => $search,
            'page'        => $page,
            'pages'       => $pages,
            'total'       => $total,
            'pageTitle'   => 'الفعاليات',
        ]);
    }

    public function view(): void {
        $id = (int)($_GET['id'] ?? 0);
        $s  = $this->db->prepare("SELECT * FROM news WHERE id=? AND is_published=1 LIMIT 1");
        $s->execute([$id]); $item = $s->fetch();
        if (!$item) redirect('/news');

        // Related articles
        try {
            $rel = $this->db->prepare("SELECT id, title, cover_image, published_at FROM news WHERE is_published=1 AND id!=? ORDER BY published_at DESC LIMIT 3");
            $rel->execute([$id]);
            $related = $rel->fetchAll();
        } catch (Throwable) { $related = []; }

        render('home.news_view', ['item' => $item, 'related' => $related, 'pageTitle' => $item['title']]);
    }
}
