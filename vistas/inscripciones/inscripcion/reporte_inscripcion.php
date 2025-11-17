<?php
// reporte_inscripcion.php
// Genera la planilla de inscripción con TCPDF siguiendo la estructura del PDF escaneado.
// Coloca este archivo en la misma carpeta donde lo llamas (recomendado).
// Asegúrate de ajustar las rutas a conexion.php y tcpdf.php si es necesario.

require_once __DIR__ . '/../../../config/conexion.php';
require_once __DIR__ . '/../../../modelos/Inscripcion.php';
require_once __DIR__ . '/../../../libs/tcpdf/tcpdf.php';

$database = new Database();
$conexion = $database->getConnection();

// helper para evitar notices y sanitizar
function v($arr, $k) {
    return htmlspecialchars($arr[$k] ?? '');
}
function vdate($arr, $k) {
    if (empty($arr[$k])) return '';
    $t = strtotime($arr[$k]);
    return $t ? date('d/m/Y', $t) : htmlspecialchars($arr[$k]);
}

date_default_timezone_set('America/Caracas');

// obtener parámetros
$idInscripcion = intval($_GET['id'] ?? 0);
$cedula = trim($_GET['cedula'] ?? '');
$anioEscolar = intval($_GET['anio_escolar'] ?? 0);

// decidir cómo buscar
if ($idInscripcion > 0) {
    // búsqueda por ID
    $where = "i.IdInscripcion = :id";
    $params = [':id' => $idInscripcion];
} elseif ($cedula !== '' && $anioEscolar > 0) {
    // búsqueda por cédula + año escolar
    $where = "e.cedula = :cedula AND fe.IdFecha_Escolar = :anio";
    $params = [':cedula' => $cedula, ':anio' => $anioEscolar];
} else {
    die('Parámetros inválidos para generar el reporte.');
}

// ---------------------------------------
// Consulta principal (adaptada desde tu ver_inscripcion.php)
// ---------------------------------------
$database = new Database();
$conexion = $database->getConnection();

$inscripcionModel = new Inscripcion($conexion);
$inscripcion = $inscripcionModel->obtenerDetallePorId($idInscripcion);

if (!$inscripcion) {
    die('Inscripción no encontrada con los parámetros proporcionados.');
}

// Requisitos relacionados
$qReq = "SELECT r.IdRequisito, r.requisito, r.obligatorio, IFNULL(ir.cumplido,0) as cumplido
         FROM requisito r
         LEFT JOIN inscripcion_requisito ir ON r.IdRequisito = ir.IdRequisito AND ir.IdInscripcion = :id
         WHERE r.IdNivel = (
            SELECT c.IdNivel FROM curso_seccion cs INNER JOIN curso c ON cs.IdCurso = c.IdCurso INNER JOIN inscripcion i ON cs.IdCurso_Seccion = i.IdCurso_Seccion WHERE i.IdInscripcion = :id
         )
         ORDER BY r.obligatorio DESC, r.requisito";
$st = $conexion->prepare($qReq);
$st->execute([':id' => $idInscripcion]);
$requisitos = $st->fetchAll(PDO::FETCH_ASSOC);

// Discapacidades
$qD = "SELECT td.tipo_discapacidad, d.discapacidad
      FROM discapacidad d
      INNER JOIN tipo_discapacidad td ON d.IdTipo_Discapacidad = td.IdTipo_Discapacidad
      WHERE d.IdPersona = :id_estudiante";
$stD = $conexion->prepare($qD);
$stD->execute([':id_estudiante' => $inscripcion['id_estudiante'] ?? 0]);
$discapacidades = $stD->fetchAll(PDO::FETCH_ASSOC);

// helper convertir telefonos concatenados
function telefonosToText($numerosStr, $tiposStr) {
    if (empty($numerosStr)) return '';
    $numeros = explode('||', $numerosStr);
    $tipos = explode('||', $tiposStr);
    $parts = [];
    foreach ($numeros as $i => $n) {
        $t = $tipos[$i] ?? '';
        $n = trim($n);
        if ($n === '') continue;
        $parts[] = $n . ($t ? " ({$t})" : '');
    }
    return implode(', ', $parts);
}

function telefonoPorTipo($numerosStr, $tiposStr, $tipoBuscado) {
    if (empty($numerosStr)) return '';
    $nums = explode('||',$numerosStr);
    $tips = explode('||',$tiposStr);
    foreach ($nums as $i=>$n) {
        if (($tips[$i] ?? '') == $tipoBuscado) {
            return trim($n);
        }
    }
    return '';
}

