<?php
require_once __DIR__ . '/../config/conexion.php';
class Plantel {
    private $conn;
    public $IdPlantel;
    public $plantel;
    public $es_privado;

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

    public function guardar() {
        $query = "INSERT INTO plantel (plantel, es_privado) VALUES (:plantel, :es_privado)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':plantel', $this->plantel);
        $esPrivado = $this->es_privado ? 1 : 0;
        $stmt->bindParam(':es_privado', $esPrivado, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->IdPlantel = $this->conn->lastInsertId();
            return $this->IdPlantel;
        }
        return false;
    }

    public function actualizar() {
        $query = "UPDATE plantel SET plantel = :plantel, es_privado = :es_privado WHERE IdPlantel = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':plantel', $this->plantel);
        $esPrivado = $this->es_privado ? 1 : 0;
        $stmt->bindParam(':es_privado', $esPrivado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->IdPlantel);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el plantel está siendo usado en la tabla inscripcion
        $query = "SELECT COUNT(*) as total FROM inscripcion WHERE ultimo_plantel = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdPlantel);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($resultado['total'] > 0);
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM plantel WHERE IdPlantel = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdPlantel);
        return $stmt->execute();
    }
}
?>
