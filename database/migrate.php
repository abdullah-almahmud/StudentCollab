<?php
declare(strict_types=1);

function migrateDatabase(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS schema_version (version INTEGER NOT NULL DEFAULT 0)');
    $version = (int)$pdo->query('SELECT version FROM schema_version LIMIT 1')->fetchColumn();

    if ($version < 1) {
        $userCols = ['github_url', 'linkedin_url', 'website_url'];
        foreach ($userCols as $col) {
            try {
                $pdo->exec("ALTER TABLE users ADD COLUMN {$col} TEXT DEFAULT ''");
            } catch (PDOException) {
            }
        }
        try {
            $pdo->exec("ALTER TABLE portfolio_items ADD COLUMN project_url TEXT DEFAULT ''");
        } catch (PDOException) {
        }
        try {
            $pdo->exec("ALTER TABLE skills ADD COLUMN tools TEXT DEFAULT ''");
            $pdo->exec("ALTER TABLE skills ADD COLUMN learning_path TEXT DEFAULT ''");
        } catch (PDOException) {
        }
        try {
            $pdo->exec("ALTER TABLE gigs ADD COLUMN topic TEXT DEFAULT ''");
        } catch (PDOException) {
        }

        $pdo->exec('CREATE TABLE IF NOT EXISTS application_skills (
            application_id INTEGER NOT NULL,
            skill_id INTEGER NOT NULL,
            PRIMARY KEY (application_id, skill_id),
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS application_portfolio (
            application_id INTEGER NOT NULL,
            portfolio_item_id INTEGER NOT NULL,
            PRIMARY KEY (application_id, portfolio_item_id),
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            FOREIGN KEY (portfolio_item_id) REFERENCES portfolio_items(id) ON DELETE CASCADE
        )');

        $pdo->exec("CREATE TABLE IF NOT EXISTS core_member_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            requester_id INTEGER NOT NULL,
            recipient_id INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'accepted', 'rejected')),
            created_at TEXT DEFAULT (datetime('now')),
            FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE(requester_id, recipient_id)
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS core_members (
            user_id INTEGER NOT NULL,
            member_id INTEGER NOT NULL,
            created_at TEXT DEFAULT (datetime('now')),
            PRIMARY KEY (user_id, member_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (member_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $pdo->exec('CREATE TABLE IF NOT EXISTS research_topics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            skill_id INTEGER,
            description TEXT DEFAULT "",
            popularity INTEGER DEFAULT 1,
            FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE SET NULL
        )');

        $pdo->exec('DELETE FROM schema_version');
        $pdo->exec('INSERT INTO schema_version (version) VALUES (1)');
    }

    require_once __DIR__ . '/seed_extra.php';
    seedExtraData($pdo);
}
