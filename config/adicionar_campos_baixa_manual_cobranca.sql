-- Script SQL para adicionar campos de baixa manual na tabela cobranca
-- Este script é idempotente (pode ser executado múltiplas vezes sem erro)
-- Campos: tarifa_bancaria, desconto, forma_pagamento, local_pagamento

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

-- Adicionar campo tarifa_bancaria (tipo DECIMAL para valores monetários)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'tarifa_bancaria'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN tarifa_bancaria DECIMAL(15, 2) DEFAULT 0;
        RAISE NOTICE 'Coluna tarifa_bancaria adicionada com sucesso à tabela cobranca.';
    ELSE
        RAISE NOTICE 'Coluna tarifa_bancaria já existe na tabela cobranca.';
    END IF;
END $$;

-- Adicionar campo desconto (tipo DECIMAL para valores monetários)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'desconto'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN desconto DECIMAL(15, 2) DEFAULT 0;
        RAISE NOTICE 'Coluna desconto adicionada com sucesso à tabela cobranca.';
    ELSE
        RAISE NOTICE 'Coluna desconto já existe na tabela cobranca.';
    END IF;
END $$;

-- Adicionar campo forma_pagamento (tipo VARCHAR para armazenar a forma de pagamento)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'forma_pagamento'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN forma_pagamento VARCHAR(50);
        RAISE NOTICE 'Coluna forma_pagamento adicionada com sucesso à tabela cobranca.';
    ELSE
        RAISE NOTICE 'Coluna forma_pagamento já existe na tabela cobranca.';
    END IF;
END $$;

-- Adicionar campo local_pagamento (tipo VARCHAR para armazenar o local de pagamento)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'local_pagamento'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN local_pagamento VARCHAR(50);
        RAISE NOTICE 'Coluna local_pagamento adicionada com sucesso à tabela cobranca.';
    ELSE
        RAISE NOTICE 'Coluna local_pagamento já existe na tabela cobranca.';
    END IF;
END $$;

-- Criar índices para melhorar performance (opcional)
CREATE INDEX IF NOT EXISTS idx_cobranca_forma_pagamento ON cobranca(forma_pagamento);
CREATE INDEX IF NOT EXISTS idx_cobranca_local_pagamento ON cobranca(local_pagamento);

-- Verificar se todas as colunas foram criadas
DO $$ 
DECLARE
    tarifa_exists BOOLEAN;
    desconto_exists BOOLEAN;
    forma_exists BOOLEAN;
    local_exists BOOLEAN;
BEGIN
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'tarifa_bancaria'
    ) INTO tarifa_exists;
    
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'desconto'
    ) INTO desconto_exists;
    
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'forma_pagamento'
    ) INTO forma_exists;
    
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'local_pagamento'
    ) INTO local_exists;
    
    IF tarifa_exists AND desconto_exists AND forma_exists AND local_exists THEN
        RAISE NOTICE 'Validação: Todas as colunas foram criadas com sucesso na tabela cobranca!';
        RAISE NOTICE '  - tarifa_bancaria: OK';
        RAISE NOTICE '  - desconto: OK';
        RAISE NOTICE '  - forma_pagamento: OK';
        RAISE NOTICE '  - local_pagamento: OK';
    ELSE
        RAISE WARNING 'Validação: Algumas colunas não foram encontradas:';
        IF NOT tarifa_exists THEN
            RAISE WARNING '  - tarifa_bancaria: NÃO ENCONTRADA';
        END IF;
        IF NOT desconto_exists THEN
            RAISE WARNING '  - desconto: NÃO ENCONTRADA';
        END IF;
        IF NOT forma_exists THEN
            RAISE WARNING '  - forma_pagamento: NÃO ENCONTRADA';
        END IF;
        IF NOT local_exists THEN
            RAISE WARNING '  - local_pagamento: NÃO ENCONTRADA';
        END IF;
    END IF;
END $$;


