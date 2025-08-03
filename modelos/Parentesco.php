<?php
require_once __DIR__ . '/../config/conexion.php';
class Parentesco {
    private $conn;
    public $IdParentesco;
    public $parentesco;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM parentesco";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>