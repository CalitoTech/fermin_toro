<?php
class Notificaciones {
    // Mensajes de éxito
    public static function exito($mensaje = "Acción realizada con éxito.") {
        return [
            'tipo' => 'success',
            'titulo' => 'Éxito',
            'texto' => $mensaje,
            'color' => '#c90000'
        ];
    }

    // Mensajes de error
    public static function error($mensaje = "Error al procesar la solicitud.") {
        return [
            'tipo' => 'error',
            'titulo' => 'Error',
            'texto' => $mensaje,
            'color' => '#c90000'
        ];
    }

    // Mensajes de advertencia
    public static function advertencia($mensaje = "Por favor, revisa los datos.") {
        return [
            'tipo' => 'warning',
            'titulo' => 'Advertencia',
            'texto' => $mensaje,
            'color' => '#c90000'
        ];
    }

    // Mensaje de bloqueo por intentos
    public static function bloqueo($tiempo_restante) {
        return [
            'tipo' => 'bloqueado',
            'tiempo' => $tiempo_restante,
            'texto' => "Has fallado 3 veces. Intenta de nuevo en <b>{$tiempo_restante}</b> segundos."
        ];
    }

    // Mostrar alerta en SweetAlert2
    public static function mostrar($alerta) {
        // Si es una alerta de bloqueo, inyectamos el tiempo
        if ($alerta['tipo'] === 'bloqueado') {
            $tiempo = (int) $alerta['tiempo'];
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Swal === 'undefined') {
                        console.error('SweetAlert2 no está disponible');
                        return;
                    }

                    let tiempoRestante = $tiempo;
                    const timerInterval = setInterval(() => {
                        tiempoRestante--;
                        const b = Swal.getHtmlContainer()?.querySelector('b');
                        if (b) b.textContent = tiempoRestante;
                        if (tiempoRestante <= 0) {
                            clearInterval(timerInterval);
                        }
                    }, 1000);

                    Swal.fire({
                        title: 'Acceso Bloqueado',
                        html: `Has fallado 3 veces. Intenta de nuevo en <b>$tiempo</b> segundos.`,
                        icon: 'error',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        timer: $tiempo * 1000,
                        willClose: () => {
                            clearInterval(timerInterval);
                        }
                    });
                });
            </script>";
        } else {
            // Para cualquier otra alerta (success, error, warning)
            $tipo = json_encode($alerta['tipo']);
            $titulo = json_encode($alerta['titulo']);
            $texto = json_encode($alerta['texto']);
            $color = json_encode($alerta['color']);
            
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof Swal === 'undefined') {
                        console.error('SweetAlert2 no está disponible');
                        return;
                    }

                    Swal.fire({
                        icon: $tipo,
                        title: $titulo,
                        text: $texto,
                        confirmButtonColor: $color
                    });
                });
            </script>";
        }
    }
}