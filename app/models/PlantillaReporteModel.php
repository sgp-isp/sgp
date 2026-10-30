<?php
/**
 * PlantillaReporteModel — Modelo para el Gestor Centralizado de Plantillas PDF
 * Sistema de Gestión de Pasantías (SGP)
 *
 * Administra el catálogo de plantillas oficiales de reportes y documentos
 * con soporte para edición visual WYSIWYG, variables inteligentes y firmas dinámicas.
 */

declare(strict_types=1);

class PlantillaReporteModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->sembrarDefaultsSiVacio();
    }

    /**
     * Obtiene todas las plantillas disponibles ordenadas por categoría y nombre.
     */
    public function getAll(?string $categoria = null): array
    {
        $sql = "SELECT id, codigo, nombre, descripcion, categoria, orientacion, tamano_papel,
                       cintillo_url, titulo_documento, subtitulo_documento,
                       firmas_json, pie_pagina, variables_disponibles, es_personalizada,
                       updated_at
                FROM plantillas_reportes";
        
        if ($categoria && $categoria !== 'ALL') {
            $sql .= " WHERE categoria = :cat";
        }
        $sql .= " ORDER BY categoria ASC, nombre ASC";

        $this->db->query($sql);
        if ($categoria && $categoria !== 'ALL') {
            $this->db->bind(':cat', $categoria);
        }

        $rows = $this->db->resultSet();
        return $rows ? array_map(fn($r) => (array)$r, $rows) : [];
    }

    /**
     * Obtiene una plantilla completa por su código único.
     */
    public function getByCodigo(string $codigo): ?array
    {
        $this->db->query("
            SELECT * FROM plantillas_reportes 
            WHERE codigo = :codigo 
            LIMIT 1
        ");
        $this->db->bind(':codigo', $codigo);
        $row = $this->db->single();
        if (!$row) return null;

        $arr = (array)$row;
        if (!empty($arr['firmas_json']) && is_string($arr['firmas_json'])) {
            $arr['firmas'] = json_decode($arr['firmas_json'], true) ?: [];
        } else {
            $arr['firmas'] = [];
        }
        if (!empty($arr['variables_disponibles']) && is_string($arr['variables_disponibles'])) {
            $arr['variables'] = json_decode($arr['variables_disponibles'], true) ?: [];
        } else {
            $arr['variables'] = [];
        }

        return $arr;
    }

    /**
     * Guarda modificaciones en una plantilla.
     */
    public function guardar(string $codigo, array $data, int $userId): bool
    {
        $firmasJson = isset($data['firmas']) ? json_encode($data['firmas'], JSON_UNESCAPED_UNICODE) : null;

        $this->db->query("
            UPDATE plantillas_reportes
            SET titulo_documento   = :titulo,
                subtitulo_documento= :subtitulo,
                cuerpo_html        = :cuerpo,
                cintillo_url       = :cintillo,
                firmas_json        = :firmas,
                pie_pagina         = :pie,
                orientacion        = :orientacion,
                tamano_papel       = :tamano,
                es_personalizada   = 1,
                updated_by         = :uid,
                updated_at         = NOW()
            WHERE codigo = :codigo
        ");

        $this->db->bind(':titulo',     $data['titulo_documento'] ?? '');
        $this->db->bind(':subtitulo',  $data['subtitulo_documento'] ?? '');
        $this->db->bind(':cuerpo',     $data['cuerpo_html'] ?? '');
        $this->db->bind(':cintillo',   $data['cintillo_url'] ?? null);
        $this->db->bind(':firmas',     $firmasJson);
        $this->db->bind(':pie',        $data['pie_pagina'] ?? '');
        $this->db->bind(':orientacion',$data['orientacion'] ?? 'portrait');
        $this->db->bind(':tamano',     $data['tamano_papel'] ?? 'letter');
        $this->db->bind(':uid',        $userId);
        $this->db->bind(':codigo',     $codigo);

        return $this->db->execute();
    }

    /**
     * Restablece una plantilla a sus valores de fábrica originales.
     */
    public function restablecer(string $codigo, int $userId): bool
    {
        $defaults = $this->getDefaultsCatalogo();
        if (!isset($defaults[$codigo])) {
            return false;
        }

        $def = $defaults[$codigo];
        $firmasJson = json_encode($def['firmas'], JSON_UNESCAPED_UNICODE);

        $this->db->query("
            UPDATE plantillas_reportes
            SET titulo_documento   = :titulo,
                subtitulo_documento= :subtitulo,
                cuerpo_html        = :cuerpo,
                cintillo_url       = :cintillo,
                firmas_json        = :firmas,
                pie_pagina         = :pie,
                orientacion        = :orientacion,
                tamano_papel       = :tamano,
                es_personalizada   = 0,
                updated_by         = :uid,
                updated_at         = NOW()
            WHERE codigo = :codigo
        ");

        $this->db->bind(':titulo',     $def['titulo_documento']);
        $this->db->bind(':subtitulo',  $def['subtitulo_documento']);
        $this->db->bind(':cuerpo',     $def['cuerpo_html']);
        $this->db->bind(':cintillo',   $def['cintillo_url']);
        $this->db->bind(':firmas',     $firmasJson);
        $this->db->bind(':pie',        $def['pie_pagina']);
        $this->db->bind(':orientacion',$def['orientacion']);
        $this->db->bind(':tamano',     $def['tamano_papel']);
        $this->db->bind(':uid',        $userId);
        $this->db->bind(':codigo',     $codigo);

        return $this->db->execute();
    }

    /**
     * Siembra las plantillas iniciales del sistema si la tabla está vacía.
     */
    private function sembrarDefaultsSiVacio(): void
    {
        try {
            $this->db->query("SELECT COUNT(*) AS total FROM plantillas_reportes");
            $total = (int)($this->db->single()->total ?? 0);
            if ($total > 0) return;

            $catalogo = $this->getDefaultsCatalogo();
            foreach ($catalogo as $codigo => $item) {
                $this->db->query("
                    INSERT INTO plantillas_reportes (
                        codigo, nombre, descripcion, categoria, orientacion, tamano_papel,
                        cintillo_url, titulo_documento, subtitulo_documento, cuerpo_html,
                        firmas_json, pie_pagina, variables_disponibles, es_personalizada
                    ) VALUES (
                        :codigo, :nombre, :descripcion, :categoria, :orientacion, :tamano_papel,
                        :cintillo_url, :titulo_documento, :subtitulo_documento, :cuerpo_html,
                        :firmas_json, :pie_pagina, :variables_disponibles, 0
                    )
                ");

                $this->db->bind(':codigo',               $codigo);
                $this->db->bind(':nombre',               $item['nombre']);
                $this->db->bind(':descripcion',          $item['descripcion']);
                $this->db->bind(':categoria',            $item['categoria']);
                $this->db->bind(':orientacion',          $item['orientacion']);
                $this->db->bind(':tamano_papel',         $item['tamano_papel']);
                $this->db->bind(':cintillo_url',         $item['cintillo_url']);
                $this->db->bind(':titulo_documento',     $item['titulo_documento']);
                $this->db->bind(':subtitulo_documento',  $item['subtitulo_documento']);
                $this->db->bind(':cuerpo_html',          $item['cuerpo_html']);
                $this->db->bind(':firmas_json',          json_encode($item['firmas'], JSON_UNESCAPED_UNICODE));
                $this->db->bind(':pie_pagina',           $item['pie_pagina']);
                $this->db->bind(':variables_disponibles',json_encode($item['variables'], JSON_UNESCAPED_UNICODE));
                $this->db->execute();
            }
        } catch (Throwable $e) {
            error_log('[PlantillaReporteModel] Error al sembrar defaults: ' . $e->getMessage());
        }
    }

    /**
     * Catálogo maestro de definiciones originales de fábrica.
     */
    public function getDefaultsCatalogo(): array
    {
        return [
            'constancia_culminacion' => [
                'nombre'              => 'Carta de Culminación de Pasantías',
                'descripcion'         => 'Documento oficial de culminación exitosa de pasantías emitido por la Dirección de Telemática.',
                'categoria'           => 'Constancias',
                'orientacion'         => 'portrait',
                'tamano_papel'        => 'letter',
                'cintillo_url'        => 'img/cintillo.png',
                'titulo_documento'    => 'CARTA DE CULMINACIÓN DE PASANTÍAS PROFESIONALES',
                'subtitulo_documento' => 'Dirección de Telemática / División de Soporte Técnico',
                'cuerpo_html'         => '<p>Quien suscribe, en su carácter de responsable del área de <strong>{DEPARTAMENTO}</strong>, adscrito a la <strong>División de Soporte Técnico</strong> de la <strong>Dirección de Telemática</strong> del Instituto de Salud Pública del Estado Bolívar, certifica por medio de la presente que el/la ciudadano/a <strong>{NOMBRE_PASANTE}</strong>, titular de la Cédula de Identidad Nro. <strong>{CEDULA_PASANTE}</strong>, ha <strong>culminado satisfactoriamente</strong> sus Pasantías Profesionales en la institución bajo la supervisión del área de <strong>{DEPARTAMENTO}</strong>, durante el período académico <strong>{PERIODO}</strong>, completando una carga horaria total de <strong>{HORAS_ACUMULADAS} horas</strong>, cumpliendo cabalmente con las <strong>{HORAS_META} horas</strong> exigidas por su casa de estudios <strong>{INSTITUCION}</strong>.</p><p>Se expide la presente carta a petición de la parte interesada, en Ciudad Bolívar, Estado Bolívar, con fecha <strong>{FECHA_EMISION}</strong>.</p>',
                'firmas'              => [
                    'firmante_nombre' => '{TUTOR_NOMBRE}',
                    'firmante_cargo'  => 'Jefe de la División de Soporte Técnico de la Dirección de Telemática',
                    'antefirma'       => 'Atentamente,',
                    'institucion'     => 'Instituto de Salud Pública del Estado Bolívar',
                    'mostrar_sello'   => true,
                    'sello_texto'     => 'Sello Oficial'
                ],
                'pie_pagina'          => 'Documento emitido por el Sistema de Gestión de Pasantías (SGP) | Instituto de Salud Pública del Estado Bolívar',
                'variables'           => [
                    '{NOMBRE_PASANTE}'   => 'Nombre completo del pasante',
                    '{CEDULA_PASANTE}'   => 'Cédula de identidad (V-...)',
                    '{DEPARTAMENTO}'     => 'Departamento o área técnica',
                    '{INSTITUCION}'      => 'Universidad o Instituto educativo',
                    '{PERIODO}'          => 'Nombre o duración del período',
                    '{HORAS_ACUMULADAS}' => 'Horas completadas',
                    '{HORAS_META}'       => 'Horas requeridas',
                    '{FECHA_INICIO}'     => 'Fecha de inicio (dd/mm/aaaa)',
                    '{FECHA_FIN}'        => 'Fecha de culminación (dd/mm/aaaa)',
                    '{FECHA_EMISION}'    => 'Fecha actual de emisión',
                    '{NUMERO_CONTROL}'   => 'Código único de control correlativo',
                    '{TUTOR_NOMBRE}'     => 'Nombre del tutor o jefe de división'
                ]
            ],

            'constancia_servicio' => [
                'nombre'              => 'Constancia de Pasantía en Curso',
                'descripcion'         => 'Constancia que certifica que el estudiante se encuentra activo realizando pasantías.',
                'categoria'           => 'Constancias',
                'orientacion'         => 'portrait',
                'tamano_papel'        => 'letter',
                'cintillo_url'        => 'img/cintillo.png',
                'titulo_documento'    => 'CONSTANCIA DE PASANTÍA',
                'subtitulo_documento' => 'Dirección de Telemática / División de Soporte Técnico',
                'cuerpo_html'         => '<p>Quien suscribe, en su carácter de responsable del área de <strong>{DEPARTAMENTO}</strong>, adscrito a la <strong>División de Soporte Técnico</strong> de la <strong>Dirección de Telemática</strong> del Instituto de Salud Pública del Estado Bolívar, hace constar por medio de la presente que el/la ciudadano/a <strong>{NOMBRE_PASANTE}</strong>, titular de la Cédula de Identidad Nro. <strong>{CEDULA_PASANTE}</strong>, se encuentra realizando pasantías académicas en esta institución bajo la supervisión del área de <strong>{DEPARTAMENTO}</strong>, cumpliendo una carga horaria estimada de <strong>{HORAS_META} horas</strong> correspondiente al período <strong>{PERIODO}</strong>, con fecha de inicio el <strong>{FECHA_INICIO}</strong> y fecha estimada de culminación el <strong>{FECHA_FIN}</strong>.</p><p>Constancia que se expide a solicitud de la parte interesada, en Ciudad Bolívar, a los {FECHA_EMISION}.</p>',
                'firmas'              => [
                    'firmante_nombre' => '{TUTOR_NOMBRE}',
                    'firmante_cargo'  => 'Jefe de la División de Soporte Técnico de la Dirección de Telemática',
                    'antefirma'       => 'Atentamente,',
                    'institucion'     => 'Instituto de Salud Pública del Estado Bolívar',
                    'mostrar_sello'   => true,
                    'sello_texto'     => 'Sello Oficial'
                ],
                'pie_pagina'          => 'Documento emitido por el Sistema de Gestión de Pasantías (SGP) | Instituto de Salud Pública del Estado Bolívar',
                'variables'           => [
                    '{NOMBRE_PASANTE}'   => 'Nombre completo del pasante',
                    '{CEDULA_PASANTE}'   => 'Cédula de identidad (V-...)',
                    '{DEPARTAMENTO}'     => 'Departamento o área técnica',
                    '{INSTITUCION}'      => 'Universidad o Instituto educativo',
                    '{PERIODO}'          => 'Nombre o duración del período',
                    '{HORAS_META}'       => 'Horas requeridas',
                    '{FECHA_INICIO}'     => 'Fecha de inicio',
                    '{FECHA_FIN}'        => 'Fecha estimada de fin',
                    '{FECHA_EMISION}'    => 'Fecha actual de emisión',
                    '{NUMERO_CONTROL}'   => 'Código de control',
                    '{TUTOR_NOMBRE}'     => 'Nombre del tutor asignado'
                ]
            ],

            'constancia_egresado' => [
                'nombre'              => 'Constancia de Pasantías (Egresados Históricos)',
                'descripcion'         => 'Constancia especial para pasantes que culminaron en períodos académicos cerrados.',
                'categoria'           => 'Constancias',
                'orientacion'         => 'portrait',
                'tamano_papel'        => 'letter',
                'cintillo_url'        => 'img/cintillo.png',
                'titulo_documento'    => 'CONSTANCIA DE SERVICIO DE PASANTÍA PROFESIONAL',
                'subtitulo_documento' => 'Historial y Respaldo Institucional — Dirección de Telemática',
                'cuerpo_html'         => '<p>Por medio de la presente, la Dirección de Telemática del Instituto de Salud Pública del Estado Bolívar deja constancia de que el ciudadano/a <strong>{NOMBRE_PASANTE}</strong>, portador de la C.I. <strong>{CEDULA_PASANTE}</strong>, egresado de <strong>{INSTITUCION}</strong>, prestó satisfactoriamente sus servicios de pasantías en esta dependencia en el área de <strong>{DEPARTAMENTO}</strong>, durante un lapso de <strong>{PERIODO}</strong>, cumpliendo una carga horaria de <strong>{HORAS_ACUMULADAS} horas</strong>.</p><p>Se extiende la presente constancia histórica en Ciudad Bolívar, en fecha <strong>{FECHA_EMISION}</strong>.</p>',
                'firmas'              => [
                    'firmante_nombre' => '{TUTOR_NOMBRE}',
                    'firmante_cargo'  => 'Jefe de la División de Soporte Técnico de la Dirección de Telemática',
                    'antefirma'       => 'Atentamente,',
                    'institucion'     => 'Instituto de Salud Pública del Estado Bolívar',
                    'mostrar_sello'   => true,
                    'sello_texto'     => 'Sello Oficial'
                ],
                'pie_pagina'          => 'Registro Histórico SGP | Respaldo Oficial de la Dirección de Telemática',
                'variables'           => [
                    '{NOMBRE_PASANTE}'   => 'Nombre completo del pasante',
                    '{CEDULA_PASANTE}'   => 'Cédula de identidad',
                    '{DEPARTAMENTO}'     => 'Departamento de pasantía',
                    '{INSTITUCION}'      => 'Institución educativa',
                    '{PERIODO}'          => 'Duración (ej: 6 Meses / 2024-2025)',
                    '{HORAS_ACUMULADAS}' => 'Horas cumplidas',
                    '{FECHA_EMISION}'    => 'Fecha de expedición',
                    '{TUTOR_NOMBRE}'     => 'Nombre bajo firma'
                ]
            ],

            'ficha_asistencia_individual' => [
                'nombre'              => 'Ficha Personal y Control de Asistencia',
                'descripcion'         => 'Expediente integral con historial de asistencias, tardanzas, faltas y firmas.',
                'categoria'           => 'Asistencias',
                'orientacion'         => 'portrait',
                'tamano_papel'        => 'letter',
                'cintillo_url'        => 'img/cintillo.png',
                'titulo_documento'    => 'FICHA DE ASISTENCIA Y CONTROL INDIVIDUAL',
                'subtitulo_documento' => 'Expediente Académico y Récord Horario',
                'cuerpo_html'         => '<p>Reporte oficial de asistencias registradas mediante el sistema biométrico y de kiosco digital para el pasante <strong>{NOMBRE_PASANTE}</strong> (C.I. <strong>{CEDULA_PASANTE}</strong>), adscrito a <strong>{DEPARTAMENTO}</strong> bajo tutoría de <strong>{TUTOR_NOMBRE}</strong>.</p>',
                'firmas'              => [
                    'firmante_nombre' => '{TUTOR_NOMBRE}',
                    'firmante_cargo'  => 'Tutor Institucional Asignado',
                    'antefirma'       => 'Conforme,',
                    'institucion'     => 'División de Soporte Técnico / Telemática',
                    'mostrar_sello'   => true,
                    'sello_texto'     => 'Visto Bueno'
                ],
                'pie_pagina'          => 'Expediente Individual de Asistencias SGP | Sistema Biométrico',
                'variables'           => [
                    '{NOMBRE_PASANTE}' => 'Nombre del pasante',
                    '{CEDULA_PASANTE}' => 'Cédula del pasante',
                    '{DEPARTAMENTO}'   => 'Departamento asignado',
                    '{TUTOR_NOMBRE}'   => 'Tutor institucional',
                    '{PERIODO}'        => 'Período académico'
                ]
            ],

            'evaluacion_desempeno' => [
                'nombre'              => 'Ficha de Evaluación de Desempeño',
                'descripcion'         => 'Evaluación técnica, actitudinal y cuantitativa del pasante con baremo oficial.',
                'categoria'           => 'Evaluaciones',
                'orientacion'         => 'portrait',
                'tamano_papel'        => 'letter',
                'cintillo_url'        => 'img/cintillo.png',
                'titulo_documento'    => 'EVALUACIÓN DE DESEMPEÑO DEL PASANTE',
                'subtitulo_documento' => 'Dirección de Telemática — Instituto de Salud Pública',
                'cuerpo_html'         => '<p>Instrumento de evaluación integral de competencias, rendimiento y responsabilidad aplicado al pasante <strong>{NOMBRE_PASANTE}</strong> (C.I. <strong>{CEDULA_PASANTE}</strong>) durante el desarrollo de sus pasantías en <strong>{DEPARTAMENTO}</strong>.</p>',
                'firmas'              => [
                    'firmante_nombre' => '{TUTOR_NOMBRE}',
                    'firmante_cargo'  => 'Tutor Institucional Evaluador',
                    'antefirma'       => 'Evaluado por,',
                    'institucion'     => 'Instituto de Salud Pública del Estado Bolívar',
                    'mostrar_sello'   => true,
                    'sello_texto'     => 'Sello de Evaluación'
                ],
                'pie_pagina'          => 'Ficha de Evaluación SGP | Confidencial Institucional',
                'variables'           => [
                    '{NOMBRE_PASANTE}' => 'Nombre del pasante',
                    '{CEDULA_PASANTE}' => 'Cédula de identidad',
                    '{DEPARTAMENTO}'   => 'Área evaluada',
                    '{TUTOR_NOMBRE}'   => 'Nombre del evaluador',
                    '{FECHA_EMISION}'  => 'Fecha de evaluación'
                ]
            ],

            'auditoria_bitacora' => [
                'nombre'              => 'Comprobante de Auditoría de Bitácora',
                'descripcion'         => 'Certificado forense de trazabilidad de acciones y eventos del sistema.',
                'categoria'           => 'Auditoría',
                'orientacion'         => 'portrait',
                'tamano_papel'        => 'letter',
                'cintillo_url'        => 'img/cintillo.png',
                'titulo_documento'    => 'COMPROBANTE DE AUDITORÍA Y TRAZABILIDAD TÉCNICA',
                'subtitulo_documento' => 'Bitácora Forense Digital — SGP',
                'cuerpo_html'         => '<p>El presente documento certifica la autenticidad e integridad del registro de auditoría registrado en el sistema SGP. La acción auditada fue ejecutada conforme a los protocolos institucionales de seguridad informática.</p>',
                'firmas'              => [
                    'firmante_nombre' => 'SuperAdministrador del Sistema',
                    'firmante_cargo'  => 'Oficina de Seguridad y Auditoría de Sistemas',
                    'antefirma'       => 'Certificación Digital,',
                    'institucion'     => 'Dirección de Telemática',
                    'mostrar_sello'   => true,
                    'sello_texto'     => 'Sello de Seguridad'
                ],
                'pie_pagina'          => 'Certificado de Auditoría Digital SGP | Trazabilidad Inmutable',
                'variables'           => [
                    '{NUMERO_CONTROL}' => 'Número correlativo de auditoría',
                    '{FECHA_EMISION}'  => 'Fecha y hora exacta del registro'
                ]
            ]
        ];
    }
}
