<?php
// Vista mínima — el DaemonController opera principalmente via JSON endpoints:
//   POST /daemon/autoFill          → ejecuta el relleno global
//   GET  /daemon/autoFillPreview   → preview sin insertar
// Esta página solo confirma que el controlador está activo.
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Daemon SGP</title></head>
<body style="font-family:monospace;padding:2rem">
<h2>SGP — Daemon de Asistencias</h2>
<p>Endpoints disponibles (solo Administrador):</p>
<ul>
  <li><code>POST /daemon/autoFill</code> — Ejecutar relleno global (marca Ausente días sin registro)</li>
  <li><code>GET  /daemon/autoFillPreview</code> — Vista previa sin insertar</li>
</ul>
</body></html>
