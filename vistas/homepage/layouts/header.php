<!DOCTYPE html>
<html lang="en">
   <head>
      <!-- basic -->
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <!-- mobile metas -->
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="viewport" content="initial-scale=1, maximum-scale=1">
      <!-- site metas -->
      <title>UECFT Araure</title>
      <meta name="keywords" content="">
      <meta name="description" content="">
      <meta name="author" content="">
      <!-- bootstrap css -->
      <link rel="stylesheet" href="css/bootstrap.min.css">
      <!-- style css -->
      <link rel="stylesheet" href="css/style.css">
      <!-- Responsive-->
      <link rel="stylesheet" href="css/responsive.css">
      <!-- fevicon -->
      <link rel="icon" href="images/fermin.png"/>
      <!-- Scrollbar Custom CSS -->
      <link rel="stylesheet" href="css/jquery.mCustomScrollbar.min.css">
      <!-- Tweaks for older IEs-->
      <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/2.1.5/jquery.fancybox.min.css" media="screen">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
      <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script><![endif]-->
      <link rel="stylesheet" href="../../assets/css/solicitud_cupo.css">
      <style>
         /* Estilos generales */
         .navbar-custom {
            background-color: #c90000;; /* Rojo intenso */
            padding: 0.5rem 1rem; /* Espaciado interno del navbar */
         }

         .navbar-custom .navbar-brand,
         .navbar-custom .nav-link,
         .navbar-custom .navbar-text,
         .navbar-custom .navbar-text a {
            color: #FFFFFF !important; /* Letras blancas */
         }

         .navbar-custom .navbar-text a {
            text-decoration: underline; /* Subrayado para el enlace */
         }

         /* Estilos para el menú del header */
         .header-nav {
            flex-direction: row; /* Por defecto, en fila */
         }

         /* Ajustes para móviles */
         @media (max-width: 991.98px) {
            .navbar-brand {
               display: none; /* Ocultar "UECFT Araure" en móviles */
            }
            .navbar-text {
               font-size: 12px; /* Reducir tamaño del texto en móviles */
               margin-right: auto; /* Mover el texto a la izquierda */
               white-space: nowrap; /* Evitar que el texto se divida en varias líneas */
               padding-right: 1rem; /* Espacio entre el texto y los íconos */
            }
            .header-nav {
               flex-direction: column; /* Cambiar a columna en móviles */
               width: 100%; /* Ocupar todo el ancho */
            }
            .header-nav .nav-link {
               font-size: 14px; /* Reducir tamaño de los íconos en móviles */
               padding: 0.5rem 1rem; /* Espaciado entre opciones */
               text-align: left; /* Alinear texto a la izquierda */
            }
            .header-nav .dropdown-menu {
               position: static; /* Mostrar sub-menús en línea con el menú principal */
               float: none; /* Evitar que los sub-menús floten */
               width: 100%; /* Ocupar todo el ancho disponible */
               background-color: transparent; /* Fondo transparente */
               border: none; /* Sin bordes */
            }
            .header-nav .dropdown-item {
               padding: 0.5rem 1rem; /* Espaciado entre opciones */
               color: #FFFFFF !important; /* Letras blancas */
            }
            .header-nav .dropdown-item:hover {
               background-color: rgba(255, 255, 255, 0.1); /* Fondo claro al pasar el mouse */
            }
            .btn-login {
               background-color: #c90000; /* Fondo rojo */
               color: #FFFFFF !important; /* Texto blanco */
               font-weight: bold; /* Texto en negrita */
               border-radius: 5px; /* Bordes redondeados */
               padding: 0.5rem 1rem; /* Espaciado interno */
               text-align: center; /* Centrar texto */
               margin: 0.5rem 0; /* Margen superior e inferior */
            }

            .btn-login:hover {
               background-color: #a80000; /* Cambio de color al pasar el mouse */
            }

            .sign_btn {
               display: none; /* Ocultar el botón de Iniciar Sesión fuera del menú en móviles */
            }
         }

         /* Ajustes para pantallas grandes */
         @media (min-width: 992px) {
            .navbar-text {
               margin-right: auto; /* Mover el texto a la izquierda */
            }
            .header-nav {
               flex-direction: row; /* Mostrar íconos en línea */
               margin-left: auto; /* Mover los íconos a la derecha */
            }
            .btn-login {
               display: none; /* Ocultar el botón de Iniciar Sesión dentro del menú en desktop */
            }

            .sign_btn {
               display: block; /* Mostrar el botón de Iniciar Sesión fuera del menú en desktop */
            }
         }

         /* Estilos para el menú del footer (no cambian) */
         .navbar-custom .navbar-nav {
            flex-direction: row; /* Siempre en fila */
         }

         /* Estilos para los títulos de los programas */
        .program-title {
            font-weight: bold; /* Texto en negritas */
            text-align: center; /* Centrar el texto */
            color: #FF0000; /* Color rojo para resaltar */
            margin-bottom: 15px; /* Espacio debajo del título */
            font-size: 1.5rem; /* Tamaño de fuente más grande */
        }

        /* Estilos para los nombres de los locutores */
        .locutor {
            font-weight: bold; /* Texto en negritas */
            color: #003366; /* Color azul oscuro */
            font-size: 1.1rem; /* Tamaño de fuente ligeramente mayor */
        }

        /* Ajustes para móviles */
      @media (max-width: 991.98px) {
         .navbar-brand {
            display: none; /* Ocultar "UECFT Araure" en móviles */
         }
         .navbar-text {
            font-size: 12px; /* Reducir tamaño del texto en móviles */
            margin-right: auto; /* Mover el texto a la izquierda */
            white-space: nowrap; /* Evitar que el texto se divida en varias líneas */
            padding-right: 1rem; /* Espacio entre el texto y los íconos */
         }
         .header-nav {
            flex-direction: column; /* Cambiar a columna en móviles */
            width: 100%; /* Ocupar todo el ancho */
         }
         .header-nav .nav-link {
            font-size: 14px; /* Reducir tamaño de los íconos en móviles */
            padding: 0.5rem 1rem; /* Espaciado entre opciones */
            text-align: left; /* Alinear texto a la izquierda */
         }
         .header-nav .dropdown-menu {
            position: static; /* Mostrar sub-menús en línea con el menú principal */
            float: none; /* Evitar que los sub-menús floten */
            width: 100%; /* Ocupar todo el ancho disponible */
            background-color: transparent; /* Fondo transparente */
            border: none; /* Sin bordes */
         }
         .header-nav .dropdown-item {
            padding: 0.5rem 1rem; /* Espaciado entre opciones */
            color: #FFFFFF !important; /* Letras blancas */
         }
         .header-nav .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1); /* Fondo claro al pasar el mouse */
         }
      }
      .custom-slide-image {
          width: 100%;
          max-height: 85vh;
          background-size: cover;
          background-position: center;
          /* Imagen por defecto (escritorio) */
          background-image: url('images/fondolisto.jpg');
        }
        
        /* Para dispositivos móviles */
        @media (max-width: 768px) {
          .custom-slide-image {
            background-image: url('images/fondo_movil.png');
          }
        }
      </style>
   </head>
   <body class="main-layout">
      <?php include __DIR__ . '/../../layouts/loader.php'; ?>
      <!-- header -->
      <header>
         <!-- header inner -->
         <div class="header">
            <div class="container-fluid">
               <div class="row">
                  <div class="col-xl-3 col-lg-3 col-md-3 col-sm-3 col logo_section">
                     <div class="full">
                        <div class="center-desk">
                           <div class="logo">
                              <a href="index.php"><img src="images/fermin.jpg" alt="#" /></a>
                           </div>
                           <div class="logo2">
                              <a href="index.php">UECFT Araure</a>
                           </div>
                        </div>
                     </div>
                  </div>
                  <div class="col-xl-9 col-lg-9 col-md-9 col-sm-9">
                     <div class="header_information">
                        <nav class="navigation navbar navbar-expand-md navbar-dark ">
                           <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExample04" aria-controls="navbarsExample04" aria-expanded="false" aria-label="Toggle navigation">
                           <span class="navbar-toggler-icon"></span>
                           </button>
                           <div class="collapse navbar-collapse" id="navbarsExample04">
                              <ul class="navbar-nav mr-auto">
                                 <li class="nav-item">
                                    <a class="nav-link" href="about.php">¿Quiénes Somos?</a>
                                 </li> 
                                 <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownNiveles" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Niveles E.</a>
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdownNiveles">
                                       <a class="dropdown-item" href="#">Preescolar</a>
                                       <a class="dropdown-item" href="#">Primaria</a>
                                       <a class="dropdown-item" href="#">Media General</a>
                                    </div>
                                 </li> 
                                 <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMultimedia" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Multimedia</a>
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdownMultimedia">
                                       <a class="dropdown-item" href="nuestravoz.php">Nuestra Voz</a>
                                    </div>
                                 </li>
                                 <li class="nav-item">
                                    <a class="nav-link" href="solicitud_cupo.php">Solicitud de Cupo</a>
                                 </li>
                                 <!-- Botón de Iniciar Sesión como elemento del menú en móviles -->
                                 <li class="nav-item d-block d-md-none">
                                    <a class="nav-link btn-login" href="../login/login.php">Iniciar Sesión</a>
                                 </li>
                              </ul>
                              <!-- Botón de Iniciar Sesión fuera del menú en desktop -->
                              <div class="sign_btn d-none d-md-block"><a href="../login/login.php">Iniciar Sesión</a></div>
                           </div>
                        </nav>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </header>
      <!-- end header inner -->
      <!-- end header -->
