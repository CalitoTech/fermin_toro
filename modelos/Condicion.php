<?php
require_once __DIR__ . '/../config/conexion.php';
class Condicion {
    private $conn;
    public $IdCondicion;
    public $condicion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM condicion";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>