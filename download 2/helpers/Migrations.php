<?php
declare(strict_types=1);

class Migrations
{
    private static bool $ran = false;

    public static function run(PDO $db): void
    {
        if (self::$ran) return;
        self::$ran = true;

        try {
            $db->exec("PRAGMA journal_mode=WAL");
            $db->exec("PRAGMA foreign_keys=ON");

            self::ensureTables($db);
            self::ensureTeamTable($db);
            self::ensureCampaignsTable($db);
            self::ensureAchievementsTable($db);
            self::ensureMagazineTables($db);
            self::ensureColumns($db);
            self::ensureDefaultSettings($db);
            self::migrateToCrossroads($db);
            self::seedTestAdmin($db);
        } catch (Throwable $e) {
            Logger::error('migrations', $e->getMessage());
        }
    }

    private static function ensureCampaignsTable(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS campaigns (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            title      TEXT NOT NULL DEFAULT '',
            image      TEXT NOT NULL DEFAULT '',
            link       TEXT NOT NULL DEFAULT '',
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_visible INTEGER NOT NULL DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now'))
        )");
    }

    private static function ensureAchievementsTable(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS achievements (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            title      TEXT NOT NULL DEFAULT '',
            body       TEXT NOT NULL DEFAULT '',
            when_text  TEXT NOT NULL DEFAULT '',
            images     TEXT NOT NULL DEFAULT '[]',
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_visible INTEGER NOT NULL DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now'))
        )");
        // team_members.bio (short description) — added after first release
        $cols = array_column($db->query("PRAGMA table_info(team_members)")->fetchAll(), 'name');
        if ($cols && !in_array('bio', $cols, true)) $db->exec("ALTER TABLE team_members ADD COLUMN bio TEXT NOT NULL DEFAULT ''");
    }

