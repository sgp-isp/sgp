<?php
/**
 * PlantillasController — Diseñador y Gestor de Plantillas PDF Oficiales
 * 
 * Permite a SuperAdministradores y Administradores personalizar y previsualizar
 * las plantillas oficiales del sistema (Constancias, Evaluaciones, Cartas de Aceptación/Culminación).
 */
class PlantillasController extends Controller
{
    /**
     * Guard de seguridad: solo SuperAdministrador (0) y Administrador (1)
     */
    private function guardAccess(): void
    {
        Session::start();
        $userId = (int)Session::get('user_id');
        $roleId = (int)Session::get('role_id');

        if (!$userId) {
            $this->redirect('/auth/login');
            exit;
        }

        if (!in_array($roleId, [0, 1], true)) {
            http_response_code(403);
            require APPROOT . '/views/errors/403.php';
            exit;
        }
    }

    /**
     * Vista principal: Diseñador de Plantillas PDF
     */
    public function index(): void
    {
        $this->guardAccess();

        $plantillasModel = $this->model('PlantillaReporte');
        $plantillas = $plantillasModel->getAll();

        $categorias = [];
        foreach ($plantillas as $p) {
            $cat = $p['categoria'] ?? 'General';
            $categorias[$cat] = ($categorias[$cat] ?? 0) + 1;
        }

        $this->view('superadmin/plantillas', [
            'title'      => 'Diseñador de Plantillas PDF',
            'plantillas' => $plantillas,
            'categorias' => $categorias,
            'total'      => count($plantillas)
        ]);
    }

    /**
     * API: Obtiene una plantilla específica por código.
     * GET /plantillas/getPlantillaAjax?codigo=X
     */
    public function getPlantillaAjax(): void
    {
        $this->guardAccess();
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
     * POST /plantillas/guardarPlantillaAjax
     */
    public function guardarPlantillaAjax(): void
    {
        $this->guardAccess();
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
            require_once APPROOT . '/models/AuditModel.php';
            AuditModel::log('PLANTILLA_REPORTE_MODIFICADA', 'plantillas_reportes', null, [
                'codigo'           => $codigo,
                'titulo_documento' => $input['titulo_documento'] ?? '',
                'usuario_id'       => $userId,
                'descripcion'      => "Usuario guardó cambios en la plantilla maestra '{$codigo}'"
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
     * POST /plantillas/resetPlantillaAjax
     */
    public function resetPlantillaAjax(): void
    {
        $this->guardAccess();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $codigo = trim($input['codigo'] ?? '');

        if (empty($codigo)) {
            echo json_encode(['success' => false, 'message' => 'Código requerido.']);
            exit;
        }

        $plantillasModel = $this->model('PlantillaReporte');
        $userId = (int)Session::get('user_id');
        $ok = $plantillasModel->restablecer($codigo, $userId);

        echo json_encode([
            'success' => $ok,
            'message' => $ok ? 'Plantilla restablecida a valores de fábrica' : 'No se pudo restablecer la plantilla'
        ]);
        exit;
    }

    /**
     * API: Sube una nueva imagen de cintillo o membrete institucional.
     * POST /plantillas/subirCintilloAjax
     */
    public function subirCintilloAjax(): void
    {
        $this->guardAccess();
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
            require_once APPROOT . '/models/AuditModel.php';
            AuditModel::log('CINTILLO_SUBIDO', 'plantillas_reportes', null, [
                'archivo'    => $fileName,
                'usuario_id' => (int)Session::get('user_id'),
                'descripcion'=> "Usuario subió un nuevo cintillo institucional: {$fileName}"
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
     * GET /plantillas/previewPlantillaPdf?codigo=X
     */
    public function previewPlantillaPdf(): void
    {
        $this->guardAccess();

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
                <div class="control-left">N° Control: SGP-PREVIEW-DEMO</div>
                <div class="control-right">Ciudad Bolívar, ' . date('d/m/Y') . '</div>
            </div>
            <div class="doc-titulo">' . htmlspecialchars($tituloFinal) . '</div>
            <div class="doc-cuerpo">' . $cuerpoFinal . '</div>
            <div class="firma-row">
                <div class="firma-col">
                    ' . ($mostrarSello ? '<div class="sello-box" style="' . $selloPosStyle . '">[' . htmlspecialchars($selloTexto) . ']</div>' : '') . '
                </div>
                <div class="firma-col">
                    <div class="firma-antefirma">' . htmlspecialchars($antefirma) . '</div>
                    <div class="firma-linea">' . htmlspecialchars($firmanteNombre) . '</div>
                    <div class="firma-cargo">' . htmlspecialchars($firmanteCargo) . '</div>
                </div>
            </div>
            <div class="footer-line">
                Documento de previsualización generado por el Diseñador Oficial SGP — Instituto de Salud Pública del Estado Bolívar
            </div>
        </body>
        </html>';

        try {
            if (class_exists('Dompdf\Dompdf')) {
                $options = new \Dompdf\Options();
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
                $dompdf = new \Dompdf\Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('letter', 'portrait');
                $dompdf->render();
                $dompdf->stream('preview_' . $codigo . '.pdf', ['Attachment' => false]);
                exit;
            }
        } catch (\Throwable $e) {}

        echo $html;
        exit;
    }
}
