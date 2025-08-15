<?php
class Nivel {
    private $conexion;
    public $IdNivel;
    public $nivel;

    public function __construct($conexionPDO) {
        $this->conexion = $conexionPDO;
    }

    public function guardar() {
        $query = "INSERT INTO nivel (nivel) VALUES (:nivel)";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':nivel', $this->nivel);
        
        if ($stmt->execute()) {
            $this->IdNivel = $this->conexion->lastInsertId();
            return $this->IdNivel;
        }
        return false;
    }
    
    public function actualizar() {
        $query = "UPDATE nivel SET nivel = :nivel WHERE IdNivel = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':nivel', $this->nivel);
        $stmt->bindParam(':id', $this->IdNivel);
        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM nivel WHERE IdNivel = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $this->IdNivel);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar en múltiples tablas
        $tablas = ['aula', 'requisito']; // Agrega todas las tablas relevantes
        
        foreach ($tablas as $tabla) {
            $query = "SELECT COUNT(*) as total FROM $tabla WHERE IdNivel = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $this->IdNivel);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['total'] > 0) {
                return true;
            }
        }
        
        return false;
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM nivel WHERE IdNivel = :id LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdNivel = $row['IdNivel'];
            $this->nivel = $row['nivel'];
            return $row;
        }
        
        return false;
    }

    public function obtenerTodos() {
        $consulta = "SELECT IdNivel, nivel FROM nivel";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerRequisitos($idNivel) {
        $consulta = "SELECT IdRequisito, requisito, obligatorio 
                     FROM requisito 
                     WHERE IdNivel = ?";
        
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute([$idNivel]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}