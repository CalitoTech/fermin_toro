<?php
require_once __DIR__ . '/../config/conexion.php';
$database = new Database();
$conexion = $database->getConnection();

$q = $_GET['q'] ?? '';

$stmt = $conexion->prepare("
    SELECT DISTINCT p.IdPersona, p.nombre, p.apellido, p.cedula, n.nacionalidad
    FROM persona p
    INNER JOIN detalle_perfil dp ON p.IdPersona = dp.IdPersona
    INNER JOIN perfil pr ON dp.IdPerfil = pr.IdPerfil
    LEFT JOIN nacionalidad n ON p.IdNacionalidad = n.IdNacionalidad
    WHERE pr.nombre_perfil = 'Estudiante'
    AND p.IdPersona NOT IN (SELECT IdPersona FROM egreso)
    AND (p.nombre LIKE :q OR p.apellido LIKE :q OR p.cedula LIKE :q)
    ORDER BY p.apellido, p.nombre
    LIMIT 10
");
$search = "%$q%";
$stmt->bindParam(':q', $search);
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
