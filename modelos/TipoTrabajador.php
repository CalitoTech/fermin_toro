<?php
require_once __DIR__ . '/../config/conexion.php';
class TipoTrabajador {
    private $conn;
    public $IdTipoTrabajador;
    public $tipo_trabajador;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM tipo_trabajador ORDER BY IdTipoTrabajador";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM tipo_trabajador WHERE IdTipoTrabajador = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
