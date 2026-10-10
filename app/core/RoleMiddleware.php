<?php
/**
 * RoleMiddleware - Middleware de Autorización por Rol
 * 
 * Funciones:
 * - Verificar que el usuario tenga el rol correcto
 * - Redirigir automáticamente si el rol no coincide
 * - Centralizar lógica de autorización
 */
class RoleMiddleware
{
    /**
     * Mapeo de roles a rutas
     */
    const ROLE_ROUTES = [
        0 => '/superadmin', // SuperAdministrador
        1 => '/admin',      // Administrador
        2 => '/tutor',      // Tutor
        3 => '/pasante'     // Pasante
    ];
    
    /**
     * Autoriza el acceso basado en un array de roles permitidos.
     * Si no está autorizado, usa Session::flash y redirige.
     * 
     * @param array $allowed_roles Ejemplo: [1] para Admin, [1,2] para Admin+Tutor
     * @return void
     */
    public static function authorize(array $allowed_roles): void
    {
        Session::start();
        $currentRoleId = Session::get('role_id');
        
        // El Super Administrador (rol 0) tiene acceso total automático
        if ((int)$currentRoleId === 0) return;

        // Normaliza a enteros estrictos (PHP 8 Best Practice)
        $allowed_roles = array_map('intval', $allowed_roles);

        if (!in_array((int)$currentRoleId, $allowed_roles, true)) {
            // Si es petición AJAX (Grid.js, fetch, etc.) → responder JSON con 403
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Acceso Denegado: No tienes permisos para ver este módulo'
                ]);
                exit;
            }
            
            // Mostrar página de error 403
            if (!headers_sent()) {
                http_response_code(403);
            }
            $view403 = defined('APPROOT')
                ? APPROOT . '/views/errors/403.php'
                : dirname(dirname(__FILE__)) . '/views/errors/403.php';
            require $view403;
            exit;
        }
    }
    
    /**
     * Requiere que el usuario tenga un rol específico
     * Si no lo tiene, redirige al dashboard correcto
     * 
     * @param int $expectedRoleId ID del rol esperado
     * @return void
     */
    public static function requireRole($expectedRoleId)
    {
        Session::start();
        
        $currentRoleId = Session::get('role_id');
        
        // Si el usuario no tiene el rol esperado, redirigir
        if ($currentRoleId != $expectedRoleId) {
            self::redirectToRoleDashboard($currentRoleId);
        }
    }
    
    /**
     * Redirige al dashboard correcto según el rol del usuario
     * 
     * @param int $roleId ID del rol
     * @return void
     */
    public static function redirectToRoleDashboard($roleId)
    {
        $route = self::ROLE_ROUTES[$roleId] ?? '/auth/login';
        header('Location: ' . URLROOT . $route);
        exit;
    }
    
    /**
     * Verifica si el usuario tiene un rol específico
     * 
     * @param int $roleId ID del rol a verificar
     * @return bool
     */
    public static function hasRole($roleId)
    {
        Session::start();
        return Session::get('role_id') == $roleId;
    }
    
    /**
     * Verifica si el usuario tiene alguno de los roles especificados
     * 
     * @param array $roleIds Array de IDs de roles permitidos
     * @return bool
     */
    public static function hasAnyRole(array $roleIds)
    {
        Session::start();
        $currentRoleId = Session::get('role_id');
        return in_array($currentRoleId, $roleIds);
    }
    
    /**
     * Obtiene el nombre del rol actual
     * 
     * @return string
     */
    public static function getRoleName()
    {
        Session::start();
        $roleId = Session::get('role_id');
        
        $roleNames = [
            0 => 'SuperAdministrador',
            1 => 'Administrador',
            2 => 'Tutor',
            3 => 'Pasante'
        ];
        
        return $roleNames[$roleId] ?? 'Invitado';
    }

    /**
     * Verifica si el usuario tiene permiso para una acción específica.
     * Implementa Data-Driven UI: consulta el cache de sesión cargado al login.
     *
     * SuperAdmin (rol 0) siempre retorna TRUE sin consultar nada.
     * Si la clave no existe en sesión, retorna TRUE (permisivo — mantiene comportamiento actual).
     *
     * @param  string $clave  Ej: 'ver_usuarios', 'crear_backup', 'exportar_reporte'
     * @return bool
     */
    public static function hasPermission(string $clave): bool
    {
        Session::start();
        $roleId = (int)Session::get('role_id');

        // SuperAdmin bypass total — nunca se le niega nada
        if ($roleId === 0) return true;

        // Leer desde cache de sesión (cargado en login por AuthController)
        $permisos = Session::get('permisos');

        // Si no hay cache (usuario legacy sin permisos cargados) → permitir
        if (empty($permisos) || !is_array($permisos)) return true;

        // Si la clave no existe en el cache → usar default permisivo
        if (!array_key_exists($clave, $permisos)) return true;

        return (bool)$permisos[$clave];
    }

    /**
     * Verifica si el usuario actual es SuperAdmin.
     *
     * @return bool
     */
    public static function isSuperAdmin(): bool
    {
        Session::start();
        return (int)Session::get('role_id') === 0;
    }
}
