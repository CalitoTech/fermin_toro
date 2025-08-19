<?php
require_once __DIR__ . '/../config/conexion.php';
class TipoGrupoInteres {
    private $conn;
    public $IdTipoGrupo;
    public $nombre_grupo;
    public $descripcion;
    public $capacidad_maxima;
    public $inscripcion_activa;
    public $IdNivel;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerPorNivel($idNivel) {
        $query = "SELECT * FROM tipo_grupo_interes WHERE IdNivel = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idNivel);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar() {
        $query = "INSERT INTO tipo_grupo_interes SET 
            nombre_grupo = :nombre_grupo,
            descripcion = :descripcion,
            capacidad_maxima = :capacidad_maxima,
            inscripcion_activa = :inscripcion_activa,
            IdNivel = :IdNivel";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre_grupo = htmlspecialchars(strip_tags($this->nombre_grupo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->capacidad_maxima = htmlspecialchars(strip_tags($this->capacidad_maxima));
        $this->inscripcion_activa = htmlspecialchars(strip_tags($this->inscripcion_activa));
        $this->IdNivel = htmlspecialchars(strip_tags($this->IdNivel));

        // Vincular valores
        $stmt->bindParam(":nombre_grupo", $this->nombre_grupo);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":capacidad_maxima", $this->capacidad_maxima);
        $stmt->bindParam(":inscripcion_activa", $this->inscripcion_activa);
        $stmt->bindParam(":IdNivel", $this->IdNivel);

        return $stmt->execute();
    }

    public function actualizar() {
        $query = "UPDATE tipo_grupo_interes SET nombre_grupo = :nombre_grupo,
        descripcion = :descripcion, capacidad_maxima = :capacidad_maxima,
        inscripcion_activa = :inscripcion_activa, IdNivel = :nivel WHERE IdTipo_Grupo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nombre_grupo', $this->nombre_grupo);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':capacidad_maxima', $this->capacidad_maxima);
        $stmt->bindParam(':inscripcion_activa', $this->inscripcion_activa);
        $stmt->bindParam(':nivel', $this->IdNivel);
        $stmt->bindParam(':id', $this->IdTipoGrupo);
        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM tipo_grupo_interes WHERE IdTipo_Grupo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdTipoGrupo);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el tipo_grupo_interes está siendo usado en la tabla grupo_interes
        $query = "SELECT COUNT(*) as total FROM grupo_interes WHERE IdTipo_Grupo = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdTipoGrupo);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM tipo_grupo_interes WHERE IdTipo_Grupo = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdTipoGrupo = $row['IdTipo_Grupo'];
            $this->nombre_grupo = $row['nombre_grupo'];
            $this->descripcion = $row['descripcion'];
            $this->capacidad_maxima = $row['capacidad_maxima'];
            $this->inscripcion_activa = $row['inscripcion_activa'];
            return $row;
        }
        
        return false;
    }
}
?>