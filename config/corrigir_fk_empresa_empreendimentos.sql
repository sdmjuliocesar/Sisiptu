-- Script SQL para corrigir a foreign key fk_empreendimentos_empresa
-- Este script verifica e corrige a foreign key se estiver referenciando a tabela errada

-- Verificar a foreign key atual
DO $$ 
DECLARE
    fk_name TEXT;
    fk_table TEXT;
BEGIN
    -- Buscar o nome da constraint e a tabela referenciada
    SELECT 
        tc.constraint_name,
        ccu.table_name AS foreign_table_name
    INTO fk_name, fk_table
    FROM information_schema.table_constraints AS tc 
    JOIN information_schema.key_column_usage AS kcu
      ON tc.constraint_name = kcu.constraint_name
      AND tc.table_schema = kcu.table_schema
    JOIN information_schema.constraint_column_usage AS ccu
      ON ccu.constraint_name = tc.constraint_name
      AND ccu.table_schema = tc.table_schema
    WHERE tc.constraint_type = 'FOREIGN KEY' 
      AND tc.table_name = 'empreendimentos'
      AND kcu.column_name = 'empresa_id';
    
    IF fk_name IS NOT NULL THEN
        RAISE NOTICE 'Foreign key encontrada: % referencia a tabela: %', fk_name, fk_table;
        
        -- Se a foreign key referencia a tabela errada (empresas), remover e recriar
        IF fk_table = 'empresas' THEN
            RAISE NOTICE 'Foreign key incorreta detectada. Removendo e recriando...';
            
            -- Remover a foreign key incorreta
            EXECUTE format('ALTER TABLE empreendimentos DROP CONSTRAINT IF EXISTS %I', fk_name);
            
            -- Recriar a foreign key correta apontando para clientes
            ALTER TABLE empreendimentos 
            ADD CONSTRAINT fk_empreendimentos_empresa 
            FOREIGN KEY (empresa_id) 
            REFERENCES clientes(id) 
            ON DELETE SET NULL 
            ON UPDATE CASCADE;
            
            RAISE NOTICE 'Foreign key corrigida com sucesso! Agora referencia a tabela clientes.';
        ELSIF fk_table = 'clientes' THEN
            RAISE NOTICE 'Foreign key já está correta, referenciando a tabela clientes.';
        ELSE
            RAISE WARNING 'Foreign key referencia uma tabela inesperada: %. Verifique manualmente.', fk_table;
        END IF;
    ELSE
        RAISE NOTICE 'Nenhuma foreign key encontrada para empresa_id. Criando nova...';
        
        -- Criar a foreign key se não existir
        ALTER TABLE empreendimentos 
        ADD CONSTRAINT fk_empreendimentos_empresa 
        FOREIGN KEY (empresa_id) 
        REFERENCES clientes(id) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE;
        
        RAISE NOTICE 'Foreign key criada com sucesso!';
    END IF;
END $$;

-- Verificar se a correção foi aplicada
DO $$ 
DECLARE
    fk_table TEXT;
BEGIN
    SELECT 
        ccu.table_name AS foreign_table_name
    INTO fk_table
    FROM information_schema.table_constraints AS tc 
    JOIN information_schema.key_column_usage AS kcu
      ON tc.constraint_name = kcu.constraint_name
      AND tc.table_schema = kcu.table_schema
    JOIN information_schema.constraint_column_usage AS ccu
      ON ccu.constraint_name = tc.constraint_name
      AND ccu.table_schema = tc.table_schema
    WHERE tc.constraint_type = 'FOREIGN KEY' 
      AND tc.table_name = 'empreendimentos'
      AND kcu.column_name = 'empresa_id';
    
    IF fk_table = 'clientes' THEN
        RAISE NOTICE 'Validação: Foreign key corrigida com sucesso! Agora referencia a tabela clientes.';
    ELSE
        RAISE WARNING 'Validação: Foreign key ainda referencia: %. Verifique manualmente.', fk_table;
    END IF;
END $$;

