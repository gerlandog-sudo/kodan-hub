<?php
/**
 * MIGRACIÓN PARA CONFIGURACIÓN DE TEMA - KODAN-HUB
 * Este script inserta la opción 'theme_preference' en la tabla 'settings'.
 */
require_once __DIR__ . '/src/Core/Database.php';

try {
    $db = \App\Core\Database::getInstance()->getDB();
    echo "--- INICIANDO MIGRACIÓN DE CONFIGURACIÓN DE TEMA ---\n";

    // 1. Asegurar que exista el registro para 'theme_preference'
    // La tabla settings tiene una estructura simple de key-value.
    // Usamos INSERT OR IGNORE o verificamos si ya existe.
    $check = $db->select('settings', '*', ['key' => 'theme_preference']);
    if (empty($check)) {
        $db->insert('settings', [
            'key' => 'theme_preference',
            'value' => 'dark'
        ]);
        echo "✅ Configuración 'theme_preference' creada con valor 'dark'.\n";
    } else {
        echo "ℹ️ La configuración 'theme_preference' ya existe.\n";
    }

    echo "✨ Migración de tema completada con éxito.\n";

} catch (Exception $e) {
    die("❌ ERROR: " . $e->getMessage() . "\n");
}
