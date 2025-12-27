<?php
require_once __DIR__ . '/../config/conexion.php';

class MensajeWhatsapp {
    private $conn;
    private $table = "mensaje_whatsapp";

    public $IdMensajeWhatsapp;
    public $IdStatus;
    public $titulo;
    public $contenido;
    public $incluir_requisitos;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtiene el mensaje activo para un status específico
     */
    public function obtenerPorStatus($idStatus) {
        $query = "SELECT m.*, s.status as nombre_status
                  FROM " . $this->table . " m
                  INNER JOIN status s ON m.IdStatus = s.IdStatus
                  WHERE m.IdStatus = :idStatus AND m.activo = TRUE
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idStatus', $idStatus, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->cargarDatos($row);
            return $row;
        }

        return false;
    }

    /**
     * Obtiene mensaje por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT m.*, s.status as nombre_status
                  FROM " . $this->table . " m
                  INNER JOIN status s ON m.IdStatus = s.IdStatus
                  WHERE m.IdMensajeWhatsapp = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->cargarDatos($row);
            return $row;
        }

        return false;
    }

    /**
     * Obtiene todos los mensajes con información del status
     */
    public function obtenerTodos() {
        $query = "SELECT m.*, s.status as nombre_status
                  FROM " . $this->table . " m
                  INNER JOIN status s ON m.IdStatus = s.IdStatus
                  ORDER BY m.IdStatus ASC, m.activo DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los mensajes activos
     */
    public function obtenerActivos() {
        $query = "SELECT m.*, s.status as nombre_status
                  FROM " . $this->table . " m
                  INNER JOIN status s ON m.IdStatus = s.IdStatus
                  WHERE m.activo = TRUE
                  ORDER BY m.IdStatus ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda un nuevo mensaje
     */
    public function guardar() {
        $query = "INSERT INTO " . $this->table . "
                  (IdStatus, titulo, contenido, incluir_requisitos, activo)
                  VALUES (:idStatus, :titulo, :contenido, :incluir_requisitos, :activo)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idStatus', $this->IdStatus, PDO::PARAM_INT);
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':contenido', $this->contenido);
        $stmt->bindParam(':incluir_requisitos', $this->incluir_requisitos, PDO::PARAM_BOOL);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            $this->IdMensajeWhatsapp = $this->conn->lastInsertId();
            return $this->IdMensajeWhatsapp;
        }
        return false;
    }

    /**
     * Actualiza un mensaje existente
     */
    public function actualizar() {
        $query = "UPDATE " . $this->table . " SET
                  IdStatus = :idStatus,
                  titulo = :titulo,
                  contenido = :contenido,
                  incluir_requisitos = :incluir_requisitos,
                  activo = :activo
                  WHERE IdMensajeWhatsapp = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idStatus', $this->IdStatus, PDO::PARAM_INT);
        $stmt->bindParam(':titulo', $this->titulo);
        $stmt->bindParam(':contenido', $this->contenido);
        $stmt->bindParam(':incluir_requisitos', $this->incluir_requisitos, PDO::PARAM_BOOL);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $this->IdMensajeWhatsapp, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina un mensaje
     */
    public function eliminar() {
        $query = "DELETE FROM " . $this->table . " WHERE IdMensajeWhatsapp = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdMensajeWhatsapp, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Activa/Desactiva un mensaje
     */
    public function cambiarEstado($activo) {
        $query = "UPDATE " . $this->table . " SET activo = :activo WHERE IdMensajeWhatsapp = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':activo', $activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $this->IdMensajeWhatsapp, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Desactiva otros mensajes del mismo status (para tener solo uno activo por status)
     */
    public function desactivarOtrosDelMismoStatus($idExcluir = null) {
        if ($idExcluir) {
            $query = "UPDATE " . $this->table . "
                      SET activo = FALSE
                      WHERE IdStatus = :idStatus AND IdMensajeWhatsapp != :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':idStatus', $this->IdStatus, PDO::PARAM_INT);
            $stmt->bindParam(':id', $idExcluir, PDO::PARAM_INT);
        } else {
            $query = "UPDATE " . $this->table . "
                      SET activo = FALSE
                      WHERE IdStatus = :idStatus";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':idStatus', $this->IdStatus, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    /**
     * Obtiene los status de inscripción disponibles
     */
    public function obtenerStatusInscripcion() {
        $query = "SELECT IdStatus, status
                  FROM status
                  WHERE IdTipo_Status = 2
                  ORDER BY IdStatus";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si existe un mensaje (activo o inactivo) para un status
     */
    public function existeMensajeParaStatus($idStatus) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . "
                  WHERE IdStatus = :idStatus";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idStatus', $idStatus, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }

    /**
     * Obtiene los status de inscripción que NO tienen mensaje asignado
     */
    public function obtenerStatusSinMensaje() {
        $query = "SELECT s.IdStatus, s.status
                  FROM status s
                  WHERE s.IdTipo_Status = 2
                  AND s.IdStatus NOT IN (
                      SELECT IdStatus FROM " . $this->table . "
                  )
                  ORDER BY s.IdStatus";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Procesa las variables del mensaje y las reemplaza con valores reales
     *
     * Variables disponibles:
     * - {nombre_representante}: Nombre del representante
     * - {nombre_estudiante}: Nombre completo del estudiante
     * - {codigo_inscripcion}: Código de inscripción
     * - {curso}: Nombre del curso
     * - {seccion}: Nombre de la sección
     * - {cedula_representante}: Cédula del representante
     * - {requisitos}: Lista de requisitos (si incluir_requisitos es true)
     * - {login_url}: URL de login (si está configurada)
     */
    public function procesarMensaje($datos, $requisitos = [], $loginUrl = null) {
        $mensaje = $this->contenido;

        // Reemplazar variables básicas
        $variables = [
            '{nombre_representante}' => $datos['nombre_representante'] ?? 'Representante',
            '{nombre_estudiante}' => $datos['nombre_estudiante'] ?? '',
            '{codigo_inscripcion}' => $datos['codigo_inscripcion'] ?? '',
            '{curso}' => $datos['curso'] ?? '',
            '{seccion}' => $datos['seccion'] ?? '',
            '{cedula_representante}' => $datos['cedula_representante'] ?? '',
            '{fecha_reunion}' => !empty($datos['fecha_reunion']) ? date('d/m/Y', strtotime($datos['fecha_reunion'])) : ''
        ];

        foreach ($variables as $variable => $valor) {
            $mensaje = str_replace($variable, $valor, $mensaje);
        }

        // Procesar requisitos si corresponde
        if ($this->incluir_requisitos && !empty($requisitos)) {
            $listaRequisitos = "";
            foreach ($requisitos as $req) {
                $listaRequisitos .= "\n- " . $req['requisito'];
            }
            $mensaje = str_replace('{requisitos}', $listaRequisitos, $mensaje);
        } else {
            $mensaje = str_replace('{requisitos}', '', $mensaje);
        }

        // Procesar URL de login
        if (!empty($loginUrl)) {
            $mensaje = str_replace('{login_url}', "Acceda aqui: $loginUrl", $mensaje);
        } else {
            $mensaje = str_replace('{login_url}', '', $mensaje);
        }

        return $mensaje;
    }

    /**
     * Verifica si existe un mensaje activo para un status
     */
    public function existeMensajeActivoParaStatus($idStatus) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . "
                  WHERE IdStatus = :idStatus AND activo = TRUE";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':idStatus', $idStatus, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }

    /**
     * Carga los datos en las propiedades del objeto
     */
    private function cargarDatos($row) {
        $this->IdMensajeWhatsapp = $row['IdMensajeWhatsapp'];
        $this->IdStatus = $row['IdStatus'];
        $this->titulo = $row['titulo'];
        $this->contenido = $row['contenido'];
        $this->incluir_requisitos = $row['incluir_requisitos'];
        $this->activo = $row['activo'];
    }
}
