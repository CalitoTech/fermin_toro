<?php
require_once __DIR__ . '/../config/conexion.php';
class Dia {
    private $conn;
    public $IdDia;
    public $dia;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM dia";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>