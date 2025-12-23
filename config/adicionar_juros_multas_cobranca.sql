-- Script SQL para adicionar os campos juros e multas na tabela cobranca
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

-- Adicionar campo juros (tipo DECIMAL para valores monetários)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'juros'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN juros DECIMAL(15, 2) DEFAULT 0;
        RAISE NOTICE 'Coluna juros adicionada com sucesso à tabela cobranca.';
    ELSE
        RAISE NOTICE 'Coluna juros já existe na tabela cobranca.';
    END IF;
END $$;

-- Adicionar campo multas (tipo DECIMAL para valores monetários)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'multas'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN multas DECIMAL(15, 2) DEFAULT 0;
        RAISE NOTICE 'Coluna multas adicionada com sucesso à tabela cobranca.';
    ELSE
        RAISE NOTICE 'Coluna multas já existe na tabela cobranca.';
    END IF;
END $$;

-- Verificar se as colunas foram criadas corretamente
DO $$ 
DECLARE
    juros_exists BOOLEAN;
    multas_exists BOOLEAN;
BEGIN
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'juros'
        AND data_type = 'numeric'
    ) INTO juros_exists;
    
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'multas'
        AND data_type = 'numeric'
    ) INTO multas_exists;
    
    IF juros_exists THEN
        RAISE NOTICE 'Validação: Coluna juros criada com sucesso!';
    ELSE
        RAISE WARNING 'Validação: Coluna juros não foi criada corretamente.';
    END IF;
    
    IF multas_exists THEN
        RAISE NOTICE 'Validação: Coluna multas criada com sucesso!';
    ELSE
        RAISE WARNING 'Validação: Coluna multas não foi criada corretamente.';
    END IF;
END $$;

