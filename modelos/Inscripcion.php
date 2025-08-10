<?php
require_once __DIR__ . '/../config/conexion.php';

class Inscripcion {
    private $conn;
    public $IdInscripcion;
    public $codigo_inscripcion;
    public $IdEstudiante;
    public $fecha_inscripcion;
    public $ultimo_plantel;
    public $nro_hermanos;
    public $responsable_inscripcion;
    public $IdFecha_Escolar;
    public $IdStatus;
    public $IdCurso_Seccion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO inscripcion (
            codigo_inscripcion, IdEstudiante, fecha_inscripcion, ultimo_plantel, 
            nro_hermanos, responsable_inscripcion, IdFecha_Escolar, IdStatus, IdCurso_Seccion
        ) VALUES (
            :codigo_inscripcion, :IdEstudiante, :fecha_inscripcion, :ultimo_plantel, 
            :nro_hermanos, :responsable_inscripcion, :IdFecha_Escolar, :IdStatus, :IdCurso_Seccion
        )";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->codigo_inscripcion = htmlspecialchars(strip_tags($this->codigo_inscripcion));
        $this->IdEstudiante = htmlspecialchars(strip_tags($this->IdEstudiante));
        $this->ultimo_plantel = htmlspecialchars(strip_tags($this->ultimo_plantel));
        $this->nro_hermanos = htmlspecialchars(strip_tags($this->nro_hermanos));
        $this->responsable_inscripcion = htmlspecialchars(strip_tags($this->responsable_inscripcion));
        $this->IdFecha_Escolar = htmlspecialchars(strip_tags($this->IdFecha_Escolar));
        $this->IdStatus = htmlspecialchars(strip_tags($this->IdStatus));
        $this->IdCurso_Seccion = htmlspecialchars(strip_tags($this->IdCurso_Seccion));

        // Vincular valores
        $stmt->bindParam(":codigo_inscripcion", $this->codigo_inscripcion);
        $stmt->bindParam(":IdEstudiante", $this->IdEstudiante, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_inscripcion", $this->fecha_inscripcion);
        $stmt->bindParam(":ultimo_plantel", $this->ultimo_plantel);
        $stmt->bindParam(":nro_hermanos", $this->nro_hermanos, PDO::PARAM_INT);
        $stmt->bindParam(":responsable_inscripcion", $this->responsable_inscripcion, PDO::PARAM_INT);
        $stmt->bindParam(":IdFecha_Escolar", $this->IdFecha_Escolar, PDO::PARAM_INT);
        $stmt->bindParam(":IdStatus", $this->IdStatus, PDO::PARAM_INT);
        $stmt->bindParam(":IdCurso_Seccion", $this->IdCurso_Seccion, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->IdInscripcion = $this->conn->lastInsertId();
            return $this->IdInscripcion;
        }
        return false;
    }

    public function obtenerPorEstudiante($idEstudiante) {
        $query = "SELECT i.*, s.status, f.fecha_escolar, cs.IdCurso, cs.IdSeccion
                 FROM inscripcion i
                 JOIN status s ON i.IdStatus = s.IdStatus
                 JOIN fecha_escolar f ON i.IdFecha_Escolar = f.IdFecha_Escolar
                 JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
                 WHERE i.IdEstudiante = :IdEstudiante
                 ORDER BY i.fecha_inscripcion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":IdEstudiante", $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($idInscripcion) {
        $query = "SELECT i.*, s.status, f.fecha_escolar, cs.IdCurso, cs.IdSeccion
                 FROM inscripcion i
                 JOIN status s ON i.IdStatus = s.IdStatus
                 JOIN fecha_escolar f ON i.IdFecha_Escolar = f.IdFecha_Escolar
                 JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
                 WHERE i.IdInscripcion = :IdInscripcion
                 LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":IdInscripcion", $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarStatus($idInscripcion, $nuevoStatus) {
        $query = "UPDATE inscripcion SET IdStatus = :IdStatus WHERE IdInscripcion = :IdInscripcion";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":IdStatus", $nuevoStatus, PDO::PARAM_INT);
        $stmt->bindParam(":IdInscripcion", $idInscripcion, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function generarCodigoInscripcion() {
        $prefix = "INS-";
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $this->codigo_inscripcion = $prefix . date('Ymd') . '-' . $random;
        return $this->codigo_inscripcion;
    }
}