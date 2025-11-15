<?php
require_once __DIR__ . '/../config/conexion.php';

class Prefijo {
    private $conn;
    public $IdPrefijo;
    public $codigo_prefijo;
    public $pais;
    public $max_digitos;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO prefijo (codigo_prefijo, pais, max_digitos)
                  VALUES (:codigo_prefijo, :pais, :max_digitos)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':codigo_prefijo', $this->codigo_prefijo);
        $stmt->bindParam(':pais', $this->pais);
        $stmt->bindParam(':max_digitos', $this->max_digitos);

        if ($stmt->execute()) {
            $this->IdPrefijo = $this->conn->lastInsertId();
            return $this->IdPrefijo;
        }
        return false;
    }

    public function actualizar() {
        $query = "UPDATE prefijo
                  SET codigo_prefijo = :codigo_prefijo,
                      pais = :pais,
                      max_digitos = :max_digitos
                  WHERE IdPrefijo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':codigo_prefijo', $this->codigo_prefijo);
        $stmt->bindParam(':pais', $this->pais);
        $stmt->bindParam(':max_digitos', $this->max_digitos);
        $stmt->bindParam(':id', $this->IdPrefijo);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el prefijo está siendo usado en la tabla telefono
        $query = "SELECT COUNT(*) as total FROM telefono WHERE IdPrefijo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdPrefijo);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($resultado['total'] > 0);
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM prefijo WHERE IdPrefijo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdPrefijo);
        return $stmt->execute();
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM prefijo ORDER BY pais, codigo_prefijo";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM prefijo WHERE IdPrefijo = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->IdPrefijo = $row['IdPrefijo'];
            $this->codigo_prefijo = $row['codigo_prefijo'];
            $this->pais = $row['pais'];
            $this->max_digitos = $row['max_digitos'];
            return $row;
        }

        return false;
    }

    public function obtenerPorCodigo($codigo) {
        $query = "SELECT * FROM prefijo WHERE codigo_prefijo = :codigo LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':codigo', $codigo);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->IdPrefijo = $row['IdPrefijo'];
            $this->codigo_prefijo = $row['codigo_prefijo'];
            $this->pais = $row['pais'];
            $this->max_digitos = $row['max_digitos'];
            return $row;
        }

        return false;
    }
}
?>
