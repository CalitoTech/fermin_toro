<?php
require_once __DIR__ . '/../config/conexion.php';

class Persona {
    private $conn;
    private $table = "persona";
    public $IdPersona;
    public $IdNacionalidad;
    public $cedula;
    public $nombre;
    public $apellido;
    public $fecha_nacimiento;
    public $lugar_nacimiento;
    public $correo;
    public $usuario;
    public $password;
    public $direccion;
    public $IdSexo;
    public $IdUrbanismo;
    public $IdStatus;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        // Validar que las claves foráneas existan (opcional pero recomendado)
        if (!$this->existeForeignKey('nacionalidad', 'IdNacionalidad', $this->IdNacionalidad)) {
            throw new Exception("La nacionalidad con IdNacionalidad={$this->IdNacionalidad} no existe.");
        }
        if (!$this->existeForeignKey('sexo', 'IdSexo', $this->IdSexo)) {
            throw new Exception("El sexo con IdSexo={$this->IdSexo} no existe.");
        }
        if (!$this->existeForeignKey('urbanismo', 'IdUrbanismo', $this->IdUrbanismo)) {
            throw new Exception("El urbanismo con IdUrbanismo={$this->IdUrbanismo} no existe.");
        }
        if ($this->IdStatus !== null && !$this->existeForeignKey('status', 'IdStatus', $this->IdStatus)) {
            throw new Exception("El status con IdStatus={$this->IdStatus} no existe.");
        }

