<?php
declare(strict_types=1);

/**
 * Ensambla manual_maestro.html a partir de los 4 manuales de rol
 * (superadministrador, administrador, tutor, pasante), agrupando
 * sus capítulos en 4 Partes (I-IV) precedidas por un separador
 * de Parte, con una TOC nueva y los Anexos A/B compartidos
 * (tomados de superadministrador) al final.
 *
 * Uso: php build_manual_maestro.php
 * Salida: manual_maestro.NUEVO.html (junto a este script, en ../)
 */

$BASE = dirname(__DIR__); // sgp/public/manuales

function leer_normalizado(string $path): string
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException("No se pudo leer $path");
    }
    return str_replace(["\r\n", "\r"], "\n", $raw);
}

/**
 * Devuelve el offset (exclusivo) donde termina el </div> que
 * balancea al <div ...> que comienza en el offset $start.
 */
function fin_div_balanceado(string $html, int $start): int
{
    $depth = 0;
    $cursor = $start;
    while (preg_match('/<div\b[^>]*>|<\/div>/i', $html, $m, PREG_OFFSET_CAPTURE, $cursor)) {
        $tag = $m[0][0];
        $tagPos = $m[0][1];
        if (stripos($tag, '</div>') === 0) {
            $depth--;
            if ($depth === 0) {
                return $tagPos + strlen($tag);
            }
        } else {
            $depth++;
        }
        $cursor = $tagPos + strlen($tag);
    }
    throw new RuntimeException("No se pudo balancear <div> en offset $start");
}

/**
 * Extrae todos los bloques <div class="chapter">...</div> de nivel
 * superior, junto con su comentario decorativo previo y su ch-num.
 *
 * @return array<int, array{comment: ?string, html: string, chnum_raw: string}>
 */
function extraer_capitulos(string $html): array
{
    $capitulos = [];
    $offset = 0;
    $openTag = '<div class="chapter">';

    while (($start = strpos($html, $openTag, $offset)) !== false) {
        $end = fin_div_balanceado($html, $start);
        $chapterHtml = substr($html, $start, $end - $start);

        // Solo se busca en una ventana corta inmediatamente anterior, para no
        // hacer "backtracking" sobre comentarios decorativos de capítulos previos.
        $comment = null;
        $tailLen = 600;
        $tailStart = max(0, $start - $tailLen);
        $tail = substr($html, $tailStart, $start - $tailStart);
        if (preg_match('/(<!--\s*\x{2550}+.*?\x{2550}+\s*-->)\s*$/usD', $tail, $cm)) {
            $comment = $cm[1];
        }

        preg_match('/<div class="ch-num">(.*?)<\/div>/su', $chapterHtml, $numM);

        $capitulos[] = [
            'comment'   => $comment,
            'html'      => $chapterHtml,
            'chnum_raw' => trim($numM[1] ?? ''),
        ];

        $offset = $end;
    }

    return $capitulos;
}

/** @param array<int, array{comment: ?string, html: string, chnum_raw: string}> $capitulos */
function solo_capitulos_contenido(array $capitulos): array
{
    return array_values(array_filter($capitulos, function (array $c): bool {
        return (bool) preg_match('/^Capítulo\s+\d+$/u', $c['chnum_raw']);
    }));
}

/** @param array<int, array{comment: ?string, html: string, chnum_raw: string}> $capitulos */
function solo_apendices(array $capitulos): array
{
    return array_values(array_filter($capitulos, function (array $c): bool {
        return (bool) preg_match('/^Anexo\s+[AB]$/u', $c['chnum_raw']);
    }));
}

/**
 * Reprefija el ch-num ("Capítulo 7" -> "Capítulo II.7") y el
 * comentario decorativo ("CAP. 7" -> "CAP. II.7") de un capítulo.
 *
 * @param array{comment: ?string, html: string, chnum_raw: string} $cap
 * @return array{comment: ?string, html: string, chnum_raw: string}
 */
