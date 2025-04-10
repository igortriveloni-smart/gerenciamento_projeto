-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo'
);

-- Tabela de permissões
CREATE TABLE IF NOT EXISTS permissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT
);

-- Tabela de relacionamento entre usuários e permissões
CREATE TABLE IF NOT EXISTS usuario_permissoes (
    usuario_id INT,
    permissao_id INT,
    PRIMARY KEY (usuario_id, permissao_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (permissao_id) REFERENCES permissoes(id) ON DELETE CASCADE
);

-- Inserir permissões básicas
INSERT INTO permissoes (nome, descricao) VALUES
('visualizar_projetos', 'Permite visualizar projetos'),
('criar_projetos', 'Permite criar novos projetos'),
('editar_projetos', 'Permite editar projetos existentes'),
('excluir_projetos', 'Permite excluir projetos'),
('gerenciar_usuarios', 'Permite gerenciar usuários e permissões');

-- Inserir usuário administrador padrão (senha: admin123)
INSERT INTO usuarios (nome, email, senha) VALUES
('Administrador', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Atribuir todas as permissões ao administrador
INSERT INTO usuario_permissoes (usuario_id, permissao_id)
SELECT u.id, p.id
FROM usuarios u
CROSS JOIN permissoes p
WHERE u.email = 'admin@admin.com'; 