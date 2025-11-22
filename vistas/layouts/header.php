<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>UECFT Araure</title>
    <!-- Favicon -->
    <link rel="icon" href="../../../assets/images/fermin.png" type="image/png">

    <!-- CSS Crítico Inline - Previene FOUC -->
    <style>
        /* Ocultar body hasta que los estilos estén cargados - SIN afectar otros estilos */
        body:not(.loaded) {
            visibility: hidden;
            opacity: 0;
        }

        /* Mostrar cuando esté listo con transición suave */
        body.loaded {
            visibility: visible;
            opacity: 1;
            transition: opacity 0.2s ease-in;
        }
    </style>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Boxicons -->
    <link href='https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Flatpickr (Calendario) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Sweet Alert 2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Toastr (opcional) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">


    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="../../../assets/css/menu.css">
    <link rel="stylesheet" href="../../../assets/css/style.css">
    <link rel="stylesheet" href="../../../assets/css/ver_inscripcion.css">
    <link rel="stylesheet" href="../../../assets/css/ver_representante.css">
    <link rel="stylesheet" href="../../../assets/css/flatpickr.css">

    <!-- Script de carga - Muestra la página cuando todo esté listo -->
    <script>
        // Función para mostrar el contenido
        function showContent() {
            if (!document.body.classList.contains('loaded')) {
                document.body.classList.add('loaded');
            }
        }

        // Método 1: Detectar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                // Esperar un frame para que los estilos se apliquen
                requestAnimationFrame(function() {
                    requestAnimationFrame(showContent);
                });
            });
        } else {
            // DOM ya está listo
            requestAnimationFrame(function() {
                requestAnimationFrame(showContent);
            });
        }

        // Método 2: Cuando todos los recursos (imágenes, CSS) estén cargados
        window.addEventListener('load', showContent);

        // Fallback de seguridad: mostrar después de 800ms máximo
        setTimeout(showContent, 800);
    </script>
</head>
<body>