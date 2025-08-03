<?php
require_once __DIR__ . '/../config/conexion.php';
class Dificultad {
    private $conn;
    public $IdDificultad;
    public $visual;
    public $auditiva;
    public $motora;
    public $es_alergico;
    public $alergia;
    public $tiene_enfermedad;
    public $enfermedad;
    public $IdPersona;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO dificultad (
            visual, auditiva, motora, es_alergico, alergia, 
            tiene_enfermedad, enfermedad, IdPersona
        ) VALUES (
            :visual, :auditiva, :motora, :es_alergico, :alergia, 
            :tiene_enfermedad, :enfermedad, :IdPersona
        )";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->visual = htmlspecialchars(strip_tags($this->visual));
        $this->auditiva = htmlspecialchars(strip_tags($this->auditiva));
        $this->motora = htmlspecialchars(strip_tags($this->motora));
        $this->es_alergico = htmlspecialchars(strip_tags($this->es_alergico));
        $this->alergia = htmlspecialchars(strip_tags($this->alergia));
        $this->tiene_enfermedad = htmlspecialchars(strip_tags($this->tiene_enfermedad));
        $this->enfermedad = htmlspecialchars(strip_tags($this->enfermedad));
        $this->IdPersona = htmlspecialchars(strip_tags($this->IdPersona));

        // Vincular valores
        $stmt->bindParam(":visual", $this->visual);
        $stmt->bindParam(":auditiva", $this->auditiva);
        $stmt->bindParam(":motora", $this->motora);
        $stmt->bindParam(":es_alergico", $this->es_alergico);
        $stmt->bindParam(":alergia", $this->alergia);
        $stmt->bindParam(":tiene_enfermedad", $this->tiene_enfermedad);
        $stmt->bindParam(":enfermedad", $this->enfermedad);
        $stmt->bindParam(":IdPersona", $this->IdPersona);

        return $stmt->execute();
    }
}
?>