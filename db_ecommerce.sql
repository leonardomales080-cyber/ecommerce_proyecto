-- Eliminar si ya existe de forma parcial y recrearlo correctamente
DELETE FROM usuarios WHERE email = 'admin@ecommerce.com';

INSERT INTO usuarios (id, rol_id, cedula_ruc, nombres, apellidos, email, PASSWORD, telefono, direccion) 
VALUES (1, 1, '1001001001001', 'Administrador', 'General', 'admin@ecommerce.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', '0999999999', 'Oficina Central');