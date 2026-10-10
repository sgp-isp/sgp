<?php
/* ══════════════════════════════════════════════════════
   Mi Constancia — Premium Bento Grid v2
   Variables: $pasante
   ══════════════════════════════════════════════════════ */
$pasante = $data['pasante'] ?? null;
$sinAsignar = in_array($pasante->estado_pasantia ?? '', ['Sin Asignar', 'Pendiente', '', null]);

$nombres = trim(($pasante->nombres ?? '') . ' ' . ($pasante->apellidos ?? ''));
$cedula = $pasante->cedula ?? '';
$depto = $pasante->departamento ?? '';
$tutor = $pasante->tutor_nombre ?? '';
$horas = (int) ($pasante->horas_meta ?? 0);
$fechaInicio = !empty($pasante->fecha_inicio_pasantia) ? date('d/m/Y', strtotime($pasante->fecha_inicio_pasantia)) : '';
$fechaFin = !empty($pasante->fecha_fin_estimada) ? date('d/m/Y', strtotime($pasante->fecha_fin_estimada)) : '';
$estado = $pasante->estado_pasantia ?? 'Sin Asignar';
?>
<style>
    /* ── keyframes ── */
    @keyframes conFadeUp {
        from {
            opacity: 0;
            transform: translateY(22px)
        }

        to {
            opacity: 1;
            transform: translateY(0)
        }
    }

    @keyframes conFloat {

        0%,
        100% {
            transform: translateY(0)
        }

        50% {
            transform: translateY(-6px)
        }
    }

    @keyframes conShimmer {
        0% {
            background-position: -200% 0
        }

        100% {
            background-position: 200% 0
        }
    }

    @keyframes conPulse {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: .6
        }
    }

    @keyframes conRotate {
        from {
            transform: rotate(0deg)
        }

        to {
            transform: rotate(360deg)
        }
    }

    /* ── layout ── */
    .con-wrap {
        display: flex;
        flex-direction: column;
        gap: 22px;
        animation: conFadeUp .5s ease both
    }

    /* ── hero ── */
    .con-hero {
        background: linear-gradient(135deg, #162660 0%, #1e3a8a 40%, #1d4ed8 75%, #2563eb 100%);
        border-radius: 24px;
        padding: 32px 36px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
        box-shadow: 0 8px 32px rgba(22, 38, 96, .4);
    }

    .con-hero::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -40px;
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(99, 102, 241, .2) 0%, transparent 70%);
        pointer-events: none;
    }

    .con-hero::after {
        content: '';
        position: absolute;
        bottom: -40px;
        left: 20%;
        width: 180px;
        height: 180px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, .06) 0%, transparent 70%);
        pointer-events: none;
    }

    .con-hero-seal {
        background: rgba(255, 255, 255, .12);
        backdrop-filter: blur(8px);
        border: 2px solid rgba(255, 255, 255, .2);
        border-radius: 50%;
        width: 74px;
        height: 74px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        z-index: 1;
        animation: conFloat 4s ease-in-out infinite;
    }

    /* ── bento grid principal ── */
    .con-bento {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 18px;
        align-items: start
    }

    @media(max-width:900px) {
        .con-bento {
            grid-template-columns: 1fr
        }
    }

    /* ── documento card ── */
    .con-doc {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 4px 28px rgba(22, 38, 96, .1);
        border: 1px solid #e2e8f0;
        overflow: hidden;
        animation: conFadeUp .55s ease both;
    }

    .con-doc-header {
        background: linear-gradient(135deg, #162660 0%, #1e3a8a 60%, #2563eb 100%);
        padding: 24px 28px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .con-doc-body {
        padding: 28px
    }

    /* ── preview de texto oficial ── */
    .con-preview {
        background: #f8fafc;
        border-radius: 12px;
        padding: 18px 20px;
        border-left: 4px solid #162660;
        margin-bottom: 24px;
        font-size: .875rem;
        color: #334155;
        line-height: 1.85;
    }

    /* ── data grid ── */
    .con-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden
    }

    @media(max-width:540px) {
        .con-grid {
            grid-template-columns: 1fr
        }
    }

    .con-cell {
        padding: 14px 18px;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0
    }

    .con-cell:nth-child(2n) {
        border-right: none
    }

    .con-cell.full {
        grid-column: 1/-1;
        border-right: none
    }

    .con-cell:nth-last-child(-n+2):not(.full) {
        border-bottom: none
    }

    .con-cell:last-child {
        border-bottom: none
    }

    @media(max-width:540px) {
        .con-cell {
            border-right: none;
        }

        .con-cell:not(:last-child) {
            border-bottom: 1px solid #e2e8f0
        }
    }

    .con-cell-label {
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #94a3b8;
        margin-bottom: 4px
    }

    .con-cell-val {
        font-size: .9rem;
        font-weight: 600;
        color: #1e293b
    }

    .con-cell-empty {
        color: #cbd5e1;
        font-style: italic;
        font-weight: 400
    }

    /* ── sidebar ── */
    .con-side {
        display: flex;
        flex-direction: column;
        gap: 14px
    }

    /* ── status card ── */
    .con-status {
        background: #fff;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 2px 14px rgba(0, 0, 0, .06);
        border: 1px solid #f1f5f9;
        animation: conFadeUp .6s ease both;
    }

    .con-status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 6px;
    }

    /* ── info items ── */
    .con-info-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #f1f5f9;
    }

    .con-info-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
    }

    /* ── acciones card ── */
    .con-action {
        background: #fff;
        border-radius: 18px;
        padding: 22px;
        box-shadow: 0 2px 14px rgba(0, 0, 0, .06);
        border: 1px solid #f1f5f9;
        display: flex;
        flex-direction: column;
        gap: 14px;
        animation: conFadeUp .65s ease both;
    }

    .con-btn-primary {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: linear-gradient(135deg, #162660, #2563eb);
        color: #fff;
        padding: 14px 22px;
        border-radius: 14px;
        font-weight: 700;
        font-size: .92rem;
        text-decoration: none;
        border: none;
        cursor: pointer;
        width: 100%;
        box-shadow: 0 4px 16px rgba(22, 38, 96, .25);
        transition: transform .15s, box-shadow .15s;
    }

    .con-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(22, 38, 96, .3);
        color: #fff
    }

    .con-btn-disabled {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: #f1f5f9;
        color: #94a3b8;
        padding: 14px 22px;
        border-radius: 14px;
        font-weight: 700;
        font-size: .92rem;
        border: none;
        cursor: not-allowed;
        width: 100%;
    }

    /* ── locked overlay ── */
    .con-locked {
        text-align: center;
        padding: 52px 20px
    }

    .con-locked-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 1.8rem;
        color: #94a3b8;
    }

    /* ── warning banner ── */
    .con-warning {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-left: 4px solid #f59e0b;
        border-radius: 14px;
        padding: 16px 20px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        animation: conFadeUp .5s ease both;
    }

    /* ── note ── */
    .con-note {
        background: rgba(22, 38, 96, .04);
        border: 1px solid rgba(22, 38, 96, .12);
        border-left: 4px solid #162660;
        border-radius: 12px;
        padding: 14px 18px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        animation: conFadeUp .7s ease both;
    }
