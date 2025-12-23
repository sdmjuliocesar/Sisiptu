-- Script SQL para renomear o campo dia_vencimento para dataVencimento na tabela cobranca
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

-- Renomear campo dia_vencimento para dataVencimento na tabela cobranca
DO $$ 
BEGIN
    -- Verificar se o campo dia_vencimento existe e dataVencimento não existe
    -- No PostgreSQL, nomes com aspas são case-sensitive, então verificamos em minúsculas
    IF EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND LOWER(column_name) = 'dia_vencimento'
    ) AND NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND LOWER(column_name) = 'datavencimento'
    ) THEN
        -- Renomear o campo
        -- Usando "dataVencimento" com aspas para manter camelCase (PostgreSQL converte para minúsculas sem aspas)
        ALTER TABLE cobranca RENAME COLUMN dia_vencimento TO "dataVencimento";
        RAISE NOTICE 'Campo dia_vencimento renomeado para dataVencimento com sucesso na tabela cobranca.';
    ELSE
        IF EXISTS (
            SELECT 1 
            FROM information_schema.columns
            WHERE table_schema = 'public'
            AND table_name = 'cobranca' 
            AND LOWER(column_name) = 'datavencimento'
        ) THEN
            RAISE NOTICE 'Campo dataVencimento já existe na tabela cobranca.';
        ELSE
            RAISE NOTICE 'Campo dia_vencimento não existe na tabela cobranca.';
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
        AND LOWER(column_name) = 'datavencimento'
    ) INTO col_exists;
    
    IF col_exists THEN
        RAISE NOTICE 'Validação: Campo dataVencimento existe na tabela cobranca!';
    ELSE
        RAISE WARNING 'Validação: Campo dataVencimento não foi encontrado na tabela cobranca.';
    END IF;
END $$;

