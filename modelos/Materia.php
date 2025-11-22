<?php
require_once __DIR__ . '/../config/conexion.php';
class Materia {
    private $conn;
    public $IdMateria;
    public $materia;
    public $IdNivel;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO materia SET 
            materia = :materia,
            IdNivel = :IdNivel";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->materia = htmlspecialchars(strip_tags($this->materia));
        $this->IdNivel = htmlspecialchars(strip_tags($this->IdNivel));

        // Vincular valores
        $stmt->bindParam(":materia", $this->materia);
        $stmt->bindParam(":IdNivel", $this->IdNivel);

        return $stmt->execute();
    }

    public function actualizar() {
        $query = "UPDATE materia SET materia = :materia, IdNivel = :nivel WHERE IdMateria = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':materia', $this->materia);
        $stmt->bindParam(':nivel', $this->IdNivel);
        $stmt->bindParam(':id', $this->IdMateria);
        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM materia WHERE IdMateria = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdMateria);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el materia está siendo usado en la tabla materia_seccion
        $query = "SELECT COUNT(*) as total FROM horario WHERE IdMateria = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdMateria);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM materia WHERE IdMateria = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdMateria = $row['IdMateria'];
            $this->materia = $row['materia'];
            return $row;
        }
        
        return false;
    }

    public function obtenerPorNivel($idNivel) {
        $query = "SELECT * FROM materia WHERE IdNivel = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idNivel);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerMaterias($idPersona) {
        // Obtener todos los perfiles del usuario
        $sqlPerfiles = "SELECT IdPerfil FROM detalle_perfil WHERE IdPersona = :idPersona";
        $stmtPerfiles = $this->conn->prepare($sqlPerfiles);
        $stmtPerfiles->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
        $stmtPerfiles->execute();
        $perfilesUsuario = $stmtPerfiles->fetchAll(PDO::FETCH_COLUMN);

        // Determinar si tiene algún perfil con acceso total
        $perfilesAutorizadosTotales = [1, 6, 7]; // Administrador, Director, Control de Estudios
        $tieneAccesoTotal = !empty(array_intersect($perfilesUsuario, $perfilesAutorizadosTotales));

        // Determinar qué niveles puede ver (por IdNivel)
        $nivelesPermitidos = [];

        if (in_array(8, $perfilesUsuario)) $nivelesPermitidos[] = 1; // Inicial
        if (in_array(9, $perfilesUsuario)) $nivelesPermitidos[] = 2; // Primaria
        if (in_array(10, $perfilesUsuario)) $nivelesPermitidos[] = 3; // Media General

        // Construir condición WHERE según los permisos
        $filtroNivel = "";

        if (!$tieneAccesoTotal && !empty($nivelesPermitidos)) {
            // Generar lista segura para el filtro
            $nivelesIn = implode(",", array_map('intval', $nivelesPermitidos));
            $filtroNivel = "WHERE m.IdNivel IN ($nivelesIn)";
        }

        $query = "
            SELECT
                m.IdMateria,
                m.materia,
                n.nivel
            FROM materia m
            INNER JOIN nivel n ON m.IdNivel = n.IdNivel
            $filtroNivel
            ORDER BY n.IdNivel, m.materia
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>