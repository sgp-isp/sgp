<?php
class Validator
{
    // Sanitize String
    public static function sanitizeString($data) {
        $data = trim($data);
        $data = stripslashes($data);
        // [FIX-P1] Eliminado htmlspecialchars() del input — anti-patrón que corrompe datos en BD.
        // El escape XSS debe aplicarse en la SALIDA (vistas), no en la entrada.
        // Todas las vistas deben usar htmlspecialchars($var, ENT_QUOTES, 'UTF-8') al hacer echo.
        return $data;
    }

    // Sanitize Email
    public static function sanitizeEmail($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    // Validate Email format
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // Check if required fields are present
    public static function required($fields, $data) {
        foreach ($fields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        return true;
    }

    // Get cleaned POST value
    public static function post($key)
    {
        return isset($_POST[$key]) ? self::sanitizeString($_POST[$key]) : '';
    }

    // Get cleaned Email from POST
    public static function email($key)
    {
        return isset($_POST[$key]) ? self::sanitizeEmail($_POST[$key]) : '';
    }
}
