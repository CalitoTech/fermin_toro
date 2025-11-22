<?php
require_once __DIR__ . '/../config/conexion.php';

class TipoInscripcion {
    private $conn;
    public $IdTipo_Inscripcion;
    public $tipo_inscripcion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT IdTipo_Inscripcion, tipo_inscripcion FROM tipo_inscripcion ORDER BY IdTipo_Inscripcion ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT IdTipo_Inscripcion, tipo_inscripcion FROM tipo_inscripcion WHERE IdTipo_Inscripcion = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
