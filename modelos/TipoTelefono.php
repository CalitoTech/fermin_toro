<?php
require_once __DIR__ . '/../config/conexion.php';
class TipoTelefono {
    private $conn;
    public $IdTipo_Telefono;
    public $tipo_telefono;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM tipo_telefono";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>