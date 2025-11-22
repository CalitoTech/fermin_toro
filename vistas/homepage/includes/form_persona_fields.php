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
        'attrs' => 'pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" minlength="3" maxlength="40" onkeypress="return onlyText(event)" oninput="formatearTexto2()" placeholder="Ej: García Pérez"',
        'required' => true
    ],
    'Nombres' => [
        'type' => 'text',
        'label' => 'Nombres',
        'col' => 6,
        'attrs' => 'pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+" minlength="3" maxlength="40" onkeypress="return onlyText(event)" oninput="formatearTexto1()" placeholder="Ej: María José"',
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
        'attrs' => 'minlength="7" maxlength="8" pattern="[0-9]+" onkeypress="return onlyNumber(event)" placeholder="Ej: 12345678"',
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
        'col' => 6,
        'attrs' => 'minlength="3" maxlength="40" onkeypress="return onlyText3(event)" oninput="formatearTexto1()" placeholder="Ej: Docente, Comerciante, Sin Empleo"',
        'required' => true
    ],
    'TipoTrabajador' => [
        'type' => 'radio_buttons',
        'label' => 'Tipo de Trabajador',
        'col' => 6,
        'options' => 'tiposTrabajador',
        'option_value' => 'IdTipoTrabajador',
        'option_text' => 'tipo_trabajador',
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
        'attrs' => 'minlength="3" maxlength="40" oninput="formatearTexto1()" placeholder="Ej: Calle Principal, Casa Nº 45"',
        'required' => true
    ],
    'TelefonoHabitacion' => [
        'type' => 'tel_con_prefijo',
        'label' => 'Teléfono de Habitación',
        'col' => 4,
        'prefijo_tipo' => 'fijo', // filtra prefijos sin +
        'prefijo_default' => '0255',
        'tel_attrs' => 'minlength="7" maxlength="7" pattern="[0-9]+" onkeypress="return onlyNumber2(event)" placeholder="5521234"',
        'required' => true
    ],
    'Celular' => [
        'type' => 'tel_con_prefijo',
        'label' => 'Celular',
        'col' => 4,
        'prefijo_tipo' => 'internacional', // filtra prefijos con +
        'prefijo_default' => '+58',
        'tel_attrs' => 'minlength="10" maxlength="10" pattern="[0-9]+" onkeypress="return onlyNumber2(event)" placeholder="4121234567"',
        'required' => true
    ],
    'Correo' => [
        'type' => 'email',
        'label' => 'Correo electrónico',
        'col' => 4,
        'attrs' => 'minlength="10" maxlength="50" placeholder="Ej: correo@ejemplo.com"',
        'required' => true
    ],
    'LugarTrabajo' => [
        'type' => 'text',
        'label' => 'Lugar de Trabajo',
        'col' => 6,
        'attrs' => 'minlength="3" maxlength="40" oninput="formatearTexto1()" placeholder="Ej: Farmacia Central, No Aplica"',
        'required' => true
    ],
    'TelefonoTrabajo' => [
        'type' => 'tel_con_prefijo',
        'label' => 'Teléfono del Trabajo',
        'col' => 6,
        'prefijo_tipo' => 'internacional', // filtra prefijos con +
        'prefijo_default' => '+58',
        'tel_attrs' => 'minlength="10" maxlength="10" pattern="[0-9]+" onkeypress="return onlyNumber2(event)" placeholder="4121234567"',
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
    } elseif ($config['type'] === 'tel_con_prefijo') {
        // Renderizar teléfono con prefijo integrado (estilo cédula)
        $prefijoTipo = $config['prefijo_tipo'] ?? 'internacional';
        $prefijoDefault = $config['prefijo_default'] ?? '+58';
        $telAttrs = $config['tel_attrs'] ?? '';

        $prefijoInputId = $id . 'Prefijo_input';
        $prefijoHiddenId = $id . 'Prefijo';
        $prefijoHiddenNombre = $id . 'Prefijo_nombre';
        $prefijoResultadosId = $id . 'Prefijo_resultados';

        echo '<div class="input-group">';

        // Select de prefijo (estilo cédula)
        echo '<div class="position-relative" style="max-width: 100px;">';
        echo '<input type="text"
                class="form-control buscador-input text-center fw-bold prefijo-telefono"
                id="' . $prefijoInputId . '"
                autocomplete="off"
                placeholder="' . $prefijoDefault . '"
                data-prefijo-tipo="' . $prefijoTipo . '"
                maxlength="4"
                onkeypress="return /[0-9+]/.test(event.key)"
                oninput="this.value = this.value.replace(/[^0-9+]/g, \'\')"
                style="
                    border-top-right-radius: 0;
                    border-bottom-right-radius: 0;
                    border-right: none;
                    background: #f8f9fa;
                    color: #c90000;
                    font-size: 0.9rem;
                    font-weight: bold;
                ">';
        echo '<input type="hidden" id="' . $prefijoHiddenId . '" name="' . $name . 'Prefijo" value="">';
        echo '<input type="hidden" id="' . $prefijoHiddenNombre . '" name="' . $name . 'Prefijo_nombre">';
        echo '<div id="' . $prefijoResultadosId . '" class="autocomplete-results d-none" style="z-index: 10000;"></div>';
        echo '</div>';

        // Input del número de teléfono
        echo '<input type="tel"
                class="form-control"
                id="' . $id . '"
                name="' . $name . '"
                ' . $telAttrs . '
                ' . $requiredAttr . '
                style="border-top-left-radius: 0; border-bottom-left-radius: 0;">';

        // Icono de teléfono
        echo '<span class="input-group-text"><i class="fas fa-phone" style="color: #c90000;"></i></span>';

        echo '</div>';

        // Script para inicializar el buscador de prefijos
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const buscador = new BuscadorGenerico(
                "' . $prefijoInputId . '",
                "' . $prefijoResultadosId . '",
                "prefijo",
                "' . $prefijoHiddenId . '",
                "' . $prefijoHiddenNombre . '"
            );

            // Establecer valor por defecto y buscar ID en BD
            const inputPrefijo = document.getElementById("' . $prefijoInputId . '");
            inputPrefijo.value = "' . $prefijoDefault . '";

            // Formatear prefijo: evitar que se borre el + en internacionales
            const esInternacional = "' . $prefijoTipo . '" === "internacional";
            if (esInternacional) {
                inputPrefijo.addEventListener("input", function(e) {
                    let valor = this.value;
                    // Si no empieza con +, agregarlo
                    if (!valor.startsWith("+")) {
                        this.value = "+" + valor.replace(/\+/g, "");
                    }
                    // Asegurar que solo haya un + al inicio
                    if (valor.indexOf("+") > 0) {
                        this.value = "+" + valor.replace(/\+/g, "");
                    }
                });
                inputPrefijo.addEventListener("keydown", function(e) {
                    // Evitar que se borre el + cuando está al inicio
                    if (this.value === "+" && (e.key === "Backspace" || e.key === "Delete")) {
                        e.preventDefault();
                    }
                });
            }

            // Buscar el ID del prefijo por defecto
            const prefijoTipo = "' . $prefijoTipo . '";
            const baseUrl = buscador.baseUrl;
            fetch(`${baseUrl}?tipo=prefijo&q=' . urlencode($prefijoDefault) . '&filtro=${prefijoTipo}`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const prefijoEncontrado = data.find(p => p.codigo_prefijo === "' . $prefijoDefault . '");
                        if (prefijoEncontrado) {
                            document.getElementById("' . $prefijoHiddenId . '").value = prefijoEncontrado.IdPrefijo;
                        }
                    }
                })
                .catch(err => console.error("Error al cargar prefijo por defecto:", err));
        });
        </script>';
    } elseif ($config['type'] === 'radio_buttons') {
        // Renderizar radio buttons (horizontal)
        $options_data = $data_options[$config['options']] ?? [];
        echo '<div class="row mt-2">';
        foreach ($options_data as $option) {
            $optionValue = $option[$config['option_value']];
            $optionText = $option[$config['option_text']];
            $radioId = $id . '_' . $optionValue;

            echo '<div class="col-md-6">';
            echo '<div class="custom-control custom-radio">';
            echo '<input type="radio" id="' . $radioId . '" name="' . $name . '" class="custom-control-input" value="' . $optionValue . '" ' . $requiredAttr . '>';
            echo '<label class="custom-control-label" for="' . $radioId . '">' . htmlspecialchars($optionText) . '</label>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
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

        // Nacionalidad y Cédula del contacto de emergencia
        echo '<div class="col-md-4">';
        echo '<div class="form-group required-field">';
        echo '<label for="emergenciaCedula">Cédula</label>';
        echo '<div class="input-group">';
        echo '<select class="form-select form-select-sm" name="emergenciaNacionalidad" id="emergenciaNacionalidad" style="max-width: 60px; border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: none; text-align: center; font-weight: bold; background: #f8f9fa; color: #c90000; font-size: 0.9rem;" required>';
        foreach ($data_options['nacionalidades'] as $nacionalidad) {
            echo '<option value="' . $nacionalidad['IdNacionalidad'] . '">' . $nacionalidad['nacionalidad'] . '</option>';
        }
        echo '</select>';
        echo '<input type="text" class="form-control" id="emergenciaCedula" name="emergenciaCedula" minlength="7" maxlength="8" pattern="[0-9]+" onkeypress="return onlyNumber(event)" placeholder="Ej: 12345678" required style="border-top-left-radius: 0; border-bottom-left-radius: 0;">';
        echo '</div>';
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
        echo '</div>';

        // Segunda fila: Celular
        echo '<div class="row">';
        echo '<div class="col-md-4">';
        echo '<div class="form-group required-field">';
        echo '<label for="emergenciaCelular">Celular</label>';
        echo '<div class="input-group">';

        // Prefix selector
        echo '<div class="position-relative" style="max-width: 100px;">';
        echo '<input type="text" class="form-control buscador-input text-center fw-bold prefijo-telefono"
                id="emergenciaCelularPrefijo_input" maxlength="4" data-prefijo-tipo="internacional"
                onkeypress="return /[0-9+]/.test(event.key)"
                oninput="this.value = this.value.replace(/[^0-9+]/g, \'\')"
                style="border-top-right-radius: 0; border-bottom-right-radius: 0; border-right: none; background: #f8f9fa; color: #c90000;">';
        echo '<input type="hidden" id="emergenciaCelularPrefijo" name="emergenciaCelularPrefijo" required>';
        echo '<input type="hidden" id="emergenciaCelularPrefijo_nombre" name="emergenciaCelularPrefijo_nombre">';
        echo '<div id="emergenciaCelularPrefijo_resultados" class="autocomplete-results d-none"></div>';
        echo '</div>';

        // Phone number input
        echo '<input type="tel" class="form-control" id="emergenciaCelular" name="emergenciaCelular"
                minlength="10" maxlength="10" pattern="[0-9]+" onkeypress="return onlyNumber2(event)" placeholder="4121234567" required
                style="border-top-left-radius: 0; border-bottom-left-radius: 0;">';

        // Phone icon
        echo '<span class="input-group-text"><i class="fas fa-phone"></i></span>';
        echo '</div>';

        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const buscador = new BuscadorGenerico(
                "emergenciaCelularPrefijo_input",
                "emergenciaCelularPrefijo_resultados",
                "prefijo",
                "emergenciaCelularPrefijo",
                "emergenciaCelularPrefijo_nombre"
            );

            // Establecer valor por defecto
            const inputPrefijo = document.getElementById("emergenciaCelularPrefijo_input");
            inputPrefijo.value = "+58";

            // Formatear prefijo: evitar que se borre el +
            inputPrefijo.addEventListener("input", function(e) {
                let valor = this.value;
                if (!valor.startsWith("+")) {
                    this.value = "+" + valor.replace(/\+/g, "");
                }
                if (valor.indexOf("+") > 0) {
                    this.value = "+" + valor.replace(/\+/g, "");
                }
            });
            inputPrefijo.addEventListener("keydown", function(e) {
                if (this.value === "+" && (e.key === "Backspace" || e.key === "Delete")) {
                    e.preventDefault();
                }
            });

            // Buscar el ID del prefijo por defecto
            const baseUrl = buscador.baseUrl;
            fetch(`${baseUrl}?tipo=prefijo&q=%2B58&filtro=internacional`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const prefijoEncontrado = data.find(p => p.codigo_prefijo === "+58");
                        if (prefijoEncontrado) {
                            document.getElementById("emergenciaCelularPrefijo").value = prefijoEncontrado.IdPrefijo;
                        }
                    }
                })
                .catch(err => console.error("Error al cargar prefijo por defecto:", err));
        });
        </script>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Renderiza solo los campos de una persona (sin card wrapper)
 * Usado en solicitar_cupo.php donde el card ya está definido en el HTML
 *
 * @param string $tipo Tipo de persona (madre, padre, representante)
 * @param string $parentesco_value Valor del parentesco
 * @param array $data_options Arreglos con opciones para selects
 * @param bool $mostrar_parentesco Si debe mostrar el campo de parentesco
 */
function renderizarCamposPersona($tipo, $parentesco_value, $data_options, $mostrar_parentesco = true) {
    global $campos_persona;

    // Generar campos organizados por filas
    $fila_actual = [];
    $cols_actuales = 0;

    foreach ($campos_persona as $nombre_campo => $config) {
        // Omitir parentesco si no se debe mostrar
        if ($nombre_campo === 'Parentesco' && !$mostrar_parentesco) {
            continue;
        }

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
}
?>
