<?php
require_once __DIR__ . '/../config/conexion.php';
class InscripcionGrupoInteres {
    private $conn;
    public $IdInscripcionGrupo;
    public $IdGrupoInteres;
    public $IdEstudiante;
    public $IdInscripcion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO inscripcion_grupo_interes SET 
            IdGrupoInteres = :IdGrupoInteres,
            IdEstudiante = :IdEstudiante,
            IdInscripcion = :IdInscripcion";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdGrupoInteres = htmlspecialchars(strip_tags($this->IdGrupoInteres));
        $this->IdEstudiante = htmlspecialchars(strip_tags($this->IdEstudiante));
        $this->IdInscripcion = htmlspecialchars(strip_tags($this->IdInscripcion));

        // Vincular valores
        $stmt->bindParam(":IdGrupoInteres", $this->IdGrupoInteres);
        $stmt->bindParam(":IdEstudiante", $this->IdEstudiante);
        $stmt->bindParam(":IdInscripcion", $this->IdInscripcion);

        return $stmt->execute();
    }

    public function obtenerPorGrupo($idGrupoInteres) {
        $query = "SELECT ig.*, p.nombre, p.apellido, p.cedula
                 FROM inscripcion_grupo_interes ig
                 JOIN persona p ON ig.IdEstudiante = p.IdPersona
                 WHERE ig.IdGrupoInteres = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idGrupoInteres);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>