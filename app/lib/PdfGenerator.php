<?php
/**
 * PdfGenerator — Wrapper unificado para DomPDF y TCPDF
 *
 * USO:
 *   $pdf = new PdfGenerator();
 *   // Para reportes visuales (HTML → PDF)
 *   $pdf->renderDomPdf($htmlContent, 'reporte.pdf');
 *   // Para reportes tabulares masivos
 *   $pdf->renderTcpdf($titulo, $columnas, $filas, 'reporte.pdf');
 */

declare(strict_types=1);

class PdfGenerator
{
    private string $vendorPath;

    public function __construct()
    {
        $this->vendorPath = dirname(__DIR__, 2) . '/vendor';
    }

    // ─────────────────────────────────────────────────────────────────
    // DOM PDF — Reportes con diseño visual (HTML to PDF)
    // Ideal para: Kardex individual, informes de evaluación
    // ─────────────────────────────────────────────────────────────────

    /**
     * Genera un PDF a partir de HTML usando DomPDF y lo descarga.
     *
     * @param string $html     Contenido HTML completo del reporte
     * @param string $filename Nombre del archivo .pdf para descarga
     * @param bool   $download true = forzar descarga | false = inline (para pdf.js viewer)
     */
    public function renderDomPdf(string $html, string $filename = 'reporte.pdf', bool $download = true): void
    {
        $autoload = $this->vendorPath . '/autoload.php';
        if (!file_exists($autoload)) {
            throw new RuntimeException('Composer vendor no encontrado. Ejecuta: composer install');
        }
        require_once $autoload;

        $options = new \Dompdf\Options();
        $options->setIsRemoteEnabled(false);
        $options->setIsHtml5ParserEnabled(false); // parser HTML4 es suficiente y más rápido
        $options->setDefaultFont('Helvetica');     // fuente built-in PDF: carga en 0ms vs ~1s de DejaVu

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename, ['Attachment' => $download]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TCP DF — Reportes tabulares masivos con datos exactos
    // Ideal para: Nóminas de pasantes, listados de asistencia completos
    // ─────────────────────────────────────────────────────────────────

    /**
     * Genera un PDF tabular con TCPDF.
     *
     * @param string  $titulo   Título del reporte
     * @param array   $columnas ['Cabecera 1', 'Cabecera 2', ...]
     * @param array   $filas    [['dato1','dato2'], ['dato3','dato4'], ...]
     * @param string  $filename Nombre del archivo
     * @param string  $subtitulo Subtítulo/descripción del reporte
     * @param bool    $download true = forzar descarga | false = inline
     * @param array   $anchos   Anchos por columna en mm (deben sumar ~180).
     *                          Si se omite, se reparten en partes iguales.
     */
    public function renderTcpdf(
        string $titulo,
        array  $columnas,
        array  $filas,
        string $filename    = 'reporte.pdf',
        string $subtitulo   = '',
        bool   $download    = true,
        array  $anchos      = []
    ): void {
        $autoload = $this->vendorPath . '/autoload.php';
        if (!file_exists($autoload)) {
            throw new RuntimeException('Composer vendor no encontrado. Ejecuta: composer install');
        }
        require_once $autoload;

        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Metadatos
        $pdf->SetCreator('SGP — Sistema de Gestión de Pasantes');
        $pdf->SetAuthor('UPT Bolívar');
        $pdf->SetTitle($titulo);

        // Encabezado / Pie de página
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetFooterFont(['helvetica', '', 8]);
        $pdf->SetFooterMargin(10);

        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();

        // ── Cintillo institucional ─────────────
        $imgPath = __DIR__ . '/../../public/img/cintillo_isp_bolivar.jpg';
        if (file_exists($imgPath)) {
            $pdf->Image($imgPath, 15, 10, 180, 0, 'JPEG');
            $pdf->Ln(22);
        }

        // ── Encabezado del reporte ─────────────
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetTextColor(22, 38, 96); // Deep Azure
        $pdf->Cell(0, 10, $titulo, 0, 1, 'C');

        if ($subtitulo) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(0, 6, $subtitulo, 0, 1, 'C');
        }

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->Cell(0, 6, 'Generado: ' . date('d/m/Y H:i'), 0, 1, 'R');
        $pdf->Ln(4);

        // ── Anchos de columna ──────────────────
        // [SGP-FIX-TCPDF-OVERLAP] Cell() de TCPDF NO envuelve ni recorta el
        // texto: si el contenido es más ancho que la columna, se escribe
        // igual y se monta sobre la celda siguiente (esto era lo que se veía
        // como datos superpuestos en "Pasante"/"Fecha"). Se admite un ancho
        // por columna (para dar más espacio a nombres largos) y además se
        // trunca con "…" cualquier texto que no quepa, como red de seguridad
        // para datos más largos de lo esperado.
        if (count($anchos) === count($columnas)) {
            $colWidths = array_values($anchos);
        } else {
            $colWidths = array_fill(0, count($columnas), 180 / count($columnas));
        }

        $truncar = function (string $texto, float $maxWidth) use ($pdf): string {
            $pad = 2; // margen interno aproximado de la celda (mm)
            if ($pdf->GetStringWidth($texto) <= $maxWidth - $pad) {
                return $texto;
            }
            while (mb_strlen($texto) > 1 && $pdf->GetStringWidth($texto . '…') > $maxWidth - $pad) {
                $texto = mb_substr($texto, 0, -1);
            }
            return rtrim($texto) . '…';
        };

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(22, 38, 96);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(22, 38, 96);

        foreach ($columnas as $i => $col) {
            $pdf->Cell($colWidths[$i], 8, $truncar((string)$col, $colWidths[$i]), 1, 0, 'C', true);
        }
        $pdf->Ln();

        // ── Filas de datos ─────────────────────
        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->SetDrawColor(226, 232, 240);

        $altRow = false;
        foreach ($filas as $fila) {
            $pdf->SetFillColor($altRow ? 248 : 255, $altRow ? 250 : 255, $altRow ? 252 : 255);
            foreach (array_values($fila) as $i => $celda) {
                $w = $colWidths[$i] ?? (180 / count($columnas));
                $pdf->Cell($w, 7, $truncar((string)$celda, $w), 1, 0, 'L', $altRow);
            }
            $pdf->Ln();
            $altRow = !$altRow;
        }

        // Descarga o Previsualización
        $mode = $download ? 'D' : 'I';
        $pdf->Output($filename, $mode);
    }