    private static function ensureMagazineTables(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS articles (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            author_id   INTEGER NOT NULL,
            title       TEXT NOT NULL DEFAULT '',
            description TEXT NOT NULL DEFAULT '',
            cover_image TEXT NOT NULL DEFAULT '',
            body        TEXT NOT NULL DEFAULT '',
            created_at  TEXT DEFAULT (datetime('now')),
            updated_at  TEXT DEFAULT (datetime('now'))
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS article_likes (
            article_id INTEGER NOT NULL,
            user_id    INTEGER NOT NULL,
            created_at TEXT DEFAULT (datetime('now')),
            PRIMARY KEY (article_id, user_id)
        )");
        $cols = array_column($db->query("PRAGMA table_info(users)")->fetchAll(), 'name');
        if ($cols && !in_array('is_volunteer', $cols, true)) $db->exec("ALTER TABLE users ADD COLUMN is_volunteer INTEGER NOT NULL DEFAULT 0");
    }

    private static function ensureTeamTable(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS team_members (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            name       TEXT NOT NULL DEFAULT '',
            role       TEXT NOT NULL DEFAULT '',
            avatar     TEXT NOT NULL DEFAULT '',
            facebook   TEXT NOT NULL DEFAULT '',
            twitter    TEXT NOT NULL DEFAULT '',
            instagram  TEXT NOT NULL DEFAULT '',
            discord    TEXT NOT NULL DEFAULT '',
            telegram   TEXT NOT NULL DEFAULT '',
            sort_order INTEGER NOT NULL DEFAULT 0,
            is_visible INTEGER NOT NULL DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now'))
        )");
    }

    private static function ensureTables(PDO $db): void
    {
        // admin_announcements
        $db->exec("CREATE TABLE IF NOT EXISTS admin_announcements (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            title      TEXT NOT NULL,
            body       TEXT NOT NULL,
            type       TEXT NOT NULL DEFAULT 'info'
                       CHECK(type IN ('info','warning','plan','alert')),
            is_pinned  INTEGER NOT NULL DEFAULT 0,
            created_by INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");

        // email OTP — for password reset and registration verification
        // Create email_otp without restrictive CHECK — supports all OTP purposes
        $db->exec("CREATE TABLE IF NOT EXISTS email_otp (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            email      TEXT    NOT NULL COLLATE NOCASE,
            otp_hash   TEXT    NOT NULL,
            purpose    TEXT    NOT NULL DEFAULT 'reset',
            expires_at TEXT    NOT NULL,
            used       INTEGER NOT NULL DEFAULT 0,
            attempts   INTEGER NOT NULL DEFAULT 0,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        )");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_email_otp_email ON email_otp(email, purpose)");

        // Migrate old table that had CHECK(purpose IN ('reset','register')) — recreate without CHECK
        $tableInfo = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='email_otp'")->fetchColumn();
        if ($tableInfo && strpos($tableInfo, 'CHECK') !== false) {
            $db->exec("ALTER TABLE email_otp RENAME TO email_otp_old");
            $db->exec("CREATE TABLE email_otp (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL COLLATE NOCASE,
                otp_hash TEXT NOT NULL,
                purpose TEXT NOT NULL DEFAULT 'reset',
                expires_at TEXT NOT NULL,
                used INTEGER NOT NULL DEFAULT 0,
                attempts INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )");
            $db->exec("INSERT INTO email_otp SELECT id,email,otp_hash,purpose,expires_at,used,attempts,created_at FROM email_otp_old");
            $db->exec("DROP TABLE email_otp_old");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_email_otp_email ON email_otp(email, purpose)");
        }
    }

    private static function ensureColumns(PDO $db): void
    {
        $checks = [
            // table => [[col, definition], ...]
            'users' => [
                ['avatar',         "TEXT DEFAULT NULL"],
                ['bio',            "TEXT DEFAULT NULL"],
                ['totp_last_code', "TEXT DEFAULT NULL"],
                ['totp_last_used', "INTEGER DEFAULT 0"],
            ],
            'form_submissions' => [
                ['ai_review',      "TEXT DEFAULT NULL"],
                ['admin_note',     "TEXT DEFAULT NULL"],
                ['reviewed_by',    "INTEGER DEFAULT NULL"],
                ['reviewed_at',    "TEXT DEFAULT NULL"],
            ],
            'certificates' => [
                ['is_valid',       "INTEGER NOT NULL DEFAULT 1"],
                ['expires_at',     "TEXT DEFAULT NULL"],
            ],
            'challenges' => [
                ['pass_score',     "INTEGER NOT NULL DEFAULT 70"],
                ['max_attempts',   "INTEGER NOT NULL DEFAULT 0"],
                ['time_limit_min', "INTEGER NOT NULL DEFAULT 0"],
            ],
            'news' => [
                ['cover_image',    "TEXT DEFAULT NULL"],
                ['is_published',   "INTEGER NOT NULL DEFAULT 1"],
                ['category',       "TEXT DEFAULT NULL"],
                ['published_at',   "TEXT DEFAULT NULL"],
            ],
            'application_forms' => [
                ['prerequisite_form_id', "INTEGER DEFAULT NULL"],
            ],
            'sessions' => [
                ['last_active',    "TEXT DEFAULT NULL"],
                ['device_info',    "TEXT DEFAULT NULL"],
            ],
        ];

        foreach ($checks as $table => $cols) {
            try {
                // Security: validate table name against known alphanumeric pattern before interpolating
                if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $table)) {
                    Logger::error('migrations', "Invalid table name skipped: {$table}");
                    continue;
                }
                $existing = $db->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_COLUMN, 1);
                foreach ($cols as [$col, $def]) {
                    if (!in_array($col, $existing, true)) {
                        $db->exec("ALTER TABLE {$table} ADD COLUMN {$col} {$def}");
                        Logger::info('migrations', "Added column {$table}.{$col}");
                    }
                }
            } catch (Throwable $e) {
                Logger::error('migrations', "Column check failed for {$table}: " . $e->getMessage());
            }
        }
    }

    private static function ensureDefaultSettings(PDO $db): void
    {
        $defaults = [
            'site_name'               => 'مفترق الطرق',
            'site_logo'               => '',
            'site_logo_dark'          => '',
            'footer_text'             => '© ' . date('Y') . ' مفترق الطرق. جميع الحقوق محفوظة.',
            'contact_email'           => '',
            'hero_subtitle'           => 'مجتمع تطوعي يجمع الطموحين عند نقطة واحدة، ثم يشق لكل واحد منهم طريقه.',
            'ollama_endpoint'         => 'http://localhost:11434',
            'ollama_model'            => 'llama3.2',
            // Feature flags — all ON by default
            'feature_login'           => '1',
            'feature_register'        => '1',
            'feature_apply'           => '1',
            'feature_challenges'      => '1',
            'feature_courses'         => '1',
            'feature_news'            => '1',
            'feature_ai_review'       => '1',
            'maintenance_mode'        => '0',
            'maintenance_message'     => 'الموقع تحت الصيانة حاليا، سنعود قريبا.',
            'smtp_host'               => 'smtp.gmail.com',
            'smtp_port'               => '587',
            'smtp_user'               => '',
            'smtp_pass'               => '',
            'smtp_from_name'          => 'مفترق الطرق',
            'email_otp_register'      => '1',
            'email_otp_reset'         => '1',
        ];

        $ins = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
        foreach ($defaults as $k => $v) {
            $ins->execute([$k, $v]);
        }
    }

    /** One-time: rename the brand and turn the old cards (about / goals / features) into free-text sections. */
    private static function migrateToCrossroads(PDO $db): void
    {
        $get = $db->prepare("SELECT value FROM settings WHERE key=? LIMIT 1");
        $get->execute(['theme_crossroads_v1']);
        if ($get->fetchColumn() !== false) return;
        $set = $db->prepare("INSERT OR REPLACE INTO settings (key,value,updated_at) VALUES (?,?,datetime('now'))");
        $val = function (string $k) use ($get): string { $get->execute([$k]); $v = $get->fetchColumn(); return $v === false ? '' : (string)$v; };

        // Name
        $name = $val('site_name');
        if ($name === '' || $name === 'مسار') $set->execute(['site_name', 'مفترق الطرق']);
        $from = $val('smtp_from_name');
        if ($from === '' || $from === 'مسار') $set->execute(['smtp_from_name', 'مفترق الطرق']);
        $foot = $val('footer_text');
        if ($foot === '' || mb_strpos($foot, 'مسار') !== false) $set->execute(['footer_text', '© ' . date('Y') . ' مفترق الطرق. جميع الحقوق محفوظة.']);
        $mail = $val('contact_email');
        if (stripos($mail, 'masar') !== false) $set->execute(['contact_email', '']);

        // Sections from the old content (only if no sections exist yet)
        if ($val('content_sections') === '') {
            $secs = [];
            $rep = fn(string $s) => trim(str_replace('مسار', 'مفترق الطرق', $s));
            $aT = $rep($val('about_title')); $aB = $rep($val('about_text'));
            if ($aB !== '') $secs[] = ['title' => $aT !== '' ? $aT : 'من نحن', 'body' => $aB];
            $gT = $rep($val('goals_title')); $gB = str_replace('\\n', "\n", $val('goals_text'));
            $lines = array_values(array_filter(array_map('trim', explode("\n", $gB))));
            if ($lines) $secs[] = ['title' => $gT !== '' ? $gT : 'أهدافنا', 'body' => '- ' . implode("\n- ", array_map($rep, $lines))];
            if ($secs) $set->execute(['content_sections', json_encode($secs, JSON_UNESCAPED_UNICODE)]);
        }
        // Old hero title belonged to the previous brand
        $ht = $val('hero_title');
        if ($ht === '' || mb_strpos($ht, 'تمكين العقول') !== false) $set->execute(['hero_title', "مفترق الطرق\nمن هنا يبدأ طريقك"]);
        $hs = $val('hero_subtitle');
        if ($hs === '' || mb_strpos($hs, 'أكاديمية') !== false || mb_strpos($hs, 'مسار') !== false) $set->execute(['hero_subtitle', 'مجتمع تطوعي يجمع الطموحين عند نقطة واحدة، ثم يشق لكل واحد منهم طريقه.']);
        $terms = $val('terms_content');
        if ($terms !== '' && mb_strpos($terms, 'مسار') !== false) $set->execute(['terms_content', str_replace('منصة مسار', 'مفترق الطرق', str_replace('مسار', 'مفترق الطرق', $terms))]);

        if ($val('cert_signer_name') === '') { $set->execute(['cert_signer_name', 'Yousif Hamid']); $set->execute(['cert_signer_title', 'المؤسس']); }
        // The previous brand's logos must not carry over — the new logo is uploaded from Admin → الإعدادات
        $set->execute(['site_logo', '']);
        $set->execute(['site_logo_dark', '']);
        $set->execute(['theme_crossroads_v1', '1']);
    }

    /** One-time: test super-admin account (change the password after first login). */
    private static function seedTestAdmin(PDO $db): void
    {
        $get = $db->prepare("SELECT value FROM settings WHERE key=? LIMIT 1");
        $get->execute(['seed_test_admin_v1']);
        if ($get->fetchColumn() !== false) return;

        $email = 'zlliitn445@gmail.com';
        $hash  = password_hash('Mutimuti11!', PASSWORD_BCRYPT);
        $perms = json_encode(['manage_users','manage_content','manage_forms','manage_challenges','manage_certificates','run_sql','view_logs']);

        $q = $db->prepare("SELECT id FROM users WHERE email=? COLLATE NOCASE LIMIT 1");
        $q->execute([$email]);
        $id = $q->fetchColumn();
        if ($id) {
            $db->prepare("UPDATE users SET role='super_admin', permissions=?, password=?, is_active=1, updated_at=datetime('now') WHERE id=?")
               ->execute([$perms, $hash, $id]);
        } else {
            $username = 'mutimuti11';
            $c = $db->prepare("SELECT 1 FROM users WHERE username=? COLLATE NOCASE LIMIT 1");
            $c->execute([$username]);
            if ($c->fetchColumn()) $username .= '_' . random_int(100, 999);
            $db->prepare("INSERT INTO users (display_name,username,email,password,role,permissions,is_active,email_notify) VALUES (?,?,?,?,?,?,1,0)")
               ->execute(['Admin', $username, $email, $hash, 'super_admin', $perms]);
        }
        $db->prepare("INSERT OR REPLACE INTO settings (key,value,updated_at) VALUES ('seed_test_admin_v1','1',datetime('now'))")->execute();
    }
}
