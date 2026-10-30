<?php
/**
 * DepartamentoModel - Gestión de Departamentos
 * 
 * PROPÓSITO:
 * Modelo simple para obtener la lista de departamentos del
 * Instituto de Salud Pública. Se usa principalmente para
 * llenar los formularios.

 */

class DepartamentoModel
{
    private $db;

    public function __construct()
    {
        $config = require '../app/config/config.php';
        $this->db = Database::getInstance(); // SGP-FIX-v2 [6/2.1] aplicado
    }

    /**
     * Obtener Todos los Departamentos
     * 
     * PROPÓSITO:
     * Retornar lista de departamentos activos para llenar
     * selectores en formularios (ej: asignación de pasantes).
     * 
     * @return array Lista de departamentos
     */
    public function getAll(): array
    {
        $this->db->query("
            SELECT 
                id,
                nombre,
                descripcion
            FROM departamentos
            WHERE activo = 1
            ORDER BY nombre ASC
        ");
        
        return $this->db->resultSet();
    }

    /**
     * Obtener Departamento por ID
     * 
     * @param int $id ID del departamento
     * @return object|null Departamento o null si no existe
     */
    public function getById(int $id): ?object
    {
        $this->db->query("
            SELECT 
                id,
                nombre,
                descripcion,
                activo
            FROM departamentos
            WHERE id = :id
            LIMIT 1
        ");
        
        $this->db->bind(':id', $id);
        
        return $this->db->single();
    }
}
