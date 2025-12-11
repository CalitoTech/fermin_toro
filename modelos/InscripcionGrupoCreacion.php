<?php
require_once __DIR__ . '/../config/conexion.php';
class InscripcionGrupoCreacion {
    private $conn;
    public $IdInscripcionGrupo;
    public $IdGrupoCreacion;
    public $IdEstudiante;
    public $IdInscripcion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO inscripcion_grupo_creacion SET 
            IdGrupoCreacion = :IdGrupoCreacion,
            IdEstudiante = :IdEstudiante,
            IdInscripcion = :IdInscripcion";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdGrupoCreacion = htmlspecialchars(strip_tags($this->IdGrupoCreacion));
        $this->IdEstudiante = htmlspecialchars(strip_tags($this->IdEstudiante));
        $this->IdInscripcion = htmlspecialchars(strip_tags($this->IdInscripcion));

        // Vincular valores
        $stmt->bindParam(":IdGrupoCreacion", $this->IdGrupoCreacion);
        $stmt->bindParam(":IdEstudiante", $this->IdEstudiante);
        $stmt->bindParam(":IdInscripcion", $this->IdInscripcion);

        return $stmt->execute();
    }

    public function obtenerPorGrupo($idGrupoCreacion) {
        $query = "SELECT ig.*, p.nombre, p.apellido, p.cedula
                 FROM inscripcion_grupo_creacion ig
                 JOIN persona p ON ig.IdEstudiante = p.IdPersona
                 WHERE ig.IdGrupoCreacion = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idGrupoCreacion);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>