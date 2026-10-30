<?php
/**
 * AdminController — Panel de Control del Administrador (Rol ID = 1)
 *
 * Obtiene métricas reales de la BD para poblar el dashboard usando Clean MVC.
 */

declare(strict_types=1);

class AdminController extends Controller
{
    private $dashboardModel;

    public function __construct()
    {
        Session::start();
        AuthMiddleware::require();
        AuthMiddleware::verificarEstado();

        // 🔒 NUEVO: Protección Anti-Caché estricta
        CacheControl::noCache();

        if (!RoleMiddleware::hasAnyRole([0, 1])) {
            RoleMiddleware::redirectToRoleDashboard(Session::get('role_id'));
        }

        // Instanciar el modelo con el método heredado del Controller base
        $this->dashboardModel = $this->model('DashboardAdminModel');
    }

    /** Obtiene la instancia de BD para métodos AJAX de este controller */
    private function getDb(): Database
    {
        return Database::getInstance();
    }

    /**
     * GET /admin
     * Dashboard principal con métricas reales del sistema a través del Modelo.
     */
    public function index(): void
    {
        // Traer métricas base en una sola consulta
        $kpis = $this->dashboardModel->getKpiTotales();

        // Traer y estructurar la data cruzada para la vista y el Data Bridge JSON
        $data = [
            'user_name'          => Session::get('user_name') ?? 'Administrador',
            'role'               => 'Administrador',
            'totalActivos'       => $kpis->totalActivos,
            'pendientesAsignar'  => $kpis->pendientesAsignar,
            'asistenciasHoy'     => $kpis->asistenciasHoy,
            'faltasHoy'          => $kpis->faltasHoy,
            'totalTutores'       => $kpis->totalTutores,
            'totalInstituciones' => $kpis->totalInstituciones,
            'actividadReciente'  => $this->dashboardModel->getActividadReciente(8),
            'alertas_pendientes' => $this->dashboardModel->getAlertasPendientes(6),
            'departamentos'      => $this->dashboardModel->getDepartamentosParaAsignacion(),
            'tutores'            => $this->dashboardModel->getTutoresParaAsignacion(),
            'periodos'           => $this->dashboardModel->getPeriodosAcademicos(),
            
            'ausentesMes'           => $this->dashboardModel->getAusentesMes(),
            'cortosResumen'         => $this->dashboardModel->getCortosResumen(),
            'alertasPeriodosProx'   => $this->dashboardModel->getAlertasPeriodosProximos(7),
            'alertasPeriodosVencidos' => $this->dashboardModel->getAlertasPeriodosVencidos(),
            'periodosRecientementeC'  => $this->dashboardModel->getPeriodosRecientementeCerrados(30),

            // ── NUEVO: DATA BRIDGE (Gráficas Dinámicas) ──
            'metricas_graficos' => [
                'asistenciaDiaria'        => $this->dashboardModel->getAsistenciaDiaria(),
                'asistenciaSemanal'       => $this->dashboardModel->getAsistenciaSemanal(),
                'asistenciaMensual'       => $this->dashboardModel->getAsistenciaMensual(),
                'asistenciaDepartamento'  => $this->dashboardModel->getAsistenciaPorDepartamento(),
                'asistenciaInstitucion'   => $this->dashboardModel->getAsistenciaPorInstitucion(),
            ]
        ];

        $this->view('admin/dashboard', $data);
    }

