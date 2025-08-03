<?php
require_once __DIR__ . '/../config/conexion.php';

class GrupoCreacion {
    private $conn;
    public $IdGrupoCreacion;
    public $IdTipoGrupo;
    public $IdProfesor;
    public $IdAula;
    public $IdFecha_Escolar;
    public $cupos_disponibles;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO grupo_creacion SET 
            IdTipoGrupo = :IdTipoGrupo,
            IdProfesor = :IdProfesor,
            IdAula = :IdAula,
            IdFecha_Escolar = :IdFecha_Escolar,
            cupos_disponibles = :cupos_disponibles";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdTipoGrupo = htmlspecialchars(strip_tags($this->IdTipoGrupo));
        $this->IdProfesor = htmlspecialchars(strip_tags($this->IdProfesor));
        $this->IdAula = htmlspecialchars(strip_tags($this->IdAula));
        $this->IdFecha_Escolar = htmlspecialchars(strip_tags($this->IdFecha_Escolar));
        $this->cupos_disponibles = htmlspecialchars(strip_tags($this->cupos_disponibles));

        // Vincular valores
        $stmt->bindParam(":IdTipoGrupo", $this->IdTipoGrupo);
        $stmt->bindParam(":IdProfesor", $this->IdProfesor);
        $stmt->bindParam(":IdAula", $this->IdAula);
        $stmt->bindParam(":IdFecha_Escolar", $this->IdFecha_Escolar);
        $stmt->bindParam(":cupos_disponibles", $this->cupos_disponibles);

        return $stmt->execute();
    }

    public function obtenerPorFechaEscolar($idFechaEscolar) {
        $query = "SELECT gc.*, tg.nombre_grupo, tg.descripcion, p.nombre, p.apellido, 
                 a.capacidad, c.curso, s.seccion
                 FROM grupo_creacion gc
                 JOIN tipo_grupo_creacion tg ON gc.IdTipoGrupo = tg.IdTipoGrupo
                 JOIN persona p ON gc.IdProfesor = p.IdPersona
                 JOIN aula a ON gc.IdAula = a.IdAula
                 JOIN curso c ON a.IdCurso = c.IdCurso
                 JOIN seccion s ON a.IdSeccion = s.IdSeccion
                 WHERE gc.IdFecha_Escolar = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idFechaEscolar);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>