<?php
declare(strict_types=1);

class Database
{
    private static ?PDO $pdo = null;

    public static function getInstance(): PDO
    {
        if (self::$pdo !== null) return self::$pdo;

        $raw  = Config::get('DB_PATH', 'database/muntalaq.sqlite');
        // Security: resolve to absolute path, must be inside project root
        $path = ROOT_PATH . DIRECTORY_SEPARATOR . ltrim(str_replace(['..', "\0"], '', $raw), '/\\');
        $dir  = dirname($path);

        if (!is_dir($dir)) mkdir($dir, 0750, true);

        self::$pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        self::$pdo->exec('PRAGMA journal_mode = WAL');
        self::$pdo->exec('PRAGMA synchronous  = NORMAL');
        self::$pdo->exec('PRAGMA foreign_keys = ON');
        self::$pdo->exec('PRAGMA temp_store   = MEMORY');
        self::$pdo->exec('PRAGMA cache_size   = -8000');  // 8 MB cache

        self::migrate(self::$pdo);
        return self::$pdo;
    }

    private static function migrate(PDO $db): void
    {
        $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            display_name     TEXT    NOT NULL,
            username         TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            telegram_username TEXT,
            email            TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            password         TEXT    NOT NULL,
            role             TEXT    NOT NULL DEFAULT 'user'
                             CHECK(role IN ('user','moderator','admin','super_admin')),
            permissions      TEXT,
            totp_secret      TEXT,
            two_fa_enabled   INTEGER NOT NULL DEFAULT 0,
            totp_last_code   TEXT DEFAULT NULL,
            totp_last_used   INTEGER DEFAULT 0,
            email_notify     INTEGER NOT NULL DEFAULT 1,
            is_active        INTEGER NOT NULL DEFAULT 1,
            last_login_at    TEXT,
            last_login_ip    TEXT,
            created_at       TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at       TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
        CREATE INDEX IF NOT EXISTS idx_users_email    ON users(email);

        CREATE TABLE IF NOT EXISTS sessions (
            id          TEXT    PRIMARY KEY,
            user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            ip_address  TEXT,
            user_agent  TEXT,
            last_active TEXT    NOT NULL DEFAULT (datetime('now')),
            remember_me INTEGER NOT NULL DEFAULT 0,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id);

        CREATE TABLE IF NOT EXISTS email_subscribers (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            email         TEXT    NOT NULL UNIQUE COLLATE NOCASE,
            source        TEXT    DEFAULT 'footer',
            subscribed_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS application_forms (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT    NOT NULL,
            description TEXT,
            cover_image TEXT,
            status      TEXT    NOT NULL DEFAULT 'closed' CHECK(status IN ('open','closed')),
            sort_order  INTEGER NOT NULL DEFAULT 0,
            created_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_forms_status ON application_forms(status);

        CREATE TABLE IF NOT EXISTS form_questions (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            form_id     INTEGER NOT NULL REFERENCES application_forms(id) ON DELETE CASCADE,
            label       TEXT    NOT NULL,
            type        TEXT    NOT NULL DEFAULT 'short_text',
            placeholder TEXT,
            options     TEXT,
            is_required INTEGER NOT NULL DEFAULT 1,
            sort_order  INTEGER NOT NULL DEFAULT 0
        );
        CREATE INDEX IF NOT EXISTS idx_fq_form ON form_questions(form_id);

        CREATE TABLE IF NOT EXISTS form_submissions (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            form_id      INTEGER NOT NULL REFERENCES application_forms(id),
            user_id      INTEGER REFERENCES users(id) ON DELETE SET NULL,
            answers      TEXT    NOT NULL DEFAULT '{}',
            status       TEXT    NOT NULL DEFAULT 'pending'
                         CHECK(status IN ('pending','accepted','rejected')),
            admin_note   TEXT,
            reviewed_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
            reviewed_at  TEXT,
            submitted_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_fs_form   ON form_submissions(form_id);
        CREATE INDEX IF NOT EXISTS idx_fs_status ON form_submissions(status);
        CREATE INDEX IF NOT EXISTS idx_fs_user   ON form_submissions(user_id);

        CREATE TABLE IF NOT EXISTS news (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            title        TEXT    NOT NULL,
            description  TEXT    NOT NULL,
            cover_image  TEXT,
            is_published INTEGER NOT NULL DEFAULT 0,
            published_at TEXT,
            created_by   INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at   TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_news_pub ON news(is_published, published_at);

        CREATE TABLE IF NOT EXISTS settings (
            key        TEXT PRIMARY KEY,
            value      TEXT,
            updated_at TEXT NOT NULL DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS rate_limits (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address   TEXT    NOT NULL,
            action       TEXT    NOT NULL,
            attempts     INTEGER NOT NULL DEFAULT 1,
            window_start TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_rl_ip ON rate_limits(ip_address, action);

        CREATE TABLE IF NOT EXISTS system_logs (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            level      TEXT    NOT NULL DEFAULT 'info'
                       CHECK(level IN ('debug','info','warning','error','critical')),
            action     TEXT    NOT NULL,
            message    TEXT    NOT NULL,
            context    TEXT,
            user_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
            ip_address TEXT,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_logs_level   ON system_logs(level);
        CREATE INDEX IF NOT EXISTS idx_logs_created ON system_logs(created_at);

        CREATE TABLE IF NOT EXISTS certificates (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            token         TEXT    NOT NULL UNIQUE,
            user_id       INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            form_id       INTEGER REFERENCES application_forms(id) ON DELETE SET NULL,
            challenge_id  INTEGER REFERENCES challenges(id) ON DELETE SET NULL,
            submission_id INTEGER REFERENCES form_submissions(id) ON DELETE SET NULL,
            title         TEXT    NOT NULL,
            description   TEXT,
            issued_by     INTEGER REFERENCES users(id) ON DELETE SET NULL,
            issued_at     TEXT    NOT NULL DEFAULT (datetime('now')),
            expires_at    TEXT,
            is_valid      INTEGER NOT NULL DEFAULT 1
        );
        CREATE INDEX IF NOT EXISTS idx_cert_token ON certificates(token);
        CREATE INDEX IF NOT EXISTS idx_cert_user  ON certificates(user_id);

        -- ── Challenges ────────────────────────────────────────────
        CREATE TABLE IF NOT EXISTS challenges (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            title         TEXT    NOT NULL,
            description   TEXT,
            cover_image   TEXT,
            category      TEXT    DEFAULT 'عام',
            passing_score INTEGER NOT NULL DEFAULT 80,
            time_limit    INTEGER DEFAULT NULL,  -- minutes, NULL = no limit
            status        TEXT    NOT NULL DEFAULT 'draft'
                          CHECK(status IN ('draft','published','archived')),
            randomize     INTEGER NOT NULL DEFAULT 0,
            created_by    INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_ch_status ON challenges(status);

        CREATE TABLE IF NOT EXISTS challenge_questions (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            challenge_id INTEGER NOT NULL REFERENCES challenges(id) ON DELETE CASCADE,
            question     TEXT    NOT NULL,   -- Markdown supported
            points       INTEGER NOT NULL DEFAULT 1,
            sort_order   INTEGER NOT NULL DEFAULT 0
        );
        CREATE INDEX IF NOT EXISTS idx_cq_challenge ON challenge_questions(challenge_id);

        CREATE TABLE IF NOT EXISTS challenge_options (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            question_id INTEGER NOT NULL REFERENCES challenge_questions(id) ON DELETE CASCADE,
            option_text TEXT    NOT NULL,
            is_correct  INTEGER NOT NULL DEFAULT 0,
            sort_order  INTEGER NOT NULL DEFAULT 0
        );
        CREATE INDEX IF NOT EXISTS idx_co_question ON challenge_options(question_id);

        CREATE TABLE IF NOT EXISTS challenge_attempts (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            challenge_id  INTEGER NOT NULL REFERENCES challenges(id) ON DELETE CASCADE,
            user_id       INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            status        TEXT    NOT NULL DEFAULT 'in_progress'
                          CHECK(status IN ('in_progress','completed')),
            score         INTEGER DEFAULT 0,
            max_score     INTEGER DEFAULT 0,
            percentage    REAL    DEFAULT 0,
            passed        INTEGER NOT NULL DEFAULT 0,
            current_q     INTEGER NOT NULL DEFAULT 0,  -- index of current question
            answers       TEXT    NOT NULL DEFAULT '{}', -- JSON: {question_id: [option_ids]}
            started_at    TEXT    NOT NULL DEFAULT (datetime('now')),
            completed_at  TEXT,
            time_spent    INTEGER DEFAULT 0,  -- seconds
            UNIQUE(challenge_id, user_id)
        );
        CREATE INDEX IF NOT EXISTS idx_ca_user      ON challenge_attempts(user_id);
        CREATE INDEX IF NOT EXISTS idx_ca_challenge ON challenge_attempts(challenge_id);
        CREATE INDEX IF NOT EXISTS idx_ca_passed    ON challenge_attempts(passed, completed_at);

        -- Courses
        CREATE TABLE IF NOT EXISTS courses (
            id                  INTEGER PRIMARY KEY AUTOINCREMENT,
            title               TEXT    NOT NULL,
            description         TEXT,
            cover_image         TEXT,
            category            TEXT    DEFAULT 'عام',
            status              TEXT    NOT NULL DEFAULT 'draft'
                                CHECK(status IN ('draft','published','archived')),
            challenge_id        INTEGER REFERENCES challenges(id) ON DELETE SET NULL,
            certificate_enabled INTEGER NOT NULL DEFAULT 1,
            created_by          INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at          TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at          TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_courses_status ON courses(status);
        CREATE INDEX IF NOT EXISTS idx_courses_challenge ON courses(challenge_id);

        CREATE TABLE IF NOT EXISTS course_lessons (
            id                INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id         INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
            title             TEXT    NOT NULL,
            description       TEXT,
            cover_image       TEXT,
            youtube_url       TEXT,
            video_stream_url  TEXT,
            duration_seconds  INTEGER NOT NULL DEFAULT 0,
            is_published      INTEGER NOT NULL DEFAULT 1,
            sort_order        INTEGER NOT NULL DEFAULT 0,
            created_at        TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at        TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_course_lessons_course ON course_lessons(course_id, sort_order);

        CREATE TABLE IF NOT EXISTS course_enrollments (
            id                INTEGER PRIMARY KEY AUTOINCREMENT,
            course_id         INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
            user_id           INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            status            TEXT    NOT NULL DEFAULT 'active'
                              CHECK(status IN ('active','completed')),
            current_lesson_id INTEGER REFERENCES course_lessons(id) ON DELETE SET NULL,
            progress_percent  REAL    NOT NULL DEFAULT 0,
            started_at        TEXT    NOT NULL DEFAULT (datetime('now')),
            completed_at      TEXT,
            updated_at        TEXT    NOT NULL DEFAULT (datetime('now')),
            UNIQUE(course_id, user_id)
        );
        CREATE INDEX IF NOT EXISTS idx_course_enrollments_user ON course_enrollments(user_id, status);

        CREATE TABLE IF NOT EXISTS course_lesson_progress (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            lesson_id        INTEGER NOT NULL REFERENCES course_lessons(id) ON DELETE CASCADE,
            course_id        INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
            user_id          INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            watched_seconds  INTEGER NOT NULL DEFAULT 0,
            duration_seconds INTEGER NOT NULL DEFAULT 0,
            completed        INTEGER NOT NULL DEFAULT 0,
            updated_at       TEXT    NOT NULL DEFAULT (datetime('now')),
            UNIQUE(lesson_id, user_id)
        );
        CREATE INDEX IF NOT EXISTS idx_clp_user_course ON course_lesson_progress(user_id, course_id);

        CREATE TABLE IF NOT EXISTS course_lesson_files (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            lesson_id     INTEGER NOT NULL REFERENCES course_lessons(id) ON DELETE CASCADE,
            course_id     INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
            original_name TEXT    NOT NULL,
            file_path     TEXT    NOT NULL,
            mime_type     TEXT,
            file_size     INTEGER NOT NULL DEFAULT 0,
            created_by    INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_course_files_lesson ON course_lesson_files(lesson_id);

        CREATE TABLE IF NOT EXISTS course_comments (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            lesson_id  INTEGER NOT NULL REFERENCES course_lessons(id) ON DELETE CASCADE,
            course_id  INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
            user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            parent_id  INTEGER REFERENCES course_comments(id) ON DELETE CASCADE,
            body       TEXT    NOT NULL,
            is_deleted INTEGER NOT NULL DEFAULT 0,
            created_at TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_course_comments_lesson ON course_comments(lesson_id, created_at);
        CREATE INDEX IF NOT EXISTS idx_course_comments_parent ON course_comments(parent_id);

        CREATE TABLE IF NOT EXISTS course_lesson_likes (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            lesson_id  INTEGER NOT NULL REFERENCES course_lessons(id) ON DELETE CASCADE,
            course_id  INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
            user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            created_at TEXT    NOT NULL DEFAULT (datetime('now')),
            UNIQUE(lesson_id, user_id)
        );
        CREATE INDEX IF NOT EXISTS idx_course_likes_lesson ON course_lesson_likes(lesson_id);

        -- ── Device Fingerprints & Advanced Rate Limiting ───────────
        CREATE TABLE IF NOT EXISTS device_fingerprints (
            id             INTEGER PRIMARY KEY AUTOINCREMENT,
            fingerprint    TEXT    NOT NULL,
            user_id        INTEGER REFERENCES users(id) ON DELETE CASCADE,
            ip_address     TEXT,
            user_agent     TEXT,
            screen_res     TEXT,
            timezone       TEXT,
            language       TEXT,
            platform       TEXT,
            canvas_hash    TEXT,
            webgl_hash     TEXT,
            audio_hash     TEXT,
            first_seen     TEXT    NOT NULL DEFAULT (datetime('now')),
            last_seen      TEXT    NOT NULL DEFAULT (datetime('now')),
            is_trusted     INTEGER NOT NULL DEFAULT 0,
            is_blocked     INTEGER NOT NULL DEFAULT 0
        );
        CREATE INDEX IF NOT EXISTS idx_df_fp   ON device_fingerprints(fingerprint);
        CREATE INDEX IF NOT EXISTS idx_df_user ON device_fingerprints(user_id);

        CREATE TABLE IF NOT EXISTS rate_limit_fingerprints (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            fingerprint  TEXT    NOT NULL,
            ip_address   TEXT,
            action       TEXT    NOT NULL,
            attempts     INTEGER NOT NULL DEFAULT 1,
            blocked_until TEXT,
            window_start TEXT    NOT NULL DEFAULT (datetime('now')),
            created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_rlf_fp    ON rate_limit_fingerprints(fingerprint, action);
        CREATE INDEX IF NOT EXISTS idx_rlf_ip    ON rate_limit_fingerprints(ip_address, action);

        -- ── Email Templates ───────────────────────────────────────
        CREATE TABLE IF NOT EXISTS email_templates (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            name        TEXT    NOT NULL UNIQUE,
            subject     TEXT    NOT NULL,
            html_body   TEXT    NOT NULL,
            plain_body  TEXT,
            variables   TEXT,  -- JSON: available variables
            is_system   INTEGER NOT NULL DEFAULT 0,
            created_by  INTEGER REFERENCES users(id) ON DELETE SET NULL,
            created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
            updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
        );

        -- ── Bulk Email Queue ──────────────────────────────────────
        CREATE TABLE IF NOT EXISTS email_queue (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            template_id  INTEGER REFERENCES email_templates(id) ON DELETE SET NULL,
            subject      TEXT,
            html_body    TEXT,
            recipient_email TEXT NOT NULL,
            recipient_name  TEXT,
            recipient_user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
            status       TEXT    NOT NULL DEFAULT 'pending'
                          CHECK(status IN ('pending','sending','sent','failed')),
            priority     INTEGER NOT NULL DEFAULT 5,
            attempts     INTEGER NOT NULL DEFAULT 0,
            last_error   TEXT,
            scheduled_at TEXT,
            sent_at      TEXT,
            created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_eq_status ON email_queue(status, scheduled_at);

        -- ── User Activity Log (Extended) ──────────────────────────
        CREATE TABLE IF NOT EXISTS user_activity_log (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id       INTEGER REFERENCES users(id) ON DELETE CASCADE,
            action        TEXT    NOT NULL,
            ip_address    TEXT,
            user_agent    TEXT,
            fingerprint   TEXT,
            country_code  TEXT,
            city          TEXT,
            latitude      REAL,
            longitude     REAL,
            device_type   TEXT,
            browser       TEXT,
            os            TEXT,
            extra_data    TEXT,  -- JSON for additional context
            created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
        );
        CREATE INDEX IF NOT EXISTS idx_ual_user   ON user_activity_log(user_id);
        CREATE INDEX IF NOT EXISTS idx_ual_action ON user_activity_log(action);
        CREATE INDEX IF NOT EXISTS idx_ual_created ON user_activity_log(created_at);
        ");

        // ── Runtime migrations (safe for existing databases) ──
        $col    = static function(string $t) use ($db): array {
            return array_column($db->query("PRAGMA table_info({$t})")->fetchAll(), 'name');
        };
        $tables = array_column($db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(), 'name');

        if (in_array('form_questions',    $tables) && !in_array('options',               $col('form_questions')))
            $db->exec("ALTER TABLE form_questions ADD COLUMN options TEXT DEFAULT '[]'");

        if (in_array('users',             $tables) && !in_array('permissions',           $col('users')))
            $db->exec("ALTER TABLE users ADD COLUMN permissions TEXT DEFAULT '[]'");

        if (in_array('application_forms', $tables) && !in_array('prerequisite_form_id', $col('application_forms')))
            $db->exec("ALTER TABLE application_forms ADD COLUMN prerequisite_form_id INTEGER REFERENCES application_forms(id) ON DELETE SET NULL");

        if (in_array('form_submissions',  $tables) && !in_array('ai_review',            $col('form_submissions')))
            $db->exec("ALTER TABLE form_submissions ADD COLUMN ai_review TEXT");

        if (in_array('users', $tables) && !in_array('email_2fa_enabled', $col('users')))
            $db->exec("ALTER TABLE users ADD COLUMN email_2fa_enabled INTEGER NOT NULL DEFAULT 0");

        if (in_array('users', $tables) && !in_array('avatar', $col('users')))
            $db->exec("ALTER TABLE users ADD COLUMN avatar TEXT DEFAULT NULL");

        if (in_array('users', $tables) && !in_array('bio', $col('users')))
            $db->exec("ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL");

        if (in_array('certificates', $tables) && !in_array('course_id', $col('certificates')))
            $db->exec("ALTER TABLE certificates ADD COLUMN course_id INTEGER REFERENCES courses(id) ON DELETE SET NULL");

        $db->exec("CREATE TABLE IF NOT EXISTS notifications (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id    INTEGER REFERENCES users(id) ON DELETE CASCADE,
            type       TEXT NOT NULL DEFAULT 'system',
            title      TEXT NOT NULL,
            message    TEXT,
            link       TEXT,
            is_read    INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        )");

        // ── System email templates ──
        $systemTemplates = [
            [
                'name' => 'welcome',
                'subject' => 'مرحبا بك في {site_name}',
                'html_body' => '<div style="font-family:Tahoma,Arial;direction:rtl;padding:20px"><h2>مرحبا {display_name}!</h2><p>شكرا لتسجيلك في {site_name}. نحن متحمسون لوجودك معنا.</p><p>يمكنك الآن:</p><ul><li>تقديم طلبات على البرامج</li><li>خوض التحديات</li><li>حصول على شهادات</li></ul></div>',
                'is_system' => 1,
            ],
            [
                'name' => 'password_reset',
                'subject' => 'استعادة كلمة المرور - {site_name}',
                'html_body' => '<div style="font-family:Tahoma,Arial;direction:rtl;padding:20px"><h2>مرحبا {display_name}</h2><p>لقد طلبت استعادة كلمة المرور الخاصة بك.</p><p>رمز التحقق: <strong>{otp_code}</strong></p><p>صالح لمدة 10 دقائق.</p></div>',
                'is_system' => 1,
            ],
            [
                'name' => 'login_alert',
                'subject' => 'تنبيه تسجيل دخول جديد - {site_name}',
                'html_body' => '<div style="font-family:Tahoma,Arial;direction:rtl;padding:20px"><h2>مرحبا {display_name}</h2><p>تم تسجيل دخول جديد إلى حسابك:</p><ul><li>التاريخ: {datetime}</li><li>الموقع: {location}</li><li>الجهاز: {device_type}</li><li>المتصفح: {browser}</li></ul><p>إذا لم يكن هذا أنت، قم بتغيير كلمة مرورك فورا.</p></div>',
                'is_system' => 1,
            ],
            [
                'name' => 'new_workshop',
                'subject' => 'ورشة جديدة متاحة - {site_name}',
                'html_body' => '<div style="font-family:Tahoma,Arial;direction:rtl;padding:20px"><h2>ورشة جديدة!</h2><p>مرحبا {display_name}،</p><p>تم إضافة ورشة جديدة: <strong>{workshop_title}</strong></p><p>{workshop_description}</p><a href="{workshop_link}" style="display:inline-block;background:#7c411a;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">سجل الآن</a></div>',
                'is_system' => 1,
            ],
            [
                'name' => 'certificate_issued',
                'subject' => 'حصلت على شهادة جديدة - {site_name}',
                'html_body' => '<div style="font-family:Tahoma,Arial;direction:rtl;padding:20px"><h2>تهانينا {display_name}!</h2><p>لقد حصلت على شهادة جديدة:</p><h3>{certificate_title}</h3><p>{certificate_description}</p><a href="{certificate_link}" style="display:inline-block;background:#7c411a;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px">عرض الشهادة</a></div>',
                'is_system' => 1,
            ],
        ];
        $insTemplate = $db->prepare("INSERT OR IGNORE INTO email_templates (name, subject, html_body, is_system) VALUES (?, ?, ?, ?)");
        foreach ($systemTemplates as $t) $insTemplate->execute([$t['name'], $t['subject'], $t['html_body'], $t['is_system']]);

        // Default settings
        $defaults = [
            'site_name'        => 'مفترق الطرق',
            'hero_title'       => "مفترق الطرق\nمن هنا يبدأ طريقك",
            'hero_subtitle'    => 'مجتمع تطوعي يجمع الطموحين عند نقطة واحدة، ثم يشق لكل واحد منهم طريقه.',
            'content_sections' => json_encode([
                ['title' => 'رسالتنا', 'body' => "نؤمن أن لكل شخص طريقا يناسبه، ومهمتنا أن نكون المكان اللي يتقاطع فيه الطموح مع الفرصة.\n\nنوفر برامج وتحديات ومجتمعا يدعمك لتكمل طريقك بثقة."],
                ['title' => 'هدفنا', 'body' => "- توفير فرص تعلم حقيقية للجميع\n- بناء مجتمع يشجع التجربة والمبادرة\n- تحويل الجهد إلى شهادات وإنجازات موثقة"],
            ], JSON_UNESCAPED_UNICODE),
            'footer_text'      => '© ' . date('Y') . ' مفترق الطرق. جميع الحقوق محفوظة.',
            'contact_email'    => '',
            'terms_content'    => "**الشروط والأحكام**\n\nبالتسجيل في مفترق الطرق، فإنك توافق على:\n\n1. استخدام المنصة بشكل مسؤول.\n2. تقديم معلومات صحيحة ودقيقة.\n3. احترام جميع أعضاء المجتمع.",
            'social_instagram' => '',
            'social_twitter'   => '',
            'social_linkedin'  => '',
            'feature_courses'   => '1',
        ];
        $ins = $db->prepare("INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)");
        foreach ($defaults as $k => $v) $ins->execute([$k, $v]);

        // Default super admin
        if (!$db->query("SELECT id FROM users WHERE username='admin' LIMIT 1")->fetch()) {
            $db->prepare("INSERT INTO users (display_name,username,telegram_username,email,password,role)
                          VALUES (?,?,?,?,?,?)")
               ->execute(['مشرف النظام','admin','@admin','admin@localhost',
                          password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost'=>12]),'super_admin']);
        }
    }
}
