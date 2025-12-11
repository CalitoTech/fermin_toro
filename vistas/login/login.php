<?php
session_start();

if(isset($_SESSION['usuario'])){
    header("location: ../inicio/inicio/inicio.php");
}

require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../modelos/Nacionalidad.php';

// Obtener nacionalidades
$database = new Database();
$conexionPDO = $database->getConnection();
$modeloNacionalidad = new Nacionalidad($conexionPDO);
$nacionalidades = $modeloNacionalidad->obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>UECFT Araure</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
    <link rel="icon" href="../../assets/images/fermin.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Incluyendo SweetAlert2 CSS y JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>

</head>

<body>
    <?php include '../layouts/loader.php'; ?>
    <img class="wave" src="img/wave2.png">

    <!-- Botón volver flotante -->
    <div class="back-container">
        <a href="../homepage/index.php" class="btn-back">
            <i class='bx bx-arrow-back'></i>
            <span class="text">Volver</span>
        </a>
    </div>

    <div class="container">
        <div class="img">
            <img src="../../assets/images/fermin.png">
        </div>
        <div class="login-content">
            <!-- Formulario de inicio de sesión -->
            <form id="login-form" method="POST" action="../../controladores/login/iniciar_sesion.php">
                <picture>
                    <source srcset="../../assets/images/fermin.png" media="(max-width: 900px)">
                    <img src="img/avatar.svg">
                </picture>
                <h2 class="title">BIENVENIDO/A</h2>              
                <div class="input-div one">
                    <div class="i"><i class="fas fa-user"></i></div>
                    <div class="div">
                        <h5>Usuario</h5>
                        <input id="usuario" type="text" class="input" name="usuario" required autocomplete="off">
                    </div>
                </div>
                <div class="input-div pass">
                    <div class="i"><i class="fas fa-lock"></i></div>
                    <div class="div">
                        <h5>Contraseña</h5>
                        <input type="password" id="input" class="input" name="password" required autocomplete="off">
                    </div>
                </div>
                <div class="view">
                    <div class="fas fa-eye verPassword" onclick="vistas()" id="verPassword"></div>
                </div>
                <input name="btningresar" class="btn" type="submit" value="INICIAR SESIÓN">
                <p class="forgot-text" onclick="mostrarRecuperacion()">¿Olvidaste tu contraseña?</p>
            </form>

            <!-- FORMULARIO RECUPERACIÓN -->
            <form id="recover-form" class="hidden" method="POST" action="../../controladores/login/iniciar_sesion.php">
                <picture>
                    <source srcset="../../assets/images/fermin.png" media="(max-width: 900px)">
                    <img src="img/avatar.svg">
                </picture>
                <h2 class="title">Recuperar Contraseña</h2>     

                <!-- Documento (Nacionalidad + Cédula) -->
                <div class="input-div one doc-input">
                    <div class="div">
                        <h5>Documento</h5>
                        <div class="doc-group">
                            <select name="nacionalidad" id="nacionalidad" required>
                                <?php foreach ($nacionalidades as $nac): ?>
                                    <option value="<?= $nac['IdNacionalidad'] ?>" <?= $nac['IdNacionalidad'] == 1 ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($nac['nacionalidad']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input 
                                id="cedula" 
                                type="text" 
                                name="cedula" 
                                placeholder="Ej: 12345678" 
                                required 
                                autocomplete="off" 
                                pattern="[0-9]+" 
                                minlength="6" 
                                maxlength="9"
                            >
                        </div>
                    </div>
                </div>

                <!-- Código temporal -->
                <div class="input-div one">
                    <div class="i"><i class="fas fa-key"></i></div>
                    <div class="div">
                        <h5>Código de verificación</h5>
                        <input id="codigo" type="text" class="input" name="codigo" autocomplete="off">
                    </div>
                </div>

                <!-- Botón enviar código -->
                <button type="button" id="btnAvisoRecuperacion" class="btn-whatsapp mt-3">
                    <i class="fab fa-whatsapp"></i> Recibir código por WhatsApp
                </button>

                <!-- Validar código -->
                <input name="btnrecuperar" class="btn" type="submit" value="VALIDAR CÓDIGO">

                <p class="forgot-text" onclick="mostrarLogin()">← Volver al inicio de sesión</p>
            </form>
        </div>
    </div>

    <script src="js/fontawesome.js"></script>
    <script src="js/main.js"></script>
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/bootstrap.bundle.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* 🔙 Botón volver flotante */
        .back-container {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 9999;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            background-color: white;
            border: 2px solid #c90000;
            color: #c90000;
            font-weight: 600;
            font-size: 16px;
            border-radius: 50px;
            padding: 8px 18px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .btn-back i {
            font-size: 20px;
            margin-right: 6px;
            transition: transform 0.3s ease;
        }

        .btn-back:hover {
            background-color: #c90000;
            color: white;
            transform: translateY(-2px);
            text-decoration: none;
        }

        .btn-back:hover i {
            transform: translateX(-4px);
        }

        .btn-back .text {
            display: inline-block;
        }

        /* 🔐 Enlace olvidé mi contraseña */
        .forgot-container {
            margin-top: 15px;
            text-align: center;
        }

        .forgot-link {
            color: #c90000;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .forgot-link:hover {
            color: #a00000;
            text-decoration: underline;
        }

        /* 📱 Responsive */
        @media (max-width: 768px) {
            .btn-back {
                padding: 6px 14px;
                font-size: 14px;
            }

            .btn-back i {
                font-size: 18px;
                margin-right: 4px;
            }

            .forgot-link {
                font-size: 15px;
            }
        }

        /* ====== Tamaño del formulario corregido ====== */
        .login-content {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            min-height: 500px;
        }

        .login-content form {
            border-radius: 15px;
            position: absolute;
            transition: all 0.6s ease;
        }

        /* Animaciones de transición */
        .hidden {
            opacity: 0;
            transform: translateY(40px) scale(0.95);
            pointer-events: none;
        }

        .visible {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: all;
        }

        /* Estilo del texto de “Olvidé mi contraseña” */
        .forgot-text {
            margin-top: 15px;
            color: #c90000;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .forgot-text:hover {
            color: #a00000;
            text-decoration: underline;
        }

        /* 🌿 Botón de WhatsApp personalizado */
        .btn-whatsapp {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #25d366;
            color: white;
            font-weight: 600;
            border: none;
            border-radius: 50px;
            padding: 10px 22px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 6px rgba(0,0,0,0.2);
            width: 100%;
            margin-top: 8px;
        }

        .btn-whatsapp i {
            margin-right: 8px;
            font-size: 18px;
        }

        .btn-whatsapp:hover {
            background-color: #28ec73ff;
            transform: translateY(-2px);
        }

        .btn-whatsapp:disabled {
            background-color: #76f0a5ff !important;
            color: #ffffff;
            font-weight: 500;
            box-shadow: none;
            transform: none;
        }


        /* 📱 Ajuste del formulario de recuperación */
        #recover-form {
            margin-top: -20px; /* lo sube un poco */
        }

        /* 🔼 Asegura que el texto “volver al inicio” quede visible */
        #recover-form .forgot-text {
            margin-top: 12px;
            margin-bottom: 0;
        }

        /* En pantallas grandes, subimos un poco todo el bloque */
        @media (min-width: 992px) {
            .login-content {
                transform: translateY(-30px);
            }
        }

        /* ===== Documento (nacionalidad + cédula) ===== */
        .doc-input {
            margin-bottom: 20px;
        }

        .doc-group {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
        }

        .doc-group select {
            width: 75px;
            border: 2px solid #c90000;
            border-radius: 10px;
            padding: 8px 6px;
            font-weight: bold;
            color: #c90000;
            text-align: center;
            background-color: #fff;
            transition: all 0.3s ease;
        }

        .doc-group select:hover {
            background-color: #f9f9f9;
        }

        .doc-group input {
            flex: 1;
            border: 2px solid #ccc;
            border-radius: 10px;
            padding: 8px 10px;
            outline: none;
            transition: all 0.3s ease;
        }

        .doc-group input:focus {
            border-color: #c90000;
        }

        /* 🎯 Ajuste específico solo para los campos de documento (recuperación) */
        #recover-form .input-div.one .div h5 {
            top: -10px;
            font-size: 13px;
            color: #555;
        }
    </style>

    <script>
        function mostrarRecuperacion() {
            const loginForm = document.getElementById("login-form");
            const recoverForm = document.getElementById("recover-form");

            // Ocultar el login
            loginForm.classList.remove("visible");
            loginForm.classList.add("hidden");

            // Mostrar recuperación después de la animación
            setTimeout(() => {
                recoverForm.classList.remove("hidden");
                recoverForm.classList.add("visible");
            }, 300);
        }

        function mostrarLogin() {
            const loginForm = document.getElementById("login-form");
            const recoverForm = document.getElementById("recover-form");

            // Ocultar recuperación
            recoverForm.classList.remove("visible");
            recoverForm.classList.add("hidden");

            // Mostrar login después de la animación
            setTimeout(() => {
                loginForm.classList.remove("hidden");
                loginForm.classList.add("visible");
            }, 300);
        }

        // ✅ Botón WhatsApp con control de reintento y temporizador
        const btnWhatsApp = document.getElementById('btnAvisoRecuperacion');
        let cooldown = false; // Estado para controlar los 60 segundos
        let timerInterval;

        btnWhatsApp.addEventListener('click', async () => {
            const cedula = document.getElementById('cedula')?.value.trim();
            const nacionalidad = document.getElementById('nacionalidad')?.value;

            if (!cedula) {
                Swal.fire({
                    title: "Atención",
                    text: "Debes ingresar tu cédula antes de solicitar el código.",
                    icon: "warning",
                    confirmButtonColor: "#c90000"
                });
                return;
            }

            try {
                const response = await fetch('../../controladores/EnviarAvisoRecuperacion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `nacionalidad=${encodeURIComponent(nacionalidad)}&cedula=${encodeURIComponent(cedula)}`
                });

                const data = await response.json();

                if (data.ok) {
                    Swal.fire({
                        title: "Código enviado",
                        text: data.mensaje || "Se ha enviado un nuevo código a tu WhatsApp.",
                        icon: "success",
                        confirmButtonColor: "#28a745"
                    });

                    iniciarCooldown(60);
                } 
                else if (data.bloqueado) {
                    iniciarCooldown(data.tiempo_restante);

                    // 🕒 Mostrar alerta con cuenta regresiva dinámica
                    let tiempo = data.tiempo_restante;
                    Swal.fire({
                        title: "Espera antes de volver a enviar",
                        html: `Por favor espera <b>${tiempo}</b> segundos antes de solicitar un nuevo código.`,
                        icon: "info",
                        showConfirmButton: true,
                        allowOutsideClick: false,
                        didOpen: () => {
                            const interval = setInterval(() => {
                                tiempo--;
                                if (tiempo <= 0) {
                                    clearInterval(interval);
                                    Swal.close();
                                } else {
                                    const b = Swal.getHtmlContainer().querySelector("b");
                                    if (b) b.textContent = tiempo;
                                }
                            }, 1000);
                        }
                    });
                } 
                else {
                    Swal.fire({
                        title: "Error",
                        text: data.mensaje || "No se pudo enviar el código. Intenta más tarde.",
                        icon: "error",
                        confirmButtonColor: "#c90000"
                    });
                }
            } catch (err) {
                Swal.fire({
                    title: "Error",
                    text: "Ocurrió un problema al enviar el código. Inténtalo nuevamente.",
                    icon: "error",
                    confirmButtonColor: "#c90000"
                });
            }
        });

        // 🕒 Función que bloquea el botón y muestra tiempo dinámico
        function iniciarCooldown(segundos) {
            const boton = document.getElementById('btnAvisoRecuperacion');
            boton.disabled = true;
            let restante = segundos;

            const intervalo = setInterval(() => {
                boton.innerHTML = `<i class="fas fa-hourglass-half"></i> Espera ${restante}s`;
                restante--;
                if (restante <= 0) {
                    clearInterval(intervalo);
                    boton.disabled = false;
                    boton.innerHTML = `<i class="fab fa-whatsapp"></i> Recibir código por WhatsApp`;
                }
            }, 1000);
        }
    </script>

</body>
</html>