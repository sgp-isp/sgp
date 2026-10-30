<?php
declare(strict_types=1);

/**
 * Database — Clase de acceso a datos con patrón Singleton
 *
 * Una sola conexión PDO por request HTTP.
 * Todos los métodos públicos originales se mantienen sin cambios.
 *
 * SGP-FIX-v2 [6] aplicado — Singleton + silenciar PDOException
 */
class Database
{
    // ── Singleton ────────────────────────────────────────────────────────────
    private static ?Database $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            // La clave del nombre de BD en config.php es 'name' (NO 'dbname')
            $config = require dirname(__DIR__) . '/config/config.php';
            self::$instance = new self($config['db']);
        }
        return self::$instance;
    }

    /** Previene clonación del Singleton */
    private function __clone() {}

    /** Previene deserialización del Singleton */
    public function __wakeup(): void
    {
        throw new RuntimeException('Cannot unserialize singleton.');
    }

    // ── Propiedades ──────────────────────────────────────────────────────────
    private string $host;
    private string $db_name;
    private string $user;
    private string $pass;
    private string $charset;

    private $dbh;
    private $stmt;
    public string $error = '';

    // ── Constructor (private — usar getInstance()) ───────────────────────────
    public function __construct(array $config)
    {
        $this->host    = $config['host'];
        $this->db_name = $config['name'];    // 'name' — clave real del config.php SGP
        $this->user    = $config['user'];
        $this->pass    = $config['pass'];
        $this->charset = $config['charset'];

        $dsn     = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=' . $this->charset;
        $options = [
            PDO::ATTR_PERSISTENT         => false, // [FIX-P2] Desactivado: conexiones persistentes agotan el pool de MySQL en Apache/mod_php sin pooler intermedio
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci"
        ];

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
            // Sincronizar timezone de la conexión MySQL con Venezuela (-04:00 / America/Caracas)
            $this->dbh->exec("SET time_zone = '-04:00'");
        } catch (PDOException $e) {
            // SGP-FIX-v2 [1.2] — Nunca exponer detalles de BD al usuario
            error_log('[SGP-DB-ERROR] ' . date('Y-m-d H:i:s') . ' | ' . $e->getMessage());
            $this->error = 'Error de conexión a la base de datos';
            throw new RuntimeException('Error de conexión a la base de datos', 0, $e);
        }
    }

    // ── API pública (todos los métodos originales preservados) ───────────────

    /** Prepara una sentencia SQL */
    public function query($sql)
    {
        try {
            $this->stmt = $this->dbh->prepare($sql);
        } catch (PDOException $e) {
            error_log('[SGP-DB-QUERY-ERROR] ' . date('Y-m-d H:i:s') . ' | SQL: ' . substr($sql, 0, 100) . ' | Error: ' . $e->getMessage());
            throw new RuntimeException('Error al procesar la consulta.', 0, $e);
        }
    }

    /** Enlaza un parámetro a la sentencia preparada */
    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }

        $this->stmt->bindValue($param, $value, $type);
    }

    /** Ejecuta la sentencia preparada */
    public function execute()
    {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            error_log('[SGP-DB-EXEC-ERROR] ' . date('Y-m-d H:i:s') . ' | Error: ' . $e->getMessage());
            throw new RuntimeException('Error al ejecutar la consulta.', 0, $e);
        }
    }

    /** Devuelve todos los resultados como array de objetos */
    public function resultSet()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** Devuelve un único resultado como objeto */
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    /** Número de filas afectadas */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /** Último ID insertado */
    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }

    /** Inicia una transacción */
    public function beginTransaction()
    {
        return $this->dbh->beginTransaction();
    }

    /** Confirma la transacción */
    public function commit()
    {
        return $this->dbh->commit();
    }

    /** Revierte la transacción */
    public function rollback()
    {
        return $this->dbh->rollBack();
    }
}
