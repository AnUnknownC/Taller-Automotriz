-- =====================================================================
-- Taller Automotriz · schema.sql
-- Motor: MariaDB 10.4 (incluido en XAMPP 8.0.30) · InnoDB · utf8mb4
-- =====================================================================
SET NAMES utf8mb4;
DROP DATABASE IF EXISTS taller_automotriz;
CREATE DATABASE taller_automotriz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE taller_automotriz;

-- ---------------------------------------------------------------------
-- SEGURIDAD Y USUARIOS
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  id      TINYINT UNSIGNED PRIMARY KEY,
  nombre  VARCHAR(30) NOT NULL UNIQUE          -- administrador, asesor, tecnico, cliente
) ENGINE=InnoDB;

CREATE TABLE usuarios (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rol_id         TINYINT UNSIGNED NOT NULL,
  nombre         VARCHAR(100) NOT NULL,
  email          VARCHAR(150) NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,        -- RS-01: nunca texto plano
  activo         TINYINT(1) NOT NULL DEFAULT 1, -- RF-03: activación/desactivación
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- RS-07: limitación progresiva por email + IP (incluye emails inexistentes)
CREATE TABLE intentos_login (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email       VARCHAR(150) NOT NULL,
  ip          VARCHAR(45)  NOT NULL,
  exitoso     TINYINT(1)   NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_intentos_email_fecha (email, created_at),
  INDEX idx_intentos_ip_fecha (ip, created_at)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- CLIENTES Y VEHÍCULOS
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id      INT UNSIGNED NULL UNIQUE,    -- cuenta de acceso (rol cliente), opcional
  tipo_documento  ENUM('CC','CE','NIT','PAS') NOT NULL,
  documento       VARCHAR(20)  NOT NULL,
  nombre          VARCHAR(120) NOT NULL,
  telefono        VARCHAR(20)  NOT NULL,
  email           VARCHAR(150) NULL,
  direccion       VARCHAR(200) NULL,
  activo          TINYINT(1) NOT NULL DEFAULT 1, -- eliminación lógica
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cliente_documento (tipo_documento, documento),
  INDEX idx_clientes_nombre (nombre),
  CONSTRAINT fk_clientes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE vehiculos (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cliente_id   INT UNSIGNED NOT NULL,
  placa        VARCHAR(10) NOT NULL UNIQUE,
  marca        VARCHAR(50) NOT NULL,
  modelo       VARCHAR(50) NOT NULL,
  anio         SMALLINT UNSIGNED NOT NULL,
  color        VARCHAR(30) NULL,
  vin          VARCHAR(17) NULL UNIQUE,
  activo       TINYINT(1) NOT NULL DEFAULT 1,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_vehiculo_anio CHECK (anio BETWEEN 1950 AND 2100),
  CONSTRAINT fk_vehiculos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- INVENTARIO
-- ---------------------------------------------------------------------
CREATE TABLE proveedores (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nit         VARCHAR(20)  NOT NULL UNIQUE,
  nombre      VARCHAR(120) NOT NULL,
  telefono    VARCHAR(20)  NULL,
  email       VARCHAR(150) NULL,
  activo      TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE repuestos (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo         VARCHAR(30)  NOT NULL UNIQUE,
  nombre         VARCHAR(120) NOT NULL,
  marca          VARCHAR(50)  NULL,
  costo          DECIMAL(12,2) NOT NULL,
  precio_venta   DECIMAL(12,2) NOT NULL,
  stock          INT NOT NULL DEFAULT 0,
  stock_minimo   INT NOT NULL DEFAULT 0,      -- alerta de stock bajo
  activo         TINYINT(1) NOT NULL DEFAULT 1,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_repuesto_stock  CHECK (stock >= 0),          -- Regla: stock nunca negativo
  CONSTRAINT chk_repuesto_precio CHECK (costo >= 0 AND precio_venta >= 0),
  INDEX idx_repuestos_nombre (nombre)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- ÓRDENES DE TRABAJO (proceso principal)
-- ---------------------------------------------------------------------
CREATE TABLE ordenes_trabajo (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo               VARCHAR(20) NOT NULL UNIQUE,  -- ej. OT-2026-0001
  vehiculo_id          INT UNSIGNED NOT NULL,
  asesor_id            INT UNSIGNED NOT NULL,
  tecnico_id           INT UNSIGNED NULL,
  estado               ENUM('recibida','diagnostico','cotizada','aprobada','rechazada',
                            'en_reparacion','lista','entregada') NOT NULL DEFAULT 'recibida',
  motivo_ingreso       TEXT NOT NULL,
  kilometraje_ingreso  INT UNSIGNED NOT NULL,
  fecha_ingreso        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_estimada       DATE NULL,
  fecha_entrega        DATETIME NULL,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_ot_estado_fecha (estado, fecha_ingreso),     -- listado de abiertas ordenado por fecha (RNF-06)
  INDEX idx_ot_vehiculo_estado (vehiculo_id, estado),    -- regla R5: ¿el vehículo tiene orden abierta?
  INDEX idx_ot_tecnico_estado (tecnico_id, estado),
  INDEX idx_ot_fecha (fecha_ingreso),
  CONSTRAINT fk_ot_vehiculo FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id),
  CONSTRAINT fk_ot_asesor   FOREIGN KEY (asesor_id)   REFERENCES usuarios(id),
  CONSTRAINT fk_ot_tecnico  FOREIGN KEY (tecnico_id)  REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE diagnosticos (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orden_id     INT UNSIGNED NOT NULL UNIQUE,
  tecnico_id   INT UNSIGNED NOT NULL,
  descripcion  TEXT NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_diag_orden   FOREIGN KEY (orden_id)   REFERENCES ordenes_trabajo(id),
  CONSTRAINT fk_diag_tecnico FOREIGN KEY (tecnico_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE cotizaciones (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orden_id        INT UNSIGNED NOT NULL UNIQUE,
  elaborada_por   INT UNSIGNED NOT NULL,       -- asesor
  estado          ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  respondida_por  INT UNSIGNED NULL,           -- usuario cliente que decide
  respondida_en   DATETIME NULL,
  canal_respuesta ENUM('portal','presencial','telefono') NULL,  -- quién/cómo respondió el cliente
  motivo_rechazo  VARCHAR(255) NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cot_orden     FOREIGN KEY (orden_id)       REFERENCES ordenes_trabajo(id),
  CONSTRAINT fk_cot_asesor    FOREIGN KEY (elaborada_por)  REFERENCES usuarios(id),
  CONSTRAINT fk_cot_respuesta FOREIGN KEY (respondida_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE cotizacion_items (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cotizacion_id    INT UNSIGNED NOT NULL,
  tipo             ENUM('mano_obra','repuesto') NOT NULL,
  repuesto_id      INT UNSIGNED NULL,
  descripcion      VARCHAR(200) NOT NULL,
  cantidad         DECIMAL(10,2) NOT NULL,
  precio_unitario  DECIMAL(12,2) NOT NULL,     -- precio congelado al cotizar
  CONSTRAINT chk_item_cantidad CHECK (cantidad > 0),
  CONSTRAINT chk_item_tipo CHECK (
    (tipo = 'repuesto'  AND repuesto_id IS NOT NULL) OR
    (tipo = 'mano_obra' AND repuesto_id IS NULL)),
  CONSTRAINT fk_item_cot FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE,
  CONSTRAINT fk_item_rep FOREIGN KEY (repuesto_id)   REFERENCES repuestos(id)
) ENGINE=InnoDB;

-- Trazabilidad de stock: entradas de proveedor, salidas por orden, ajustes
CREATE TABLE movimientos_inventario (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  repuesto_id   INT UNSIGNED NOT NULL,
  tipo          ENUM('entrada','salida','ajuste') NOT NULL,
  cantidad      INT NOT NULL,                  -- entrada/salida: positiva · ajuste: con signo
  costo_unitario DECIMAL(12,2) NULL,           -- costo de compra en entradas
  orden_id      INT UNSIGNED NULL,             -- obligatorio en salidas
  proveedor_id  INT UNSIGNED NULL,             -- obligatorio en entradas
  usuario_id    INT UNSIGNED NOT NULL,
  motivo        VARCHAR(255) NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_mov_cantidad CHECK ((tipo = 'ajuste' AND cantidad <> 0) OR (tipo <> 'ajuste' AND cantidad > 0)),
  CONSTRAINT chk_mov_origen CHECK (
    (tipo = 'entrada' AND proveedor_id IS NOT NULL) OR
    (tipo = 'salida'  AND orden_id IS NOT NULL) OR
    (tipo = 'ajuste')),
  INDEX idx_mov_repuesto_fecha (repuesto_id, created_at),
  CONSTRAINT fk_mov_repuesto  FOREIGN KEY (repuesto_id)  REFERENCES repuestos(id),
  CONSTRAINT fk_mov_orden     FOREIGN KEY (orden_id)     REFERENCES ordenes_trabajo(id),
  CONSTRAINT fk_mov_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id),
  CONSTRAINT fk_mov_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- Liquidación SIMULADA (sin pasarela ni factura legal)
CREATE TABLE liquidaciones (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orden_id             INT UNSIGNED NOT NULL UNIQUE,
  subtotal_mano_obra   DECIMAL(12,2) NOT NULL,
  subtotal_repuestos   DECIMAL(12,2) NOT NULL,
  impuestos            DECIMAL(12,2) NOT NULL,
  total                DECIMAL(12,2) NOT NULL,
  metodo_simulado      ENUM('efectivo','tarjeta','transferencia') NOT NULL,
  registrada_por       INT UNSIGNED NOT NULL,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_liq_orden   FOREIGN KEY (orden_id)       REFERENCES ordenes_trabajo(id),
  CONSTRAINT fk_liq_usuario FOREIGN KEY (registrada_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- HISTORIAL Y AUDITORÍA (RF-11)
-- ---------------------------------------------------------------------
CREATE TABLE orden_historial_estados (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orden_id         INT UNSIGNED NOT NULL,
  estado_anterior  VARCHAR(20) NULL,
  estado_nuevo     VARCHAR(20) NOT NULL,
  usuario_id       INT UNSIGNED NOT NULL,
  comentario       VARCHAR(255) NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_hist_orden (orden_id, created_at),
  CONSTRAINT fk_hist_orden   FOREIGN KEY (orden_id)   REFERENCES ordenes_trabajo(id),
  CONSTRAINT fk_hist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE auditoria (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id     INT UNSIGNED NULL,
  accion         VARCHAR(50)  NOT NULL,        -- crear, editar, desactivar, aprobar...
  entidad        VARCHAR(50)  NOT NULL,
  entidad_id     INT UNSIGNED NULL,
  datos_antes    LONGTEXT NULL,                -- JSON, nunca contraseñas ni hashes
  datos_despues  LONGTEXT NULL,
  ip             VARCHAR(45) NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_entidad (entidad, entidad_id),
  INDEX idx_audit_fecha (created_at),
  CONSTRAINT fk_audit_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

INSERT INTO roles (id, nombre) VALUES
  (1,'administrador'),(2,'asesor'),(3,'tecnico'),(4,'cliente');
