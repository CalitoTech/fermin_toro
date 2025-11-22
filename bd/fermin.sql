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

CREATE TABLE nivel (
    IdNivel int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nivel varchar(50) NOT NULL
);

INSERT INTO `nivel` (`IdNivel`, `nivel`) VALUES
(1, 'Inicial'), (2, 'Primaria'), (3, 'Media General');

CREATE TABLE tipo_trabajador (
    IdTipoTrabajador int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_trabajador varchar(50) NOT NULL
);

INSERT INTO `tipo_trabajador` (`IdTipoTrabajador`, `tipo_trabajador`) VALUES
(1, 'Sin actividad laboral'),
(2, 'Independiente'),
(3, 'Dependiente'),
(4, 'Empresario');

CREATE TABLE tipo_requisito (
    IdTipo_Requisito int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_requisito varchar(50) NOT NULL
);

INSERT INTO `tipo_requisito` (`IdTipo_Requisito`, `tipo_requisito`) VALUES
(1, 'General'),
(2, 'Uniforme');

CREATE TABLE requisito (
    IdRequisito int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    requisito varchar(255) NOT NULL,
    obligatorio BOOLEAN NOT NULL DEFAULT FALSE,
    IdNivel int NULL,
    IdTipoTrabajador int NULL,
    IdTipo_Requisito int NOT NULL,
    solo_plantel_privado BOOLEAN NOT NULL DEFAULT FALSE,
    descripcion_adicional TEXT NULL,
    FOREIGN KEY (IdNivel) REFERENCES nivel(IdNivel),
    FOREIGN KEY (IdTipoTrabajador) REFERENCES tipo_trabajador(IdTipoTrabajador),
    FOREIGN KEY (IdTipo_Requisito) REFERENCES tipo_requisito(IdTipo_Requisito)
);

-- REQUISITOS GENERALES (aplican a todos los niveles)
INSERT INTO `requisito` (`IdRequisito`, `requisito`, `obligatorio`, `IdNivel`, `IdTipoTrabajador`, `IdTipo_Requisito`, `solo_plantel_privado`, `descripcion_adicional`) VALUES
(1, 'Foto tipo carnet del alumno', TRUE, NULL, NULL, 1, FALSE, '1 foto tipo carnet'),
(2, 'Foto tipo carnet del representante', TRUE, NULL, NULL, 1, FALSE, '1 foto tipo carnet'),
(3, 'Partida de nacimiento Original del alumno', TRUE, NULL, NULL, 1, FALSE, NULL),
(4, 'Fotocopia de la cédula de identidad del alumno', TRUE, NULL, NULL, 1, FALSE, '1 fotocopia'),
(5, 'Fotocopia de la cédula de identidad de ambos padres', TRUE, NULL, NULL, 1, FALSE, '1 fotocopia de cada uno'),
(6, 'Registro de Información Fiscal (RIF) del representante', TRUE, NULL, NULL, 1, FALSE, NULL),
(7, 'Carpeta oficio plastificada color marrón con gancho', TRUE, NULL, NULL, 1, FALSE, NULL),
(8, 'Solvencia administrativa del plantel anterior', TRUE, NULL, NULL, 1, TRUE, 'Firmada y sellada por el colegio de procedencia'),

-- REQUISITOS POR TIPO DE TRABAJADOR (aplican a todos los niveles)
(9, 'Constancia de trabajo', TRUE, NULL, 3, 1, FALSE, 'Con logo de la empresa y vigencia no mayor a tres (3) meses firmada en original y con sello húmedo'),
(10, 'Certificación de Ingresos', TRUE, NULL, 2, 1, FALSE, 'Original, firmada y sellada por Contador Público colegiado, vigencia no mayor a tres (3) meses'),
(11, 'Copia del Registro Mercantil', TRUE, NULL, 4, 1, FALSE, 'Donde se verifique su posición como Propietario y/o Asociado de la empresa (Rif jurídico, legible y actualizado)'),