// normalizar array (evitar nulls)
foreach ($inscripcion as $k => $v) {
    $inscripcion[$k] = $v === null ? '' : $v;
}

// -------------------- CLASE TCPDF PERSONALIZADA --------------------
class PDFInscripcion extends TCPDF {
    // Puedes sobreescribir Header() si quieres agregar logo o título en cada página
    public function Header() {
        // No ponemos nada, ya que tu header es personalizado por página
    }

    // Footer personalizado: fecha y número de página
    public function Footer() {
        $this->SetY(-15); // 15 mm del final
        $this->SetFont('helvetica','I',7);

        $fecha = date('d/m/Y H:i');
        $pagina = 'Página '.$this->getAliasNumPage().' / '.$this->getAliasNbPages();

        // Escribir alineado a la derecha
        $this->Cell(0, 5, 'Fecha de impresión: '.$fecha.' | '.$pagina, 0, 0, 'R');
    }
}

// -------------------- CREACIÓN DEL PDF --------------------
$pdf = new PDFInscripcion('P','mm','A4',true,'UTF-8',false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('UECFT Araure');
$pdf->SetTitle('Planilla Inscripcion - '.($inscripcion['codigo_inscripcion'] ?? $idInscripcion));
$pdf->setPrintHeader(false); // header personalizado
$pdf->setPrintFooter(true);  // footer personalizado
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 20); // margen inferior 20mm para footer
$pdf->AddPage();

// Cintillo superior: logo a la izquierda, recuadros FOTO a la derecha, texto institucional centrado
$logo = __DIR__ . '/../../../assets/images/fermin.png';
if (file_exists($logo)) {
    $pdf->Image($logo, 12, 5, 35, '', 'PNG');
}

// Texto institucional (más a la izquierda)
$pdf->SetFont('helvetica','B',8);

$texto = "REPÚBLICA BOLIVARIANA DE VENEZUELA\nMINISTERIO DEL PODER POPULAR PARA LA EDUCACIÓN\nUNIDAD EDUCATIVA COLEGIO FERMÍN TORO\nINSCRITO EN EL MPPE BAJO EL NO. PD04281802";

// Definir ancho del bloque igual al ancho deseado, por ejemplo 90 mm
$ancho = 90;

// Mantener la posición actual
$x = $pdf->GetX() + 30;
$y = $pdf->GetY();

$pdf->MultiCell($ancho, 4, $texto, 0, 'C', false, 1, $x, $y);

// Recuadros FOTO (ahora lado a lado)
$pdf->Rect(130, 10, 30, 30); // foto estudiante
$pdf->Rect(165, 10, 30, 30); // foto representante
$pdf->SetFont('helvetica','',8);
$pdf->Text(140, 20, "FOTO");
$pdf->Text(135, 25, "ESTUDIANTE");
$pdf->Text(175, 20, "FOTO");
$pdf->Text(167, 25, "REPRESENTANTE");

// Título principal
$pdf->SetFont('helvetica','B',12);
$pdf->Ln(5);

// Definir ancho menor que 0 (ancho total) para que SetX funcione
$anchoTitulo = 120; // ajusta según necesidad
$pdf->SetX(($pdf->GetPageWidth() - $anchoTitulo) / 2 - 30); // 30 mm más a la izquierda
$pdf->Cell($anchoTitulo, 8, 'PLANILLA DE INSCRIPCIÓN', 0, 1, 'C');
$pdf->Ln(2);

// -------------------- DATOS DEL ESTUDIANTE --------------------
$pdf->SetFont('helvetica','B',10);
$anchoSubtitulo = 120;
$pdf->SetX(($pdf->GetPageWidth() - $anchoSubtitulo) / 2 - 30);
$pdf->Cell($anchoSubtitulo, 8, 'DATOS DEL ESTUDIANTE', 0, 1, 'C');
$pdf->SetFont('helvetica','',9);

// -------------------- CONDICIONES DE SALUD --------------------

// Obtener dinámicamente los tipos de discapacidad desde el modelo
require_once __DIR__ . '/../../../modelos/TipoDiscapacidad.php';
$tipoDiscapModel = new TipoDiscapacidad($conexion);
$tiposDiscapacidad = $tipoDiscapModel->obtenerTodos(); // Devuelve array con ['IdTipo_Discapacidad', 'tipo_discapacidad']

// Inicializar array para cada categoría
$discapArr = [];
foreach ($tiposDiscapacidad as $tipo) {
    $discapArr[$tipo['tipo_discapacidad']] = [];
}

