<?php
/**
 * DaemonController — Procesos automáticos del sistema SGP
 * =========================================================
 * Expone endpoints de administración para ejecutar procesos
 * de mantenimiento de datos. Todos los métodos requieren:
 *   - Sesión activa con rol Administrador (rol_id = 1)
 *   - Token CSRF válido (peticiones POST)
 */
class DaemonController extends Controller
{
    public function __construct()
    {
        // [FIX-AC-7] Los métodos ::verificar() no existen en ninguna de las dos
        // clases (son AuthMiddleware::require() y RoleMiddleware::authorize()),
        // lo que provocaba un Fatal Error en cada acceso a /daemon/*.
        AuthMiddleware::require();
        RoleMiddleware::authorize([1]);

        require_once APPROOT . '/helpers/AutoFillService.php';
        require_once APPROOT . '/models/AuditModel.php';
    }

    // ── GET /daemon ─────────────────────────────────────────────────────────
    public function index(): void
    {
        $this->view('daemon/index', [], true);
    }

    // ── POST /daemon/autoFill ────────────────────────────────────────────────
    /**
     * Ejecuta el Auto-Fill de asistencias para TODOS los pasantes activos.
     * Marca como Ausente (0h) los días L-V sin registro.
     * Modo dry_run=1 → solo simula sin insertar.
     *
     * Response JSON: { success, stats }
     */
    public function autoFill(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }

        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::validateCsrfToken($token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            exit;
        }

        try {
            $dryRun = isset($_POST['dry_run']) && $_POST['dry_run'] === '1';
            $db     = Database::getInstance();
            $stats  = AutoFillService::ejecutar($db, $dryRun);

            AuditModel::log(
                $dryRun ? 'DAEMON_AUTOFILL_PREVIEW' : 'DAEMON_AUTOFILL_EJECUTADO',
                'asistencias',
                null,
                json_encode([
                    'ausentes'  => $stats['dias_rellenos'],
                    'pasantes'  => $stats['pasantes_afectados'],
                    'dry_run'   => $dryRun,
                ])
            );

            echo json_encode([
                'success' => true,
                'dry_run' => $dryRun,
                'stats'   => [
                    'revisados'          => $stats['revisados'],
                    'pasantes_afectados' => $stats['pasantes_afectados'],
                    'dias_rellenos'      => $stats['dias_rellenos'],
                    'dias_omitidos'      => $stats['dias_omitidos'],
                    'errores'            => $stats['errores'],
                ],
                'detalle' => $stats['detalle'],
            ]);

        } catch (\Throwable $e) {
            // [FIX-ED-5] Mensaje genérico al cliente — detalle solo en log
            error_log('[SGP-DAEMON] autoFill error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al ejecutar el auto-relleno. Intente de nuevo.']);
        }

        exit;
    }

    // ── GET /daemon/autoFillPreview ──────────────────────────────────────────
    /**
     * Vista previa: muestra cuántos días se rellenarían sin ejecutar.
     * Devuelve JSON consumido desde la UI de configuración.
     */
    public function autoFillPreview(): void
    {
        header('Content-Type: application/json');

        try {
            $db    = Database::getInstance();
            $stats = AutoFillService::ejecutar($db, true);

            echo json_encode([
                'success'            => true,
                'pasantes_afectados' => $stats['pasantes_afectados'],
                'dias_a_rellenar'    => $stats['dias_rellenos'],
                'detalle'            => array_slice($stats['detalle'], 0, 10),
            ]);

        } catch (\Throwable $e) {
            // [FIX-ED-5] Antes no se logueaba en absoluto — un fallo en producción no
            // dejaba rastro. Ahora se registra y se da mensaje genérico al cliente.
            error_log('[SGP-DAEMON] autoFillPreview error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error al generar la vista previa.']);
        }

        exit;
    }
}
