<?php
require_once __DIR__ . '/../config/conexion.php';
class Materia {
    private $conn;
    public $IdMateria;
    public $materia;
    public $IdNivel;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerPorNivel($idNivel) {
        $query = "SELECT * FROM materia WHERE IdNivel = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idNivel);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar() {
        $query = "INSERT INTO materia SET 
            materia = :materia,
            IdNivel = :IdNivel";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->materia = htmlspecialchars(strip_tags($this->materia));
        $this->IdNivel = htmlspecialchars(strip_tags($this->IdNivel));

        // Vincular valores
        $stmt->bindParam(":materia", $this->materia);
        $stmt->bindParam(":IdNivel", $this->IdNivel);

        return $stmt->execute();
    }
}
?>