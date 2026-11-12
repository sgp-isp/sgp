<?php
declare(strict_types=1);

/**
 * PlantillaHelper — Carga y resuelve plantillas maestras de `plantillas_reportes`.
 *
 * Permite que los cambios guardados desde /plantillas (título, subtítulo,
 * cuerpo HTML, cintillo, firmas/sello, pie de página, orientación y tamaño)
 * se reflejen automáticamente al generar reportes PDF en /reportes.
 */
class PlantillaHelper
{
    /**
     * Obtiene una plantilla de `plantillas_reportes` por código y reemplaza {VARIABLES}.
     *
     * @param string $codigo Código único de la plantilla en `plantillas_reportes`
     * @param array  $vars   Mapa ['{VARIABLE}' => 'valor'] o ['VARIABLE' => 'valor']
     * @return object|null   Objeto con raw, titulo, subtitulo, cuerpo, pie, cintillo, firmas, orientacion, tamano, es_personalizada
     */
    public static function obtener(string $codigo, array $vars = []): ?object
    {
        try {
            $db = Database::getInstance();
            $db->query("SELECT * FROM plantillas_reportes WHERE codigo = :codigo LIMIT 1");
            $db->bind(':codigo', $codigo);
            $raw = $db->single();
            if (!$raw) {
                return null;
            }

            $esPersonalizada = !empty($raw->es_personalizada);

            // Normalizar claves de variables para aceptar con o sin llaves
            $reemplazos = [];
            foreach ($vars as $k => $v) {
                $key = (str_starts_with((string)$k, '{') && str_ends_with((string)$k, '}'))
                    ? (string)$k
                    : '{' . $k . '}';
                $reemplazos[$key] = (string)$v;
            }

            $aplicar = static function (?string $texto) use ($reemplazos): string {
                if ($texto === null || $texto === '') {
                    return '';
                }
                return empty($reemplazos) ? $texto : strtr($texto, $reemplazos);
            };

            $firmas = [];
            if (!empty($raw->firmas_json)) {
                $decoded = json_decode((string)$raw->firmas_json, true);
                if (is_array($decoded)) {
                    $firmas = $decoded;
                }
            }

            return (object)[
                'raw'              => $raw,
                'es_personalizada' => $esPersonalizada,
                'titulo'           => $aplicar($raw->titulo_documento ?? ''),
                'subtitulo'        => $aplicar($raw->subtitulo_documento ?? ''),
                'cuerpo'           => $aplicar($raw->cuerpo_html ?? ''),
                'pie'              => $aplicar($raw->pie_pagina ?? ''),
                'cintillo'         => trim((string)($raw->cintillo_url ?? '')),
                'firmas'           => $firmas,
                'orientacion'      => !empty($raw->orientacion) ? strtolower((string)$raw->orientacion) : 'portrait',
                'tamano'           => !empty($raw->tamano_papel) ? strtolower((string)$raw->tamano_papel) : 'letter',
            ];
        } catch (\Throwable $e) {
            error_log('[PlantillaHelper::obtener] ' . $e->getMessage());
            return null;
        }
    }
}
