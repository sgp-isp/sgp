<?php
/**
 * COMPONENTE: CABECERA INSTITUCIONAL (CINTILLO OFICIAL)
 *
 * Incluido en todos los PDFs del SGP.
 * Convierte el cintillo a Base64 para que DomPDF lo renderice
 * sin problemas de permisos o rutas relativas.
 */

$cintillo_path = __DIR__ . '/../../../../public/img/cintillo_isp_bolivar.jpg';

if (!function_exists('imgToBase64')) {
    function imgToBase64(string $path): string
    {
        // Cache estático: evita re-leer y re-codificar el archivo en el mismo request
        static $cache = [];
        if (isset($cache[$path])) {
            return $cache[$path];
        }
        if (file_exists($path)) {
            $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = ($ext === 'jpg') ? 'jpeg' : $ext;
            $cache[$path] = 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($path));
            return $cache[$path];
        }
        return $cache[$path] = '';
    }
}

$base64_cintillo = imgToBase64($cintillo_path);
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
