<?php
/**
 * sync_cloudflare_tunnel.php
 * Sincroniza la URL generada por Cloudflare en segundo plano con la base de datos de SGP
 */
$maxAttempts = 15;
$hostname = null;

for ($i = 0; $i < $maxAttempts; $i++) {
    sleep(1);
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $resp = @file_get_contents('http://127.0.0.1:20242/quicktunnel', false, $ctx);
    if ($resp) {
        $data = json_decode($resp, true);
        if (!empty($data['hostname'])) {
            $hostname = trim($data['hostname']);
            break;
        }
    }
}

if (!$hostname) {
    echo "ERROR: No se pudo obtener la URL de Cloudflare. Verifica que cloudflared este ejecutandose.\n";
    exit(1);
}

$fullUrl = "https://{$hostname}/SGP";

try {
    $db = new PDO('mysql:host=localhost;dbname=proyecto_sgp;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $stmt = $db->prepare("UPDATE configuracion SET valor = :val WHERE clave = 'url_publica'");
    $stmt->execute([':val' => $fullUrl]);
} catch (Exception $e) {
    // Si la BD falla, al menos tenemos la URL
}

echo "OK|{$fullUrl}|{$hostname}\n";
exit(0);
