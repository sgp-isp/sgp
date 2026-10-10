<?php
class DashboardController extends Controller {
    
    public function __construct() {
        // Aplicar Cache Control (prevenir botón atrás)
        CacheControl::noCache();
        
        // Verificar autenticación (con soporte AJAX)
        AuthMiddleware::require();
        
        // SISTEMA "LA JAULA" - Verificar estado del usuario
        AuthMiddleware::verificarEstado();
    }
    
    public function index() {
        Session::start();
        
        // Verificar que el usuario esté autenticado
        if (!Session::get('user_id')) {
            $this->redirect('/auth/login');
            return;
        }
        
        // Redirigir según rol
        $roleId = Session::get('role_id');
        $routes = [
            0 => '/superadmin',       // SuperAdministrador → SuperAdminController::index()
            1 => '/admin',            // Administrador → AdminController::index()
            2 => '/tutor',            // Tutor → TutorController::index()
            3 => '/pasante/dashboard' // Pasante → PasanteController::dashboard() [FIX: Evita bucle infinito]
        ];
        
        $this->redirect($routes[$roleId] ?? '/auth/login');
    }
}
