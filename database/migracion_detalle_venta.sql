-- ============================================================
-- Migración: detalle de productos por venta
-- Ejecutar sobre una base axioma_bd ya importada (phpMyAdmin > SQL).
-- Es segura de ejecutar más de una vez.
-- ============================================================

CREATE TABLE IF NOT EXISTS `detalle_venta` (
  `ID_DETALLE` int(11) NOT NULL AUTO_INCREMENT,
  `ID_VENTAS` int(11) NOT NULL,
  `ID_PRODUCTO` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal_linea` decimal(10,2) NOT NULL,
  PRIMARY KEY (`ID_DETALLE`),
  KEY `fk_detalle_venta_venta` (`ID_VENTAS`),
  KEY `fk_detalle_venta_producto` (`ID_PRODUCTO`),
  CONSTRAINT `fk_detalle_venta_venta` FOREIGN KEY (`ID_VENTAS`)
    REFERENCES `ventas` (`ID_VENTAS`) ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_venta_producto` FOREIGN KEY (`ID_PRODUCTO`)
    REFERENCES `productos` (`ID_PRODUCTO`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
