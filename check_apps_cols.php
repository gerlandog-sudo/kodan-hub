<?php
require_once __DIR__ . '/src/Core/Database.php';
try {
    $db = \Kodan\Core\Database::getInstance()->getConnection();
    $stmt = $db->query("PRAGMA table_info(apps)");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columnas en tabla 'apps':\n";
    foreach ($cols as $c) {
        echo "- " . $c['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
