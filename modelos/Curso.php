<?php
require_once __DIR__ . '/../config/conexion.php';

class Curso {
    private $conexion;
    public $IdCurso;
    public $curso;
    public $IdNivel;

    public function __construct($conexionPDO) {
        $this->conexion = $conexionPDO;
    }

    public function guardar() {
        $query = "INSERT INTO curso (curso, IdNivel) VALUES (:curso, :nivel)";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':curso', $this->curso);
        $stmt->bindParam(':nivel', $this->IdNivel);
        
        if ($stmt->execute()) {
            $this->IdCurso = $this->conexion->lastInsertId();
            return $this->IdCurso;
        }
        return false;
    }
    
    public function actualizar() {
        $query = "UPDATE curso SET curso = :curso, IdNivel = :nivel WHERE IdCurso = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':curso', $this->curso);
        $stmt->bindParam(':nivel', $this->IdNivel);
        $stmt->bindParam(':id', $this->IdCurso);
        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM curso WHERE IdCurso = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $this->IdCurso);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el curso está siendo usado en la tabla curso_seccion
        $query = "SELECT COUNT(*) as total FROM curso_seccion WHERE IdCurso = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $this->IdCurso);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($resultado['total'] > 0);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM curso WHERE IdCurso = :id LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdCurso = $row['IdCurso'];
            $this->curso = $row['curso'];
            return $row;
        }
        
        return false;
    }

    public function obtenerTodos() {
        $consulta = "SELECT c.IdCurso, c.curso, n.IdNivel, n.nivel as nombre_nivel
                     FROM curso c
                     JOIN nivel n ON c.IdNivel = n.IdNivel
                     ORDER BY n.IdNivel, c.curso";
        
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorNivel($idNivel) {
        $consulta = "SELECT c.IdCurso, c.curso, n.nivel as nombre_nivel
                     FROM curso c
                     JOIN nivel n ON c.IdNivel = n.IdNivel
                     WHERE c.IdNivel = :idNivel";
        
        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':idNivel', $idNivel, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerConSecciones($idNivel) {
        $consulta = "SELECT c.IdCurso, c.curso, s.IdSeccion, s.seccion
                     FROM curso c
                     LEFT JOIN aula a ON c.IdCurso = a.IdCurso
                     LEFT JOIN seccion s ON a.IdSeccion = s.IdSeccion
                     WHERE c.IdNivel = :idNivel";

        $stmt = $this->conexion->prepare($consulta);
        $stmt->bindParam(':idNivel', $idNivel, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCursos($idPersona) {
        // Obtener todos los perfiles del usuario
        $sqlPerfiles = "SELECT IdPerfil FROM detalle_perfil WHERE IdPersona = :idPersona";
        $stmtPerfiles = $this->conexion->prepare($sqlPerfiles);
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
            $filtroNivel = "WHERE c.IdNivel IN ($nivelesIn)";
        }

        $query = "
            SELECT
                c.IdCurso,
                c.curso,
                n.IdNivel,
                n.nivel as nombre_nivel
            FROM curso c
            JOIN nivel n ON c.IdNivel = n.IdNivel
            $filtroNivel
            ORDER BY n.IdNivel, c.curso
        ";

        $stmt = $this->conexion->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}