<?php
require_once __DIR__ . '/../config/conexion.php';
class Bloque {
    private $conn;
    public $IdBloque;
    public $hora_inicio;
    public $hora_fin;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM bloque ORDER BY hora_inicio";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar() {
        $query = "INSERT INTO bloque SET 
            hora_inicio = :hora_inicio,
            hora_fin = :hora_fin";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->hora_inicio = htmlspecialchars(strip_tags($this->hora_inicio));
        $this->hora_fin = htmlspecialchars(strip_tags($this->hora_fin));

        // Vincular valores
        $stmt->bindParam(":hora_inicio", $this->hora_inicio);
        $stmt->bindParam(":hora_fin", $this->hora_fin);

        return $stmt->execute();
    }
}
?>