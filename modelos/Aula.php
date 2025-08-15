<?php
require_once __DIR__ . '/../config/conexion.php';

class Aula {
    private $conn;
    public $IdAula;
    public $IdNivel;
    public $aula;
    public $capacidad;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO aula (aula, capacidad, IdNivel) VALUES (:aula, :capacidad, :IdNivel)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':aula', $this->aula);
        $stmt->bindParam(':capacidad', $this->capacidad);
        $stmt->bindParam(':IdNivel', $this->IdNivel);
        
        if ($stmt->execute()) {
            $this->IdAula = $this->conn->lastInsertId();
            return $this->IdAula;
        }
        return false;
    }

    public function actualizar() {
        $query = "UPDATE aula SET aula = :aula, capacidad = :capacidad, IdNivel = :IdNivel 
                 WHERE IdAula = :IdAula";
        
        $stmt = $this->conn->prepare($query);
        
        $this->aula = htmlspecialchars(strip_tags($this->aula));
        $this->capacidad = htmlspecialchars(strip_tags($this->capacidad));
        $this->IdNivel = htmlspecialchars(strip_tags($this->IdNivel));
        $this->IdAula = htmlspecialchars(strip_tags($this->IdAula));

        $stmt->bindParam(":aula", $this->aula);
        $stmt->bindParam(":capacidad", $this->capacidad, PDO::PARAM_INT);
        $stmt->bindParam(":IdNivel", $this->IdNivel, PDO::PARAM_INT);
        $stmt->bindParam(":IdAula", $this->IdAula, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM aula WHERE IdAula = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdAula);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el aula está siendo usado en la tabla curso_seccion
        $query = "SELECT COUNT(*) as total FROM curso_seccion WHERE IdAula = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdAula);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM aula WHERE IdAula = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdAula = $row['IdAula'];
            $this->aula = $row['aula'];
            return $row;
        }
        
        return false;
    }

    public function obtenerTodos() {
        $query = "SELECT a.*, n.nivel 
                 FROM aula a
                 JOIN nivel n ON a.IdNivel = n.IdNivel";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorNivel($idNivel) {
        $query = "SELECT * FROM aula WHERE IdNivel = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idNivel, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerDisponiblesPorNivel($idNivel) {
        $query = "SELECT a.* FROM aula a
                 LEFT JOIN curso_seccion cs ON a.IdAula = cs.IdAula
                 WHERE a.IdNivel = ? AND (cs.IdAula IS NULL OR cs.IdCurso_Seccion IS NULL)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idNivel, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}