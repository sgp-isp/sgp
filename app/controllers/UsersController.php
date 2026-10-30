<?php
/**
 * UsersController - Admin Panel User Management
 * Handles CRUD operations with modal-based interface
 */
class UsersController extends Controller
{
    private $userModel;

    public function __construct()
    {
        // Security
        CacheControl::noCache();
        AuthMiddleware::require();
        AuthMiddleware::verificarEstado();
        
        Session::start();
        
        // AJAX requests get JSON error instead of HTML redirect
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                  || (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
        
        $roleId = (int)Session::get('role_id');

        if (!in_array($roleId, [0, 1, 2])) {
            // [FIX-AC-1] Único caso permitido para Pasante (rol 3): consultar SU PROPIO
            // perfil vía verUniversal(), que ya valida internamente $id === usuario autenticado.
            // Antes esta condición dejaba pasar CUALQUIER método a cualquier Pasante con
            // cabecera AJAX (create/update/reset/delete/eliminarPermanente/etc.) — escalada
            // de privilegios total. Ahora se restringe explícitamente a esa única ruta.
            $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
            $isOwnProfileLookup = ($roleId === 3) && (strpos($currentPath, '/users/verUniversal') !== false);

            if (!$isOwnProfileLookup) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
                    exit;
                }
                Session::setFlash('error', 'Acceso denegado.');
                $this->redirect('/dashboard');
                exit;
            }
        }
        
