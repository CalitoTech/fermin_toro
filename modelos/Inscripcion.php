<?php
require_once __DIR__ . '/../config/conexion.php';

class Inscripcion {
    private $conn;
    public $IdInscripcion;
    public $IdTipo_Inscripcion;
    public $codigo_inscripcion;
    public $IdEstudiante;
    public $fecha_inscripcion;
    public $ultimo_plantel;
    public $responsable_inscripcion;
    public $IdFecha_Escolar;
    public $IdStatus;
    public $IdCurso_Seccion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO inscripcion (
            IdTipo_Inscripcion, codigo_inscripcion, IdEstudiante, fecha_inscripcion, ultimo_plantel,
            responsable_inscripcion, IdFecha_Escolar, IdStatus, IdCurso_Seccion
        ) VALUES (
            :IdTipo_Inscripcion, :codigo_inscripcion, :IdEstudiante, :fecha_inscripcion, :ultimo_plantel,
            :responsable_inscripcion, :IdFecha_Escolar, :IdStatus, :IdCurso_Seccion
        )";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdTipo_Inscripcion = htmlspecialchars(strip_tags($this->IdTipo_Inscripcion));
        $this->codigo_inscripcion = htmlspecialchars(strip_tags($this->codigo_inscripcion));
        $this->IdEstudiante = htmlspecialchars(strip_tags($this->IdEstudiante));
        $this->ultimo_plantel = htmlspecialchars(strip_tags($this->ultimo_plantel));
        $this->responsable_inscripcion = htmlspecialchars(strip_tags($this->responsable_inscripcion));
        $this->IdFecha_Escolar = htmlspecialchars(strip_tags($this->IdFecha_Escolar));
        $this->IdStatus = htmlspecialchars(strip_tags($this->IdStatus));
        $this->IdCurso_Seccion = htmlspecialchars(strip_tags($this->IdCurso_Seccion));

