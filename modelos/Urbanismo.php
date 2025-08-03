<?php
require_once __DIR__ . '/../config/conexion.php';
class Urbanismo {
    private $conn;
    public $IdUrbanismo;
    public $urbanismo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM urbanismo";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>