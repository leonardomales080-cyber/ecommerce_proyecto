DELETE FROM usuarios WHERE correo = 'admin@ecommerce.com';

INSERT INTO usuarios (rol_id, cedula, nombres, apellidos, correo, PASSWORD, telefono, direccion) 
VALUES (1, '0999999999', 'Administrador', 'General', 'admin@ecommerce.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0999999999', 'Oficina Central');