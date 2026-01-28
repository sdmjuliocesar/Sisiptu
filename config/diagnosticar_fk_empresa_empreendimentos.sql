-- Script SQL para diagnosticar e corrigir o erro de foreign key fk_empreendimentos_empresa
-- Erro: Chave (empresa_id)=(9) não está presente na tabela "empresas"
-- Este script verifica a estrutura atual e corrige se necessário

-- ============================================
-- PARTE 1: DIAGNÓSTICO
-- ============================================

DO $$ 
DECLARE
    fk_name TEXT;
    fk_table TEXT;
    fk_column TEXT;
    ref_table TEXT;
    ref_column TEXT;
    empresa_count INTEGER;
    empresa_id_9_exists BOOLEAN;
    tabela_empresas_exists BOOLEAN;
BEGIN
    RAISE NOTICE '========================================';
    RAISE NOTICE 'DIAGNÓSTICO DA FOREIGN KEY';
    RAISE NOTICE '========================================';
    
    -- Verificar se a tabela "empresas" existe (não deveria existir)
    SELECT EXISTS (
        SELECT 1 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'empresas'
    ) INTO tabela_empresas_exists;
    
    IF tabela_empresas_exists THEN
        RAISE WARNING 'ATENÇÃO: Tabela "empresas" existe no banco de dados!';
        RAISE NOTICE 'Esta tabela não deveria existir. As empresas estão na tabela "clientes" com tipo_cadastro = ''Empresa''.';
    ELSE
        RAISE NOTICE 'OK: Tabela "empresas" não existe (correto).';
    END IF;
    
    -- Buscar informações da foreign key atual
    SELECT 
        tc.constraint_name,
        kcu.column_name,
        ccu.table_name AS foreign_table_name,
        ccu.column_name AS foreign_column_name
    INTO fk_name, fk_column, ref_table, ref_column
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
        RAISE NOTICE '';
        RAISE NOTICE 'Foreign Key encontrada:';
        RAISE NOTICE '  Nome: %', fk_name;
        RAISE NOTICE '  Coluna: empreendimentos.%', fk_column;
        RAISE NOTICE '  Referencia: %.%', ref_table, ref_column;
        
        IF ref_table = 'empresas' THEN
            RAISE WARNING 'ERRO: Foreign key referencia a tabela ERRADA (empresas)!';
            RAISE NOTICE 'Deveria referenciar: clientes';
        ELSIF ref_table = 'clientes' THEN
            RAISE NOTICE 'OK: Foreign key referencia a tabela correta (clientes).';
        ELSE
            RAISE WARNING 'ATENÇÃO: Foreign key referencia uma tabela inesperada: %', ref_table;
        END IF;
    ELSE
        RAISE WARNING 'Nenhuma foreign key encontrada para empresa_id na tabela empreendimentos.';
    END IF;
    
    -- Verificar se empresa_id=9 existe na tabela clientes
    SELECT EXISTS (
        SELECT 1 
        FROM clientes 
        WHERE id = 9 AND tipo_cadastro = 'Empresa'
    ) INTO empresa_id_9_exists;
    
    RAISE NOTICE '';
    RAISE NOTICE 'Verificação do empresa_id=9:';
    IF empresa_id_9_exists THEN
        RAISE NOTICE 'OK: empresa_id=9 existe na tabela clientes com tipo_cadastro=''Empresa''.';
    ELSE
        RAISE WARNING 'ERRO: empresa_id=9 NÃO existe na tabela clientes com tipo_cadastro=''Empresa''.';
        
        -- Verificar se existe com outro tipo
        SELECT COUNT(*) INTO empresa_count
        FROM clientes 
        WHERE id = 9;
        
        IF empresa_count > 0 THEN
            RAISE WARNING '  Mas existe na tabela clientes com outro tipo_cadastro.';
        ELSE
            RAISE WARNING '  E também não existe na tabela clientes.';
        END IF;
    END IF;
    
    -- Listar todas as empresas disponíveis
    SELECT COUNT(*) INTO empresa_count
    FROM clientes 
    WHERE tipo_cadastro = 'Empresa';
    
    RAISE NOTICE '';
    RAISE NOTICE 'Empresas cadastradas:';
    RAISE NOTICE '  Total de empresas na tabela clientes: %', empresa_count;
    
    IF empresa_count > 0 THEN
        RAISE NOTICE '  IDs das empresas disponíveis:';
        FOR empresa_count IN 
            SELECT id, nome 
            FROM clientes 
            WHERE tipo_cadastro = 'Empresa' 
            ORDER BY id
        LOOP
            RAISE NOTICE '    ID: % - Nome: %', empresa_count.id, empresa_count.nome;
        END LOOP;
    END IF;
    
    -- Verificar empreendimentos com empresa_id inválido
    RAISE NOTICE '';
    RAISE NOTICE 'Empreendimentos com empresa_id que não existe em clientes:';
    FOR empresa_count IN 
        SELECT e.id, e.nome, e.empresa_id
        FROM empreendimentos e
        LEFT JOIN clientes c ON c.id = e.empresa_id AND c.tipo_cadastro = 'Empresa'
        WHERE e.empresa_id IS NOT NULL AND c.id IS NULL
        ORDER BY e.id
    LOOP
        RAISE WARNING '  Empreendimento ID: % - Nome: % - empresa_id inválido: %', 
            empresa_count.id, empresa_count.nome, empresa_count.empresa_id;
    END LOOP;
    
