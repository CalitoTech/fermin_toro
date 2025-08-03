<?php
require_once __DIR__ . '/../config/conexion.php';
class Seccion {
    private $conn;
    public $IdSeccion;
    public $seccion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM seccion";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>