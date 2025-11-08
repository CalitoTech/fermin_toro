<?php
require_once __DIR__ . '/../config/conexion.php';

class FechaEscolar {
    private $conexion;
    public $IdFecha_Escolar;
    public $fecha_escolar;
    public $fecha_activa;
    public $inscripcion_activa;
    public $renovacion_activa;

    public function __construct($conexionPDO) {
        $this->conexion = $conexionPDO;
    }

    /**
     * Obtiene el año escolar activo
     * @return array|null Datos del año escolar activo o null si no hay
     */
    public function obtenerActivo() {
        $consulta = "SELECT * FROM fecha_escolar WHERE fecha_activa = TRUE LIMIT 1";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los años escolares ordenados por IdFechaEscolar DESC
     * @return array Lista de años escolares
     */
    public function obtenerTodos() {
        $consulta = "SELECT * FROM fecha_escolar ORDER BY IdFecha_Escolar DESC";
        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM fecha_escolar WHERE IdFecha_Escolar = :id LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdFecha_Escolar = $row['IdFecha_Escolar'];
            $this->fecha_escolar = $row['fecha_escolar'];
            $this->fecha_activa = $row['fecha_activa'];
            $this->inscripcion_activa = $row['inscripcion_activa'];
            $this->renovacion_activa = $row['renovacion_activa'];
            return $row;
        }
        
        return false;
    }

    public function guardar() {

        if (!isset($this->fecha_activa)) {
        $this->fecha_activa = 0;
        }
        if (!isset($this->inscripcion_activa)) {
            $this->inscripcion_activa = 0;
        }
        if (!isset($this->renovacion_activa)) {
            $this->renovacion_activa = 0;
        }

        $query = "INSERT INTO fecha_escolar (fecha_escolar, fecha_activa, inscripcion_activa, renovacion_activa)
        VALUES (:fecha_escolar, :fecha_activa, :inscripcion_activa, :renovacion_activa)";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':fecha_escolar', $this->fecha_escolar);
        $stmt->bindParam(':fecha_activa', $this->fecha_activa);
        $stmt->bindParam(':inscripcion_activa', $this->inscripcion_activa);
        $stmt->bindParam(':renovacion_activa', $this->renovacion_activa);
        
        if ($stmt->execute()) {
            $this->IdFecha_Escolar = $this->conexion->lastInsertId();
            return $this->IdFecha_Escolar;
        }
        return false;
    }
    
    public function actualizar() {
        $query = "UPDATE fecha_escolar SET fecha_escolar = :fecha_escolar,
        fecha_activa = :fecha_activa, inscripcion_activa = :inscripcion_activa,
        renovacion_activa = :renovacion_activa WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':fecha_escolar', $this->fecha_escolar);
        $stmt->bindParam(':fecha_activa', $this->fecha_activa);
        $stmt->bindParam(':inscripcion_activa', $this->inscripcion_activa);
        $stmt->bindParam(':renovacion_activa', $this->renovacion_activa);
        $stmt->bindParam(':id', $this->IdFecha_Escolar);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar en múltiples tablas
        $tablas = ['horario', 'inscripcion', 'grupo_interes']; // Agrega todas las tablas relevantes
        
        foreach ($tablas as $tabla) {
            $query = "SELECT COUNT(*) as total FROM $tabla WHERE IdFecha_Escolar = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $this->IdFecha_Escolar);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['total'] > 0) {
                return true;
            }
        }
        
        return false;
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM fecha_escolar WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $this->IdFecha_Escolar);
        return $stmt->execute();
    }

    public function activarFechaEscolar($id) {
        try {
            // Iniciar transacción
            $this->conexion->beginTransaction();

            // 1. Desactivar todos los años escolares
            $queryDesactivar = "UPDATE fecha_escolar SET fecha_activa = 0, inscripcion_activa = 0, renovacion_activa = 0";
            $stmtDesactivar = $this->conexion->prepare($queryDesactivar);
            $stmtDesactivar->execute();

            // 2. Activar el año escolar específico
            $queryActivar = "UPDATE fecha_escolar SET fecha_activa = 1, inscripcion_activa = 1, renovacion_activa = 1 WHERE IdFecha_Escolar = :id";
            $stmtActivar = $this->conexion->prepare($queryActivar);
            $stmtActivar->bindParam(':id', $id);
            $stmtActivar->execute();

            // Confirmar transacción
            $this->conexion->commit();
            return true;

        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $this->conexion->rollBack();
            throw $e;
        }
    }

    /**
     * Activa o desactiva solo la inscripción del año escolar
     * @param int $id ID del año escolar
     * @param bool $estado Nuevo estado (1 o 0)
     * @return bool Éxito o fracaso
     */
    public function actualizarInscripcion($id, $estado) {
        // Validar que el año escolar esté activo
        $query = "SELECT fecha_activa FROM fecha_escolar WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['fecha_activa'] != 1) {
            return false; // Solo se puede cambiar si está activo
        }

        // Actualizar inscripcion_activa
        $update = "UPDATE fecha_escolar SET inscripcion_activa = :estado WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($update);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Activa o desactiva solo la renovación de cupo del año escolar
     * @param int $id ID del año escolar
     * @param bool $estado Nuevo estado (1 o 0)
     * @return bool Éxito o fracaso
     */
    public function actualizarRenovacion($id, $estado) {
        // Validar que el año escolar esté activo
        $query = "SELECT fecha_activa FROM fecha_escolar WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['fecha_activa'] != 1) {
            return false; // Solo se puede cambiar si está activo
        }

        // Actualizar renovacion_activa
        $update = "UPDATE fecha_escolar SET renovacion_activa = :estado WHERE IdFecha_Escolar = :id";
        $stmt = $this->conexion->prepare($update);
        $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

}
?>