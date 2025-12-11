<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>UECFT Araure</title>
    <!-- Favicon -->
    <link rel="icon" href="../../../assets/images/fermin.png" type="image/png">

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

    <!-- CSS Crítico: Loader (Inline para evitar fOUC) -->
    <style>
        /* Contenedor principal del loader - CENTRADO */
        .loader_bg {
            position: fixed;
            z-index: 9999999;
            top: 0;
            left: 0;
            background: #fff;
            width: 100%;
            height: 100%;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
        }

        .loader-container {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* Anillo giratorio rojo */
        .spinner-ring {
            position: absolute;
            width: 150px;
            height: 150px;
            border: 4px solid rgba(201, 0, 0, 0.1);
            border-top: 4px solid #c90000;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Logo con efecto de pulso */
        .logo-pulse {
            width: 150px;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 1.5s ease-in-out infinite;
            z-index: 1;
            position: relative;
            left: 3px;
            top: 60px;
        }

        .logo-pulse img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }

        /* Texto de carga */
        .loader-text {
            margin-top: 100px;
            font-size: 16px;
            font-weight: 600;
            color: #c90000;
            letter-spacing: 2px;
            animation: fade 1.5s ease-in-out infinite;
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes pulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.1); opacity: 0.8; } }
        @keyframes fade { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body>
    <!-- loader  -->
    <div class="loader_bg">
       <div class="loader-container">
          <div class="spinner-ring"></div>
          <div class="logo-pulse">
             <img src="../../../assets/images/fermin.png" alt="UECFT Araure">
          </div>
          <div class="loader-text">Cargando...</div>
       </div>
    </div>
    <!-- end loader -->