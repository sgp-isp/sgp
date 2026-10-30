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
            $rawName = strtolower(trim($url[0]));
            $controllersDir = defined('APPROOT') ? (APPROOT . '/controllers') : '../app/controllers';

            $matchedFile = null;
            $matchedClass = null;

            // 1. Coincidencia directa exacta
            $directName = ucfirst($url[0]) . 'Controller';
            if (file_exists($controllersDir . '/' . $directName . '.php')) {
                $matchedFile = $controllersDir . '/' . $directName . '.php';
                $matchedClass = $directName;
            } else {
                // 2. Búsqueda insensible a mayúsculas/minúsculas en Linux (ej: superadmin -> SuperAdminController)
                $files = @scandir($controllersDir) ?: [];
                foreach ($files as $file) {
                    if (str_ends_with($file, '.php')) {
                        $baseClass = substr($file, 0, -4);
                        if (str_ends_with($baseClass, 'Controller')) {
                            $prefix = substr($baseClass, 0, -10);
                            if (strtolower($prefix) === $rawName || strtolower($baseClass) === strtolower($url[0])) {
                                $matchedFile = $controllersDir . '/' . $file;
                                $matchedClass = $baseClass;
                                break;
                            }
                        }
                    }
                }
            }

            if ($matchedFile && $matchedClass) {
                $this->controller = $matchedClass;
                unset($url[0]);
            } else {
                // Controlador no encontrado → 404
                $this->renderError(404);
                return;
            }
        }

        $controllersDir = defined('APPROOT') ? (APPROOT . '/controllers') : '../app/controllers';
        require_once $controllersDir . '/' . $this->controller . '.php';
        $this->controller = new $this->controller;

        // Check method (con soporte case-insensitive)
        if (isset($url[1])) {
            $rawMethod = strtolower(trim($url[1]));
            $methodFound = null;

            if (method_exists($this->controller, $url[1])) {
                $methodFound = $url[1];
            } else {
                $methods = get_class_methods($this->controller) ?: [];
                foreach ($methods as $m) {
                    if (strtolower($m) === $rawMethod) {
                        $methodFound = $m;
                        break;
                    }
                }
            }

            if ($methodFound) {
                $this->method = $methodFound;
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
