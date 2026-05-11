<?php
/**
 * MIGRACIÓN DE UNIFICACIÓN DE IDENTIFICADORES - KODAN-HUB
 * Este script unifica el uso de 'app_id' como el identificador técnico oficial.
 */
require_once __DIR__ . '/src/Core/Database.php';

try {
    $db = \Kodan\Core\Database::getInstance()->getConnection();
    echo "--- INICIANDO UNIFICACIÓN DE COLUMNAS ---\n";

    // 1. Asegurar que todos los datos de 'app_identifier' pasen a 'app_id'
    $affected = $db->exec("
        UPDATE apps 
        SET app_id = app_identifier 
        WHERE (app_id IS NULL OR app_id = '') 
        AND (app_identifier IS NOT NULL AND app_identifier != '')
    ");
    echo "✅ Sincronizados $affected registros desde app_identifier a app_id.\n";

    // 2. Para las apps que no tienen NINGUNO, ponerles 'MANUAL' o dejar vacío para que el handshake actúe
    // (Opcional, pero mejor dejarlo limpio)
    
    echo "✨ Unificación completada. Ya puedes usar 'app_id' en todo el sistema.\n";

} catch (Exception $e) {
    die("❌ ERROR: " . $e->getMessage() . "\n");
}