function reprefijar_capitulo(array $cap, string $romano): array
{
    $cap['html'] = preg_replace(
        '/<div class="ch-num">Capítulo\s+(\d+)<\/div>/u',
        '<div class="ch-num">Capítulo ' . $romano . '.$1</div>',
        $cap['html'],
        1
    );

    if ($cap['comment'] !== null) {
        $cap['comment'] = preg_replace(
            '/CAP\.\s*(\d+)/u',
            'CAP. ' . $romano . '.$1',
            $cap['comment'],
            1
        );
    }

    return $cap;
}

/**
 * Genera el HTML del separador de Parte (página de portada de cada rol).
 */
function generar_part_divider(string $romano, string $rol, string $desc, string $bg, string $fg, int $numCaps): string
{
    // Inserta <wbr> en nombres de rol largos sin espacios (ej. "SuperAdministrador")
    // para que el h1 de 40pt pueda partir en 2 líneas sin desbordar la página.
    $rolH1 = str_replace('SuperAdministrador', 'Super<wbr>Administrador', $rol);

    return <<<HTML
<!-- ════════════════════════════════════════════════════
     PARTE {$romano} — {$rol}
════════════════════════════════════════════════════ -->
<div class="part-divider" style="--part-bg:{$bg}; --part-fg:{$fg};">
    <div class="part-divider-inner">
        <span class="part-divider-label">Parte {$romano}</span>
        <h1 class="part-divider-role">{$rolH1}</h1>
        <p class="part-divider-desc">{$desc}</p>
        <div class="part-divider-meta">{$numCaps} capítulos</div>
    </div>
</div>

HTML;
}

/**
 * Extrae el bloque de TOC "Capítulos" de un manual de rol y lo adapta:
 * - reemplaza el encabezado de sección por "Parte {romano} — {rol}"
 * - prefija el toc-num de las líneas de capítulo (no toc-sub) con "{romano}."
 * - reemplaza todos los toc-pg por "—"
 */
function extraer_toc_capitulos(string $html, string $romano, string $rol): string
{
    if (!preg_match(
        '/<div class="toc-section">Capítulos<\/div>\s*\n(.*?)(?=\s*<div class="toc-section">)/su',
        $html,
        $m
    )) {
        throw new RuntimeException("No se encontró la sección TOC 'Capítulos'");
    }

    $body = $m[1];

    // Prefijar toc-num de líneas de capítulo de nivel superior (sin "toc-sub")
    $body = preg_replace_callback(
        '/<div class="toc-line"><span class="toc-num">(\d+)\.<\/span>/u',
        function (array $mm) use ($romano): string {
            return '<div class="toc-line"><span class="toc-num">' . $romano . '.' . $mm[1] . '.</span>';
        },
        $body
    );

    // toc-pg -> "—" (placeholder, ver decisión confirmada en el plan)
    $body = preg_replace('/<span class="toc-pg">[^<]*<\/span>/u', '<span class="toc-pg">—</span>', $body);

    $header = '    <div class="toc-section">Parte ' . $romano . ' — ' . $rol . '</div>' . "\n";

    return $header . rtrim($body, "\n") . "\n";
}

/**
 * Genera la nueva .toc-page completa a partir de los bloques de
 * "Capítulos" ya adaptados de cada Parte.
 *
 * @param array<string, string> $tocCapitulos romano => bloque TOC adaptado
 */
