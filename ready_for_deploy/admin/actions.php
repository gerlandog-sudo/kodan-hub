<?php
require_once 'auth.php';
if (!isLoggedIn()) die('Unauthorized');

// Carga manual de servicios y core
require_once __DIR__ . '/../src/Core/Medoo.php';
require_once __DIR__ . '/../src/Core/Database.php';
require_once __DIR__ . '/../src/Services/GeminiProxy.php';
require_once __DIR__ . '/../src/Services/OpenAIProxy.php';
require_once __DIR__ . '/../src/Services/LogService.php';

use App\Services\LogService;
use App\Services\GeminiProxy;
use App\Services\OpenAIProxy;
use App\Core\Database;

$action = $_REQUEST['action'] ?? '';

/**
 * Genera un token consistente: KDN-[APP_ID]-[HASH]
 */
function generateKodanToken($name) {
    $cleanName = preg_replace('/[^A-Za-z0-9 ]/', '', $name);
    $words = explode(' ', $cleanName);
    $prefix = '';
    if (count($words) > 1) {
        foreach ($words as $w) { $prefix .= strtoupper(substr($w, 0, 1)); }
    } else {
        $prefix = strtoupper(substr($cleanName, 0, 2));
    }
    return 'KDN-' . $prefix . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
}

function redirectBack($error = null) {
    $tab = $_REQUEST['redirect_tab'] ?? 'apps-tab';
    $url = "index.php?tab=$tab";
    if ($error) $url .= "&error=$error";
    header("Location: $url");
    exit;
}

