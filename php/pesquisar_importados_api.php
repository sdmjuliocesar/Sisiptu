<?php
session_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/logger.php';

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
            $cpf_cnpj = trim($_GET['cpf_cnpj'] ?? '');
            $nome = trim($_GET['nome'] ?? '');
            $cidade = trim($_GET['cidade'] ?? '');
            $uf = trim($_GET['uf'] ?? '');
            $tipo_cadastro = trim($_GET['tipo_cadastro'] ?? '');
            $ativo = isset($_GET['ativo']) ? trim($_GET['ativo']) : '';
            
            // Construir query dinamicamente
            $where = [];
            $params = [];
            
            if ($cpf_cnpj !== '') {
                // Remover formatação do CPF/CNPJ para busca
                $cpf_cnpj_limpo = preg_replace('/\D/', '', $cpf_cnpj);
                $where[] = "REPLACE(REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', ''), ' ', '') LIKE :cpf_cnpj";
                $params[':cpf_cnpj'] = '%' . $cpf_cnpj_limpo . '%';
            }
            
            if ($nome !== '') {
                $where[] = "nome ILIKE :nome";
                $params[':nome'] = '%' . $nome . '%';
            }
            
            if ($cidade !== '') {
                $where[] = "cidade ILIKE :cidade";
                $params[':cidade'] = '%' . $cidade . '%';
            }
            
            if ($uf !== '') {
                $where[] = "UPPER(uf) = :uf";
                $params[':uf'] = strtoupper($uf);
            }
            
            if ($tipo_cadastro !== '') {
                $where[] = "tipo_cadastro = :tipo_cadastro";
                $params[':tipo_cadastro'] = $tipo_cadastro;
            }
            
            if ($ativo !== '') {
                $where[] = "ativo = :ativo";
                $params[':ativo'] = $ativo === '1' ? true : false;
            }
            
            // Montar query
            $sql = "
                SELECT 
                    id, cpf_cnpj, nome, tipo_cadastro, cep, endereco, bairro, 
                    cidade, uf, email, tel_comercial, tel_celular1, tel_celular2, 
                    tel_residencial, ativo, data_criacao, data_atualizacao
                FROM clientes
            ";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(' AND ', $where);
            }
            
            $sql .= " ORDER BY nome ASC";
            
            $stmt = $pdo->prepare($sql);
            
            // Bind dos parâmetros apenas se houver
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    if (is_bool($value)) {
                        $stmt->bindValue($key, $value, PDO::PARAM_BOOL);
                    } else {
                        $stmt->bindValue($key, $value);
                    }
                }
            }
            
            $stmt->execute();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            jsonResponse(true, 'Pesquisa realizada com sucesso.', ['clientes' => $clientes]);
            break;
            
        case 'listar-todos':
            // Listar todos os clientes sem filtros
            $stmt = $pdo->query("
                SELECT 
                    id, cpf_cnpj, nome, tipo_cadastro, cep, endereco, bairro, 
                    cidade, uf, email, tel_comercial, tel_celular1, tel_celular2, 
                    tel_residencial, ativo, data_criacao, data_atualizacao
                FROM clientes
                ORDER BY nome ASC
            ");
            
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            jsonResponse(true, 'Listagem realizada com sucesso.', ['clientes' => $clientes]);
            break;
            
        default:
            jsonResponse(false, 'Ação não reconhecida.');
            break;
    }
    
} catch (PDOException $e) {
    logError('Erro na pesquisa de clientes importados', [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    jsonResponse(false, 'Erro ao realizar pesquisa: ' . $e->getMessage());
} catch (Exception $e) {
    logError('Erro geral na pesquisa de clientes importados', [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    jsonResponse(false, 'Erro ao realizar pesquisa: ' . $e->getMessage());
}