function generar_toc(array $tocCapitulos): string
{
    $out = '<div class="toc-page">' . "\n";
    $out .= '    <p class="toc-title">Tabla de Contenido</p>' . "\n";
    $out .= '    <div class="toc-section">Sección Preliminar</div>' . "\n\n";
    $out .= '    <div class="toc-line"><span class="toc-num">—</span><span class="toc-text">Control del Documento</span><span class="toc-dots"></span><span class="toc-pg">—</span></div>' . "\n";
    $out .= '    <div class="toc-line"><span class="toc-num">—</span><span class="toc-text">Tabla de Contenido</span><span class="toc-dots"></span><span class="toc-pg">—</span></div>' . "\n\n";

    foreach ($tocCapitulos as $bloque) {
        $out .= $bloque . "\n";
    }

    $out .= '    <div class="toc-section">Anexos</div>' . "\n";
    $out .= '    <div class="toc-line"><span class="toc-num">A.</span><span class="toc-text">Glosario de Términos</span><span class="toc-dots"></span><span class="toc-pg">—</span></div>' . "\n";
    $out .= '    <div class="toc-line"><span class="toc-num">B.</span><span class="toc-text">Referencias y Normativa</span><span class="toc-dots"></span><span class="toc-pg">—</span></div>' . "\n";
    $out .= '</div>' . "\n";

    return $out;
}

/**
 * Inserta el CSS de .part-divider justo antes de </style> y añade
 * .part-divider al selector compartido de @media screen.
 *
 * Idempotente: si headFijo ya incluye la regla .part-divider (porque
 * proviene de un manual_maestro.html ya restructurado), no inyecta nada.
 */
