CREATE DATABASE fermin;
USE fermin;

CREATE TABLE perfil (
    IdPerfil int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre_perfil varchar(50) NOT NULL
);

INSERT INTO `perfil` (`IdPerfil`, `nombre_perfil`) VALUES
(1, 'Administrador'),
(2, 'Docente'),
(3, 'Estudiante'),
(4, 'Representante'),
(5, 'Director'),
(6, 'Control de estudios'),
(7, 'Coordinador Inicial'),
(8, 'Coordinador Primaria'),
(9, 'Coordinador Media General');

CREATE TABLE tabla (
    IdTabla int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre_tabla varchar(50) NOT NULL
);

CREATE TABLE permiso (
    IdPermiso int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    permiso_leer BOOLEAN NOT NULL DEFAULT FALSE,
    permiso_guardar BOOLEAN NOT NULL DEFAULT FALSE,
    permiso_modificar BOOLEAN NOT NULL DEFAULT FALSE,
    permiso_eliminar BOOLEAN NOT NULL DEFAULT FALSE,
    IdTabla int NOT NULL,
    IdPerfil int NOT NULL,
    FOREIGN KEY (IdTabla) REFERENCES tabla(IdTabla),
    FOREIGN KEY (IdPerfil) REFERENCES perfil(IdPerfil)
);

CREATE TABLE nivel (
    IdNivel int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nivel varchar(50) NOT NULL
);

INSERT INTO `nivel` (`IdNivel`, `nivel`) VALUES
(1, 'Inicial'), (2, 'Primaria'), (3, 'Media General');

CREATE TABLE requisito (
    IdRequisito int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    requisito varchar(50) NOT NULL,
    obligatorio BOOLEAN NOT NULL DEFAULT FALSE,
    IdNivel int NOT NULL,
    FOREIGN KEY (IdNivel) REFERENCES nivel(IdNivel)
);

CREATE TABLE curso (
    IdCurso int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    curso varchar(50) NOT NULL,
    IdNivel int NOT NULL,
    FOREIGN KEY (IdNivel) REFERENCES nivel(IdNivel)
);

INSERT INTO `curso` (`IdCurso`, `curso`, `IdNivel`) VALUES
(1, '1er Nivel', 1),
(2, '2do Nivel', 1),
(3, '3er Nivel', 1),
(4, '1er Grado', 2),
(5, '2do Grado', 2),
(6, '3er Grado', 2),
(7, '4to Grado', 2),
(8, '5to Grado', 2),
(9, '6to Grado', 2),
(10, '1er Año', 3),
(11, '2do Año', 3),
(12, '3er Año', 3),
(13, '4to Año', 3),
(14, '5to Año', 3);

CREATE TABLE seccion (
    IdSeccion int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    seccion varchar(50) NOT NULL
);

INSERT INTO `seccion` (`IdSeccion`, `seccion`) VALUES
(1, 'A'), (2, 'B'), (3, 'C');

CREATE TABLE aula (
    IdAula int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    capacidad int NULL,
    IdCurso int NULL,
    IdSeccion int NULL,
    FOREIGN KEY (IdCurso) REFERENCES curso(IdCurso),
    FOREIGN KEY (IdSeccion) REFERENCES seccion(IdSeccion)
);

INSERT INTO `aula` (`IdAula`, `capacidad`, `IdCurso`, `IdSeccion`) VALUES
(1, 30, 1, 1),
(2, 30, 2, 1),
(3, 30, 3, 1),
(4, 30, 4, 1),
(5, 30, 5, 1),
(6, 30, 6, 1),
(7, 30, 7, 1),
(8, 30, 8, 1),
(9, 30, 9, 1),
(10, 30, 10, 1),
(11, 30, 11, 1),
(12, 30, 12, 1),
(13, 30, 13, 1),
(14, 30, 14, 1),
(15, 30, 1, 2),
(16, 30, 2, 2),
(17, 30, 3, 2),
(18, 30, 4, 2),
(19, 30, 5, 2),
(20, 30, 6, 2),
(21, 30, 7, 2),
(22, 30, 8, 2),
(23, 30, 9, 2),
(24, 30, 10, 2),
(25, 30, 11, 2),
(26, 30, 12, 2),
(27, 30, 13, 2),
(28, 30, 14, 2),
(29, 30, 1, 3),
(30, 30, 2, 3),
(31, 30, 3, 3),
(32, 30, 4, 3),
(33, 30, 5, 3),
(34, 30, 6, 3),
(35, 30, 7, 3),
(36, 30, 8, 3),
(37, 30, 9, 3),
(38, 30, 10, 3),
(39, 30, 11, 3),
(40, 30, 12, 3),
(41, 30, 13, 3),
(42, 30, 14, 3);

