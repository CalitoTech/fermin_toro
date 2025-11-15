<?php
require_once __DIR__ . '/../config/conexion.php';

class Telefono {
    private $conn;
    public $IdTelefono;
    public $numero_telefono;
    public $IdTipo_Telefono;
    public $IdPersona;
    public $IdPrefijo;

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
        try {
            // 1) Verificar si el número ya existe en la tabla
            $checkSql = "SELECT IdTelefono, IdPersona FROM telefono WHERE numero_telefono = :numero LIMIT 1";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->bindParam(':numero', $this->numero_telefono);
            $checkStmt->execute();
            $found = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($found) {
                // Si ya existe para la misma persona, no insertar (silencioso)
                if ((int)$found['IdPersona'] === (int)$this->IdPersona) {
                    return (int)$found['IdTelefono'];
                }

                // Si ya existe para otra persona: error (debe hacer rollback en el controlador)
                throw new Exception('El número de teléfono ya está registrado para otra persona');
            }

            // 2) Insertar nuevo teléfono
            $query = "INSERT INTO telefono (numero_telefono, IdTipo_Telefono, IdPersona, IdPrefijo)
                      VALUES (:numero_telefono, :IdTipo_Telefono, :IdPersona, :IdPrefijo)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":numero_telefono", $this->numero_telefono);
            $stmt->bindParam(":IdTipo_Telefono", $this->IdTipo_Telefono, PDO::PARAM_INT);
            $stmt->bindParam(":IdPersona", $this->IdPersona, PDO::PARAM_INT);

            // IdPrefijo puede ser NULL para teléfonos sin prefijo
            if (!empty($this->IdPrefijo) && $this->IdPrefijo !== 'null') {
                $stmt->bindParam(":IdPrefijo", $this->IdPrefijo, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":IdPrefijo", null, PDO::PARAM_NULL);
            }

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }

            return false;
        } catch (Exception $e) {
            error_log("Error al guardar teléfono: " . $e->getMessage());
            // Re-lanzamos la excepción para que el controlador pueda hacer rollback cuando corresponda
            throw $e;
        }
    }

    public static function guardarTelefonosPersona($db, $idPersona, $telefonos, $prefijos = []) {
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

                // Asignar prefijo si existe (principalmente para Celular)
                $prefijoKey = $tipo === 'Celular' ? 'Celular' : $tipo;
                if (isset($prefijos[$prefijoKey]) && !empty($prefijos[$prefijoKey])) {
                    $modeloTelefono->IdPrefijo = $prefijos[$prefijoKey];
                } else {
                    $modeloTelefono->IdPrefijo = null;
                }

                try {
                    $modeloTelefono->guardar();
                } catch (Exception $e) {
                    // Si hubo conflicto (teléfono en otra persona), propagar excepción para rollback
                    error_log("Error al guardar teléfono (persona $idPersona): " . $e->getMessage());
                    throw $e;
                }
            }
            // Si está vacío, simplemente lo omite (no es un error)
        }

        return true; // Todo bien
    }

    public function obtenerPorPersona($idPersona) {
        $query = "SELECT t.*, tt.tipo_telefono, p.codigo_prefijo, p.pais
                FROM telefono t
                JOIN tipo_telefono tt ON t.IdTipo_Telefono = tt.IdTipo_Telefono
                LEFT JOIN prefijo p ON t.IdPrefijo = p.IdPrefijo
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
            $stmt = $this->conn->prepare("INSERT INTO telefono (IdPersona, IdTipo_Telefono, numero_telefono, IdPrefijo) VALUES (?, ?, ?, ?)");
            foreach ($telefonos as $tel) {
                if (!empty(trim($tel['numero']))) {
                    $idPrefijo = isset($tel['prefijo']) && !empty($tel['prefijo']) ? (int)$tel['prefijo'] : null;
                    if (!$stmt->execute([
                        $idPersona,
                        (int)$tel['tipo'],
                        trim($tel['numero']),
                        $idPrefijo
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