<?php
/**
 * SuperAdminController — Panel exclusivo del SuperAdministrador
 *
 * SEGURIDAD: Todos los métodos verifican rol_id === 0 antes de operar.
 * Cualquier acceso no autorizado retorna HTTP 403.
 */
class SuperAdminController extends Controller
{
    /**
     * Marca los permisos de un usuario como desactualizados en sesión.
     * En el próximo request de ese usuario, index.php los recargará desde BD.
     */
    private function invalidarCachePermisos(int $userId): void
    {
        $flagFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sgp_perm_' . $userId . '.flag';
        @file_put_contents($flagFile, (string)time());
    }

    /**
     * Verificar que el usuario sea SuperAdmin.
     * Centraliza la guarda de seguridad para todos los métodos.
     */
    private function guardSuperAdmin(): void
    {
        Session::start();
        $roleId = (int)Session::get('role_id');
        $userId = Session::get('user_id');

        if (!$userId) {
            $this->redirect('/auth/login');
            exit;
        }

        if ($roleId !== 0) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo SuperAdmin.']);
                exit;
            }
            http_response_code(403);
            require APPROOT . '/views/errors/403.php';
            exit;
        }
    }

    // =========================================================
    // VISTAS
    // =========================================================

    /**
     * Dashboard principal del SuperAdmin.
     */
    public function index(): void
    {
        $this->guardSuperAdmin();

        $userModel  = $this->model('User');
        $auditModel = $this->model('Audit');

        $kpisUsers = $userModel->getUsersKPIs();
        $ultimosLogs = $auditModel->getAll(50); // Incrementado para paginación
        $kpisAudit   = $auditModel->getKPIs();

        // Actividad últimos 7 días (para gráfica de líneas)
        $db = $auditModel->getDb();
        $db->query("
            SELECT DATE(created_at) AS dia, COUNT(*) AS total
            FROM bitacora
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY dia ASC
        ");
        $actividadSemana = $db->resultSet() ?: [];

        // Actividad últimos 30 días (para toggle en gráfica)
        $db->query("
            SELECT DATE(created_at) AS dia, COUNT(*) AS total
            FROM bitacora
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY dia ASC
        ");
        $actividadMes = $db->resultSet() ?: [];

        // Traducción de acciones crudas para gráfica Donut
        $diccionarioAcciones = [
            'LOGIN' => 'Inicio de Sesión',
            'LOGOUT' => 'Cierre de Sesión',
            'RESET_PASSWORD' => 'Reset Password',
            'RESET_PIN' => 'Reset PIN',
            'CREATE_USER' => 'Usuario Creado',
            'UPDATE_USER' => 'Usuario Editado',
            'DELETE_USER_PERMANENT' => 'Usuario Eliminado',
            'TOGGLE_USER_STATUS' => 'Cambio de Estado',
            'UPDATE_PROFILE' => 'Perfil Editado',
            'SESSION_MAINTENANCE' => 'Mant. Sesiones',
            'MAINTENANCE' => 'Mantenimiento',
            'CREATE_PASANTE' => 'Pasante Registrado',
            'UPDATE_PASANTE' => 'Pasante Modificado',
            'DELETE_PASANTE' => 'Pasante Eliminado',
            'CREATE_EVALUACION' => 'Evaluación Creada',
            'UPDATE_EVALUACION' => 'Evaluación Editada',
            'DELETE_EVALUACION' => 'Evaluación Eliminada',
            'EXPORT_CSV' => 'Exportación CSV',
            'UPDATE_CONFIG' => 'Configuración Editada',
            'PERMISO_MODIFICADO' => 'Permiso Cambiado',
            'PERMISOS_RESET' => 'Permisos Restablecidos',
            'MARCAR_ASISTENCIA_KIOSCO' => 'Asistencia Kiosco',
            'MARCAR_ASISTENCIA' => 'Asistencia Marcada',
            'UPDATE_ASISTENCIA' => 'Asistencia Modificada',
            'SYNC_FERIADOS_API' => 'Sincronización Feriados',
            'CAMBIO_ESTADO_PASANTE' => 'Cambio Estado Pasante',
        ];

        // Distribución de acciones top-6 (para gráfica donut)
        $db->query("
            SELECT accion, COUNT(*) AS total
            FROM bitacora
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY accion
            ORDER BY total DESC
            LIMIT 6
        ");
        $distribucionRaw = $db->resultSet() ?: [];
        $distribucionAcciones = [];
        foreach ($distribucionRaw as $row) {
            $accionUpper = strtoupper($row->accion);
            $nombreTraducido = $diccionarioAcciones[$accionUpper] ?? $row->accion;
            $distribucionAcciones[] = (object)[
                'accion' => $nombreTraducido,
                'total'  => $row->total
            ];
        }

        // Actividad por admin/tutor (quién hace qué — vista de supervisión)
        $db->query("
            SELECT
                CONCAT(COALESCE(dp.nombres,''),' ',COALESCE(dp.apellidos,'')) AS nombre,
                u.correo, r.nombre AS rol,
                COUNT(*) AS total_acciones,
                MAX(b.created_at) AS ultima_accion
            FROM bitacora b
            LEFT JOIN usuarios u ON b.usuario_id = u.id
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            LEFT JOIN roles r ON u.rol_id = r.id
            WHERE u.rol_id IN (1,2)
              AND b.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY b.usuario_id
            ORDER BY total_acciones DESC
            LIMIT 50
        ");
        // ── 1. Usuarios en Línea y Sesiones Activas (Tiempo Real) ──
        $db->query("
            SELECT u.id, u.correo, u.rol_id, r.nombre AS rol_nombre, u.ultima_actividad, u.ultima_ip, u.ultimo_user_agent,
                   CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS nombre_completo,
                   d.nombre AS departamento_nombre,
                   TIMESTAMPDIFF(MINUTE, u.ultima_actividad, NOW()) AS minutos_inactivo
            FROM usuarios u
            LEFT JOIN roles r ON r.id = u.rol_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN departamentos d ON d.id = u.departamento_id
            WHERE u.ultima_actividad >= NOW() - INTERVAL 15 MINUTE AND u.estado = 'activo'
            ORDER BY u.ultima_actividad DESC
        ");
        $usuariosEnLinea = $db->resultSet() ?: [];
        $totalEnLinea = 0;
        $totalAusentes = 0;
        foreach ($usuariosEnLinea as $uOnline) {
            if ($uOnline->minutos_inactivo <= 5) $totalEnLinea++;
            else $totalAusentes++;
        }

        // ── 2. Tickets de Soporte Técnico (HelpDesk) ──
        $db->query("
            SELECT t.*,
                   TIMESTAMPDIFF(HOUR, t.created_at, NOW()) AS horas_transcurridas
            FROM soporte_tickets t
            ORDER BY CASE t.estado WHEN 'Abierto' THEN 1 WHEN 'En Proceso' THEN 2 ELSE 3 END,
                     t.created_at DESC
            LIMIT 15
        ");
        $ticketsSoporte = $db->resultSet() ?: [];

        $db->query("
            SELECT 
                COUNT(*) AS total_tickets,
                SUM(CASE WHEN estado = 'Abierto' THEN 1 ELSE 0 END) AS abiertos,
                SUM(CASE WHEN estado = 'En Proceso' THEN 1 ELSE 0 END) AS en_proceso,
                SUM(CASE WHEN estado = 'Resuelto' THEN 1 ELSE 0 END) AS resueltos
            FROM soporte_tickets
        ");
        $kpisTickets = $db->single() ?: (object)['total_tickets' => 0, 'abiertos' => 0, 'en_proceso' => 0, 'resueltos' => 0];

        // ── 3. Diagnóstico y Salud del Servidor ──
        $db->query("SELECT valor FROM configuracion WHERE clave = 'modo_mantenimiento'");
        $maintRow = $db->single();
        $modoMantenimiento = $maintRow ? ($maintRow->valor === '1') : false;

        $db->query("SELECT valor FROM configuracion WHERE clave = 'modo_mantenimiento_mensaje'");
        $maintMsgRow = $db->single();
        $maintMensaje = $maintMsgRow ? $maintMsgRow->valor : '';

        $db->query("SELECT VERSION() AS mysql_ver");
        $mysqlVerRow = $db->single();
        $mysqlVersion = $mysqlVerRow ? $mysqlVerRow->mysql_ver : 'Desconocida';

        $backupsDir = APPROOT . '/../storage/backups';
        $totalBackups = 0;
        if (is_dir($backupsDir)) {
            $backupFiles = glob($backupsDir . '/*.{sql,zip,gz}', GLOB_BRACE);
            $totalBackups = is_array($backupFiles) ? count($backupFiles) : 0;
        }

        $serverInfo = [
            'php_version'       => PHP_VERSION,
            'mysql_version'     => $mysqlVersion,
            'zip_enabled'       => class_exists('ZipArchive'),
            'pdo_enabled'       => extension_loaded('pdo_mysql'),
            'gd_enabled'        => extension_loaded('gd'),
            'openssl_enabled'   => extension_loaded('openssl'),
            'curl_enabled'      => extension_loaded('curl'),
            'mantenimiento'     => $modoMantenimiento,
            'mantenimiento_msg' => $maintMensaje,
            'total_backups'     => $totalBackups,
            'disk_free_gb'      => function_exists('disk_free_space') ? round(disk_free_space(APPROOT) / (1024 * 1024 * 1024), 2) : 'N/A'
        ];

        $data = [
            'total_activos'        => $kpisUsers['activos'] ?? 0,
            'stats_roles'          => [
                'Administrador' => $kpisUsers['admins']   ?? 0,
                'Tutor'         => $kpisUsers['tutores']  ?? 0,
                'Pasante'       => $kpisUsers['pasantes'] ?? 0,
            ],
            'ultimos_logs'         => $ultimosLogs,
            'kpis_audit'           => $kpisAudit,
            'actividad_semana'     => $actividadSemana,
            'actividad_mes'        => $actividadMes,
            'distribucion_acciones'=> $distribucionAcciones,
            'actividad_por_usuario'=> $actividadPorUsuario,
            'usuarios_en_linea'    => $usuariosEnLinea,
            'total_en_linea'       => $totalEnLinea,
            'total_ausentes'       => $totalAusentes,
            'tickets_soporte'      => $ticketsSoporte,
            'kpis_tickets'         => $kpisTickets,
            'server_info'          => $serverInfo,
        ];

        $this->view('superadmin/index', $data);
    }

    /**
     * Vista de gestión de permisos (Data-Driven UI).
     * GET: Renderiza la tabla usuario × módulo con switches.
     */
    public function permisos(): void
    {
        $this->guardSuperAdmin();

        $permisosModel = $this->model('Permisos');
        $kpis          = $permisosModel->getKpis();
        $modulos       = $permisosModel->getModulosConAcciones();

        // Usuarios gestionables (Admin y Tutor, excluir SuperAdmin)
        $userModel = $this->model('User');
        $usuarios  = $userModel->getUsersByRoles([1, 2]);

        $this->view('superadmin/permisos', [
            'kpis'     => $kpis,
            'modulos'  => $modulos,
            'usuarios' => $usuarios,
        ]);
    }

    // =========================================================
    // API AJAX
    // =========================================================

    /**
     * API: Obtiene los permisos de un usuario específico.
     * GET /superadmin/getPermisosUsuario?usuario_id=X
     */
    public function getPermisosUsuario(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        $userId = (int)($_GET['usuario_id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
            exit;
        }

        $userModel     = $this->model('User');
        $user          = $userModel->findById($userId);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            exit;
        }

        $permisosModel = $this->model('Permisos');
        $permisos      = $permisosModel->getPermisosDetalleUsuario($userId, (int)$user['role_id']);
        $modulos       = $permisosModel->getModulosConAcciones((int)$user['role_id']);

        echo json_encode([
            'success'  => true,
            'usuario'  => [
                'id'     => $user['id'],
                'correo' => $user['correo'],
                'rol_id' => $user['role_id'],
            ],
            'permisos' => $permisos,
            'modulos'  => $modulos,
        ]);
        exit;
    }

    /**
     * API: Guarda el cambio de un switch individual.
     * POST /superadmin/savePermiso
     * Body JSON: { usuario_id, clave, habilitado }
     */
    public function savePermiso(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);

        $userId     = (int)($input['usuario_id'] ?? 0);
        $clave      = trim($input['clave']       ?? '');
        $habilitado = (bool)($input['habilitado'] ?? false);
        $otorgadoPor = (int)Session::get('user_id');

        if ($userId <= 0 || empty($clave)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $permisosModel = $this->model('Permisos');
        $userModel     = $this->model('User');
        $targetUser    = $userModel->findById($userId);
        $nombreUsuario = $targetUser ? trim(($targetUser['nombres'] ?? '') . ' ' . ($targetUser['apellidos'] ?? '')) : '';
        if (empty($nombreUsuario)) {
            $nombreUsuario = $targetUser['correo'] ?? "Usuario #{$userId}";
        }
        $rolNombre = $targetUser['rol_nombre'] ?? 'Usuario';

        $ok = $permisosModel->savePermiso($userId, $clave, $habilitado, $otorgadoPor);

        if ($ok) {
            $accionRow    = $permisosModel->getAccionPorClave($clave);
            $nombreAccion = $accionRow ? $accionRow->nombre : $clave;
            $moduloNombre = $accionRow ? $accionRow->modulo_nombre : 'Sistema';
            $estadoNuevo  = $habilitado ? 'Habilitado' : 'Deshabilitado';

            AuditModel::log('PERMISO_MODIFICADO', 'usuario_permisos', $userId, [
                'clave'                   => $clave,
                'permiso_nombre'          => $nombreAccion,
                'modulo'                  => $moduloNombre,
                'habilitado'              => $habilitado ? 'HABILITADO' : 'DESHABILITADO',
                'usuario_id'              => $userId,
                'usuario_afectado'        => $nombreUsuario,
                'usuario_afectado_correo' => $targetUser['correo'] ?? '',
                'usuario_afectado_rol'    => $rolNombre,
                'descripcion'             => "SuperAdministrador {$estadoNuevo} el permiso '{$nombreAccion}' ({$clave}) en módulo '{$moduloNombre}' para {$nombreUsuario} ({$rolNombre})"
            ]);
            $this->invalidarCachePermisos($userId);
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Permiso actualizado correctamente' : 'Error al actualizar permiso',
        ]);
        exit;
    }

    /**
     * API: Guarda múltiples permisos de un usuario de una sola vez.
     * POST /superadmin/savePermisos
     * Body JSON: { usuario_id, permisos: { clave: bool, ... } }
     */
    public function savePermisos(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        $input       = json_decode(file_get_contents('php://input'), true);
        $userId      = (int)($input['usuario_id'] ?? 0);
        $permisos    = $input['permisos'] ?? [];
        $otorgadoPor = (int)Session::get('user_id');

        if ($userId <= 0 || empty($permisos)) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
            exit;
        }

        $permisosModel = $this->model('Permisos');
        $userModel     = $this->model('User');
        $targetUser    = $userModel->findById($userId);
        $nombreUsuario = $targetUser ? trim(($targetUser['nombres'] ?? '') . ' ' . ($targetUser['apellidos'] ?? '')) : '';
        if (empty($nombreUsuario)) {
            $nombreUsuario = $targetUser['correo'] ?? "Usuario #{$userId}";
        }
        $rolNombre = $targetUser['rol_nombre'] ?? 'Usuario';

        $ok = $permisosModel->savePermisosLote($userId, $permisos, $otorgadoPor);

        if ($ok) {
            AuditModel::log('PERMISO_MODIFICADO', 'usuario_permisos', $userId, [
                'usuario_id'              => $userId,
                'usuario_afectado'        => $nombreUsuario,
                'usuario_afectado_correo' => $targetUser['correo'] ?? '',
                'usuario_afectado_rol'    => $rolNombre,
                'total_permisos'          => count($permisos),
                'descripcion'             => "SuperAdministrador actualizó permisos en lote para {$nombreUsuario} ({$rolNombre})"
            ]);
            $this->invalidarCachePermisos($userId);
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Permisos guardados correctamente' : 'Error al guardar permisos',
        ]);
        exit;
    }

    /**
     * API: Resetea todos los overrides de un usuario (vuelve a defaults del rol).
     * POST /superadmin/resetUsuario
     * Body JSON: { usuario_id }
     */
    public function resetUsuario(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        $input  = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($input['usuario_id'] ?? 0);

        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }

        $permisosModel = $this->model('Permisos');
        $userModel     = $this->model('User');
        $targetUser    = $userModel->findById($userId);
        $nombreUsuario = $targetUser ? trim(($targetUser['nombres'] ?? '') . ' ' . ($targetUser['apellidos'] ?? '')) : '';
        if (empty($nombreUsuario)) {
            $nombreUsuario = $targetUser['correo'] ?? "Usuario #{$userId}";
        }
        $rolNombre = $targetUser['rol_nombre'] ?? 'Usuario';

        $ok = $permisosModel->resetPermisos($userId);

        if ($ok) {
            AuditModel::log('PERMISOS_RESET', 'usuario_permisos', $userId, [
                'usuario_id'              => $userId,
                'usuario_afectado'        => $nombreUsuario,
                'usuario_afectado_correo' => $targetUser['correo'] ?? '',
                'usuario_afectado_rol'    => $rolNombre,
                'descripcion'             => "SuperAdministrador restableció todos los permisos por defecto del rol para {$nombreUsuario} ({$rolNombre})"
            ]);
            $this->invalidarCachePermisos($userId);
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Permisos restablecidos al rol por defecto' : 'Error al restablecer',
        ]);
        exit;
    }

    /**
     * API: Retorna usuarios de un rol específico con sus permisos cargados.
     * GET /superadmin/getUsersByRol?rol_id=1
     */
    public function getUsersByRol(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        $rolId = (int)($_GET['rol_id'] ?? 0);
        if (!in_array($rolId, [1, 2, 3], true)) {
            echo json_encode(['success' => false, 'message' => 'Rol inválido']);
            exit;
        }

        $userModel     = $this->model('User');
        $permisosModel = $this->model('Permisos');
        $modulos       = $permisosModel->getModulosConAcciones($rolId);
        $usuarios      = $userModel->getUsersByRoles([$rolId]);

        // Cargar permisos de cada usuario
        foreach ($usuarios as &$u) {
            $u['permisos'] = $permisosModel->getPermisosDetalleUsuario((int)$u['id'], $rolId);
        }
        unset($u);

        echo json_encode([
            'success'  => true,
            'usuarios' => $usuarios,
            'modulos'  => $modulos,
        ]);
        exit;
    }

    /**
     * API: Retorna todos los eventos de la bitácora registrados hoy.
     * GET /superadmin/eventosHoy
     */
    public function eventosHoy(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        $auditModel = $this->model('Audit');
        $db = $auditModel->getDb();

        $db->query("
            SELECT
                b.accion,
                b.tabla_afectada,
                b.registro_id,
                b.ip_address,
                b.detalles,
                b.created_at,
                TRIM(CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,''))) AS usuario_nombre,
                u.correo
            FROM bitacora b
            LEFT JOIN usuarios u ON b.usuario_id = u.id
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            WHERE DATE(b.created_at) = CURDATE()
            ORDER BY b.created_at DESC
            LIMIT 200
        ");
        $eventos = $db->resultSet() ?: [];

        echo json_encode([
            'success' => true,
            'total'   => count($eventos),
            'fecha'   => date('d/m/Y'),
            'eventos' => $eventos,
        ]);
        exit;
    }

    /**
     * GET /superadmin/accionesUsuario?uid=X  (AJAX → JSON)
     * Devuelve las últimas 50 acciones de un usuario en los últimos 30 días.
     */
    public function accionesUsuario(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        $uid = (int)($_GET['uid'] ?? 0);
        if (!$uid) { echo json_encode(['success' => false, 'message' => 'ID inválido.']); exit; }

        $auditModel = $this->model('Audit');
        $db = $auditModel->getDb();
        $db->query("
            SELECT accion, detalles, ip_address, created_at
            FROM bitacora
            WHERE usuario_id = :uid
              AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $db->bind(':uid', $uid);
        $acciones = $db->resultSet() ?: [];

        echo json_encode(['success' => true, 'acciones' => $acciones]);
        exit;
    }

    // =========================================================
    // GESTOR CENTRALIZADO DE PLANTILLAS DE REPORTES (SUPERADMIN)
    // =========================================================

    /**
     * Vista principal del Diseñador y Gestor de Plantillas PDF (WYSIWYG Word-Canvas).
     * GET /superadmin/plantillas
     */
    public function plantillas(): void
    {
        $this->guardSuperAdmin();

        $plantillasModel = $this->model('PlantillaReporte');
        $plantillas = $plantillasModel->getAll();

        $categorias = [];
        foreach ($plantillas as $p) {
            $cat = $p['categoria'] ?? 'General';
            $categorias[$cat] = ($categorias[$cat] ?? 0) + 1;
        }

        $this->view('superadmin/plantillas', [
            'plantillas' => $plantillas,
            'categorias' => $categorias,
            'total'      => count($plantillas)
        ]);
    }

    /**
     * API: Obtiene una plantilla específica por código.
     * GET /superadmin/getPlantillaAjax?codigo=X
     */
    public function getPlantillaAjax(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        $codigo = trim($_GET['codigo'] ?? '');
        if (empty($codigo)) {
            echo json_encode(['success' => false, 'message' => 'Código de plantilla requerido.']);
            exit;
        }

        $plantillasModel = $this->model('PlantillaReporte');
        $plantilla = $plantillasModel->getByCodigo($codigo);

        if (!$plantilla) {
            echo json_encode(['success' => false, 'message' => 'Plantilla no encontrada.']);
            exit;
        }

        echo json_encode([
            'success'   => true,
            'plantilla' => $plantilla
        ]);
        exit;
    }

    /**
     * API: Guarda cambios en una plantilla.
     * POST /superadmin/guardarPlantillaAjax
     */
    public function guardarPlantillaAjax(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $codigo = trim($input['codigo'] ?? '');

        if (empty($codigo) || empty($input['cuerpo_html'])) {
            echo json_encode(['success' => false, 'message' => 'Datos incompletos para guardar la plantilla.']);
            exit;
        }

        $plantillasModel = $this->model('PlantillaReporte');
        $userId = (int)Session::get('user_id');

        $ok = $plantillasModel->guardar($codigo, $input, $userId);

        if ($ok) {
            AuditModel::log('PLANTILLA_REPORTE_MODIFICADA', 'plantillas_reportes', null, [
                'codigo'           => $codigo,
                'titulo_documento' => $input['titulo_documento'] ?? '',
                'superadmin_id'    => $userId,
                'descripcion'      => "SuperAdministrador guardó cambios en la plantilla maestra '{$codigo}'"
            ]);
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Plantilla guardada correctamente en el sistema' : 'Error al guardar plantilla en la base de datos'
        ]);
        exit;
    }

    /**
     * API: Restablece una plantilla a los valores de fábrica predeterminados.
     * POST /superadmin/resetPlantillaAjax
     */
    public function resetPlantillaAjax(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $codigo = trim($input['codigo'] ?? '');

        if (empty($codigo)) {
            echo json_encode(['success' => false, 'message' => 'Código no proporcionado.']);
            exit;
        }

        $plantillasModel = $this->model('PlantillaReporte');
        $userId = (int)Session::get('user_id');

        $ok = $plantillasModel->restablecer($codigo, $userId);

        if ($ok) {
            AuditModel::log('PLANTILLA_REPORTE_RESTABLECIDA', 'plantillas_reportes', null, [
                'codigo'        => $codigo,
                'superadmin_id' => $userId,
                'descripcion'   => "SuperAdministrador restableció la plantilla '{$codigo}' a los valores originales de fábrica"
            ]);
        }

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Plantilla restablecida a su diseño original' : 'No se pudo restablecer la plantilla'
        ]);
        exit;
    }

    /**
     * API: Sube una nueva imagen de cintillo o membrete institucional.
     * POST /superadmin/subirCintilloAjax
     */
    public function subirCintilloAjax(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        if (empty($_FILES['cintillo_file'])) {
            echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo.']);
            exit;
        }

        $file = $_FILES['cintillo_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Error al transferir el archivo (código ' . $file['error'] . ').']);
            exit;
        }

        // Validar tamaño (máximo 4MB)
        if ($file['size'] > 4 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'La imagen no debe superar los 4 MB.']);
            exit;
        }

        // Validar tipo MIME
        $mime = mime_content_type($file['tmp_name']);
        $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
        if (!in_array($mime, $allowed, true)) {
            echo json_encode(['success' => false, 'message' => 'Formato no soportado. Debe ser PNG o JPG.']);
            exit;
        }

        $uploadDir = APPROOT . '/../public/img/cintillos/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'cintillo_custom_' . date('Ymd_His') . '.' . $ext;
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            $relUrl = 'img/cintillos/' . $fileName;
            AuditModel::log('CINTILLO_SUBIDO', 'plantillas_reportes', null, [
                'archivo'       => $fileName,
                'superadmin_id' => (int)Session::get('user_id'),
                'descripcion'   => "SuperAdministrador subió un nuevo cintillo institucional: {$fileName}"
            ]);

            echo json_encode([
                'success' => true,
                'url'     => $relUrl,
                'message' => 'Cintillo institucional actualizado con éxito.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar la imagen en el servidor.']);
        }
        exit;
    }

    /**
     * Genera una previsualización en PDF real con datos de muestra usando Dompdf.
     * GET /superadmin/previewPlantillaPdf?codigo=X
     */
    public function previewPlantillaPdf(): void
    {
        $this->guardSuperAdmin();

        $codigo = trim($_GET['codigo'] ?? 'constancia_culminacion');
        $plantillasModel = $this->model('PlantillaReporte');
        $p = $plantillasModel->getByCodigo($codigo);

        if (!$p) {
            exit('Plantilla no encontrada');
        }

        // Datos simulados de prueba para renderizar las variables
        $muestra = [
            '{NOMBRE_PASANTE}'   => 'CARLOS MANUEL RODRÍGUEZ PÉREZ',
            '{CEDULA_PASANTE}'   => 'V-28.456.789',
            '{DEPARTAMENTO}'     => 'División de Soporte Técnico y Redes',
            '{INSTITUCION}'      => 'Universidad Nacional Experimental de Guayana (UNEG)',
            '{PERIODO}'          => 'Cohorte 2026-I (6 Meses)',
            '{HORAS_ACUMULADAS}' => '1440',
            '{HORAS_META}'       => '1440',
            '{FECHA_INICIO}'     => '02/02/2026',
            '{FECHA_FIN}'        => '28/08/2026',
            '{FECHA_EMISION}'    => date('d \d\e F \d\e Y'),
            '{NUMERO_CONTROL}'   => 'SGP-PREVIEW-' . date('Ymd-His'),
            '{TUTOR_NOMBRE}'     => 'ING. LUIS YÁNEZ',
            '{TUTOR_CEDULA}'     => 'V-20.123.456',
            '{TUTOR_CARGO}'      => 'Tutor Institucional Senior',
            '{CARGO_FIRMA}'      => 'Jefe de la División de Soporte Técnico de la Dirección de Telemática'
        ];

        // Sustituir variables en el cuerpo
        $cuerpoFinal = str_replace(array_keys($muestra), array_values($muestra), $p['cuerpo_html']);
        $tituloFinal = str_replace(array_keys($muestra), array_values($muestra), $p['titulo_documento']);

        // Cintillo en base64
        $cintilloBase64 = '';
        $cintilloPath = !empty($p['cintillo_url']) ? APPROOT . '/../public/' . ltrim($p['cintillo_url'], '/') : APPROOT . '/../public/img/cintillo.png';
        if (!file_exists($cintilloPath)) {
            $cintilloPath = APPROOT . '/../public/img/cintillo.png';
        }
        if (file_exists($cintilloPath)) {
            $ext = strtolower(pathinfo($cintilloPath, PATHINFO_EXTENSION));
            $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
            $cintilloBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($cintilloPath));
        }

        $firmas = $p['firmas'] ?? [];
        $firmanteNombre = str_replace(array_keys($muestra), array_values($muestra), $firmas['firmante_nombre'] ?? 'ING. LUIS YÁNEZ');
        $firmanteCargo  = $firmas['firmante_cargo'] ?? 'Jefe de la División de Soporte Técnico de la Dirección de Telemática';
        $antefirma      = $firmas['antefirma'] ?? 'Atentamente,';
        $mostrarSello   = !empty($firmas['mostrar_sello']);
        $selloTexto     = $firmas['sello_texto'] ?? 'Sello Oficial';

        $selloPosStyle = '';
        if (!empty($firmas['sello_pos']) && is_array($firmas['sello_pos'])) {
            $sx = (int)($firmas['sello_pos']['x'] ?? 0);
            $sy = (int)($firmas['sello_pos']['y'] ?? 0);
            if ($sx !== 0 || $sy !== 0) {
                $selloPosStyle = 'position: relative; left: ' . $sx . 'px; top: ' . $sy . 'px;';
            }
        }

        $html = '<!DOCTYPE html>
        <html lang="es">
        <head>
        <meta charset="UTF-8">
        <style>
            @page { margin: 2.5cm 2cm; }
            body { font-family: Helvetica, Arial, sans-serif; font-size: 12.5px; color: #0D1424; line-height: 1.75; }
            .header-cintillo { width: 100%; margin-bottom: 22px; text-align: center; }
            .header-cintillo img { width: 100%; max-height: 85px; display: block; }
            .control-bar { display: table; width: 100%; margin-bottom: 22px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; font-size: 11px; }
            .control-left { display: table-cell; text-align: left; font-weight: bold; color: #162660; }
            .control-right { display: table-cell; text-align: right; color: #64748b; }
            .doc-titulo { font-weight: bold; font-size: 15px; color: #162660; margin-bottom: 22px; border-bottom: 2px solid #162660; padding-bottom: 8px; text-align: center; letter-spacing: 1.5px; text-transform: uppercase; }
            .doc-cuerpo { text-align: justify; margin-bottom: 24px; font-size: 12.5px; line-height: 1.8; }
            .doc-cuerpo p { margin-bottom: 14px; }
            .firma-row { display: table; width: 100%; margin-top: 45px; }
            .firma-col { display: table-cell; width: 50%; text-align: center; vertical-align: bottom; }
            .firma-antefirma { font-size: 11px; color: #475569; font-style: italic; margin-bottom: 40px; }
            .firma-linea { border-top: 1px solid #0D1424; display: inline-block; min-width: 280px; padding-top: 6px; font-size: 11px; font-weight: bold; }
            .firma-cargo { font-size: 10px; font-weight: bold; color: #162660; text-transform: uppercase; margin-top: 2px; }
            .sello-box { border: 2px dashed #cbd5e1; border-radius: 8px; width: 130px; height: 85px; margin: 0 auto; color: #94a3b8; font-size: 9px; text-align: center; padding-top: 35px; }
            .footer-line { text-align: center; font-size: 9px; color: #94a3b8; margin-top: 40px; padding-top: 8px; border-top: 1px solid #e2e8f0; }
        </style>
        </head>
        <body>
            <div class="header-cintillo">
                ' . ($cintilloBase64 ? '<img src="' . $cintilloBase64 . '" alt="Cintillo">' : '<div style="background:#162660;color:white;padding:12px;font-weight:bold;">INSTITUTO DE SALUD PÚBLICA DEL ESTADO BOLÍVAR</div>') . '
            </div>
            <div class="control-bar">
                <div class="control-left">N° DE CONTROL: ' . $muestra['{NUMERO_CONTROL}'] . '</div>
                <div class="control-right">Ciudad Bolívar, ' . $muestra['{FECHA_EMISION}'] . '</div>
            </div>
            <div class="doc-titulo">' . htmlspecialchars($tituloFinal) . '</div>
            <div class="doc-cuerpo">' . $cuerpoFinal . '</div>
            <div class="firma-row">
                <div class="firma-col">
                    ' . (!empty($antefirma) ? '<div class="firma-antefirma">' . htmlspecialchars($antefirma) . '</div>' : '<div style="margin-bottom: 40px;"></div>') . '
                    <div class="firma-linea">
                        ' . htmlspecialchars($firmanteNombre) . '<br>
                        <span class="firma-cargo">' . htmlspecialchars($firmanteCargo) . '</span>
                    </div>
                </div>
                <div class="firma-col">
                    ' . ($mostrarSello ? '<div class="sello-box" style="' . $selloPosStyle . '">' . htmlspecialchars($selloTexto) . '<br><small>Dirección de Telemática</small></div>' : '') . '
                </div>
            </div>
            <div class="footer-line">' . htmlspecialchars($p['pie_pagina'] ?? '') . ' &mdash; ' . date('d/m/Y h:i A') . '</div>
        </body>
        </html>';

        require_once APPROOT . '/../vendor/autoload.php';
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->setDefaultFont('Helvetica');
        $dompdf = new \Dompdf\Dompdf($options);

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($p['tamano_papel'] ?? 'letter', $p['orientacion'] ?? 'portrait');
        $dompdf->render();

        $dompdf->stream('Vista_Previa_' . $codigo . '.pdf', ['Attachment' => false]);
        exit;
    }

    /**
     * Alternar el modo de mantenimiento institucional global
     */
    public function toggleMantenimiento(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $estado = !empty($input['estado']) ? '1' : '0';
        $mensaje = trim($input['mensaje'] ?? 'El sistema se encuentra en labores de optimización y mantenimiento programado. Por favor, reintente más tarde.');

        $db = Database::getInstance();
        $db->query("INSERT INTO configuracion (clave, valor) VALUES ('modo_mantenimiento', :val) ON DUPLICATE KEY UPDATE valor = :val");
        $db->bind(':val', $estado);
        $db->execute();

        $db->query("INSERT INTO configuracion (clave, valor) VALUES ('modo_mantenimiento_mensaje', :msg) ON DUPLICATE KEY UPDATE valor = :msg");
        $db->bind(':msg', $mensaje);
        $db->execute();

        require_once APPROOT . '/models/AuditModel.php';
        AuditModel::log('MANTENIMIENTO_SISTEMA', 'configuracion', null, [
            'modo' => $estado === '1' ? 'ACTIVADO' : 'DESACTIVADO',
            'mensaje' => $mensaje
        ]);

        echo json_encode([
            'success' => true,
            'mantenimiento' => $estado === '1',
            'mensaje' => $estado === '1' ? 'Modo mantenimiento activado exitosamente.' : 'Modo mantenimiento desactivado. El sistema vuelve a operar con normalidad.'
        ]);
        exit;
    }

    /**
     * Revocar y cerrar la sesión activa de un usuario de forma remota
     */
    public function cerrarSesionRemota(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $targetUserId = (int)($input['usuario_id'] ?? 0);

        if ($targetUserId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
            return;
        }

        $currentUserId = (int)Session::get('user_id');
        if ($targetUserId === $currentUserId) {
            echo json_encode(['success' => false, 'message' => 'No puede forzar el cierre de su propia sesión actual.']);
            return;
        }

        $db = Database::getInstance();
        $db->query("UPDATE usuarios SET sesion_id = NULL, ultima_actividad = '2000-01-01 00:00:00' WHERE id = :id");
        $db->bind(':id', $targetUserId);
        $db->execute();

        require_once APPROOT . '/models/AuditModel.php';
        AuditModel::log('CIERRE_SESION_REMOTA', 'usuarios', $targetUserId, [
            'admin_id' => $currentUserId,
            'motivo' => 'Cierre forzado desde el monitor de sesiones activas del SuperAdministrador'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Sesión revocada exitosamente. El usuario será desconectado en su próxima acción.'
        ]);
        exit;
    }

    /**
     * Responder y actualizar el estado de un ticket de soporte técnico
     */
    public function responderTicket(): void
    {
        $this->guardSuperAdmin();
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $ticketId = (int)($input['ticket_id'] ?? 0);
        $estado = trim($input['estado'] ?? 'En Proceso');
        $respuesta = trim($input['respuesta'] ?? '');

        if ($ticketId <= 0 || empty($respuesta)) {
            echo json_encode(['success' => false, 'message' => 'Debe indicar un ticket válido y una respuesta técnica.']);
            return;
        }

        $db = Database::getInstance();
        $db->query("SELECT t.*, u.correo, CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS nombre_usuario 
                    FROM soporte_tickets t
                    INNER JOIN usuarios u ON u.id = t.usuario_id
                    LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                    WHERE t.id = :id");
        $db->bind(':id', $ticketId);
        $ticket = $db->single();

        if (!$ticket) {
            echo json_encode(['success' => false, 'message' => 'Ticket no encontrado']);
            return;
        }

        $adminId = (int)Session::get('user_id');
        $db->query("UPDATE soporte_tickets SET estado = :estado, respuesta_tecnica = :resp, responded_by = :resp_by, updated_at = NOW() WHERE id = :id");
        $db->bind(':estado', $estado);
        $db->bind(':resp', $respuesta);
        $db->bind(':resp_by', $adminId);
        $db->bind(':id', $ticketId);
        $db->execute();

        try {
            require_once APPROOT . '/lib/MailService.php';
            $htmlMsg = "
                <div style='font-family:sans-serif;max-width:600px;margin:auto;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;'>
                    <div style='background:#162660;padding:20px;text-align:center;color:#fff;'>
                        <h2 style='margin:0;'>Soporte Técnico SGP - ISPEB</h2>
                        <p style='margin:4px 0 0;font-size:13px;opacity:0.9;'>Actualización del Ticket #{$ticket->codigo}</p>
                    </div>
                    <div style='padding:24px;color:#1e293b;line-height:1.6;'>
                        <p>Estimado/a <strong>" . htmlspecialchars($ticket->nombre_usuario ?: $ticket->correo) . "</strong>,</p>
                        <p>Su solicitud de soporte con código <strong>#{$ticket->codigo}</strong> ha sido actualizada al estado: <span style='background:#eff6ff;color:#1d4ed8;padding:3px 8px;border-radius:6px;font-weight:bold;'>{$estado}</span>.</p>
                        <div style='background:#f8fafc;border-left:4px solid #162660;padding:14px;margin:16px 0;border-radius:0 8px 8px 0;'>
                            <strong style='display:block;margin-bottom:6px;color:#0f172a;'>Respuesta del Equipo Técnico:</strong>
                            " . nl2br(htmlspecialchars($respuesta)) . "
                        </div>
                        <p style='font-size:12px;color:#64748b;'>Instituto de Salud Pública del Estado Bolívar &mdash; Sistema de Gestión de Pasantes</p>
                    </div>
                </div>";
            MailService::send($ticket->correo, $ticket->nombre_usuario ?: 'Usuario SGP', "Respuesta a Ticket #{$ticket->codigo}: {$ticket->asunto}", $htmlMsg, '', 'MANUAL', $ticket->usuario_id, true);
        } catch (\Throwable $e) {
            error_log("[SOPORTE-MAIL-ERR] " . $e->getMessage());
        }

        require_once APPROOT . '/models/AuditModel.php';
        AuditModel::log('RESPUESTA_TICKET_SOPORTE', 'soporte_tickets', $ticketId, [
            'codigo' => $ticket->codigo,
            'estado' => $estado
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Respuesta técnica registrada y ticket actualizado exitosamente.'
        ]);
        exit;
    }

    /**
     * Crear un nuevo ticket de soporte técnico (accesible para cualquier usuario autenticado)
     */
    public function crearTicketSoporte(): void
    {
        Session::start();
        $userId = Session::get('user_id');
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Debe iniciar sesión para solicitar soporte.']);
            exit;
        }

        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $asunto = trim($input['asunto'] ?? '');
        $descripcion = trim($input['descripcion'] ?? '');
        $prioridad = trim($input['prioridad'] ?? 'Media');

        if (empty($asunto) || empty($descripcion)) {
            echo json_encode(['success' => false, 'message' => 'Debe completar el asunto y la descripción detallada del problema.']);
            return;
        }

        $codigoTicket = 'TCK-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $db = Database::getInstance();
        $db->query("SELECT u.correo, r.nombre AS rol_nombre, CONCAT(COALESCE(dp.nombres,''), ' ', COALESCE(dp.apellidos,'')) AS nombre_completo
                    FROM usuarios u
                    LEFT JOIN roles r ON r.id = u.rol_id
                    LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                    WHERE u.id = :id");
        $db->bind(':id', $userId);
        $remitente = $db->single();

        $nombreRemitente = trim($remitente->nombre_completo ?? '') ?: (Session::get('user_name') ?: 'Usuario SGP');
        $correoRemitente = trim($remitente->correo ?? '') ?: (Session::get('user_email') ?: 'soporte@ispeb.gob.ve');
        $ipCliente = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $db->query("INSERT INTO soporte_tickets (codigo, usuario_id, nombre, correo, categoria, prioridad, asunto, mensaje, estado, ip, created_at)
                    VALUES (:cod, :uid, :nombre, :correo, 'Error del Sistema', :prio, :asunto, :mensaje, 'Abierto', :ip, NOW())");
        $db->bind(':cod', $codigoTicket);
        $db->bind(':uid', $userId);
        $db->bind(':nombre', $nombreRemitente);
        $db->bind(':correo', $correoRemitente);
        $db->bind(':prio', $prioridad);
        $db->bind(':asunto', $asunto);
        $db->bind(':mensaje', $descripcion);
        $db->bind(':ip', $ipCliente);
        $db->execute();
        $ticketId = (int)$db->lastInsertId();

        try {
            require_once APPROOT . '/lib/MailService.php';
            $supportEmail = 'sgp.ispeb@gmail.com';
            $db->query("SELECT valor FROM configuracion WHERE clave = 'correo_soporte'");
            $cfgRow = $db->single();
            if ($cfgRow && !empty($cfgRow->valor)) {
                $supportEmail = trim($cfgRow->valor);
            }

            $htmlMsg = "
                <div style='font-family:sans-serif;max-width:600px;margin:auto;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;'>
                    <div style='background:#162660;padding:20px;text-align:center;color:#fff;'>
                        <h2 style='margin:0;'>Nuevo Ticket de Soporte Técnico</h2>
                        <p style='margin:4px 0 0;font-size:13px;opacity:0.9;'>Código #{$codigoTicket} &bull; Prioridad: {$prioridad}</p>
                    </div>
                    <div style='padding:24px;color:#1e293b;line-height:1.6;'>
                        <p><strong>Remitente:</strong> " . htmlspecialchars($remitente->nombre_completo ?: $remitente->correo) . " (" . htmlspecialchars($remitente->rol_nombre ?? 'Usuario') . ")</p>
                        <p><strong>Correo:</strong> " . htmlspecialchars($remitente->correo) . "</p>
                        <p><strong>Asunto:</strong> " . htmlspecialchars($asunto) . "</p>
                        <div style='background:#f8fafc;border-left:4px solid #f59e0b;padding:14px;margin:16px 0;border-radius:0 8px 8px 0;'>
                            <strong style='display:block;margin-bottom:6px;color:#0f172a;'>Descripción de la Falla / Solicitud:</strong>
                            " . nl2br(htmlspecialchars($descripcion)) . "
                        </div>
                        <p style='font-size:12px;color:#64748b;'>Este ticket puede ser atendido directamente desde el Dashboard del SuperAdministrador en SGP.</p>
                    </div>
                </div>";
            MailService::send($supportEmail, 'Mesa de Soporte ISPEB', "[TICKET #{$codigoTicket}] {$asunto}", $htmlMsg, '', 'MANUAL', null, true);
        } catch (\Throwable $e) {
            error_log("[SOPORTE-MAIL-ERR] " . $e->getMessage());
        }

        require_once APPROOT . '/models/AuditModel.php';
        AuditModel::log('CREAR_TICKET_SOPORTE', 'soporte_tickets', $ticketId, [
            'codigo' => $codigoTicket,
            'prioridad' => $prioridad
        ]);

        echo json_encode([
            'success' => true,
            'codigo' => $codigoTicket,
            'message' => "Ticket #{$codigoTicket} generado con éxito. El equipo de soporte técnico ha sido notificado vía correo."
        ]);
        exit;
    }
}

