<?php
require_once __DIR__ . '/src/Core/Medoo.php';
require_once __DIR__ . '/src/Core/Database.php';

use App\Core\Database;

$db = Database::getInstance()->getDB();

echo "--- APPS ---\n";
$apps = $db->select('apps', '*', ['token[LIKE]' => '%SMARTCOOK%']);
if (empty($apps)) {
    $apps = $db->select('apps', '*', ['app_id[LIKE]' => '%SMARTCOOK%']);
}
print_r($apps);

echo "\n--- LOGS COUNT FOR SMARTCOOK ---\n";
if (!empty($apps)) {
    foreach ($apps as $app) {
        $count = $db->count('logs', ['app_id' => $app['id']]);
        echo "App ID {$app['id']} ({$app['name']}): $count logs\n";
    }
} else {
    echo "No app found with SMARTCOOK in token or app_id\n";
}

echo "\n--- CHECKING FOR ORPHAN LOGS ---\n";
$orphanLogs = $db->query("SELECT COUNT(*) as count FROM logs WHERE app_id NOT IN (SELECT id FROM apps)")->fetch();
echo "Orphan logs: " . $orphanLogs['count'] . "\n";
