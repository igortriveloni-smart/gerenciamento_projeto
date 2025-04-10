CREATE DATABASE IF NOT EXISTS gerenciamento_projeto;
USE gerenciamento_projeto;

CREATE TABLE IF NOT EXISTS projetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    objetivo TEXT NOT NULL,
    escopo TEXT NOT NULL,
    gestor VARCHAR(255) NOT NULL,
    ponto_focal VARCHAR(255) NOT NULL,
    data_inicio DATE NOT NULL,
    data_conclusao DATE NOT NULL,
    status VARCHAR(50) NOT NULL,
    imagem VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS etapas_cronograma (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT NOT NULL,
    etapa VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('Infraestrutura', 'Desenvolvimento', 'Análise', 'Treinamento', 'Documentação', 'Outros', 'Teste e Validação', 'Configuração e testes') NOT NULL,
    responsavel VARCHAR(255) NOT NULL,
    data_inicio DATE NOT NULL,
    data_termino_planejada DATE NOT NULL,
    data_termino_real DATE,
    status ENUM('Não Iniciado', 'Em Andamento', 'Concluído', 'Atrasado', 'Cancelado') DEFAULT 'Não Iniciado',
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tarefas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT NOT NULL,
    etapa_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    sprint INT CHECK (sprint BETWEEN 1 AND 10),
    responsavel VARCHAR(255),
    data_inicio DATE,
    data_termino_planejada DATE,
    data_termino_real DATE,
    dias_uteis INT,
    status ENUM('Não Iniciado', 'Em Andamento', 'Concluído', 'Atrasado', 'Cancelado') DEFAULT 'Não Iniciado',
    comentarios TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    FOREIGN KEY (etapa_id) REFERENCES etapas_cronograma(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS stakeholders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    funcao_area VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(255),
    observacao TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reunioes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT NOT NULL,
    data_reuniao DATE NOT NULL,
    participantes TEXT NOT NULL,
    principais_decisoes TEXT,
    proximas_acoes TEXT,
    responsavel VARCHAR(255) NOT NULL,
    link_video VARCHAR(255),
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE
); 