CREATE TABLE sexo (
    IdSexo int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sexo varchar(50) NOT NULL
);

INSERT INTO `sexo` (`IdSexo`, `sexo`) VALUES
(1, 'Masculino'), (2, 'Femenino');


CREATE TABLE urbanismo (
    IdUrbanismo int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    urbanismo varchar(50) NOT NULL
);

INSERT INTO `urbanismo` (`IdUrbanismo`, `urbanismo`) VALUES
(1, 'Villas del Pilar'), (2, 'Llano Alto');

CREATE TABLE nacionalidad (
    IdNacionalidad int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nacionalidad varchar(50) NOT NULL
);

INSERT INTO `nacionalidad` (`IdNacionalidad`, `nacionalidad`) VALUES
(1, 'V'), (2, 'E');


CREATE TABLE condicion (
    IdCondicion int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    condicion varchar(50) NOT NULL
);

INSERT INTO `condicion` (`IdCondicion`, `condicion`) VALUES
(1, 'Activo'),
(2, 'Inactivo'),
(3, 'Jubilado'),
(4, 'Reposo'),
(5, 'Vacaciones'),
(6, 'Graduado');

CREATE TABLE estado_inscripcion (
    IdEstado_Inscripcion int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    estado_inscripcion varchar(50) NOT NULL
);

INSERT INTO `estado_inscripcion` (`IdEstado_Inscripcion`, `estado_inscripcion`) VALUES
(1, 'Pendiente de aprobación'),
(2, 'Aprobada para reunión'),
(3, 'Rechazada'),
(4, 'En espera de pago'),
(5, 'Inscrito');

CREATE TABLE parentesco (
    IdParentesco int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    parentesco varchar(50) NOT NULL
);

INSERT INTO `parentesco` (`IdParentesco`, `parentesco`) VALUES
(1, 'Padre'),
(2, 'Madre'),
(3, 'Representante Legal'),
(4, 'Tutor'),
(5, 'Hermano'),
(6, 'Abuelo'),
(7, 'Tío');

CREATE TABLE persona (
    IdPersona int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdNacionalidad int NULL,
    cedula int NULL,
    nombre varchar(50) NOT NULL,
    apellido varchar(50) NOT NULL,
    fecha_nacimiento date NULL,
    fecha_egreso date NULL,
    correo varchar(50) NULL,
    usuario varchar(50) NULL,
    password varchar(1000) NULL,
    direccion varchar(555) NULL,
    lugar_trabajo varchar(50) NULL,
    IdSexo int NULL,
    IdUrbanismo int NULL,
    IdCondicion int NULL,
    IdAula int NULL,
    FOREIGN KEY (IdNacionalidad) REFERENCES nacionalidad(IdNacionalidad),
    FOREIGN KEY (IdUrbanismo) REFERENCES urbanismo(IdUrbanismo),
    FOREIGN KEY (IdCondicion) REFERENCES condicion(IdCondicion),
    FOREIGN KEY (IdSexo) REFERENCES sexo(IdSexo),
    FOREIGN KEY (IdAula) REFERENCES aula(IdAula)
);

