USE okea_db;

INSERT INTO roles (nombre) VALUES 
  ('admin'),
  ('cliente')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Insertar usuario admin de prueba
INSERT INTO usuarios (nombre, email, password) VALUES
  ('Admin Master', 'admin@okea.com', '$2y$10$yW8zz0fMZ6jUuT8TQx12PexUzvZ6Oc4pQFb4O9fE3XWljlFq6fD2K'); 
-- password: 123456 (ya hasheada con password_hash)

-- Relacionar admin con rol "admin"
INSERT INTO usuarios_roles (usuario_id, rol_id)
SELECT u.id, r.id FROM usuarios u, roles r
WHERE u.email = 'admin@okea.com' AND r.nombre = 'admin'
ON DUPLICATE KEY UPDATE usuario_id = usuario_id;