-- REQUISITOS ESPECÍFICOS DE EDUCACIÓN INICIAL
(12, 'Copia de tarjeta de vacunación', TRUE, 1, NULL, 1, FALSE, NULL),
(13, 'Constancia de niño sano', TRUE, 1, NULL, 1, FALSE, 'Información de peso y talla del alumno'),
(14, 'Tipaje del alumno', TRUE, 1, NULL, 1, FALSE, NULL),

-- REQUISITOS ESPECÍFICOS DE EDUCACIÓN PRIMARIA
(15, 'Copia de tarjeta de vacunación', TRUE, 2, NULL, 1, FALSE, NULL),
(16, 'Constancia de niño sano', TRUE, 2, NULL, 1, FALSE, 'Información de peso y talla del alumno'),
(17, 'Tipaje del alumno', TRUE, 2, NULL, 1, FALSE, NULL),
(18, 'Constancia SIGE', TRUE, 2, NULL, 1, FALSE, NULL),
(19, 'Constancia de Prosecución', TRUE, 2, NULL, 1, FALSE, 'Para estudiantes de 1ero a 5to grado'),
(20, 'Certificación de Educación Primaria', TRUE, 2, NULL, 1, FALSE, 'Para estudiantes de 6to Grado'),
(21, 'Informe Descriptivo Final', TRUE, 2, NULL, 1, FALSE, NULL),

-- REQUISITOS ESPECÍFICOS DE EDUCACIÓN MEDIA GENERAL
(22, 'Constancia SIGE', TRUE, 3, NULL, 1, FALSE, NULL),
(23, 'Constancia de Servicio Comunitario', FALSE, 3, NULL, 1, FALSE, NULL),
(24, 'Certificación de Educación Primaria', TRUE, 3, NULL, 1, FALSE, '6to Grado'),
(25, 'Notas Certificadas Original', TRUE, 3, NULL, 1, FALSE, 'De 2do a 5to año'),

-- REQUISITOS DE UNIFORME - CHEMISSE (varía por nivel/curso)
(26, 'Franela tipo chemisse roja (con logo bordado)', TRUE, 1, NULL, 2, FALSE, 'Por dentro - Educación Inicial'),
(27, 'Chemisse color blanco (con logo bordado)', TRUE, 2, NULL, 2, FALSE, 'Por dentro - Educación Primaria'),
(28, 'Chemisse color azul claro (con logo bordado)', TRUE, 3, NULL, 2, FALSE, 'Por dentro - Para 1ero a 3er Año'),
(29, 'Chemisse color beige (con logo bordado)', TRUE, 3, NULL, 2, FALSE, 'Por dentro - Para 4to y 5to Año'),

-- REQUISITOS DE UNIFORME - PANTALONES (varía solo en Inicial)
(30, 'Pantalones azul marino de gabardina o mono de algodón', TRUE, 1, NULL, 2, FALSE, 'No stretch - Educación Inicial'),
(31, 'Pantalones azul marino de gabardina', TRUE, NULL, NULL, 2, FALSE, 'Corte clásico (recto, bota 15cm) o modelo escolar, sin adornos ni roturas'),

-- REQUISITOS DE UNIFORME - COMUNES A TODOS
(32, 'Medias blancas', TRUE, NULL, NULL, 2, FALSE, 'No tobilleras'),
(33, 'Zapatos de color negro', TRUE, NULL, NULL, 2, FALSE, NULL),
(34, 'Correa negra con hebilla sin adornos', TRUE, NULL, NULL, 2, FALSE, 'No aplica para Educación Inicial'),

