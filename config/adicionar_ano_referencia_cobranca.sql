-- Script para adicionar o campo ano_referencia na tabela cobranca
-- Execute este script no banco de dados PostgreSQL

-- Verificar se a coluna ano_referencia já existe antes de adicionar
DO $$ 
BEGIN
    -- Verificar se a coluna não existe
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
          AND table_name = 'cobranca' 
          AND column_name = 'ano_referencia'
    ) THEN
        -- Adicionar a coluna ano_referencia
        ALTER TABLE cobranca 
        ADD COLUMN ano_referencia INTEGER;
        
        RAISE NOTICE 'Coluna ano_referencia adicionada com sucesso à tabela cobranca.';
    ELSE
        RAISE NOTICE 'Coluna ano_referencia já existe na tabela cobranca.';
    END IF;
END $$;

-- Criar índice para melhorar performance de consultas por ano_referencia
CREATE INDEX IF NOT EXISTS idx_cobranca_ano_referencia ON cobranca(ano_referencia);








