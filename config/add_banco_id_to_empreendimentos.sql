-- Script para adicionar a coluna banco_id na tabela empreendimentos
-- Execute este script se a coluna banco_id não existir

-- Adicionar coluna banco_id na tabela empreendimentos se não existir
DO $$ 
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_name = 'empreendimentos' 
        AND column_name = 'banco_id'
    ) THEN
        ALTER TABLE empreendimentos ADD COLUMN banco_id INTEGER REFERENCES bancos(id);
        RAISE NOTICE 'Coluna banco_id adicionada com sucesso na tabela empreendimentos.';
    ELSE
        RAISE NOTICE 'Coluna banco_id já existe na tabela empreendimentos.';
    END IF;
END $$;

-- Criar índice para banco_id se não existir
CREATE INDEX IF NOT EXISTS idx_empreendimentos_banco_id ON empreendimentos(banco_id);










