<?php
require_once __DIR__ . '/../config/conexion.php';
class InscripcionRequisito {
    private $conn;
    public $IdInscripcionRequisito;
    public $IdInscripcion;
    public $IdRequisito;
    public $cumplido;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO inscripcion_requisito SET 
            IdInscripcion = :IdInscripcion,
            IdRequisito = :IdRequisito,
            cumplido = :cumplido";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdInscripcion = htmlspecialchars(strip_tags($this->IdInscripcion));
        $this->IdRequisito = htmlspecialchars(strip_tags($this->IdRequisito));
        $this->cumplido = htmlspecialchars(strip_tags($this->cumplido));

        // Vincular valores
        $stmt->bindParam(":IdInscripcion", $this->IdInscripcion);
        $stmt->bindParam(":IdRequisito", $this->IdRequisito);
        $stmt->bindParam(":cumplido", $this->cumplido);

        return $stmt->execute();
    }

    public function obtenerPorInscripcion($idInscripcion) {
        $query = "SELECT ir.*, r.requisito, r.obligatorio 
                 FROM inscripcion_requisito ir
                 JOIN requisito r ON ir.IdRequisito = r.IdRequisito
                 WHERE ir.IdInscripcion = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idInscripcion);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>