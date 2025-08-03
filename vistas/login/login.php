<?php
    session_start();

    if(isset($_SESSION['usuario'])){
        header("location: ../inicio/inicio.php");
    }
?>


<!DOCTYPE html>
<html lang="en">

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> <!-- Agregar Font Awesome -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body >
    
    <img class="wave" src="img/wave2.png">
    <div class="container">
        <div class="back-arrow">
            <a href="../homepage/index.html" class="btn-back">
                <i title="Volver al Inicio" class='bx bx-undo'></i>
            </a>
        </div>
        <div class="img">
            <img src="../../assets/images/fermin.png">
        </div>
        <div class="login-content">
            <form method="POST" action="../../controladores/login/iniciar_sesion.php">
                <img src="img/avatar.svg">
                <h2 class="title">BIENVENIDO/A</h2>              
                <div class="input-div one">
                    <div class="i">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="div">
                        <h5>Usuario</h5>
                        <input id="usuario" type="text" class="input" name="usuario" title="Ingrese su nombre de usuario" autocomplete="off" value="" required>
                    </div>
                </div>
                <div class="input-div pass">
                    <div class="i">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="div">
                        <h5>Contraseña</h5>
                        <input type="password" id="input" class="input" name="password" title="Ingrese su clave para ingresar" autocomplete="off" required>
                    </div>
                </div>
                <div class="view">
                    <div class="fas fa-eye verPassword" onclick="vistas()" id="verPassword"></div>
                </div>

                <input name="btningresar" class="btn" title="click para ingresar" type="submit" value="INICIAR SESION">
            </form>
        </div>
    </div>
    <script src="js/fontawesome.js"></script>
    <script src="js/main.js"></script>
    <script src="js/main2.js"></script>
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.js"></script>
    <script src="js/bootstrap.bundle.js"></script>

    <style>
        .back-arrow {
            position: absolute;
            bottom: 10px; /* Ajusta la posición vertical según sea necesario */
            right: 400px; /* Ajusta la posición horizontal según sea necesario */
            z-index: 1000; /* Asegúrate de que esté por encima de otros elementos */
            background: white;
            border-radius: 40px;
        }

        .btn-back {
            display: flex;
            align-items: center;
            text-decoration: none;
            color:rgb(252, 4, 4); /* Color del texto */
            font-size: 70px; /* Tamaño de la fuente */
            transition: all 1s;            
            text-decoration: none;
            border-radius: 40px;
        }

        .btn-back:hover {
            background:rgb(252, 4, 4);
            color: white; /* Color al pasar el mouse */
            text-decoration: none;
            border-radius: 40px;
        }

        .btn-back i {
            margin-right: 5px; /* Espacio entre el icono y el texto */
            text-decoration: none;
        }
    </style>
</body>

</html>