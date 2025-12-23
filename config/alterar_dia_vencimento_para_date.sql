-- Script SQL para alterar a coluna dia_vencimento de INTEGER para DATE
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

-- Verificar o tipo atual da coluna dia_vencimento e alterar se necessário
DO $$ 
DECLARE
    col_type TEXT;
BEGIN
    -- Verificar o tipo atual da coluna
    SELECT data_type INTO col_type
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'cobranca'
      AND column_name = 'dia_vencimento';
    
    IF col_type IS NULL THEN
        RAISE NOTICE 'Coluna dia_vencimento não existe na tabela cobranca.';
    ELSIF col_type = 'integer' THEN
        -- Primeiro, definir todos os valores INTEGER como NULL (já que não podemos converter dia do mês para data completa)
        UPDATE cobranca SET dia_vencimento = NULL WHERE dia_vencimento IS NOT NULL;
        
        -- Alterar de INTEGER para DATE
        ALTER TABLE cobranca 
        ALTER COLUMN dia_vencimento TYPE DATE 
        USING NULL;
        
        RAISE NOTICE 'Coluna dia_vencimento alterada de INTEGER para DATE com sucesso.';
        RAISE NOTICE 'ATENÇÃO: Valores existentes foram definidos como NULL. As datas devem ser recalculadas.';
    ELSIF col_type = 'date' THEN
        RAISE NOTICE 'Coluna dia_vencimento já é do tipo DATE. Nenhuma alteração necessária.';
    ELSE
        RAISE NOTICE 'Coluna dia_vencimento é do tipo %. Tipo esperado: DATE.', col_type;
    END IF;
END $$;

-- Verificar se a alteração foi bem-sucedida
DO $$ 
DECLARE
    col_type TEXT;
BEGIN
    SELECT data_type INTO col_type
    FROM information_schema.columns
    WHERE table_schema = 'public'
      AND table_name = 'cobranca'
      AND column_name = 'dia_vencimento';
    
    IF col_type = 'date' THEN
        RAISE NOTICE 'Validação: Coluna dia_vencimento está corretamente configurada como DATE.';
    ELSE
        RAISE WARNING 'Validação: Coluna dia_vencimento não está do tipo DATE. Tipo atual: %.', col_type;
    END IF;
END $$;