-- REQUISITOS DE UNIFORME DEPORTIVO (aplica a todos los niveles)
(35, 'Mono azul rey', TRUE, NULL, NULL, 2, FALSE, 'Sin rayas ni marcas de ningún tipo - Para Educación Física'),
(36, 'Medias blancas largas', TRUE, NULL, NULL, 2, FALSE, 'No tobilleras - Para Educación Física'),
(37, 'Franela blanca con cuello V azul rey (con logo bordado)', TRUE, NULL, NULL, 2, FALSE, 'Por dentro - Para Educación Física'),
(38, 'Zapatos deportivos blancos', TRUE, NULL, NULL, 2, FALSE, 'Livianos, sin rayas de colores, ni dibujos, ni marcas visibles, con trenzas blancas - Para Educación Física'),
(39, 'Suéter azul marino tipo escolar', FALSE, NULL, NULL, 2, FALSE, 'Del mismo color del pantalón de gabardina, sin dibujos ni letras (opcional)');

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
(1, 'Inscripcion'), (2, 'A'), (3, 'B'), (4, 'C');

CREATE TABLE curso_seccion (
    IdCurso_Seccion int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdCurso int NOT NULL,
    IdSeccion int NOT NULL,
    IdAula int NULL,
    FOREIGN KEY (IdAula) REFERENCES aula(IdAula),
    FOREIGN KEY (IdCurso) REFERENCES curso(IdCurso),
    FOREIGN KEY (IdSeccion) REFERENCES seccion(IdSeccion)
);

INSERT INTO `curso_seccion` (`IdCurso_Seccion`, `IdCurso`, `IdSeccion`, `IdAula`) VALUES
(1, 1, 1, NULL),
(2, 2, 1, NULL),
(3, 3, 1, NULL),
(4, 4, 1, NULL),
(5, 5, 1, NULL),
(6, 6, 1, NULL),
(7, 7, 1, NULL),
(8, 8, 1, NULL),
(9, 9, 1, NULL),
(10, 10, 1, NULL),
(11, 11, 1, NULL),
(12, 12, 1, NULL),
(13, 13, 1, NULL),
(14, 14, 1, NULL),
(15, 1, 2, NULL),
(16, 2, 2, NULL),
(17, 3, 2, NULL),
(18, 4, 2, NULL),
(19, 5, 2, NULL),
(20, 6, 2, NULL),
(21, 7, 2, NULL),
(22, 8, 2, NULL),
(23, 9, 2, NULL),
(24, 10, 2, NULL),
(25, 11, 2, NULL),
(26, 12, 2, NULL),
(27, 13, 2, NULL),
(28, 14, 2, NULL),
(29, 1, 3, NULL),
(30, 2, 3, NULL),
(31, 3, 3, NULL),
(32, 4, 3, NULL),
(33, 5, 3, NULL),
(34, 6, 3, NULL),
(35, 7, 3, NULL),
(36, 8, 3, NULL),
(37, 9, 3, NULL),
(38, 10, 3, NULL),
(39, 11, 3, NULL),
(40, 12, 3, NULL),
(41, 13, 3, NULL),
(42, 14, 3, NULL),
(43, 1, 4, NULL),
(44, 2, 4, NULL),
(45, 3, 4, NULL),
(46, 4, 4, NULL),
(47, 5, 4, NULL),
(48, 6, 4, NULL),
(49, 7, 4, NULL),
(50, 8, 4, NULL),
(51, 9, 4, NULL),
(52, 10, 4, NULL),
(53, 11, 4, NULL),
(54, 12, 4, NULL),
(55, 13, 4, NULL),
(56, 14, 4, NULL);

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
    nacionalidad varchar(50) NOT NULL,
    nombre_largo varchar(100) NULL
);

INSERT INTO `nacionalidad` (`IdNacionalidad`, `nacionalidad`, `nombre_largo`) VALUES
(1, 'V', 'Venezolano'),
(2, 'E', 'Extranjero');

CREATE TABLE plantel (
    IdPlantel int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    plantel varchar(100) NOT NULL,
    es_privado BOOLEAN NOT NULL DEFAULT FALSE
);

