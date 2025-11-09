<?php
/**
 * Archivo auxiliar para renderizar campos de personas (madre, padre, representante)
 * Similar a la lógica usada en nuevo_inscripcion.php
 */

// Definición de campos comunes para padres y representante
$campos_persona = [
    'Apellidos' => [
        'type' => 'text',
        'label' => 'Apellidos',
        'col' => 6,
        'attrs' => 'pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" minlength="3" maxlength="40" onkeypress="return onlyText(event)" oninput="formatearTexto2()"',
        'required' => true
    ],
    'Nombres' => [
        'type' => 'text',
        'label' => 'Nombres',
        'col' => 6,
        'attrs' => 'pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" minlength="3" maxlength="40" onkeypress="return onlyText(event)" oninput="formatearTexto1()"',
        'required' => true
    ],
    'Nacionalidad' => [
        'type' => 'select',
        'label' => 'Nacionalidad',
        'col' => 3,
        'options' => 'nacionalidades',
        'option_value' => 'IdNacionalidad',
        'option_text' => 'nacionalidad',
        'required' => true
    ],
    'Cedula' => [
        'type' => 'text',
        'label' => 'Cédula',
        'col' => 3,
        'attrs' => 'minlength="7" maxlength="8" pattern="[0-9]+" onkeypress="return onlyNumber(event)"',
        'required' => true
    ],
    'Parentesco' => [
        'type' => 'readonly',
        'label' => 'Parentesco',
        'col' => 6,
        'required' => false
    ],
    'Ocupacion' => [
        'type' => 'text',
        'label' => 'Ocupación',
        'col' => 12,
        'attrs' => 'pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" minlength="3" maxlength="40" onkeypress="return onlyText(event)" oninput="formatearTexto1()"',
        'required' => true
    ],
    'Urbanismo' => [
        'type' => 'buscador',
        'label' => 'Urbanismo / Sector',
        'col' => 6,
        'buscador_tipo' => 'urbanismo',
        'required' => true
    ],
    'Direccion' => [
        'type' => 'text',
        'label' => 'Dirección de Habitación',
        'col' => 6,
        'attrs' => 'minlength="3" maxlength="40" oninput="formatearTexto1()"',
        'required' => true
    ],
    'TelefonoHabitacion' => [
        'type' => 'tel',
        'label' => 'Teléfono de Habitación',
        'col' => 4,
        'attrs' => 'minlength="11" maxlength="20" pattern="[0-9]+" onkeypress="return onlyNumber2(event)"',
        'required' => true
    ],
    'Celular' => [
        'type' => 'tel',
        'label' => 'Celular',
        'col' => 4,
        'attrs' => 'minlength="11" maxlength="20" pattern="[0-9]+" onkeypress="return onlyNumber2(event)"',
        'required' => true
    ],
    'Correo' => [
        'type' => 'email',
        'label' => 'Correo electrónico',
        'col' => 4,
        'attrs' => 'minlength="10" maxlength="50"',
        'required' => true
    ],
    'LugarTrabajo' => [
        'type' => 'text',
        'label' => 'Lugar de Trabajo',
        'col' => 6,
        'attrs' => 'minlength="3" maxlength="40" oninput="formatearTexto1()"',
        'required' => true
    ],
    'TelefonoTrabajo' => [
        'type' => 'tel',
        'label' => 'Teléfono del Trabajo',
        'col' => 6,
        'attrs' => 'minlength="11" maxlength="20" pattern="[0-9]+" onkeypress="return onlyNumber2(event)"',
        'required' => false
    ],
];

/**
 * Renderiza un campo de formulario
 *
 * @param string $tipo Prefijo del campo (madre, padre, representante)
 * @param string $campo Nombre del campo
 * @param array $config Configuración del campo
 * @param array $data_options Arreglos con opciones para selects (nacionalidades, urbanismos, etc.)
 * @param string $parentesco_value Valor para el campo de parentesco readonly
 */
