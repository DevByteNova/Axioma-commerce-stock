-- Conserva la dirección usada en cada compra aunque el cliente la cambie después.
ALTER TABLE facturas
    ADD COLUMN DIRECCION_ENTREGA VARCHAR(255) NULL AFTER ID_CLIENTE;