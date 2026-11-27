-- Migración: tabla de sesiones PHP persistentes en base de datos
CREATE TABLE IF NOT EXISTS `sgp_sesiones` (
    `id` varchar(128) NOT NULL,
    `data` mediumtext NOT NULL,
    `last_access` int(11) unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sgp_sesiones_access` (`last_access`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
