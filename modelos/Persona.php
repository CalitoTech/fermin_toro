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
    public $fecha_egreso;
    public $correo;
    public $usuario;
    public $password;
    public $direccion;
    public $lugar_trabajo;
    public $IdSexo;
    public $IdUrbanismo;
    public $IdCondicion;
    public $IdAula;

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
        if ($this->IdCondicion !== null && !$this->existeForeignKey('condicion', 'IdCondicion', $this->IdCondicion)) {
            throw new Exception("La condición con IdCondicion={$this->IdCondicion} no existe.");
        }
        if ($this->IdAula !== null && !$this->existeForeignKey('aula', 'IdAula', $this->IdAula)) {
            throw new Exception("La condición con IdAula={$this->IdAula} no existe.");
        }
        
        // Hashear la contraseña antes de guardar
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Construir consulta con todos los campos, permitiendo NULL
        $query = "INSERT INTO persona (
            IdNacionalidad, cedula, nombre, apellido, fecha_nacimiento,
            fecha_egreso, correo, usuario, password, direccion,
            lugar_trabajo, IdSexo, IdUrbanismo, IdCondicion, IdAula
        ) VALUES (
            :IdNacionalidad, :cedula, :nombre, :apellido, :fecha_nacimiento,
            :fecha_egreso, :correo, :usuario, :password, :direccion,
            :lugar_trabajo, :IdSexo, :IdUrbanismo, :IdCondicion, :IdAula
        )";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdNacionalidad = $this->cleanValue($this->IdNacionalidad);
        $this->cedula = $this->cleanValue($this->cedula);
        $this->nombre = $this->cleanValue($this->nombre);
        $this->apellido = $this->cleanValue($this->apellido);
        $this->fecha_nacimiento = $this->cleanValue($this->fecha_nacimiento);
        $this->fecha_egreso = $this->cleanValue($this->fecha_egreso);
        $this->correo = $this->cleanValue($this->correo);
        $this->usuario = $this->cleanValue($this->usuario);
        $this->password = $this->cleanValue($this->password); // Considera hashearla
        $this->direccion = $this->cleanValue($this->direccion);
        $this->lugar_trabajo = $this->cleanValue($this->lugar_trabajo);
        $this->IdSexo = $this->cleanValue($this->IdSexo);
        $this->IdUrbanismo = $this->cleanValue($this->IdUrbanismo);
        $this->IdCondicion = $this->cleanValue($this->IdCondicion);
        $this->IdAula = $this->cleanValue($this->IdAula);

        // Vincular parámetros, convirtiendo cadenas vacías o valores inválidos a NULL
        $stmt->bindParam(":IdNacionalidad", $this->IdNacionalidad, is_null($this->IdNacionalidad) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":cedula", $this->cedula, is_null($this->cedula) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $this->nombre, is_null($this->nombre) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":apellido", $this->apellido, is_null($this->apellido) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":fecha_nacimiento", $this->fecha_nacimiento, is_null($this->fecha_nacimiento) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":fecha_egreso", $this->fecha_egreso, is_null($this->fecha_egreso) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":correo", $this->correo, is_null($this->correo) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $this->usuario, is_null($this->usuario) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":password", $this->password, is_null($this->password) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $this->direccion, is_null($this->direccion) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":lugar_trabajo", $this->lugar_trabajo, is_null($this->lugar_trabajo) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":IdSexo", $this->IdSexo, is_null($this->IdSexo) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdUrbanismo", $this->IdUrbanismo, is_null($this->IdUrbanismo) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdCondicion", $this->IdCondicion, is_null($this->IdCondicion) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdAula", $this->IdAula, is_null($this->IdAula) ? PDO::PARAM_NULL : PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    // Método para obtener una persona por su ID
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
            $this->fecha_egreso = $row['fecha_egreso'];
            $this->correo = $row['correo'];
            $this->usuario = $row['usuario'];
            $this->password = $row['password'];
            $this->direccion = $row['direccion'];
            $this->lugar_trabajo = $row['lugar_trabajo'];
            $this->IdSexo = $row['IdSexo'];
            $this->IdUrbanismo = $row['IdUrbanismo'];
            $this->IdCondicion = $row['IdCondicion'];
            $this->IdAula = $row['IdAula'];
            
            return $row; // Devolver el array con los datos
        }
        
        return false;
    }

     // Método para actualizar una persona
    public function actualizar() {
        $query = "UPDATE " . $this->table . " SET 
                    IdNacionalidad = :IdNacionalidad,
                    cedula = :cedula,
                    nombre = :nombre,
                    apellido = :apellido,
                    fecha_nacimiento = :fecha_nacimiento,
                    fecha_egreso = :fecha_egreso,
                    correo = :correo,
                    usuario = :usuario,
                    direccion = :direccion,
                    lugar_trabajo = :lugar_trabajo,
                    IdSexo = :IdSexo,
                    IdUrbanismo = :IdUrbanismo,
                    IdCondicion = :IdCondicion,
                    IdAula = :IdAula
                  WHERE IdPersona = :IdPersona";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdNacionalidad = $this->cleanValue($this->IdNacionalidad);
        $this->cedula = $this->cleanValue($this->cedula);
        $this->nombre = $this->cleanValue($this->nombre);
        $this->apellido = $this->cleanValue($this->apellido);
        $this->fecha_nacimiento = $this->cleanValue($this->fecha_nacimiento);
        $this->fecha_egreso = $this->cleanValue($this->fecha_egreso);
        $this->correo = $this->cleanValue($this->correo);
        $this->usuario = $this->cleanValue($this->usuario);
        $this->direccion = $this->cleanValue($this->direccion);
        $this->lugar_trabajo = $this->cleanValue($this->lugar_trabajo);
        $this->IdSexo = $this->cleanValue($this->IdSexo);
        $this->IdUrbanismo = $this->cleanValue($this->IdUrbanismo);
        $this->IdCondicion = $this->cleanValue($this->IdCondicion);
        $this->IdAula = $this->cleanValue($this->IdAula);

        // Vincular parámetros
        $stmt->bindParam(":IdNacionalidad", $this->IdNacionalidad, is_null($this->IdNacionalidad) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":cedula", $this->cedula, is_null($this->cedula) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $this->nombre, is_null($this->nombre) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":apellido", $this->apellido, is_null($this->apellido) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":fecha_nacimiento", $this->fecha_nacimiento, is_null($this->fecha_nacimiento) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":fecha_egreso", $this->fecha_egreso, is_null($this->fecha_egreso) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":correo", $this->correo, is_null($this->correo) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $this->usuario, is_null($this->usuario) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $this->direccion, is_null($this->direccion) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":lugar_trabajo", $this->lugar_trabajo, is_null($this->lugar_trabajo) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":IdSexo", $this->IdSexo, is_null($this->IdSexo) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdUrbanismo", $this->IdUrbanismo, is_null($this->IdUrbanismo) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdCondicion", $this->IdCondicion, is_null($this->IdCondicion) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdAula", $this->IdAula, is_null($this->IdAula) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Método para actualizar la contraseña
    public function actualizarPassword($nuevaPassword) {
        $hashedPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        $query = "UPDATE " . $this->table . " SET password = :password WHERE IdPersona = :IdPersona";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password", $hashedPassword);
        $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Método auxiliar para limpiar valores y convertir vacíos a NULL
    private function cleanValue($value) {
        if ($value === '' || $value === null) {
            return null;
        }
        return htmlspecialchars(strip_tags(trim($value)));
    }

    // Validar si un ID existe en una tabla (para evitar errores de clave foránea)
    private function existeForeignKey($tabla, $campo, $valor) {
        if ($valor === null || $valor === '') {
            return true; // Permitimos NULL si el campo lo permite
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

    public function obtenerCredenciales() {
        $query = "SELECT usuario, password FROM " . $this->table . " WHERE IdPersona = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdPersona);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza el usuario y/o contraseña de la persona
     */
    public function actualizarCredenciales($nuevoUsuario, $nuevaPassword = null) {
        // Si no se proporciona nueva contraseña, mantener la actual
        if ($nuevaPassword === null) {
            $query = "UPDATE " . $this->table . " SET usuario = :usuario WHERE IdPersona = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario', $nuevoUsuario);
            $stmt->bindParam(':id', $this->IdPersona);
            return $stmt->execute();
        }

        // Si hay nueva contraseña, hashearla
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


?>