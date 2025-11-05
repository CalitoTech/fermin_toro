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
    public $IdEstadoAcceso;
    public $IdEstadoInstitucional;
    public $codigo_temporal;
    public $codigo_expiracion;

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

        // Validar estados usando la columna correcta (IdStatus) y asegurarse que
        // pertenecen al tipo 'Persona' (IdTipo_Status = 1) en la tabla status.
        if ($this->IdEstadoAcceso !== null && $this->IdEstadoAcceso !== '') {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM status WHERE IdStatus = :valor AND IdTipo_Status = 1");
            $stmt->bindParam(":valor", $this->IdEstadoAcceso, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("El status de acceso con IdEstadoAcceso={$this->IdEstadoAcceso} no existe.");
            }
        }

        if ($this->IdEstadoInstitucional !== null && $this->IdEstadoInstitucional !== '') {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM status WHERE IdStatus = :valor AND IdTipo_Status = 1");
            $stmt->bindParam(":valor", $this->IdEstadoInstitucional, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("El status institucional con IdEstadoInstitucional={$this->IdEstadoInstitucional} no existe.");
            }
        }

        // Construir consulta con todos los campos, permitiendo NULL
        $query = "INSERT INTO persona (
            IdNacionalidad, cedula, nombre, apellido, fecha_nacimiento, lugar_nacimiento,
            correo, direccion,
            IdSexo, IdUrbanismo, IdEstadoAcceso, IdEstadoInstitucional
        ) VALUES (
            :IdNacionalidad, :cedula, :nombre, :apellido, :fecha_nacimiento, :lugar_nacimiento,
            :correo, :direccion,
            :IdSexo, :IdUrbanismo, :IdEstadoAcceso, :IdEstadoInstitucional
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
        $this->IdEstadoAcceso = $this->cleanValue($this->IdEstadoAcceso);
        $this->IdEstadoInstitucional = $this->cleanValue($this->IdEstadoInstitucional);

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
        $stmt->bindParam(":IdEstadoAcceso", $this->IdEstadoAcceso, is_null($this->IdEstadoAcceso) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":IdEstadoInstitucional", $this->IdEstadoInstitucional, is_null($this->IdEstadoInstitucional) ? PDO::PARAM_NULL : PDO::PARAM_INT);

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
            $this->IdEstadoAcceso = $row['IdEstadoAcceso'];
            $this->IdEstadoInstitucional = $row['IdEstadoInstitucional'];

            return $row;
        }
        
        return false;
    }

    public function actualizar()
    {
        // Validar claves foráneas básicas
        if (!$this->existeForeignKey('nacionalidad', 'IdNacionalidad', $this->IdNacionalidad)) {
            throw new Exception("La nacionalidad con IdNacionalidad={$this->IdNacionalidad} no existe.");
        }
        if (!$this->existeForeignKey('sexo', 'IdSexo', $this->IdSexo)) {
            throw new Exception("El sexo con IdSexo={$this->IdSexo} no existe.");
        }
        if (!$this->existeForeignKey('urbanismo', 'IdUrbanismo', $this->IdUrbanismo)) {
            throw new Exception("El urbanismo con IdUrbanismo={$this->IdUrbanismo} no existe.");
        }

        // Validar status si se enviaron
        if (!empty($this->IdEstadoAcceso)) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM status WHERE IdStatus = :valor AND IdTipo_Status = 1");
            $stmt->bindParam(":valor", $this->IdEstadoAcceso, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("El status de acceso con IdEstadoAcceso={$this->IdEstadoAcceso} no existe.");
            }
        }

        if (!empty($this->IdEstadoInstitucional)) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM status WHERE IdStatus = :valor AND IdTipo_Status = 1");
            $stmt->bindParam(":valor", $this->IdEstadoInstitucional, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                throw new Exception("El status institucional con IdEstadoInstitucional={$this->IdEstadoInstitucional} no existe.");
            }
        }

        // --- Construir consulta dinámica ---
        $campos = [
            "IdNacionalidad = :IdNacionalidad",
            "cedula = :cedula",
            "nombre = :nombre",
            "apellido = :apellido",
            "fecha_nacimiento = :fecha_nacimiento",
            "lugar_nacimiento = :lugar_nacimiento",
            "correo = :correo",
            "usuario = :usuario",
            "direccion = :direccion",
            "IdSexo = :IdSexo",
            "IdUrbanismo = :IdUrbanismo"
        ];

        if (!empty($this->IdEstadoAcceso)) {
            $campos[] = "IdEstadoAcceso = :IdEstadoAcceso";
        }
        if (!empty($this->IdEstadoInstitucional)) {
            $campos[] = "IdEstadoInstitucional = :IdEstadoInstitucional";
        }

        $query = "UPDATE " . $this->table . " SET " . implode(", ", $campos) . " WHERE IdPersona = :IdPersona";
        $stmt = $this->conn->prepare($query);

        // --- Limpiar y vincular ---
        $stmt->bindParam(":IdNacionalidad", $this->IdNacionalidad, PDO::PARAM_INT);
        $stmt->bindParam(":cedula", $this->cedula, PDO::PARAM_STR);
        $stmt->bindParam(":nombre", $this->nombre, PDO::PARAM_STR);
        $stmt->bindParam(":apellido", $this->apellido, PDO::PARAM_STR);
        $stmt->bindParam(":fecha_nacimiento", $this->fecha_nacimiento, PDO::PARAM_STR);
        $stmt->bindParam(":lugar_nacimiento", $this->lugar_nacimiento, PDO::PARAM_STR);
        $stmt->bindParam(":correo", $this->correo, PDO::PARAM_STR);
        $stmt->bindParam(":usuario", $this->usuario, PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $this->direccion, PDO::PARAM_STR);
        $stmt->bindParam(":IdSexo", $this->IdSexo, PDO::PARAM_INT);
        $stmt->bindParam(":IdUrbanismo", $this->IdUrbanismo, PDO::PARAM_INT);

        if (!empty($this->IdEstadoAcceso)) {
            $stmt->bindParam(":IdEstadoAcceso", $this->IdEstadoAcceso, PDO::PARAM_INT);
        }
        if (!empty($this->IdEstadoInstitucional)) {
            $stmt->bindParam(":IdEstadoInstitucional", $this->IdEstadoInstitucional, PDO::PARAM_INT);
        }

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

    public function generarCodigoTemporal() {
        // Usar la zona horaria de Caracas
        $zona = new DateTimeZone("America/Caracas");

        // Generar código aleatorio de 6 dígitos
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Calcular expiración en Caracas (1 minuto desde ahora)
        $expira = new DateTime("+1 minute", $zona);
        $expiraStr = $expira->format("Y-m-d H:i:s");

        // Hashear el código antes de guardarlo
        $hashedCodigo = password_hash($codigo, PASSWORD_DEFAULT);

        // Guardar en la BD
        $query = "UPDATE " . $this->table . " 
                SET codigo_temporal = :codigo, codigo_expiracion = :expira 
                WHERE IdPersona = :IdPersona";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':codigo', $hashedCodigo, PDO::PARAM_STR);
        $stmt->bindParam(':expira', $expiraStr, PDO::PARAM_STR);
        $stmt->bindParam(':IdPersona', $this->IdPersona, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $codigo; // ✅ Devuelves el código en claro (para enviar al usuario)
        }

        return false;
    }

    public function validarCodigoTemporal($cedula, $codigo) {
        $query = "SELECT IdPersona, IdEstadoAcceso, usuario, codigo_temporal, codigo_expiracion 
                FROM persona 
                WHERE cedula = :cedula 
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cedula', $cedula);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['valido' => false, 'mensaje' => 'Código o cédula incorrectos.'];
        }

        // Comparar el código ingresado con el hash
        if (!password_verify($codigo, $row['codigo_temporal'])) {
            return ['valido' => false, 'mensaje' => 'Código o cédula incorrectos.'];
        }

        // Verificar expiración (ambas en la misma zona horaria)
        $tz = new DateTimeZone('America/Caracas');
        $expiracion = new DateTime($row['codigo_expiracion'], $tz);
        $ahora = new DateTime('now', $tz);

        if ($expiracion < $ahora) {
            $this->limpiarCodigoTemporal($row['IdPersona']);
            return ['valido' => false, 'mensaje' => 'El código ha expirado.'];
        }

        $permitidos_recuperar = [1, 3]; // Activo, Bloqueado

        if (!in_array((int)$row['IdEstadoAcceso'], $permitidos_recuperar)) {
            $this->limpiarCodigoTemporal($row['IdPersona']);
            return ['valido' => false, 'mensaje' => 'El usuario está en un estado no permitido para recuperar acceso.'];
        }


        // ✅ Éxito
        return [
            'valido' => true,
            'IdPersona' => $row['IdPersona'],
            'usuario' => $row['usuario']
        ];
    }

    public function limpiarCodigoTemporal($idPersona) {
        $query = "UPDATE persona SET codigo_temporal = NULL, codigo_expiracion = NULL WHERE IdPersona = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $idPersona, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function bloquearCuenta() {
        $query = "UPDATE " . $this->table . " 
                SET IdEstadoAcceso = 3 
                WHERE IdPersona = :IdPersona";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':IdPersona', $this->IdPersona, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerEstudiantes($idPerfil, $idPersona) {
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
        $filtroNivel = "WHERE dp.IdPerfil = 3"; // Siempre mostrar solo estudiantes

        if (!$tieneAccesoTotal && !empty($nivelesPermitidos)) {
            // Generar lista segura para el filtro
            $nivelesIn = implode(",", array_map('intval', $nivelesPermitidos));
            $filtroNivel .= " AND niv.IdNivel IN ($nivelesIn)";
        }

        $query = "
            SELECT 
                p.IdPersona,
                p.cedula,
                p.IdNacionalidad,
                n.nacionalidad,
                p.nombre,
                p.apellido,
                p.IdSexo,
                sx.sexo,
                niv.IdNivel,
                niv.nivel,
                c.IdCurso,
                c.curso,
                s.IdSeccion,
                s.seccion,
                fe.IdFecha_Escolar,
                fe.fecha_escolar AS anio_escolar,
                GROUP_CONCAT(DISTINCT td.tipo_discapacidad SEPARATOR ', ') AS tipo_discapacidad
            FROM persona AS p
            INNER JOIN detalle_perfil AS dp ON p.IdPersona = dp.IdPersona
            INNER JOIN inscripcion AS i ON i.IdEstudiante = p.IdPersona
            INNER JOIN curso_seccion AS cs ON cs.IdCurso_Seccion = i.IdCurso_Seccion
            INNER JOIN curso AS c ON c.IdCurso = cs.IdCurso
            INNER JOIN nivel AS niv ON niv.IdNivel = c.IdNivel
            INNER JOIN seccion AS s ON s.IdSeccion = cs.IdSeccion
            INNER JOIN fecha_escolar AS fe ON fe.IdFecha_Escolar = i.IdFecha_Escolar
            LEFT JOIN nacionalidad AS n ON n.IdNacionalidad = p.IdNacionalidad
            LEFT JOIN sexo AS sx ON sx.IdSexo = p.IdSexo
            LEFT JOIN discapacidad AS d ON d.IdPersona = p.IdPersona
            LEFT JOIN tipo_discapacidad AS td ON td.IdTipo_Discapacidad = d.IdTipo_Discapacidad
            $filtroNivel AND p.IdEstadoInstitucional != 2
            GROUP BY p.IdPersona
            ORDER BY niv.IdNivel, c.IdCurso, s.IdSeccion, p.apellido
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEstudiantePorId($idPersona) {
        try {
            $query = "
                SELECT 
                    p.IdPersona,
                    p.cedula,
                    n.nacionalidad,
                    n.IdNacionalidad,
                    p.nombre,
                    p.apellido,
                    p.fecha_nacimiento,
                    sx.sexo,
                    sx.IdSexo,
                    p.correo,
                    p.direccion,
                    u.urbanismo,
                    u.IdUrbanismo,
                    ea.status AS estado_acceso,
                    ei.status AS estado_institucional,
                    GROUP_CONCAT(DISTINCT tel.numero_telefono SEPARATOR ' || ') AS numeros,
                    GROUP_CONCAT(DISTINCT tipo_tel.tipo_telefono SEPARATOR ' || ') AS tipos
                FROM persona AS p
                LEFT JOIN nacionalidad AS n ON n.IdNacionalidad = p.IdNacionalidad
                LEFT JOIN sexo AS sx ON sx.IdSexo = p.IdSexo
                LEFT JOIN urbanismo AS u ON u.IdUrbanismo = p.IdUrbanismo
                LEFT JOIN status AS ea ON ea.IdStatus = p.IdEstadoAcceso
                LEFT JOIN status AS ei ON ei.IdStatus = p.IdEstadoInstitucional
                LEFT JOIN telefono AS tel ON tel.IdPersona = p.IdPersona
                LEFT JOIN tipo_telefono AS tipo_tel ON tipo_tel.IdTipo_Telefono = tel.IdTipo_Telefono
                WHERE p.IdPersona = :id
                GROUP BY p.IdPersona
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $idPersona, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerEstudiantePorId: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerSeccionActualEstudiante($idPersona) {
        try {
            $query = "
                SELECT 
                    nvl.nivel,
                    c.curso,
                    s.seccion,
                    a.fecha_escolar
                FROM inscripcion i
                INNER JOIN curso_seccion cs ON cs.IdCurso_Seccion = i.IdCurso_Seccion
                INNER JOIN curso c ON c.IdCurso = cs.IdCurso
                INNER JOIN nivel nvl ON nvl.IdNivel = c.IdNivel
                INNER JOIN seccion s ON s.IdSeccion = cs.IdSeccion
                INNER JOIN fecha_escolar a ON a.IdFecha_Escolar = i.IdFecha_Escolar
                WHERE i.IdEstudiante = :id
                ORDER BY i.IdInscripcion DESC
                LIMIT 1
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $idPersona, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerSeccionActualEstudiante: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerDiscapacidadesEstudiante($idPersona) {
        try {
            $query = "
                SELECT 
                    td.tipo_discapacidad,
                    d.discapacidad,
                    d.IdTipo_Discapacidad
                FROM discapacidad d
                LEFT JOIN tipo_discapacidad td ON td.IdTipo_Discapacidad = d.IdTipo_Discapacidad
                WHERE d.IdPersona = :id
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $idPersona, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en obtenerDiscapacidadesEstudiante: " . $e->getMessage());
            return [];
        }
    }
}