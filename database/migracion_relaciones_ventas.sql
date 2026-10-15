-- ============================================================
-- Migración: relaciones de la tabla ventas con clientes y metodos_pago
-- Pijamas Decare
--
-- Ejecutar sobre una base axioma_bd ya importada (phpMyAdmin > SQL).
-- Es segura de ejecutar más de una vez.
-- ============================================================

ALTER TABLE `ventas`
  ADD KEY IF NOT EXISTS `fk_ventas_cliente` (`ID_CLIENTE`),
  ADD KEY IF NOT EXISTS `fk_ventas_pago` (`ID_PAGO`);

ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_cliente` FOREIGN KEY IF NOT EXISTS (`ID_CLIENTE`)
  REFERENCES `clientes` (`ID_CLIENTE`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `ventas`
  ADD CONSTRAINT `fk_ventas_pago` FOREIGN KEY IF NOT EXISTS (`ID_PAGO`)
  REFERENCES `metodos_pago` (`ID_PAGO`) ON DELETE SET NULL ON UPDATE CASCADE;
