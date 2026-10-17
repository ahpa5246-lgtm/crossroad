<?php
declare(strict_types=1);

/** المجلة — articles written by volunteers (author name + round avatar, likes). */
class MagazineController
{
    private PDO $db;
    public function __construct() { $this->db = Database::getInstance(); }

    private function listSql(string $where = '', string $tail = ''): string
    {
        return "SELECT a.*, u.display_name, u.username, u.avatar,
                       (SELECT COUNT(*) FROM article_likes l WHERE l.article_id=a.id) AS likes
                FROM articles a JOIN users u ON u.id=a.author_id $where ORDER BY a.created_at DESC $tail";
    }

    public function index(): void
    {
        $rows = $this->db->query($this->listSql('', 'LIMIT 60'))->fetchAll();
        render('home.magazine', ['articles' => $rows, 'pageTitle' => 'المجلة']);
    }

    public function view(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $st = $this->db->prepare($this->listSql('WHERE a.id=?', 'LIMIT 1'));
        $st->execute([$id]);
        $a = $st->fetch();
        if (!$a) redirect('/magazine', ['error' => 'المقال غير موجود.']);
        $liked = false;
        if (auth()) {
            $q = $this->db->prepare("SELECT 1 FROM article_likes WHERE article_id=? AND user_id=?");
            $q->execute([$id, (int)auth()['id']]);
            $liked = (bool)$q->fetchColumn();
        }
        render('home.magazine_view', ['a' => $a, 'liked' => $liked, 'pageTitle' => $a['title']]);
    }

    // ── writing (volunteers only) ─────────────────────────────
    private function requireVolunteer(): void
    {
        if (!auth()) redirect('/auth/login?next=' . rawurlencode('/magazine/write'));
        if (!is_volunteer()) redirect('/magazine', ['error' => 'النشر بالمجلة متاح للمتطوعين فقط.']);
    }

    public function write(): void
    {
        $this->requireVolunteer();
        render('home.magazine_write', ['a' => null, 'pageTitle' => 'مقال جديد']);
    }

    public function edit(): void
    {
        $this->requireVolunteer();
        $st = $this->db->prepare("SELECT * FROM articles WHERE id=? LIMIT 1");
        $st->execute([(int)($_GET['id'] ?? 0)]);
        $a = $st->fetch();
        if (!$a || (int)$a['author_id'] !== (int)auth()['id']) redirect('/magazine', ['error' => 'لا يمكنك تعديل هذا المقال.']);
        render('home.magazine_write', ['a' => $a, 'pageTitle' => 'تعديل المقال']);
    }

    public function save(): void
    {
        verify_csrf();
        $this->requireVolunteer();
        $uid   = (int)auth()['id'];
        $id    = (int)($_POST['id'] ?? 0);
        $title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 160);
        $desc  = mb_substr(trim((string)($_POST['description'] ?? '')), 0, 240);
        $body  = mb_substr(trim((string)($_POST['body'] ?? '')), 0, 30000);
        if ($title === '' || $body === '') {
            $_SESSION['flash']['old'] = $_POST;
            redirect($id ? '/magazine/edit?id=' . $id : '/magazine/write', ['error' => 'العنوان ونص المقال مطلوبان.']);
        }
        if ($desc === '') $desc = mb_substr(trim(preg_replace('/\s+/u', ' ', str_replace(['**', "\n"], ['', ' '], $body))), 0, 200);
        $cover = handle_upload('cover', 'magazine');

        if ($id > 0) {
            $st = $this->db->prepare("SELECT * FROM articles WHERE id=? AND author_id=? LIMIT 1");
            $st->execute([$id, $uid]);
            $old = $st->fetch();
            if (!$old) redirect('/magazine', ['error' => 'لا يمكنك تعديل هذا المقال.']);
            $newCover = $cover ?: $old['cover_image'];
            if (!empty($_POST['remove_cover']) && !$cover) $newCover = '';
            $this->db->prepare("UPDATE articles SET title=?,description=?,cover_image=?,body=?,updated_at=datetime('now') WHERE id=?")
                     ->execute([$title, $desc, $newCover, $body, $id]);
            if ($old['cover_image'] !== '' && $old['cover_image'] !== $newCover) $this->unlinkCover($old['cover_image']);
        } else {
            $this->db->prepare("INSERT INTO articles (author_id,title,description,cover_image,body) VALUES (?,?,?,?,?)")
                     ->execute([$uid, $title, $desc, (string)$cover, $body]);
            $id = (int)$this->db->lastInsertId();
        }
        Logger::info('magazine.save', "مقال #{$id}", ['by' => auth()['username']]);
        redirect('/magazine/view?id=' . $id, ['success' => 'تم النشر.']);
    }

    public function delete(): void
    {
        verify_csrf();
        if (!auth()) redirect('/auth/login');
        $id = (int)($_POST['id'] ?? 0);
        $st = $this->db->prepare("SELECT * FROM articles WHERE id=? LIMIT 1");
        $st->execute([$id]);
        $a = $st->fetch();
        if (!$a) redirect('/magazine');
        $own = (int)$a['author_id'] === (int)auth()['id'];
        if (!$own && !is_admin()) redirect('/magazine', ['error' => 'غير مصرح.']);
        $this->db->prepare("DELETE FROM article_likes WHERE article_id=?")->execute([$id]);
        $this->db->prepare("DELETE FROM articles WHERE id=?")->execute([$id]);
        if ($a['cover_image'] !== '') $this->unlinkCover($a['cover_image']);
        Logger::info('magazine.delete', "حذف مقال #{$id}", ['by' => auth()['username']]);
        redirect('/magazine', ['success' => 'تم حذف المقال.']);
    }

    public function like(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!auth()) { http_response_code(401); echo json_encode(['ok' => false, 'login' => true]); exit; }
        verify_csrf();
        $id  = (int)($_POST['id'] ?? 0);
        $uid = (int)auth()['id'];
        $ex  = $this->db->prepare("SELECT 1 FROM articles WHERE id=?"); $ex->execute([$id]);
        if (!$ex->fetchColumn()) { http_response_code(404); echo json_encode(['ok' => false]); exit; }
        $q = $this->db->prepare("SELECT 1 FROM article_likes WHERE article_id=? AND user_id=?");
        $q->execute([$id, $uid]);
        if ($q->fetchColumn()) {
            $this->db->prepare("DELETE FROM article_likes WHERE article_id=? AND user_id=?")->execute([$id, $uid]);
            $liked = false;
        } else {
            $this->db->prepare("INSERT OR IGNORE INTO article_likes (article_id,user_id) VALUES (?,?)")->execute([$id, $uid]);
            $liked = true;
        }
        $c = $this->db->prepare("SELECT COUNT(*) FROM article_likes WHERE article_id=?"); $c->execute([$id]);
        echo json_encode(['ok' => true, 'liked' => $liked, 'count' => (int)$c->fetchColumn()]);
        exit;
    }

    private function unlinkCover(string $rel): void
    {
        $f = ROOT_PATH . $rel;
        if (is_file($f) && str_contains($f, '/public/uploads/magazine/')) @unlink($f);
    }
}
