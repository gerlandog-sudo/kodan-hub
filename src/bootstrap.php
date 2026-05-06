<?php
/**
 * BOOTSTRAP KODAN-HUB (Medoo Edition)
 * Configuración central de base de datos y utilidades.
 */

require_once __DIR__ . '/Core/Medoo.php';
use Medoo\Medoo;

// Configuración de Base de Datos (Ajustado para cPanel)
try {
    $db = new Medoo([
        'type' => 'sqlite',
        'database' => __DIR__ . '/../database/database.sqlite'
    ]);
} catch (Exception $e) {
    die("Error crítico de base de datos: " . $e->getMessage());
}

/**
 * Envia una respuesta JSON estandarizada.
 * @param string $status 'success' o 'error'
 * @param mixed $data Datos de la respuesta
 * @param string $message Mensaje informativo
 * @param int $code Código HTTP
 */
function sendResponse($status, $data = [], $message = '', $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
