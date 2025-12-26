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
(10, 'Coordinador Media General'),
(11, 'Sub-director'),
(12, 'Dirección'),
(13, 'Psicólogo');

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
-- Aulas Nivel Inicial
(1, 1, 'Aula 1', 35),
(2, 1, 'Aula 2', 35),
(3, 1, 'Aula 3', 35),
(4, 1, 'Aula 4', 35),
(5, 1, 'Aula 5', 35),
(6, 1, 'Aula 6', 35),
(7, 1, 'Aula 7', 35),
(8, 1, 'Aula 8', 35),
(9, 1, 'Aula 9', 35),
(10, 1, 'Aula 10', 35),
(11, 1, 'Aula 11', 35),
(12, 1, 'Aula 12', 35),
-- Aulas Nivel Primaria
(13, 2, 'Aula 13', 35),
(14, 2, 'Aula 14', 35),
(15, 2, 'Aula 15', 35),
(16, 2, 'Aula 16', 35),
(17, 2, 'Aula 17', 35),
(18, 2, 'Aula 18', 35),
(19, 2, 'Aula 19', 35),
(20, 2, 'Aula 20', 35),
(21, 2, 'Aula 21', 35),
(22, 2, 'Aula 22', 35),
(23, 2, 'Aula 23', 35),
(24, 2, 'Aula 24', 35),
(25, 2, 'Aula 25', 35),
(26, 2, 'Aula 26', 35),
(27, 2, 'Aula 27', 35),
(28, 2, 'Aula 28', 35),
(29, 2, 'Aula 29', 35),
(30, 2, 'Aula 30', 35),
(31, 2, 'Aula 31', 35),
(32, 2, 'Aula 32', 35),
(33, 2, 'Aula 33', 35),
(34, 2, 'Aula 34', 35),
(35, 2, 'Aula 35', 35),
(36, 2, 'Aula 36', 35),
-- Aulas Nivel Media General
(37, 3, 'Aula 37', 35),
(38, 3, 'Aula 38', 35),
(39, 3, 'Aula 39', 35),
(40, 3, 'Aula 40', 35),
(41, 3, 'Aula 41', 35),
(42, 3, 'Aula 42', 35),
(43, 3, 'Aula 43', 35),
(44, 3, 'Aula 44', 35),
(45, 3, 'Aula 45', 35),
(46, 3, 'Aula 46', 35),
(47, 3, 'Aula 47', 35),
(48, 3, 'Aula 48', 35),
(49, 3, 'Aula 49', 35),
(50, 3, 'Aula 50', 35),
(51, 3, 'Aula 51', 35),
(52, 3, 'Aula 52', 35),
(53, 3, 'Aula 53', 35),
(54, 3, 'Aula 54', 35),
(55, 3, 'Aula 55', 35),
(56, 3, 'Aula 56', 35);

CREATE TABLE curso (
    IdCurso int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    curso varchar(50) NOT NULL,
    cantidad_secciones INT NOT NULL,
    IdNivel int NOT NULL,
    FOREIGN KEY (IdNivel) REFERENCES nivel(IdNivel)
);

INSERT INTO `curso` (`IdCurso`, `curso`, `cantidad_secciones`, `IdNivel`) VALUES
(1, '1er Nivel', 3, 1),
(2, '2do Nivel', 3, 1),
(3, '3er Nivel', 3, 1),
(4, '1er Grado', 3, 2),
(5, '2do Grado', 3, 2),
(6, '3er Grado', 3, 2),
(7, '4to Grado', 3, 2),
(8, '5to Grado', 3, 2),
(9, '6to Grado', 3, 2),
(10, '1er Año', 3, 3),
(11, '2do Año', 3, 3),
(12, '3er Año', 3, 3),
(13, '4to Año', 3, 3),
(14, '5to Año', 3, 3);

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
    activo BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (IdAula) REFERENCES aula(IdAula),
    FOREIGN KEY (IdCurso) REFERENCES curso(IdCurso),
    FOREIGN KEY (IdSeccion) REFERENCES seccion(IdSeccion)
);

