<?php
require_once __DIR__ . '/../config/conexion.php';

class Notificacion {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear($titulo, $mensaje, $tipo, $enlace, $destinatarios = 'admin') {
        $query = "INSERT INTO notificaciones (titulo, mensaje, tipo, enlace, destinatarios, fecha_creacion) 
                  VALUES (:titulo, :mensaje, :tipo, :enlace, :destinatarios, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $titulo = htmlspecialchars(strip_tags($titulo));
        $mensaje = htmlspecialchars(strip_tags($mensaje));
        $tipo = htmlspecialchars(strip_tags($tipo));
        // Enlace can be null
        $destinatarios = htmlspecialchars(strip_tags($destinatarios));

        $stmt->bindParam(":titulo", $titulo);
        $stmt->bindParam(":mensaje", $mensaje);
        $stmt->bindParam(":tipo", $tipo);
        $stmt->bindParam(":enlace", $enlace);
        $stmt->bindParam(":destinatarios", $destinatarios);

        return $stmt->execute();
    }

    public function obtenerNoLeidas($idPersona, $limit = 10) {
        // Obtener la fecha de asignación del rol interno más antiguo del usuario
        // Roles internos: 1, 6, 7, 8, 9, 10 (Administrativos/Directivos)
        $sqlFecha = "SELECT MIN(fecha_asignacion) as fecha_inicio 
                     FROM detalle_perfil 
                     WHERE IdPersona = :idPersona 
                     AND IdPerfil IN (1, 6, 7, 8, 9, 10, 11, 12, 13)";
        $stmtFecha = $this->conn->prepare($sqlFecha);
        $stmtFecha->bindParam(":idPersona", $idPersona, PDO::PARAM_INT);
        $stmtFecha->execute();
        $rowFecha = $stmtFecha->fetch(PDO::FETCH_ASSOC);
        
        // Si no tiene fecha de asignación, no mostrar notificaciones
        if (!$rowFecha || !$rowFecha['fecha_inicio']) {
            return [];
        }
        
        $fechaInicio = $rowFecha['fecha_inicio'];

        $query = "SELECT n.* 
                  FROM notificaciones n
                  WHERE n.IdNotificacion NOT IN (
                      SELECT nl.IdNotificacion 
                      FROM notificaciones_leidas nl 
                      WHERE nl.IdPersona = :idPersona
                  )
                  AND n.fecha_creacion >= :fechaInicio
                  ORDER BY n.fecha_creacion DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":idPersona", $idPersona, PDO::PARAM_INT);
        $stmt->bindParam(":fechaInicio", $fechaInicio);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function marcarComoLeida($idNotificacion, $idPersona) {
        $query = "INSERT INTO notificaciones_leidas (IdNotificacion, IdPersona, fecha_lectura) 
                  VALUES (:idNotificacion, :idPersona, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":idNotificacion", $idNotificacion, PDO::PARAM_INT);
        $stmt->bindParam(":idPersona", $idPersona, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    public function marcarTodasComoLeidas($idPersona) {
        // Insertar en leidas todas las que no estén leídas
        // Esto es un poco pesado si hay muchas, pero válido para este alcance.
        $query = "INSERT INTO notificaciones_leidas (IdNotificacion, IdPersona, fecha_lectura)
                  SELECT IdNotificacion, :idPersona, NOW()
                  FROM notificaciones n
                  WHERE NOT EXISTS (
                      SELECT 1 FROM notificaciones_leidas nl 
                      WHERE nl.IdNotificacion = n.IdNotificacion 
                      AND nl.IdPersona = :idPersona2
                  )";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":idPersona", $idPersona, PDO::PARAM_INT);
        $stmt->bindParam(":idPersona2", $idPersona, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    public function contarNoLeidas($idPersona) {
        // Obtener la fecha de asignación del rol interno más antiguo del usuario
        $sqlFecha = "SELECT MIN(fecha_asignacion) as fecha_inicio 
                     FROM detalle_perfil 
                     WHERE IdPersona = :idPersona 
                     AND IdPerfil IN (1, 6, 7, 8, 9, 10, 11, 12, 13)";
        $stmtFecha = $this->conn->prepare($sqlFecha);
        $stmtFecha->bindParam(":idPersona", $idPersona, PDO::PARAM_INT);
        $stmtFecha->execute();
        $rowFecha = $stmtFecha->fetch(PDO::FETCH_ASSOC);
        
        // Si no tiene fecha de asignación, retornar 0
        if (!$rowFecha || !$rowFecha['fecha_inicio']) {
            return 0;
        }
        
        $fechaInicio = $rowFecha['fecha_inicio'];
        
        $query = "SELECT COUNT(*) as total
                  FROM notificaciones n
                  WHERE n.IdNotificacion NOT IN (
                      SELECT nl.IdNotificacion 
                      FROM notificaciones_leidas nl 
                      WHERE nl.IdPersona = :idPersona
                  )
                  AND n.fecha_creacion >= :fechaInicio";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":idPersona", $idPersona, PDO::PARAM_INT);
        $stmt->bindParam(":fechaInicio", $fechaInicio);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }
}
?>