    /**
     * POST /admin/cerrarPeriodo  (AJAX → JSON)
     * Cierra un período vencido desde el dashboard y finaliza sus pasantes.
     */
    public function cerrarPeriodo(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        $raw      = file_get_contents('php://input');
        $json     = json_decode($raw, true);
        $token    = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($json['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
            exit;
        }

        $periodoId = (int)($json['periodo_id'] ?? 0);
        if ($periodoId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de período inválido.']);
            exit;
        }

        $db = $this->getDb();

        // Verificar que el período existe y no está cerrado
        $db->query("SELECT id, nombre, estado FROM periodos_academicos WHERE id = :id LIMIT 1");
        $db->bind(':id', $periodoId);
        $periodo = $db->single();

        if (!$periodo || $periodo->estado === 'Cerrado') {
            echo json_encode(['success' => false, 'message' => 'Período no encontrado o ya está cerrado.']);
            exit;
        }

        // 1. Cerrar el período
        $db->query("UPDATE periodos_academicos SET estado = 'Cerrado' WHERE id = :id");
        $db->bind(':id', $periodoId);
        $db->execute();

        // 2. Obtener pasantes activos con horas calculadas dinámicamente
        $db->query("
            SELECT dp.usuario_id, dp.horas_meta,
                   COALESCE((SELECT COUNT(*) * 8
                             FROM asistencias a WHERE a.pasante_id=dp.usuario_id
                             AND a.estado != 'Anulado'
                             AND a.fecha >= COALESCE(dp.fecha_inicio_pasantia, (SELECT fecha_inicio FROM periodos_academicos WHERE id = dp.periodo_id))
                             AND a.fecha <= CURDATE()), 0) AS horas_acumuladas
            FROM datos_pasante dp
            WHERE dp.periodo_id = :pid AND dp.estado_pasantia = 'Activo'
        ");
        $db->bind(':pid', $periodoId);
        $pasantes      = $db->resultSet();
        $totalPasantes = count($pasantes);

        // 3. Finalizar pasantes y desactivar cuentas
        $db->query("UPDATE datos_pasante SET estado_pasantia = 'Finalizado' WHERE periodo_id = :pid AND estado_pasantia = 'Activo'");
        $db->bind(':pid', $periodoId);
        $db->execute();

        $db->query("UPDATE usuarios SET estado = 'inactivo' WHERE id IN (SELECT usuario_id FROM datos_pasante WHERE periodo_id = :pid) AND rol_id = 3");
        $db->bind(':pid', $periodoId);
        $db->execute();

        // 4. Notificaciones de culminación
        require_once APPROOT . '/models/NotificationModel.php';
        $notifModel = new NotificationModel($db);
        foreach ($pasantes as $p) {
            $hAcum      = (int)($p->horas_acumuladas ?? 0);
            $hMeta      = max(1, (int)($p->horas_meta ?? 1440));
            $pct        = min(100, round($hAcum / $hMeta * 100));
            $completado = $hAcum >= $hMeta;
            $notifModel->createWithRef(
                (int)$p->usuario_id,
                $completado ? 'pasantia_completada' : 'periodo_cerrado',
                $completado ? 'Pasantia completada' : 'Periodo academico cerrado',
                $completado
                    ? "Has completado exitosamente tu pasantia del periodo {$periodo->nombre} con {$hAcum}h ({$pct}%). Excelente!"
                    : "El periodo {$periodo->nombre} ha sido cerrado. Acumulaste {$hAcum}h de {$hMeta}h ({$pct}%).",
                URLROOT . '/pasante/dashboard',
                $periodoId
            );
        }

        // Bitácora
        $adminId = (int)Session::get('user_id');
        $db->query("INSERT INTO bitacora (usuario_id, accion, ip_address, detalles) VALUES (:uid, 'CERRAR_PERIODO', :ip, :det)");
        $db->bind(':uid', $adminId);
        $db->bind(':ip',  $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $db->bind(':det', json_encode(['periodo' => $periodo->nombre, 'pasantes_finalizados' => $totalPasantes]));
        $db->execute();

        echo json_encode([
            'success'              => true,
            'message'              => "Periodo {$periodo->nombre} cerrado correctamente.",
            'pasantes_finalizados' => $totalPasantes,
        ]);
        exit;
    }

    /**
     * GET /admin/infoPreCierre?periodo_id=X  (AJAX → JSON)
     * Devuelve cuántos pasantes del período NO tienen evaluaciones.
     */
    public function infoPreCierre(): void
    {
        header('Content-Type: application/json');
        $periodoId = (int)($_GET['periodo_id'] ?? 0);
        if ($periodoId <= 0) { echo json_encode(['success' => false]); exit; }

        $db = $this->getDb();

        // Verificar pasantes con fecha_fin_estimada en el futuro (aún no terminaron)
        $db->query("
            SELECT u.id, CONCAT(dp.nombres,' ',dp.apellidos) AS nombre,
                   dpa.fecha_fin_estimada,
                   DATEDIFF(dpa.fecha_fin_estimada, CURDATE()) AS dias_restantes
            FROM datos_pasante dpa
            JOIN usuarios u ON u.id = dpa.usuario_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE dpa.periodo_id = :pid AND dpa.estado_pasantia = 'Activo'
              AND dpa.fecha_fin_estimada > CURDATE()
            ORDER BY dpa.fecha_fin_estimada ASC
        ");
        $db->bind(':pid', $periodoId);
        $noTerminaron = $db->resultSet();

        $db->query("
            SELECT u.id, CONCAT(dp.nombres,' ',dp.apellidos) AS nombre
            FROM datos_pasante dpa
            JOIN usuarios u ON u.id = dpa.usuario_id
            LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
            WHERE dpa.periodo_id = :pid AND dpa.estado_pasantia = 'Activo'
              AND NOT EXISTS (SELECT 1 FROM evaluaciones e WHERE e.pasante_id = u.id)
            ORDER BY dp.apellidos
        ");
        $db->bind(':pid', $periodoId);
        $sinEval = $db->resultSet();

        echo json_encode([
            'success'           => true,
            'sin_evaluacion'    => count($sinEval),
            'nombres'           => array_map(fn($p) => $p->nombre, $sinEval),
            'no_terminaron'     => count($noTerminaron),
            'no_terminaron_det' => array_map(fn($p) => [
                'nombre'          => $p->nombre,
                'fecha_fin'       => date('d/m/Y', strtotime($p->fecha_fin_estimada)),
                'dias_restantes'  => (int)$p->dias_restantes,
            ], $noTerminaron),
        ]);
        exit;
    }

    /**
     * POST /admin/reactivarPeriodo  (AJAX → JSON)
     * Reactiva un período cerrado y sus pasantes.
     */
    public function reactivarPeriodo(): void
    {
        header('Content-Type: application/json');
        $raw   = file_get_contents('php://input');
        $json  = json_decode($raw, true);
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($json['csrf_token'] ?? '');
        if (!Session::validateCsrfToken($token)) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF invalido.']); exit;
        }
        $periodoId = (int)($json['periodo_id'] ?? 0);
        if ($periodoId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID invalido.']); exit;
        }

        $db = $this->getDb();

        $db->query("SELECT id, nombre, estado FROM periodos_academicos WHERE id = :id LIMIT 1");
        $db->bind(':id', $periodoId);
        $periodo = $db->single();
        if (!$periodo) {
            echo json_encode(['success' => false, 'message' => 'Periodo no encontrado.']); exit;
        }

        // 1. Reabrir el período
        $db->query("UPDATE periodos_academicos SET estado = 'Activo' WHERE id = :id");
        $db->bind(':id', $periodoId);
        $db->execute();

        // 2. Reactivar pasantes del período
        $db->query("UPDATE datos_pasante SET estado_pasantia = 'Activo' WHERE periodo_id = :pid AND estado_pasantia = 'Finalizado'");
        $db->bind(':pid', $periodoId);
        $db->execute();
        $pasantesReactivados = $db->rowCount();

        // 3. Reactivar cuentas de usuario
        $db->query("UPDATE usuarios SET estado = 'activo' WHERE id IN (SELECT usuario_id FROM datos_pasante WHERE periodo_id = :pid) AND rol_id = 3");
        $db->bind(':pid', $periodoId);
        $db->execute();

        // 4. Bitácora
        $adminId = (int)Session::get('user_id');
        $db->query("INSERT INTO bitacora (usuario_id, accion, ip_address, detalles) VALUES (:uid, 'REACTIVAR_PERIODO', :ip, :det)");
        $db->bind(':uid', $adminId);
        $db->bind(':ip',  $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $db->bind(':det', json_encode(['periodo' => $periodo->nombre, 'pasantes_reactivados' => $pasantesReactivados]));
        $db->execute();

        echo json_encode([
            'success'              => true,
            'message'              => "Periodo {$periodo->nombre} reactivado.",
            'pasantes_reactivados' => $pasantesReactivados,
        ]);
        exit;
    }

    /**
     * POST /admin/cierreDiario  (AJAX → JSON)
     * Ejecuta el cierre diario de asistencias desde el panel web.
     * Inserta 'Ausente/Sistema' en todos los días hábiles sin registro.
     */
    public function cierreDiario(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            exit;
        }

        require_once APPROOT . '/scripts/cierre_diario.php';

        $db  = Database::getInstance();
        $res = ejecutarCierreDiario($db);

        // Log en bitácora
        $adminId = (int)Session::get('user_id');
        $db->query("INSERT INTO bitacora (usuario_id, accion, ip_address, detalles) VALUES (:uid, 'CIERRE_DIARIO', :ip, :det)");
        $db->bind(':uid', $adminId);
        $db->bind(':ip',  $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $db->bind(':det', json_encode(['insertados' => $res['insertados'], 'pasantes' => $res['pasantes']]));
        $db->execute();

        echo json_encode([
            'success'    => true,
            'message'    => "✅ Cierre realizado: {$res['insertados']} registros de ausencia insertados para {$res['pasantes']} pasantes.",
            'detalle'    => $res,
        ]);
        exit;
    }
}
