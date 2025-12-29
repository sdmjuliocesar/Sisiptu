-- Script para adicionar campo banco_id na tabela empreendimentos
-- Este script adiciona a coluna banco_id com foreign key para a tabela bancos

-- Adicionar coluna banco_id na tabela empreendimentos se não existir
DO $$ 
BEGIN
    -- Verificar se a coluna já existe
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name = 'empreendimentos' 
        AND column_name = 'banco_id'
    ) THEN
        -- Adicionar a coluna
        ALTER TABLE empreendimentos 
        ADD COLUMN banco_id INTEGER REFERENCES bancos(id);
        
        -- Criar índice para melhorar performance
        CREATE INDEX IF NOT EXISTS idx_empreendimentos_banco_id ON empreendimentos(banco_id);
        
        RAISE NOTICE 'Coluna banco_id adicionada com sucesso na tabela empreendimentos.';
    ELSE
        RAISE NOTICE 'Coluna banco_id já existe na tabela empreendimentos.';
    END IF;
END $$;














