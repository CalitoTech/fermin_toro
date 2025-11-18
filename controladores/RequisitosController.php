<?php
require_once __DIR__ . '/../modelos/Requisito.php';
require_once __DIR__ . '/../config/conexion.php';

class RequisitosController {
    private $conexionPDO;
    private $requisitoModel;

    public function __construct($conexionPDO) {
        $this->conexionPDO = $conexionPDO;
        $this->requisitoModel = new Requisito($conexionPDO);
    }

    public function obtenerRequisitos() {
        header('Content-Type: application/json');

        if (!isset($_GET['idNivel']) || !is_numeric($_GET['idNivel'])) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de nivel no válido']);
            return;
        }

        $idNivel = (int)$_GET['idNivel'];

        // Obtener requisitos del nivel incluyendo generales (no filtrar por tipo de trabajador ni plantel privado aún)
        $requisitos = $this->requisitoModel->obtenerPorNivel($idNivel);

        if ($requisitos === false) {
            http_response_code(404);
            echo json_encode(['error' => 'Error al obtener requisitos']);
            return;
        }

        // Agrupar requisitos por tipo
        $requisitosPorTipo = [];
        foreach ($requisitos as $req) {
            $tipo = $req['tipo_requisito'];
            if (!isset($requisitosPorTipo[$tipo])) {
                $requisitosPorTipo[$tipo] = [];
            }

            // Agregar información adicional al requisito
            $reqFormateado = [
                'IdRequisito' => $req['IdRequisito'],
                'requisito' => $req['requisito'],
                'obligatorio' => $req['obligatorio'] ? 'Sí' : 'No',
                'tipo_requisito' => $req['tipo_requisito'],
                'tipo_trabajador' => $req['tipo_trabajador'] ?? null,
                'descripcion_adicional' => $req['descripcion_adicional'] ?? null,
                'solo_plantel_privado' => $req['solo_plantel_privado'] ? true : false
            ];

            $requisitosPorTipo[$tipo][] = $reqFormateado;
        }

        echo json_encode([
            'por_tipo' => $requisitosPorTipo,
            'lista_completa' => array_map(function($req) {
                return [
                    'IdRequisito' => $req['IdRequisito'],
                    'requisito' => $req['requisito'],
                    'obligatorio' => $req['obligatorio'] ? 'Sí' : 'No',
                    'tipo_requisito' => $req['tipo_requisito'],
                    'tipo_trabajador' => $req['tipo_trabajador'] ?? null,
                    'descripcion_adicional' => $req['descripcion_adicional'] ?? null,
                    'solo_plantel_privado' => $req['solo_plantel_privado'] ? true : false
                ];
            }, $requisitos)
        ]);
    }
}

// Uso del controlador
$database = new Database();
$conexionPDO = $database->getConnection();
$controlador = new RequisitosController($conexionPDO);
$controlador->obtenerRequisitos();
?>