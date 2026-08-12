-- PostgreSQL schema converted from conexion.php MySQL definitions
-- Run this in Supabase SQL editor or psql to create the tables

CREATE TABLE IF NOT EXISTS usuarios (
  id SERIAL PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  primer_apellido VARCHAR(255),
  segundo_apellido VARCHAR(255),
  correo VARCHAR(255) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,
  pais VARCHAR(100),
  fecha_registro TIMESTAMP DEFAULT now()
);

CREATE TABLE IF NOT EXISTS productos (
  id SERIAL PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  descripcion TEXT,
  origen VARCHAR(100),
  precio NUMERIC(10,2) DEFAULT 0,
  rating NUMERIC(3,1) DEFAULT 0,
  stock INT DEFAULT 0,
  imagen VARCHAR(255),
  fecha_creacion TIMESTAMP DEFAULT now()
);

CREATE TABLE IF NOT EXISTS pedidos (
  id SERIAL PRIMARY KEY,
  usuario_id INT REFERENCES usuarios(id) ON DELETE SET NULL,
  subtotal NUMERIC(10,2) DEFAULT 0,
  impuestos NUMERIC(10,2) DEFAULT 0,
  total NUMERIC(10,2) DEFAULT 0,
  fecha TIMESTAMP DEFAULT now()
);

CREATE TABLE IF NOT EXISTS pedido_detalle (
  id SERIAL PRIMARY KEY,
  pedido_id INT REFERENCES pedidos(id) ON DELETE CASCADE,
  producto_id INT REFERENCES productos(id) ON DELETE CASCADE,
  cantidad INT DEFAULT 1,
  precio_unitario NUMERIC(10,2) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS contactos (
  id SERIAL PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  primer_apellido VARCHAR(255),
  segundo_apellido VARCHAR(255),
  correo VARCHAR(255) NOT NULL,
  contrasena VARCHAR(255),
  pais VARCHAR(100),
  mensaje TEXT,
  fecha TIMESTAMP DEFAULT now()
);

-- Optional: create indexes to match MySQL performance hints
CREATE INDEX IF NOT EXISTS idx_pedidos_usuario_id ON pedidos(usuario_id);
CREATE INDEX IF NOT EXISTS idx_pedido_detalle_pedido_id ON pedido_detalle(pedido_id);
CREATE INDEX IF NOT EXISTS idx_pedido_detalle_producto_id ON pedido_detalle(producto_id);
