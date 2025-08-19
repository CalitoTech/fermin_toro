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

    public function actualizar() {
        $query = "UPDATE requisito SET requisito = :requisito, IdNivel = :nivel, obligatorio = :obligatorio
        WHERE IdRequisito = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':requisito', $this->requisito);
        $stmt->bindParam(':nivel', $this->IdNivel);
        $stmt->bindParam(':obligatorio', $this->obligatorio);
        $stmt->bindParam(':id', $this->IdRequisito);
        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM requisito WHERE IdRequisito = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdRequisito);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el requisito está siendo usado en la tabla requisito_seccion
        $query = "SELECT COUNT(*) as total FROM inscripcion_requisito WHERE IdRequisito = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdRequisito);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM requisito WHERE IdRequisito = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdRequisito = $row['IdRequisito'];
            $this->requisito = $row['requisito'];
            $this->obligatorio = $row['obligatorio'];
            return $row;
        }
        
        return false;
    }
}
?>