function renderizarCampoPersona($tipo, $campo, $config, $data_options, $parentesco_value = '') {
    $id = $tipo . $campo;
    $name = $tipo . $campo;
    $required = $config['required'] ?? true;
    $requiredAttr = $required ? 'required' : '';
    $requiredClass = $required ? 'required-field' : '';

    echo '<div class="col-md-' . $config['col'] . '">';
    echo '<div class="form-group ' . $requiredClass . '">';
    echo '<label for="' . $id . '">' . $config['label'] . '</label>';

    if ($config['type'] === 'select') {
        $options_data = $data_options[$config['options']] ?? [];
        echo '<select class="form-control" id="' . $id . '" name="' . $name . '" ' . $requiredAttr . '>';
        echo '<option value="">Seleccione una opción</option>';
        foreach ($options_data as $option) {
            echo '<option value="' . $option[$config['option_value']] . '">' . $option[$config['option_text']] . '</option>';
        }
        echo '</select>';
    } elseif ($config['type'] === 'buscador') {
        // Renderizar buscador personalizado
        $buscadorTipo = $config['buscador_tipo'] ?? 'urbanismo';
        $inputId = $id . '_input';
        $hiddenId = $id;
        $hiddenNombre = $id . '_nombre';
        $resultadosId = $id . '_resultados';

        echo '<div class="position-relative">';
        echo '<input type="text" class="form-control buscador-input" id="' . $inputId . '" autocomplete="off" placeholder="Buscar o escribir nuevo...">';
        echo '<input type="hidden" id="' . $hiddenId . '" name="' . $name . '" ' . $requiredAttr . '>';
        echo '<input type="hidden" id="' . $hiddenNombre . '" name="' . $name . '_nombre">';
        echo '<div id="' . $resultadosId . '" class="autocomplete-results d-none"></div>';
        echo '</div>';

        // Agregar atributo data para inicializar el buscador después
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            new BuscadorGenerico("' . $inputId . '", "' . $resultadosId . '", "' . $buscadorTipo . '", "' . $hiddenId . '", "' . $hiddenNombre . '");
        });
        </script>';
    } elseif ($config['type'] === 'readonly') {
        echo '<input type="text" class="form-control" id="' . $id . '" name="' . $name . '" value="' . htmlspecialchars($parentesco_value) . '" readonly>';
    } else {
        $attrs = $config['attrs'] ?? '';
        echo '<input type="' . $config['type'] . '" class="form-control" id="' . $id . '" name="' . $name . '" ' . $attrs . ' ' . $requiredAttr . '>';
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Renderiza el bloque completo de una persona (madre, padre o representante)
 *
 * @param string $tipo Tipo de persona (madre, padre, representante)
 * @param string $titulo Título del card
 * @param string $icono Clase del icono FontAwesome
 * @param string $collapse_id ID del collapse
 * @param string $parentesco_value Valor del parentesco
 * @param array $data_options Arreglos con opciones para selects
 * @param string $show_class Clase para mostrar el collapse por defecto
 * @param bool $incluir_emergencia Si debe incluir el bloque de contacto de emergencia
 */
function renderizarBloquePersona($tipo, $titulo, $icono, $collapse_id, $parentesco_value, $data_options, $show_class = '', $incluir_emergencia = false) {
    global $campos_persona;

    echo '<div class="card mb-4">';
    echo '<div class="card-header form-title" style="background-color: #c90000; color: white;" data-toggle="collapse" data-target="#' . $collapse_id . '">';
    echo '<h5><i class="fas ' . $icono . ' mr-2"></i>' . $titulo . '</h5>';
    echo '</div>';
    echo '<div class="card-body collapse ' . $show_class . '" id="' . $collapse_id . '">';

    // Generar campos organizados por filas
    $fila_actual = [];
    $cols_actuales = 0;

    foreach ($campos_persona as $nombre_campo => $config) {
        $fila_actual[] = ['nombre' => $nombre_campo, 'config' => $config];
        $cols_actuales += $config['col'];

        // Si completamos 12 columnas o es el último campo, renderizamos la fila
        if ($cols_actuales >= 12 || $nombre_campo === array_key_last($campos_persona)) {
            echo '<div class="row">';
            foreach ($fila_actual as $item) {
                renderizarCampoPersona($tipo, $item['nombre'], $item['config'], $data_options, $parentesco_value);
            }
            echo '</div>';

            // Resetear para la siguiente fila
            $fila_actual = [];
            $cols_actuales = 0;
        }
    }

    // Contacto de emergencia (solo para madre)
    if ($incluir_emergencia) {
        echo '<div class="row">';
        echo '<div class="col-md-4">';
        echo '<div class="form-group required-field">';
        echo '<label for="emergenciaNombre">En caso de emergencia, llamar a:</label>';
        echo '<input type="text" class="form-control" id="emergenciaNombre" name="emergenciaNombre" pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" minlength="3" maxlength="40" onkeypress="return onlyText(event)" oninput="formatearTexto1()" required>';
        echo '</div>';
        echo '</div>';

        echo '<div class="col-md-4">';
        echo '<div class="form-group required-field">';
        echo '<label for="emergenciaParentesco">Parentesco</label>';
        echo '<div class="position-relative">';
        echo '<input type="text" class="form-control buscador-input" id="emergenciaParentesco_input" autocomplete="off" placeholder="Buscar o escribir nuevo parentesco...">';
        echo '<input type="hidden" id="emergenciaParentesco" name="emergenciaParentesco" required>';
        echo '<input type="hidden" id="emergenciaParentesco_nombre" name="emergenciaParentesco_nombre">';
        echo '<div id="emergenciaParentesco_resultados" class="autocomplete-results d-none"></div>';
        echo '</div>';
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            new BuscadorGenerico("emergenciaParentesco_input", "emergenciaParentesco_resultados", "parentesco", "emergenciaParentesco", "emergenciaParentesco_nombre");
        });
        </script>';
        echo '</div>';
        echo '</div>';

        echo '<div class="col-md-4">';
        echo '<div class="form-group required-field">';
        echo '<label for="emergenciaCelular">Celular</label>';
        echo '<input type="tel" class="form-control" id="emergenciaCelular" name="emergenciaCelular" minlength="11" maxlength="20" pattern="[0-9]+" onkeypress="return onlyNumber2(event)" required>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}
?>
