<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/config/config.php';
require_once __DIR__ . '/../../app/core/Database.php';

try {
    $db = Database::getInstance();
    $sql = "CREATE TABLE IF NOT EXISTS `soporte_ticket_mensajes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ticket_id` int(11) NOT NULL,
        `usuario_id` int(11) DEFAULT NULL,
        `remitente_tipo` ENUM('usuario', 'soporte', 'sistema') NOT NULL DEFAULT 'usuario',
        `remitente_nombre` varchar(150) NOT NULL,
        `mensaje` text NOT NULL,
        `adjunto` varchar(255) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_ticket_id` (`ticket_id`),
        KEY `idx_usuario_id` (`usuario_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->query($sql);
    $db->execute();
    echo "SUCCESS: soporte_ticket_mensajes table created/verified.\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
