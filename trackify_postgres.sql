-- =====================================================================
-- Trackify — esquema de la base de datos (PostgreSQL)
-- =====================================================================
-- Única base soportada por la app. Crea las tablas, índices, claves
-- foráneas y las categorías predeterminadas (sin usuarios ni datos).
--
-- Uso (base vacía):
--   createdb -U postgres trackify
--   psql -U postgres -d trackify -f trackify_postgres.sql
--
-- La app se conecta con los datos DB_* (o DATABASE_URL) de .env y necesita
-- extension=pdo_pgsql en php.ini. Una base creada con una versión anterior
-- de este archivo se actualiza sola (includes/migraciones.php).
-- Para traer los datos de una base MySQL vieja:
--   php herramientas/migrar_mysql_a_postgres.php
--
-- Notas:
--  * Nombres sin comillas: PostgreSQL los guarda en minúscula.
--  * email_verificado y es_hormiga son SMALLINT (0/1), no BOOLEAN: el
--    código compara con 0/1.
-- =====================================================================

BEGIN;

SET TIME ZONE 'UTC';

-- ---------------------------------------------------------------------
-- Tipos ENUM
-- ---------------------------------------------------------------------
CREATE TYPE tipo_movimiento AS ENUM ('ingreso', 'gasto');
CREATE TYPE estado_meta     AS ENUM ('activo', 'inactivo', 'logrado');

-- ---------------------------------------------------------------------
-- usuarios
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id_usuario          SERIAL       NOT NULL,
  nombre              VARCHAR(50)  NOT NULL,
  nombre_usuario      VARCHAR(30)  DEFAULT NULL,
  email               VARCHAR(100) NOT NULL,
  clave               VARCHAR(255) NOT NULL,
  fecha_registro      TIMESTAMP    NOT NULL,
  uuid                CHAR(36)     NOT NULL,
  foto_perfil         VARCHAR(255) DEFAULT NULL,
  google_id           VARCHAR(255) DEFAULT NULL,
  email_verificado    SMALLINT     NOT NULL DEFAULT 0,
  codigo_verificacion VARCHAR(6)   DEFAULT NULL,
  codigo_expiracion   TIMESTAMP    DEFAULT NULL,
  moneda_principal    VARCHAR(3)   NOT NULL DEFAULT 'ARS',
  PRIMARY KEY (id_usuario),
  CONSTRAINT usuarios_email_key UNIQUE (email),
  CONSTRAINT usuarios_uuid_key UNIQUE (uuid),
  CONSTRAINT uq_usuarios_nombre_usuario UNIQUE (nombre_usuario),
  CONSTRAINT usuarios_moneda_check CHECK (moneda_principal IN ('ARS', 'USD')),
  CONSTRAINT usuarios_verificado_check CHECK (email_verificado IN (0, 1))
);

-- ---------------------------------------------------------------------
-- categorias  (id_usuario NULL = categoría del sistema; sin FK a propósito)
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
  id_categoria SERIAL       NOT NULL,
  nombre       VARCHAR(50)  NOT NULL,
  tipo         tipo_movimiento NOT NULL,
  icono        VARCHAR(255) DEFAULT NULL,
  color        VARCHAR(7)   DEFAULT NULL,
  id_usuario   INTEGER      DEFAULT NULL,
  PRIMARY KEY (id_categoria)
);

-- ---------------------------------------------------------------------
-- movimientos  (monto SIEMPRE en ARS)
-- ---------------------------------------------------------------------
CREATE TABLE movimientos (
  id_movimiento SERIAL        NOT NULL,
  id_usuario    INTEGER       NOT NULL,
  id_categoria  INTEGER       NOT NULL,
  monto         NUMERIC(12,2) NOT NULL,
  tipo          tipo_movimiento NOT NULL,
  descripcion   VARCHAR(255)  DEFAULT NULL,
  fecha         DATE          NOT NULL,
  es_hormiga    SMALLINT      DEFAULT 0,
  moneda        VARCHAR(3)    NOT NULL DEFAULT 'ARS',
  PRIMARY KEY (id_movimiento)
);

-- ---------------------------------------------------------------------
-- metas_ahorro  (montos en ARS)
-- ---------------------------------------------------------------------
CREATE TABLE metas_ahorro (
  id_meta        SERIAL        NOT NULL,
  id_usuario     INTEGER       DEFAULT NULL,
  nombre_meta    VARCHAR(100)  NOT NULL,
  descripcion    VARCHAR(255)  DEFAULT NULL,
  monto_objetivo NUMERIC(12,2) NOT NULL,
  monto_actual   NUMERIC(12,2) NOT NULL DEFAULT 0,
  fecha_limite   DATE          NOT NULL,
  estado         estado_meta   DEFAULT 'activo',
  PRIMARY KEY (id_meta)
);

-- ---------------------------------------------------------------------
-- consejos_ia  (reservada: hoy la app no guarda historial)
-- ---------------------------------------------------------------------
CREATE TABLE consejos_ia (
  id_consejos SERIAL    NOT NULL,
  id_usuario  INTEGER   DEFAULT NULL,
  mensaje     TEXT      NOT NULL,
  fecha       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_consejos)
);

-- "fecha" se actualiza sola en cada UPDATE (PostgreSQL no tiene ON UPDATE).
CREATE OR REPLACE FUNCTION actualizar_fecha_consejos_ia()
RETURNS TRIGGER AS $$
BEGIN
  NEW.fecha = CURRENT_TIMESTAMP;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_consejos_ia_fecha
BEFORE UPDATE ON consejos_ia
FOR EACH ROW
EXECUTE FUNCTION actualizar_fecha_consejos_ia();

-- ---------------------------------------------------------------------
-- Índices
-- ---------------------------------------------------------------------
CREATE INDEX idx_categorias_usuario        ON categorias (id_usuario);
CREATE INDEX idx_consejos_ia_id_usuario    ON consejos_ia (id_usuario);
CREATE INDEX idx_metas_ahorro_id_usuario   ON metas_ahorro (id_usuario);
CREATE INDEX idx_movimientos_usuario_fecha ON movimientos (id_usuario, fecha);
CREATE INDEX idx_movimientos_id_categoria  ON movimientos (id_categoria);

-- ---------------------------------------------------------------------
-- Claves foráneas
-- ---------------------------------------------------------------------
ALTER TABLE consejos_ia
  ADD CONSTRAINT consejos_ia_ibfk_1 FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario);

ALTER TABLE metas_ahorro
  ADD CONSTRAINT metas_ahorro_ibfk_1 FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario);

ALTER TABLE movimientos
  ADD CONSTRAINT movimientos_ibfk_1 FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario),
  ADD CONSTRAINT movimientos_ibfk_2 FOREIGN KEY (id_categoria) REFERENCES categorias (id_categoria);

-- ---------------------------------------------------------------------
-- Categorías predeterminadas del sistema (las mismas que trackify.sql)
-- ---------------------------------------------------------------------
INSERT INTO categorias (nombre, tipo, icono, color) VALUES
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
