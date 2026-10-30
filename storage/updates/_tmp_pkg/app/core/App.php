<?php
class App
{
    protected $controller = 'AuthController'; // Default controller
    protected $method = 'index';
    protected $params = [];

    public function __construct()
    {
        $url = $this->parseUrl();

        // Check controller first
        if (isset($url[0])) {
            // Buscar controlador con sufijo estándar "Controller"
            $controllerName = ucfirst($url[0]) . 'Controller';
            if (file_exists('../app/controllers/' . $controllerName . '.php')) {
                $this->controller = $controllerName;
                unset($url[0]);
            } else {
                // Controlador no encontrado → 404
                $this->renderError(404);
                return;
            }
        }

        require_once '../app/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // Check method
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            } else {
                // Método no encontrado → 404
                $this->renderError(404);
                return;
            }
        }

        // Params
        $this->params = $url ? array_values($url) : [];

        // Call method with params
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    public function parseUrl()
    {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }

    /**
     * Renderiza una página de error HTTP y termina la ejecución.
     * @param int $code  Código HTTP (404, 403, 500…)
     */
    public static function renderError(int $code): void
    {
        if (!headers_sent()) {
            http_response_code($code);
        }
        $view = APPROOT . '/views/errors/' . $code . '.php';
        if (!file_exists($view)) {
            $view = APPROOT . '/views/errors/error.php';
        }
        require $view;
        exit;
    }
}
