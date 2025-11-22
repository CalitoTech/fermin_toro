<?php
require_once __DIR__ . '/../config/conexion.php';
class Nacionalidad {
    private $conn;
    public $IdNacionalidad;
    public $nacionalidad;
    public $nombre_largo;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM nacionalidad";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener nacionalidades con nombres largos para mostrar en select
     * Retorna array con IdNacionalidad, nacionalidad (V/E) y nombre_largo (Venezolano/Extranjero)
     */
    public function obtenerConNombresLargos() {
        $query = "SELECT IdNacionalidad, nacionalidad,
                         COALESCE(nombre_largo, nacionalidad) as nombre_largo
                  FROM nacionalidad";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>