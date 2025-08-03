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

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        // === PASO 1: Verificar si ya existe la relación (IdPersona + IdEstudiante + IdParentesco) ===
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
            // Ya existe → retornar el Id existente
            return $stmt->fetchColumn();
        }

        // === PASO 2: Si no existe, proceder con la inserción ===
        $query = "INSERT INTO representante (IdPersona, IdParentesco, IdEstudiante, ocupacion) 
                VALUES (:IdPersona, :IdParentesco, :IdEstudiante, :ocupacion)";

        $stmt = $this->conn->prepare($query);

        $this->IdPersona = htmlspecialchars(strip_tags($this->IdPersona));
        $this->IdParentesco = htmlspecialchars(strip_tags($this->IdParentesco));
        $this->IdEstudiante = htmlspecialchars(strip_tags($this->IdEstudiante));
        $ocupacionLimpia = !empty($this->ocupacion) ? htmlspecialchars(strip_tags(trim($this->ocupacion))) : null;

        $stmt->bindParam(":IdPersona", $this->IdPersona);
        $stmt->bindParam(":IdParentesco", $this->IdParentesco);
        $stmt->bindParam(":IdEstudiante", $this->IdEstudiante);
        $stmt->bindValue(":ocupacion", $ocupacionLimpia, $ocupacionLimpia === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function guardarContactoEmergencia() {
    // Procesar nombre y apellido según las reglas especificadas
    $nombreCompleto = trim($this->nombre_contacto);
    $partesNombre = preg_split('/\s+/', $nombreCompleto);
    
    // Si hay menos de 2 palabras, tomamos la primera como nombre y dejamos apellido vacío
    if (count($partesNombre) < 2) {
        $nombre = $partesNombre[0] ?? '';
        $apellido = '';
    } 
    // Si hay exactamente 2 palabras: primera es nombre, segunda es apellido
    elseif (count($partesNombre) == 2) {
        $nombre = $partesNombre[0];
        $apellido = $partesNombre[1];
    }
    // Si hay 3 o más palabras: primeras dos son nombre, el resto es apellido
    else {
        $nombre = $partesNombre[0].' '.$partesNombre[1];
        $apellido = implode(' ', array_slice($partesNombre, 2));
    }

    // Crear persona para el contacto de emergencia
    $persona = new Persona($this->conn);
    $persona->nombre = $nombre;
    $persona->apellido = $apellido;
    $persona->IdSexo = NULL; // Valor por defecto
    $persona->IdUrbanismo = NULL; // Valor por defecto
    $persona->IdNacionalidad = NULL; // Valor por defecto para nacionalidad (Venezolano)
    
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
}
?>