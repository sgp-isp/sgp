<?php
/**
 * CsrfHelper — Wrapper de CSRF para SGP
 *
 * Delega completamente a los métodos ya existentes en Session.php
 * (generateCsrfToken / validateCsrfToken). No duplica lógica.
 *
 * Uso en formularios: <?php echo CsrfHelper::field(); ?>
 * Uso en <head>:      <?php echo CsrfHelper::meta(); ?>
 * Uso en controlador: CsrfHelper::verify();
 *
 * SGP-FIX-v2 [3] aplicado
 */
class CsrfHelper
{
    /**
     * Genera o recupera el token CSRF de la sesión actual.
     */
    public static function generate(): string
    {
        return Session::generateCsrfToken();
    }

    /**
     * Verifica el token CSRF recibido (POST body o header HTTP).
     * Termina la ejecución con HTTP 403 si el token es inválido.
     */
    public static function verify(): void
    {
        // [FIX-CS-4] Acepta las 3 convenciones de nombre usadas históricamente en la
        // app ('_csrf' en forms nuevos, 'csrf_token' en forms/AJAX más antiguos, y el
        // header X-CSRF-TOKEN en fetch()) para poder centralizar la verificación sin
        // tener que renombrar el campo en cada vista existente.
        $token = $_POST['_csrf']
            ?? $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';

        if (empty($token) || !Session::validateCsrfToken($token)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Token de seguridad inválido. Recarga la página e intenta de nuevo.'
            ]);
            exit;
        }
    }

    /**
     * Retorna un campo hidden HTML con el token CSRF.
     * Insertar dentro de cualquier <form method="POST">.
     */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::generate() . '">';
    }

    /**
     * Retorna el meta tag CSRF para ser leído desde JavaScript.
     * Insertar en el <head> del layout.
     */
    public static function meta(): string
    {
        return '<meta name="csrf-token" content="' . self::generate() . '">';
    }
}
