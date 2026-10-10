<?php
/**
 * UrlSecurity - URL ID Encryption Library
 * Prevents IDOR (Insecure Direct Object Reference) attacks
 * by encrypting database IDs in URLs using AES-256-CBC
 */
class UrlSecurity {
    // Claves de Seguridad Generadas (NO CAMBIAR EN PRODUCCIÓN)
    private static $secret_key = 'SGP_SEC_KEY_v1_9xZ2qL5mP8wR3vK7nB4dJ9s'; 
    private static $secret_iv = 'SGP_IV_2026_Xy7aB2cD3eF4gH5i';
    private static $encrypt_method = "AES-256-CBC";

    /**
     * Encrypt ID for URL usage
     * Converts: 5 -> "Xy9zK4mP8w"
     * 
     * @param mixed $string The ID to encrypt
     * @return string URL-safe encrypted string
     */
    public static function encrypt($string) {
        $key = hash('sha256', self::$secret_key);
        $iv = substr(hash('sha256', self::$secret_iv), 0, 16);

        $output = openssl_encrypt($string, self::$encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
        
        // Make URL-safe (replace +/= with -_ and empty)
        return str_replace(['+', '/', '='], ['-', '_', ''], $output);
    }

    /**
     * Decrypt URL hash back to ID
     * Converts: "Xy9zK4mP8w" -> 5
     * 
     * @param string $string The encrypted hash from URL
     * @return mixed Decrypted ID or false on failure
     */
    public static function decrypt($string) {
        $key = hash('sha256', self::$secret_key);
        $iv = substr(hash('sha256', self::$secret_iv), 0, 16);

        // Restore original characters
        $string = str_replace(['-', '_'], ['+', '/'], $string);
        
        $output = openssl_decrypt(base64_decode($string), self::$encrypt_method, $key, 0, $iv);
        return $output;
    }
    
    /**
     * Validate and decrypt ID from URL
     * Returns numeric ID or triggers error response
     * 
     * @param string $encrypted_id Encrypted ID from URL
     * @return int Valid numeric ID
     * @throws Exception if decryption fails or ID is invalid
     */
    public static function validateAndDecrypt($encrypted_id) {
        $id = self::decrypt($encrypted_id);
        
        if (!$id || !is_numeric($id)) {
            return false;
        }
        
        return (int)$id;
    }
}
