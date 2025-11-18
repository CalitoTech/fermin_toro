<?php
require_once __DIR__ . '/../config/conexion.php';
class Requisito {
    private $conn;
    public $IdRequisito;
    public $requisito;
    public $obligatorio;
    public $IdNivel;
    public $IdTipoTrabajador;
    public $IdTipo_Requisito;
    public $solo_plantel_privado;
    public $descripcion_adicional;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtiene requisitos por nivel, considerando requisitos generales y específicos
     * También filtra por tipo de trabajador y plantel privado si aplica
     */
    public function obtenerPorNivel($idNivel, $idTipoTrabajador = null, $esPlantelPrivado = false) {
        $query = "
            SELECT
                r.*,
                tr.tipo_requisito,
                tt.tipo_trabajador
            FROM requisito r
            INNER JOIN tipo_requisito tr ON r.IdTipo_Requisito = tr.IdTipo_Requisito
            LEFT JOIN tipo_trabajador tt ON r.IdTipoTrabajador = tt.IdTipoTrabajador
            WHERE (r.IdNivel = :idNivel OR r.IdNivel IS NULL)
        ";

        // Filtrar por tipo de trabajador
        if ($idTipoTrabajador !== null) {
            $query .= " AND (r.IdTipoTrabajador = :idTipoTrabajador OR r.IdTipoTrabajador IS NULL)";
        } else {
            $query .= " AND r.IdTipoTrabajador IS NULL";
        }

        // Filtrar por plantel privado
        if (!$esPlantelPrivado) {
            $query .= " AND r.solo_plantel_privado = FALSE";
        }

        $query .= " ORDER BY tr.IdTipo_Requisito, r.obligatorio DESC, r.requisito";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idNivel', $idNivel, PDO::PARAM_INT);

        if ($idTipoTrabajador !== null) {
            $stmt->bindParam(':idTipoTrabajador', $idTipoTrabajador, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardar() {
        $query = "INSERT INTO requisito SET
            requisito = :requisito,
            obligatorio = :obligatorio,
            IdNivel = :IdNivel,
            IdTipoTrabajador = :IdTipoTrabajador,
            IdTipo_Requisito = :IdTipo_Requisito,
            solo_plantel_privado = :solo_plantel_privado,
            descripcion_adicional = :descripcion_adicional";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->requisito = htmlspecialchars(strip_tags($this->requisito));
        $this->obligatorio = (bool)$this->obligatorio;
        $this->solo_plantel_privado = (bool)$this->solo_plantel_privado;

        // Vincular valores
        $stmt->bindParam(":requisito", $this->requisito);
        $stmt->bindParam(":obligatorio", $this->obligatorio, PDO::PARAM_BOOL);
        $stmt->bindParam(":IdNivel", $this->IdNivel, PDO::PARAM_INT);
        $stmt->bindParam(":IdTipoTrabajador", $this->IdTipoTrabajador, PDO::PARAM_INT);
        $stmt->bindParam(":IdTipo_Requisito", $this->IdTipo_Requisito, PDO::PARAM_INT);
        $stmt->bindParam(":solo_plantel_privado", $this->solo_plantel_privado, PDO::PARAM_BOOL);
        $stmt->bindParam(":descripcion_adicional", $this->descripcion_adicional);

        return $stmt->execute();
    }

    public function actualizar() {
        $query = "UPDATE requisito SET
            requisito = :requisito,
            IdNivel = :nivel,
            obligatorio = :obligatorio,
            IdTipoTrabajador = :IdTipoTrabajador,
            IdTipo_Requisito = :IdTipo_Requisito,
            solo_plantel_privado = :solo_plantel_privado,
            descripcion_adicional = :descripcion_adicional
            WHERE IdRequisito = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':requisito', $this->requisito);
        $stmt->bindParam(':nivel', $this->IdNivel, PDO::PARAM_INT);
        $stmt->bindParam(':obligatorio', $this->obligatorio, PDO::PARAM_BOOL);
        $stmt->bindParam(':IdTipoTrabajador', $this->IdTipoTrabajador, PDO::PARAM_INT);
        $stmt->bindParam(':IdTipo_Requisito', $this->IdTipo_Requisito, PDO::PARAM_INT);
        $stmt->bindParam(':solo_plantel_privado', $this->solo_plantel_privado, PDO::PARAM_BOOL);
        $stmt->bindParam(':descripcion_adicional', $this->descripcion_adicional);
        $stmt->bindParam(':id', $this->IdRequisito, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function eliminar() {
        // Primero verificar dependencias
        if ($this->tieneDependencias()) {
            return false; // No se puede eliminar porque tiene dependencias
        }

        $query = "DELETE FROM requisito WHERE IdRequisito = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdRequisito);
        return $stmt->execute();
    }

    public function tieneDependencias() {
        // Verificar si el requisito está siendo usado en la tabla inscripcion_requisito
        $query = "SELECT COUNT(*) as total FROM inscripcion_requisito WHERE IdRequisito = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdRequisito);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($resultado['total'] > 0);
    }

    public function obtenerPorId($id) {
        $query = "
            SELECT
                r.*,
                tr.tipo_requisito,
                tt.tipo_trabajador
            FROM requisito r
            INNER JOIN tipo_requisito tr ON r.IdTipo_Requisito = tr.IdTipo_Requisito
            LEFT JOIN tipo_trabajador tt ON r.IdTipoTrabajador = tt.IdTipoTrabajador
            WHERE r.IdRequisito = :id
            LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->IdRequisito = $row['IdRequisito'];
            $this->requisito = $row['requisito'];
            $this->obligatorio = $row['obligatorio'];
            $this->IdNivel = $row['IdNivel'];
            $this->IdTipoTrabajador = $row['IdTipoTrabajador'];
            $this->IdTipo_Requisito = $row['IdTipo_Requisito'];
            $this->solo_plantel_privado = $row['solo_plantel_privado'];
            $this->descripcion_adicional = $row['descripcion_adicional'];
            return $row;
        }

        return false;
    }

    /**
     * Obtiene requisitos con estado de cumplimiento para una inscripción específica
     * Considera el nivel, tipo de trabajador del representante y si el plantel anterior es privado
     * EXCLUYE requisitos de tipo Uniforme (IdTipo_Requisito = 2)
     */
    public function obtenerConCumplidoPorNivel($idInscripcion) {
        $query = "
            SELECT
                r.IdRequisito,
                r.requisito,
                r.obligatorio,
                r.descripcion_adicional,
                tr.tipo_requisito,
                tt.tipo_trabajador,
                ir.cumplido,
                n.nivel
            FROM requisito r
            INNER JOIN tipo_requisito tr ON r.IdTipo_Requisito = tr.IdTipo_Requisito
            LEFT JOIN tipo_trabajador tt ON r.IdTipoTrabajador = tt.IdTipoTrabajador
            LEFT JOIN nivel n ON r.IdNivel = n.IdNivel
            LEFT JOIN inscripcion_requisito ir
                ON r.IdRequisito = ir.IdRequisito
                AND ir.IdInscripcion = :id
            WHERE r.IdTipo_Requisito != 2
            AND (
                r.IdNivel = (
                    SELECT c.IdNivel
                    FROM curso c
                    INNER JOIN curso_seccion cs ON c.IdCurso = cs.IdCurso
                    INNER JOIN inscripcion i ON cs.IdCurso_Seccion = i.IdCurso_Seccion
                    WHERE i.IdInscripcion = :id
                )
                OR r.IdNivel IS NULL
            )
            AND (
                r.IdTipoTrabajador = (
                    SELECT p.IdTipoTrabajador
                    FROM inscripcion i
                    INNER JOIN representante rep ON i.responsable_inscripcion = rep.IdRepresentante
                    INNER JOIN persona p ON rep.IdPersona = p.IdPersona
                    WHERE i.IdInscripcion = :id
                )
                OR r.IdTipoTrabajador IS NULL
            )
            AND (
                r.solo_plantel_privado = FALSE
                OR (
                    r.solo_plantel_privado = TRUE
                    AND EXISTS (
                        SELECT 1
                        FROM inscripcion i
                        INNER JOIN plantel pl ON i.ultimo_plantel = pl.IdPlantel
                        WHERE i.IdInscripcion = :id
                        AND pl.es_privado = TRUE
                    )
                )
            )
            ORDER BY tr.IdTipo_Requisito, r.obligatorio DESC, r.requisito
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $idInscripcion, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerRequisitos($idPersona) {
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
            $filtroNivel = "WHERE (r.IdNivel IN ($nivelesIn) OR r.IdNivel IS NULL)";
        } else {
            $filtroNivel = "WHERE 1=1";
        }

        $query = "
            SELECT
                r.IdRequisito,
                r.requisito,
                r.obligatorio,
                r.descripcion_adicional,
                r.solo_plantel_privado,
                COALESCE(n.nivel, 'Todos los niveles') AS nivel,
                tr.tipo_requisito,
                tt.tipo_trabajador
            FROM requisito r
            INNER JOIN tipo_requisito tr ON r.IdTipo_Requisito = tr.IdTipo_Requisito
            LEFT JOIN nivel n ON r.IdNivel = n.IdNivel
            LEFT JOIN tipo_trabajador tt ON r.IdTipoTrabajador = tt.IdTipoTrabajador
            $filtroNivel
            ORDER BY
                CASE WHEN r.IdNivel IS NULL THEN 0 ELSE r.IdNivel END,
                tr.IdTipo_Requisito,
                r.obligatorio DESC,
                r.requisito
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