        // Vincular valores
        $stmt->bindParam(":IdTipo_Inscripcion", $this->IdTipo_Inscripcion, PDO::PARAM_INT);
        $stmt->bindParam(":codigo_inscripcion", $this->codigo_inscripcion);
        $stmt->bindParam(":IdEstudiante", $this->IdEstudiante, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_inscripcion", $this->fecha_inscripcion);

        // ultimo_plantel ahora es INT (ID del plantel), pero puede ser NULL
        if ($this->ultimo_plantel === null || $this->ultimo_plantel === '') {
            $stmt->bindValue(":ultimo_plantel", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(":ultimo_plantel", $this->ultimo_plantel, PDO::PARAM_INT);
        }

        $stmt->bindParam(":responsable_inscripcion", $this->responsable_inscripcion, PDO::PARAM_INT);
        $stmt->bindParam(":IdFecha_Escolar", $this->IdFecha_Escolar, PDO::PARAM_INT);
        $stmt->bindParam(":IdStatus", $this->IdStatus, PDO::PARAM_INT);
        $stmt->bindParam(":IdCurso_Seccion", $this->IdCurso_Seccion, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->IdInscripcion = $this->conn->lastInsertId();
            return $this->IdInscripcion;
        }
        return false;
    }

    public function obtenerPorEstudiante($idEstudiante) {
        $query = "SELECT i.*, s.status, f.fecha_escolar, cs.IdCurso, cs.IdSeccion
                 FROM inscripcion i
                 JOIN status s ON i.IdStatus = s.IdStatus
                 JOIN fecha_escolar f ON i.IdFecha_Escolar = f.IdFecha_Escolar
                 JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
                 WHERE i.IdEstudiante = :IdEstudiante
                 ORDER BY i.fecha_inscripcion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":IdEstudiante", $idEstudiante, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($idInscripcion) {
        $query = "SELECT i.*, s.status, f.fecha_escolar, cs.IdCurso, cs.IdSeccion
                 FROM inscripcion i
                 JOIN status s ON i.IdStatus = s.IdStatus
                 JOIN fecha_escolar f ON i.IdFecha_Escolar = f.IdFecha_Escolar
                 JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
                 WHERE i.IdInscripcion = :IdInscripcion
                 LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":IdInscripcion", $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarStatus($idInscripcion, $nuevoStatus) {
        $query = "UPDATE inscripcion SET IdStatus = :IdStatus WHERE IdInscripcion = :IdInscripcion";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":IdStatus", $nuevoStatus, PDO::PARAM_INT);
        $stmt->bindParam(":IdInscripcion", $idInscripcion, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function generarCodigoInscripcion() {
        $prefix = "INS-";
        $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $this->codigo_inscripcion = $prefix . date('Ymd') . '-' . $random;
        return $this->codigo_inscripcion;
    }

    public function obtenerDetallePorId($idInscripcion) {
        $query = "SELECT
            -- Datos de la inscripción
            i.IdInscripcion,
            i.codigo_inscripcion,
            i.fecha_inscripcion,
            i.ultimo_plantel,
            pl.plantel AS plantel_nombre,
            i.modificado_por,
            i.ultima_modificacion,
            i.IdCurso_Seccion,
            c.curso,
            c.IdCurso,
            s.seccion,
            n.nivel,
            fe.fecha_escolar,
            st.status AS status_inscripcion,
            st.IdStatus,

            -- Estudiante
            e.IdPersona AS id_estudiante,
            e.nombre AS estudiante_nombre,
            e.apellido AS estudiante_apellido,
            e.cedula AS estudiante_cedula,
            e.fecha_nacimiento AS estudiante_fecha_nacimiento,
            e.correo AS estudiante_correo,
            e.direccion AS estudiante_direccion,
            e.lugar_nacimiento AS estudiante_lugar_nacimiento,
            sexo_e.sexo AS estudiante_sexo,
            urb_e.urbanismo AS estudiante_urbanismo,
            urb_e.IdUrbanismo AS estudiante_id_urbanismo,
            nac_e.nacionalidad AS estudiante_nacionalidad,
            CONCAT(COALESCE(pref_e.codigo_prefijo, ''), tel_e.numero_telefono) AS estudiante_telefono,
            tipo_tel_e.tipo_telefono AS estudiante_tipo_telefono,
            (
                SELECT COUNT(DISTINCT r_hermanos.IdEstudiante) - 1
                FROM representante r_hermanos
                WHERE r_hermanos.IdPersona IN (
                    SELECT rp_padres.IdPersona
                    FROM representante rp_padres
                    WHERE rp_padres.IdEstudiante = e.IdPersona
                    AND rp_padres.IdParentesco IN (1, 2)
                )
                AND r_hermanos.IdParentesco IN (1, 2)
            ) AS nro_hermanos,
            (
                SELECT GROUP_CONCAT(DISTINCT c_h.curso ORDER BY c_h.curso SEPARATOR ', ')
                FROM representante r_hermanos
                INNER JOIN inscripcion i_h ON r_hermanos.IdEstudiante = i_h.IdEstudiante
                INNER JOIN curso_seccion cs_h ON i_h.IdCurso_Seccion = cs_h.IdCurso_Seccion
                INNER JOIN curso c_h ON cs_h.IdCurso = c_h.IdCurso
                WHERE r_hermanos.IdPersona IN (
                    SELECT rp_padres.IdPersona
                    FROM representante rp_padres
                    WHERE rp_padres.IdEstudiante = e.IdPersona
                    AND rp_padres.IdParentesco IN (1, 2)
                )
                AND r_hermanos.IdParentesco IN (1, 2)
                AND r_hermanos.IdEstudiante != e.IdPersona
            ) AS cursos_hermanos,

            -- Representante Legal
            rp.IdRepresentante,
            rp.IdParentesco,
            resp.IdPersona AS id_responsable,
            resp.nombre AS responsable_nombre,
            resp.apellido AS responsable_apellido,
            resp.cedula AS responsable_cedula,
            resp.correo AS responsable_correo,
            resp.direccion AS responsable_direccion,
            sexo_r.sexo AS responsable_sexo,
            urb_r.urbanismo AS responsable_urbanismo,
            nac_r.nacionalidad AS responsable_nacionalidad,
            parent.parentesco AS responsable_parentesco,
            rp.ocupacion AS responsable_ocupacion,
            rp.lugar_trabajo AS responsable_lugar_trabajo,
            tt_r.tipo_trabajador AS responsable_tipo_trabajador,
            GROUP_CONCAT(DISTINCT CONCAT(COALESCE(pref_r.codigo_prefijo, ''), tel_r.numero_telefono) ORDER BY tel_r.IdTelefono SEPARATOR '||') AS responsable_numeros,
            GROUP_CONCAT(DISTINCT tipo_tel_r.tipo_telefono ORDER BY tel_r.IdTelefono SEPARATOR '||') AS responsable_tipos,

            -- Padre
            padre.IdPersona AS id_padre,
            padre.nombre AS padre_nombre,
            padre.apellido AS padre_apellido,
            padre.cedula AS padre_cedula,
            padre.correo AS padre_correo,
            padre.direccion AS padre_direccion,
            sexo_p.sexo AS padre_sexo,
            urb_p.urbanismo AS padre_urbanismo,
            nac_p.nacionalidad AS padre_nacionalidad,
            rp_padre.ocupacion AS padre_ocupacion,
            rp_padre.lugar_trabajo AS padre_lugar_trabajo,
            tt_p.tipo_trabajador AS padre_tipo_trabajador,
            GROUP_CONCAT(DISTINCT CONCAT(COALESCE(pref_p.codigo_prefijo, ''), tel_p.numero_telefono) ORDER BY tel_p.IdTelefono SEPARATOR '||') AS padre_numeros,
            GROUP_CONCAT(DISTINCT tipo_tel_p.tipo_telefono ORDER BY tel_p.IdTelefono SEPARATOR '||') AS padre_tipos,

            -- Madre
            madre.IdPersona AS id_madre,
            madre.nombre AS madre_nombre,
            madre.apellido AS madre_apellido,
            madre.cedula AS madre_cedula,
            madre.correo AS madre_correo,
            madre.direccion AS madre_direccion,
            sexo_m.sexo AS madre_sexo,
            urb_m.urbanismo AS madre_urbanismo,
            nac_m.nacionalidad AS madre_nacionalidad,
            rp_madre.ocupacion AS madre_ocupacion,
            rp_madre.lugar_trabajo AS madre_lugar_trabajo,
            tt_m.tipo_trabajador AS madre_tipo_trabajador,
            GROUP_CONCAT(DISTINCT CONCAT(COALESCE(pref_m.codigo_prefijo, ''), tel_m.numero_telefono) ORDER BY tel_m.IdTelefono SEPARATOR '||') AS madre_numeros,
            GROUP_CONCAT(DISTINCT tipo_tel_m.tipo_telefono ORDER BY tel_m.IdTelefono SEPARATOR '||') AS madre_tipos,

            -- Contacto de emergencia
            ce.IdPersona AS id_contacto,
            ce.nombre AS contacto_nombre,
            ce.apellido AS contacto_apellido,
            ce.cedula AS contacto_cedula,
            ce.correo AS contacto_correo,
            ce.direccion AS contacto_direccion,
            parent_ce.parentesco AS contacto_parentesco,
            rp_ce.ocupacion AS contacto_ocupacion,
            rp_ce.lugar_trabajo AS contacto_lugar_trabajo,
            CONCAT(COALESCE(pref_ce.codigo_prefijo, ''), tel_ce.numero_telefono) AS contacto_telefono,
            tipo_tel.tipo_telefono AS contacto_tipo_telefono

        FROM inscripcion i
        INNER JOIN persona e ON i.IdEstudiante = e.IdPersona
        INNER JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
        INNER JOIN curso c ON cs.IdCurso = c.IdCurso
        INNER JOIN seccion s ON cs.IdSeccion = s.IdSeccion
        INNER JOIN nivel n ON c.IdNivel = n.IdNivel
        INNER JOIN fecha_escolar fe ON i.IdFecha_Escolar = fe.IdFecha_Escolar
        INNER JOIN status st ON i.IdStatus = st.IdStatus
        LEFT JOIN plantel pl ON i.ultimo_plantel = pl.IdPlantel

        -- Representante Legal
        INNER JOIN representante rp ON i.responsable_inscripcion = rp.IdRepresentante
        INNER JOIN persona resp ON rp.IdPersona = resp.IdPersona
        LEFT JOIN sexo sexo_r ON resp.IdSexo = sexo_r.IdSexo
        LEFT JOIN urbanismo urb_r ON resp.IdUrbanismo = urb_r.IdUrbanismo
        LEFT JOIN nacionalidad nac_r ON resp.IdNacionalidad = nac_r.IdNacionalidad
        LEFT JOIN parentesco parent ON rp.IdParentesco = parent.IdParentesco
        LEFT JOIN tipo_trabajador tt_r ON resp.IdTipoTrabajador = tt_r.IdTipoTrabajador
        LEFT JOIN telefono tel_r ON resp.IdPersona = tel_r.IdPersona
        LEFT JOIN tipo_telefono tipo_tel_r ON tel_r.IdTipo_Telefono = tipo_tel_r.IdTipo_Telefono
        LEFT JOIN prefijo pref_r ON tel_r.IdPrefijo = pref_r.IdPrefijo

        -- Padre
        LEFT JOIN representante rp_padre ON e.IdPersona = rp_padre.IdEstudiante AND rp_padre.IdParentesco = 1
        LEFT JOIN persona padre ON rp_padre.IdPersona = padre.IdPersona
        LEFT JOIN sexo sexo_p ON padre.IdSexo = sexo_p.IdSexo
        LEFT JOIN urbanismo urb_p ON padre.IdUrbanismo = urb_p.IdUrbanismo
        LEFT JOIN nacionalidad nac_p ON padre.IdNacionalidad = nac_p.IdNacionalidad
        LEFT JOIN tipo_trabajador tt_p ON padre.IdTipoTrabajador = tt_p.IdTipoTrabajador
        LEFT JOIN telefono tel_p ON padre.IdPersona = tel_p.IdPersona
        LEFT JOIN tipo_telefono tipo_tel_p ON tel_p.IdTipo_Telefono = tipo_tel_p.IdTipo_Telefono
        LEFT JOIN prefijo pref_p ON tel_p.IdPrefijo = pref_p.IdPrefijo

        -- Madre
        LEFT JOIN representante rp_madre ON e.IdPersona = rp_madre.IdEstudiante AND rp_madre.IdParentesco = 2
        LEFT JOIN persona madre ON rp_madre.IdPersona = madre.IdPersona
        LEFT JOIN sexo sexo_m ON madre.IdSexo = sexo_m.IdSexo
        LEFT JOIN urbanismo urb_m ON madre.IdUrbanismo = urb_m.IdUrbanismo
        LEFT JOIN nacionalidad nac_m ON madre.IdNacionalidad = nac_m.IdNacionalidad
        LEFT JOIN tipo_trabajador tt_m ON madre.IdTipoTrabajador = tt_m.IdTipoTrabajador
        LEFT JOIN telefono tel_m ON madre.IdPersona = tel_m.IdPersona
        LEFT JOIN tipo_telefono tipo_tel_m ON tel_m.IdTipo_Telefono = tipo_tel_m.IdTipo_Telefono
        LEFT JOIN prefijo pref_m ON tel_m.IdPrefijo = pref_m.IdPrefijo

        -- Contacto de emergencia
        LEFT JOIN representante rp_ce ON e.IdPersona = rp_ce.IdEstudiante
            AND EXISTS (
                SELECT 1 FROM detalle_perfil dp
                WHERE dp.IdPersona = rp_ce.IdPersona AND dp.IdPerfil = 5
            )
        LEFT JOIN persona ce ON rp_ce.IdPersona = ce.IdPersona
        LEFT JOIN parentesco parent_ce ON rp_ce.IdParentesco = parent_ce.IdParentesco
        LEFT JOIN telefono tel_ce ON ce.IdPersona = tel_ce.IdPersona
        LEFT JOIN tipo_telefono tipo_tel ON tel_ce.IdTipo_Telefono = tipo_tel.IdTipo_Telefono
        LEFT JOIN prefijo pref_ce ON tel_ce.IdPrefijo = pref_ce.IdPrefijo

        -- Datos del estudiante
        LEFT JOIN sexo sexo_e ON e.IdSexo = sexo_e.IdSexo
        LEFT JOIN urbanismo urb_e ON e.IdUrbanismo = urb_e.IdUrbanismo
        LEFT JOIN nacionalidad nac_e ON e.IdNacionalidad = nac_e.IdNacionalidad
        LEFT JOIN telefono tel_e ON e.IdPersona = tel_e.IdPersona
        LEFT JOIN tipo_telefono tipo_tel_e ON tel_e.IdTipo_Telefono = tipo_tel_e.IdTipo_Telefono
        LEFT JOIN prefijo pref_e ON tel_e.IdPrefijo = pref_e.IdPrefijo

        WHERE i.IdInscripcion = :id;";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerTodas($idPerfil, $idPersona) {
        // === Obtener todos los perfiles del usuario ===
        $sqlPerfiles = "SELECT IdPerfil FROM detalle_perfil WHERE IdPersona = :idPersona";
        $stmtPerfiles = $this->conn->prepare($sqlPerfiles);
        $stmtPerfiles->bindParam(':idPersona', $idPersona, PDO::PARAM_INT);
        $stmtPerfiles->execute();
        $perfilesUsuario = $stmtPerfiles->fetchAll(PDO::FETCH_COLUMN);

        // === Perfiles con acceso total ===
        $perfilesAutorizadosTotales = [1, 6, 7]; // Administrador, Director, Control de Estudios
        $tieneAccesoTotal = !empty(array_intersect($perfilesUsuario, $perfilesAutorizadosTotales));

        // === Determinar qué niveles puede ver ===
        $nivelesPermitidos = [];
        if (in_array(8, $perfilesUsuario)) $nivelesPermitidos[] = 1; // Inicial
        if (in_array(9, $perfilesUsuario)) $nivelesPermitidos[] = 2; // Primaria
        if (in_array(10, $perfilesUsuario)) $nivelesPermitidos[] = 3; // Media General

        // === Construir condición WHERE dinámica ===
        $filtroNivel = "";
        if (!$tieneAccesoTotal && !empty($nivelesPermitidos)) {
            $nivelesIn = implode(",", array_map('intval', $nivelesPermitidos));
            $filtroNivel = "WHERE n.IdNivel IN ($nivelesIn)";
        }

        // === Consulta principal ===
        $query = "
            SELECT
                i.IdInscripcion,
                e.nombre AS nombre_estudiante,
                e.apellido AS apellido_estudiante,
                i.codigo_inscripcion,
                rps.nombre AS nombre_responsable,
                rps.apellido AS apellido_responsable,
                i.fecha_inscripcion,
                cs.IdCurso AS IdCurso,
                cs.IdSeccion AS IdSeccion,
                c.curso,
                s.seccion,
                n.IdNivel,
                n.nivel AS nivel,
                i.IdFecha_Escolar,
                fe.fecha_escolar,
                st.status,
                i.IdStatus,
                ti.tipo_inscripcion
            FROM inscripcion i
            INNER JOIN persona AS e ON i.IdEstudiante = e.IdPersona
            INNER JOIN representante rp ON i.responsable_inscripcion = rp.IdRepresentante
            INNER JOIN persona AS rps ON rp.IdPersona = rps.IdPersona
            INNER JOIN fecha_escolar fe ON i.IdFecha_Escolar = fe.IdFecha_Escolar
            INNER JOIN curso_seccion cs ON i.IdCurso_Seccion = cs.IdCurso_Seccion
            LEFT JOIN curso c ON cs.IdCurso = c.IdCurso
            LEFT JOIN seccion s ON cs.IdSeccion = s.IdSeccion
            LEFT JOIN nivel n ON c.IdNivel = n.IdNivel
            INNER JOIN status st ON i.IdStatus = st.IdStatus
            LEFT JOIN tipo_inscripcion ti ON i.IdTipo_Inscripcion = ti.IdTipo_Inscripcion
            $filtroNivel
            ORDER BY i.fecha_inscripcion ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula el número de hermanos de un estudiante
     * Cuenta los estudiantes del padre + los de la madre, sin duplicados y sin contar al propio estudiante
     *
     * @param int $idEstudiante ID del estudiante para calcular hermanos
     * @return int Número de hermanos
     */
    public function calcularNumeroHermanos($idEstudiante) {
        try {
            $query = "SELECT COUNT(DISTINCT CASE
                        WHEN r.IdEstudiante != :idEstudiante THEN r.IdEstudiante
                        ELSE NULL
                    END) as total_hermanos
                    FROM representante r
                    WHERE r.IdPersona IN (
                        -- Obtener IDs del padre y la madre del estudiante
                        SELECT r2.IdPersona
                        FROM representante r2
                        WHERE r2.IdEstudiante = :idEstudiante
                        AND r2.IdParentesco IN (1, 2) -- 1 = Padre, 2 = Madre
                    )";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
            $stmt->execute();

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($resultado['total_hermanos'] ?? 0);

        } catch (Exception $e) {
            error_log("Error al calcular hermanos: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene el último plantel del estudiante basado en su inscripción más reciente
     *
     * @param int $idEstudiante ID del estudiante
     * @return string|null Último plantel o null si no tiene inscripciones previas
     */
    public function obtenerUltimoPlantel($idEstudiante) {
        try {
            $query = "SELECT ultimo_plantel
                     FROM inscripcion
                     WHERE IdEstudiante = :idEstudiante
                     AND ultimo_plantel IS NOT NULL
                     AND ultimo_plantel != ''
                     ORDER BY fecha_inscripcion DESC
                     LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':idEstudiante', $idEstudiante, PDO::PARAM_INT);
            $stmt->execute();

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado['ultimo_plantel'] ?? null;

        } catch (Exception $e) {
            error_log("Error al obtener último plantel: " . $e->getMessage());
            return null;
        }
    }
}