-- Script SQL para criar o banco de dados e tabelas do sistema
-- Execute este script no seu banco de dados PostgreSQL

-- Criar banco de dados (execute como superusuário)
-- CREATE DATABASE sisiptu WITH ENCODING 'UTF8';

-- Conectar ao banco de dados sisiptu antes de executar o restante
-- \c sisiptu

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar função para atualizar data_atualizacao automaticamente
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.data_atualizacao = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Criar trigger para atualizar data_atualizacao
DROP TRIGGER IF EXISTS update_usuarios_updated_at ON usuarios;
CREATE TRIGGER update_usuarios_updated_at
    BEFORE UPDATE ON usuarios
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Tabela de empreendimentos
CREATE TABLE IF NOT EXISTS empreendimentos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    endereco VARCHAR(200),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    uf CHAR(2),
    cep VARCHAR(15),
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trigger para atualizar data_atualizacao dos empreendimentos
DROP TRIGGER IF EXISTS update_empreendimentos_updated_at ON empreendimentos;
CREATE TRIGGER update_empreendimentos_updated_at
    BEFORE UPDATE ON empreendimentos
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Tabela de módulos
CREATE TABLE IF NOT EXISTS modulos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    empreendimento_id INT NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_modulos_empreendimento
        FOREIGN KEY (empreendimento_id)
        REFERENCES empreendimentos (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

-- Trigger para atualizar data_atualizacao dos módulos
DROP TRIGGER IF EXISTS update_modulos_updated_at ON modulos;
CREATE TRIGGER update_modulos_updated_at
    BEFORE UPDATE ON modulos
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Tabela de clientes
CREATE TABLE IF NOT EXISTS clientes (
    id SERIAL PRIMARY KEY,
    cpf_cnpj VARCHAR(20) NOT NULL,
    nome VARCHAR(150) NOT NULL,
    tipo_cadastro VARCHAR(50),
    cep VARCHAR(15),
    endereco VARCHAR(200),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    uf CHAR(2),
    cod_municipio VARCHAR(20),
    data_nasc DATE,
    profissao VARCHAR(100),
    identidade VARCHAR(50),
    estado_civil VARCHAR(50),
    nacionalidade VARCHAR(50),
    regime_casamento VARCHAR(50),
    email VARCHAR(150),
    site VARCHAR(150),
    tel_comercial VARCHAR(20),
    tel_celular1 VARCHAR(20),
    tel_celular2 VARCHAR(20),
    tel_residencial VARCHAR(20),
    cpf_conjuge VARCHAR(20),
    nome_conjuge VARCHAR(150),
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TRIGGER IF EXISTS update_clientes_updated_at ON clientes;
CREATE TRIGGER update_clientes_updated_at
    BEFORE UPDATE ON clientes
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Tabela de bancos (Contas Correntes)
CREATE TABLE IF NOT EXISTS bancos (
    id SERIAL PRIMARY KEY,
    cedente VARCHAR(200),
    cnpj_cpf VARCHAR(20),
    banco VARCHAR(150),
    conta VARCHAR(50),
    agencia VARCHAR(20),
    num_banco VARCHAR(20),
    carteira VARCHAR(50),
    operacao_cc VARCHAR(50),
    apelido VARCHAR(100),
    convenio VARCHAR(50),
    multa_mes DECIMAL(10, 2),
    tarifa_bancaria DECIMAL(10, 2),
    juros_mes DECIMAL(10, 2),
    prazo_devolucao INTEGER,
    codigo_cedente VARCHAR(50),
    operacao_cedente VARCHAR(50),
    emissao_via_banco BOOLEAN DEFAULT FALSE,
    integracao_bancaria BOOLEAN DEFAULT FALSE,
    instrucoes_bancarias TEXT,
    caminho_remessa VARCHAR(500),
    caminho_retorno VARCHAR(500),
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trigger para atualizar data_atualizacao dos bancos
DROP TRIGGER IF EXISTS update_bancos_updated_at ON bancos;
CREATE TRIGGER update_bancos_updated_at
    BEFORE UPDATE ON bancos
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Se a tabela bancos já existir e tiver o campo codigo, removê-lo
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'bancos' AND column_name = 'codigo'
    ) THEN
        ALTER TABLE bancos DROP COLUMN codigo;
    END IF;
END $$;

-- Inserir usuário padrão (senha: admin123)
-- Senha armazenada em texto plano (sem criptografia)
INSERT INTO usuarios (usuario, senha, nome, email) VALUES 
('admin', 'admin123', 'Administrador', 'admin@sisiptu.com')
ON CONFLICT (usuario) DO NOTHING;