        $this->userModel = $this->model('User');
    }

    /**
     * List all users with DataTable
     */
    public function index()
    {
        $users = $this->userModel->getAllUsers();
        
        // Optimización: KPIs precalculados en SQL
        $kpis = $this->userModel->getUsersKPIs();
        
        // Get roles for dropdown
        $config = require '../app/config/config.php';
        $db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
        $db->query("SELECT id, nombre FROM roles ORDER BY id");
        $roles = $db->resultSet();
        
        // Get departamentos for tutor assignment
        $db->query("SELECT id, nombre FROM departamentos ORDER BY nombre");
        $departamentos = $db->resultSet();

        // Instituciones para el campo de pasante en crear usuario
        $db->query("SELECT id, nombre FROM instituciones ORDER BY nombre");
        $instituciones = $db->resultSet();

        // Estado del período académico (para advertencias preventivas en creación de pasantes)
        require_once APPROOT . '/helpers/PeriodoService.php';
        $periodoActivo = PeriodoService::getActivo();

        $this->view('users/index', [
            'users'         => $users,
            'kpis'          => $kpis,
            'roles'         => $roles,
            'departamentos' => $departamentos,
            'instituciones' => $instituciones,
            'periodoActivo' => $periodoActivo,
        ]);
    }

    /**
     * Listado exclusivo de Pasantes Egresados (cohorte / período cerrado)
     */
    public function egresados()
    {
        $egresados = $this->userModel->getEgresadosUsers();
        $kpis = $this->userModel->getUsersKPIs();
        
        $db = Database::getInstance();
        $db->query("SELECT id, nombre, estado, fecha_inicio, fecha_fin FROM periodos_academicos ORDER BY fecha_inicio DESC");
        $periodos = $db->resultSet();
        
        $db->query("SELECT id, nombre FROM instituciones ORDER BY nombre");
        $instituciones = $db->resultSet();

        $db->query("SELECT id, nombre FROM departamentos ORDER BY nombre");
        $departamentos = $db->resultSet();

        $db->query("SELECT id, nombre FROM roles ORDER BY id");
        $roles = $db->resultSet();

        $this->view('users/egresados', [
            'egresados'     => $egresados,
            'kpis'          => $kpis,
            'periodos'      => $periodos,
            'instituciones' => $instituciones,
            'departamentos' => $departamentos,
            'roles'         => $roles,
        ]);
    }

    /**
     * Create user with temporary password (AJAX)
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
        }

        // [FIX-CS-4] Unificado sobre el helper central (antes reimplementaba la
        // validación a mano — esa duplicación es la causa raíz de que reset()/
        // delete()/eliminarPermanente()/toggleStatus() se quedaran sin CSRF, ver CS-2).
        $this->verifyCsrf();

        // 2. Validate extracted logic
        $validacion = $this->validarDatosUsuario($_POST);
        if (!$validacion['success']) {
            $this->jsonResponse(false, $validacion['message']);
        }
        
        $datos = $validacion['data'];
        $nombre          = $datos['nombre'];
        $apellido        = $datos['apellido'];
        $cedula          = $datos['cedula'];
        $correo          = $datos['correo'];
        $rol_id          = $datos['rol_id'];
        $departamento_id = $datos['departamento_id'];
        $institucion_id  = ($rol_id == 3 && !empty($_POST['institucion_id']))
                           ? (int)$_POST['institucion_id'] : null;

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            // 1. Create user with temporary password in 'usuarios'
            $data = [
                'cedula'          => $cedula,
                'correo'          => $correo,
                'rol_id'          => $rol_id,
                'departamento_id' => $departamento_id,
            ];

            if (!$this->userModel->createWithTempPassword($data)) {
                throw new Exception('Error al crear registro en la tabla usuarios.');
            }

            // 2. Get the new User ID
            $userId = (int) $db->lastInsertId();

            // 3. Create record in 'datos_personales'
            $db->query("INSERT INTO datos_personales (usuario_id, nombres, apellidos) VALUES (:uid, :nom, :ape)");
            $db->bind(':uid', $userId);
            $db->bind(':nom', $nombre);
            $db->bind(':ape', $apellido);
            if (!$db->execute()) {
                throw new Exception('Error al crear el registro de datos personales.');
            }

            // 4. Si es Pasante con institución: crear registro mínimo en datos_pasante
            if ($rol_id == 3 && $institucion_id) {
                $instNombre = '';
                $db->query("SELECT nombre FROM instituciones WHERE id = :iid LIMIT 1");
                $db->bind(':iid', $institucion_id);
                $iRow = $db->single();
                if ($iRow) $instNombre = $iRow->nombre;

                $db->query("
                    INSERT INTO datos_pasante (usuario_id, estado_pasantia, institucion_id, institucion_procedencia)
                    VALUES (:uid, 'Sin Asignar', :inst, :inst_nom)
                    ON DUPLICATE KEY UPDATE
                        institucion_id = VALUES(institucion_id),
                        institucion_procedencia = VALUES(institucion_procedencia)
                ");
                $db->bind(':uid',      $userId);
                $db->bind(':inst',     $institucion_id);
                $db->bind(':inst_nom', $instNombre ?: $institucion_id);
                $db->execute();
            }

            // 5. Commit transaction
            $db->commit();

            AuditModel::log('CREATE_USER', 'usuarios', $userId, [
                'cedula'          => $cedula,
                'rol_id'          => $rol_id,
                'email'           => $correo,
                'nombre_completo' => $nombre . ' ' . $apellido,
                'institucion_id'  => $institucion_id,
            ]);

            $tempPassword = $this->userModel->generateTempPassword($cedula);
            $this->jsonResponse(true, 'Usuario creado exitosamente. Contraseña temporal: ' . $tempPassword);

        } catch (Exception $e) {
            $db->rollBack();
            // [FIX-C2] Mensaje genérico al cliente — detalle interno solo en log
            error_log('[SGP-USERS] create() Error: ' . $e->getMessage());
            $this->jsonResponse(false, 'Error al crear el usuario. Intente de nuevo.');
        }
    }

    /**
     * AJAX: Registro Grupal / Masivo de Pasantes por Institución
     * POST /users/crearGrupal
     */
    public function crearGrupal(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido.');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!Session::validateCsrfToken($csrf)) {
            $this->jsonResponse(false, 'Token CSRF inválido o sesión expirada. Recarga la página.');
        }

        $institucionId = !empty($_POST['institucion_id']) ? (int)$_POST['institucion_id'] : null;

        // Decodificar lista de pasantes
        $pasantesRaw = $_POST['pasantes'] ?? [];
        if (is_string($pasantesRaw)) {
            $pasantesRaw = json_decode($pasantesRaw, true) ?? [];
        }

        if (empty($pasantesRaw) || !is_array($pasantesRaw)) {
            $this->jsonResponse(false, 'Debe incluir al menos un pasante con datos válidos.');
        }

        if (count($pasantesRaw) > 50) {
            $this->jsonResponse(false, 'El límite máximo por lote es de 50 pasantes.');
        }

        $db = Database::getInstance();
        $limpios = [];
        $cedulasVistas = [];
        $correosVistos = [];

        // 1. Pre-validación de filas y unicidad interna dentro del lote
        foreach ($pasantesRaw as $idx => $p) {
            $filaNum = $idx + 1;
            $cedula = trim($p['cedula'] ?? '');
            $nombres = trim($p['nombres'] ?? '');
            $apellidos = trim($p['apellidos'] ?? '');
            $correo = strtolower(trim($p['correo'] ?? ''));

            // Omitir filas completamente vacías
            if ($cedula === '' && $nombres === '' && $apellidos === '' && $correo === '') {
                continue;
            }

            // Validar cédula: entre 7 y 8 dígitos numéricos
            if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
                $this->jsonResponse(false, "Fila #{$filaNum}: Cédula '{$cedula}' inválida. Debe tener 7 u 8 dígitos numéricos.");
            }

            // Validar nombres y apellidos
            if (empty($nombres) || mb_strlen($nombres) < 2) {
                $this->jsonResponse(false, "Fila #{$filaNum}: El nombre es obligatorio (mínimo 2 letras).");
            }
            if (empty($apellidos) || mb_strlen($apellidos) < 2) {
                $this->jsonResponse(false, "Fila #{$filaNum}: El apellido es obligatorio (mínimo 2 letras).");
            }

            // Validar correo
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $this->jsonResponse(false, "Fila #{$filaNum}: El correo '{$correo}' no es válido.");
            }

            // Unicidad interna
            if (isset($cedulasVistas[$cedula])) {
                $this->jsonResponse(false, "La cédula {$cedula} está repetida varias veces en la lista a registrar.");
            }
            if (isset($correosVistos[$correo])) {
                $this->jsonResponse(false, "El correo {$correo} está repetido varias veces en la lista a registrar.");
            }

            $cedulasVistas[$cedula] = true;
            $correosVistos[$correo] = true;

            $limpios[] = [
                'cedula'    => $cedula,
                'nombres'   => $nombres,
                'apellidos' => $apellidos,
                'correo'    => $correo,
            ];
        }

        if (empty($limpios)) {
            $this->jsonResponse(false, 'No hay pasantes válidos para registrar en el formulario.');
        }

        // 2. Pre-validación contra base de datos
        foreach ($limpios as $item) {
            if ($this->userModel->findByCedula($item['cedula'])) {
                $this->jsonResponse(false, "La cédula {$item['cedula']} ({$item['nombres']} {$item['apellidos']}) ya está registrada en el sistema.");
            }
            if ($this->userModel->findByEmail($item['correo'])) {
                $this->jsonResponse(false, "El correo {$item['correo']} ya está registrado en el sistema.");
            }
        }

        // 3. Inserción atómica en base de datos
        $creados = [];
        try {
            $db->beginTransaction();

            foreach ($limpios as $item) {
                $userData = [
                    'cedula'          => $item['cedula'],
                    'correo'          => $item['correo'],
                    'rol_id'          => 3, // Pasante
                    'departamento_id' => null,
                ];

                if (!$this->userModel->createWithTempPassword($userData)) {
                    throw new Exception("Error al crear usuario para cédula {$item['cedula']}.");
                }

                $userId = (int)$db->lastInsertId();

                // Datos personales
                $db->query("INSERT INTO datos_personales (usuario_id, nombres, apellidos) VALUES (:uid, :nom, :ape)");
                $db->bind(':uid', $userId);
                $db->bind(':nom', $item['nombres']);
                $db->bind(':ape', $item['apellidos']);
                if (!$db->execute()) {
                    throw new Exception("Error al guardar datos personales para {$item['nombres']} {$item['apellidos']}.");
                }

                // Datos pasante vinculados a la institución
                $instBatchNombre = '';
                if ($institucionId) {
                    $db->query("SELECT nombre FROM instituciones WHERE id = :iid LIMIT 1");
                    $db->bind(':iid', $institucionId);
                    $ibRow = $db->single();
                    if ($ibRow) $instBatchNombre = $ibRow->nombre;
                }

                $db->query("
                    INSERT INTO datos_pasante (usuario_id, estado_pasantia, institucion_id, institucion_procedencia)
                    VALUES (:uid, 'Sin Asignar', :inst, :inst_nom)
                    ON DUPLICATE KEY UPDATE
                        institucion_id = VALUES(institucion_id),
                        institucion_procedencia = VALUES(institucion_procedencia)
                ");
                $db->bind(':uid',      $userId);
                $db->bind(':inst',     $institucionId);
                $db->bind(':inst_nom', $instBatchNombre ?: $institucionId);
                $db->execute();

                AuditModel::log('CREATE_USER_BATCH', 'usuarios', $userId, [
                    'cedula'          => $item['cedula'],
                    'rol_id'          => 3,
                    'email'           => $item['correo'],
                    'nombre_completo' => $item['nombres'] . ' ' . $item['apellidos'],
                    'institucion_id'  => $institucionId,
                ]);

                $tempPass = $this->userModel->generateTempPassword($item['cedula']);
                $creados[] = [
                    'id'        => $userId,
                    'cedula'    => $item['cedula'],
                    'nombre'    => $item['nombres'] . ' ' . $item['apellidos'],
                    'correo'    => $item['correo'],
                    'password'  => $tempPass,
                ];
            }

            $db->commit();

            $this->jsonResponse(true, 'Se registraron exitosamente ' . count($creados) . ' pasantes.', [
                'total'   => count($creados),
                'creados' => $creados,
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            error_log('[SGP-USERS] crearGrupal() Error: ' . $e->getMessage());
            $this->jsonResponse(false, 'Error en el registro grupal: ' . $e->getMessage());
        }
    }

    /**
     * Get user data for editing (AJAX)
     */
    public function edit($encrypted_id)
    {
        // Decrypt and validate ID
        $id = UrlSecurity::validateAndDecrypt($encrypted_id);
        
        if (!$id) {
            $this->jsonResponse(false, 'URL inválida o enlace manipulado');
        }
        
        $user = $this->userModel->findById($id);
        
        if ($user) {
            // Get personal data (nombres/apellidos from datos_personales, cedula from usuarios)
            $config = require '../app/config/config.php';
            $db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
            $db->query("
                SELECT u.cedula, dp.nombres, dp.apellidos
                FROM usuarios u
                LEFT JOIN datos_personales dp ON dp.usuario_id = u.id
                WHERE u.id = :uid
            ");
            $db->bind(':uid', $id);
            $personalData = $db->single();
            
            $response = [
                'success' => true,
                'data' => [
                    'id'             => $user['id'],
                    'correo'         => $user['correo'],
                    'rol_id'         => $user['rol_id'],
                    'departamento_id'=> $user['departamento_id'] ?? null,
                    'estado'         => $user['estado'],
                    'cedula'         => $personalData->cedula   ?? '',
                    'nombres'        => $personalData->nombres  ?? '',
                    'apellidos'      => $personalData->apellidos ?? ''
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
        } else {
            $this->jsonResponse(false, 'Usuario no encontrado');
        }
    }

    /**
 * Update user (AJAX)
 */
public function update()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->jsonResponse(false, 'Método no permitido');
    }

    // [FIX-CS-4] Unificado sobre el helper central
    $this->verifyCsrf();

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if (!$id) {
        $this->jsonResponse(false, 'ID de usuario inválido');
    }

    // 2a. Protección de integridad: bloquear cambio de rol entre familias incompatibles
    $nuevoRolId = (int)($_POST['rol_id'] ?? 0);
    $db = Database::getInstance();
    $db->query("SELECT rol_id, cedula, departamento_id FROM usuarios WHERE id = :id LIMIT 1");
    $db->bind(':id', $id);
    $currentRow = $db->single();
    $rolActual  = (int)($currentRow->rol_id ?? 0);
    $cedulaActual = $currentRow->cedula ?? '';

    // Validar privilegio de edición de cédula
    $currentRole = (int)Session::get('role_id');
    $nuevaCedula = trim($_POST['cedula'] ?? '');
    if ($currentRole !== 0 && $nuevaCedula !== $cedulaActual) {
        $this->jsonResponse(false, 'Acceso Denegado: Solo el SuperAdmin tiene privilegios para modificar la cédula de un usuario.');
    }

    if ($nuevoRolId !== null && $_POST['rol_id'] !== '') {
        // Familia A: SuperAdmin(0), Admin(1) y Tutor(2) | Familia B: Pasante(3)
        $familiaActual = ($rolActual === 3) ? 'B' : 'A';
        $familiaNew    = ($nuevoRolId  === 3) ? 'B' : 'A';

        if ($familiaActual !== $familiaNew) {
            $nombres = [0 => 'SuperAdministrador', 1 => 'Administrador', 2 => 'Tutor', 3 => 'Pasante'];
            $this->jsonResponse(false,
                'No se puede cambiar el rol de ' . ($nombres[$rolActual] ?? "ID $rolActual") .
                ' a ' . ($nombres[$nuevoRolId] ?? "ID $nuevoRolId") .
                '. Los perfiles requieren datos distintos. Elimina y vuelve a crear el usuario con el rol correcto.'
            );
        }
    }

    // 2b. Validate extracted logic
    $validacion = $this->validarDatosUsuario($_POST, $id);
    if (!$validacion['success']) {
        $this->jsonResponse(false, $validacion['message']);
    }
    
    $datos = $validacion['data'];
    $nombres = $datos['nombre'];
    $apellidos = $datos['apellido'];
    $cedula = $datos['cedula'];
    $correo = $datos['correo'];
    $rol_id = $datos['rol_id'];
    $departamento_id = $datos['departamento_id'];

    $db = Database::getInstance();

    // Update usuarios table (including cedula)
    $data = [
        'id'             => $id,
        'email'          => $correo,
        'role_id'        => $rol_id,
        'departamento_id'=> $departamento_id
    ];

    try {
        if (!$this->userModel->update($data)) {
            $this->jsonResponse(false, 'Error al actualizar usuario');
        }

        // Actualizar cédula si fue modificada (solo SuperAdmin y Admin)
        if (in_array($currentRole, [0, 1]) && $cedula !== $cedulaActual) {
            // Verificar duplicado
            $db->query("SELECT COUNT(*) AS total FROM usuarios WHERE cedula = :ced AND id != :id");
            $db->bind(':ced', $cedula);
            $db->bind(':id',  $id);
            $dupCheck = (int)($db->single()->total ?? 0);
            if ($dupCheck > 0) {
                $this->jsonResponse(false, "La cédula {$cedula} ya está registrada en otro usuario.");
            }
            $db->query("UPDATE usuarios SET cedula = :cedula WHERE id = :id");
            $db->bind(':cedula', $cedula);
            $db->bind(':id',    $id);
            $db->execute();

            $db->query("UPDATE datos_personales SET cedula = :cedula WHERE usuario_id = :id");
            $db->bind(':cedula', $cedula);
            $db->bind(':id',    $id);
            $db->execute();
        }

        // Upsert datos_personales (nombres, apellidos)
        $db->query("SELECT id FROM datos_personales WHERE usuario_id = :uid");
        $db->bind(':uid', $id);
        $exists = $db->single();

        if ($exists) {
            $db->query("UPDATE datos_personales SET nombres = :nombres, apellidos = :apellidos WHERE usuario_id = :usuario_id");
        } else {
            $db->query("INSERT INTO datos_personales (usuario_id, nombres, apellidos) VALUES (:usuario_id, :nombres, :apellidos)");
        }
        $db->bind(':usuario_id', $id);
        $db->bind(':nombres',    $nombres);
        $db->bind(':apellidos',  $apellidos);

        if ($db->execute()) {
            AuditModel::log('UPDATE_USER', 'usuarios', $id, [
                'campos_actualizados' => ['email', 'rol_id', 'departamento_id', 'cedula', 'datos_personales']
            ]);
            $this->jsonResponse(true, 'Usuario actualizado correctamente');
        } else {
            $this->jsonResponse(false, 'Error al actualizar datos personales');
        }
    } catch (\Throwable $e) {
        // [FIX-ED-5] Mensaje genérico al cliente — detalle solo en log
        error_log('[SGP-USERS] update() Error: ' . $e->getMessage());
        $this->jsonResponse(false, 'Error al actualizar el usuario. Intente de nuevo.');
    }
}

    /**
     * Validar consistencia de datos de usuario (Principio DRY)
     */
    private function validarDatosUsuario($datos, $usuario_id = null)
    {
        // Sanitize fields
        $nombre = htmlspecialchars(trim($datos['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
        $apellido = htmlspecialchars(trim($datos['apellido'] ?? ''), ENT_QUOTES, 'UTF-8');
        $cedula = trim($datos['cedula'] ?? '');
        $correo = filter_var(trim($datos['correo'] ?? ''), FILTER_VALIDATE_EMAIL);
        $rol_id = filter_var($datos['rol_id'] ?? null, FILTER_VALIDATE_INT);

        // Required fields
        if (empty($nombre) || empty($apellido) || empty($cedula)) {
            return ['success' => false, 'message' => 'Nombre, apellido y cédula son obligatorios'];
        }

        if (!$correo) {
            return ['success' => false, 'message' => 'Formato de correo inválido'];
        }

        // Format rules
        if (!preg_match('/^[A-Za-záéíóúÁÉÍÓÚñÑ\s]+$/', $nombre) || !preg_match('/^[A-Za-záéíóúÁÉÍÓÚñÑ\s]+$/', $apellido)) {
            return ['success' => false, 'message' => 'Nombres y apellidos solo pueden contener letras y espacios'];
        }

        if (strlen($nombre) > 100 || strlen($apellido) > 100) {
            return ['success' => false, 'message' => 'El nombre/apellido excede los 100 caracteres permitidos'];
        }

        if (!preg_match('/^[0-9]{7,8}$/', $cedula)) {
            return ['success' => false, 'message' => 'La cédula debe contener entre 7 y 8 dígitos numéricos'];
        }

        // Duplicate checks
        $existingEmail = $this->userModel->findByEmail($correo);
        if ($existingEmail && ($usuario_id === null || $existingEmail['id'] != $usuario_id)) {
            return ['success' => false, 'message' => 'El correo ya está registrado por otro usuario'];
        }
        
        $existingCedula = $this->userModel->findByCedula($cedula);
        if ($existingCedula && ($usuario_id === null || $existingCedula['id'] != $usuario_id)) {
            return ['success' => false, 'message' => 'La cédula ya se encuentra registrada en el sistema'];
        }

        // Rol requerido (0=SuperAdmin, 1=Admin, 2=Tutor, 3=Pasante)
        if ($rol_id === false || $rol_id === null || $rol_id < 0) {
            return ['success' => false, 'message' => 'Debe seleccionar un rol para el usuario.'];
        }

        // Tutors y Administradores: dependencias de departamento
        $departamento_id = null;
        if ($rol_id == 2) {
            // Tutor: departamento estrictamente obligatorio
            $departamento_id = filter_var($datos['departamento_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$departamento_id) {
                return ['success' => false, 'message' => 'Debe seleccionar obligatoriamente un departamento para asignar al tutor'];
            }
        } elseif (in_array($rol_id, [0, 1])) {
            // SuperAdmin y Admin: departamento opcional
            $depVal = filter_var($datos['departamento_id'] ?? null, FILTER_VALIDATE_INT);
            $departamento_id = $depVal ?: null;
        }

        return [
            'success' => true,
            'data' => [
                'nombre' => $nombre,
                'apellido' => $apellido,
                'cedula' => $cedula,
                'correo' => $correo,
                'rol_id' => $rol_id,
                'departamento_id' => $departamento_id
            ]
        ];
    }

    /**
     * Reset user password to temporary (AJAX)
     */
    public function reset($encrypted_id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
        }

        // [FIX-CS-2] Verificación CSRF — antes ausente, permitía forzar el reset
        // de la contraseña de cualquier usuario vía una petición cross-site.
        $this->verifyCsrf();

        // Decrypt and validate ID
        $id = UrlSecurity::validateAndDecrypt($encrypted_id);
        
        if (!$id) {
            $this->jsonResponse(false, 'URL inválida o enlace manipulado');
        }

        // Get user cedula — now lives in usuarios table
        $config = require '../app/config/config.php';
        $db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
        $db->query("SELECT cedula FROM usuarios WHERE id = :uid");
        $db->bind(':uid', $id);
        $result = $db->single();

        if (!$result || empty($result->cedula)) {
            $this->jsonResponse(false, 'Usuario no tiene cédula registrada');
        }

        $cedula = $result->cedula;

        if ($this->userModel->resetToTempPassword($id, $cedula)) {
            AuditModel::log('RESET_PASSWORD', 'usuarios', $id, ['motivo' => 'Solicitud admin']);
            $tempPassword = $this->userModel->generateTempPassword($cedula);
            $this->jsonResponse(true, 'Contraseña restablecida a: ' . $tempPassword);
        } else {
            $this->jsonResponse(false, 'Error al restablecer contraseña');
        }
    }

    /**
     * Delete user (soft delete - set estado to inactivo)
     */
    public function delete($encrypted_id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
        }

        // [FIX-CS-2] Verificación CSRF
        $this->verifyCsrf();

        // Decrypt and validate ID
        $id = UrlSecurity::validateAndDecrypt($encrypted_id);
        
        if (!$id) {
            $this->jsonResponse(false, 'URL inválida o enlace manipulado');
        }

        // Prevent self-deletion
        if ($id == Session::get('user_id')) {
            $this->jsonResponse(false, 'No puedes eliminar tu propia cuenta');
        }

        // Soft delete - set estado to inactivo
        $config = require '../app/config/config.php';
        $db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
        $db->query("UPDATE usuarios SET estado = 'inactivo' WHERE id = :id");
        $db->bind(':id', $id);

        if ($db->execute()) {
            AuditModel::log('DELETE_USER', 'usuarios', $id, ['tipo' => 'soft_delete']);
            $this->jsonResponse(true, 'Usuario desactivado correctamente');
        } else {
            $this->jsonResponse(false, 'Error al desactivar usuario');
        }
    }

    // ─────────────────────────────────────────────────────────────────
    // POST /users/eliminarPermanente/ENC_ID — eliminación permanente
    // ─────────────────────────────────────────────────────────────────
    public function eliminarPermanente(string $encrypted_id = ''): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
            return;
        }

        // [FIX-CS-2] Verificación CSRF — operación destructiva (hard delete en cascada)
        $this->verifyCsrf();

        $id = UrlSecurity::validateAndDecrypt($encrypted_id);
        if (!$id) {
            $this->jsonResponse(false, 'URL inválida o enlace manipulado');
            return;
        }

        if ($id == Session::get('user_id')) {
            $this->jsonResponse(false, 'No puedes eliminar tu propia cuenta');
            return;
        }

        $db = Database::getInstance();

        // Guard: no eliminar admin raíz (ID 1)
        $db->query("SELECT id, rol_id FROM usuarios WHERE id = :id LIMIT 1");
        $db->bind(':id', $id);
        $usuario = $db->single();

        if (!$usuario) {
            $this->jsonResponse(false, 'El usuario no existe');
            return;
        }
        if ((int)$usuario->rol_id === 1 && (int)$id === 1) {
            $this->jsonResponse(false, 'No se puede eliminar el administrador raíz del sistema');
            return;
        }

        // Si es pasante (rol 3), cascade completo
        if ((int)$usuario->rol_id === 3) {
            foreach ([
                "DELETE FROM asistencias  WHERE pasante_id = :id",
                "DELETE FROM evaluaciones WHERE pasante_id = :id",
                "DELETE FROM datos_pasante WHERE usuario_id = :id",
            ] as $sql) {
                $db->query($sql);
                $db->bind(':id', $id);
                $db->execute();
            }
        } else {
            // Tutor/admin: desvincular pasantes antes de borrar
            $db->query("UPDATE datos_pasante SET tutor_id = NULL WHERE tutor_id = :id");
            $db->bind(':id', $id);
            $db->execute();
        }

        $db->query("DELETE FROM datos_personales WHERE usuario_id = :id");
        $db->bind(':id', $id);
        $db->execute();

        $db->query("DELETE FROM usuarios WHERE id = :id");
        $db->bind(':id', $id);

        if ($db->execute()) {
            AuditModel::log('DELETE_USER_PERMANENT', 'usuarios', $id, ['tipo' => 'hard_delete']);
            $this->jsonResponse(true, 'Usuario eliminado permanentemente');
        } else {
            $this->jsonResponse(false, 'Error al eliminar el usuario');
        }
    }

    /**
     * Get full user details for profile view (AJAX)
     */
    public function obtenerDetalles($id)
    {
        // Validate ID (allow plain ID for AJAX GET)
        $id = filter_var($id, FILTER_VALIDATE_INT);
        
        if (!$id) {
            $this->jsonResponse(false, 'ID de usuario inválido o manipulado');
        }
        
        $config = require '../app/config/config.php';
        $db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
        
        $db->query("
            SELECT 
                u.id, u.cedula, u.correo, u.rol_id, u.estado,
                dp.nombres, dp.apellidos, dp.telefono, dp.genero, dp.fecha_nacimiento,
                r.nombre as rol_nombre,
                i.nombre as nombre_institucion
            FROM usuarios u
            LEFT JOIN datos_personales dp ON u.id = dp.usuario_id
            LEFT JOIN roles r ON u.rol_id = r.id
            LEFT JOIN datos_pasante dpas ON u.id = dpas.usuario_id
            LEFT JOIN instituciones i ON dpas.institucion_procedencia = i.id
            WHERE u.id = :id
        ");
        $db->bind(':id', $id);
        $user = $db->single();
        
        if ($user) {
            // Format some fields for presentation
            $user->nombre_completo = trim(($user->nombres ?? '') . ' ' . ($user->apellidos ?? ''));
            $user->fecha_nacimiento_formateada = $user->fecha_nacimiento ? date('d/m/Y', strtotime($user->fecha_nacimiento)) : 'No registrada';
            $user->genero_texto = [
                'M' => 'Masculino',
                'F' => 'Femenino',
                'Otro' => 'Otro'
            ][$user->genero] ?? 'No especificado';
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $user
            ]);
            exit;
        } else {
            $this->jsonResponse(false, 'Usuario no encontrado');
        }
    }

    /**
     * Toggle user status activo/inactivo (AJAX)
     */
    public function toggleStatus($encrypted_id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
        }

        // [FIX-CS-2] Verificación CSRF
        $this->verifyCsrf();

        // Decrypt and validate ID
        $id = UrlSecurity::validateAndDecrypt($encrypted_id);

        if (!$id) {
            $this->jsonResponse(false, 'URL inválida o enlace manipulado');
        }

        // Prevent self-deactivation
        if ($id == Session::get('user_id')) {
            $this->jsonResponse(false, 'No puedes cambiar el estado de tu propia cuenta');
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->jsonResponse(false, 'Usuario no encontrado');
        }

        $newStatus = $user['estado'] === 'activo' ? 'inactivo' : 'activo';

        $config = require '../app/config/config.php';
        $db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
        $db->query("UPDATE usuarios SET estado = :status WHERE id = :id");
        $db->bind(':status', $newStatus);
        $db->bind(':id', $id);

        if ($db->execute()) {
            AuditModel::log('TOGGLE_USER_STATUS', 'usuarios', $id, ['nuevo_estado' => $newStatus]);
            $this->jsonResponse(true, 'Usuario ' . ($newStatus === 'activo' ? 'activado' : 'desactivado') . ' correctamente');
        } else {
            $this->jsonResponse(false, 'Error al actualizar estado');
        }
    }

    /**
     * AJAX: Búsqueda universal de usuarios (Admin o Tutor)
     * GET /users/buscar?q=texto
     */
    public function buscar()
    {
        header('Content-Type: application/json');
        
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        $config = require '../app/config/config.php';
        $db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado

        // [FIX-M2] Escapar wildcards LIKE ('%', '_') para prevenir full-table scans por DoS
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        $rolFilter = isset($_GET['rol']) ? (int)$_GET['rol'] : 0;
        $rolWhere = $rolFilter > 0 ? ' AND u.rol_id = :rol_filter' : '';
        
        $db->query("
            SELECT u.id, u.cedula, u.rol_id, u.estado,
                   dp.nombres, dp.apellidos,
                   r.nombre AS rol_nombre,
                   -- Para pasantes: dept viene de datos_pasante; para tutores/admins: de usuarios
                   COALESCE(dept_p.nombre, dept_u.nombre) AS departamento,
                   dpa.tutor_id,
                   CONCAT(COALESCE(dp_tutor.nombres,''), ' ', COALESCE(dp_tutor.apellidos,'')) AS tutor_nombre,
                   inst.nombre AS institucion
            FROM usuarios u
            LEFT JOIN datos_personales dp      ON u.id = dp.usuario_id
            LEFT JOIN roles r                  ON u.rol_id = r.id
            LEFT JOIN departamentos dept_u     ON u.departamento_id = dept_u.id
            LEFT JOIN datos_pasante dpa        ON dpa.usuario_id = u.id
            LEFT JOIN departamentos dept_p     ON dpa.departamento_asignado_id = dept_p.id
            LEFT JOIN usuarios u_tutor         ON dpa.tutor_id = u_tutor.id
            LEFT JOIN datos_personales dp_tutor ON u_tutor.id = dp_tutor.usuario_id
            LEFT JOIN instituciones inst        ON dpa.institucion_procedencia = inst.id
            WHERE (u.cedula LIKE :q1 OR dp.nombres LIKE :q2 OR dp.apellidos LIKE :q3) $rolWhere
              AND u.estado = 'activo'
            ORDER BY dp.apellidos ASC, dp.nombres ASC
            LIMIT 15
        ");
        $db->bind(':q1', $like);
        $db->bind(':q2', $like);
        $db->bind(':q3', $like);
        if ($rolFilter > 0) {
            $db->bind(':rol_filter', $rolFilter);
        }
        $results = $db->resultSet();

        $data = [];
        foreach ($results as $r) {
            $tutorNombre = trim($r->tutor_nombre ?? '');
            $data[] = [
                'id'           => $r->id,
                'cedula'       => $r->cedula,
                'nombres'      => $r->nombres ?? '',
                'apellidos'    => $r->apellidos ?? '',
                'rol_id'       => $r->rol_id,
                'rol_nombre'   => $r->rol_nombre ?? 'Sin rol',
                'departamento' => $r->departamento ?? 'Sin asignar',
                'tutor_nombre' => $tutorNombre ?: null,
                'tutor_id'     => $r->tutor_id ?? null,
                'institucion'  => $r->institucion ?? null,
                'estado'       => $r->estado,
                'iniciales'    => strtoupper(substr($r->nombres ?? '?', 0, 1) . substr($r->apellidos ?? '', 0, 1))
            ];
        }

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    /**
     * AJAX: Ver usuario completo para Modal Universal (Admin o Tutor)
     * GET /users/verUniversal/{id}
     */
    public function verUniversal($userId = null)
    {
        header('Content-Type: application/json');

        $id = (int)($userId ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }

        // Permiso: Solo SuperAdmin(0), Admin(1) o Tutor(2). Pasante solo su propio perfil.
        $currentRole = (int)Session::get('role_id');
        $currentUser = (int)Session::get('user_id');
        if ($currentRole === 3 && $id !== $currentUser) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit;
        }

        $user = $this->userModel->getDatosCompletos($id);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            exit;
        }

        $isPasante = ((int)$user->rol_id === 3);
        $horasAcum = (float)($user->horas_acumuladas ?? 0);
        $horasReq  = (float)($user->horas_requeridas ?? 480);
        $porcHoras = $horasReq > 0 ? min(100, round(($horasAcum / $horasReq) * 100)) : 0;

        $data = [
            'id'             => $user->id,
            'cedula'         => $user->cedula,
            'correo'         => $user->correo ?? '',
            'nombres'        => $user->nombres ?? '',
            'apellidos'      => $user->apellidos ?? '',
            'telefono'       => $user->telefono ?? '',
            'genero'         => $user->genero ?? '',
            'cargo'          => $user->cargo ?? '',
            'fecha_nacimiento' => $user->fecha_nacimiento ? date('d/m/Y', strtotime($user->fecha_nacimiento)) : '',
            'rol_id'         => (int)$user->rol_id,
            'rol_nombre'     => $user->rol_nombre ?? 'Sin rol',
            'estado'         => $user->estado,
            'departamento'   => $user->departamento ?? 'Sin asignar',
            'institucion'       => $user->institucion ?? '',
            'inst_rep_nombre'   => $user->inst_rep_nombre   ?? '',
            'inst_rep_cargo'    => $user->inst_rep_cargo    ?? '',
            'inst_rep_correo'   => $user->inst_rep_correo   ?? '',
            'inst_rep_telefono' => $user->inst_rep_telefono ?? '',
            'avatar'            => $user->avatar ?? 'default.png',
            'iniciales'         => strtoupper(substr($user->nombres ?? '?', 0, 1) . substr($user->apellidos ?? '', 0, 1)),
            'es_pasante'        => $isPasante,
            'horas_acumuladas'  => $horasAcum,
            'horas_requeridas'  => $horasReq,
            'porcentaje_horas'  => $porcHoras,
            'estado_pasantia'   => $user->estado_pasantia ?? '',
            'fecha_inicio'      => $user->fecha_inicio ? date('d/m/Y', strtotime($user->fecha_inicio)) : '',
            'fecha_fin'         => $user->fecha_fin ? date('d/m/Y', strtotime($user->fecha_fin)) : '',
            'tutor_nombre'      => $user->tutor_nombre ?? 'Sin asignar',
            'tiene_pin'         => $isPasante
        ];

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    /**
     * AJAX: Buscar pasante por cédula (para Kardex en Reportes)
     * POST /users/buscarPorCedula  { cedula: '12345678' }
     * Solo Admin (1) o Tutor (2)
     */
    public function buscarPorCedula(): void {
        header('Content-Type: application/json');

        // Solo superadmins, admins y tutores
        $role = (int)Session::get('role_id');
        if (!in_array($role, [0, 1, 2])) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit;
        }

        $cedula = trim($_POST['cedula'] ?? '');

        // Normalizar: quitar prefijo V-, v-, E-, etc.
        $cedula = preg_replace('/^[a-zA-Z]-?/', '', $cedula);

        if (empty($cedula) || !preg_match('/^[0-9]{6,9}$/', $cedula)) {
            echo json_encode(['success' => false, 'message' => 'Cédula inválida']);
            exit;
        }

        $p = $this->userModel->findPasanteByCedula($cedula);

        if (!$p) {
            echo json_encode(['success' => false, 'message' => 'No se encontró ningún pasante con esa cédula']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'pasante' => [
                'id'               => (int)$p->id,
                'cedula'           => $p->cedula,
                'nombres'          => $p->nombres ?? '',
                'apellidos'        => $p->apellidos ?? '',
                'correo'           => $p->correo ?? '',
                'departamento'     => $p->departamento ?? '—',
                'estado_pasantia'  => $p->estado_pasantia ?? '—',
                'horas_acumuladas' => (int)($p->horas_acumuladas ?? 0),
                'horas_meta'       => (int)($p->horas_meta ?? 0),
            ]
        ]);
        exit;
    }

    /**
     * PDF: Exportar Ficha Técnica / Kardex (Previsualización)
     */
    public function exportPdf(int $id = 0): void {
        if (!$id) { App::renderError(400); return; }

        $config = require APPROOT . '/config/config.php';
        $db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado

        // Extraer Data
        $db->query("
            SELECT dp.nombres, dp.apellidos, u.cedula, u.correo, u.rol_id, dp.telefono,
                dpa.estado_pasantia, dpa.horas_acumuladas, dpa.horas_meta,
                dpa.fecha_inicio_pasantia, dpa.fecha_fin_estimada,
                COALESCE(inst.nombre, dpa.institucion_procedencia) AS institucion_procedencia,
                d.nombre AS departamento, r.nombre AS nombre_rol, u.estado AS user_estado,
                CONCAT(COALESCE(dp_tutor.nombres,''), ' ', COALESCE(dp_tutor.apellidos,'')) AS tutor_nombre
            FROM usuarios u
            JOIN datos_personales dp ON dp.usuario_id = u.id
            LEFT JOIN datos_pasante dpa ON dpa.usuario_id = u.id
            LEFT JOIN departamentos d ON d.id = dpa.departamento_asignado_id
            LEFT JOIN roles r ON r.id = u.rol_id
            LEFT JOIN instituciones inst ON inst.id = dpa.institucion_id
            LEFT JOIN usuarios u_tutor ON u_tutor.id = dpa.tutor_id
            LEFT JOIN datos_personales dp_tutor ON dp_tutor.usuario_id = u_tutor.id
            WHERE u.id = :id
        ");
        $db->bind(':id', $id);
        $p = $db->single();
        
        if (!$p) { App::renderError(404); return; }

        $isPasante = ($p->rol_id == 3);
        $evals = [];
        if ($isPasante) {
            $db->query("
                SELECT e.fecha_evaluacion, e.lapso_academico, e.promedio_final, e.observaciones,
                       CONCAT(tp.nombres, ' ', tp.apellidos) AS tutor_nombre_eval,
                       DATE_FORMAT(e.fecha_evaluacion, '%d/%m/%Y') AS fecha_formateada
                FROM evaluaciones e
                LEFT JOIN datos_personales tp ON tp.usuario_id = e.tutor_id
                WHERE e.pasante_id = :id ORDER BY e.fecha_evaluacion DESC LIMIT 5
            ");
            $db->bind(':id', $id);
            $evals = $db->resultSet();
        }

        $data = [
            'p' => $p,
            'isPasante' => $isPasante,
            'evals' => $evals
        ];

        $autoload = dirname(APPROOT) . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            $this->renderErrorPage('Dependencia faltante', 'El autoloader de Composer no se encontró. Ejecuta <code>composer install</code>.');
            return;
        }
        require_once $autoload;

        // Configuración Dompdf
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true); 
        $options->set('isHtml5ParserEnabled', true);
        $options->setDefaultFont('Helvetica');
        $dompdf = new \Dompdf\Dompdf($options);

        // Capturar Vista HTML
        ob_start();
        require APPROOT . '/views/users/pdf_profile.php';
        $html = ob_get_clean();

        // Renderizar y forzar previsualización (Attachment => false)
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream("Perfil_Usuario_{$id}.pdf", ["Attachment" => false]);
        exit();
    }

    /**
     * AJAX: Verificar unicidad de cédula o correo (sin exponer datos sensibles)
     * GET /users/checkUnique?campo=cedula&valor=12345678
     * GET /users/checkUnique?campo=correo&valor=user@mail.com&exclude=42
     */
    public function checkUnique(): void
    {
        header('Content-Type: application/json');

        $campo     = $_GET['campo']   ?? '';
        $valor     = trim($_GET['valor'] ?? '');
        $excludeId = (int)($_GET['exclude'] ?? 0);

        if (!in_array($campo, ['cedula', 'correo']) || $valor === '') {
            echo json_encode(['available' => true]);
            exit;
        }

        $db = Database::getInstance();

        if ($campo === 'cedula') {
            // Solo validar formato antes de consultar
            if (!preg_match('/^[0-9]{7,8}$/', $valor)) {
                echo json_encode(['available' => true, 'skip' => true]);
                exit;
            }
            $sql = "SELECT id FROM usuarios WHERE cedula = :val";
        } else {
            if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['available' => true, 'skip' => true]);
                exit;
            }
            $sql = "SELECT id FROM usuarios WHERE correo = :val";
        }

        if ($excludeId) {
            $sql .= " AND id != :eid";
        }

        $db->query($sql);
        $db->bind(':val', $valor);
        if ($excludeId) {
            $db->bind(':eid', $excludeId);
        }

        $exists = $db->single();
        echo json_encode(['available' => !$exists]);
        exit;
    }

    /**
     * JSON response helper
     */
    private function jsonResponse($success, $message)
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
        exit;
    }
}
