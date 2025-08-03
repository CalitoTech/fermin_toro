<?php
require_once __DIR__ . '/../config/conexion.php';
class HistorialAula {
    private $conn;
    public $IdHistorialAula;
    public $IdPersona;
    public $IdAula;
    public $IdFecha_Escolar;
    public $fecha_ingreso;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO historial_aula SET 
            IdPersona = :IdPersona,
            IdFecha_Escolar = :IdFecha_Escolar,
            IdAula = :IdAula,
            fecha_ingreso = :fecha_ingreso";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);
        $stmt->bindParam(":IdFecha_Escolar", $this->IdFecha_Escolar, PDO::PARAM_INT);
        $stmt->bindParam(":IdAula", $this->IdAula, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_ingreso", $this->fecha_ingreso, PDO::PARAM_STR);
        $stmt->execute();

        $stmt = $this->conn->prepare($query);

        $this->IdPersona = htmlspecialchars(strip_tags($this->IdPersona));
        $this->IdAula = htmlspecialchars(strip_tags($this->IdAula));
        $this->IdFecha_Escolar = htmlspecialchars(strip_tags($this->IdFecha_Escolar));
        $this->fecha_ingreso = htmlspecialchars(strip_tags($this->fecha_ingreso));
        
        $stmt->bindParam(":IdPersona", $this->IdPersona);
        $stmt->bindParam(":IdAula", $this->IdAula);
        $stmt->bindParam(":IdFecha_Escolar", $this->IdFecha_Escolar);
        $stmt->bindParam(":fecha_ingreso", $this->fecha_ingreso);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
}
?>