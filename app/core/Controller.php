<?php
abstract class Controller
{
    // Load model
    protected function model($model)
    {
        // Require model file
        // Support both naming conventions: User.php or UserModel.php
        if (file_exists('../app/models/' . $model . 'Model.php')) {
            require_once '../app/models/' . $model . 'Model.php';
            $className = $model . 'Model';
        } elseif (file_exists('../app/models/' . $model . '.php')) {
            require_once '../app/models/' . $model . '.php';
            $className = $model;
        } else {
            die("Model {$model} does not exist");
        }

        // SGP-FIX-v2 [5/2.1] aplicado — Singleton en lugar de new Database()
        $db = Database::getInstance();

        // Instantiate model with DB connection
        return new $className($db);
    }

    /**
     * Verifica el token CSRF. Termina con HTTP 403 si es inválido.
     * SGP-FIX-v2 [5] aplicado
     */
    protected function verifyCsrf(): void
    {
        CsrfHelper::verify();
    }

    // Load view with optional layout support
    protected function view($view, $data = [], $useMasterLayout = true)
    {
        // Check for view file
        $viewPath = defined('APPROOT') ? (APPROOT . '/views/' . $view . '.php') : ('../app/views/' . $view . '.php');

        if (!file_exists($viewPath)) {
            die("View {$view} does not exist: {$viewPath}");
        }

        // Extract data for the view
        extract($data);

        // ── PJAX: Detectar si la petición viene del módulo sgp-pjax.js ──
        // Si sí, renderizar solo el contenido (sin layout completo).
        // Esto evita re-descargar CSS/JS/sidebar/topbar en cada navegación.
        $isPjax = isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX'] === '1';

        if ($isPjax && $useMasterLayout) {
            // Inyectar el title codificado para que el JS lo use
            $pageTitle = $title ?? 'SGP';
            // [SGP-FIX-PJAX-TITLE] urlencode() codifica los espacios como "+",
            // pero decodeURIComponent() en JS NO convierte "+" en espacio (solo
            // decodifica %XX) — por eso el título de la pestaña salía con los
            // espacios literales como "+" (ej. "Asistencias+—+Diaria"). Con
            // rawurlencode() los espacios van como %20, que sí decodifica bien.
            echo '<span data-pjax-title="' . rawurlencode($pageTitle) . '" style="display:none;"></span>';
            // Renderizar solo el contenido de la vista
            require $viewPath;
            return;
        }

        if ($useMasterLayout) {
            // Petición normal → layout completo
            $content = $viewPath;
            require '../app/views/layouts/main_layout.php';
        } else {
            // Vista standalone (Login, Wizard, etc.)
            require $viewPath;
        }
    }

    // Helper for redirection
    protected function redirect($path) {
        $path = ltrim($path, '/');
        // Use URLROOT constant defined in index.php
        header("Location: " . URLROOT . '/' . $path);
        exit();
    }
}