INSERT INTO `persona` (`IdPersona`, `IdNacionalidad`, `cedula`, `nombre`, `apellido`, `fecha_nacimiento`, `fecha_egreso`, `correo`, `usuario`, `password`, `direccion`, `lugar_trabajo`, `IdSexo`, `IdUrbanismo`, `IdCondicion`, `IdAula`) VALUES
(1, 1, 30588094, 'Carlos', 'Navas', '2004-10-26', NULL, 'carlosdanielnavas26@gmail.com', 'carlos', '$2y$10$DeA8v8DgHihCe2aKBW4qZuwtITen6EM5W4OdQKoZoQHqWsBCuOM/2', 'Av. Sucre, Calle 3, Casa #152', 'CorpoEureka', 1, 1, NULL, NULL);

CREATE TABLE fecha_escolar (
    IdFecha_Escolar int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fecha_escolar varchar(50) NOT NULL,
    activa BOOLEAN NOT NULL DEFAULT FALSE
);

INSERT INTO `fecha_escolar` (`IdFecha_Escolar`, `fecha_escolar`, `activa`) VALUES
(1, '2023-2024', False),
(2, '2024-2025', False),
(3, '2025-2026', True);

CREATE TABLE historial_aula (
    IdHistorial_Aula int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdPersona int NOT NULL,
    IdAula int NOT NULL,
    IdFecha_Escolar int NOT NULL,
    fecha_ingreso date NOT NULL,
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona),
    FOREIGN KEY (IdAula) REFERENCES aula(IdAula),
    FOREIGN KEY (IdFecha_Escolar) REFERENCES fecha_escolar(IdFecha_Escolar)
);

CREATE TABLE dificultad (
    IdDificultad int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    visual BOOLEAN NOT NULL DEFAULT FALSE,
    auditiva BOOLEAN NOT NULL DEFAULT FALSE,
    motora BOOLEAN NOT NULL DEFAULT FALSE,
    es_alergico BOOLEAN NOT NULL DEFAULT FALSE,
    alergia varchar(50) DEFAULT NULL,
    tiene_enfermedad BOOLEAN NOT NULL DEFAULT FALSE,
    enfermedad varchar(50) DEFAULT NULL,
    IdPersona int NOT NULL,
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona)
);

CREATE TABLE representante (
    IdRepresentante int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdPersona int NOT NULL,
    IdParentesco int NOT NULL,
    IdEstudiante int NOT NULL,
    ocupacion varchar(50) DEFAULT NULL,
    FOREIGN KEY (IdParentesco) REFERENCES parentesco(IdParentesco),
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona),
    FOREIGN KEY (IdEstudiante) REFERENCES persona(IdPersona)
);

CREATE TABLE inscripcion (
    IdInscripcion int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    codigo_inscripcion varchar(20) NOT NULL,
    IdEstudiante int NOT NULL,
    fecha_inscripcion datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_plantel varchar(100) DEFAULT NULL,
    nro_hermanos int DEFAULT 0,
    responsable_inscripcion int NOT NULL,
    IdFecha_Escolar int NOT NULL,
    IdEstado_Inscripcion int NOT NULL,
    IdCurso int NOT NULL,
    FOREIGN KEY (IdCurso) REFERENCES curso(IdCurso),
    FOREIGN KEY (IdEstado_Inscripcion) REFERENCES estado_inscripcion(IdEstado_Inscripcion),
    FOREIGN KEY (IdFecha_Escolar) REFERENCES fecha_escolar(IdFecha_Escolar),
    FOREIGN KEY (IdEstudiante) REFERENCES persona(IdPersona),
    FOREIGN KEY (responsable_inscripcion) REFERENCES representante(IdRepresentante)
);

CREATE TABLE inscripcion_requisito (
    IdInscripcionRequisito int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdInscripcion int NOT NULL,
    IdRequisito int NOT NULL,
    cumplido BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (IdInscripcion) REFERENCES inscripcion(IdInscripcion),
    FOREIGN KEY (IdRequisito) REFERENCES requisito(IdRequisito)
);