        // Construir consulta con todos los campos, permitiendo NULL
        $query = "INSERT INTO persona (
            IdNacionalidad, cedula, nombre, apellido, fecha_nacimiento, lugar_nacimiento,
            correo, direccion,
            IdSexo, IdUrbanismo, IdStatus
        ) VALUES (
            :IdNacionalidad, :cedula, :nombre, :apellido, :fecha_nacimiento, :lugar_nacimiento,
            :correo, :direccion,
            :IdSexo, :IdUrbanismo, :IdStatus
        )";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdNacionalidad = $this->cleanValue($this->IdNacionalidad);
        $this->cedula = $this->cleanValue($this->cedula);
        $this->nombre = $this->cleanValue($this->nombre);
        $this->apellido = $this->cleanValue($this->apellido);
        $this->fecha_nacimiento = $this->cleanValue($this->fecha_nacimiento);
        $this->lugar_nacimiento = $this->cleanValue($this->lugar_nacimiento);
        $this->correo = $this->cleanValue($this->correo);
        $this->direccion = $this->cleanValue($this->direccion);
        $this->IdSexo = $this->cleanValue($this->IdSexo);
        $this->IdUrbanismo = $this->cleanValue($this->IdUrbanismo);
        $this->IdStatus = $this->cleanValue($this->IdStatus);

        // Vincular parámetros, convirtiendo cadenas vacías o valores inválidos a NULL
        $stmt->bindParam(":IdNacionalidad", $this->IdNacionalidad, is_null($this->IdNacionalidad) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":cedula", $this->cedula, is_null($this->cedula) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $this->nombre, is_null($this->nombre) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":apellido", $this->apellido, is_null($this->apellido) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":fecha_nacimiento", $this->fecha_nacimiento, is_null($this->fecha_nacimiento) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":lugar_nacimiento", $this->lugar_nacimiento, is_null($this->lugar_nacimiento) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":correo", $this->correo, is_null($this->correo) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $this->direccion, is_null($this->direccion) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":IdSexo", $this->IdSexo, is_null($this->IdSexo) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdUrbanismo", $this->IdUrbanismo, is_null($this->IdUrbanismo) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdStatus", $this->IdStatus, is_null($this->IdStatus) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE IdPersona = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Asignar propiedades
            $this->IdPersona = $row['IdPersona'];
            $this->IdNacionalidad = $row['IdNacionalidad'];
            $this->cedula = $row['cedula'];
            $this->nombre = $row['nombre'];
            $this->apellido = $row['apellido'];
            $this->fecha_nacimiento = $row['fecha_nacimiento'];
            $this->lugar_nacimiento = $row['lugar_nacimiento'];
            $this->correo = $row['correo'];
            $this->usuario = $row['usuario'];
            $this->password = $row['password'];
            $this->direccion = $row['direccion'];
            $this->IdSexo = $row['IdSexo'];
            $this->IdUrbanismo = $row['IdUrbanismo'];
            $this->IdStatus = $row['IdStatus'];
            
            return $row;
        }
        
        return false;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table . " SET 
                    IdNacionalidad = :IdNacionalidad,
                    cedula = :cedula,
                    nombre = :nombre,
                    apellido = :apellido,
                    fecha_nacimiento = :fecha_nacimiento,
                    lugar_nacimiento = :lugar_nacimiento,
                    correo = :correo,
                    usuario = :usuario,
                    direccion = :direccion,
                    IdSexo = :IdSexo,
                    IdUrbanismo = :IdUrbanismo,
                    IdStatus = :IdStatus
                  WHERE IdPersona = :IdPersona";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdNacionalidad = $this->cleanValue($this->IdNacionalidad);
        $this->cedula = $this->cleanValue($this->cedula);
        $this->nombre = $this->cleanValue($this->nombre);
        $this->apellido = $this->cleanValue($this->apellido);
        $this->fecha_nacimiento = $this->cleanValue($this->fecha_nacimiento);
        $this->lugar_nacimiento = $this->cleanValue($this->lugar_nacimiento);
        $this->correo = $this->cleanValue($this->correo);
        $this->usuario = $this->cleanValue($this->usuario);
        $this->direccion = $this->cleanValue($this->direccion);
        $this->IdSexo = $this->cleanValue($this->IdSexo);
        $this->IdUrbanismo = $this->cleanValue($this->IdUrbanismo);
        $this->IdStatus = $this->cleanValue($this->IdStatus);

        // Vincular parámetros
        $stmt->bindParam(":IdNacionalidad", $this->IdNacionalidad, is_null($this->IdNacionalidad) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":cedula", $this->cedula, is_null($this->cedula) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $this->nombre, is_null($this->nombre) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":apellido", $this->apellido, is_null($this->apellido) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":fecha_nacimiento", $this->fecha_nacimiento, is_null($this->fecha_nacimiento) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":lugar_nacimiento", $this->lugar_nacimiento, is_null($this->lugar_nacimiento) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":correo", $this->correo, is_null($this->correo) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $this->usuario, is_null($this->usuario) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $this->direccion, is_null($this->direccion) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":IdSexo", $this->IdSexo, is_null($this->IdSexo) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdUrbanismo", $this->IdUrbanismo, is_null($this->IdUrbanismo) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdStatus", $this->IdStatus, is_null($this->IdStatus) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function actualizarPassword($nuevaPassword) {
        $hashedPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $query = "UPDATE " . $this->table . " SET password = :password WHERE IdPersona = :IdPersona";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);
        return $stmt->execute();
    }

    private function cleanValue($value) {
        if ($value === '' || $value === null) {
            return null;
        }
        return htmlspecialchars(strip_tags(trim($value)));
    }

    private function existeForeignKey($tabla, $campo, $valor) {
        if ($valor === null || $valor === '') {
            return true;
        }
        $query = "SELECT COUNT(*) FROM {$tabla} WHERE {$campo} = :valor";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":valor", $valor, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function obtenerPorCedula($nacionalidad, $cedula) {
        $query = "SELECT * FROM persona 
                  WHERE IdNacionalidad = :nacionalidad AND cedula = :cedula 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nacionalidad", $nacionalidad, PDO::PARAM_INT);
        $stmt->bindParam(":cedula", $cedula, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crearCredenciales($idPersona, $usuario, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "UPDATE " . $this->table . " SET 
                    usuario = :usuario,
                    password = :password
                  WHERE IdPersona = :IdPersona";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":usuario", $usuario);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":IdPersona", $idPersona, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    public function obtenerCredenciales() {
        $query = "SELECT usuario, password FROM " . $this->table . " WHERE IdPersona = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdPersona);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarCredenciales($nuevoUsuario, $nuevaPassword = null) {
        if ($nuevaPassword === null) {
            $query = "UPDATE " . $this->table . " SET usuario = :usuario WHERE IdPersona = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario', $nuevoUsuario);
            $stmt->bindParam(':id', $this->IdPersona);
            return $stmt->execute();
        }

        $hashedPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $query = "UPDATE " . $this->table . " SET usuario = :usuario, password = :password WHERE IdPersona = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario', $nuevoUsuario);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $this->IdPersona);
        return $stmt->execute();
    }

    public function eliminar() {
        $query = "DELETE FROM persona WHERE IdPersona = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdPersona, PDO::PARAM_INT);
        return $stmt->execute();
    }
}