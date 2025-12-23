-- Script SQL para renomear o campo "Datavencimento" para "datavencimento" (minúsculo) na tabela cobranca
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

-- Renomear campo "Datavencimento" para "datavencimento" (minúsculo) na tabela cobranca
DO $$ 
BEGIN
    -- Verificar se o campo "Datavencimento" existe (com D maiúsculo)
    IF EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'Datavencimento'
    ) AND NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'datavencimento'
    ) THEN
        -- Renomear o campo para minúsculo (sem aspas, será convertido para minúsculo automaticamente)
        ALTER TABLE cobranca RENAME COLUMN "Datavencimento" TO datavencimento;
        RAISE NOTICE 'Campo Datavencimento renomeado para datavencimento com sucesso na tabela cobranca.';
    ELSE
        IF EXISTS (
            SELECT 1 
            FROM information_schema.columns
            WHERE table_schema = 'public'
            AND table_name = 'cobranca' 
            AND column_name = 'datavencimento'
        ) THEN
            RAISE NOTICE 'Campo datavencimento já existe na tabela cobranca.';
        ELSE
            RAISE NOTICE 'Campo Datavencimento não existe na tabela cobranca.';
        END IF;
    END IF;
END $$;

-- Verificar se o campo foi renomeado corretamente
DO $$ 
DECLARE
    col_exists BOOLEAN;
BEGIN
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'datavencimento'
    ) INTO col_exists;
    
    IF col_exists THEN
        RAISE NOTICE 'Validação: Campo datavencimento existe na tabela cobranca!';
    ELSE
        RAISE WARNING 'Validação: Campo datavencimento não foi encontrado na tabela cobranca.';
    END IF;
END $$;

