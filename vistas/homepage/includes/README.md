# Optimización del Formulario de Solicitud de Cupo

## Resumen

Este directorio contiene archivos auxiliares para optimizar el código de `solicitud_cupo.php`, reduciendo la duplicación de código HTML mediante la reutilización de funciones.

## Archivos

### `form_persona_fields.php`

Archivo auxiliar que contiene:

1. **`$campos_persona`**: Array que define los campos comunes para madre, padre y representante legal
   - Configuración de tipo, label, columnas, atributos y validaciones
   - Centraliza la definición de campos para evitar duplicación

2. **`renderizarCampoPersona()`**: Función que renderiza un campo individual
   - Parámetros: tipo, campo, configuración, opciones de datos, valor de parentesco
   - Maneja diferentes tipos: text, email, tel, select, readonly
   - Aplica validaciones y atributos definidos en la configuración

3. **`renderizarBloquePersona()`**: Función que renderiza el bloque completo de una persona
   - Parámetros: tipo, título, icono, collapse_id, parentesco, opciones, show_class, incluir_emergencia
   - Genera automáticamente todas las filas de campos
   - Opcionalmente incluye bloque de contacto de emergencia (solo para madre)

## Beneficios de la Optimización

### Reducción de Código
- **Antes**: 1005 líneas en `solicitud_cupo.php`
- **Después**: 631 líneas en `solicitud_cupo.php`
- **Reducción**: 374 líneas (37%)

### Ventajas
1. **Mantenibilidad**: Cambios en la estructura de campos se hacen en un solo lugar
2. **Consistencia**: Todos los bloques (madre, padre, representante) usan la misma estructura
3. **Escalabilidad**: Fácil agregar o modificar campos sin duplicar código
4. **Legibilidad**: Código más limpio y fácil de entender
5. **Reutilización**: Mismo patrón usado en `nuevo_inscripcion.php`

## Uso

En `solicitud_cupo.php`:

```php
<?php
// Incluir funciones auxiliares
require_once __DIR__ . '/includes/form_persona_fields.php';

// Preparar opciones para los selects
$data_options = [
    'nacionalidades' => $nacionalidades,
    'urbanismos' => $urbanismos,
    'parentescos' => $parentescos
];

// Renderizar bloque de la Madre (con contacto de emergencia)
renderizarBloquePersona('madre', 'Datos de la Madre', 'fa-female', 'datosMadre', 'Madre', $data_options, '', true);

// Renderizar bloque del Padre
renderizarBloquePersona('padre', 'Datos del Padre', 'fa-male', 'datosPadre', 'Padre', $data_options, '', false);
?>
```

## Estructura de Campos

Los campos se organizan automáticamente en filas de 12 columnas Bootstrap:

- Apellidos (6 col) + Nombres (6 col) = 12 col → 1 fila
- Nacionalidad (3) + Cédula (3) + Parentesco (6) = 12 col → 1 fila
- Ocupación (12 col) = 12 col → 1 fila
- Urbanismo (6) + Dirección (6) = 12 col → 1 fila
- Teléfono Habitación (4) + Celular (4) + Correo (4) = 12 col → 1 fila
- Lugar Trabajo (6) + Teléfono Trabajo (6) = 12 col → 1 fila

## Notas Técnicas

- La función `renderizarBloquePersona()` maneja automáticamente el layout responsive
- El campo "Parentesco" es readonly para madre/padre, pero select para representante
- Los campos requeridos se marcan con la clase `required-field`
- Las validaciones (pattern, minlength, maxlength) están centralizadas en `$campos_persona`
