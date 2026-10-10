<?php
require_once __DIR__ . '/_error_helpers.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado — SGP</title>
    <link rel="icon" type="image/png" href="<?= $_url ?>/img/favicon.png">
    <link rel="stylesheet" href="<?= $_url ?>/css/tabler-icons.min.css">
    <link rel="stylesheet" href="<?= $_url ?>/css/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= $_url ?>/css/swal-bento-navy.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #172554 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Orbes decorativos de fondo */
        body::before {
            content: '';
            position: fixed;
            top: -120px; right: -120px;
            width: 400px; height: 400px;
            background: rgba(220,38,38,.12);
            border-radius: 50%;
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -80px; left: -80px;
            width: 300px; height: 300px;
            background: rgba(255,255,255,.04);
            border-radius: 50%;
            pointer-events: none;
        }

        /* Sobrescribir el popup de Swal para la 403 */
        .swal2-popup.swal-403-popup {
            border-radius: 20px;
            padding: 2.5rem 2rem 2rem;
            max-width: 420px;
            border: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 30px 60px -12px rgba(0,0,0,.45), 0 0 0 1px rgba(220,38,38,.15);
        }
        .swal2-popup.swal-403-popup .swal2-icon.swal2-error {
            border-color: #dc2626;
            color: #dc2626;
            width: 4.5rem;
            height: 4.5rem;
            margin: 0 auto 1.25rem;
        }
        .swal2-popup.swal-403-popup .swal2-icon.swal2-error [class^='swal2-x-mark-line'] {
            background-color: #dc2626;
        }
        .swal2-popup.swal-403-popup .swal2-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0D1424;
            padding: 0;
            margin-bottom: .5rem;
        }
        .swal2-popup.swal-403-popup .swal2-html-container {
            font-size: .875rem;
            color: #6b7280;
            margin: 0 0 .75rem;
        }
        .swal-403-hint {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 7px 14px;
            font-size: .78rem;
            color: #b91c1c;
            margin-top: .25rem;
        }
        .swal2-popup.swal-403-popup .swal2-actions {
            margin-top: 1.5rem;
            gap: 10px;
        }
        .swal-403-confirm {
            background: #dc2626 !important;
            color: white !important;
            border-radius: 12px !important;
            padding: 10px 22px !important;
            font-size: .875rem !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 14px rgba(220,38,38,.35) !important;
            border: none !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
        }
        .swal-403-confirm:hover {
            background: #b91c1c !important;
        }
        .swal-403-cancel {
            background: white !important;
            color: #374151 !important;
            border: 1.5px solid #e5e7eb !important;
            border-radius: 12px !important;
            padding: 10px 22px !important;
            font-size: .875rem !important;
            font-weight: 600 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
        }
        .swal-403-cancel:hover {
            background: #f9fafb !important;
            border-color: #d1d5db !important;
        }
    </style>
</head>
<body>
    <script src="<?= $_url ?>/js/sweetalert2.min.js"></script>
    <script>
        var _homeLink = <?= json_encode($_homeLink) ?>;

        Swal.fire({
            icon: 'error',
            title: 'Acceso Denegado',
            html: '<p style="line-height:1.65;margin:0 0 .5rem">No tienes permisos para acceder a este módulo.</p>'
                + '<div class="swal-403-hint"><i class="ti ti-shield-lock" style="font-size:.9rem"></i> Tu rol actual no tiene autorización para ver este recurso.</div>',
            confirmButtonText: '<i class="ti ti-layout-dashboard"></i> Mi Dashboard',
            showCancelButton: true,
            cancelButtonText: '<i class="ti ti-arrow-left"></i> Volver Atrás',
            reverseButtons: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            customClass: {
                popup:         'swal-403-popup',
                confirmButton: 'swal-403-confirm',
                cancelButton:  'swal-403-cancel',
                actions:       'swal2-actions'
            },
            buttonsStyling: false
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = _homeLink;
            } else {
                history.back();
            }
        });
    </script>
</body>
</html>