INSERT INTO `curso_seccion` (`IdCurso_Seccion`, `IdCurso`, `IdSeccion`, `IdAula`, `activo`) VALUES
-- Sección Inscripción (sin aula asignada)
(1, 1, 1, NULL, TRUE),
(2, 2, 1, NULL, TRUE),
(3, 3, 1, NULL, TRUE),
(4, 4, 1, NULL, TRUE),
(5, 5, 1, NULL, TRUE),
(6, 6, 1, NULL, TRUE),
(7, 7, 1, NULL, TRUE),
(8, 8, 1, NULL, TRUE),
(9, 9, 1, NULL, TRUE),
(10, 10, 1, NULL, TRUE),
(11, 11, 1, NULL, TRUE),
(12, 12, 1, NULL, TRUE),
(13, 13, 1, NULL, TRUE),
(14, 14, 1, NULL, TRUE),
-- Sección A
(15, 1, 2, 1, TRUE),   -- 1er Nivel A -> Aula 1
(16, 2, 2, 2, TRUE),   -- 2do Nivel A -> Aula 2
(17, 3, 2, 3, TRUE),   -- 3er Nivel A -> Aula 3
(18, 4, 2, 13, TRUE),  -- 1er Grado A -> Aula 13
(19, 5, 2, 14, TRUE),  -- 2do Grado A -> Aula 14
(20, 6, 2, 15, TRUE),  -- 3er Grado A -> Aula 15
(21, 7, 2, 16, TRUE),  -- 4to Grado A -> Aula 16
(22, 8, 2, 17, TRUE),  -- 5to Grado A -> Aula 17
(23, 9, 2, 18, TRUE),  -- 6to Grado A -> Aula 18
(24, 10, 2, 37, TRUE), -- 1er Año A -> Aula 37
(25, 11, 2, 38, TRUE), -- 2do Año A -> Aula 38
(26, 12, 2, 39, TRUE), -- 3er Año A -> Aula 39
(27, 13, 2, 40, TRUE), -- 4to Año A -> Aula 40
(28, 14, 2, 41, TRUE), -- 5to Año A -> Aula 41
-- Sección B
(29, 1, 3, 4, TRUE),   -- 1er Nivel B -> Aula 4
(30, 2, 3, 5, TRUE),   -- 2do Nivel B -> Aula 5
(31, 3, 3, 6, TRUE),   -- 3er Nivel B -> Aula 6
(32, 4, 3, 19, TRUE),  -- 1er Grado B -> Aula 19
(33, 5, 3, 20, TRUE),  -- 2do Grado B -> Aula 20
(34, 6, 3, 21, TRUE),  -- 3er Grado B -> Aula 21
(35, 7, 3, 22, TRUE),  -- 4to Grado B -> Aula 22
(36, 8, 3, 23, TRUE),  -- 5to Grado B -> Aula 23
(37, 9, 3, 24, TRUE),  -- 6to Grado B -> Aula 24
(38, 10, 3, 42, TRUE), -- 1er Año B -> Aula 42
(39, 11, 3, 43, TRUE), -- 2do Año B -> Aula 43
(40, 12, 3, 44, TRUE), -- 3er Año B -> Aula 44
(41, 13, 3, 45, TRUE), -- 4to Año B -> Aula 45
(42, 14, 3, 46, TRUE), -- 5to Año B -> Aula 46
-- Sección C
(43, 1, 4, 7, TRUE),   -- 1er Nivel C -> Aula 7
(44, 2, 4, 8, TRUE),   -- 2do Nivel C -> Aula 8
(45, 3, 4, 9, TRUE),   -- 3er Nivel C -> Aula 9
(46, 4, 4, 25, TRUE),  -- 1er Grado C -> Aula 25
(47, 5, 4, 26, TRUE),  -- 2do Grado C -> Aula 26
(48, 6, 4, 27, TRUE),  -- 3er Grado C -> Aula 27
(49, 7, 4, 28, TRUE),  -- 4to Grado C -> Aula 28
(50, 8, 4, 29, TRUE),  -- 5to Grado C -> Aula 29
(51, 9, 4, 30, TRUE),  -- 6to Grado C -> Aula 30
(52, 10, 4, 47, TRUE), -- 1er Año C -> Aula 47
(53, 11, 4, 48, TRUE), -- 2do Año C -> Aula 48
(54, 12, 4, 49, TRUE), -- 3er Año C -> Aula 49
(55, 13, 4, 50, TRUE), -- 4to Año C -> Aula 50
(56, 14, 4, 51, TRUE); -- 5to Año C -> Aula 51

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
    foto_perfil LONGBLOB DEFAULT NULL,
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
(1, 'Nuevo Ingreso'), (2, 'Estudiante Regular'), (3, 'Reinscripción');

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
    repite BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Indica si el estudiante repite el curso',
    codigo_pago varchar(50) DEFAULT NULL COMMENT 'Código de factura/pago del sistema administrativo',
    fecha_validacion_pago datetime DEFAULT NULL COMMENT 'Fecha en que se validó el pago',
    validado_por int DEFAULT NULL COMMENT 'IdPersona del usuario que validó el pago',
    FOREIGN KEY (IdCurso_Seccion) REFERENCES curso_seccion(IdCurso_Seccion),
    FOREIGN KEY (IdTipo_Inscripcion) REFERENCES tipo_inscripcion(IdTipo_Inscripcion),
    FOREIGN KEY (IdStatus) REFERENCES status(IdStatus),
    FOREIGN KEY (IdFecha_Escolar) REFERENCES fecha_escolar(IdFecha_Escolar),
    FOREIGN KEY (IdEstudiante) REFERENCES persona(IdPersona),
    FOREIGN KEY (responsable_inscripcion) REFERENCES representante(IdRepresentante),
    FOREIGN KEY (ultimo_plantel) REFERENCES plantel(IdPlantel),
    FOREIGN KEY (validado_por) REFERENCES persona(IdPersona)
);

