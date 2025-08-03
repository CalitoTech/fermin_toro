<?php
require_once __DIR__ . '/../config/conexion.php';
class Sexo {
    private $conn;
    public $IdSexo;
    public $sexo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM sexo";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>