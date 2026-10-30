<?php
/**
 * CacheControl - Helper para Control de Caché
 * 
 * Previene que el navegador guarde en caché páginas protegidas
 * Esto evita que el usuario pueda usar el botón "Atrás" para ver
 * páginas privadas después de cerrar sesión
 */
class CacheControl
{
    /**
     * Enviar headers anti-caché
     * Debe llamarse ANTES de cualquier salida HTML
     * 
     * @return void
     */
    public static function noCache()
    {
        // HTTP 1.1
        header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
        
        // HTTP 1.0
        header("Pragma: no-cache");
        
        // Proxies
        header("Expires: 0");
        
        // Adicional: prevenir almacenamiento en caché del navegador
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    }
    
    /**
     * Enviar headers para permitir caché (páginas públicas)
     * 
     * @param int $seconds Tiempo en segundos para cachear
     * @return void
     */
    public static function allowCache($seconds = 3600)
    {
        header("Cache-Control: public, max-age=" . $seconds);
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $seconds) . " GMT");
    }
}
