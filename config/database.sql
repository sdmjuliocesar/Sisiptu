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
    banco_id INTEGER REFERENCES bancos(id),
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

-- Tabela para Gerar IPTU
CREATE TABLE IF NOT EXISTS gerar_iptu (
    id SERIAL PRIMARY KEY,
    empreendimento_id INTEGER REFERENCES empreendimentos(id),
    modulo_id INTEGER REFERENCES modulos(id),
    contrato_codigo VARCHAR(50),
    contrato_descricao VARCHAR(200),
    ano_referencia INTEGER,
    valor_total_iptu DECIMAL(15, 2),
    parcelamento_quantidade INTEGER,
    parcelamento_tipo VARCHAR(100),
    primeira_vencimento DATE,
    observacoes TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trigger para atualizar data_atualizacao do gerar_iptu
DROP TRIGGER IF EXISTS update_gerar_iptu_updated_at ON gerar_iptu;
CREATE TRIGGER update_gerar_iptu_updated_at
    BEFORE UPDATE ON gerar_iptu
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Tabela para Contratos
CREATE TABLE IF NOT EXISTS contratos (
    id SERIAL PRIMARY KEY,
    empreendimento_id INTEGER REFERENCES empreendimentos(id),
    modulo_id INTEGER REFERENCES modulos(id),
    cliente_id INTEGER REFERENCES clientes(id),
    area VARCHAR(200),
    modulo VARCHAR(200),
    submodulo VARCHAR(200),
    contrato VARCHAR(200),
    inscricao VARCHAR(100),
    metragem DECIMAL(15, 2),
    vrm2 DECIMAL(15, 2),
    valor_venal DECIMAL(15, 2),
    aliquota DECIMAL(15, 2),
    tx_coleta_lixo DECIMAL(15, 2),
    desconto_a_vista DECIMAL(15, 2),
    dia_vencimento INTEGER,
    parcelamento INTEGER,
    obs TEXT,
    valor_mensal DECIMAL(15, 2),
    valor_anual DECIMAL(15, 2),
    cpf_cnpj VARCHAR(20),
    situacao VARCHAR(100),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Trigger para atualizar data_atualizacao dos contratos
DROP TRIGGER IF EXISTS update_contratos_updated_at ON contratos;
CREATE TRIGGER update_contratos_updated_at
    BEFORE UPDATE ON contratos
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Remover campo codigo_iptu da tabela contratos se existir
DO $$ 
BEGIN
    IF EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name = 'contratos' 
        AND column_name = 'codigo_iptu'
    ) THEN
        ALTER TABLE contratos DROP COLUMN codigo_iptu;
    END IF;
END $$;

-- Adicionar campos empreendimento_id e modulo_id se não existirem
DO $$ 
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'contratos' AND column_name = 'empreendimento_id') THEN
        ALTER TABLE contratos ADD COLUMN empreendimento_id INTEGER REFERENCES empreendimentos(id);
    END IF;
    
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'contratos' AND column_name = 'modulo_id') THEN
        ALTER TABLE contratos ADD COLUMN modulo_id INTEGER REFERENCES modulos(id);
    END IF;
END $$;

-- Índices para melhorar performance
CREATE INDEX IF NOT EXISTS idx_contratos_cpf_cnpj ON contratos(cpf_cnpj);
CREATE INDEX IF NOT EXISTS idx_contratos_codigo_iptu ON contratos(codigo_iptu);
CREATE INDEX IF NOT EXISTS idx_contratos_inscricao ON contratos(inscricao);
CREATE INDEX IF NOT EXISTS idx_contratos_situacao ON contratos(situacao);
CREATE INDEX IF NOT EXISTS idx_contratos_empreendimento_modulo_contrato ON contratos(empreendimento_id, modulo_id, contrato);

-- Adicionar coluna banco_id na tabela empreendimentos se não existir (para bancos já criados)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name = 'empreendimentos' 
        AND column_name = 'banco_id'
    ) THEN
        ALTER TABLE empreendimentos ADD COLUMN banco_id INTEGER REFERENCES bancos(id);
    END IF;
END $$;

-- Criar índice para banco_id se não existir
CREATE INDEX IF NOT EXISTS idx_empreendimentos_banco_id ON empreendimentos(banco_id);

