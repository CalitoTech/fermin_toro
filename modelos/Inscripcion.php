<?php
require_once __DIR__ . '/../config/conexion.php';

class Inscripcion {
    private $conn;
    public $IdInscripcion;
    public $IdEstudiante;
    public $fecha_inscripcion;
    public $ultimo_plantel;
    public $nro_hermanos;
    public $responsable_inscripcion;
    public $IdFecha_Escolar;
    public $IdEstado_Inscripcion;
    public $IdCurso;
    public $codigo_inscripcion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO inscripcion (
            IdEstudiante, codigo_inscripcion, fecha_inscripcion, ultimo_plantel, nro_hermanos,
            responsable_inscripcion, IdFecha_Escolar, IdEstado_Inscripcion, IdCurso
        ) VALUES (
            :IdEstudiante, :codigo_inscripcion, :fecha_inscripcion, :ultimo_plantel, :nro_hermanos,
            :responsable_inscripcion, :IdFecha_Escolar, :IdEstado_Inscripcion, :IdCurso
        )";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos (excepto fechas que necesitan formato específico)
        $this->IdEstudiante = htmlspecialchars(strip_tags($this->IdEstudiante));
        $this->codigo_inscripcion = htmlspecialchars(strip_tags($this->codigo_inscripcion));
        $this->ultimo_plantel = htmlspecialchars(strip_tags($this->ultimo_plantel));
        $this->nro_hermanos = htmlspecialchars(strip_tags($this->nro_hermanos));
        $this->responsable_inscripcion = htmlspecialchars(strip_tags($this->responsable_inscripcion));
        $this->IdFecha_Escolar = htmlspecialchars(strip_tags($this->IdFecha_Escolar));
        $this->IdEstado_Inscripcion = htmlspecialchars(strip_tags($this->IdEstado_Inscripcion));
        $this->IdCurso = htmlspecialchars(strip_tags($this->IdCurso));

        // Validar formato de fecha si viene desde fuera
        if ($this->fecha_inscripcion && !$this->validarFecha($this->fecha_inscripcion)) {
            throw new Exception("Formato de fecha inválido");
        }

        // Vincular valores
        $stmt->bindParam(":IdEstudiante", $this->IdEstudiante, PDO::PARAM_INT);
        $stmt->bindParam(":codigo_inscripcion", $this->codigo_inscripcion, PDO::PARAM_STR);
        $stmt->bindParam(":fecha_inscripcion", $this->fecha_inscripcion, PDO::PARAM_STR);
        $stmt->bindParam(":ultimo_plantel", $this->ultimo_plantel, PDO::PARAM_STR);
        $stmt->bindParam(":nro_hermanos", $this->nro_hermanos, PDO::PARAM_INT);
        $stmt->bindParam(":responsable_inscripcion", $this->responsable_inscripcion, PDO::PARAM_INT);
        $stmt->bindParam(":IdFecha_Escolar", $this->IdFecha_Escolar, PDO::PARAM_INT);
        $stmt->bindParam(":IdEstado_Inscripcion", $this->IdEstado_Inscripcion, PDO::PARAM_INT);
        $stmt->bindParam(":IdCurso", $this->IdCurso, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->IdInscripcion = $this->conn->lastInsertId();
            return $this->IdInscripcion;
        }
        return false;
    }

    private function validarFecha($fecha) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $fecha);
        return $d && $d->format('Y-m-d H:i:s') === $fecha;
    }

    public function obtenerPorEstudiante($idEstudiante) {
        $query = "SELECT i.*, e.estado_inscripcion, f.fecha_escolar 
                 FROM inscripcion i
                 JOIN estado_inscripcion e ON i.IdEstado_Inscripcion = e.IdEstado_Inscripcion
                 JOIN fecha_escolar f ON i.IdFecha_Escolar = f.IdFecha_Escolar
                 WHERE i.IdEstudiante = :IdEstudiante
                 ORDER BY i.fecha_inscripcion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":IdEstudiante", $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($idInscripcion) {
        $query = "SELECT i.*, e.estado_inscripcion, f.fecha_escolar 
                 FROM inscripcion i
                 JOIN estado_inscripcion e ON i.IdEstado_Inscripcion = e.IdEstado_Inscripcion
                 JOIN fecha_escolar f ON i.IdFecha_Escolar = f.IdFecha_Escolar
                 WHERE i.IdInscripcion = :IdInscripcion
                 LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":IdInscripcion", $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            // Asignar propiedades si se encuentra el registro
            foreach ($resultado as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
        
        return $resultado;
    }

    // Método adicional para actualizar estado de inscripción
    public function actualizarEstado($idInscripcion, $nuevoEstado) {
        $query = "UPDATE inscripcion SET IdEstado_Inscripcion = :estado WHERE IdInscripcion = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":estado", $nuevoEstado, PDO::PARAM_INT);
        $stmt->bindParam(":id", $idInscripcion, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}