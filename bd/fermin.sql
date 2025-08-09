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
(5, 'Contacto de Emergencia'),
(6, 'Director'),
(7, 'Control de estudios'),
(8, 'Coordinador Inicial'),
(9, 'Coordinador Primaria'),
(10, 'Coordinador Media General');

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

CREATE TABLE aula (
    IdAula int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdNivel int NOT NULL,
    aula varchar(50) NOT NULL,
    capacidad int NOT NULL,
    FOREIGN KEY (IdNivel) REFERENCES nivel(IdNivel)
);

INSERT INTO `aula` (`IdAula`, `IdNivel`, `aula`, `capacidad`) VALUES
(1, 1, 'Aula 1', 35), 
(2, 1, 'Aula 2', 35), 
(3, 1, 'Aula 3', 35),
(4, 1, 'Aula 4', 35), 
(5, 1, 'Aula 5', 35), 
(6, 1, 'Aula 6', 35), 
(7, 1, 'Aula 7', 35), 
(8, 1, 'Aula 8', 35), 
(9, 1, 'Aula 9', 35),
(10, 1, 'Aula 10', 35);

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
(1, 'A'), (2, 'B'), (3, 'C'), (4, 'Inscripcion');

CREATE TABLE curso_seccion (
    IdCurso_Seccion int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    cantidad_estudiantes int NOT NULL,
    IdCurso int NOT NULL,
    IdSeccion int NOT NULL,
    IdAula int NOT NULL,
    FOREIGN KEY (IdAula) REFERENCES aula(IdAula),
    FOREIGN KEY (IdCurso) REFERENCES curso(IdCurso),
    FOREIGN KEY (IdSeccion) REFERENCES seccion(IdSeccion)
);

INSERT INTO `curso_seccion` (`IdCurso_Seccion`, `cantidad_estudiantes`, `IdCurso`, `IdSeccion`, `IdAula`) VALUES
(1, 0, 1, 1),
(2, 0, 2, 1),
(3, 0, 3, 1),
(4, 0, 4, 1),
(5, 0, 5, 1),
(6, 0, 6, 1),
(7, 0, 7, 1),
(8, 0, 8, 1),
(9, 0, 9, 1),
(10, 0, 10, 1),
(11, 0, 11, 1),
(12, 0, 12, 1),
(13, 0, 13, 1),
(14, 0, 14, 1),
(15, 0, 1, 2),
(16, 0, 2, 2),
(17, 0, 3, 2),
(18, 0, 4, 2),
(19, 0, 5, 2),
(20, 0, 6, 2),
(21, 0, 7, 2),
(22, 0, 8, 2),
(23, 0, 9, 2),
(24, 0, 10, 2),
(25, 0, 11, 2),
(26, 0, 12, 2),
(27, 0, 13, 2),
(28, 0, 14, 2),
(29, 0, 1, 3),
(30, 0, 2, 3),
(31, 0, 3, 3),
(32, 0, 4, 3),
(33, 0, 5, 3),
(34, 0, 6, 3),
(35, 0, 7, 3),
(36, 0, 8, 3),
(37, 0, 9, 3),
(38, 0, 10, 3),
(39, 0, 11, 3),
(40, 0, 12, 3),
(41, 0, 13, 3),
(42, 0, 14, 3),
(43, 0, 1, 4),
(44, 0, 2, 4),
(45, 0, 3, 4),
(46, 0, 4, 4),
(47, 0, 5, 4),
(48, 0, 6, 4),
(49, 0, 7, 4),
(50, 0, 8, 4),
(51, 0, 9, 4),
(52, 0, 10, 4),
(53, 0, 11, 4),
(54, 0, 12, 4),
(55, 0, 13, 4),
(56, 0, 14, 4);

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

CREATE TABLE tipo_status (
    IdTipo_Status int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_status varchar(50) NOT NULL
);

INSERT INTO `tipo_status` (`IdTipo_Status`, `tipo_status`) VALUES
(1, 'Persona'), (2, 'Inscripción');

CREATE TABLE status (
    IdStatus int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdTipo_Status int NOT NULL,
    status varchar(50) NOT NULL,
    FOREIGN KEY (IdTipo_Status) REFERENCES tipo_status(IdTipo_Status)
);

INSERT INTO `status` (`IdStatus`, `IdTipo_Status`, `status`) VALUES
(1, 1, 'Activo'),
(2, 1, 'Inactivo'),
(3, 1, 'Jubilado'),
(4, 1, 'Reposo'),
(5, 1, 'Vacaciones'),
(6, 1, 'Graduado'),
(7, 2, 'Pendiente de aprobación'),
(8, 2, 'Aprobada para reunión'),
(9, 2, 'Rechazada'),
(10, 2, 'En espera de pago'),
(11, 2, 'Inscrito');

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
    correo varchar(50) NULL,
    usuario varchar(50) NULL,
    password varchar(1000) NULL,
    direccion varchar(555) NULL,
    IdSexo int NULL,
    IdUrbanismo int NULL,
    IdStatus int NULL,
    FOREIGN KEY (IdNacionalidad) REFERENCES nacionalidad(IdNacionalidad),
    FOREIGN KEY (IdUrbanismo) REFERENCES urbanismo(IdUrbanismo),
    FOREIGN KEY (IdStatus) REFERENCES status(IdStatus),
    FOREIGN KEY (IdSexo) REFERENCES sexo(IdSexo)
);

