<?php
require_once APPROOT . '/helpers/CaptchaHelper.php';

/**
 * CAPTCHA Controller
 * Genera imágenes CAPTCHA con diseño institucional
 */
class CaptchaController extends Controller {
    
    /**
     * Genera y muestra la imagen CAPTCHA
     */
    public function generate() {
        // Generar código aleatorio
        $code = CaptchaHelper::generateCode();
        
        // Almacenar en sesión
        CaptchaHelper::storeInSession($code);
        
        // Dimensiones de la imagen (optimizadas para diseño compacto)
        $width = 180;
        $height = 60;
        
        // Crear imagen
        $image = imagecreatetruecolor($width, $height);
        
        // Colores institucionales
        $bgStart = imagecolorallocate($image, 22, 38, 96);      // #162660
        $bgEnd = imagecolorallocate($image, 13, 26, 61);        // #0d1a3d
        $textColor = imagecolorallocate($image, 241, 228, 209); // #F1E4D1
        $lineColor = imagecolorallocate($image, 241, 228, 209); // #F1E4D1 con alpha
        
        // Crear gradiente de fondo
        for ($i = 0; $i < $height; $i++) {
            $ratio = $i / $height;
            $r = (int)(22 + ($ratio * (13 - 22)));
            $g = (int)(38 + ($ratio * (26 - 38)));
            $b = (int)(96 + ($ratio * (61 - 96)));
            $color = imagecolorallocate($image, $r, $g, $b);
            imagefilledrectangle($image, 0, $i, $width, $i + 1, $color);
        }
        
        // Agregar líneas de ruido
        for ($i = 0; $i < 5; $i++) {
            $lineAlpha = imagecolorallocatealpha($image, 241, 228, 209, 100);
            imageline(
                $image,
                rand(0, $width),
                rand(0, $height),
                rand(0, $width),
                rand(0, $height),
                $lineAlpha
            );
        }
        
        // Agregar puntos de ruido
        for ($i = 0; $i < 100; $i++) {
            $dotAlpha = imagecolorallocatealpha($image, 241, 228, 209, 110);
            imagesetpixel($image, rand(0, $width), rand(0, $height), $dotAlpha);
        }
        
        // Configurar fuente y tamaño (optimizado para diseño compacto)
        $fontSize = 16; // Texto más pequeño
        $fontPath = $this->getFontPath();
        
        // Dibujar cada carácter con rotación aleatoria y espaciado compacto
        $x = 15; // Menos margen izquierdo
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            $angle = rand(-12, 12); // Menos rotación para más compacto
            $y = rand(38, 42); // Centrado vertical para font size 16px
            
            // Sombra del texto
            $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, 80);
            imagettftext($image, $fontSize, $angle, $x + 2, $y + 2, $shadowColor, $fontPath, $char);
            
            // Texto principal
            imagettftext($image, $fontSize, $angle, $x, $y, $textColor, $fontPath, $char);
            
            $x += 28; // Espaciado más compacto entre caracteres
        }
        
        // Headers de no-caché agresivos (browser + proxies + IE/Edge-legacy)
        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
        
        // Generar imagen
        imagepng($image);
        
        // Liberar memoria
        imagedestroy($image);
    }
    
    /**
     * Obtiene la ruta de la fuente TTF
     * Intenta usar fuentes del sistema, fallback a fuente por defecto
     * 
     * @return string Ruta a la fuente TTF
     */
    private function getFontPath() {
        // Intentar fuentes comunes del sistema
        $fonts = [
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/arialbd.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
        ];
        
        foreach ($fonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }
        
        // Si no encuentra ninguna, usar fuente GD por defecto (número 5)
        // En este caso, usaremos imagestring en lugar de imagettftext
        return null;
    }
    
    /**
     * Endpoint para refrescar CAPTCHA (AJAX)
     */
    public function refresh() {
        $this->generate();
    }
}