    // ─────────────────────────────────────────────────────────────────
    // HTML Template helper para DomPDF
    // ─────────────────────────────────────────────────────────────────

    /**
     * Envuelve contenido en template HTML base para DomPDF.
     */
    public static function wrapHtml(string $body, string $titulo = 'Reporte SGP'): string
    {
        $imgPath = __DIR__ . '/../../public/img/cintillo_isp_bolivar.jpg';
        $cintillo = '';
        if (file_exists($imgPath)) {
            $b64 = base64_encode(file_get_contents($imgPath));
            $cintillo = '<div style="width:100%;margin-bottom:16px;line-height:0;">'
                      . '<img src="data:image/jpeg;base64,' . $b64 . '" style="width:100%;height:auto;display:block;" alt="Cintillo ISP Bolívar">'
                      . '</div>';
        } else {
            $cintillo = '<div style="width:100%;padding:12px 20px;background:#162660;color:white;font-family:Helvetica,Arial,sans-serif;font-size:12pt;font-weight:bold;margin-bottom:16px;">Instituto de Salud Pública de Bolívar &nbsp;|&nbsp; SGP</div>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>{$titulo}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
  .section-title { font-size: 13px; font-weight: bold; color: #162660; border-left: 4px solid #162660; padding-left: 10px; margin: 16px 0 10px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
  th { background: #162660; color: white; padding: 7px 10px; font-size: 10px; text-align: left; }
  td { padding: 6px 10px; font-size: 10px; border-bottom: 1px solid #f1f5f9; }
  tr:nth-child(even) td { background: #f8fafc; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; }
  .badge-success { background: #dcfce7; color: #16a34a; }
  .badge-warning { background: #fef3c7; color: #d97706; }
  .badge-info    { background: #dbeafe; color: #1d4ed8; }
  .footer { text-align: center; font-size: 9px; color: #94a3b8; margin-top: 30px; padding-top: 10px; border-top: 1px solid #e2e8f0; }
  .kpi-row { display: flex; gap: 12px; margin-bottom: 16px; }
  .kpi-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; text-align: center; }
  .kpi-val { font-size: 20px; font-weight: bold; color: #162660; }
  .kpi-lbl { font-size: 9px; color: #64748b; margin-top: 2px; }
</style>
</head>
<body>
  {$cintillo}
  {$body}
  <div class="footer">Sistema de Gestión de Pasantes — UPT Bolívar — Documento generado el HTML</div>
</body>
</html>
HTML;
    }
}