INSERT INTO `persona` (`IdPersona`, `IdNacionalidad`, `cedula`, `nombre`, `apellido`, `fecha_nacimiento`, `fecha_egreso`, `correo`, `usuario`, `password`, `direccion`, `lugar_trabajo`, `IdSexo`, `IdUrbanismo`, `IdStatus`, `IdCurso_Seccion`) VALUES
(1, 1, 30588094, 'Carlos', 'Navas', '2004-10-26', NULL, 'carlosdanielnavas26@gmail.com', 'carlos', '$2y$10$DeA8v8DgHihCe2aKBW4qZuwtITen6EM5W4OdQKoZoQHqWsBCuOM/2', 'Av. Sucre, Calle 3, Casa #152', 'CorpoEureka', 1, 1, NULL, NULL);

CREATE TABLE egreso (
    IdEgreso int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fecha_egreso date NOT NULL,
    motivo varchar(255) DEFAULT NULL,
    IdPersona int NOT NULL,
    IdStatus int NOT NULL,
    FOREIGN KEY (IdStatus) REFERENCES status(IdStatus),
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona)
);

CREATE TABLE fecha_escolar (
    IdFecha_Escolar int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    fecha_escolar varchar(50) NOT NULL,
    activa BOOLEAN NOT NULL DEFAULT FALSE
);

INSERT INTO `fecha_escolar` (`IdFecha_Escolar`, `fecha_escolar`, `activa`) VALUES
(1, '2023-2024', False),
(2, '2024-2025', False),
(3, '2025-2026', True);

CREATE TABLE tipo_discapacidad (
    IdTipo_Discapacidad int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_discapacidad varchar(50) NOT NULL
);

CREATE TABLE discapacidad (
    IdDiscapacidad int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdPersona int NOT NULL,
    IdTipo_Discapacidad int NOT NULL,
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona),
    FOREIGN KEY (IdTipo_Discapacidad) REFERENCES tipo_discapacidad(IdTipo_Discapacidad)
);

CREATE TABLE representante (
    IdRepresentante int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdPersona int NOT NULL,
    IdParentesco int NOT NULL,
    IdEstudiante int NOT NULL,
    ocupacion varchar(50) DEFAULT NULL,
    lugar_trabajo varchar(50) NULL,
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
    IdStatus int NOT NULL,
    IdCurso_Seccion int NOT NULL,
    FOREIGN KEY (IdCurso_Seccion) REFERENCES curso_seccion(IdCurso_Seccion),
    FOREIGN KEY (IdStatus) REFERENCES status(IdStatus),
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
    IdCurso_Seccion int NOT NULL,
    IdFecha_Escolar int NOT NULL,
    FOREIGN KEY (IdFecha_Escolar) REFERENCES fecha_escolar(IdFecha_Escolar),
    FOREIGN KEY (IdDia) REFERENCES dia(IdDia),
    FOREIGN KEY (IdBloque) REFERENCES bloque(IdBloque),
    FOREIGN KEY (IdMateria) REFERENCES materia(IdMateria),
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona),
    FOREIGN KEY (IdCurso_Seccion) REFERENCES curso_seccion(IdCurso_Seccion)
);

CREATE TABLE tipo_grupo_creacion (
    IdTipo_Grupo int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre_grupo varchar(50) NOT NULL,
    descripcion varchar(255) DEFAULT NULL,
    capacidad_maxima int NOT NULL,
    inscripcion_activa BOOLEAN NOT NULL DEFAULT FALSE,
    IdNivel int NOT NULL,
    FOREIGN KEY (IdNivel) REFERENCES nivel(IdNivel)
);

CREATE TABLE grupo_creacion (
    IdGrupo_Creacion int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdTipo_Grupo int NOT NULL,
    IdProfesor int NOT NULL,
    IdCurso_Seccion int NOT NULL,
    IdFecha_Escolar int NOT NULL,
    cupos_disponibles int NOT NULL,
    FOREIGN KEY (IdTipo_Grupo) REFERENCES tipo_grupo_creacion(IdTipo_Grupo),
    FOREIGN KEY (IdProfesor) REFERENCES persona(IdPersona),
    FOREIGN KEY (IdCurso_Seccion) REFERENCES curso_seccion(IdCurso_Seccion),
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