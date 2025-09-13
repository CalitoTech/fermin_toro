
<?php include 'layouts/header.php'; ?>
<body class="main-layout">
   <!-- main content -->
   <main id="main">
      <section class="h-100" style="margin-top: 100px;">
         <div class="container h-100">
            <div class="row justify-content-sm-center h-100">
               <div class="col-xxl-4 col-xl-5 col-lg-5 col-md-7 col-sm-9">
                  <div class="card shadow-lg">
                     <!-- Contenedor de la imagen con texto superpuesto -->
                     <div class="card-img-top position-relative">
                        <img src="images/radio2.png" alt="Nuestra Voz Radio" class="img-fluid">
                     </div>
                     <!-- Reproductor de audio -->
                     <div class="card-footer py-3 border-0">
                        <div class="text-center">
                           <audio id="stream" controls preload="none" style="width: 100%;">
                              <source src="https://guri.tepuyserver.net/8070/stream" type="audio/mpeg">
                           </audio>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <br>
      <br>
      <section class="wrapper">
         <div class="container-fostrap">
            <div class="titlepage">
               <h2 style="text-align: center;">Programación</h2>
               <span style="margin-left: 50%"></span>
            </div>
            <div class="content">
               <div class="container">
                  <div class="row">
                     <!---------------- PROGRAMA 1 ---------------------------->
                     <div class="col-xs-12 col-sm-4">
                        <div class="card">
                           <a class="img-card" href="#">
                              <img src="images/es_musica.jpg" alt="Es Música" class="img-fluid">
                           </a>
                           <div class="card-content" style="padding: 10px; text-align: justify;">
                              <h4 class="card-title program-title">Es Música</h4>
                              <p class="">Especial de 30 minutos dedicado a tu artista o agrupación favorita.</p>
                              <p style="text-align: center;"><strong class="locutor">Estefanía Rivero</strong>.</p>
                           </div>
                           <div class="card-read-more">
                              <a href="#" target="_blank" class="btn btn-link btn-block">31/01/2024</a>
                              <a href="#" target="_blank" class="btn btn-link btn-block">07/02/2024</a>
                           </div>
                        </div>
                     </div>
                     <!---------------- PROGRAMA 2 ---------------------------->
                     <div class="col-xs-12 col-sm-4">
                        <div class="card">
                           <a class="img-card" href="#">
                              <img src="images/la_otra_cara.jpg" alt="La Otra Cara" class="img-fluid">
                           </a>
                           <div class="card-content" style="padding: 10px; text-align: justify;">
                              <h4 class="card-title program-title">La Otra Cara</h4>
                              <p class="">Programa que muestra la cara desconocida de los miembros de la familia fermintoriana.</p>
                              <p style="text-align: center;"><strong class="locutor">Salomón Rangel</strong>.</p>
                           </div>
                           <div class="card-read-more">
                              <a href="#" target="_blank" class="btn btn-link btn-block">03/02/2024</a>
                              <a href="#" target="_blank" class="btn btn-link btn-block">10/02/2024</a>
                           </div>
                        </div>
                     </div>
                     <!---------------- PROGRAMA 3 ---------------------------->
                     <div class="col-xs-12 col-sm-4">
                        <div class="card">
                           <a class="img-card" href="#">
                              <img src="images/nuestra_psique.webp" alt="Nuestra Psique" class="img-fluid">
                           </a>
                           <div class="card-content" style="padding: 10px; text-align: justify;">
                              <h4 class="card-title program-title">Nuestra Psique</h4>
                              <p class="">Programa dedicado a abordar los temas relacionados con el bienestar mental.</p>
                              <p style="text-align: center;"><strong class="locutor">Anderson Bastidas y Alicia Berbesí</strong>.</p>
                           </div>
                           <div class="card-read-more">
                              <a href="https://www.youtube.com/watch?v=W5zpin6hHRY" target="_blank" class="btn btn-link btn-block">12/01/2024</a>
                              <a href="https://www.youtube.com/watch?v=eRUDdzxElEw" target="_blank" class="btn btn-link btn-block">19/01/2024</a>
                              <a href="https://www.youtube.com/watch?v=m755dLgQEkg" target="_blank" class="btn btn-link btn-block">26/01/2024</a>
                           </div>
                        </div>
                     </div>
                     <!------------------------------------------->
                  </div>
               </div>
            </div>
         </div>
      </section>
   </main>
   <!-- end main content -->

   <?php include 'layouts/footer.php'; ?>