// Llenar con registros de la BD
if ($discapacidades && count($discapacidades) > 0) {
    foreach ($discapacidades as $d) {
        $cat = $d['tipo_discapacidad'];
        $val = $d['discapacidad'];
        if (isset($discapArr[$cat])) {
            $discapArr[$cat][] = $val;
        }
    }
}

// Agregar alergias y enfermedades específicas
$alergias = v($inscripcion,'alergia_que') ?: '';
$enfermedades = v($inscripcion,'enfermedad_que') ?: '';

if (!empty($alergias)) {
    if(!isset($discapArr['Alergia'])) $discapArr['Alergia'] = [];
    $discapArr['Alergia'][] = $alergias;
}

if (!empty($enfermedades)) {
    if(!isset($discapArr['Enfermedad'])) $discapArr['Enfermedad'] = [];
    $discapArr['Enfermedad'][] = $enfermedades;
}

// -------------------- HTML DEL PDF --------------------
$html = '<table cellpadding="3" cellspacing="0" border="1" style="width:100%; font-size:9px;">
<tr>
  <td style="width:50%;"><b>Apellidos:</b> '.v($inscripcion,'estudiante_apellido').'</td>
  <td style="width:50%;"><b>Nombres:</b> '.v($inscripcion,'estudiante_nombre').'</td>
</tr>
<tr>
  <td style="width:25%;"><b>Cédula:</b> '.(v($inscripcion,'estudiante_nacionalidad')? v($inscripcion,'estudiante_nacionalidad').' - ' : '') . v($inscripcion,'estudiante_cedula').'</td>
  <td style="width:25%;"><b>Sexo:</b> '.v($inscripcion,'estudiante_sexo').'</td>
  <td style="width:50%;"><b>Fecha de Nacimiento:</b> '.vdate($inscripcion,'estudiante_fecha_nacimiento').'</td>
</tr>
<tr>
  <td colspan="2"><b>Lugar de Nacimiento:</b> '.v($inscripcion,'estudiante_lugar_nacimiento').'</td>
  <td><b>Teléfono:</b> '. (v($inscripcion,'estudiante_telefono') ? v($inscripcion,'estudiante_telefono').' '.v($inscripcion,'estudiante_tipo_telefono') : '') .'</td>
</tr>
<tr>
  <td colspan="3"><b>Correo:</b> '.v($inscripcion,'estudiante_correo').'</td>
</tr>
<tr>
  <td colspan="3" align="center"><b>CONDICIONES DE SALUD</b></td>
</tr>';

// Una fila por cada categoría (tipo) de discapacidad
foreach ($discapArr as $categoria => $valoresArr) {
    $valores = !empty($valoresArr) ? implode(', ', $valoresArr) : 'Ninguna';
    $html .= '<tr>
        <td colspan="3"><b>'.$categoria.':</b> '.$valores.'</td>
    </tr>';
}

$html .= '</table>';

// Escribir en el PDF
$pdf->writeHTML($html, true, false, false, false, '');

// -------------------- DATOS DE LA MADRE --------------------
$pdf->SetFont('helvetica','B',10);
$pdf->Cell(0, 8, 'DATOS DE LA MADRE', 0, 1, 'C');
$pdf->SetFont('helvetica','',9);

$htmlM = '<table cellpadding="3" cellspacing="0" border="1" style="width:100%; font-size:9px;">
<tr>
  <td style="width:50%;"><b>Apellidos:</b> '.v($inscripcion,'madre_apellido').'</td>
  <td style="width:50%;"><b>Nombres:</b> '.v($inscripcion,'madre_nombre').'</td>
</tr>
<tr>
  <td style="width:25%;"><b>C.I:</b> '.(v($inscripcion,'madre_nacionalidad')? v($inscripcion,'madre_nacionalidad').' - ' : '') . v($inscripcion,'madre_cedula').'</td>
  <td style="width:25%;"><b>Parentesco:</b> MADRE</td>
  <td style="width:50%;"><b>Ocupación:</b> '.v($inscripcion,'madre_ocupacion').'</td>
</tr>
<tr>
  <td colspan="3"><b>Dirección de Habitación:</b> '.v($inscripcion,'madre_direccion').'</td>
</tr>
<tr>
  <td style="width:50%;"><b>Teléfono de Habitación:</b> '.telefonoPorTipo($inscripcion['madre_numeros'],$inscripcion['madre_tipos'],'Habitación').'</td>
  <td style="width:50%;"><b>Celular:</b> '.telefonoPorTipo($inscripcion['madre_numeros'],$inscripcion['madre_tipos'],'Celular').'</td>
