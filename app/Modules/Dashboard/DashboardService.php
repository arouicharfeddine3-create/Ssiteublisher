<?php
namespace App\Modules\Dashboard;

use App\Core\Database;

class DashboardService
{
    public function getStats(?int $siteId): array
    {
        $db = Database::getInstance();
        $todayStart = date('Y-m-d 00:00:00');
        $tomorrowStart = date('Y-m-d 00:00:00', strtotime('+1 day'));

        return [
            'articles_published' => (int) $db->query("SELECT COUNT(*) FROM articles WHERE site_id = ? AND status = 'published'", [$siteId])->fetchColumn(),
            'articles_scheduled' => (int) $db->query("SELECT COUNT(*) FROM articles WHERE site_id = ? AND status = 'scheduled'", [$siteId])->fetchColumn(),
            'visitors_today' => (int) $db->query("SELECT COUNT(*) FROM analytics_visits WHERE site_id = ? AND visited_at >= ? AND visited_at < ?", [$siteId, $todayStart, $tomorrowStart])->fetchColumn(),
            'ai_usage_today' => (int) $db->query("SELECT COUNT(*) FROM ai_requests WHERE site_id = ? AND created_at >= ? AND created_at < ?", [$siteId, $todayStart, $tomorrowStart])->fetchColumn(),
            'queue_jobs' => (int) $db->query("SELECT COUNT(*) FROM queue_jobs")->fetchColumn(),
        ];
    }
}
