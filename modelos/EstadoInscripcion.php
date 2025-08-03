<?php
require_once __DIR__ . '/../config/conexion.php';
class EstadoInscripcion {
    private $conn;
    public $IdEstado_Inscripcion;
    public $estado_inscripcion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM estado_inscripcion";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>