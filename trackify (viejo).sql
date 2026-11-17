-- =====================================================================
-- Trackify — esquema unificado para MySQL / MariaDB
-- =====================================================================
-- Fusión de las dos bases del equipo (11/2026):
--   * base de devagus (la que usa el código: icono, id_usuario en categorías,
--     metas con descripción/estado y centavos, nombre_usuario, Foto_perfil)
--   * base de devulises ("trackify (ultima version).sql": movimientos.moneda,
--     uuid CHAR(36) NOT NULL UNIQUE)
-- Equivalente en PostgreSQL: trackify_postgres.sql (mismas tablas y columnas).
--
-- Uso: crear la base "trackify" (utf8mb4_general_ci) en phpMyAdmin e importar
-- este archivo. Sin usuarios de prueba: registrate desde la app.
-- Si ya tenés una base de una versión anterior NO hace falta reimportar:
-- conexion.php la actualiza sola al abrir cualquier página.
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- usuarios
-- ---------------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id_usuario`          int(11)       NOT NULL AUTO_INCREMENT,
  `nombre`              varchar(50)   NOT NULL,
  `nombre_usuario`      varchar(30)   DEFAULT NULL,            -- @usuario (único)
  `email`               varchar(100)  NOT NULL,
  `clave`               varchar(255)  NOT NULL,                -- hash bcrypt; '' en cuentas de Google sin contraseña
  `fecha_registro`      datetime      NOT NULL,
  `uuid`                char(36)      NOT NULL,                -- identificador público (UUID v4)
  `Foto_perfil`         varchar(255)  DEFAULT NULL,
  `google_id`           varchar(255)  DEFAULT NULL,
  `email_verificado`    tinyint(1)    NOT NULL DEFAULT 0,
  `codigo_verificacion` varchar(6)    DEFAULT NULL,            -- OTP de 6 dígitos
  `codigo_expiracion`   datetime      DEFAULT NULL,
  `moneda_principal`    enum('ARS','USD') NOT NULL DEFAULT 'ARS',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `uq_usuarios_nombre_usuario` (`nombre_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- categorias  (id_usuario NULL = categoría del sistema, solo lectura)