</style>

<div class="con-wrap">

    <!-- ══════════════════════ HERO ══════════════════════ -->
    <div class="con-hero">
        <div class="con-hero-seal">
            <i class="ti ti-file-certificate" style="font-size:2rem;color:#fff;"></i>
        </div>
        <div style="z-index:1;flex:1;">
            <div
                style="display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.9);border-radius:999px;padding:4px 12px;font-size:.73rem;font-weight:700;letter-spacing:.4px;text-transform:uppercase;margin-bottom:8px;">
                <i class="ti ti-building-community" style="font-size:.73rem;"></i> Dirección de Telemática
            </div>
            <h1
                style="color:#fff;font-size:1.65rem;font-weight:800;margin:0 0 4px;text-shadow:0 2px 8px rgba(0,0,0,.15);">
                Mi Constancia de Pasantía
            </h1>
            <p style="color:rgba(255,255,255,.72);margin:0;font-size:.87rem;">
                Instituto de Salud Pública del Estado Bolívar &nbsp;·&nbsp;
                <?php if (!$sinAsignar): ?>
                    <span style="color:#86efac;">
                        <i class="ti ti-circle-check-filled" style="font-size:.8rem;"></i>
                        Disponible para descarga
                    </span>
                <?php else: ?>
                    <span style="color:#fca5a5;">
                        <i class="ti ti-lock" style="font-size:.8rem;"></i>
                        Pendiente de asignación
                    </span>
                <?php endif; ?>
            </p>
        </div>
        <div
            style="z-index:1;text-align:center;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:16px;padding:14px 20px;flex-shrink:0;">
            <div
                style="font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:rgba(255,255,255,.6);margin-bottom:6px;">
                Consultado</div>
            <div style="font-size:.85rem;font-weight:700;color:#fff;"><?= date('d/m/Y') ?></div>
            <div style="font-size:.75rem;color:rgba(255,255,255,.6);"><?= date('H:i') ?> hrs</div>
        </div>
    </div>

    <?php if ($sinAsignar): ?>
        <!-- ══ AVISO ══ -->
        <div class="con-warning">
            <i class="ti ti-alert-triangle" style="color:#f59e0b;font-size:1.2rem;flex-shrink:0;margin-top:2px;"></i>
            <div>
                <p style="font-weight:700;color:#92400e;margin:0 0 3px;font-size:.9rem;">Constancia no disponible aún</p>
                <p style="color:#78350f;font-size:.82rem;margin:0;line-height:1.6;">
                    Aún no tienes departamento ni tutor asignados. El documento estará disponible una vez que el
                    administrador complete tu asignación de pasantía.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <!-- ══════════════════════ BENTO MAIN ══════════════════════ -->
    <div class="con-bento">

        <!-- ── DOCUMENTO CARD (columna grande) ── -->
        <div class="con-doc">
            <div class="con-doc-header">
                <div
                    style="width:52px;height:52px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="ti ti-building-community" style="font-size:1.5rem;color:#fff;"></i>
                </div>
                <div>
                    <p
                        style="color:rgba(255,255,255,.55);font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin:0 0 2px;">
                        República Bolivariana de Venezuela</p>
                    <h2 style="color:#fff;font-size:1.05rem;font-weight:800;margin:0 0 2px;">Constancia de Pasantía</h2>
                    <p style="color:rgba(255,255,255,.5);font-size:.74rem;margin:0;">Instituto de Salud Pública del
                        Estado Bolívar — Dir. Telemática</p>
                </div>
            </div>

            <div class="con-doc-body">
                <?php if ($sinAsignar): ?>
                    <div class="con-locked">
                        <div class="con-locked-icon"><i class="ti ti-lock"></i></div>
                        <p style="font-weight:700;color:#94a3b8;font-size:1rem;margin:0 0 6px;">Documento bloqueado</p>
                        <p style="color:#cbd5e1;font-size:.84rem;margin:0;max-width:340px;margin:0 auto;line-height:1.6;">
                            Los datos aparecerán aquí cuando tengas una asignación activa en el sistema.
                        </p>
                    </div>

                <?php else: ?>
                    <!-- ── Preview oficial ── -->
                    <div class="con-preview">
                        Quien suscribe, hace constar que <strong><?= htmlspecialchars($nombres) ?></strong>,
                        titular de la C.I. <strong>V-<?= htmlspecialchars($cedula) ?></strong>,
                        se encuentra realizando pasantías en el Departamento de
                        <strong><?= htmlspecialchars($depto) ?></strong>,
                        con una carga horaria de <strong><?= $horas > 0 ? $horas . ' horas académicas' : '—' ?></strong>,
                        bajo la supervisión del tutor institucional
                        <strong><?= htmlspecialchars($tutor) ?></strong>.
                        Constancia que se expide a petición del interesado en Ciudad Bolívar a los <?= date('d') ?> días
                        del mes de <?= strftime('%B', time()) ?? date('F') ?> de <?= date('Y') ?>.
                    </div>

                    <!-- ── Grid de datos ── -->
                    <div class="con-grid">
                        <?php
                        function conCell(string $lbl, string $val, bool $full = false): void
                        {
                            $hasVal = $val !== '';
                            $cls = $full ? 'con-cell full' : 'con-cell';
                            $valCls = $hasVal ? 'con-cell-val' : 'con-cell-val con-cell-empty';
                            $text = $hasVal ? htmlspecialchars($val) : '— pendiente';
                            echo "<div class='{$cls}'>";
                            echo "<div class='con-cell-label'>{$lbl}</div>";
                            echo "<div class='{$valCls}'>{$text}</div>";
                            echo "</div>";
                        }
                        conCell('Nombre Completo', $nombres);
                        conCell('Cédula de Identidad', 'V-' . $cedula);
                        conCell('Departamento', $depto);
                        conCell('Tutor Asignado', $tutor);
                        conCell('Fecha de Inicio', $fechaInicio);
                        conCell('Fecha Fin Estimada', $fechaFin);
                        conCell('Horas Requeridas', $horas > 0 ? $horas . ' horas académicas' : '', true);
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── SIDEBAR ── -->
        <div class="con-side">

            <!-- Estado -->
            <div class="con-status">
                <div
                    style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:14px;">
                    <i class="ti ti-info-circle" style="margin-right:4px;"></i>Estado del Documento
                </div>
                <?php
                $statusMap = [
                    'Sin Asignar' => ['#ef4444', 'Pendiente de asignación', 'ti-clock'],
                    'Pendiente' => ['#f59e0b', 'En proceso de asignación', 'ti-hourglass'],
                    'Activo' => ['#10b981', 'Pasantía activa', 'ti-circle-check-filled'],
                    'Completado' => ['#6366f1', 'Pasantía completada', 'ti-award'],
                ];
                $sm = $statusMap[$estado] ?? ['#94a3b8', $estado, 'ti-question-mark'];
                ?>
                <div
                    style="display:flex;align-items:center;gap:10px;padding:14px;background:<?= $sm[0] ?>10;border:1px solid <?= $sm[0] ?>30;border-radius:12px;">
                    <span class="con-status-dot"
                        style="background:<?= $sm[0] ?>;box-shadow:0 0 0 3px <?= $sm[0] ?>25;"></span>
                    <div>
                        <div style="font-size:.82rem;font-weight:700;color:<?= $sm[0] ?>"><?= $sm[1] ?></div>
                        <div style="font-size:.72rem;color:#94a3b8;margin-top:1px;">Estado:
                            <?= htmlspecialchars($estado) ?>
                        </div>
                    </div>
                </div>

                <!-- Info items -->
                <div style="display:flex;flex-direction:column;gap:8px;margin-top:14px;">
                    <div class="con-info-item">
                        <div class="con-info-icon" style="background:#eff6ff;color:#2563eb;"><i class="ti ti-user"></i>
                        </div>
                        <div>
                            <div style="font-size:.68rem;color:#94a3b8;font-weight:600;">Pasante</div>
                            <div style="font-size:.82rem;font-weight:700;color:#1e293b;">
                                <?= $nombres ? htmlspecialchars($nombres) : '—' ?>
                            </div>
                        </div>
                    </div>
                    <div class="con-info-item">
                        <div class="con-info-icon" style="background:#f0fdf4;color:#16a34a;"><i
                                class="ti ti-building"></i></div>
                        <div>
                            <div style="font-size:.68rem;color:#94a3b8;font-weight:600;">Departamento</div>
                            <div style="font-size:.82rem;font-weight:700;color:#1e293b;">
                                <?= $depto ? htmlspecialchars($depto) : 'No asignado' ?>
                            </div>
                        </div>
                    </div>
                    <div class="con-info-item">
                        <div class="con-info-icon" style="background:#fdf4ff;color:#9333ea;"><i
                                class="ti ti-user-check"></i></div>
                        <div>
                            <div style="font-size:.68rem;color:#94a3b8;font-weight:600;">Tutor</div>
                            <div style="font-size:.82rem;font-weight:700;color:#1e293b;">
                                <?= $tutor ? htmlspecialchars($tutor) : 'No asignado' ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($horas > 0): ?>
                        <div class="con-info-item">
                            <div class="con-info-icon" style="background:#fff7ed;color:#ea580c;"><i class="ti ti-clock"></i>
                            </div>
                            <div>
                                <div style="font-size:.68rem;color:#94a3b8;font-weight:600;">Carga horaria</div>
                                <div style="font-size:.82rem;font-weight:700;color:#1e293b;"><?= $horas ?> horas académicas
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Acción descarga -->
            <div class="con-action">
                <div
                    style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;">
                    <i class="ti ti-download" style="margin-right:4px;"></i>Descargar
                </div>

                <?php if (!$sinAsignar): ?>
                    <a href="<?= URLROOT ?>/pasante/descargarConstancia" target="_blank" class="con-btn-primary">
                        <i class="ti ti-file-type-pdf" style="font-size:1.1rem;"></i>
                        Descargar PDF
                    </a>
                    <p style="margin:0;font-size:.75rem;color:#94a3b8;text-align:center;line-height:1.5;">
                        Se abrirá en una nueva pestaña.<br>Para su validez debe ser firmada y sellada.
                    </p>
                <?php else: ?>
                    <button class="con-btn-disabled" disabled>
                        <i class="ti ti-lock" style="font-size:1rem;"></i>
                        No disponible
                    </button>
                    <p style="margin:0;font-size:.75rem;color:#94a3b8;text-align:center;line-height:1.5;">
                        Estará disponible cuando el administrador complete tu asignación.
                    </p>
                <?php endif; ?>
            </div>

            <!-- Vigencia / nota legal -->
            <div
                style="background:#f8fafc;border-radius:14px;padding:16px 18px;border:1px solid #f1f5f9;animation:conFadeUp .7s ease both;">
                <div
                    style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#94a3b8;margin-bottom:10px;">
                    <i class="ti ti-shield-check" style="margin-right:4px;"></i>Validez Legal
                </div>
                <ul
                    style="margin:0;padding:0 0 0 16px;font-size:.78rem;color:#475569;line-height:2;list-style:none;padding:0;display:flex;flex-direction:column;gap:6px;">
                    <li style="display:flex;align-items:flex-start;gap:8px;">
                        <i class="ti ti-point-filled"
                            style="color:#162660;margin-top:4px;font-size:.6rem;flex-shrink:0;"></i>
                        <span>Debe llevar firma y sello oficial para su validez.</span>
                    </li>
                    <li style="display:flex;align-items:flex-start;gap:8px;">
                        <i class="ti ti-point-filled"
                            style="color:#162660;margin-top:4px;font-size:.6rem;flex-shrink:0;"></i>
                        <span>Emitida por la Dirección de Telemática — ISPEB.</span>
                    </li>
                    <li style="display:flex;align-items:flex-start;gap:8px;">
                        <i class="ti ti-point-filled"
                            style="color:#162660;margin-top:4px;font-size:.6rem;flex-shrink:0;"></i>
                        <span>Documento para uso académico e institucional.</span>
                    </li>
                </ul>
            </div>

        </div><!-- /con-side -->
    </div><!-- /con-bento -->

    <!-- ══ NOTA LEGAL (pie) ══ -->
    <?php if (!$sinAsignar): ?>
        <div class="con-note">
            <i class="ti ti-info-circle" style="color:#162660;font-size:1rem;margin-top:2px;flex-shrink:0;"></i>
            <p style="margin:0;font-size:.81rem;color:#334155;line-height:1.6;">
                <strong>Importante:</strong> Para su validez oficial este documento debe ser firmado y sellado por la
                Coordinación de Pasantías de la Dirección de Telemática del Instituto de Salud Pública del Estado Bolívar.
            </p>
        </div>
    <?php endif; ?>

</div><!-- /con-wrap -->