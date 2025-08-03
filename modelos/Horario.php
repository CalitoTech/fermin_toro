<?php
require_once __DIR__ . '/../config/conexion.php';
class Horario {
    private $conn;
    public $IdHorario;
    public $IdMateria;
    public $IdBloque;
    public $IdDia;
    public $IdPersona;
    public $IdAula;
    public $IdFecha_Escolar;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO horario SET 
            IdMateria = :IdMateria,
            IdBloque = :IdBloque,
            IdDia = :IdDia,
            IdPersona = :IdPersona,
            IdAula = :IdAula,
            IdFecha_Escolar = :IdFecha_Escolar";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdMateria = htmlspecialchars(strip_tags($this->IdMateria));
        $this->IdBloque = htmlspecialchars(strip_tags($this->IdBloque));
        $this->IdDia = htmlspecialchars(strip_tags($this->IdDia));
        $this->IdPersona = htmlspecialchars(strip_tags($this->IdPersona));
        $this->IdAula = htmlspecialchars(strip_tags($this->IdAula));
        $this->IdFecha_Escolar = htmlspecialchars(strip_tags($this->IdFecha_Escolar));

        // Vincular valores
        $stmt->bindParam(":IdMateria", $this->IdMateria);
        $stmt->bindParam(":IdBloque", $this->IdBloque);
        $stmt->bindParam(":IdDia", $this->IdDia);
        $stmt->bindParam(":IdPersona", $this->IdPersona);
        $stmt->bindParam(":IdAula", $this->IdAula);
        $stmt->bindParam(":IdFecha_Escolar", $this->IdFecha_Escolar);

        return $stmt->execute();
    }

    public function obtenerPorProfesor($idProfesor, $idFechaEscolar) {
        $query = "SELECT h.*, m.materia, b.hora_inicio, b.hora_fin, d.dia, a.capacidad, c.curso, s.seccion 
                 FROM horario h
                 JOIN materia m ON h.IdMateria = m.IdMateria
                 JOIN bloque b ON h.IdBloque = b.IdBloque
                 JOIN dia d ON h.IdDia = d.IdDia
                 JOIN aula a ON h.IdAula = a.IdAula
                 JOIN curso c ON a.IdCurso = c.IdCurso
                 JOIN seccion s ON a.IdSeccion = s.IdSeccion
                 WHERE h.IdPersona = ? AND h.IdFecha_Escolar = ?
                 ORDER BY d.IdDia, b.hora_inicio";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idProfesor);
        $stmt->bindParam(2, $idFechaEscolar);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>