-- Tabla para registrar el historial de cambios en inscripciones y personas
CREATE TABLE historial_cambios (
    IdHistorial int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdInscripcion int NULL COMMENT 'ID de la inscripción (NULL si el cambio es en persona)',
    IdPersona int NULL COMMENT 'ID de la persona (NULL si el cambio es en inscripción)',
    tipo_entidad ENUM('inscripcion', 'persona') NOT NULL DEFAULT 'inscripcion' COMMENT 'Tipo de entidad modificada',
    campo_modificado varchar(50) NOT NULL COMMENT 'Nombre del campo que fue modificado',
    valor_anterior varchar(255) DEFAULT NULL COMMENT 'Valor antes del cambio',
    valor_nuevo varchar(255) DEFAULT NULL COMMENT 'Valor después del cambio',
    descripcion varchar(500) DEFAULT NULL COMMENT 'Descripción legible del cambio',
    fecha_cambio datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    IdUsuario int NOT NULL COMMENT 'IdPersona del usuario que realizó el cambio',
    FOREIGN KEY (IdInscripcion) REFERENCES inscripcion(IdInscripcion) ON DELETE CASCADE,
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona) ON DELETE CASCADE,
    FOREIGN KEY (IdUsuario) REFERENCES persona(IdPersona),
    INDEX idx_inscripcion (IdInscripcion),
    INDEX idx_persona (IdPersona),
    INDEX idx_fecha (fecha_cambio),
    CONSTRAINT chk_historial_tipo CHECK (
        (IdInscripcion IS NOT NULL AND IdPersona IS NULL) OR
        (IdInscripcion IS NULL AND IdPersona IS NOT NULL)
    )
) COMMENT = 'Historial de cambios para inscripciones y personas';

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
    fecha_asignacion datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
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

