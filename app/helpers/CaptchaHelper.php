<?php
/**
 * CAPTCHA Helper
 * Genera códigos CAPTCHA aleatorios de 5 caracteres
 */
class CaptchaHelper {
    
    /**
     * Genera un código CAPTCHA aleatorio de 5 caracteres
     * Excluye caracteres confusos: 0, O, I, l, 1
     * 
     * @return string Código CAPTCHA de 5 caracteres
     */
    public static function generateCode() {
        // Caracteres permitidos (sin confusos)
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        $length = 5;
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $code;
    }
    
    /**
     * Almacena el código CAPTCHA en la sesión
     * 
     * @param string $code Código a almacenar
     */
    public static function storeInSession($code) {
        Session::start();
        // Limpiar valor anterior antes de asignar el nuevo para evitar desfase de sesión
        unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);
        $_SESSION['captcha_code'] = strtoupper($code);
        $_SESSION['captcha_time'] = time();
    }
    
    /**
     * Valida el código CAPTCHA ingresado por el usuario
     * 
     * @param string $userInput Código ingresado por el usuario
     * @return bool True si es válido, False si no
     */
    public static function validate($userInput) {
        Session::start();
        
        // Verificar que existe código en sesión
        if (!isset($_SESSION['captcha_code'])) {
            return false;
        }
        
        // Verificar que no haya expirado (5 minutos)
        if (isset($_SESSION['captcha_time']) && (time() - $_SESSION['captcha_time']) > 300) {
            self::clear();
            return false;
        }
        
        // Comparar códigos (case-insensitive)
        $isValid = strtoupper(trim($userInput)) === $_SESSION['captcha_code'];
        
        // Limpiar sesión después de validar
        self::clear();
        
        return $isValid;
    }
    
    /**
     * Limpia el código CAPTCHA de la sesión
     */
    public static function clear() {
        Session::start();
        unset($_SESSION['captcha_code']);
        unset($_SESSION['captcha_time']);
    }
}