CREATE TABLE detalle_perfil (
    IdDetalle_Perfil int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdPerfil int NOT NULL,
    IdPersona int NOT NULL,
    FOREIGN KEY (IdPerfil) REFERENCES perfil(IdPerfil),
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona)
);

INSERT INTO `detalle_perfil` (`IdDetalle_Perfil`, `IdPerfil`, `IdPersona`) VALUES
(1, 1, 1);

CREATE TABLE tipo_telefono (
    IdTipo_Telefono int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_telefono varchar(50) NOT NULL
);

INSERT INTO `tipo_telefono` (`IdTipo_Telefono`, `tipo_telefono`) VALUES
(1, 'Habitación'), (2, 'Celular'), (3, 'Trabajo');

CREATE TABLE telefono (
    IdTelefono int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdTipo_Telefono int NOT NULL,
    numero_telefono varchar(50) NOT NULL,
    IdPersona int NOT NULL,
    FOREIGN KEY (IdTipo_Telefono) REFERENCES tipo_telefono(IdTipo_Telefono),
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona)
);

CREATE TABLE materia (
    IdMateria int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    materia varchar(50) NOT NULL,
    IdNivel int NOT NULL,
    FOREIGN KEY (IdNivel) REFERENCES nivel(IdNivel)
);

CREATE TABLE dia (
    IdDia int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    dia varchar(50) NOT NULL
);

INSERT INTO `dia` (`IdDia`, `dia`) VALUES
(1, 'Lunes'), (2, 'Martes'), (3, 'Miércoles'), (4, 'Jueves'), (5, 'Viernes'), (6, 'Sábado'), (7, 'Domingo');

CREATE TABLE bloque (
    IdBloque int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    hora_inicio time NOT NULL,
    hora_fin time NOT NULL
);

CREATE TABLE horario (
    IdHorario int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdMateria int NOT NULL,
    IdBloque int NOT NULL,
    IdDia int NOT NULL,
    IdPersona int NOT NULL,
    IdAula int NOT NULL,
    IdFecha_Escolar int NOT NULL,
    FOREIGN KEY (IdFecha_Escolar) REFERENCES fecha_escolar(IdFecha_Escolar),
    FOREIGN KEY (IdDia) REFERENCES dia(IdDia),
    FOREIGN KEY (IdBloque) REFERENCES bloque(IdBloque),
    FOREIGN KEY (IdMateria) REFERENCES materia(IdMateria),
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona),
    FOREIGN KEY (IdAula) REFERENCES aula(IdAula)
);

CREATE TABLE tipo_grupo_creacion (
    IdTipoGrupo int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre_grupo varchar(50) NOT NULL,
    descripcion varchar(255) DEFAULT NULL,
    capacidad_maxima int NOT NULL,
    inscripcion_activa BOOLEAN NOT NULL DEFAULT FALSE,
    IdNivel int NOT NULL,
    FOREIGN KEY (IdNivel) REFERENCES nivel(IdNivel)
);

CREATE TABLE grupo_creacion (
    IdGrupo_Creacion int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdTipoGrupo int NOT NULL,
    IdProfesor int NOT NULL,
    IdAula int NOT NULL,
    IdFecha_Escolar int NOT NULL,
    cupos_disponibles int NOT NULL,
    FOREIGN KEY (IdTipoGrupo) REFERENCES tipo_grupo_creacion(IdTipoGrupo),
    FOREIGN KEY (IdProfesor) REFERENCES persona(IdPersona),
    FOREIGN KEY (IdAula) REFERENCES aula(IdAula),
    FOREIGN KEY (IdFecha_Escolar) REFERENCES fecha_escolar(IdFecha_Escolar)
);

CREATE TABLE inscripcion_grupo_creacion (
    IdInscripcion_Grupo int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdGrupo_Creacion int NOT NULL,
    IdEstudiante int NOT NULL,
    fecha_ingreso_grupo datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IdGrupo_Creacion) REFERENCES grupo_creacion(IdGrupo_Creacion),
    FOREIGN KEY (IdEstudiante) REFERENCES persona(IdPersona)
);