-- Tabela para Cobrança
CREATE TABLE IF NOT EXISTS cobranca (
    id SERIAL PRIMARY KEY,
    empreendimento_id INTEGER REFERENCES empreendimentos(id),
    modulo_id INTEGER REFERENCES modulos(id),
    contrato VARCHAR(200),
    cpf_cnpj VARCHAR(20),
    cliente_nome VARCHAR(200),
    area VARCHAR(200),
    metragem DECIMAL(15, 2),
    vrm2 DECIMAL(15, 2),
    inscricao VARCHAR(100),
    valor_venal DECIMAL(15, 2),
    aliquota DECIMAL(15, 2),
    tx_coleta_lixo DECIMAL(15, 2),
    dia_vencimento INTEGER,
    desconto_vista DECIMAL(15, 2),
    valor_anual DECIMAL(15, 2),
    parcelamento INTEGER,
    valor_mensal DECIMAL(15, 2),
    data_vencimento DATE,
    observacao TEXT,
    situacao VARCHAR(100),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Criar constraint única composta na tabela contratos primeiro (necessário para a FK composta)
-- Nota: Esta constraint só será criada se não houver duplicatas na tabela
DO $$ 
BEGIN
    -- Verificar se já existe a constraint
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'contratos_empreendimento_modulo_contrato_key'
    ) THEN
        -- Verificar se há duplicatas antes de criar a constraint
        IF NOT EXISTS (
            SELECT empreendimento_id, modulo_id, contrato, COUNT(*)
            FROM contratos
            WHERE empreendimento_id IS NOT NULL 
              AND modulo_id IS NOT NULL 
              AND contrato IS NOT NULL
            GROUP BY empreendimento_id, modulo_id, contrato
            HAVING COUNT(*) > 1
        ) THEN
            ALTER TABLE contratos 
            ADD CONSTRAINT contratos_empreendimento_modulo_contrato_key 
            UNIQUE (empreendimento_id, modulo_id, contrato);
        ELSE
            RAISE NOTICE 'Não foi possível criar a constraint única: existem registros duplicados na tabela contratos.';
        END IF;
    END IF;
END $$;

-- Criar constraint única na tabela clientes para cpf_cnpj (necessário para a FK)
DO $$ 
BEGIN
    -- Criar constraint única na tabela clientes para cpf_cnpj se não existir
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'clientes_cpf_cnpj_key'
    ) THEN
        ALTER TABLE clientes 
        ADD CONSTRAINT clientes_cpf_cnpj_key 
        UNIQUE (cpf_cnpj);
    END IF;
END $$;

-- Adicionar chaves estrangeiras após criar as constraints
DO $$ 
BEGIN
    -- Adicionar foreign key composta para contratos (empreendimento_id, modulo_id, contrato)
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'fk_cobranca_contrato'
    ) THEN
        ALTER TABLE cobranca 
        ADD CONSTRAINT fk_cobranca_contrato 
        FOREIGN KEY (empreendimento_id, modulo_id, contrato) 
        REFERENCES contratos(empreendimento_id, modulo_id, contrato);
    END IF;
    
    -- Adicionar foreign key para clientes via cpf_cnpj
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'fk_cobranca_cliente'
    ) THEN
        ALTER TABLE cobranca 
        ADD CONSTRAINT fk_cobranca_cliente 
        FOREIGN KEY (cpf_cnpj) 
        REFERENCES clientes(cpf_cnpj);
    END IF;
END $$;

-- Trigger para atualizar data_atualizacao da cobranca
DROP TRIGGER IF EXISTS update_cobranca_updated_at ON cobranca;
CREATE TRIGGER update_cobranca_updated_at
    BEFORE UPDATE ON cobranca
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Adicionar campo data_vencimento na tabela cobranca se não existir
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name = 'cobranca' 
        AND column_name = 'data_vencimento'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN data_vencimento DATE;
    END IF;
END $$;

-- Índices para melhorar performance
CREATE INDEX IF NOT EXISTS idx_cobranca_empreendimento_id ON cobranca(empreendimento_id);
CREATE INDEX IF NOT EXISTS idx_cobranca_modulo_id ON cobranca(modulo_id);
CREATE INDEX IF NOT EXISTS idx_cobranca_contrato ON cobranca(contrato);
CREATE INDEX IF NOT EXISTS idx_cobranca_cpf_cnpj ON cobranca(cpf_cnpj);
CREATE INDEX IF NOT EXISTS idx_cobranca_empreendimento_modulo_contrato ON cobranca(empreendimento_id, modulo_id, contrato);
CREATE INDEX IF NOT EXISTS idx_cobranca_data_vencimento ON cobranca(data_vencimento);

-- Inserir usuário padrão (senha: admin123)
-- Senha armazenada em texto plano (sem criptografia)
INSERT INTO usuarios (usuario, senha, nome, email) VALUES 
('admin', 'admin123', 'Administrador', 'admin@sisiptu.com')
ON CONFLICT (usuario) DO NOTHING;

