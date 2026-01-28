-- Script SQL RÁPIDO para corrigir a foreign key fk_empreendimentos_empresa
-- Use este script se você já sabe que o problema é a foreign key referenciando a tabela errada

-- Remover a foreign key incorreta (se existir)
ALTER TABLE empreendimentos DROP CONSTRAINT IF EXISTS fk_empreendimentos_empresa;

-- Recriar a foreign key correta apontando para clientes
ALTER TABLE empreendimentos 
ADD CONSTRAINT fk_empreendimentos_empresa 
FOREIGN KEY (empresa_id) 
REFERENCES clientes(id) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- Limpar dados inválidos (definir empresa_id como NULL para registros inválidos)
UPDATE empreendimentos e
SET empresa_id = NULL
WHERE e.empresa_id IS NOT NULL 
AND NOT EXISTS (
    SELECT 1 
    FROM clientes c 
    WHERE c.id = e.empresa_id 
    AND c.tipo_cadastro = 'Empresa'
);

-- Verificar resultado
SELECT 
    'Foreign key corrigida!' AS status,
    (SELECT COUNT(*) FROM clientes WHERE tipo_cadastro = 'Empresa') AS empresas_disponiveis,
    (SELECT COUNT(*) FROM empreendimentos WHERE empresa_id IS NOT NULL) AS empreendimentos_com_empresa;




