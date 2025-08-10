<?php
require_once __DIR__ . '/../config/conexion.php';

class Discapacidad {
    private $conn;
    public $IdDiscapacidad;
    public $discapacidad;
    public $IdPersona;
    public $IdTipo_Discapacidad;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Guardar una discapacidad
    public function guardar() {
        $query = "INSERT INTO discapacidad (
            discapacidad, IdPersona, IdTipo_Discapacidad
        ) VALUES (
            :discapacidad, :IdPersona, :IdTipo_Discapacidad
        )";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->discapacidad = htmlspecialchars(strip_tags($this->discapacidad));
        $this->IdPersona = htmlspecialchars(strip_tags($this->IdPersona));
        $this->IdTipo_Discapacidad = htmlspecialchars(strip_tags($this->IdTipo_Discapacidad));

        // Vincular valores
        $stmt->bindParam(":discapacidad", $this->discapacidad);
        $stmt->bindParam(":IdPersona", $this->IdPersona);
        $stmt->bindParam(":IdTipo_Discapacidad", $this->IdTipo_Discapacidad);

        return $stmt->execute();
    }

    // Obtener discapacidades por persona
    public function obtenerPorPersona($idPersona) {
        $query = "SELECT d.*, t.tipo_discapacidad 
                 FROM discapacidad d
                 JOIN tipo_discapacidad t ON d.IdTipo_Discapacidad = t.IdTipo_Discapacidad
                 WHERE d.IdPersona = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idPersona);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Eliminar discapacidades de una persona
    public function eliminarPorPersona($idPersona) {
        $query = "DELETE FROM discapacidad WHERE IdPersona = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idPersona);
        return $stmt->execute();
    }
}
?>