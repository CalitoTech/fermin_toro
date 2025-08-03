<?php
require_once __DIR__ . '/../config/conexion.php';
class Permiso {
    private $conn;
    public $IdPermiso;
    public $permiso_leer;
    public $permiso_guardar;
    public $permiso_modificar;
    public $permiso_eliminar;
    public $IdTabla;
    public $IdPerfil;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        $query = "INSERT INTO permiso SET 
            permiso_leer = :permiso_leer,
            permiso_guardar = :permiso_guardar,
            permiso_modificar = :permiso_modificar,
            permiso_eliminar = :permiso_eliminar,
            IdTabla = :IdTabla,
            IdPerfil = :IdPerfil";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->permiso_leer = htmlspecialchars(strip_tags($this->permiso_leer));
        $this->permiso_guardar = htmlspecialchars(strip_tags($this->permiso_guardar));
        $this->permiso_modificar = htmlspecialchars(strip_tags($this->permiso_modificar));
        $this->permiso_eliminar = htmlspecialchars(strip_tags($this->permiso_eliminar));
        $this->IdTabla = htmlspecialchars(strip_tags($this->IdTabla));
        $this->IdPerfil = htmlspecialchars(strip_tags($this->IdPerfil));

        // Vincular valores
        $stmt->bindParam(":permiso_leer", $this->permiso_leer);
        $stmt->bindParam(":permiso_guardar", $this->permiso_guardar);
        $stmt->bindParam(":permiso_modificar", $this->permiso_modificar);
        $stmt->bindParam(":permiso_eliminar", $this->permiso_eliminar);
        $stmt->bindParam(":IdTabla", $this->IdTabla);
        $stmt->bindParam(":IdPerfil", $this->IdPerfil);

        return $stmt->execute();
    }

    public function obtenerPorPerfil($idPerfil) {
        $query = "SELECT p.*, t.nombre_tabla 
                 FROM permiso p
                 JOIN tabla t ON p.IdTabla = t.IdTabla
                 WHERE p.IdPerfil = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idPerfil);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>