INSERT INTO `plantel` (`IdPlantel`, `plantel`, `es_privado`) VALUES
(1, 'U.E.C "Fermín Toro"', TRUE),
(2, 'U.E. Simón Bolívar', FALSE),
(3, 'U.E. José Antonio Páez', FALSE),
(4, 'Colegio San Francisco de Asís', TRUE),
(5, 'U.E. Andrés Bello', FALSE);

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
(3, 1, 'Bloqueado'),
(4, 1, 'Reposo'),
(5, 1, 'Vacaciones'),
(6, 1, 'Jubilado'),
(7, 1, 'Graduado'),
(8, 2, 'Pendiente de aprobación'),
(9, 2, 'Aprobada para reunión'),
(10, 2, 'En espera de pago'),
(11, 2, 'Inscrito'),
(12, 2, 'Rechazada');

CREATE TABLE parentesco (
    IdParentesco int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    parentesco varchar(50) NOT NULL
);

INSERT INTO `parentesco` (`IdParentesco`, `parentesco`) VALUES
(1, 'Padre'),
(2, 'Madre'),
(3, 'Tutor'),
(4, 'Hermano'),
(5, 'Abuelo'),
(6, 'Tío');

CREATE TABLE persona (
    IdPersona int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdNacionalidad int NULL,
    cedula varchar(11) NULL,
    nombre varchar(50) NOT NULL,
    apellido varchar(50) NOT NULL,
    fecha_nacimiento date NULL,
    lugar_nacimiento varchar(50) NULL,
    correo varchar(50) NULL,
    usuario varchar(50) NULL,
    password varchar(1000) NULL,
    codigo_temporal varchar (1000) NULL,
    codigo_expiracion datetime NULL,
    direccion varchar(555) NULL,
    IdSexo int NULL,
    IdUrbanismo int NULL,
    IdTipoTrabajador int NULL,
    IdEstadoAcceso int NOT NULL,
    IdEstadoInstitucional int NOT NULL,
    FOREIGN KEY (IdNacionalidad) REFERENCES nacionalidad(IdNacionalidad),
    FOREIGN KEY (IdUrbanismo) REFERENCES urbanismo(IdUrbanismo),
    FOREIGN KEY (IdTipoTrabajador) REFERENCES tipo_trabajador(IdTipoTrabajador),
    FOREIGN KEY (IdEstadoAcceso) REFERENCES status(IdStatus),
    FOREIGN KEY (IdEstadoInstitucional) REFERENCES status(IdStatus),
    FOREIGN KEY (IdSexo) REFERENCES sexo(IdSexo)
);

INSERT INTO `persona` (`IdPersona`, `IdNacionalidad`, `cedula`, `nombre`, `apellido`, `fecha_nacimiento`, `correo`, `usuario`, `password`, `codigo_temporal`, `codigo_expiracion`, `direccion`, `IdSexo`, `IdUrbanismo`, `IdEstadoAcceso`, `IdEstadoInstitucional`) VALUES
(1, 1, 30588094, 'Carlos', 'Navas', '2004-10-26', 'carlosdanielnavas26@gmail.com', 'carlos', '$2y$10$DeA8v8DgHihCe2aKBW4qZuwtITen6EM5W4OdQKoZoQHqWsBCuOM/2', NULL, NULL, 'Av. Sucre, Calle 3, Casa #152', 1, 1, 1, 1);

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
    fecha_activa BOOLEAN NOT NULL DEFAULT FALSE,
    inscripcion_activa BOOLEAN NOT NULL DEFAULT FALSE,
    renovacion_activa BOOLEAN NOT NULL DEFAULT FALSE
);

INSERT INTO `fecha_escolar` (`IdFecha_Escolar`, `fecha_escolar`, `fecha_activa`, `inscripcion_activa`, `renovacion_activa`) VALUES
(1, '2023-2024', False, False, False),
(2, '2024-2025', False, False, False),
(3, '2025-2026', True, True, True);