</tr>
<tr>
  <td style="width:50%;"><b>Teléfono del Trabajo:</b> '.telefonoPorTipo($inscripcion['madre_numeros'],$inscripcion['madre_tipos'],'Trabajo').'</td>
  <td style="width:50%;"><b>Lugar de Trabajo:</b> '.v($inscripcion,'madre_lugar_trabajo').'</td>
</tr>
<tr>
  <td style="width:100%;"><b>Correo electrónico:</b> '.v($inscripcion,'madre_correo').'</td>
</tr>
<tr>
<td style="width:100%;"><b>En caso de no poder llamar a papá o mamá llamar a: </b>'.v($inscripcion,'contacto_nombre').' '.v($inscripcion,'contacto_apellido').'</td>
</tr>
<tr>
<td style="width:50%;"><b>Parentesco:</b>'.v($inscripcion,'contacto_parentesco').'</td>
<td style="width:50%;"><b>Teléfono:</b>'.v($inscripcion,'contacto_telefono').'</td>
</tr>
</table>';
$pdf->writeHTML($htmlM,true,false,false,false,'');
$pdf->Ln(2);

// -------------------- DATOS DEL PADRE --------------------
$pdf->SetFont('helvetica','B',10);
$pdf->Cell(0, 8, 'DATOS DEL PADRE', 0, 1, 'C');
$pdf->SetFont('helvetica','',9);

$htmlP = '<table cellpadding="3" cellspacing="0" border="1" style="width:100%; font-size:9px;">
<tr>
  <td style="width:50%;"><b>Apellidos:</b> '.v($inscripcion,'padre_apellido').'</td>
  <td style="width:50%;"><b>Nombres:</b> '.v($inscripcion,'padre_nombre').'</td>
</tr>
<tr>
  <td style="width:25%;"><b>C.I.:</b> '.(v($inscripcion,'padre_nacionalidad')? v($inscripcion,'padre_nacionalidad').' - ' : '') . v($inscripcion,'padre_cedula').'</td>
  <td style="width:25%;"><b>Parentesco:</b> PADRE</td>
  <td style="width:50%;"><b>Ocupación:</b> '.v($inscripcion,'padre_ocupacion').'</td>
</tr>
<tr>
  <td colspan="3"><b>Dirección de Habitación:</b> '.v($inscripcion,'padre_direccion').'</td>
</tr>
<tr>
  <td style="width:50%;"><b>Teléfono de Habitación:</b> '.telefonoPorTipo($inscripcion['padre_numeros'],$inscripcion['padre_tipos'],'Habitación').'</td>
  <td style="width:50%;"><b>Celular:</b> '.telefonoPorTipo($inscripcion['padre_numeros'],$inscripcion['padre_tipos'],'Celular').'</td>
</tr>
<tr>
  <td style="width:50%;"><b>Teléfono del Trabajo:</b> '.telefonoPorTipo($inscripcion['padre_numeros'],$inscripcion['padre_tipos'],'Trabajo').'</td>
  <td style="width:50%;"><b>Lugar de Trabajo:</b> '.v($inscripcion,'padre_lugar_trabajo').'</td>
</tr>
<tr>
  <td style="width:100%;"><b>Correo electrónico:</b> '.v($inscripcion,'padre_correo').'</td>
</tr>
</table>';
$pdf->writeHTML($htmlP, true, false, false, false, '');
$pdf->Ln(4);

// -------------------- DATOS DEL REPRESENTANTE LEGAL --------------------
$pdf->SetFont('helvetica','B',10);
$pdf->Cell(0, 8, 'DATOS DEL REPRESENTANTE LEGAL', 0, 1, 'C');
$pdf->SetFont('helvetica','',9);

$htmlR = '<table cellpadding="3" cellspacing="0" border="1" style="width:100%; font-size:9px;">
<tr>
  <td style="width:50%;"><b>Apellidos:</b> '.v($inscripcion,'responsable_apellido').'</td>
  <td style="width:50%;"><b>Nombres:</b> '.v($inscripcion,'responsable_nombre').'</td>
</tr>
<tr>
  <td style="width:25%;"><b>C.I.:</b> '.(v($inscripcion,'responsable_nacionalidad')? v($inscripcion,'responsable_nacionalidad').' - ' : '') . v($inscripcion,'responsable_cedula').'</td>
  <td style="width:25%;"><b>Parentesco:</b> '.v($inscripcion,'responsable_parentesco').'</td>
  <td style="width:50%;"><b>Ocupación:</b> '.v($inscripcion,'responsable_ocupacion').'</td>
</tr>
<tr>
  <td colspan="3"><b>Dirección de Habitación:</b> '.v($inscripcion,'responsable_direccion').'</td>
