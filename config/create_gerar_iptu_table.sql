-- Script SQL para criar a tabela gerar_iptu se não existir
-- Este script é idempotente (pode ser executado múltiplas vezes sem erro)

-- Verificar se as tabelas dependentes existem
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'empreendimentos'
    ) THEN
        RAISE EXCEPTION 'Tabela empreendimentos não existe. Execute o script database.sql primeiro.';
    END IF;
    
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'modulos'
    ) THEN
        RAISE EXCEPTION 'Tabela modulos não existe. Execute o script database.sql primeiro.';
    END IF;
END $$;

-- Verificar se a tabela gerar_iptu existe, se não, criar
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'gerar_iptu'
    ) THEN
        -- Criar a tabela gerar_iptu
        CREATE TABLE gerar_iptu (
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
        
        RAISE NOTICE 'Tabela gerar_iptu criada com sucesso.';
    ELSE
        RAISE NOTICE 'Tabela gerar_iptu já existe.';
    END IF;
END $$;

-- Criar ou recriar o trigger para atualizar data_atualizacao
DROP TRIGGER IF EXISTS update_gerar_iptu_updated_at ON gerar_iptu;

-- Verificar se a função update_updated_at_column existe
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM pg_proc p
        JOIN pg_namespace n ON p.pronamespace = n.oid
        WHERE n.nspname = 'public' 
        AND p.proname = 'update_updated_at_column'
    ) THEN
        -- Criar a função se não existir
        CREATE OR REPLACE FUNCTION update_updated_at_column()
        RETURNS TRIGGER AS $$
        BEGIN
            NEW.data_atualizacao = CURRENT_TIMESTAMP;
            RETURN NEW;
        END;
        $$ language 'plpgsql';
        
        RAISE NOTICE 'Função update_updated_at_column criada.';
    END IF;
END $$;

-- Criar o trigger
CREATE TRIGGER update_gerar_iptu_updated_at
    BEFORE UPDATE ON gerar_iptu
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Criar índices para melhorar performance
CREATE INDEX IF NOT EXISTS idx_gerar_iptu_empreendimento ON gerar_iptu(empreendimento_id);
CREATE INDEX IF NOT EXISTS idx_gerar_iptu_modulo ON gerar_iptu(modulo_id);
CREATE INDEX IF NOT EXISTS idx_gerar_iptu_contrato_codigo ON gerar_iptu(contrato_codigo);
CREATE INDEX IF NOT EXISTS idx_gerar_iptu_ano_referencia ON gerar_iptu(ano_referencia);
CREATE INDEX IF NOT EXISTS idx_gerar_iptu_ativo ON gerar_iptu(ativo);

