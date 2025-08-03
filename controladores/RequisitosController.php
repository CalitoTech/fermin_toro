<?php
require_once __DIR__ . '/../modelos/Nivel.php';
require_once __DIR__ . '/../config/conexion.php';

class RequisitosController {
    private $conexionPDO;
    private $modeloNivel;

    public function __construct($conexionPDO) {
        $this->conexionPDO = $conexionPDO;
        $this->modeloNivel = new Nivel($conexionPDO);
    }

    public function obtenerRequisitos() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['idNivel']) || !is_numeric($_GET['idNivel'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de nivel no válido']);
            return;
        }

        $idNivel = (int)$_GET['idNivel'];
        $requisitos = $this->modeloNivel->obtenerRequisitos($idNivel);

        if ($requisitos === false) {
            http_response_code(404);
            echo json_encode(['error' => 'Error al obtener requisitos']);
            return;
        }

        // Convertir boolean a texto para mejor visualización
        $requisitos = array_map(function($req) {
            $req['obligatorio'] = $req['obligatorio'] ? 'Sí' : 'No';
            return $req;
        }, $requisitos);

        echo json_encode($requisitos);
    }
}

// Uso del controlador
$database = new Database();
$conexionPDO = $database->getConnection();
$controlador = new RequisitosController($conexionPDO);
$controlador->obtenerRequisitos();
?>