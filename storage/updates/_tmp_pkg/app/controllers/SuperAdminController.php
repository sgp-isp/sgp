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
        $actividadPorUsuario = $db->resultSet() ?: [];

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
        $ok = $permisosModel->savePermiso($userId, $clave, $habilitado, $otorgadoPor);

        if ($ok) {
            AuditModel::log('PERMISO_MODIFICADO', 'usuario_permisos', $userId, [
                'clave'      => $clave,
                'habilitado' => $habilitado ? 'HABILITADO' : 'DESHABILITADO',
                'usuario_id' => $userId,
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
        $ok = $permisosModel->savePermisosLote($userId, $permisos, $otorgadoPor);

        if ($ok) {
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
        $ok = $permisosModel->resetPermisos($userId);

        if ($ok) {
            AuditModel::log('PERMISOS_RESET', 'usuario_permisos', $userId,
                "SuperAdmin restableció permisos al default del rol para usuario #{$userId}");
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
}
