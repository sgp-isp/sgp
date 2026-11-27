<?php
declare(strict_types=1);

/**
 * ExamSchemaHelper — Garantiza de forma idempotente (1 vez por request)
 * que las columnas de temporizador y emparejamiento existan en BD (local o TiDB Cloud).
 */
class ExamSchemaHelper
{
    private static bool $checked = false;

    public static function ensure(Database $db): void
    {
        if (self::$checked) {
            return;
        }
        self::$checked = true;

        try {
            $pdo = $db->getPdo();

            // 1. examenes.duracion_minutos
            $colDur = $pdo->query("SHOW COLUMNS FROM `examenes` LIKE 'duracion_minutos'")->fetch();
            if (!$colDur) {
                $pdo->exec("ALTER TABLE `examenes` ADD COLUMN `duracion_minutos` INT NULL DEFAULT NULL AFTER `intentos_permitidos`");
            }

            // 2. examen_preguntas.tipo ENUM con 'emparejamiento'
            $colTipo = $pdo->query("SHOW COLUMNS FROM `examen_preguntas` LIKE 'tipo'")->fetch();
            if ($colTipo && stripos((string)($colTipo['Type'] ?? ''), 'emparejamiento') === false) {
                $pdo->exec("ALTER TABLE `examen_preguntas` MODIFY COLUMN `tipo` ENUM('opcion_multiple','verdadero_falso','emparejamiento') NOT NULL DEFAULT 'opcion_multiple'");
            }

            // 3. examen_opciones.texto_par
            $colPar = $pdo->query("SHOW COLUMNS FROM `examen_opciones` LIKE 'texto_par'")->fetch();
            if (!$colPar) {
                $pdo->exec("ALTER TABLE `examen_opciones` ADD COLUMN `texto_par` VARCHAR(500) NULL DEFAULT NULL AFTER `texto`");
            }

            // 4. examen_respuestas.opcion_par_elegida_id
            $colRespPar = $pdo->query("SHOW COLUMNS FROM `examen_respuestas` LIKE 'opcion_par_elegida_id'")->fetch();
            if (!$colRespPar) {
                $pdo->exec("ALTER TABLE `examen_respuestas` ADD COLUMN `opcion_par_elegida_id` INT NULL DEFAULT NULL AFTER `opcion_id_elegida`");
            }
        } catch (\Throwable $e) {
            error_log('[SGP-EXAM-SCHEMA] ensure error: ' . $e->getMessage());
        }
    }
}
