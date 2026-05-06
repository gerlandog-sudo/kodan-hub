<?php
/**
 * KODAN-HUB MIGRATION V6 - ARQUITECTURA AGNÓSTICA
 * Transforma el Hub en un orquestador multi-IA con Catálogo Global y Fallback.
 */
require_once __DIR__ . '/src/Core/Database.php';

try {
    $db = \Kodan\Core\Database::getInstance()->getConnection();
    echo "--- INICIANDO MIGRACIÓN V6 ---<br>";

    // 1. Crear tabla 'ai_catalog' (Catálogo Maestro)
    $db->exec("CREATE TABLE IF NOT EXISTS ai_catalog (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        provider TEXT NOT NULL,
        name TEXT NOT NULL,
        identifier TEXT NOT NULL UNIQUE,
        protocol TEXT NOT NULL, -- 'openai-v1' o 'gemini-v1'
        endpoint TEXT NOT NULL,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    echo "✅ Tabla 'ai_catalog' lista.<br>";

    // 2. Poblar Catálogo con modelos iniciales
    $initialModels = [
        [
            'provider' => 'NVIDIA',
            'name' => 'Kimi v2.6 (Multimodal)',
            'identifier' => 'moonshotai/kimi-k2.6',
            'protocol' => 'openai-v1',
            'endpoint' => 'https://integrate.api.nvidia.com/v1/chat/completions'
        ],
        [
            'provider' => 'Google',
            'name' => 'Gemini 1.5 Flash',
            'identifier' => 'gemini-1.5-flash',
            'protocol' => 'gemini-v1',
            'endpoint' => 'https://generativelanguage.googleapis.com/v1/models/'
        ],
        [
            'provider' => 'Google',
            'name' => 'Gemini 2.0 Flash',
            'identifier' => 'gemini-2.0-flash',
            'protocol' => 'gemini-v1',
            'endpoint' => 'https://generativelanguage.googleapis.com/v1/models/'
        ]
    ];

    foreach ($initialModels as $m) {
        $stmt = $db->prepare("INSERT OR IGNORE INTO ai_catalog (provider, name, identifier, protocol, endpoint) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$m['provider'], $m['name'], $m['identifier'], $m['protocol'], $m['endpoint']]);
    }
    echo "✅ Catálogo inicial poblado.<br>";

    // 3. Crear o Actualizar tabla 'app_services'
    $db->exec("CREATE TABLE IF NOT EXISTS app_services (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        app_id INTEGER NOT NULL,
        catalog_id INTEGER,
        api_key TEXT,
        priority INTEGER DEFAULT 1,
        is_active INTEGER DEFAULT 1,
        config_json TEXT DEFAULT '{}',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (app_id) REFERENCES apps(id)
    )");
    echo "✅ Tabla 'app_services' verificada/creada.<br>";

    // Asegurar que las columnas de la V6 existan por si la tabla ya existía (ej. de la V4)
    $stmt = $db->query("PRAGMA table_info(app_services)");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);

    if (!in_array('catalog_id', $cols)) {
        $db->exec("ALTER TABLE app_services ADD COLUMN catalog_id INTEGER");
        $db->exec("ALTER TABLE app_services ADD COLUMN priority INTEGER DEFAULT 1");
        $db->exec("ALTER TABLE app_services ADD COLUMN is_active INTEGER DEFAULT 1");
        echo "✅ Columnas V6 agregadas a tabla existente.<br>";
    }

    // 4. Vincular NVIDIA a SmartCook (Si existe la App)
    $smartcook = $db->query("SELECT id FROM apps WHERE name LIKE '%SmartCook%' OR app_id = 'KDN-SMARTCOOK-APP'")->fetch();
    if ($smartcook) {
        $catalogNvidia = $db->query("SELECT id FROM ai_catalog WHERE identifier = 'moonshotai/kimi-k2.6'")->fetch();
        if ($catalogNvidia) {
            // Desactivar otros servicios de SmartCook para poner este como principal
            $db->exec("UPDATE app_services SET priority = 2 WHERE app_id = " . $smartcook['id']);
            
            // Insertar NVIDIA Key
            $nvidiaKey = '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';
            $stmt = $db->prepare("INSERT INTO app_services (app_id, catalog_id, api_key, priority) VALUES (?, ?, ?, 1)");
            $stmt->execute([$smartcook['id'], $catalogNvidia['id'], $nvidiaKey]);
            echo "🚀 NVIDIA Kimi vinculado a SmartCook como prioridad 1.<br>";
        }
    }

    echo "<br>✨ **MIGRACIÓN V6 FINALIZADA EXITOSAMENTE.**";
    echo "<br>Ya puedes usar los nuevos modelos agnósticos.";

} catch (Exception $e) {
    die("❌ ERROR EN MIGRACIÓN V6: " . $e->getMessage());
}
