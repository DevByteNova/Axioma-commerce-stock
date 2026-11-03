-- ============================================================
-- Migración: relaciones de la tabla ventas con clientes y metodos_pago
-- Pijamas Decare
--
-- Ejecutar sobre una base axioma_bd ya importada (phpMyAdmin > SQL).
-- Los ID_PAGO sin método existente se convierten en NULL antes de crear la FK.
-- Se puede ejecutar más de una vez.
-- ============================================================

ALTER TABLE `ventas`
  MODIFY `ID_PAGO` int(11) DEFAULT NULL;

UPDATE `ventas` v
LEFT JOIN `metodos_pago` mp ON mp.`ID_PAGO` = v.`ID_PAGO`
SET v.`ID_PAGO` = NULL
WHERE v.`ID_PAGO` IS NOT NULL AND mp.`ID_PAGO` IS NULL;

SET @index_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'ventas'
    AND index_name = 'fk_ventas_pago'
);
SET @sql = IF(
  @index_exists = 0,
  'ALTER TABLE `ventas` ADD KEY `fk_ventas_pago` (`ID_PAGO`)',
  'DO 0'
);
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @payment_fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.key_column_usage
  WHERE table_schema = DATABASE()
    AND table_name = 'ventas'
    AND column_name = 'ID_PAGO'
    AND referenced_table_name = 'metodos_pago'
    AND referenced_column_name = 'ID_PAGO'
);
SET @sql = IF(
  @payment_fk_exists = 0,
  'ALTER TABLE `ventas` ADD CONSTRAINT `fk_ventas_pago` FOREIGN KEY (`ID_PAGO`) REFERENCES `metodos_pago` (`ID_PAGO`) ON DELETE SET NULL ON UPDATE CASCADE',
  'DO 0'
);
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @client_index_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'ventas'
    AND index_name = 'fk_ventas_cliente'
);
SET @sql = IF(
  @client_index_exists = 0,
  'ALTER TABLE `ventas` ADD KEY `fk_ventas_cliente` (`ID_CLIENTE`)',
  'DO 0'
);
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @client_fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.key_column_usage
  WHERE table_schema = DATABASE()
    AND table_name = 'ventas'
    AND column_name = 'ID_CLIENTE'
    AND referenced_table_name = 'clientes'
    AND referenced_column_name = 'ID_CLIENTE'
);
SET @sql = IF(
  @client_fk_exists = 0,
  'ALTER TABLE `ventas` ADD CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`ID_CLIENTE`) REFERENCES `clientes` (`ID_CLIENTE`) ON DELETE SET NULL ON UPDATE CASCADE',
  'DO 0'
);
PREPARE migration_statement FROM @sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
