<?php
require_once __DIR__ . '/../config/conexion.php';

class ConfigWhatsapp {
    private $conn;
    private $table = "config_whatsapp";

    // Clave para encriptación (en producción debería estar en un archivo .env)
    private $encryptionKey = 'FERMIN_TORO_WHATSAPP_SECRET_KEY_2024';
    private $encryptionMethod = 'AES-256-CBC';

    public $IdConfigWhatsapp;
    public $api_url;
    public $api_key; // Almacenada encriptada
    public $nombre_instancia;
    public $login_url;
    public $activo;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Encripta un valor (reversible)
     */
    private function encriptar($valor) {
        if (empty($valor)) return '';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->encryptionMethod));
        $encrypted = openssl_encrypt($valor, $this->encryptionMethod, $this->encryptionKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Desencripta un valor
     */
    private function desencriptar($valorEncriptado) {
        if (empty($valorEncriptado)) return '';
        $data = base64_decode($valorEncriptado);
        $ivLength = openssl_cipher_iv_length($this->encryptionMethod);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        return openssl_decrypt($encrypted, $this->encryptionMethod, $this->encryptionKey, 0, $iv);
    }

    /**
     * Obtiene la configuración activa
     */
    public function obtenerConfiguracionActiva() {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = TRUE LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->IdConfigWhatsapp = $row['IdConfigWhatsapp'];
            $this->api_url = $row['api_url'];
            $this->api_key = $row['api_key'];
            $this->nombre_instancia = $row['nombre_instancia'];
            $this->login_url = $row['login_url'];
            $this->activo = $row['activo'];
            return $row;
        }

        return false;
    }

    /**
     * Obtiene configuración por ID
     */
    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE IdConfigWhatsapp = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->IdConfigWhatsapp = $row['IdConfigWhatsapp'];
            $this->api_url = $row['api_url'];
            $this->api_key = $row['api_key'];
            $this->nombre_instancia = $row['nombre_instancia'];
            $this->login_url = $row['login_url'];
            $this->activo = $row['activo'];
            return $row;
        }

        return false;
    }

    /**
     * Obtiene todas las configuraciones
     */
    public function obtenerTodos() {
        $query = "SELECT IdConfigWhatsapp, api_url, nombre_instancia, login_url, activo,
                         fecha_creacion, fecha_actualizacion
                  FROM " . $this->table . "
                  ORDER BY activo DESC, IdConfigWhatsapp DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda una nueva configuración
     * @param string $apiKeyPlano La API key en texto plano (será encriptada)
     */
    public function guardar($apiKeyPlano = null) {
        // Si se proporciona una API key, encriptarla
        $apiKeyEncriptada = '';
        if (!empty($apiKeyPlano)) {
            $apiKeyEncriptada = $this->encriptar($apiKeyPlano);
        }

        $query = "INSERT INTO " . $this->table . "
                  (api_url, api_key, nombre_instancia, login_url, activo)
                  VALUES (:api_url, :api_key, :nombre_instancia, :login_url, :activo)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':api_url', $this->api_url);
        $stmt->bindParam(':api_key', $apiKeyEncriptada);
        $stmt->bindParam(':nombre_instancia', $this->nombre_instancia);
        $stmt->bindParam(':login_url', $this->login_url);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            $this->IdConfigWhatsapp = $this->conn->lastInsertId();
            return $this->IdConfigWhatsapp;
        }
        return false;
    }

    /**
     * Actualiza la configuración
     * @param string|null $apiKeyPlano Nueva API key en texto plano (null = no cambiar)
     */
    public function actualizar($apiKeyPlano = null) {
        // Si se proporciona nueva API key, encriptarla y actualizar
        if (!empty($apiKeyPlano)) {
            $apiKeyEncriptada = $this->encriptar($apiKeyPlano);
            $query = "UPDATE " . $this->table . " SET
                      api_url = :api_url,
                      api_key = :api_key,
                      nombre_instancia = :nombre_instancia,
                      login_url = :login_url,
                      activo = :activo
                      WHERE IdConfigWhatsapp = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':api_key', $apiKeyEncriptada);
        } else {
            // No actualizar la API key
            $query = "UPDATE " . $this->table . " SET
                      api_url = :api_url,
                      nombre_instancia = :nombre_instancia,
                      login_url = :login_url,
                      activo = :activo
                      WHERE IdConfigWhatsapp = :id";

            $stmt = $this->conn->prepare($query);
        }

        $stmt->bindParam(':api_url', $this->api_url);
        $stmt->bindParam(':nombre_instancia', $this->nombre_instancia);
        $stmt->bindParam(':login_url', $this->login_url);
        $stmt->bindParam(':activo', $this->activo, PDO::PARAM_BOOL);
        $stmt->bindParam(':id', $this->IdConfigWhatsapp, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Actualiza solo la API key
     * @param string $apiKeyPlano Nueva API key en texto plano
     */
    public function actualizarApiKey($apiKeyPlano) {
        $apiKeyEncriptada = $this->encriptar($apiKeyPlano);

        $query = "UPDATE " . $this->table . " SET api_key = :api_key WHERE IdConfigWhatsapp = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':api_key', $apiKeyEncriptada);
        $stmt->bindParam(':id', $this->IdConfigWhatsapp, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Obtiene la API key desencriptada para uso con Evolution API
     * @return string API key en texto plano
     */
    public function obtenerApiKeyParaUso() {
        return $this->desencriptar($this->api_key);
    }

    /**
     * Verifica si hay una API key configurada
     * @return bool
     */
    public function tieneApiKey() {
        return !empty($this->api_key);
    }

    /**
     * Desactiva todas las configuraciones excepto la especificada
     */
    public function desactivarOtras($idExcluir = null) {
        if ($idExcluir) {
            $query = "UPDATE " . $this->table . " SET activo = FALSE WHERE IdConfigWhatsapp != :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $idExcluir, PDO::PARAM_INT);
        } else {
            $query = "UPDATE " . $this->table . " SET activo = FALSE";
            $stmt = $this->conn->prepare($query);
        }

        return $stmt->execute();
    }

    /**
     * Activa una configuración específica (y desactiva las demás)
     */
    public function activar($id) {
        $this->desactivarOtras($id);

        $query = "UPDATE " . $this->table . " SET activo = TRUE WHERE IdConfigWhatsapp = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Elimina una configuración
     */
    public function eliminar() {
        $query = "DELETE FROM " . $this->table . " WHERE IdConfigWhatsapp = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->IdConfigWhatsapp, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Verifica si hay al menos una configuración activa
     */
    public function hayConfiguracionActiva() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE activo = TRUE";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }
}
