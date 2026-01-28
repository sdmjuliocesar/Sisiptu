-- Script SQL validado para adicionar a coluna banco_id na tabela empreendimentos
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

-- Verificar se a tabela bancos existe
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'bancos'
    ) THEN
        RAISE EXCEPTION 'Tabela bancos não existe. Execute o script database.sql primeiro.';
    END IF;
END $$;

-- Adicionar coluna banco_id na tabela empreendimentos se não existir
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'empreendimentos' 
        AND column_name = 'banco_id'
    ) THEN
        ALTER TABLE empreendimentos 
        ADD COLUMN banco_id INTEGER REFERENCES bancos(id);
        
        RAISE NOTICE 'Coluna banco_id adicionada com sucesso na tabela empreendimentos.';
    ELSE
        RAISE NOTICE 'Coluna banco_id já existe na tabela empreendimentos.';
    END IF;
END $$;

-- Criar índice para banco_id se não existir
CREATE INDEX IF NOT EXISTS idx_empreendimentos_banco_id ON empreendimentos(banco_id);

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
        AND column_name = 'banco_id'
        AND data_type = 'integer'
    ) INTO col_exists;
    
    IF col_exists THEN
        RAISE NOTICE 'Validação: Coluna banco_id criada com sucesso!';
    ELSE
        RAISE WARNING 'Validação: Coluna banco_id não foi criada corretamente.';
    END IF;
END $$;




















