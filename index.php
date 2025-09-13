<?php
session_start();
if (empty($_SESSION['user']) and empty($_SESSION['clave'])) {
    header('location:./vistas/homepage/index.php');
}else{

header('location:./vistas/inicio/inicio.php');
}
?>