function inyectar_css_part_divider(string $headFijo): string
{
    if (str_contains($headFijo, '.part-divider {')) {
        return $headFijo;
    }

    $css = <<<'CSS'

/* ═══════════════════════════════════════════════════════
   SEPARADOR DE PARTE
═══════════════════════════════════════════════════════ */
.part-divider {
    break-before: page;
    break-after: page;
    min-height: 24cm;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background: linear-gradient(160deg, #ffffff 0%, var(--part-bg, #f1f5f9) 140%);
}
.part-divider-inner {
    text-align: center;
    padding: 3cm 2cm;
    min-width: 0;
    max-width: 100%;
}
.part-divider-label {
    display: inline-block;
    background: var(--part-bg, #e2e8f0);
    color: var(--part-fg, #334155);
    font-size: 11pt;
    font-weight: 800;
    letter-spacing: 4px;
    text-transform: uppercase;
    padding: 8px 28px;
    border-radius: 30px;
    margin-bottom: 28px;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.part-divider-role {
    font-size: 40pt;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -1px;
    margin-bottom: 16px;
    line-height: 1.1;
    overflow-wrap: break-word;
    word-break: break-word;
}
.part-divider-desc {
    font-size: 12pt;
    color: #475569;
    max-width: 11cm;
    margin: 0 auto 24px;
    line-height: 1.6;
}
.part-divider-meta {
    display: inline-block;
    border-top: 2px solid var(--part-fg, #334155);
    padding-top: 10px;
    font-size: 9pt;
    font-weight: 700;
    color: var(--part-fg, #334155);
    text-transform: uppercase;
    letter-spacing: 2px;
}

CSS;

    $headFijo = str_replace('</style>', $css . "\n</style>", $headFijo);

    $headFijo = str_replace(
        '.ctrl-page, .toc-page, .chapter {',
        '.ctrl-page, .toc-page, .chapter, .part-divider {',
        $headFijo
    );

    return $headFijo;
}

function main(): void
{
    global $BASE;

    $PARTES = [
        'I' => [
            'file' => "$BASE/manual_superadministrador.html",
            'rol'  => 'SuperAdministrador',
            'bg'   => '#fef3c7',
            'fg'   => '#92400e',
            'desc' => 'Control total del sistema: supervisión, auditoría, permisos y configuración general.',
        ],
        'II' => [
            'file' => "$BASE/manual_administrador.html",
            'rol'  => 'Administrador',
            'bg'   => '#dbeafe',
            'fg'   => '#1e40af',
            'desc' => 'Coordinación operativa de pasantías: pasantes, períodos, usuarios, reportes y respaldos.',
        ],
        'III' => [
            'file' => "$BASE/manual_tutor.html",
            'rol'  => 'Tutor',
            'bg'   => '#dcfce7',
            'fg'   => '#15803d',
            'desc' => 'Seguimiento de los pasantes asignados: asistencias, puntualidad, actividades y evaluaciones.',
        ],
        'IV' => [
            'file' => "$BASE/manual_pasante.html",
            'rol'  => 'Pasante',
            'bg'   => '#f3e8ff',
            'fg'   => '#7c3aed',
            'desc' => 'Registro de asistencias, bitácora de actividades, analíticas, evaluaciones y constancia.',
        ],
    ];

    $maestroFile = "$BASE/manual_maestro.html";
    $outputFile  = "$BASE/manual_maestro.NUEVO.html";

    // ── 1) Head fijo: <head>+<style>+body+botón+cover+ctrl-page de maestro ──
    $maestroHtml = leer_normalizado($maestroFile);
    $ctrlStart = strpos($maestroHtml, '<div class="ctrl-page">');
    if ($ctrlStart === false) {
        throw new RuntimeException('No se encontró .ctrl-page en manual_maestro.html');
    }
    $ctrlEnd = fin_div_balanceado($maestroHtml, $ctrlStart);
    $headFijo = rtrim(substr($maestroHtml, 0, $ctrlEnd)) . "\n\n";

    // ── 2) Insertar CSS de .part-divider ──
    $headFijo = inyectar_css_part_divider($headFijo);

    // ── 3) Procesar cada Parte ──
    $bloquesPartes = [];
    $tocCapitulos = [];

    foreach ($PARTES as $romano => $cfg) {
        $src = leer_normalizado($cfg['file']);
        $todos = extraer_capitulos($src);
        $contenido = solo_capitulos_contenido($todos);
        $contenido = array_map(
            fn(array $c): array => reprefijar_capitulo($c, $romano),
            $contenido
        );

        $bloque = generar_part_divider($romano, $cfg['rol'], $cfg['desc'], $cfg['bg'], $cfg['fg'], count($contenido)) . "\n";
        foreach ($contenido as $cap) {
            if ($cap['comment'] !== null) {
                $bloque .= $cap['comment'] . "\n";
            }
            $bloque .= $cap['html'] . "\n\n";
        }
        $bloquesPartes[$romano] = $bloque;

        $tocCapitulos[$romano] = extraer_toc_capitulos($src, $romano, $cfg['rol']);

        fwrite(STDOUT, "Parte $romano ({$cfg['rol']}): " . count($contenido) . " capítulos\n");
    }

    // ── 4) Anexos compartidos (de superadministrador) ──
    $srcSuper = leer_normalizado($PARTES['I']['file']);
    $apendices = solo_apendices(extraer_capitulos($srcSuper));
    $bloqueApendices = '';
    foreach ($apendices as $ap) {
        if ($ap['comment'] !== null) {
            $bloqueApendices .= $ap['comment'] . "\n";
        }
        $bloqueApendices .= $ap['html'] . "\n\n";
    }
    fwrite(STDOUT, 'Anexos compartidos: ' . count($apendices) . "\n");

    // ── 5) Nueva TOC ──
    $toc = generar_toc($tocCapitulos);

    // ── 6) Ensamblaje final ──
    $tocComment = "<!-- ════════════════════════════════════════════════════\n     3. TABLA DE CONTENIDO\n════════════════════════════════════════════════════ -->\n";

    $final = $headFijo
        . $tocComment
        . $toc . "\n\n"
        . $bloquesPartes['I']
        . $bloquesPartes['II']
        . $bloquesPartes['III']
        . $bloquesPartes['IV']
        . $bloqueApendices
        . "</body>\n</html>\n";

    file_put_contents($outputFile, $final);
    fwrite(STDOUT, "OK -> $outputFile (" . strlen($final) . " bytes)\n");
}

main();
