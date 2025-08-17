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

    public function actualizar() {
        $query = "UPDATE materia SET materia = :materia, IdNivel = :nivel WHERE IdMateria = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':materia', $this->materia);
        $stmt->bindParam(':nivel', $this->IdNivel);
        $stmt->bindParam(':id', $this->IdMateria);
        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM materia WHERE IdMateria = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdMateria);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el materia está siendo usado en la tabla materia_seccion
        $query = "SELECT COUNT(*) as total FROM horario WHERE IdMateria = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdMateria);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM materia WHERE IdMateria = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdMateria = $row['IdMateria'];
            $this->materia = $row['materia'];
            return $row;
        }
        
        return false;
    }

    public function obtenerPorNivel($idNivel) {
        $query = "SELECT * FROM materia WHERE IdNivel = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idNivel);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>