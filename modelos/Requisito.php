<?php
require_once __DIR__ . '/../config/conexion.php';
class Requisito {
    private $conn;
    public $IdRequisito;
    public $requisito;
    public $obligatorio;
    public $IdNivel;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerPorNivel($idNivel) {
        $query = "SELECT * FROM requisito WHERE IdNivel = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idNivel);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar() {
        $query = "INSERT INTO requisito SET 
            requisito = :requisito,
            obligatorio = :obligatorio,
            IdNivel = :IdNivel";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->requisito = htmlspecialchars(strip_tags($this->requisito));
        $this->obligatorio = htmlspecialchars(strip_tags($this->obligatorio));
        $this->IdNivel = htmlspecialchars(strip_tags($this->IdNivel));

        // Vincular valores
        $stmt->bindParam(":requisito", $this->requisito);
        $stmt->bindParam(":obligatorio", $this->obligatorio);
        $stmt->bindParam(":IdNivel", $this->IdNivel);

        return $stmt->execute();
    }
}
?>