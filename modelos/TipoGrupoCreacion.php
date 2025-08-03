<?php
require_once __DIR__ . '/../config/conexion.php';
class TipoGrupoCreacion {
    private $conn;
    public $IdTipoGrupo;
    public $nombre_grupo;
    public $descripcion;
    public $capacidad_maxima;
    public $IdNivel;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerPorNivel($idNivel) {
        $query = "SELECT * FROM tipo_grupo_creacion WHERE IdNivel = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idNivel);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar() {
        $query = "INSERT INTO tipo_grupo_creacion SET 
            nombre_grupo = :nombre_grupo,
            descripcion = :descripcion,
            capacidad_maxima = :capacidad_maxima,
            IdNivel = :IdNivel";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre_grupo = htmlspecialchars(strip_tags($this->nombre_grupo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->capacidad_maxima = htmlspecialchars(strip_tags($this->capacidad_maxima));
        $this->IdNivel = htmlspecialchars(strip_tags($this->IdNivel));

        // Vincular valores
        $stmt->bindParam(":nombre_grupo", $this->nombre_grupo);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":capacidad_maxima", $this->capacidad_maxima);
        $stmt->bindParam(":IdNivel", $this->IdNivel);

        return $stmt->execute();
    }
}
?>