CREATE TABLE tipo_discapacidad (
    IdTipo_Discapacidad int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_discapacidad varchar(50) NOT NULL
);

INSERT INTO `tipo_discapacidad` (IdTipo_Discapacidad, tipo_discapacidad) VALUES
(1, 'Visual'),
(2, 'Auditiva'),
(3, 'Motora');

CREATE TABLE discapacidad (
    IdDiscapacidad int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    discapacidad varchar(50) NOT NULL,
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

CREATE TABLE tipo_inscripcion (
    IdTipo_Inscripcion int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_inscripcion varchar(50) NOT NULL
);

INSERT INTO `tipo_inscripcion` (`IdTipo_Inscripcion`, `tipo_inscripcion`) VALUES
(1, 'Nuevo Ingreso'), (2, 'Estudiante Regular');

CREATE TABLE inscripcion (
    IdInscripcion int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdTipo_Inscripcion int NOT NULL,
    codigo_inscripcion varchar(20) NOT NULL,
    IdEstudiante int NOT NULL,
    fecha_inscripcion datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_plantel int DEFAULT NULL,
    responsable_inscripcion int NOT NULL,
    IdFecha_Escolar int NOT NULL,
    IdStatus int NOT NULL,
    IdCurso_Seccion int NOT NULL,
    ultima_modificacion datetime DEFAULT NULL,
    modificado_por int DEFAULT NULL,
    FOREIGN KEY (IdCurso_Seccion) REFERENCES curso_seccion(IdCurso_Seccion),
    FOREIGN KEY (IdTipo_Inscripcion) REFERENCES tipo_inscripcion(IdTipo_Inscripcion),
    FOREIGN KEY (IdStatus) REFERENCES status(IdStatus),
    FOREIGN KEY (IdFecha_Escolar) REFERENCES fecha_escolar(IdFecha_Escolar),
    FOREIGN KEY (IdEstudiante) REFERENCES persona(IdPersona),
    FOREIGN KEY (modificado_por) REFERENCES persona(IdPersona),
    FOREIGN KEY (responsable_inscripcion) REFERENCES representante(IdRepresentante),
    FOREIGN KEY (ultimo_plantel) REFERENCES plantel(IdPlantel)
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

CREATE TABLE prefijo (
    IdPrefijo int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    codigo_prefijo varchar(10) NOT NULL,
    pais varchar(100) NOT NULL,
    max_digitos int NOT NULL DEFAULT 10
);

INSERT INTO `prefijo` (`IdPrefijo`, `codigo_prefijo`, `pais`, `max_digitos`) VALUES
(1, '+58', 'Venezuela', 10),
(2, '+1', 'Estados Unidos / Canadá', 10),
(3, '+52', 'México', 10),
(4, '+57', 'Colombia', 10),
(5, '+51', 'Perú', 9),
(6, '+56', 'Chile', 9),
(7, '+54', 'Argentina', 10),
(8, '+55', 'Brasil', 11),
(9, '+593', 'Ecuador', 9),
(10, '+591', 'Bolivia', 8),
(11, '+595', 'Paraguay', 9),
(12, '+598', 'Uruguay', 8),
(13, '+507', 'Panamá', 8),
(14, '+506', 'Costa Rica', 8),
(15, '+503', 'El Salvador', 8),
(16, '+504', 'Honduras', 8),
(17, '+505', 'Nicaragua', 8),
(18, '+502', 'Guatemala', 8),
(19, '+509', 'Haití', 8),
(20, '+53', 'Cuba', 8),
(21, '+34', 'España', 9),
(22, '+39', 'Italia', 10),
(23, '+33', 'Francia', 9),
(24, '+49', 'Alemania', 11),
(25, '+44', 'Reino Unido', 10),
(26, '+351', 'Portugal', 9),
(27, '+41', 'Suiza', 9),
(28, '+31', 'Países Bajos', 9),
(29, '+32', 'Bélgica', 9),
(30, '+86', 'China', 11),
(31, '+81', 'Japón', 10),
(32, '+82', 'Corea del Sur', 10),
(33, '+91', 'India', 10),
(34, '+7', 'Rusia', 10),
(35, '+61', 'Australia', 9),
(36, '+64', 'Nueva Zelanda', 9),
(37, '+27', 'Sudáfrica', 9),
(38, '+20', 'Egipto', 10),
(39, '+971', 'Emiratos Árabes Unidos', 9),
(40, '+966', 'Arabia Saudita', 9),
(41, '+90', 'Turquía', 10),
(42, '+98', 'Irán', 10),
(43, '+92', 'Pakistán', 10),
(44, '+880', 'Bangladesh', 10),
(45, '+62', 'Indonesia', 10),
(46, '+63', 'Filipinas', 10),
(47, '+66', 'Tailandia', 9),
(48, '+84', 'Vietnam', 9),
(49, '+60', 'Malasia', 9),
(50, '+65', 'Singapur', 8),
(51, '+852', 'Hong Kong', 8),
(52, '+886', 'Taiwán', 9),
(53, '+972', 'Israel', 9),
(54, '+234', 'Nigeria', 10),
(55, '+254', 'Kenia', 9),
(56, '+213', 'Argelia', 9),
(57, '+212', 'Marruecos', 9),
(58, '+216', 'Túnez', 8),
(59, '0255', 'Venezuela - Portuguesa (Fijo)', 7);

CREATE TABLE telefono (
    IdTelefono int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdTipo_Telefono int NOT NULL,
    numero_telefono varchar(50) NOT NULL,
    IdPersona int NOT NULL,
    IdPrefijo int DEFAULT NULL,
    FOREIGN KEY (IdTipo_Telefono) REFERENCES tipo_telefono(IdTipo_Telefono),
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona),
    FOREIGN KEY (IdPrefijo) REFERENCES prefijo(IdPrefijo)
);

INSERT INTO `telefono` (`IdTelefono`, `IdTipo_Telefono`, `numero_telefono`, `IdPersona`) VALUES
(1, 2, '04263519830', 1);

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

CREATE TABLE tipo_grupo_interes (
    IdTipo_Grupo int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nombre_grupo varchar(50) NOT NULL,
    descripcion varchar(255) DEFAULT NULL,
    capacidad_maxima int NOT NULL,
    inscripcion_activa BOOLEAN NOT NULL DEFAULT FALSE,
    IdNivel int NOT NULL,
    FOREIGN KEY (IdNivel) REFERENCES nivel(IdNivel)
);

CREATE TABLE grupo_interes (
    IdGrupo_Interes int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdTipo_Grupo int NOT NULL,
    IdProfesor int NOT NULL,
    IdCurso_Seccion int NOT NULL,
    IdFecha_Escolar int NOT NULL,
    cupos_disponibles int NOT NULL,
    FOREIGN KEY (IdTipo_Grupo) REFERENCES tipo_grupo_interes(IdTipo_Grupo),
    FOREIGN KEY (IdProfesor) REFERENCES persona(IdPersona),
    FOREIGN KEY (IdCurso_Seccion) REFERENCES curso_seccion(IdCurso_Seccion),
    FOREIGN KEY (IdFecha_Escolar) REFERENCES fecha_escolar(IdFecha_Escolar)
);

CREATE TABLE inscripcion_grupo_interes (
    IdInscripcion_Grupo int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdGrupo_Interes int NOT NULL,
    IdEstudiante int NOT NULL,
    fecha_ingreso_grupo datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IdGrupo_Interes) REFERENCES grupo_interes(IdGrupo_Interes),
    FOREIGN KEY (IdEstudiante) REFERENCES persona(IdPersona)
);