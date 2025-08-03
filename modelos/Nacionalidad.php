<?php
require_once __DIR__ . '/../config/conexion.php';
class Nacionalidad {
    private $conn;
    public $IdNacionalidad;
    public $nacionalidad;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM nacionalidad";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>