<?php
require_once __DIR__ . '/../config/conexion.php';

class TipoDiscapacidad {
    private $conn;
    public $IdTipo_Discapacidad;
    public $tipo_discapacidad;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Obtener todos los tipos de discapacidad
    public function obtenerTodos() {
        $query = "SELECT * FROM tipo_discapacidad ORDER BY tipo_discapacidad";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un tipo por ID
    public function obtenerPorId($id) {
        $query = "SELECT * FROM tipo_discapacidad WHERE IdTipo_Discapacidad = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>