<?php
/**
 * COMPONENTE: CABECERA INSTITUCIONAL (CINTILLO OFICIAL)
 *
 * Incluido en todos los PDFs del SGP.
 * Convierte el cintillo a Base64 para que DomPDF lo renderice
 * sin problemas de permisos o rutas relativas.
 */

$baseImgDir = defined('APPROOT') ? APPROOT . '/../public/img/' : dirname(dirname(dirname(__DIR__))) . '/public/img/';
$cintilloCandidates = [
    $baseImgDir . 'cintillo-salud.png',
    $baseImgDir . 'cintillo_isp_bolivar.jpg',
    $baseImgDir . 'cintillo_pdf.png',
    $baseImgDir . 'cintillo.png'
];

$base64_cintillo = '';
foreach ($cintilloCandidates as $cp) {
    if (file_exists($cp)) {
        $ext = strtolower(pathinfo($cp, PATHINFO_EXTENSION));
        $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
        $base64_cintillo = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($cp));
        break;
    }
}
?>

<?php if ($base64_cintillo): ?>
<div style="width: 100%; margin-bottom: 20px; line-height: 0;">
    <img src="<?= $base64_cintillo ?>"
         style="width: 100%; height: auto; display: block;"
         alt="Cintillo Institucional ISP Bolívar">
</div>
<?php else: ?>
<!-- Fallback: bloque de color si la imagen no existe en disco -->
<div style="width: 100%; padding: 14px 20px; background-color: #162660;
            color: white; font-family: Helvetica, Arial, sans-serif;
            font-size: 12pt; font-weight: bold; margin-bottom: 20px;">
    Instituto de Salud Pública de Bolívar &nbsp;|&nbsp; SGP
</div>
<?php endif; ?>
