-- Script SQL para adicionar campo empresa_id na tabela empreendimentos
-- Este script é idempotente (pode ser executado múltiplas vezes sem erro)

-- Verificar se a tabela empreendimentos existe
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
END $$;

-- Verificar se a tabela clientes existe (para referência de empresas)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'clientes'
    ) THEN
        RAISE EXCEPTION 'Tabela clientes não existe. Execute o script database.sql primeiro.';
    END IF;
END $$;

-- Adicionar coluna empresa_id na tabela empreendimentos se não existir
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'empreendimentos' 
        AND column_name = 'empresa_id'
    ) THEN
        ALTER TABLE empreendimentos 
        ADD COLUMN empresa_id INTEGER REFERENCES clientes(id);
        
        -- Criar índice para melhorar performance
        CREATE INDEX IF NOT EXISTS idx_empreendimentos_empresa_id ON empreendimentos(empresa_id);
        
        RAISE NOTICE 'Coluna empresa_id adicionada com sucesso na tabela empreendimentos.';
    ELSE
        RAISE NOTICE 'Coluna empresa_id já existe na tabela empreendimentos.';
    END IF;
END $$;

-- Verificar se a coluna foi criada corretamente
DO $$ 
DECLARE
    col_exists BOOLEAN;
BEGIN
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'empreendimentos' 
        AND column_name = 'empresa_id'
    ) INTO col_exists;
    
    IF col_exists THEN
        RAISE NOTICE 'Validação: Coluna empresa_id criada com sucesso na tabela empreendimentos!';
    ELSE
        RAISE WARNING 'Validação: Coluna empresa_id não foi encontrada na tabela empreendimentos.';
    END IF;
END $$;






