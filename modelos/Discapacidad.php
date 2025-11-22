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
        // Sanitizar
        $this->discapacidad = trim($this->discapacidad === null ? '' : $this->discapacidad);
        $this->discapacidad = htmlspecialchars(strip_tags($this->discapacidad));
        $this->IdPersona = (int)$this->IdPersona;
        $this->IdTipo_Discapacidad = (int)$this->IdTipo_Discapacidad;

        if (empty($this->discapacidad) && $this->IdTipo_Discapacidad <= 0) {
            // Nada que guardar
            return false;
        }

        // Verificar si ya existe la misma discapacidad para la persona (silencioso)
        $checkSql = "SELECT IdDiscapacidad FROM discapacidad WHERE IdPersona = :IdPersona AND IdTipo_Discapacidad = :IdTipo LIMIT 1";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bindParam(':IdPersona', $this->IdPersona, PDO::PARAM_INT);
        $checkStmt->bindParam(':IdTipo', $this->IdTipo_Discapacidad, PDO::PARAM_INT);
        $checkStmt->execute();
        $found = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($found) {
            // Ya existe -> no insertar de nuevo
            return (int)$found['IdDiscapacidad'];
        }

        $query = "INSERT INTO discapacidad (
            discapacidad, IdPersona, IdTipo_Discapacidad
        ) VALUES (
            :discapacidad, :IdPersona, :IdTipo_Discapacidad
        )";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":discapacidad", $this->discapacidad);
        $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);
        $stmt->bindParam(":IdTipo_Discapacidad", $this->IdTipo_Discapacidad, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Error al guardar discapacidad: " . $e->getMessage());
            return false;
        }
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