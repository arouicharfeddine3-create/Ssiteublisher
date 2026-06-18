<?php
namespace App\Database;

use App\Core\Database;

class Migrations
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run(): void
    {
        $driver = $this->db->driver();
        foreach ($this->schema($driver) as $sql) {
            $this->db->getPdo()->exec($sql);
        }
    }

    public function rollback(): void
    {
        // Implement rollback logic if needed
    }

    private function schema(string $driver): array
    {
        return $driver === 'sqlite' ? $this->sqliteSchema() : $this->mysqlSchema();
    }

    private function sqliteSchema(): array
    {
        return [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'editor',
                site_id INTEGER DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS sites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                domain TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                settings TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                site_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                slug TEXT NOT NULL,
                content TEXT,
                meta_description TEXT,
                featured_image TEXT DEFAULT NULL,
                status TEXT DEFAULT 'draft',
                source_url TEXT,
                scheduled_at TEXT DEFAULT NULL,
                published_at TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE INDEX IF NOT EXISTS articles_site_status ON articles (site_id, status)",
            "CREATE TABLE IF NOT EXISTS content_sources (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                type TEXT NOT NULL,
                url TEXT NOT NULL,
                css_selector TEXT,
                xpath TEXT,
                trust_score INTEGER DEFAULT 50,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS queue_jobs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                job_class TEXT NOT NULL,
                payload TEXT,
                available_at TEXT NOT NULL,
                reserved_at TEXT DEFAULT NULL,
                failed_at TEXT DEFAULT NULL,
                error TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE INDEX IF NOT EXISTS queue_jobs_available ON queue_jobs (available_at, reserved_at)",
            "CREATE TABLE IF NOT EXISTS analytics_visits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                site_id INTEGER DEFAULT NULL,
                ip_address TEXT,
                user_agent TEXT,
                url TEXT,
                visited_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE INDEX IF NOT EXISTS analytics_visits_site_date ON analytics_visits (site_id, visited_at)",
            "CREATE TABLE IF NOT EXISTS analytics_pageviews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                site_id INTEGER DEFAULT NULL,
                article_id INTEGER DEFAULT NULL,
                visited_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS knowledge_base (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                keyword TEXT NOT NULL UNIQUE,
                content TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )",
        ];
    }

    private function mysqlSchema(): array
    {
        $schemaFile = BASE_PATH . '/database/schema.sql';
        if (is_file($schemaFile)) {
            return array_values(array_filter(
                array_map('trim', explode(';', (string) file_get_contents($schemaFile))),
                fn(string $sql): bool => $sql !== ''
            ));
        }

        return [];
    }
}