END $$;

-- ============================================
-- PARTE 2: CORREÇÃO (se necessário)
-- ============================================

DO $$ 
DECLARE
    fk_name TEXT;
    ref_table TEXT;
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE '========================================';
    RAISE NOTICE 'CORREÇÃO DA FOREIGN KEY';
    RAISE NOTICE '========================================';
    
    -- Buscar o nome da constraint e a tabela referenciada
    SELECT 
        tc.constraint_name,
        ccu.table_name AS foreign_table_name
    INTO fk_name, ref_table
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
        -- Se a foreign key referencia a tabela errada (empresas), remover e recriar
        IF ref_table = 'empresas' THEN
            RAISE NOTICE 'Corrigindo foreign key incorreta...';
            
            -- Remover a foreign key incorreta
            EXECUTE format('ALTER TABLE empreendimentos DROP CONSTRAINT IF EXISTS %I', fk_name);
            RAISE NOTICE 'Foreign key incorreta removida: %', fk_name;
            
            -- Recriar a foreign key correta apontando para clientes
            ALTER TABLE empreendimentos 
            ADD CONSTRAINT fk_empreendimentos_empresa 
            FOREIGN KEY (empresa_id) 
            REFERENCES clientes(id) 
            ON DELETE SET NULL 
            ON UPDATE CASCADE;
            
            RAISE NOTICE 'Foreign key corrigida! Agora referencia a tabela clientes.';
        ELSIF ref_table = 'clientes' THEN
            RAISE NOTICE 'Foreign key já está correta, referenciando a tabela clientes.';
        ELSE
            RAISE WARNING 'Foreign key referencia uma tabela inesperada: %. Verifique manualmente.', ref_table;
        END IF;
    ELSE
        RAISE NOTICE 'Nenhuma foreign key encontrada. Criando nova...';
        
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

-- ============================================
-- PARTE 3: LIMPEZA DE DADOS INVÁLIDOS (opcional)
-- ============================================

DO $$ 
DECLARE
    registros_afetados INTEGER;
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE '========================================';
    RAISE NOTICE 'LIMPEZA DE DADOS INVÁLIDOS';
    RAISE NOTICE '========================================';
    
    -- Contar empreendimentos com empresa_id inválido
    SELECT COUNT(*) INTO registros_afetados
    FROM empreendimentos e
    LEFT JOIN clientes c ON c.id = e.empresa_id AND c.tipo_cadastro = 'Empresa'
    WHERE e.empresa_id IS NOT NULL AND c.id IS NULL;
    
    IF registros_afetados > 0 THEN
        RAISE WARNING 'Encontrados % empreendimento(s) com empresa_id inválido.', registros_afetados;
        RAISE NOTICE 'Definindo empresa_id como NULL para esses registros...';
        
        -- Definir empresa_id como NULL para registros inválidos
        UPDATE empreendimentos e
        SET empresa_id = NULL
        WHERE e.empresa_id IS NOT NULL 
        AND NOT EXISTS (
            SELECT 1 
            FROM clientes c 
            WHERE c.id = e.empresa_id 
            AND c.tipo_cadastro = 'Empresa'
        );
        
        GET DIAGNOSTICS registros_afetados = ROW_COUNT;
        RAISE NOTICE '% registro(s) atualizado(s).', registros_afetados;
    ELSE
        RAISE NOTICE 'Nenhum registro com empresa_id inválido encontrado.';
    END IF;
END $$;

-- ============================================
-- PARTE 4: VALIDAÇÃO FINAL
-- ============================================

DO $$ 
DECLARE
    fk_table TEXT;
    empresa_count INTEGER;
    empreendimentos_count INTEGER;
BEGIN
    RAISE NOTICE '';
    RAISE NOTICE '========================================';
    RAISE NOTICE 'VALIDAÇÃO FINAL';
    RAISE NOTICE '========================================';
    
    -- Verificar se a foreign key está correta
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
        RAISE NOTICE '✓ Foreign key está correta, referenciando a tabela clientes.';
    ELSE
        RAISE WARNING '✗ Foreign key ainda está incorreta, referenciando: %', fk_table;
    END IF;
    
    -- Contar empresas disponíveis
    SELECT COUNT(*) INTO empresa_count
    FROM clientes 
    WHERE tipo_cadastro = 'Empresa';
    
    RAISE NOTICE '✓ Total de empresas cadastradas: %', empresa_count;
    
    -- Contar empreendimentos
    SELECT COUNT(*) INTO empreendimentos_count
    FROM empreendimentos;
    
    RAISE NOTICE '✓ Total de empreendimentos: %', empreendimentos_count;
    
    -- Verificar se ainda há dados inválidos
    SELECT COUNT(*) INTO empresa_count
    FROM empreendimentos e
    LEFT JOIN clientes c ON c.id = e.empresa_id AND c.tipo_cadastro = 'Empresa'
    WHERE e.empresa_id IS NOT NULL AND c.id IS NULL;
    
    IF empresa_count = 0 THEN
        RAISE NOTICE '✓ Nenhum empreendimento com empresa_id inválido.';
    ELSE
        RAISE WARNING '✗ Ainda existem % empreendimento(s) com empresa_id inválido.', empresa_count;
    END IF;
    
    RAISE NOTICE '';
    RAISE NOTICE '========================================';
    RAISE NOTICE 'DIAGNÓSTICO CONCLUÍDO';
    RAISE NOTICE '========================================';
END $$;




