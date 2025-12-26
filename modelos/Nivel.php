<?php
class Nivel {
    private $conexion;
    public $IdNivel;
    public $nivel;

    public function __construct($conexionPDO) {
        $this->conexion = $conexionPDO;
    }

    public function guardar() {
        $query = "INSERT INTO nivel (nivel) VALUES (:nivel)";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':nivel', $this->nivel);
        
        if ($stmt->execute()) {
            $this->IdNivel = $this->conexion->lastInsertId();
            return $this->IdNivel;
        }
        return false;
    }
    
    public function actualizar() {
        $query = "UPDATE nivel SET nivel = :nivel WHERE IdNivel = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':nivel', $this->nivel);
        $stmt->bindParam(':id', $this->IdNivel);
        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM nivel WHERE IdNivel = :id";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $this->IdNivel);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar en múltiples tablas
        $tablas = ['aula', 'requisito']; // Agrega todas las tablas relevantes
        
        foreach ($tablas as $tabla) {
            $query = "SELECT COUNT(*) as total FROM $tabla WHERE IdNivel = :id";
            $stmt = $this->conexion->prepare($query);
            $stmt->bindParam(':id', $this->IdNivel);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['total'] > 0) {
                return true;
            }
        }
        
        return false;
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM nivel WHERE IdNivel = :id LIMIT 1";
        $stmt = $this->conexion->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->IdNivel = $row['IdNivel'];
            $this->nivel = $row['nivel'];
            return $row;
        }
        
        return false;
    }

    public function obtenerTodos() {
        $consulta = "SELECT IdNivel, nivel FROM nivel";
        $stmt = $this->conexion->query($consulta);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerRequisitos($idNivel) {
        $consulta = "SELECT IdRequisito, requisito, obligatorio
                     FROM requisito
                     WHERE IdNivel = ?";

        $stmt = $this->conexion->prepare($consulta);
        $stmt->execute([$idNivel]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerNiveles($idPersona = null) {
        // Si no hay persona logeada (caso público como solicitud_cupo), mostrar todos
        if ($idPersona === null) {
            return $this->obtenerTodos();
        }

        // Obtener todos los perfiles del usuario
        $sqlPerfiles = "SELECT IdPerfil FROM detalle_perfil WHERE IdPersona = :idPersona";
        $stmtPerfiles = $this->conexion->prepare($sqlPerfiles);
        $stmtPerfiles->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
        $stmtPerfiles->execute();
        $perfilesUsuario = $stmtPerfiles->fetchAll(PDO::FETCH_COLUMN);

        // Determinar si tiene algún perfil con acceso total
        $perfilesAutorizadosTotales = [1, 6, 7, 11, 12]; // Administrador, Director, Control de Estudios, Sub-director, Dirección
        $tieneAccesoTotal = !empty(array_intersect($perfilesUsuario, $perfilesAutorizadosTotales));

        // Si tiene acceso total, mostrar todos los niveles
        if ($tieneAccesoTotal) {
            return $this->obtenerTodos();
        }

        // Determinar qué niveles puede ver (por IdNivel)
        $nivelesPermitidos = [];

        if (in_array(8, $perfilesUsuario)) $nivelesPermitidos[] = 1; // Inicial
        if (in_array(9, $perfilesUsuario)) $nivelesPermitidos[] = 2; // Primaria
        if (in_array(10, $perfilesUsuario)) $nivelesPermitidos[] = 3; // Media General

        // Si no tiene permisos específicos, retornar array vacío
        if (empty($nivelesPermitidos)) {
            return [];
        }

        // Filtrar niveles según permisos
        $nivelesIn = implode(",", array_map('intval', $nivelesPermitidos));
        $query = "SELECT IdNivel, nivel FROM nivel WHERE IdNivel IN ($nivelesIn) ORDER BY IdNivel";

        $stmt = $this->conexion->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}