</tr>
<tr>
  <td style="width:50%;"><b>Teléfono de Habitación:</b> '.telefonoPorTipo($inscripcion['responsable_numeros'],$inscripcion['responsable_tipos'],'Habitación').'</td>
  <td style="width:50%;"><b>Celular:</b> '.telefonoPorTipo($inscripcion['responsable_numeros'],$inscripcion['responsable_tipos'],'Celular').'</td>
</tr>
<tr>
  <td style="width:50%;"><b>Teléfono del Trabajo:</b> '.telefonoPorTipo($inscripcion['responsable_numeros'],$inscripcion['responsable_tipos'],'Trabajo').'</td>
  <td style="width:50%;"><b>Lugar de Trabajo:</b> '.v($inscripcion,'responsable_lugar_trabajo').'</td>
</tr>
<tr>
  <td style="width:100%;"><b>Correo electrónico:</b> '.v($inscripcion,'responsable_correo').'</td>
</tr>
</table>';
$pdf->writeHTML($htmlR, true, false, false, false, '');
$pdf->Ln(4);

// -------------------- DATOS DE ESTUDIO --------------------
$pdf->SetFont('helvetica','B',10);
$pdf->Cell(0, 8, 'DATOS DE ESTUDIO', 0, 1, 'C');
$pdf->SetFont('helvetica','',9);

$htmlEst = '<table border="1" cellpadding="3" cellspacing="0" width="100%" style="font-size:9px;">
<tr>
  <td width="100%"><b>Plantel donde cursó el último año:</b> '.v($inscripcion,'plantel_nombre').'</td>
</tr>
<tr>
  <td width="40%"><b>No. de Hermanos:</b> '.v($inscripcion,'nro_hermanos').'</td>
  <td width="60%"><b>Grados que Cursan:</b> '.(v($inscripcion,'cursos_hermanos') ?: 'Ninguno').'</td>
  </tr>
<tr>
  <td width="40%"><b>Fecha de Inscripción:</b> '.v($inscripcion,'fecha_inscripcion').'</td>
  <td width="60%"><b>Responsable de Inscripción:</b> '.v($inscripcion,'responsable_nombre').' '.v($inscripcion,'responsable_apellido').'</td>
</tr>
</table>';
$pdf->writeHTML($htmlEst,true,false,false,false,'');

// -------------------- INSCRIPCIÓN --------------------
$pdf->SetFont('helvetica','B',10);
$pdf->Cell(0, 8, 'INSCRIPCIÓN', 0, 1, 'C');
$pdf->SetFont('helvetica','',9);

for($i=0;$i<4;$i++){
  $block = '<table border="1" cellpadding="3" cellspacing="0" width="100%" style="font-size:9px;">
  <tr>
    <td width="20%"><b>Grado:</b></td>
    <td width="20%"><b>Sección:</b></td>
    <td width="30%"><b>Año Escolar:</b></td>
    <td width="30%"><b>Fecha:</b></td>
  </tr>
  <tr>
    <td colspan="2"><b>Nombre del Representante:</b></td>
    <td colspan="2"><b>C.I.:</b></td>
  </tr>
  <tr>
    <td><b>Aprobado:</b> Sí ( ) No ( )</td>
    <td>con el literal ______</td>
    <td colspan="2">Repitió: Sí ( ) No ( )</td>
  </tr>
  <tr>
    <td colspan="2"><b>Docente:</b></td>
    <td><b>C.I.:</b></td>
    <td><b>Firma del Docente:</b></td>
  </tr>
  <tr>
    <td width="50%"><b>Firma del Representante:</b></td>
    <td width="50%"><b>Teléfono:</b></td>
  </tr>
  </table><br>';
  $pdf->writeHTML($block,true,false,false,false,'');
}

// -------------------- PIE --------------------
$pdf->Ln(4);
$pdf->SetFont('helvetica','',9);
$pie = '<table border="1" width="100%" cellpadding="4" style="font-size:9px;">
<tr><td>
Firmo en señal de haber leído las Normas de Convivencia de la Unidad Educativa Colegio Fermín Toro Araure, cumplirlo y hacerlo cumplir por mi representado, y firmo dando fe que toda la información declarada en esta plantilla es fiel testimonio de la realidad de mi representado.
<br><br>
<b>Firma del Representante:</b> ___________________________ &nbsp;&nbsp; <b>C.I. No:</b> ___________________________
</td></tr>
</table>';
$pdf->writeHTML($pie,true,false,false,false,'');

$pdf->Output('Planilla_Inscripcion_'.$idInscripcion.'.pdf','I');
exit();