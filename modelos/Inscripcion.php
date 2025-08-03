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

        // Limpiar datos
        $this->IdEstudiante = htmlspecialchars(strip_tags($this->IdEstudiante));
        $this->codigo_inscripcion = htmlspecialchars(strip_tags($this->codigo_inscripcion));
        $this->fecha_inscripcion = htmlspecialchars(strip_tags($this->fecha_inscripcion));
        $this->ultimo_plantel = htmlspecialchars(strip_tags($this->ultimo_plantel));
        $this->nro_hermanos = htmlspecialchars(strip_tags($this->nro_hermanos));
        $this->responsable_inscripcion = htmlspecialchars(strip_tags($this->responsable_inscripcion));
        $this->IdFecha_Escolar = htmlspecialchars(strip_tags($this->IdFecha_Escolar));
        $this->IdEstado_Inscripcion = htmlspecialchars(strip_tags($this->IdEstado_Inscripcion));

        // Vincular valores
        $stmt->bindParam(":IdEstudiante", $this->IdEstudiante);
        $stmt->bindParam(":codigo_inscripcion", $this->codigo_inscripcion);
        $stmt->bindParam(":fecha_inscripcion", $this->fecha_inscripcion);
        $stmt->bindParam(":ultimo_plantel", $this->ultimo_plantel);
        $stmt->bindParam(":nro_hermanos", $this->nro_hermanos);
        $stmt->bindParam(":responsable_inscripcion", $this->responsable_inscripcion);
        $stmt->bindParam(":IdFecha_Escolar", $this->IdFecha_Escolar);
        $stmt->bindParam(":IdEstado_Inscripcion", $this->IdEstado_Inscripcion);
        $stmt->bindParam(":IdCurso", $this->IdCurso);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function obtenerPorEstudiante($idEstudiante) {
        $query = "SELECT i.*, e.estado_inscripcion, f.fecha_escolar 
                 FROM inscripcion i
                 JOIN estado_inscripcion e ON i.IdEstado_Inscripcion = e.IdEstado_Inscripcion
                 JOIN fecha_escolar f ON i.IdFecha_Escolar = f.IdFecha_Escolar
                 WHERE i.IdEstudiante = :IdEstudiante
                 ORDER BY i.fecha_inscripcion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":IdEstudiante", $idEstudiante);
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
        $stmt->bindParam(":IdInscripcion", $idInscripcion);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>