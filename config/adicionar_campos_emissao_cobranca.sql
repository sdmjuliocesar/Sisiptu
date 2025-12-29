-- Script SQL para adicionar campos de emissão na tabela cobranca
-- Este script é idempotente (pode ser executado múltiplas vezes sem erro)
-- Campos: dataemissao (DATE), emissao (CHAR(1) DEFAULT 'N')

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

-- Adicionar campo dataemissao (tipo DATE)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'dataemissao'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN dataemissao DATE;
        RAISE NOTICE 'Coluna dataemissao adicionada com sucesso à tabela cobranca.';
    ELSE
        RAISE NOTICE 'Coluna dataemissao já existe na tabela cobranca.';
    END IF;
END $$;

-- Adicionar campo emissao (tipo CHAR(1) com valor padrão 'N')
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'emissao'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN emissao CHAR(1) DEFAULT 'N';
        RAISE NOTICE 'Coluna emissao adicionada com sucesso à tabela cobranca.';
    ELSE
        RAISE NOTICE 'Coluna emissao já existe na tabela cobranca.';
    END IF;
END $$;

-- Criar índices para melhorar performance (opcional)
CREATE INDEX IF NOT EXISTS idx_cobranca_dataemissao ON cobranca(dataemissao);
CREATE INDEX IF NOT EXISTS idx_cobranca_emissao ON cobranca(emissao);

-- Verificar se todas as colunas foram criadas
DO $$ 
DECLARE
    dataemissao_exists BOOLEAN;
    emissao_exists BOOLEAN;
BEGIN
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'dataemissao'
    ) INTO dataemissao_exists;
    
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.columns
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'emissao'
    ) INTO emissao_exists;
    
    IF dataemissao_exists AND emissao_exists THEN
        RAISE NOTICE 'Validação: Todas as colunas foram criadas com sucesso na tabela cobranca!';
        RAISE NOTICE '  - dataemissao: OK';
        RAISE NOTICE '  - emissao: OK';
    ELSE
        RAISE WARNING 'Validação: Algumas colunas não foram encontradas:';
        IF NOT dataemissao_exists THEN
            RAISE WARNING '  - dataemissao: NÃO ENCONTRADA';
        END IF;
        IF NOT emissao_exists THEN
            RAISE WARNING '  - emissao: NÃO ENCONTRADA';
        END IF;
    END IF;
END $$;

