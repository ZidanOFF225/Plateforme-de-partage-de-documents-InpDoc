-- Création d'un utilisateur administrateur
INSERT INTO users (email, password, nom, prenoms, role, date_creation) 
VALUES (
    'admin@inphb.ci', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Le mot de passe est 'admin'
    'Admin',
    'INPHB',
    'admin',
    NOW()
); 