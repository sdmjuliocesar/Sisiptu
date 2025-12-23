-- Script SQL para remover o campo dia_vencimento da tabela contratos
-- Este script é idempotente (pode ser executado múltiplas vezes sem erro)

-- Verificar se a tabela contratos existe
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'contratos'
    ) THEN
        RAISE EXCEPTION 'Tabela contratos não existe. Execute o script database.sql primeiro.';
    END IF;
END $$;

-- Remover campo dia_vencimento da tabela contratos
DO $$ 
BEGIN
    -- Verificar se o campo dia_vencimento existe
    IF EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'contratos' 
        AND column_name = 'dia_vencimento'
    ) THEN
        -- Remover o campo
        ALTER TABLE contratos DROP COLUMN dia_vencimento;
        RAISE NOTICE 'Campo dia_vencimento removido com sucesso da tabela contratos.';
    ELSE
        RAISE NOTICE 'Campo dia_vencimento não existe na tabela contratos.';
    END IF;
END $$;

-- Verificar se o campo foi removido corretamente
DO $$ 
DECLARE
    col_exists BOOLEAN;
BEGIN
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'contratos' 
        AND column_name = 'dia_vencimento'
    ) INTO col_exists;
    
    IF NOT col_exists THEN
        RAISE NOTICE 'Validação: Campo dia_vencimento foi removido com sucesso da tabela contratos!';
    ELSE
        RAISE WARNING 'Validação: Campo dia_vencimento ainda existe na tabela contratos.';
    END IF;
END $$;