INSERT INTO `telefono` (`IdTelefono`, `IdTipo_Telefono`, `numero_telefono`, `IdPersona`, `IdPrefijo`) VALUES
(1, 2, '4263519830', 1, 1);

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
    IdCurso int NOT NULL,
    IdFecha_Escolar int NOT NULL,
    FOREIGN KEY (IdTipo_Grupo) REFERENCES tipo_grupo_interes(IdTipo_Grupo),
    FOREIGN KEY (IdProfesor) REFERENCES persona(IdPersona),
    FOREIGN KEY (IdCurso) REFERENCES curso(IdCurso),
    FOREIGN KEY (IdFecha_Escolar) REFERENCES fecha_escolar(IdFecha_Escolar)
);

CREATE TABLE inscripcion_grupo_interes (
    IdInscripcion_Grupo int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdGrupo_Interes int NOT NULL,
    IdEstudiante int NOT NULL,
    IdInscripcion int NOT NULL,
    FOREIGN KEY (IdGrupo_Interes) REFERENCES grupo_interes(IdGrupo_Interes),
    FOREIGN KEY (IdEstudiante) REFERENCES persona(IdPersona),
    FOREIGN KEY (IdInscripcion) REFERENCES inscripcion(IdInscripcion)
);

CREATE TABLE notificaciones (
    IdNotificacion int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    titulo varchar(100) NOT NULL,
    mensaje varchar(255) NOT NULL,
    fecha_creacion datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo varchar(50) NOT NULL,
    enlace varchar(255) NULL,
    destinatarios varchar(50) NOT NULL DEFAULT 'admin'
);

CREATE TABLE notificaciones_leidas (
    IdNotificacionLeida int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdNotificacion int NOT NULL,
    IdPersona int NOT NULL,
    fecha_lectura datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (IdNotificacion) REFERENCES notificaciones(IdNotificacion) ON DELETE CASCADE,
    FOREIGN KEY (IdPersona) REFERENCES persona(IdPersona) ON DELETE CASCADE
);

-- =====================================================
-- Configuración de WhatsApp / Evolution API
-- =====================================================

