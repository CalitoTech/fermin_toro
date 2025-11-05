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
    public $IdEstadoAcceso;
    public $IdEstadoInstitucional;

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
        // Validar que se haya ingresado nombre completo (nombre + apellido)
        $nombreCompleto = trim($this->nombre_contacto);
        if (empty($nombreCompleto)) {
            throw new Exception("Por favor ingrese el nombre completo del contacto de emergencia");
        }

        // Validar que haya al menos un espacio (nombre y apellido)
        if (strpos($nombreCompleto, ' ') === false) {
            throw new Exception("Debe ingresar tanto el nombre como el apellido del contacto de emergencia");
        }

        // Validar teléfono
        if (empty(trim($this->telefono_contacto))) {
            throw new Exception("Debe ingresar un número de teléfono para el contacto de emergencia");
        }

        // Separar nombre y apellido
        $partes = explode(' ', $nombreCompleto, 2);
        $nombre = $partes[0];
        $apellido = $partes[1] ?? '';

        // Validar que el apellido no esté vacío
        if (empty(trim($apellido))) {
            throw new Exception("Debe ingresar un apellido válido para el contacto de emergencia");
        }

        // Crear persona para el contacto de emergencia
        $persona = new Persona($this->conn);
        $persona->nombre = $nombre;
        $persona->apellido = $apellido;
        $persona->IdSexo = NULL;
        $persona->IdUrbanismo = NULL;
        $persona->IdNacionalidad = NULL;
        $persona->IdEstadoAcceso = 2;
        $persona->IdEstadoInstitucional = 2;
        
        $idPersona = $persona->guardar();
        
        if (!$idPersona) {
            throw new Exception("Error al guardar los datos del contacto de emergencia");
        }

        // Guardar teléfono
        $telefono = new Telefono($this->conn);
        $telefono->numero_telefono = $this->telefono_contacto;
        $telefono->IdTipo_Telefono = 2; // Celular
        $telefono->IdPersona = $idPersona;
        
        if (!$telefono->guardar()) {
            throw new Exception("Error al guardar el teléfono del contacto de emergencia");
        }
        
        // Crear relación como representante
        $this->IdPersona = $idPersona;
        return $this->guardar();
    }

     public function obtenerPorEstudiante($idEstudiante) {
        $query = "
            SELECT 
                p.IdPersona,
                p.cedula,
                n.nacionalidad,
                p.nombre,
                p.apellido,
                sx.sexo,
                u.urbanismo,
                p.correo,
                p.direccion,
                r.ocupacion,
                r.lugar_trabajo,
                par.parentesco,
                ea.status AS estado_acceso,
                ei.status AS estado_institucional,
                GROUP_CONCAT(DISTINCT tel.numero_telefono SEPARATOR ' || ') AS numeros,
                GROUP_CONCAT(DISTINCT tipo_tel.tipo_telefono SEPARATOR ' || ') AS tipos
            FROM representante AS r
            INNER JOIN persona AS p ON p.IdPersona = r.IdPersona
            LEFT JOIN nacionalidad AS n ON n.IdNacionalidad = p.IdNacionalidad
            LEFT JOIN sexo AS sx ON sx.IdSexo = p.IdSexo
            LEFT JOIN urbanismo AS u ON u.IdUrbanismo = p.IdUrbanismo
            LEFT JOIN parentesco AS par ON par.IdParentesco = r.IdParentesco
            LEFT JOIN telefono AS tel ON tel.IdPersona = p.IdPersona
            LEFT JOIN tipo_telefono AS tipo_tel ON tipo_tel.IdTipo_Telefono = tel.IdTipo_Telefono
            LEFT JOIN status AS ea ON ea.IdStatus = p.IdEstadoAcceso
            LEFT JOIN status AS ei ON ei.IdStatus = p.IdEstadoInstitucional
            WHERE r.IdEstudiante = :id
            GROUP BY p.IdPersona
            ORDER BY p.apellido, p.nombre
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorIdPersona($idPersona) {
        $query = "
            SELECT 
                r.IdRepresentante,
                r.IdPersona,
                r.IdEstudiante,
                r.IdParentesco,
                r.ocupacion,
                r.lugar_trabajo,

                -- Persona
                p.nombre,
                p.apellido,
                p.cedula,
                p.correo,
                p.direccion,
                p.IdSexo,
                p.IdNacionalidad,
                p.IdUrbanismo,
                p.IdEstadoAcceso,
                p.IdEstadoInstitucional,

                -- Relaciones descriptivas
                s.sexo,
                n.nacionalidad,
                u.urbanismo,
                pa.parentesco,

                -- Estados descriptivos
                ea.status AS estado_acceso,
                ei.status AS estado_institucional,

                -- Teléfonos agrupados
                GROUP_CONCAT(DISTINCT tel.numero_telefono SEPARATOR ' || ') AS numeros,
                GROUP_CONCAT(DISTINCT tt.tipo_telefono SEPARATOR ' || ') AS tipos,

                -- Contacto de emergencia
                MAX(dp.IdPerfil = 5) AS contacto_emergencia

            FROM representante r
            INNER JOIN persona p ON r.IdPersona = p.IdPersona
            LEFT JOIN sexo s ON p.IdSexo = s.IdSexo
            LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
            LEFT JOIN urbanismo u ON p.IdUrbanismo = u.IdUrbanismo
            LEFT JOIN parentesco pa ON r.IdParentesco = pa.IdParentesco
            LEFT JOIN status ea ON ea.IdStatus = p.IdEstadoAcceso
            LEFT JOIN status ei ON ei.IdStatus = p.IdEstadoInstitucional
            LEFT JOIN telefono tel ON tel.IdPersona = p.IdPersona
            LEFT JOIN tipo_telefono tt ON tt.IdTipo_Telefono = tel.IdTipo_Telefono
            LEFT JOIN detalle_perfil dp ON dp.IdPersona = p.IdPersona

            WHERE p.IdPersona = ?
            GROUP BY p.IdPersona
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idPersona, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function obtenerEstudiantesPorRepresentante($idPersona) {
        $sql = "
            SELECT 
                p.IdPersona AS IdEstudiante,
                p.cedula,
                n.nacionalidad,
                p.nombre,
                p.apellido,
                p.fecha_nacimiento,
                sx.sexo,
                u.urbanismo,
                p.direccion,
                CONCAT(c.curso, ' \"', s.seccion, '\"') AS curso_actual
            FROM representante r
            INNER JOIN persona p ON p.IdPersona = r.IdEstudiante
            LEFT JOIN nacionalidad n ON n.IdNacionalidad = p.IdNacionalidad
            LEFT JOIN sexo sx ON sx.IdSexo = p.IdSexo
            LEFT JOIN urbanismo u ON u.IdUrbanismo = p.IdUrbanismo
            LEFT JOIN inscripcion i ON i.IdEstudiante = p.IdPersona
            LEFT JOIN curso_seccion cs ON cs.IdCurso_Seccion = i.IdCurso_Seccion
            LEFT JOIN curso c ON c.IdCurso = cs.IdCurso
            LEFT JOIN seccion s ON s.IdSeccion = cs.IdSeccion
            WHERE r.IdPersona = :id
            GROUP BY p.IdPersona
            ORDER BY p.apellido, p.nombre
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $idPersona, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodos($idPerfil, $idPersona) {
        // === 1. Obtener los perfiles del usuario ===
        $sqlPerfiles = "SELECT IdPerfil FROM detalle_perfil WHERE IdPersona = :idPersona";
        $stmtPerfiles = $this->conn->prepare($sqlPerfiles);
        $stmtPerfiles->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
        $stmtPerfiles->execute();
        $perfilesUsuario = $stmtPerfiles->fetchAll(PDO::FETCH_COLUMN);

        // === 2. Determinar si tiene acceso total ===
        $perfilesAutorizadosTotales = [1, 6, 7]; // Administrador, Director, Control de Estudios
        $tieneAccesoTotal = !empty(array_intersect($perfilesUsuario, $perfilesAutorizadosTotales));

        // === 3. Determinar niveles que puede ver ===
        $nivelesPermitidos = [];
        if (in_array(8, $perfilesUsuario)) $nivelesPermitidos[] = 1; // Inicial
        if (in_array(9, $perfilesUsuario)) $nivelesPermitidos[] = 2; // Primaria
        if (in_array(10, $perfilesUsuario)) $nivelesPermitidos[] = 3; // Media General

        // === 4. Consulta base ===
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
                r.IdRepresentante,
                r.IdParentesco,
                par.parentesco,
                COUNT(DISTINCT i.IdEstudiante) AS cantidad_estudiantes,
                GROUP_CONCAT(DISTINCT niv.nivel SEPARATOR ', ') AS niveles_hijos,
                MAX(dp.IdPerfil = 5) AS contacto_emergencia
            FROM representante AS r
            INNER JOIN persona AS p ON p.IdPersona = r.IdPersona
            LEFT JOIN nacionalidad AS n ON n.IdNacionalidad = p.IdNacionalidad
            LEFT JOIN sexo AS sx ON sx.IdSexo = p.IdSexo
            LEFT JOIN parentesco AS par ON par.IdParentesco = r.IdParentesco
            LEFT JOIN detalle_perfil AS dp ON dp.IdPersona = p.IdPersona
            INNER JOIN inscripcion AS i ON i.IdEstudiante = r.IdEstudiante
            INNER JOIN curso_seccion AS cs ON cs.IdCurso_Seccion = i.IdCurso_Seccion
            INNER JOIN curso AS c ON c.IdCurso = cs.IdCurso
            INNER JOIN nivel AS niv ON niv.IdNivel = c.IdNivel
        ";

        // === 5. Aplicar filtro si no tiene acceso total ===
        if (!$tieneAccesoTotal && !empty($nivelesPermitidos)) {
            $nivelesIn = implode(",", array_map('intval', $nivelesPermitidos));
            $query .= " AND niv.IdNivel IN ($nivelesIn)";
        }

        // === 6. Agrupar y ordenar ===
        $query .= "
            AND p.IdEstadoInstitucional != 2
            GROUP BY p.IdPersona
            ORDER BY p.apellido, p.nombre
        ";

        // === 7. Ejecutar ===
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}