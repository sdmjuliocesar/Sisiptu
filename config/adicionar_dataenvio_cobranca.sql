-- Script SQL para adicionar o campo dataenvio na tabela cobranca
-- Este script é idempotente (pode ser executado múltiplas vezes sem erro)

-- Verificar se a tabela cobranca existe
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'cobranca'
    ) THEN
        RAISE EXCEPTION 'Tabela cobranca não existe. Execute o script database.sql primeiro.';
    END IF;
END $$;

-- Adicionar campo dataenvio (tipo DATE)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'dataenvio'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN dataenvio DATE;
        RAISE NOTICE 'Coluna dataenvio adicionada com sucesso.';
    ELSE
        RAISE NOTICE 'Coluna dataenvio já existe.';
    END IF;
END $$;

-- Criar índice para melhorar performance
CREATE INDEX IF NOT EXISTS idx_cobranca_dataenvio ON cobranca(dataenvio);

-- Verificar se a coluna foi criada corretamente
DO $$ 
DECLARE
    col_exists BOOLEAN;
BEGIN
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'dataenvio'
        AND data_type = 'date'
    ) INTO col_exists;
    
    IF col_exists THEN
        RAISE NOTICE 'Validação: Coluna dataenvio criada com sucesso!';
    ELSE
        RAISE WARNING 'Validação: Coluna dataenvio não foi criada corretamente.';
    END IF;
END $$;