CREATE TABLE config_whatsapp (
    IdConfigWhatsapp INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    api_url VARCHAR(255) NOT NULL COMMENT 'URL de Evolution API',
    api_key VARCHAR(255) NOT NULL COMMENT 'API Key hasheada',
    nombre_instancia VARCHAR(100) NOT NULL COMMENT 'Nombre de la instancia de WhatsApp',
    login_url VARCHAR(255) NULL COMMENT 'URL de login para incluir en mensajes',
    activo BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Solo una configuración activa',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar configuración inicial (api_key vacía, se configura desde la interfaz)
INSERT INTO config_whatsapp (api_url, api_key, nombre_instancia, login_url, activo) VALUES
('http://localhost:8080', '04E444271B95-471D-8CFA-47254AC4208C', 'Test', NULL, TRUE);

-- Tabla para mensajes parametrizables de WhatsApp por status de inscripción
CREATE TABLE mensaje_whatsapp (
    IdMensajeWhatsapp INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    IdStatus INT NOT NULL COMMENT 'Status de inscripción asociado',
    titulo VARCHAR(100) NOT NULL COMMENT 'Título identificador del mensaje',
    contenido TEXT NOT NULL COMMENT 'Contenido del mensaje con variables (soporta emojis)',
    incluir_requisitos BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Si se incluyen requisitos del nivel',
    activo BOOLEAN NOT NULL DEFAULT TRUE COMMENT 'Si el mensaje está activo',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (IdStatus) REFERENCES status(IdStatus)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Mensajes por defecto para cada status de inscripción
-- Variables disponibles: {nombre_representante}, {nombre_estudiante}, {codigo_inscripcion}, {curso}, {seccion}, {cedula_representante}, {requisitos}, {login_url}

-- Status 8: Pendiente de aprobación
INSERT INTO mensaje_whatsapp (IdStatus, titulo, contenido, incluir_requisitos, activo) VALUES
(8, 'Solicitud Recibida',
'⏳ *Solicitud en Proceso*

Estimado(a) *{nombre_representante}*,

La solicitud de inscripción de *{nombre_estudiante}* ha sido recibida y está en revisión inicial.

Nuestro equipo administrativo verificará la documentación y le notificará los próximos pasos en un plazo de 48 horas hábiles.

Código de Seguimiento: {codigo_inscripcion}',
FALSE, TRUE);

-- Status 9: Aprobada para reunión
INSERT INTO mensaje_whatsapp (IdStatus, titulo, contenido, incluir_requisitos, activo) VALUES
(9, 'Aprobado para Reunión',
'✅ *Aprobado para Reunión*

Estimado(a) *{nombre_representante}*,

La solicitud de *{nombre_estudiante}* ha sido pre-aprobada.

*📅 Próximo paso:* Asistir a la reunión de formalización entre el *1 y 31 de octubre* en horario de oficina.

*📋 Debe traer:*
{requisitos}

Código de seguimiento: {codigo_inscripcion}',
TRUE, TRUE);

-- Status 10: En espera de pago
INSERT INTO mensaje_whatsapp (IdStatus, titulo, contenido, incluir_requisitos, activo) VALUES
(10, 'Pendiente de Pago',
'💳 *Pendiente de Pago*

Estimado(a) *{nombre_representante}*,

*{nombre_estudiante}* ha sido *aceptado oficialmente* en nuestra institución.

*📅 Próximo paso:* Diríjase a la caja para realizar el pago de:
• Matrícula de inscripción
• Primera mensualidad

*⏰ Horario de caja:*
Lunes a Viernes: 7:00 AM - 2:00 PM

Una vez realizado el pago, la inscripción se completará automáticamente.

Código de Seguimiento: {codigo_inscripcion}',
FALSE, TRUE);

-- Status 11: Inscrito
INSERT INTO mensaje_whatsapp (IdStatus, titulo, contenido, incluir_requisitos, activo) VALUES
(11, 'Inscripción Completada',
'🎉 *¡Inscripción Completada!*

Estimado(a) *{nombre_representante}*,

*¡Felicidades!*

*{nombre_estudiante}* ha sido oficialmente inscrito(a) en:
• 🏫 Curso: {curso}
• 📚 Sección: {seccion}

*📅 Inicio de clases:*
Primera semana de noviembre

*🌐 Información importante:*
Ahora puede consultar el horario y demás información en nuestro sitio web.

👤 Usuario: {cedula_representante}
🔑 Contraseña: {cedula_representante}

⚠️ *Importante:* Por seguridad, cambie su contraseña después de iniciar sesión por primera vez.

{login_url}

¡Bienvenido(a) a nuestra familia fermintoriana!',
FALSE, TRUE);

-- Status 12: Rechazada
INSERT INTO mensaje_whatsapp (IdStatus, titulo, contenido, incluir_requisitos, activo) VALUES
(12, 'Solicitud Rechazada',
'❌ *Solicitud Rechazada*

Estimado(a) *{nombre_representante}*,

Luego de revisar la documentación de *{nombre_estudiante}*, lamentamos informarle que la solicitud de inscripción no pudo ser procesada.

*📞 Contacte a administración* para:
• Conocer los motivos específicos
• Recibir orientación sobre opciones disponibles
• Solicitar reconsideración si aplica

Horario de atención: Lunes a Viernes 7:00 AM - 3:00 PM

Código de Seguimiento: {codigo_inscripcion}',
FALSE, TRUE);

-- Índices para búsquedas rápidas
CREATE INDEX idx_mensaje_status ON mensaje_whatsapp(IdStatus);
CREATE INDEX idx_mensaje_activo ON mensaje_whatsapp(activo);