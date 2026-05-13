<?php
/**
 * KODAN-HUB AI Gateway - Master Root File (v4.0 - Medoo Unified)
 */

// Autoload manual si composer no está disponible o para asegurar carga de nuestras clases
require_once __DIR__ . '/src/Core/Medoo.php';
require_once __DIR__ . '/src/Core/Database.php';
require_once __DIR__ . '/src/Services/GeminiProxy.php';
require_once __DIR__ . '/src/Services/OpenAIProxy.php';
require_once __DIR__ . '/src/Services/LogService.php';

use App\Core\Database;
use App\Services\GeminiProxy;
use App\Services\OpenAIProxy;
use App\Services\LogService;

// Inicializar Base de Datos (Medoo)
$db = Database::getInstance()->getDB();

// --- SEGURIDAD Y CORS ---
header('Content-Type: application/json; charset=UTF-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('/^https?:\/\/(.*\.?kodan\.software)$/', $origin)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-KODAN-TOKEN, X-KODAN-APP-ID, X-KODAN-APP-NAME, Authorization");
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; connect-src 'self' https://*.googleapis.com https://api.openai.com;");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

try {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    
    $token = $headers['X-KODAN-TOKEN'] ?? $headers['x-kodan-token'] ?? $_SERVER['HTTP_X_KODAN_TOKEN'] ?? null;
    $appId = $headers['X-KODAN-APP-ID'] ?? $headers['x-kodan-app-id'] ?? $_SERVER['HTTP_X_KODAN_APP_ID'] ?? null;

    // 1. Identificar Aplicación vía Medoo
    $app = null;
    if (!empty($token)) {
        $results = $db->select('apps', '*', ['token' => trim($token)]);
        $app = !empty($results) ? $results[0] : null;
    } elseif (!empty($appId)) {
        $results = $db->select('apps', '*', ['app_id' => trim($appId)]);
        $app = !empty($results) ? $results[0] : null;
    }

    // 2. Capturar Cuerpo del Mensaje (Una sola vez para evitar vaciar el stream)
    $inputRaw = file_get_contents('php://input');
    $inputJSON = json_decode($inputRaw, true);

    // 3. Handshake Automático (Registro / Recuperación)
    if (!empty($appId) && empty($inputRaw)) {
        if (!$app) {
            // Caso: Registro de nueva App
            $newToken = 'KDN-' . strtoupper(substr(md5(uniqid()), 0, 16));
            $appName = $headers['X-KODAN-APP-NAME'] ?? 'Nueva App';
            
            $db->insert('apps', [
                'name' => $appName,
                'app_id' => $appId,
                'token' => $newToken,
                'status' => 'active'
            ]);
            
            echo json_encode([
                'status' => 'success', 
                'new_kodan_token' => $newToken, 
                'message' => 'Handshake OK (Registrado)'
            ]);
        } else {
            // Caso: Recuperación de token para App existente
            echo json_encode([
                'status' => 'success', 
                'new_kodan_token' => $app['token'], 
                'message' => 'Handshake OK (Sincronizado)'
            ]);
        }
        exit;
    }

    if (!$app || $app['status'] !== 'active') {
        http_response_code(401);
        die(json_encode(['status' => 'error', 'message' => 'App no autorizada, pausada o Token inválido.']));
    }

    // 4. Procesar IA
    $action = $inputJSON['action'] ?? null;
    $payload = $inputJSON['payload'] ?? [];

    if ($action !== 'ai' || empty($payload)) {
        die(json_encode(['status' => 'error', 'message' => 'Acción IA no válida o sin contenido.']));
    }

    // 4. Catálogo de Servicios (Join Manual vía Medoo Syntax o Query Cruda Segura)
    $services = $db->query("
        SELECT s.*, c.protocol, c.identifier, c.endpoint, c.provider 
        FROM app_services s 
        JOIN ai_catalog c ON s.catalog_id = c.id 
        WHERE s.app_id = ? AND s.is_active = 1 
        ORDER BY s.priority ASC
    ", [$app['id']])->fetchAll();

    if (empty($services)) {
        die(json_encode(['status' => 'error', 'message' => 'App sin servicios configurados.']));
    }

    foreach ($services as $service) {
        $startTime = microtime(true);
        
        if ($service['protocol'] === 'openai-v1') {
            $result = OpenAIProxy::generateContent($service['api_key'], $service['identifier'], $payload, $service['endpoint']);
        } else {
            $result = GeminiProxy::generateContent($service['api_key'], $service['identifier'], $payload, $service['endpoint']);
        }

        $latency = round(microtime(true) - $startTime, 2);

        if ($result['status'] === 'success') {
            $tokens = LogService::extractTokens($result['data'], $service['protocol']);
            LogService::save($app['id'], $service['identifier'], $tokens[0], $tokens[1], $latency, 'success');
            
            echo json_encode([
                'status' => 'success',
                'response' => $result['response'] ?? '',
                'usage' => [
                    'prompt_tokens' => $tokens[0],
                    'completion_tokens' => $tokens[1],
                    'total_tokens' => $tokens[0] + $tokens[1]
                ],
                'hub_model' => $service['identifier'],
                'provider' => $service['provider'] ?? 'Unknown'
            ]);
            exit;
        } else {
            LogService::save($app['id'], $service['identifier'], 0, 0, $latency, 'error');
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'Todos los servicios de IA fallaron.']);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'HUB CRITICAL: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
}
