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
    
    public function actualizar() {
        $query = "UPDATE bloque SET 
            hora_inicio = :hora_inicio,
            hora_fin = :hora_fin
            WHERE IdBloque = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hora_inicio', $this->hora_inicio);
        $stmt->bindParam(':hora_fin', $this->hora_fin);
        $stmt->bindParam(':id', $this->IdBloque);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el bloque está siendo usado en la tabla horario
        $query = "SELECT COUNT(*) as total FROM horario WHERE IdBloque = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdBloque);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM bloque WHERE IdBloque = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdBloque);
        return $stmt->execute();
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM bloque WHERE IdBloque = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdBloque = $row['IdBloque'];
            $this->hora_inicio = $row['hora_inicio'];
            $this->hora_fin = $row['hora_fin'];
            return $row;
        }
        
        return false;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM bloque ORDER BY hora_inicio";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>