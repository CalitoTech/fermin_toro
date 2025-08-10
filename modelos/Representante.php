<?php
require_once __DIR__ . '/../config/conexion.php';

class Representante {
    private $conn;
    public $IdRepresentante;
    public $IdPersona;
    public $IdParentesco;
    public $IdEstudiante;
    public $nombre_contacto;
    public $telefono_contacto;
    public $nacionalidad;
    public $ocupacion;
    public $lugar_trabajo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        // Verificar si ya existe la relación
        $query = "SELECT IdRepresentante FROM representante 
                WHERE IdPersona = :IdPersona 
                AND IdEstudiante = :IdEstudiante 
                AND IdParentesco = :IdParentesco 
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);
        $stmt->bindParam(":IdEstudiante", $this->IdEstudiante, PDO::PARAM_INT);
        $stmt->bindParam(":IdParentesco", $this->IdParentesco, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetchColumn();
        }

        // Si no existe, proceder con la inserción
        $query = "INSERT INTO representante (
            IdPersona, IdParentesco, IdEstudiante, ocupacion, lugar_trabajo
        ) VALUES (
            :IdPersona, :IdParentesco, :IdEstudiante, :ocupacion, :lugar_trabajo
        )";

        $stmt = $this->conn->prepare($query);

        $this->IdPersona = htmlspecialchars(strip_tags($this->IdPersona));
        $this->IdParentesco = htmlspecialchars(strip_tags($this->IdParentesco));
        $this->IdEstudiante = htmlspecialchars(strip_tags($this->IdEstudiante));
        $this->ocupacion = !empty($this->ocupacion) ? htmlspecialchars(strip_tags(trim($this->ocupacion))) : null;
        $this->lugar_trabajo = !empty($this->lugar_trabajo) ? htmlspecialchars(strip_tags(trim($this->lugar_trabajo))) : null;

        $stmt->bindParam(":IdPersona", $this->IdPersona);
        $stmt->bindParam(":IdParentesco", $this->IdParentesco);
        $stmt->bindParam(":IdEstudiante", $this->IdEstudiante);
        $stmt->bindValue(":ocupacion", $this->ocupacion, $this->ocupacion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(":lugar_trabajo", $this->lugar_trabajo, $this->lugar_trabajo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarContactoEmergencia() {
        // Procesar nombre y apellido según las reglas especificadas
        $nombreCompleto = trim($this->nombre_contacto);
        $partesNombre = preg_split('/\s+/', $nombreCompleto);
        
        if (count($partesNombre) < 2) {
            $nombre = $partesNombre[0] ?? '';
            $apellido = '';
        } 
        elseif (count($partesNombre) == 2) {
            $nombre = $partesNombre[0];
            $apellido = $partesNombre[1];
        }
        else {
            $nombre = $partesNombre[0].' '.$partesNombre[1];
            $apellido = implode(' ', array_slice($partesNombre, 2));
        }

        // Crear persona para el contacto de emergencia
        $persona = new Persona($this->conn);
        $persona->nombre = $nombre;
        $persona->apellido = $apellido;
        $persona->IdSexo = NULL;
        $persona->IdUrbanismo = NULL;
        $persona->IdNacionalidad = NULL;
        
        $idPersona = $persona->guardar();
        
        if ($idPersona) {
            // Guardar teléfono del contacto de emergencia
            $telefono = new Telefono($this->conn);
            $telefono->numero_telefono = $this->telefono_contacto;
            $telefono->IdTipo_Telefono = 2; // Celular
            $telefono->IdPersona = $idPersona;
            $telefono->guardar();
            
            // Crear relación como representante
            $this->IdPersona = $idPersona;
            return $this->guardar();
        }
        
        return false;
    }

    public function obtenerPorEstudiante($idEstudiante) {
        $query = "SELECT r.*, p.nombre, p.apellido, pr.parentesco
                 FROM representante r
                 JOIN persona p ON r.IdPersona = p.IdPersona
                 JOIN parentesco pr ON r.IdParentesco = pr.IdParentesco
                 WHERE r.IdEstudiante = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}