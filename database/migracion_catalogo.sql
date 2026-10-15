-- ============================================================
-- Migración: catálogo por categorías e imágenes de productos
-- Pijamas Decare
--
-- Ejecutar sobre una base Axioma_BD ya importada (phpMyAdmin > SQL).
-- Es segura de ejecutar más de una vez.
-- ============================================================

SET NAMES utf8mb4;

-- 1. Tabla de categorías
CREATE TABLE IF NOT EXISTS `categorias` (
  `ID_CATEGORIA` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `slug` varchar(60) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID_CATEGORIA`),
  UNIQUE KEY `uq_categoria_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `categorias` (`ID_CATEGORIA`, `nombre`, `slug`, `descripcion`) VALUES
(1, 'Mujer', 'mujer', 'Pijamas y batas para dama: satín, algodón, franela y encaje.'),
(2, 'Hombre', 'hombre', 'Pijamas para caballero: conjuntos, franela y térmicas.'),
(3, 'Infantil', 'infantil', 'Diseños divertidos y cómodos para niñas y niños.'),
(4, 'Temáticas y ocasiones', 'tematicas', 'Pijamas de temporada, para parejas y fechas especiales.');

-- 2. Nuevas columnas en productos
ALTER TABLE `productos`
  ADD COLUMN IF NOT EXISTS `ID_CATEGORIA` int(11) DEFAULT NULL AFTER `talla`,
  ADD COLUMN IF NOT EXISTS `imagen` varchar(255) DEFAULT NULL AFTER `ID_CATEGORIA`;

ALTER TABLE `productos`
  ADD KEY IF NOT EXISTS `fk_producto_categoria` (`ID_CATEGORIA`);

ALTER TABLE `productos`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY IF NOT EXISTS (`ID_CATEGORIA`)
  REFERENCES `categorias` (`ID_CATEGORIA`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 3. Clasificar los productos existentes
UPDATE `productos` SET `ID_CATEGORIA` = 1 WHERE `ID_PRODUCTO` IN (1, 2, 3, 6, 8, 9, 12, 19) AND `ID_CATEGORIA` IS NULL;
UPDATE `productos` SET `ID_CATEGORIA` = 2 WHERE `ID_PRODUCTO` IN (4, 5, 7, 10, 17, 18, 20) AND `ID_CATEGORIA` IS NULL;
UPDATE `productos` SET `ID_CATEGORIA` = 3 WHERE `ID_PRODUCTO` IN (13, 16) AND `ID_CATEGORIA` IS NULL;
UPDATE `productos` SET `ID_CATEGORIA` = 4 WHERE `ID_PRODUCTO` IN (11, 14, 15) AND `ID_CATEGORIA` IS NULL;
