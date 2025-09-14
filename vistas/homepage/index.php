<?php include 'layouts/header.php'; ?>
      <!-- banner -->
      <div id="myCarousel" class="carousel slide banner_main" data-ride="carousel">
         <ol class="carousel-indicators">
            <li data-target="#myCarousel" data-slide-to="0" class="active"></li>
            <li data-target="#myCarousel" data-slide-to="1"></li>
            <li data-target="#myCarousel" data-slide-to="2"></li>
            <li data-target="#myCarousel" data-slide-to="3"></li>
            <li data-target="#myCarousel" data-slide-to="4"></li>
         </ol>
         <div class="carousel-inner">
            <div class="carousel-item active">
               <!-- Usamos la etiqueta picture para cambiar imágenes según el tamaño -->
               <picture>
                  <source media="(max-width: 768px)" srcset="images/fondo_movil.png">
                  <img class="first-slide" src="images/fondolisto.jpg" alt="First slide" style="max-height: 85vh; width: 100%;">
               </picture>
               <div class="container">
                  <div class="carousel-caption relative">
                    
                  </div>
               </div>
            </div>
            <div class="carousel-item">
               <img class="second-slide" src="images/banner.jpg" alt="First slide" style="max-height: 85vh;">
               <div class="container">
                  <div class="carousel-caption relative">
                     <h1><span>Inscripción</span> Online</h1>
                     <a href="solicitud_cupo.php">Solicita tu Cupo</a>
                  </div>
               </div>
            </div>
            <div class="carousel-item">
               <img class="third-slide" src="images/noticia3.jpg" alt="Second slide" style="max-height: 85vh;">
               <div class="container">
                  <div class="carousel-caption relative">
                     <h1><span>Educando el</span> Futuro</h1>
                     <a href="about.php">Conócenos</a>
                  </div>
               </div>
            </div>
            <div class="carousel-item">
               <img class="third-slide" src="images/redes3.jpg" alt="Third slide" style="max-height: 85vh;">
               <div class="container">
                  <div class="carousel-caption relative">
                     <h1><span>Redes</span> Sociales</h1>
                     <a href="#redes">Síguenos</a>
                  </div>
               </div>
            </div>
            <div class="carousel-item">
               <img class="third-slide" src="images/radio2.png" alt="four slide" style="max-height: 85vh;">
               <div class="container">
                  <div class="carousel-caption relative">
                     <br>
                     <br>
                     <br><br><br><br>
                     <a href="nuestravoz.php">Escuchála</a>
                  </div>
               </div>
            </div>
         </div>
         <a class="carousel-control-prev" href="#myCarousel" role="button" data-slide="prev">
         <span class="carousel-control-prev-icon" aria-hidden="true"></span>
         <span class="sr-only">Previous</span>
         </a>
         <a class="carousel-control-next" href="#myCarousel" role="button" data-slide="next">
         <span class="carousel-control-next-icon" aria-hidden="true"></span>
         <span class="sr-only">Next</span>
         </a>
      </div>
      <!-- end banner -->
      <!-- about -->
      <div id="about"  class="about">
         <div class="container">
            <div class="row d_flex">
               <div class="col-md-7">
                  <div class="titlepage">
                     <h2 style="text-align: justify;">¡Convivencia Escolar!</h2>
                     <span></span>
                     <p style="text-align: justify;">Es imprescindible que los alumnos asistan y permanezcan correctamente vestidos y aseados en la Institución, ya que la presentación personal constituye un aspecto relevante dentro de la formación integral que se inculca en la UECFT Araure.</p>
                     <a class="read_more" href="#">Normas de Convivencia <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                  </div>
               </div>
               <div class="col-md-5">
                  <div class="about_img">
                     <figure><img src="images/convivencia2.png" alt="#"/></figure>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- end about -->
      <!-- mobile -->
      <div id="mobile"  class="mobile">
         <div class="container">
            <div class="row d_flex">
               <div class="col-md-5">
                  <div class="mobile_img">
                     <figure><img src="images/caja2.png" alt="#"/></figure>
                  </div>
               </div>
               <div class="col-md-7">
                  <div class="titlepage">
                     <h2 style="text-align: justify;">Horario de caja</h2>
                     <span></span>
                     <p style="text-align: justify;">La Administración de la U.E.C. FERMÍN TORO, informa a los padres, madres, representantes y/o responsables que el proceso de pago es presencial en las instalaciones del colegio en horario comprendido entre las 7:30 am y las 1:30 pm.</p>
                     <p style="text-align: justify;">CUENTAS BANCARIAS <br>
                        Banco Mercantil: Cta.Cte. Nº 0105-0749-90-1749053373. <br>
                        Banco Provincial: Cta.Cte. Nº 0108-0906-11-0100015884. <br>
                        A nombre: ASOC. CIVIL UNIDAD EDUCATIVA FERMIN TORO. <br>
                        RIF. J-299441341.</p>
                     <a class="read_more" href="mailto:fermin.toro.araure@gmail.com">Contáctanos <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div id="about"  class="about">
         <div class="container">
            <div class="row d_flex">
               <div class="col-md-7">
                  <div class="titlepage">
                     <h2>Robótica Educativa: Alianza Kurios</h2>
                     <span></span>
                     <p style="text-align: justify;">Nos encontramos muy contentos de haber iniciado una alianza con kurios academy a partir de enero 2025. Una estrategia que da crecimiento en el área de Robótica en todos los niveles educativos y enriquece el futuro de nuestros estudiantes. El propósito es incentivar la curiosidad y enseñar, a nuestros estudiantes, las habilidades fundamentales para el futuro.</p>
                  </div>
               </div>
               <div class="col-md-5">
                  <div class="about_img">
                     <figure><img src="images/robotica.png" alt="#"/></figure>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- end mobile -->
      <!-- clients -->
      <!--<div class="clients">
         <div class="container">
            <div class="row">
               <div class="col-md-6 offset-md-3">
                  <div class="titlepage">
                     <h2>What is Say clients</h2>
                     <span></span>
                  </div>
               </div>
            </div>
            <div class="row">
               <div class="col-md-12">
                  <div class="clients_box">
                     <p>There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text.</p>
                  </div>
                  <div class="jonu">
                     <img src="images/cross_img.png" alt="#"/>
                     <h3>Jone due</h3>
                     <strong>(sure there isn't)</strong>
                     <a class="read_more" href="#">Get A Quote</a>
                  </div>
               </div>
            </div>
         </div>
      </div>
       end clients -->
<?php include 'layouts/footer.php'; ?>