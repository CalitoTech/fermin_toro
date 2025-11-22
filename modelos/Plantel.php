<?php
require_once __DIR__ . '/../config/conexion.php';
class Plantel {
    private $conn;
    public $IdPlantel;
    public $plantel;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM plantel ORDER BY plantel ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM plantel WHERE IdPlantel = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insertar() {
        $query = "INSERT INTO plantel (plantel) VALUES (:plantel)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':plantel', $this->plantel);

        if ($stmt->execute()) {
            $this->IdPlantel = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function buscarPorNombre($nombre) {
        $query = "SELECT * FROM plantel WHERE plantel LIKE :nombre ORDER BY plantel ASC";
        $stmt = $this->conn->prepare($query);
        $nombreBusqueda = '%' . $nombre . '%';
        $stmt->bindParam(':nombre', $nombreBusqueda);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