-- ---------------------------------------------------------------------
CREATE TABLE `categorias` (
  `id_categoria` int(11)      NOT NULL AUTO_INCREMENT,
  `nombre`       varchar(50)  NOT NULL,
  `tipo`         enum('ingreso','gasto') NOT NULL,
  `icono`        varchar(255) DEFAULT NULL,
  `color`        varchar(7)   DEFAULT NULL,
  `id_usuario`   int(11)      DEFAULT NULL,
  PRIMARY KEY (`id_categoria`),
  KEY `idx_categorias_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- movimientos  (monto SIEMPRE en ARS; "moneda" registra eso)
-- ---------------------------------------------------------------------
CREATE TABLE `movimientos` (
  `id_movimiento` int(11)       NOT NULL AUTO_INCREMENT,
  `id_usuario`    int(11)       NOT NULL,
  `id_categoria`  int(11)       NOT NULL,
  `monto`         decimal(12,2) NOT NULL,
  `tipo`          enum('ingreso','gasto') NOT NULL,
  `descripcion`   varchar(255)  DEFAULT NULL,
  `fecha`         date          NOT NULL,
  `es_hormiga`    tinyint(1)    DEFAULT 0,
  `moneda`        varchar(3)    NOT NULL DEFAULT 'ARS',
  PRIMARY KEY (`id_movimiento`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_categoria` (`id_categoria`),
  CONSTRAINT `movimientos_ibfk_1` FOREIGN KEY (`id_usuario`)   REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `movimientos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- metas_ahorro  (montos en ARS)
-- ---------------------------------------------------------------------
CREATE TABLE `metas_ahorro` (
  `id_meta`        int(11)       NOT NULL AUTO_INCREMENT,
  `id_usuario`     int(11)       DEFAULT NULL,
  `nombre_meta`    varchar(100)  NOT NULL,
  `descripcion`    varchar(255)  DEFAULT NULL,
  `monto_objetivo` decimal(12,2) NOT NULL,
  `monto_actual`   decimal(12,2) NOT NULL DEFAULT 0.00,
  `fecha_limite`   date          NOT NULL,
  `estado`         enum('activo','inactivo','logrado') DEFAULT 'activo',
  PRIMARY KEY (`id_meta`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `metas_ahorro_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- consejos_ia  (reservada: hoy la app no guarda historial)
-- ---------------------------------------------------------------------
CREATE TABLE `consejos_ia` (
  `id_consejos` int(11)   NOT NULL AUTO_INCREMENT,
  `id_usuario`  int(11)   DEFAULT NULL,
  `mensaje`     text      NOT NULL,
  `fecha`       timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_consejos`),
  KEY `id_usuario` (`id_usuario`),
  CONSTRAINT `consejos_ia_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Categorías predeterminadas del sistema (las mismas que crea conexion.php)
-- ---------------------------------------------------------------------
INSERT INTO `categorias` (`nombre`, `tipo`, `icono`, `color`) VALUES
('Sueldo', 'ingreso', 'iconos/ingreso/sueldo.png', '#084734'),
('Préstamo recibido', 'ingreso', 'iconos/ingreso/prestamo.png', '#CFF27C'),
('Reintegro', 'ingreso', 'iconos/ingreso/reembolso.png', '#EA73F5'),
('Ventas', 'ingreso', 'iconos/ingreso/ventas.png', '#CFF27C'),
('Inversiones', 'ingreso', 'iconos/ingreso/inversiones1.png', '#084734'),
('Intereses', 'ingreso', 'iconos/ingreso/intereses1.png', '#700353'),
('Regalos', 'ingreso', 'iconos/ingreso/regalo.png', '#CFF27C'),
('Devoluciones', 'ingreso', 'iconos/ingreso/devoluciones.png', '#EA73F5'),
('Freelance / trabajos extra', 'ingreso', 'iconos/ingreso/freelancetrabajoextra.png', '#700353'),
('Becas / subsidios', 'ingreso', 'iconos/ingreso/becasubsidioayuda.png', '#084734'),
('Otros ingresos', 'ingreso', 'iconos/ingreso/otrosdinero.png', '#334155'),
('Alimentos', 'gasto', 'iconos/generales/pizza.png', '#EA73F5'),
('Transporte', 'gasto', 'iconos/gasto/transporte.png', '#084734'),
('Vivienda', 'gasto', 'iconos/gasto/vivienda.png', '#700353'),
('Salud', 'gasto', 'iconos/gasto/salud.png', '#084734'),
('Educación', 'gasto', 'iconos/gasto/educacion.png', '#700353'),
('Entretenimiento', 'gasto', 'iconos/gasto/entretenimiento.png', '#EA73F5'),
('Compras personales', 'gasto', 'iconos/gasto/compraspersonales.png', '#EA73F5'),
('Deudas', 'gasto', 'iconos/gasto/deuda.png', '#700353'),
('Impuestos', 'gasto', 'iconos/gasto/impuesto.png', '#700353'),
('Mascotas', 'gasto', 'iconos/gasto/mascotahuellagato.png', '#084734'),
('Suscripciones', 'gasto', 'iconos/gasto/suscripcion.png', '#700353'),
('Regalos / donaciones', 'gasto', 'iconos/gasto/regalodonacion.png', '#CFF27C'),
('Ropa', 'gasto', 'iconos/gasto/ropa.png', '#700353'),
('Tecnología', 'gasto', 'iconos/gasto/tecnologia1.png', '#700353'),
('Viajes', 'gasto', 'iconos/gasto/viajemaleta.png', '#084734'),
('Otros gastos', 'gasto', 'iconos/gasto/otrosgastos.png', '#334155');

COMMIT;
