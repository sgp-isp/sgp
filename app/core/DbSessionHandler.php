<?php
declare(strict_types=1);

/**
 * DbSessionHandler — Almacenamiento de sesiones PHP en MySQL/TiDB
 *
 * Evita pérdida de sesiones en entornos con disco efímero o reinicios de
 * contenedor (ej. Render Free) donde /tmp se limpia entre despliegues o spin-down.
 */
class DbSessionHandler implements SessionHandlerInterface
{
    private PDO $pdo;
    private static bool $tableEnsured = false;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        if (self::$tableEnsured) {
            return;
        }
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `sgp_sesiones` (
                    `id` varchar(128) NOT NULL,
                    `data` mediumtext NOT NULL,
                    `last_access` int(11) unsigned NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_sgp_sesiones_access` (`last_access`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            self::$tableEnsured = true;
        } catch (\Throwable $e) {
            error_log('[SGP-SESSION] ensureTable error: ' . $e->getMessage());
        }
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $stmt = $this->pdo->prepare('SELECT data FROM sgp_sesiones WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (string)$row['data'] : '';
        } catch (\Throwable $e) {
            error_log('[SGP-SESSION] read error: ' . $e->getMessage());
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'REPLACE INTO sgp_sesiones (id, data, last_access) VALUES (?, ?, ?)'
            );
            return $stmt->execute([$id, $data, time()]);
        } catch (\Throwable $e) {
            error_log('[SGP-SESSION] write error: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM sgp_sesiones WHERE id = ?');
            return $stmt->execute([$id]);
        } catch (\Throwable $e) {
            error_log('[SGP-SESSION] destroy error: ' . $e->getMessage());
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM sgp_sesiones WHERE last_access < ?');
            $stmt->execute([time() - $max_lifetime]);
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log('[SGP-SESSION] gc error: ' . $e->getMessage());
            return false;
        }
    }
}
