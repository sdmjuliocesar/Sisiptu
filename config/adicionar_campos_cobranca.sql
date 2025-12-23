-- Script SQL para adicionar campos na tabela cobranca
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

-- Adicionar campo Pago (1 posição - BOOLEAN ou CHAR(1))
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'pago'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN pago CHAR(1) DEFAULT 'N';
        RAISE NOTICE 'Coluna pago adicionada com sucesso.';
    ELSE
        RAISE NOTICE 'Coluna pago já existe.';
    END IF;
END $$;

-- Adicionar campo Usuario (20 posições)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'usuario'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN usuario VARCHAR(20);
        RAISE NOTICE 'Coluna usuario adicionada com sucesso.';
    ELSE
        RAISE NOTICE 'Coluna usuario já existe.';
    END IF;
END $$;

-- Adicionar campo Remessa (50 posições)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'remessa'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN remessa VARCHAR(50);
        RAISE NOTICE 'Coluna remessa adicionada com sucesso.';
    ELSE
        RAISE NOTICE 'Coluna remessa já existe.';
    END IF;
END $$;

-- Adicionar campo Retorno (50 posições)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'retorno'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN retorno VARCHAR(50);
        RAISE NOTICE 'Coluna retorno adicionada com sucesso.';
    ELSE
        RAISE NOTICE 'Coluna retorno já existe.';
    END IF;
END $$;

-- Adicionar campo datapagamento (tipo DATE)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'datapagamento'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN datapagamento DATE;
        RAISE NOTICE 'Coluna datapagamento adicionada com sucesso.';
    ELSE
        RAISE NOTICE 'Coluna datapagamento já existe.';
    END IF;
END $$;

-- Adicionar campo databaixa (tipo DATE)
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public'
        AND table_name = 'cobranca' 
        AND column_name = 'databaixa'
    ) THEN
        ALTER TABLE cobranca ADD COLUMN databaixa DATE;
        RAISE NOTICE 'Coluna databaixa adicionada com sucesso.';
    ELSE
        RAISE NOTICE 'Coluna databaixa já existe.';
    END IF;
END $$;

-- Criar índices para melhorar performance
CREATE INDEX IF NOT EXISTS idx_cobranca_pago ON cobranca(pago);
CREATE INDEX IF NOT EXISTS idx_cobranca_usuario ON cobranca(usuario);
CREATE INDEX IF NOT EXISTS idx_cobranca_datapagamento ON cobranca(datapagamento);
CREATE INDEX IF NOT EXISTS idx_cobranca_databaixa ON cobranca(databaixa);

-- Verificar se todas as colunas foram criadas
DO $$ 
DECLARE
    col_count INTEGER;
BEGIN
    SELECT COUNT(*) INTO col_count
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'cobranca'
      AND column_name IN ('pago', 'usuario', 'remessa', 'retorno', 'datapagamento', 'databaixa');
    
    IF col_count = 6 THEN
        RAISE NOTICE 'Validação: Todas as 6 colunas foram criadas com sucesso!';
    ELSE
        RAISE WARNING 'Validação: Apenas % de 6 colunas foram encontradas.', col_count;
    END IF;
END $$;

