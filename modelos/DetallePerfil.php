<?php
require_once __DIR__ . '/../config/conexion.php';
class DetallePerfil {
    private $conn;
    public $IdDetalle_Perfil;
    public $IdPerfil;
    public $IdPersona;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO detalle_perfil SET 
            IdPerfil = :IdPerfil,
            IdPersona = :IdPersona";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->IdPerfil = htmlspecialchars(strip_tags($this->IdPerfil));
        $this->IdPersona = htmlspecialchars(strip_tags($this->IdPersona));

        // Vincular valores
        $stmt->bindParam(":IdPerfil", $this->IdPerfil);
        $stmt->bindParam(":IdPersona", $this->IdPersona);

        return $stmt->execute();
    }

    public function obtenerPorPersona($idPersona) {
        $query = "SELECT dp.*, p.nombre_perfil 
                FROM detalle_perfil dp
                JOIN perfil p ON dp.IdPerfil = p.IdPerfil
                WHERE dp.IdPersona = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idPersona, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarRoles($idPersona, $nuevosRoles, $useTransaction = true) {
        try {
            if ($useTransaction) {
                $this->conn->beginTransaction();
            }
            
            // Eliminar roles actuales
            $stmt = $this->conn->prepare("DELETE FROM detalle_perfil WHERE IdPersona = ?");
            if (!$stmt->execute([$idPersona])) {
                throw new Exception("Error al eliminar roles anteriores");
            }
            
            // Insertar nuevos roles
            $stmt = $this->conn->prepare("INSERT INTO detalle_perfil (IdPersona, IdPerfil) VALUES (?, ?)");
            foreach ($nuevosRoles as $idPerfil) {
                if (!$stmt->execute([$idPersona, $idPerfil])) {
                    throw new Exception("Error al asignar el rol $idPerfil");
                }
            }
            
            if ($useTransaction) {
                $this->conn->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($useTransaction) {
                $this->conn->rollBack();
            }
            error_log("Error al actualizar roles: " . $e->getMessage());
            throw $e; // Relanzamos la excepción para manejarla en el controlador
        }
    }

    public function eliminarPorPersona($idPersona) {
        $query = "DELETE FROM detalle_perfil WHERE IdPersona = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $idPersona, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>