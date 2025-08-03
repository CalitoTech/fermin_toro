<?php
require_once __DIR__ . '/../config/conexion.php';

class Telefono {
    private $conn;
    public $IdTelefono;
    public $numero_telefono;
    public $IdTipo_Telefono;
    public $IdPersona;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function guardar() {
        // === Validación: número no vacío y sin solo espacios ===
        $this->numero_telefono = trim($this->numero_telefono);
        if (empty($this->numero_telefono)) {
            return false; // No guardar números vacíos
        }

        // Sanitizar
        $this->numero_telefono = htmlspecialchars(strip_tags($this->numero_telefono));
        $this->IdTipo_Telefono = (int)$this->IdTipo_Telefono;
        $this->IdPersona = (int)$this->IdPersona;

        $query = "INSERT INTO telefono (numero_telefono, IdTipo_Telefono, IdPersona) 
                  VALUES (:numero_telefono, :IdTipo_Telefono, :IdPersona)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":numero_telefono", $this->numero_telefono);
        $stmt->bindParam(":IdTipo_Telefono", $this->IdTipo_Telefono, PDO::PARAM_INT);
        $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);

        try {
            return $stmt->execute() ? $this->conn->lastInsertId() : false;
        } catch (Exception $e) {
            error_log("Error al guardar teléfono: " . $e->getMessage());
            return false;
        }
    }

    public static function guardarTelefonosPersona($db, $idPersona, $telefonos) {
        $tipos = [
            'TelefonoHabitacion' => 1,
            'Celular' => 2,
            'TelefonoTrabajo' => 3
        ];

        foreach ($tipos as $tipo => $idTipo) {
            // Solo procesar si el número existe y no está vacío
            if (isset($telefonos[$tipo]) && !empty(trim($telefonos[$tipo]))) {
                $modeloTelefono = new self($db);
                $modeloTelefono->numero_telefono = $telefonos[$tipo];
                $modeloTelefono->IdTipo_Telefono = $idTipo;
                $modeloTelefono->IdPersona = $idPersona;

                if (!$modeloTelefono->guardar()) {
                    error_log("Error al guardar teléfono: Tipo=$tipo, Número=" . $telefonos[$tipo] . ", IdPersona=$idPersona");
                    return false; // ← Retorna false si uno falla
                }
            }
            // Si está vacío, simplemente lo omite (no es un error)
        }

        return true; // Todo bien
    }

    public function obtenerPorPersona($idPersona) {
        $query = "SELECT t.*, tt.tipo_telefono 
                FROM telefono t
                JOIN tipo_telefono tt ON t.IdTipo_Telefono = tt.IdTipo_Telefono
                WHERE t.IdPersona = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $idPersona, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarTelefonos($idPersona, $telefonos, $useTransaction = true) {
        try {
            if ($useTransaction) {
                $this->conn->beginTransaction();
            }
            
            // Eliminar teléfonos existentes
            $stmt = $this->conn->prepare("DELETE FROM telefono WHERE IdPersona = ?");
            if (!$stmt->execute([$idPersona])) {
                throw new Exception("Error al eliminar teléfonos anteriores");
            }
            
            // Insertar nuevos teléfonos
            $stmt = $this->conn->prepare("INSERT INTO telefono (IdPersona, IdTipo_Telefono, numero_telefono) VALUES (?, ?, ?)");
            foreach ($telefonos as $tel) {
                if (!empty(trim($tel['numero']))) {
                    if (!$stmt->execute([
                        $idPersona,
                        (int)$tel['tipo'],
                        trim($tel['numero'])
                    ])) {
                        throw new Exception("Error al guardar teléfono: " . $tel['numero']);
                    }
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
            error_log("Error al actualizar teléfonos: " . $e->getMessage());
            throw $e; // Relanzamos la excepción para manejarla en el controlador
        }
    }

    public function eliminarPorPersona($idPersona) {
        $query = "DELETE FROM telefono WHERE IdPersona = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $idPersona, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>