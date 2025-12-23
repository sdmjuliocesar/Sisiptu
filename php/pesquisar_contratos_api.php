<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/logger.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Acesso não autorizado. Faça login novamente.'
    ]);
    exit;
}

function jsonResponse($sucesso, $mensagem, $extra = []) {
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'pesquisar';

try {
    $pdo = getConnection();

    switch ($action) {
        case 'pesquisar':
            // Coletar filtros
            $contrato = trim($_GET['contrato'] ?? '');
            $cpf_cnpj = trim($_GET['cpf_cnpj'] ?? '');
            $inscricao = trim($_GET['inscricao'] ?? '');
            $empreendimento_id = isset($_GET['empreendimento_id']) ? trim($_GET['empreendimento_id']) : '';
            $modulo_id = isset($_GET['modulo_id']) ? trim($_GET['modulo_id']) : '';
            $situacao = trim($_GET['situacao'] ?? '');
            
            // Construir query dinamicamente
            $where = [];
            $params = [];
            
            if ($contrato !== '') {
                $where[] = "c.contrato ILIKE :contrato";
                $params[':contrato'] = '%' . $contrato . '%';
            }
            
            if ($cpf_cnpj !== '') {
                // Remover formatação do CPF/CNPJ para busca
                $cpf_cnpj_limpo = preg_replace('/\D/', '', $cpf_cnpj);
                $where[] = "REPLACE(REPLACE(REPLACE(REPLACE(c.cpf_cnpj, '.', ''), '/', ''), '-', ''), ' ', '') LIKE :cpf_cnpj";
                $params[':cpf_cnpj'] = '%' . $cpf_cnpj_limpo . '%';
            }
            
            if ($inscricao !== '') {
                $where[] = "c.inscricao ILIKE :inscricao";
                $params[':inscricao'] = '%' . $inscricao . '%';
            }
            
            if ($empreendimento_id !== '') {
                $where[] = "c.empreendimento_id = :empreendimento_id";
                $params[':empreendimento_id'] = (int)$empreendimento_id;
            }
            
            if ($modulo_id !== '') {
                $where[] = "c.modulo_id = :modulo_id";
                $params[':modulo_id'] = (int)$modulo_id;
            }
            
            if ($situacao !== '') {
                $where[] = "c.situacao = :situacao";
                $params[':situacao'] = $situacao;
            }
            
            // Montar query - todos os campos da tabela contratos
            $sql = "
                SELECT 
                    c.id,
                    c.empreendimento_id,
                    c.modulo_id,
                    c.cliente_id,
                    c.area,
                    c.modulo,
                    c.submodulo,
                    c.contrato,
                    c.inscricao,
                    c.metragem,
                    c.vrm2,
                    c.valor_venal,
                    c.aliquota,
                    c.tx_coleta_lixo,
                    c.desconto_a_vista,
                    c.parcelamento,
                    c.obs,
                    c.valor_mensal,
                    c.valor_anual,
                    c.cpf_cnpj,
                    c.situacao,
                    c.data_criacao,
                    c.data_atualizacao,
                    e.nome as empreendimento_nome,
                    m.nome as modulo_nome
                FROM contratos c
                LEFT JOIN empreendimentos e ON c.empreendimento_id = e.id
                LEFT JOIN modulos m ON c.modulo_id = m.id
            ";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            $sql .= " ORDER BY c.data_criacao DESC";
            
            $stmt = $pdo->prepare($sql);
            
            // Bind dos parâmetros apenas se houver
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    if (is_int($value)) {
                        $stmt->bindValue($key, $value, PDO::PARAM_INT);
                    } else {
                        $stmt->bindValue($key, $value);
                    }
                }
            }
            
            $stmt->execute();
            $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            jsonResponse(true, 'Pesquisa realizada com sucesso.', ['contratos' => $contratos]);
            break;
            
        case 'listar-todos':
            // Listar todos os contratos sem filtros - todos os campos
            $stmt = $pdo->query("
                SELECT 
                    c.id,
                    c.empreendimento_id,
                    c.modulo_id,
                    c.cliente_id,
                    c.area,
                    c.modulo,
                    c.submodulo,
                    c.contrato,
                    c.inscricao,
                    c.metragem,
                    c.vrm2,
                    c.valor_venal,
                    c.aliquota,
                    c.tx_coleta_lixo,
                    c.desconto_a_vista,
                    c.parcelamento,
                    c.obs,
                    c.valor_mensal,
                    c.valor_anual,
                    c.cpf_cnpj,
                    c.situacao,
                    c.data_criacao,
                    c.data_atualizacao,
                    e.nome as empreendimento_nome,
                    m.nome as modulo_nome
                FROM contratos c
                LEFT JOIN empreendimentos e ON c.empreendimento_id = e.id
                LEFT JOIN modulos m ON c.modulo_id = m.id
                ORDER BY c.data_criacao DESC
            ");
            
            $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            jsonResponse(true, 'Listagem realizada com sucesso.', ['contratos' => $contratos]);
            break;
            
        default:
            jsonResponse(false, 'Ação não reconhecida.');
            break;
    }
    
} catch (PDOException $e) {
    logError('Erro na pesquisa de contratos', [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    jsonResponse(false, 'Erro ao realizar pesquisa: ' . $e->getMessage());
} catch (Exception $e) {
    logError('Erro geral na pesquisa de contratos', [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    jsonResponse(false, 'Erro ao realizar pesquisa: ' . $e->getMessage());
}