try {
    $dbWrapper = Database::getInstance();
    $db = $dbWrapper->getDB();
    
    switch ($action) {
        case 'add_app':
            $name = $_POST['name'] ?? '';
            $token = $_POST['custom_token'] ?: generateKodanToken($name);
            
            $db->insert('apps', [
                'name' => $name,
                'token' => $token,
                'status' => 'active'
            ]);
            redirectBack();
            break;

        case 'rotate_token':
            $id = $_GET['id'] ?? 0;
            $results = $db->select('apps', ['name', 'token'], ['id' => $id]);
            $app = !empty($results) ? $results[0] : null;
            if ($app) {
                $newToken = generateKodanToken($app['name']);
                $db->update('apps', [
                    'old_token' => $app['token'],
                    'token' => $newToken
                ], ['id' => $id]);
            }
            redirectBack();
            break;

        case 'update_app_name':
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            if ($id && $name) {
                $db->update('apps', ['name' => $name], ['id' => $id]);
            }
            redirectBack();
            break;

        case 'delete_app':
            $id = $_GET['id'] ?? 0;
            
            // Ya no bloqueamos por logs, porque ahora archivamos (Soft Delete)
            // Esto preserva la integridad referencial para las estadísticas
            $db->update('apps', ['status' => 'archived'], ['id' => $id]);
            
            // Opcional: Desvincular servicios para liberar cuotas de IA asignadas
            $db->delete('app_services', ['app_id' => $id]);
            
            redirectBack();
            break;

        case 'toggle_status':
            $id = $_GET['id'] ?? 0;
            $db->query("UPDATE apps SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?", [$id]);
            redirectBack();
            break;

        case 'get_catalog_ajax':
            header('Content-Type: application/json');
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, intval($_GET['limit'] ?? 10));
            $offset = ($page - 1) * $limit;

            try {
                $total = $db->query("SELECT COUNT(*) FROM ai_catalog")->fetchColumn();
                $data = $db->query("SELECT * FROM ai_catalog ORDER BY provider ASC, name ASC LIMIT $limit OFFSET $offset")->fetchAll();
                
                echo json_encode([
                    'status' => 'success', 
                    'data' => $data,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => intval($total),
                        'total_pages' => ceil($total / $limit)
                    ]
                ]);
            } catch (\Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            break;

        case 'get_services_ajax':
            header('Content-Type: application/json');
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, intval($_GET['limit'] ?? 10));
            $offset = ($page - 1) * $limit;

            try {
                $total = $db->query("SELECT COUNT(*) FROM app_services")->fetchColumn();
                $data = $db->query("
                    SELECT s.*, a.name as app_name, c.name as model_name, c.provider 
                    FROM app_services s 
                    JOIN apps a ON s.app_id = a.id 
                    JOIN ai_catalog c ON s.catalog_id = c.id 
                    ORDER BY a.name ASC, s.priority ASC
                    LIMIT $limit OFFSET $offset
                ")->fetchAll();
                
                echo json_encode([
                    'status' => 'success', 
                    'data' => $data,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => intval($total),
                        'total_pages' => ceil($total / $limit)
                    ]
                ]);
            } catch (\Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            break;

        case 'add_catalog_model':
            $db->insert('ai_catalog', [
                'provider' => $_POST['provider'] ?? '',
                'name' => $_POST['name'] ?? '',
                'identifier' => $_POST['identifier'] ?? '',
                'protocol' => $_POST['protocol'] ?? 'openai-v1',
                'endpoint' => $_POST['endpoint'] ?? ''
            ]);
            redirectBack();
            break;

        case 'add_app_service':
            $db->insert('app_services', [
                'app_id' => $_POST['app_id'] ?? 0,
                'catalog_id' => $_POST['catalog_id'] ?? 0,
                'api_key' => $_POST['api_key'] ?? '',
                'priority' => $_POST['priority'] ?? 1,
                'is_active' => 1
            ]);
            redirectBack();
            break;

        case 'delete_service':
            $db->delete('app_services', ['id' => $_GET['id'] ?? 0]);
            redirectBack();
            break;

        case 'edit_catalog_model':
            $db->update('ai_catalog', [
                'provider' => $_POST['provider'] ?? '',
                'name' => $_POST['name'] ?? '',
                'identifier' => $_POST['identifier'] ?? '',
                'protocol' => $_POST['protocol'] ?? 'openai-v1',
                'endpoint' => $_POST['endpoint'] ?? ''
            ], ['id' => $_POST['id'] ?? 0]);
            redirectBack();
            break;

        case 'edit_app_service':
            $db->update('app_services', [
                'catalog_id' => $_POST['catalog_id'] ?? 0,
                'api_key' => $_POST['api_key'] ?? '',
                'priority' => $_POST['priority'] ?? 1
            ], ['id' => $_POST['id'] ?? 0]);
            redirectBack();
            break;

        case 'delete_catalog_model':
            $db->delete('ai_catalog', ['id' => $_GET['id'] ?? 0]);
            redirectBack();
            break;

        case 'test_service_ajax':
            header('Content-Type: application/json');
            $id = $_GET['id'] ?? 0;
            $results = $db->query("
                SELECT s.*, c.protocol, c.identifier, c.endpoint 
                FROM app_services s 
                JOIN ai_catalog c ON s.catalog_id = c.id 
                WHERE s.id = ?
            ", [$id])->fetchAll();
            $service = !empty($results) ? $results[0] : null;

            if ($service) {
                $startTime = microtime(true);
                $p = ["messages" => [["role" => "user", "content" => "respond only with 'pong'"]]];
                
                if ($service['protocol'] === 'openai-v1') {
                    $res = OpenAIProxy::generateContent($service['api_key'], $service['identifier'], $p, $service['endpoint']);
                } else {
                    $res = GeminiProxy::generateContent($service['api_key'], $service['identifier'], $p, $service['endpoint']);
                }

                $latency = round(microtime(true) - $startTime, 2);

                if ($res['status'] === 'success') {
                    $tokens = LogService::extractTokens($res['data'], $service['protocol']);
                    LogService::save($service['app_id'], $service['identifier'], $tokens[0], $tokens[1], $latency, 'success');
                } else {
                    LogService::save($service['app_id'], $service['identifier'], 0, 0, $latency, 'error');
                }

                $res['debug_request'] = $p;
                $res['debug_endpoint'] = $service['endpoint'];
                $res['debug_response'] = $res['data'];
                $res['latency'] = $latency . 's';

                echo json_encode($res);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Service mapping not found']);
            }
            exit;
            break;

        case 'get_stats_ajax':
            header('Content-Type: application/json');
            $tokens = $db->query("SELECT SUM(tokens_in + tokens_out) FROM logs")->fetchColumn() ?: 0;
            $reqs = $db->query("SELECT COUNT(*) FROM logs")->fetchColumn() ?: 0;
            $appsCount = $db->query("SELECT COUNT(*) FROM apps WHERE status = 'active'")->fetchColumn() ?: 0;
            $hour = $db->query("SELECT COUNT(*) FROM logs WHERE timestamp >= datetime('now', '-1 hour')")->fetchColumn() ?: 0;
            $errors = $db->query("SELECT COUNT(*) FROM logs WHERE status = 'error'")->fetchColumn() ?: 0;
            
            $apps = $db->query("
                SELECT a.id, 
                (SELECT SUM(tokens_in + tokens_out) FROM logs WHERE app_id = a.id) as app_tokens,
                (SELECT COUNT(*) FROM logs WHERE app_id = a.id) as app_requests
                FROM apps a
            ")->fetchAll();
            
            echo json_encode([
                'tokens' => number_format($tokens),
                'requests' => number_format($reqs),
                'apps_active' => $appsCount,
                'hour' => number_format($hour),
                'errors' => number_format($errors),
                'apps_grid' => $apps
            ]);
            exit;
            break;

        case 'get_errors_ajax':
            header('Content-Type: application/json');
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, intval($_GET['limit'] ?? 10));
            $offset = ($page - 1) * $limit;

            try {
                $total = $db->query("SELECT COUNT(*) FROM logs WHERE status = 'error'")->fetchColumn();
                $errors = $db->query("
                    SELECT l.*, a.name as app_name 
                    FROM logs l 
                    LEFT JOIN apps a ON l.app_id = a.id 
                    WHERE l.status = 'error' 
                    ORDER BY l.timestamp DESC 
                    LIMIT $limit OFFSET $offset
                ")->fetchAll();
                
                echo json_encode([
                    'status' => 'success', 
                    'data' => $errors,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => intval($total),
                        'total_pages' => ceil($total / $limit)
                    ]
                ]);
            } catch (\Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            break;

        case 'get_consumption_stats_ajax':
            header('Content-Type: application/json');
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, intval($_GET['limit'] ?? 15));
            $offset = ($page - 1) * $limit;

            $app_id = $_GET['app_id'] ?? '';
            $status = $_GET['status'] ?? '';
            $date_from = $_GET['date_from'] ?? '';
            $date_to = $_GET['date_to'] ?? '';

            $where = ["1=1"];
            $params = [];

            if ($app_id !== '') { $where[] = "l.app_id = ?"; $params[] = $app_id; }
            if ($status !== '') { $where[] = "l.status = ?"; $params[] = $status; }
            if ($date_from !== '') { $where[] = "l.timestamp >= ?"; $params[] = $date_from . ' 00:00:00'; }
            if ($date_to !== '') { $where[] = "l.timestamp <= ?"; $params[] = $date_to . ' 23:59:59'; }

            $whereStr = implode(" AND ", $where);

            try {
                $totals = $db->query("
                    SELECT 
                        SUM(tokens_in + tokens_out) as total_tokens,
                        COUNT(*) as total_requests,
                        AVG(latency) as avg_latency,
                        SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as total_success
                    FROM logs l WHERE $whereStr
                ", $params)->fetch();

                $total_rows = $db->query("SELECT COUNT(*) FROM logs l WHERE $whereStr", $params)->fetchColumn();
                $data = $db->query("
                    SELECT l.*, a.name as app_name 
                    FROM logs l 
                    LEFT JOIN apps a ON l.app_id = a.id 
                    WHERE $whereStr 
                    ORDER BY l.timestamp DESC 
                    LIMIT $limit OFFSET $offset
                ", $params)->fetchAll();

                echo json_encode([
                    'status' => 'success',
                    'totals' => [
                        'tokens' => number_format($totals['total_tokens'] ?: 0),
                        'requests' => number_format($totals['total_requests'] ?: 0),
                        'latency' => round($totals['avg_latency'] ?: 0, 3) . 's',
                        'efficiency' => $totals['total_requests'] > 0 ? round(($totals['total_success'] / $totals['total_requests']) * 100, 1) . '%' : '0%'
                    ],
                    'data' => $data,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => intval($total_rows),
                        'total_pages' => ceil($total_rows / $limit)
                    ]
                ]);
            } catch (\Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
            exit;
            break;
    }
} catch (\Exception $e) {
    die("Error en acción administrativa: " . $e->getMessage());
}
