<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carga manual del core
require_once __DIR__ . '/../src/Core/Medoo.php';
require_once __DIR__ . '/../src/Core/Database.php';

require_once 'auth.php'; 

if (!isLoggedIn()) redirect('login.php');

use App\Core\Database;

try {
    $dbWrapper = Database::getInstance();
    $db = $dbWrapper->getDB();
    
    // Estadísticas Globales
    $totalTokens = $db->query("SELECT SUM(tokens_in + tokens_out) FROM logs")->fetchColumn() ?: 0;
    $totalRequests = $db->query("SELECT COUNT(*) FROM logs")->fetchColumn() ?: 0;
    $activeAppsCount = $db->query("SELECT COUNT(*) FROM apps WHERE status = 'active'")->fetchColumn() ?: 0;
    
    // Nuevas Estadísticas
    $reqHour = $db->query("SELECT COUNT(*) FROM logs WHERE timestamp >= datetime('now', '-1 hour')")->fetchColumn() ?: 0;
    $reqMin = $db->query("SELECT COUNT(*) FROM logs WHERE timestamp >= datetime('now', '-1 minute')")->fetchColumn() ?: 0;
    $errorCount = $db->query("SELECT COUNT(*) FROM logs WHERE status = 'error'")->fetchColumn() ?: 0;
    
    $activeTab = $_GET['tab'] ?? 'apps-tab';
    $showArchived = isset($_GET['show_archived']) && $_GET['show_archived'] == '1';

    // Listado de Apps con sus consumos (Consulta robusta con paginación)
    $appsPage = max(1, intval($_GET['p_page'] ?? 1));
    $appsLimit = 10;
    $appsOffset = ($appsPage - 1) * $appsLimit;
    
    // Filtro de estado
    $whereClause = $showArchived ? "1=1" : "status != 'archived'";
    
    $totalApps = $db->query("SELECT COUNT(*) FROM apps WHERE $whereClause")->fetchColumn();
    $totalPagesApps = ceil($totalApps / $appsLimit);

    $apps = $db->query("
        SELECT a.*, 
        (SELECT SUM(tokens_in + tokens_out) FROM logs WHERE app_id = a.id) as app_tokens,
        (SELECT COUNT(*) FROM logs WHERE app_id = a.id) as app_requests
        FROM apps a 
        WHERE $whereClause
        ORDER BY a.created_at DESC
        LIMIT $appsLimit OFFSET $appsOffset
    ")->fetchAll();
} catch (\Exception $e) {
    die("Error crítico de base de datos en administración: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>kodanHUB | Neural Access</title>
    <link rel="icon" type="image/svg+xml" href="https://kodan.software/kodan-terminal.svg">
    <link rel="stylesheet" href="css/modern-hub.css?v=1.1.5">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="logo-box">
                <svg viewBox="60 0 380 120" xmlns="http://www.w3.org/2000/svg" class="logo-svg">
                    <defs>
                        <filter id="neon-glow" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur in="SourceAlpha" stdDeviation="2" result="blur" />
                            <feFlood flood-color="#00FFC2" flood-opacity="0.8" result="color" />
                            <feComposite in="color" in2="blur" operator="in" result="glow" />
                            <feMerge>
                                <feMergeNode in="glow" />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>
                    </defs>
                    <path d="M 100.75 40 L 70.75 60 L 100.75 80" fill="none" stroke="#00FFC2" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" filter="url(#neon-glow)" />
                    <text x="234.5" y="60" text-anchor="middle" dominant-baseline="middle" class="kodan-text">kodan</text>
                    <path d="M 384.25 30 L 368.25 90" fill="none" stroke="#00FFC2" stroke-width="10" stroke-linecap="round" filter="url(#neon-glow)" />
                    <path d="M 399.25 40 L 429.25 60 L 399.25 80" fill="none" stroke="#00FFC2" stroke-width="10" stroke-linecap="round" stroke-linejoin="round" filter="url(#neon-glow)" />
                </svg>
            </div>

            <nav style="flex: 1;">
                <ul>
                    <li class="nav-item">
                        <a href="javascript:void(0)" class="nav-link active" onclick="showTab('apps-tab', this)" id="nav-apps">
                            <i data-lucide="layers"></i>
                            <span>Aplicaciones</span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="nav-link" onclick="showTab('catalog-tab', this)">
                            <i data-lucide="database"></i>
                            <span>Catálogo IA</span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="nav-link" onclick="showTab('services-tab', this)">
                            <i data-lucide="cpu"></i>
                            <span>Asignación IA</span>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:void(0)" class="nav-link" onclick="showTab('stats-tab', this)" id="nav-stats">
                            <i data-lucide="bar-chart-2"></i>
                            <span>Estadísticas</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div style="margin-top: auto; padding-top: 2rem; border-top: 1px solid var(--glass-border);">
                <a href="javascript:void(0)" class="nav-link" onclick="showModal('configModal')">
                    <i data-lucide="settings"></i>
                    <span>Ajustes</span>
                </a>
                <a href="auth.php?logout=1" class="nav-link" style="color: #ff4d4d;">
                    <i data-lucide="log-out"></i>
                    <span>Desconectarse</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="content">
            <!-- Global Stats Widgets -->
            <div class="stats-grid">
                <div class="stat-card" style="cursor: pointer; border-color: var(--mint-neon);" onclick="document.getElementById('nav-stats').click()">
                    <h3>Tokens Totales</h3>
                    <div class="stat-value" id="stat-tokens"><?php echo number_format($totalTokens); ?></div>
                    <i data-lucide="zap"></i>
                </div>
                <div class="stat-card">
                    <h3>Consultas Totales</h3>
                    <div class="stat-value" id="stat-requests"><?php echo number_format($totalRequests); ?></div>
                    <i data-lucide="bar-chart-3"></i>
                </div>
                <div class="stat-card">
                    <h3>Apps Activas</h3>
                    <div class="stat-value" id="stat-apps"><?php echo $activeAppsCount; ?></div>
                    <i data-lucide="layers"></i>
                </div>
                <div class="stat-card">
                    <h3>Consultas / Hora</h3>
                    <div class="stat-value" id="stat-hour"><?php echo number_format($reqHour); ?></div>
                    <i data-lucide="clock"></i>
                </div>
                <div class="stat-card" style="cursor: pointer;" onclick="openErrorsModal()">
                    <h3>Errores Críticos</h3>
                    <div class="stat-value <?php echo $errorCount > 0 ? 'badge-error' : ''; ?>" id="stat-errors" style="color: <?php echo $errorCount > 0 ? '#ff4d4d' : 'inherit'; ?>"><?php echo number_format($errorCount); ?></div>
                    <i data-lucide="alert-octagon"></i>
                </div>
            </div>

            <!-- TAB SECTIONS -->
            <div id="apps-tab" class="tab-content active">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 class="section-title">GESTIÓN DE <span>APLICACIONES</span></h2>
                    
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <!-- Linear Style Toggle -->
                        <div class="toggle-container" title="Mostrar u ocultar aplicaciones archivadas">
                            <span style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted); margin-right: 10px;">VER ARCHIVADAS</span>
                            <label class="switch">
                                <input type="checkbox" id="toggleArchived" <?php echo $showArchived ? 'checked' : ''; ?> onchange="toggleArchivedView(this.checked)">
                                <span class="slider"></span>
                            </label>
                        </div>

                        <button class="btn-neural" onclick="showModal('addAppModal')">
                            <i data-lucide="plus" style="width:14px; margin-right:5px; vertical-align:middle;"></i> NUEVA APP
                        </button>
                    </div>
                </div>

                <div class="glass-container" style="padding: 0; overflow: hidden;">
                    <table>
                        <thead>
                            <tr>
                                <th>APLICACIÓN</th>
                                <th>TOKEN KODAN</th>
                                <th style="text-align: right;">TOKENS</th>
                                <th style="text-align: right;">PEDIDOS</th>
                                <th style="text-align: center;">ESTADO</th>
                                <th style="text-align: right;">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apps as $app): ?>
                            <tr class="<?php echo $app['status'] === 'archived' ? 'archived-row' : ''; ?>">
                                <td>
                                    <div style="font-weight: 700; color: var(--text-titanium);"><?php echo htmlspecialchars($app['name']); ?></div>
                                    <div style="font-size: 0.65rem; color: var(--text-muted);"><?php echo htmlspecialchars($app['app_id'] ?? 'INSTANCIA MANUAL'); ?></div>
                                </td>
                                <td>
                                    <code style="background: rgba(255,255,255,0.03); padding: 4px 8px; border-radius: 6px; font-size: 0.75rem; border: 1px solid var(--glass-border);">
                                        <?php echo substr($app['token'], 0, 12); ?>...
                                    </code>
                                    <button class="btn-outline" style="padding: 4px 8px; font-size: 0.6rem; margin-left: 5px;" onclick="copyToClipboard('<?php echo $app['token']; ?>')">COPIAR</button>
                                </td>
                                <td style="text-align: right; font-weight: 600;" id="stat-app-tokens-<?php echo $app['id']; ?>"><?php echo number_format($app['app_tokens'] ?: 0); ?></td>
                                <td style="text-align: right; font-weight: 600;" id="stat-app-requests-<?php echo $app['id']; ?>"><?php echo number_format($app['app_requests'] ?: 0); ?></td>
                                <td style="text-align: center;">
                                    <?php if ($app['status'] === 'archived'): ?>
                                        <span class="badge" style="background: rgba(255,255,255,0.05); color: #666; border: 1px dashed #444;">
                                            ARCHIVADA
                                        </span>
                                    <?php else: ?>
                                        <span class="badge <?php echo $app['status'] === 'active' ? 'badge-mint' : 'badge-error'; ?>" onclick="toggleStatus('<?php echo $app['id']; ?>')" style="cursor:pointer;">
                                            <?php echo strtoupper($app['status']); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <button class="action-icon-btn" onclick="showEditAppModal('<?php echo $app['id']; ?>', '<?php echo htmlspecialchars($app['name']); ?>')" title="Editar Nombre">
                                            <i data-lucide="edit-3" style="width:14px;"></i>
                                        </button>
                                        <button class="action-icon-btn" onclick="rotateToken('<?php echo $app['id']; ?>')" title="Rotar Token de Seguridad">
                                            <i data-lucide="refresh-cw" style="width:14px;"></i>
                                        </button>
                                        <button class="action-icon-btn danger" onclick="confirmDelete('<?php echo $app['id']; ?>', '<?php echo htmlspecialchars($app['name']); ?>')" title="Archivar Aplicación">
                                            <i data-lucide="archive" style="width:14px;"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($totalPagesApps > 1): ?>
                    <div class="pagination-container">
                        <div class="pagination-info">Mostrando página <span><?php echo $appsPage; ?></span> de <span><?php echo $totalPagesApps; ?></span></div>
                        <div class="pagination-controls">
                            <button class="page-btn" <?php echo $appsPage <= 1 ? 'disabled' : ''; ?> onclick="window.location.href='?p_page=<?php echo $appsPage-1; ?>'"><i data-lucide="chevron-left" style="width:14px;"></i></button>
                            <?php for($i=1; $i<=$totalPagesApps; $i++): ?>
                                <button class="page-btn <?php echo $i == $appsPage ? 'active' : ''; ?>" onclick="window.location.href='?p_page=<?php echo $i; ?>'"><?php echo $i; ?></button>
                            <?php endfor; ?>
                            <button class="page-btn" <?php echo $appsPage >= $totalPagesApps ? 'disabled' : ''; ?> onclick="window.location.href='?p_page=<?php echo $appsPage+1; ?>'"><i data-lucide="chevron-right" style="width:14px;"></i></button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- SECCIÓN: CATÁLOGO IA -->
            <div id="catalog-tab" class="tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 class="section-title">MODELOS <span>GLOBALES</span></h2>
                    <button class="btn-neural" onclick="showModal('addCatalogModal')">
                        <i data-lucide="plus" style="width:14px; margin-right:5px; vertical-align:middle;"></i> NUEVO MODELO
                    </button>
                </div>
                <div class="glass-container" id="catalog-list-container" style="padding: 0;">
                    <p style="padding: 40px; text-align:center; color: var(--text-muted);">Sincronizando catálogo neural...</p>
                </div>
                <div id="catalog-pagination" class="pagination-container" style="display:none; border-top:none; background:none;"></div>
            </div>

            <!-- SECCIÓN: SERVICIOS -->
            <div id="services-tab" class="tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 class="section-title">ASIGNACIÓN DE <span>IA</span></h2>
                    <button class="btn-neural" onclick="showModal('addServiceModal')">
                        <i data-lucide="link-2" style="width:14px; margin-right:5px; vertical-align:middle;"></i> ASIGNAR MODELO
                    </button>
                </div>
                <div class="glass-container" id="services-list-container" style="padding: 0;">
                    <p style="padding: 40px; text-align:center; color: var(--text-muted);">Cargando mapas de servicio...</p>
                </div>
                <div id="services-pagination" class="pagination-container" style="display:none; border-top:none; background:none;"></div>
            </div>

            <!-- SECCIÓN: ANALÍTICA NEURAL -->
            <div id="stats-tab" class="tab-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 class="section-title">ANALÍTICA <span>NEURAL</span></h2>
                    <div style="display: flex; gap: 10px;">
                        <button class="btn-outline" onclick="loadConsumptionStats(1)" title="Refrescar Analítica">
                            <i data-lucide="refresh-cw" style="width:14px;"></i>
                        </button>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="glass-container" style="margin-bottom: 2rem; padding: 1.5rem;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; align-items: end;">
                        <div class="form-group" style="margin:0;">
                            <label style="font-size: 0.65rem;">Aplicación</label>
                            <select id="filter-app" class="limit-selector" style="width:100%; height:40px;">
                                <option value="">Todas</option>
                                <?php foreach ($apps as $app): ?>
                                    <option value="<?php echo $app['id']; ?>"><?php echo htmlspecialchars($app['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label style="font-size: 0.65rem;">Estado</label>
                            <select id="filter-status" class="limit-selector" style="width:100%; height:40px;">
                                <option value="">Todos</option>
                                <option value="success">Éxitos</option>
                                <option value="error">Errores</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label style="font-size: 0.65rem;">Desde</label>
                            <input type="date" id="filter-from" class="limit-selector" style="width:100%; height:40px;">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label style="font-size: 0.65rem;">Hasta</label>
                            <input type="date" id="filter-to" class="limit-selector" style="width:100%; height:40px;">
                        </div>
                        <button class="btn-neural" style="height:40px;" onclick="loadConsumptionStats(1)">FILTRAR</button>
                    </div>
                </div>

                <!-- Totalizadores Dinámicos -->
                <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom: 2rem;">
                    <div class="stat-card" style="padding: 1.5rem; background: rgba(0,255,194,0.03);">
                        <h3 style="font-size: 0.65rem;">Tokens Filtrados</h3>
                        <div class="stat-value" id="agg-tokens" style="font-size: 1.2rem;">0</div>
                    </div>
                    <div class="stat-card" style="padding: 1.5rem; background: rgba(255,255,255,0.02);">
                        <h3 style="font-size: 0.65rem;">Peticiones Totales</h3>
                        <div class="stat-value" id="agg-requests" style="font-size: 1.2rem;">0</div>
                    </div>
                    <div class="stat-card" style="padding: 1.5rem; background: rgba(255,255,255,0.02);">
                        <h3 style="font-size: 0.65rem;">Latencia Media</h3>
                        <div class="stat-value" id="agg-latency" style="font-size: 1.2rem;">0s</div>
                    </div>
                    <div class="stat-card" style="padding: 1.5rem; background: rgba(0,255,194,0.03);">
                        <h3 style="font-size: 0.65rem;">Eficiencia</h3>
                        <div class="stat-value" id="agg-efficiency" style="font-size: 1.2rem;">0%</div>
                    </div>
                </div>

                <!-- Grilla de Detalle -->
                <div class="glass-container" id="stats-list-container" style="padding: 0;">
                    <p style="padding: 40px; text-align:center; color: var(--text-muted);">Configure filtros para visualizar analítica...</p>
                </div>
                <div id="stats-pagination" class="pagination-container" style="display:none; border-top:none; background:none;"></div>
            </div>
        </main>
    </div>

    <!-- MODALES -->
    <div id="configModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="margin-bottom: 1.5rem; font-weight: 700; color: var(--mint-neon);">Ajustes de Administrador</h3>
            <form action="actions.php" method="POST">
                <input type="hidden" name="action" value="update_password">
                <div class="form-group">
                    <label>Nueva Contraseña de Acceso</label>
                    <input type="password" name="new_password" required placeholder="Ingresar nueva clave">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 2rem;">
                    <button type="submit" class="btn-neural" style="flex: 1;">Actualizar</button>
                    <button type="button" class="btn-outline" style="flex: 1;" onclick="hideModal('configModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addAppModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="margin-bottom: 1.5rem; font-weight: 700; color: var(--mint-neon);">Registrar Aplicación</h3>
            <form action="actions.php" method="POST">
                <input type="hidden" name="action" value="add_app">
                <input type="hidden" name="redirect_tab" value="apps-tab">
                <div class="form-group">
                    <label>Nombre de la Aplicación</label>
                    <input type="text" name="name" placeholder="Ej: SmartCook" required>
                </div>
                <div class="form-group">
                    <label>Token Personalizado (Opcional)</label>
                    <input type="text" name="custom_token" placeholder="SK-HUB-XXXX-KDN">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 2rem;">
                    <button type="submit" class="btn-neural" style="flex: 1;">Guardar</button>
                    <button type="button" class="btn-outline" style="flex: 1;" onclick="hideModal('addAppModal')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editAppModal" class="modal-overlay">
        <div class="modal-content">
            <h2 class="section-title">EDITAR <span>APLICACIÓN</span></h2>
            <form action="actions.php?action=update_app_name" method="POST">
                <input type="hidden" name="id" id="editAppId">
                <input type="hidden" name="redirect_tab" value="apps-tab">
                <div class="form-group">
                    <label>NOMBRE COMERCIAL</label>
                    <input type="text" name="name" id="editAppName" required placeholder="Ej: SmartCook PRO">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 2rem;">
                    <button type="submit" class="btn-neural" style="flex: 1;">GUARDAR CAMBIOS</button>
                    <button type="button" class="btn-outline" style="flex: 1;" onclick="hideModal('editAppModal')">CANCELAR</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addCatalogModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="margin-bottom: 1.5rem; font-weight: 700; color: var(--mint-neon);">Nuevo Modelo Global</h3>
            <form action="actions.php" method="POST">
                <input type="hidden" name="action" value="add_catalog_model">
                <input type="hidden" name="redirect_tab" value="catalog-tab">
                <div class="form-group">
                    <label>Proveedor (ej: NVIDIA, GOOGLE)</label>
                    <input type="text" name="provider" required>
                </div>
                <div class="form-group">
                    <label>Nombre Amigable</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Identificador Técnico</label>
                    <input type="text" name="identifier" required>
                </div>
                <div class="form-group">
                    <label>Protocolo</label>
                    <select name="protocol" style="width: 100%; padding: 1rem; background: #050505; border: 1px solid var(--glass-border); color: white; border-radius: 10px;">
                        <option value="openai-v1">openai-v1</option>
                        <option value="gemini-v1">gemini-v1</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Endpoint URL</label>
                    <input type="text" name="endpoint" required>
                </div>
                <div style="display: flex; gap: 10px; margin-top: 2rem;">
                    <button type="submit" class="btn-neural" style="flex: 1;">Registrar</button>
                    <button type="button" class="btn-outline" style="flex: 1;" onclick="hideModal('addCatalogModal')">Cerrar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="addServiceModal" class="modal-overlay">
        <div class="modal-content">
            <h3 style="margin-bottom: 1.5rem; font-weight: 700; color: var(--mint-neon);">Vincular IA a Aplicación</h3>
            <form action="actions.php" method="POST">
                <input type="hidden" name="action" value="add_app_service">
                <input type="hidden" name="redirect_tab" value="services-tab">
                
                <div class="form-group">
                    <label>Aplicación Destino</label>
                    <select name="app_id" style="width: 100%; padding: 1rem; background: #050505; border: 1px solid var(--glass-border); color: white; border-radius: 10px;">
                        <?php foreach ($apps as $app): ?>
                            <option value="<?php echo $app['id']; ?>"><?php echo htmlspecialchars($app['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Modelo a Asignar</label>
                    <select name="catalog_id" id="catalogSelect" style="width: 100%; padding: 1rem; background: #050505; border: 1px solid var(--glass-border); color: white; border-radius: 10px;">
                        <!-- Dinámico -->
                    </select>
                </div>

                <div class="form-group">
                    <label>API Key Específica</label>
                    <input type="password" name="api_key" placeholder="Pegar clave aquí">
                </div>
                
                <div class="form-group">
                    <label>Prioridad (1 = Principal)</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <button type="button" onclick="this.nextElementSibling.stepDown()" style="position: absolute; left: 10px; background: transparent; border: none; color: var(--mint-neon); cursor: pointer; padding: 5px;"><i data-lucide="chevron-down" style="width:16px;"></i></button>
                        <input type="number" name="priority" value="1" style="width: 100%; padding: 1rem 3rem; background: #050505; border: 1px solid var(--glass-border); color: var(--mint-neon); border-radius: 10px; font-weight: 800; font-size: 1.1rem;">
                        <button type="button" onclick="this.previousElementSibling.stepUp()" style="position: absolute; right: 10px; background: transparent; border: none; color: var(--mint-neon); cursor: pointer; padding: 5px;"><i data-lucide="chevron-up" style="width:16px;"></i></button>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 2rem;">
                    <button type="submit" class="btn-neural" style="flex: 1;">Vincular</button>
                    <button type="button" class="btn-outline" style="flex: 1;" onclick="hideModal('addServiceModal')">Cerrar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="modal-overlay">
        <div class="modal-content" style="text-align: center;">
            <div id="deleteModalIcon" style="color: #ff4d4d; margin-bottom: 1rem;">
                <i data-lucide="trash-2" style="width: 48px; height: 48px;"></i>
            </div>
            <h3 style="margin-bottom: 1rem; font-weight: 700;">¿Confirmar Acción?</h3>
            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 2rem;">La aplicación será archivada. Dejará de funcionar en producción pero se preservarán todos sus registros históricos.</p>
            <div style="display: flex; gap: 10px;">
                <button id="btnConfirmDelete" class="btn-neural" style="flex: 1; background: #ff4d4d;">Confirmar</button>
                <button type="button" class="btn-outline" style="flex: 1;" onclick="hideModal('deleteModal')">Abortar</button>
            </div>
        </div>
    </div>

    <div id="testResultModal" class="modal-overlay">
        <div class="modal-content" style="max-width: 850px; padding: 2rem;">
            <div class="diagnostic-header">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 10px; height: 10px; background: var(--mint-neon); border-radius: 50%; box-shadow: 0 0 10px var(--mint-neon);"></div>
                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 700; letter-spacing: -0.5px;">DIAGNÓSTICO NEURAL V3.2</h3>
                </div>
                <div style="display: flex; gap: 8px;">
                    <div id="test-status-pill" class="diag-pill">-</div>
                    <div id="test-latency-pill" class="diag-pill" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">-</div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="trace-container">
                    <div class="trace-header">
                        <span>TRAZA DE SALIDA (REQUEST)</span>
                        <i data-lucide="upload-cloud" style="width:12px;"></i>
                    </div>
                    <div id="test-request" class="trace-body">-</div>
                </div>
                <div class="trace-container">
                    <div class="trace-header">
                        <span>RESPUESTA CRUDA (API_RAW)</span>
                        <i data-lucide="download-cloud" style="width:12px;"></i>
                    </div>
                    <div id="test-response" class="trace-body">-</div>
                </div>
            </div>

            <div class="terminal-extracted">
                <div id="test-extracted">Esperando flujo de datos...</div>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 0.65rem; color: var(--text-muted); font-family: monospace;">
                    ENDPOINT: <span id="test-endpoint-display" style="color: #666;">-</span>
                </div>
                <button class="btn-neural" style="padding: 10px 30px;" onclick="hideModal('testResultModal')">CERRAR DIAGNÓSTICO</button>
            </div>
        </div>
    </div>

    <div id="toast-container" style="position: fixed; top: 30px; right: 30px; z-index: 10000;"></div>

    <script src="js/neural-ui.js?v=1.0.7"></script>
    <script>
        function showTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.nav-link').forEach(b => b.classList.remove('active'));
            const content = document.getElementById(tabId);
            if (content) content.classList.add('active');
            if (btn) btn.classList.add('active');
            else {
                // Si no hay botón (carga inicial), buscarlo por data-tab o similar
                const navBtn = document.querySelector(`.nav-link[onclick*="'${tabId}'"]`);
                if (navBtn) navBtn.classList.add('active');
            }
            
            // Persistir en inputs ocultos de formularios
            document.querySelectorAll('input[name="redirect_tab"]').forEach(i => i.value = tabId);

            if (tabId === 'catalog-tab') loadCatalog();
            if (tabId === 'services-tab') loadServices();
            if (tabId === 'stats-tab') loadConsumptionStats();
        }

        async function loadConsumptionStats(page = 1) {
            const container = document.getElementById('stats-list-container');
            const appId = document.getElementById('filter-app').value;
            const status = document.getElementById('filter-status').value;
            const from = document.getElementById('filter-from').value;
            const to = document.getElementById('filter-to').value;

            container.innerHTML = '<p style="padding: 40px; text-align:center; color: var(--text-muted);">Procesando analítica...</p>';

            try {
                const url = `actions.php?action=get_consumption_stats_ajax&page=${page}&app_id=${appId}&status=${status}&date_from=${from}&date_to=${to}`;
                const r = await fetch(url);
                const d = await r.json();

                if (d.status === 'success') {
                    // Actualizar Totalizadores
                    document.getElementById('agg-tokens').innerText = d.totals.tokens;
                    document.getElementById('agg-requests').innerText = d.totals.requests;
                    document.getElementById('agg-latency').innerText = d.totals.latency;
                    document.getElementById('agg-efficiency').innerText = d.totals.efficiency;

                    if (d.data.length === 0) {
                        container.innerHTML = '<p style="padding: 40px; text-align:center; color: var(--text-muted);">No hay registros para los filtros aplicados.</p>';
                        document.getElementById('stats-pagination').style.display = 'none';
                        return;
                    }

                    container.innerHTML = `
                        <table>
                            <thead>
                                <tr>
                                    <th>FECHA</th>
                                    <th>APLICACIÓN</th>
                                    <th>MODELO</th>
                                    <th style="text-align:right;">TOKENS</th>
                                    <th style="text-align:right;">LATENCIA</th>
                                    <th style="text-align:center;">ESTADO</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${d.data.map(l => `
                                    <tr>
                                        <td><small>${l.timestamp}</small></td>
                                        <td><strong>${l.app_name || 'Desconocida'}</strong></td>
                                        <td><code style="font-size:0.7rem;">${l.model}</code></td>
                                        <td style="text-align:right;">${(parseInt(l.tokens_in) + parseInt(l.tokens_out)).toLocaleString()}</td>
                                        <td style="text-align:right;">${l.latency}s</td>
                                        <td style="text-align:center;">
                                            <span class="badge ${l.status === 'success' ? 'badge-mint' : 'badge-error'}">${l.status.toUpperCase()}</span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;

                    renderPagination('stats-pagination', d.pagination, loadConsumptionStats);
                    lucide.createIcons();
                }
            } catch (e) {
                container.innerHTML = '<p style="padding: 40px; text-align:center; color: #ff4d4d;">Error al cargar analítica.</p>';
            }
        }

        async function populateCatalogSelect() {
            const select = document.getElementById('catalogSelect');
            if (!select) return;
            try {
                const r = await fetch(`actions.php?action=get_catalog_ajax&page=1&limit=100`);
                const d = await r.json();
                if (d.status === 'success') {
                    select.innerHTML = d.data.map(m => `<option value="${m.id}">${m.provider} - ${m.name}</option>`).join('');
                }
            } catch (e) {}
        }

        async function loadCatalog(page = 1) {
            const container = document.getElementById('catalog-list-container');
            if (!container) return;
            try {
                const r = await fetch(`actions.php?action=get_catalog_ajax&page=${page}&limit=10`);
                const d = await r.json();
                if (d.status === 'success') {
                    container.innerHTML = `
                        <table>
                            <thead><tr><th>PROVEEDOR</th><th>MODELO</th><th>PROTOCOLO</th><th style="text-align:right;">ACCIONES</th></tr></thead>
                            <tbody>
                                ${d.data.map(m => `
                                    <tr>
                                        <td><strong>${m.provider}</strong></td>
                                        <td>${m.name} <br><small style="color:var(--text-muted);">${m.identifier}</small></td>
                                        <td><span class="badge badge-mint">${m.protocol}</span></td>
                                        <td style="padding:12px; text-align:right;">
                                            <div style="display:flex; gap:8px; justify-content:flex-end;">
                                                <button class="action-icon-btn" onclick="openEditCatalog('${m.id}', '${m.provider}', '${m.name}', '${m.identifier}', '${m.protocol}', '${m.endpoint}')" title="Editar Modelo">
                                                    <i data-lucide="edit-3" style="width:14px;"></i>
                                                </button>
                                                <button class="action-icon-btn danger" onclick="deleteCatalog('${m.id}')" title="Eliminar del Catálogo">
                                                    <i data-lucide="trash-2" style="width:14px;"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                    
                    d.pagination.data_count = d.data.length;
                    renderPagination('catalog-pagination', d.pagination, loadCatalog);
                    lucide.createIcons();
                }
            } catch (e) {}
        }

        async function loadServices(page = 1) {
            const container = document.getElementById('services-list-container');
            if (!container) return;
            try {
                const r = await fetch(`actions.php?action=get_services_ajax&page=${page}&limit=10`);
                const d = await r.json();
                if (d.status === 'success') {
                    container.innerHTML = `
                        <table>
                            <thead><tr><th>APLICACIÓN</th><th>MODELO ASIGNADO</th><th>PRIORIDAD</th><th style="text-align:center;">ESTADO</th><th style="text-align:right;">ACCIONES</th></tr></thead>
                            <tbody>
                                ${d.data.map(s => `
                                    <tr>
                                        <td><strong>${s.app_name}</strong></td>
                                        <td>${s.model_name} <br><small style="color:var(--text-muted);">${s.provider}</small></td>
                                        <td style="text-align:center;">${s.priority}</td>
                                        <td style="text-align:center;">
                                            <span class="badge ${s.is_active ? 'badge-mint' : 'badge-error'}">${s.is_active ? 'ACTIVO' : 'PAUSADO'}</span>
                                        </td>
                                        <td style="padding:12px; text-align:right;">
                                            <div style="display:flex; gap:8px; justify-content:flex-end;">
                                                <button class="action-icon-btn" onclick="testService('${s.id}')" title="Probar Conexión Neural">
                                                    <i data-lucide="zap" style="width:14px;"></i>
                                                </button>
                                                <button class="action-icon-btn" onclick="openEditService('${s.id}', '${s.catalog_id}', '${s.api_key}', '${s.priority}')" title="Editar Asignación">
                                                    <i data-lucide="edit-3" style="width:14px;"></i>
                                                </button>
                                                <button class="action-icon-btn danger" onclick="deleteService('${s.id}')" title="Desvincular Servicio">
                                                    <i data-lucide="link-2-off" style="width:14px;"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    `;
                    
                    d.pagination.data_count = d.data.length;
                    renderPagination('services-pagination', d.pagination, loadServices);
                    lucide.createIcons();
                }
            } catch (e) {}
        }

        async function testService(id) {
            const loadingToast = showToast('Estableciendo conexión con la red neural...', 'info', 0);
            try {
                const r = await fetch('actions.php?action=test_service_ajax&id=' + id);
                const d = await r.json();
                closeToast(loadingToast);
                
                document.getElementById('test-status-pill').innerText = d.status.toUpperCase();
                document.getElementById('test-status-pill').className = 'diag-pill ' + (d.status === 'success' ? 'success' : 'error');
                document.getElementById('test-latency-pill').innerText = d.latency ? d.latency + ' LATENCY' : '-';
                document.getElementById('test-endpoint-display').innerText = d.debug_endpoint || '-';
                document.getElementById('test-request').innerText = JSON.stringify(d.debug_request, null, 2);
                document.getElementById('test-response').innerText = JSON.stringify(d.debug_response, null, 2);
                document.getElementById('test-extracted').innerText = d.response || '(Vacío)';
                
                showModal('testResultModal');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } catch (e) {
                closeToast(loadingToast);
                showToast('Fallo crítico en la infraestructura de red', 'error');
            }
        }

        function openEditCatalog(id, provider, name, identifier, protocol, endpoint) {
            document.querySelector('#addCatalogModal [name="action"]').value = 'edit_catalog_model';
            const modal = document.getElementById('addCatalogModal');
            modal.querySelector('h3').innerText = 'EDITAR MODELO GLOBAL';
            modal.querySelector('[name="provider"]').value = provider;
            modal.querySelector('[name="name"]').value = name;
            modal.querySelector('[name="identifier"]').value = identifier;
            modal.querySelector('[name="protocol"]').value = protocol;
            modal.querySelector('[name="endpoint"]').value = endpoint;
            let idInput = modal.querySelector('[name="id"]');
            if (!idInput) { idInput = document.createElement('input'); idInput.type = 'hidden'; idInput.name = 'id'; modal.querySelector('form').appendChild(idInput); }
            idInput.value = id;
            showModal('addCatalogModal');
        }

        async function openEditService(id, catalogId, apiKey, priority) {
            // Asegurar que el catálogo esté cargado antes de seleccionar
            await populateCatalogSelect();
            
            document.querySelector('#addServiceModal [name="action"]').value = 'edit_app_service';
            const modal = document.getElementById('addServiceModal');
            modal.querySelector('h3').innerText = 'EDITAR ASIGNACIÓN DE IA';
            modal.querySelector('[name="catalog_id"]').value = catalogId;
            modal.querySelector('[name="api_key"]').value = apiKey;
            modal.querySelector('[name="priority"]').value = priority;
            let idInput = modal.querySelector('[name="id"]');
            if (!idInput) { idInput = document.createElement('input'); idInput.type = 'hidden'; idInput.name = 'id'; modal.querySelector('form').appendChild(idInput); }
            idInput.value = id;
            showModal('addServiceModal');
        }

        async function refreshStats() {
            try {
                const r = await fetch('actions.php?action=get_stats_ajax');
                const d = await r.json();
                const updateEl = (id, val) => {
                    const el = document.getElementById(id);
                    if (el && el.innerText != val) {
                        gsap.to(el, { opacity: 0, duration: 0.2, onComplete: () => { el.innerText = val; gsap.to(el, { opacity: 1, duration: 0.2 }); }});
                    }
                };
                updateEl('stat-tokens', d.tokens);
                updateEl('stat-requests', d.requests);
                updateEl('stat-apps', d.apps_active);
                updateEl('stat-hour', d.hour);
                updateEl('stat-errors', d.errors);

                // Actualizar Grilla de Apps
                if (d.apps_grid) {
                    d.apps_grid.forEach(app => {
                        updateEl('stat-app-tokens-' + app.id, Number(app.app_tokens || 0).toLocaleString());
                        updateEl('stat-app-requests-' + app.id, Number(app.app_requests || 0).toLocaleString());
                    });
                }
            } catch (e) {}
        }

        async function openErrorsModal(page = 1) {
            const body = document.getElementById('errorsTableBody');
            body.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 40px; color:var(--text-muted);">Recuperando fallos...</td></tr>';
            showModal('errorsModal');
            try {
                const r = await fetch(`actions.php?action=get_errors_ajax&page=${page}&limit=10`);
                const d = await r.json();
                if (d.status === 'success') {
                    if (d.data.length === 0) { 
                        body.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 40px;">No se registran anomalías.</td></tr>'; 
                        document.getElementById('errors-pagination').style.display = 'none';
                        return; 
                    }
                    body.innerHTML = d.data.map(log => `<tr><td>${log.timestamp}</td><td><strong>${log.app_name}</strong></td><td>${log.model}</td><td>${log.latency}s</td><td style="color:#ff4d4d; font-weight:700;">FAILURE</td></tr>`).join('');
                    
                    d.pagination.data_count = d.data.length;
                    renderPagination('errors-pagination', d.pagination, openErrorsModal);
                }
            } catch (e) {}
        }

        function platformConfirm(options) {
            const modal = document.getElementById('deleteModal');
            modal.querySelector('h3').innerText = options.title || '¿Confirmar Operación?';
            modal.querySelector('p').innerHTML = options.message || '¿Deseas continuar con esta acción?';
            
            const iconContainer = document.getElementById('deleteModalIcon');
            iconContainer.innerHTML = `<i data-lucide="${options.icon || 'trash-2'}" style="width: 48px; height: 48px;"></i>`;
            iconContainer.style.color = options.isDanger ? '#ff4d4d' : 'var(--mint-neon)';

            const btn = document.getElementById('btnConfirmDelete');
            btn.innerText = options.confirmText || 'Confirmar';
            btn.style.background = options.isDanger ? '#ff4d4d' : 'var(--mint-neon)';
            
            btn.onclick = function() {
                window.location.href = options.url;
            };
            
            showModal('deleteModal');
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function deleteCatalog(id) { 
            platformConfirm({
                title: '¿Purgar Modelo?',
                message: 'Esta operación eliminará el modelo del catálogo global.',
                confirmText: 'Eliminar',
                isDanger: true,
                url: 'actions.php?action=delete_catalog_model&id=' + id + '&redirect_tab=catalog-tab'
            });
        }

        function deleteService(id) { 
            platformConfirm({
                title: '¿Desvincular Servicio?',
                message: 'Se eliminará la conexión entre la aplicación y este modelo de IA.',
                confirmText: 'Desvincular',
                icon: 'link-2-off',
                isDanger: true,
                url: 'actions.php?action=delete_service&id=' + id + '&redirect_tab=services-tab'
            });
        }

        function rotateToken(id) { 
            platformConfirm({
                title: '¿Rotar Token?',
                message: 'Se generará un nuevo token de seguridad. La aplicación deberá actualizar sus credenciales inmediatamente.',
                confirmText: 'Rotar Token',
                icon: 'refresh-cw',
                isDanger: false,
                url: 'actions.php?action=rotate_token&id=' + id + '&redirect_tab=apps-tab'
            });
        }

        function toggleStatus(id) { window.location.href = 'actions.php?action=toggle_status&id=' + id + '&redirect_tab=apps-tab'; }
        
        function showEditAppModal(id, name) {
            document.getElementById('editAppId').value = id;
            document.getElementById('editAppName').value = name;
            showModal('editAppModal');
        }

        function confirmDelete(id, name) {             platformConfirm({
                title: '¿Archivar Aplicación?',
                message: `¿Estás seguro de archivar <strong>${name}</strong>? Dejará de responder Handshakes pero mantendrás las estadísticas.`,
                confirmText: 'Archivar App',
                icon: 'archive',
                isDanger: true,
                url: 'actions.php?action=delete_app&id=' + id + '&redirect_tab=apps-tab'
            });
        }

        function toggleArchivedView(show) {
            const url = new URL(window.location.href);
            if (show) url.searchParams.set('show_archived', '1');
            else url.searchParams.delete('show_archived');
            window.location.href = url.toString();
        }
        window.onload = function() { 
            refreshStats(); 
            populateCatalogSelect(); // Cargar combo de IAs al inicio
            setInterval(refreshStats, 30000); 
            lucide.createIcons(); 
            
            // Activar pestaña desde URL si existe
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'apps-tab';
            showTab(tab);

            // Manejo de errores desde URL
            if (urlParams.get('error') === 'app_has_logs') {
                showToast('OPERACIÓN BLOQUEADA: La aplicación tiene registros de logs activos.', 'error', 6000);
            }
        };
    </script>
</body>
</html>
