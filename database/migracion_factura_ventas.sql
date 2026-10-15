-- ============================================================
-- Migración: cada venta pertenece a una factura (ventas.ID_FACTURA)
-- Pijamas Decare
--
-- Corrige que la factura mostrara productos de otras compras del mismo
-- cliente. Ejecutar sobre una base axioma_bd ya importada (phpMyAdmin > SQL).
-- Es segura de ejecutar más de una vez.
-- ============================================================

ALTER TABLE `ventas`
  ADD COLUMN IF NOT EXISTS `ID_FACTURA` int(11) DEFAULT NULL AFTER `ID_PAGO`;

ALTER TABLE `ventas`
  ADD KEY IF NOT EXISTS `fk_ventas_factura` (`ID_FACTURA`);

ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_factura` FOREIGN KEY IF NOT EXISTS (`ID_FACTURA`)
  REFERENCES `facturas` (`ID_FACTURA`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Asociar las ventas antiguas: misma persona y mismo día que la factura.
-- Si un cliente tiene varias facturas el mismo día, se usa la más reciente.
UPDATE `ventas` v
JOIN (
  SELECT ID_CLIENTE, FECHA, MAX(ID_FACTURA) AS ID_FACTURA
  FROM `facturas`
  GROUP BY ID_CLIENTE, FECHA
) f ON f.ID_CLIENTE = v.ID_CLIENTE AND f.FECHA = DATE(v.FECHA_VENTA)
SET v.ID_FACTURA = f.ID_FACTURA
WHERE v.ID_FACTURA IS NULL;
