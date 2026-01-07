<?php
session_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../config/logger.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Acesso não autorizado. Faça login novamente.'
    ]);
    exit;
}

function jsonResponseEmp($sucesso, $mensagem, $extra = []) {
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

try {
    $pdo = getConnection();

    switch ($action) {
        case 'list':
            $stmt = $pdo->query("
                SELECT id, nome, descricao, endereco, bairro, cidade, uf, cep, empresa_id, banco_id, ativo, data_criacao, data_atualizacao
                FROM empreendimentos
                ORDER BY id
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponseEmp(true, 'Lista de empreendimentos carregada com sucesso.', ['empreendimentos' => $rows]);
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                jsonResponseEmp(false, 'ID inválido.');
            }

            $stmt = $pdo->prepare("
                SELECT id, nome, descricao, endereco, bairro, cidade, uf, cep, empresa_id, banco_id, ativo
                FROM empreendimentos
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                jsonResponseEmp(false, 'Empreendimento não encontrado.');
            }

            jsonResponseEmp(true, 'Empreendimento carregado com sucesso.', ['empreendimento' => $row]);
            break;

        case 'create':
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $endereco = trim($_POST['endereco'] ?? '');
            $bairro = trim($_POST['bairro'] ?? '');
            $cidade = trim($_POST['cidade'] ?? '');
            $uf = strtoupper(trim($_POST['uf'] ?? ''));
            $cep = trim($_POST['cep'] ?? '');
            // Processar empresa_id - tratar string vazia como null
            $empresa_id_raw = $_POST['empresa_id'] ?? '';
            $empresa_id = ($empresa_id_raw !== '' && $empresa_id_raw !== '0' && $empresa_id_raw !== null) ? (int)$empresa_id_raw : null;
            
            // Processar banco_id - tratar string vazia como null
            $banco_id_raw = $_POST['banco_id'] ?? '';
            $banco_id = ($banco_id_raw !== '' && $banco_id_raw !== '0' && $banco_id_raw !== null) ? (int)$banco_id_raw : null;
            
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            if ($nome === '') {
                jsonResponseEmp(false, 'O campo Nome do Empreendimento é obrigatório.');
            }

            // Validar empresa_id se fornecido
            if ($empresa_id !== null && $empresa_id > 0) {
                $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = :id AND tipo_cadastro = 'Empresa'");
                $stmt->bindParam(':id', $empresa_id, PDO::PARAM_INT);
                $stmt->execute();
                if (!$stmt->fetch()) {
                    jsonResponseEmp(false, 'Empresa selecionada não encontrada ou inválida. Verifique se a empresa existe e está cadastrada corretamente.');
                }
            }

            // Validar banco_id se fornecido
            if ($banco_id !== null && $banco_id > 0) {
                $stmt = $pdo->prepare("SELECT id FROM bancos WHERE id = :id");
                $stmt->bindParam(':id', $banco_id, PDO::PARAM_INT);
                $stmt->execute();
                if (!$stmt->fetch()) {
                    jsonResponseEmp(false, 'Banco selecionado não encontrado.');
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO empreendimentos (nome, descricao, endereco, bairro, cidade, uf, cep, empresa_id, banco_id, ativo)
                VALUES (:nome, :descricao, :endereco, :bairro, :cidade, :uf, :cep, :empresa_id, :banco_id, :ativo)
            ");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':uf', $uf);
            $stmt->bindParam(':cep', $cep);
            // Usar PDO::PARAM_NULL quando o valor for null, senão PDO::PARAM_INT
            if ($empresa_id === null) {
                $stmt->bindValue(':empresa_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':empresa_id', $empresa_id, PDO::PARAM_INT);
            }
            if ($banco_id === null) {
                $stmt->bindValue(':banco_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':banco_id', $banco_id, PDO::PARAM_INT);
            }
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->execute();

            jsonResponseEmp(true, 'Empreendimento criado com sucesso.');
            break;

        case 'update':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $nome = trim($_POST['nome'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $endereco = trim($_POST['endereco'] ?? '');
            $bairro = trim($_POST['bairro'] ?? '');
            $cidade = trim($_POST['cidade'] ?? '');
            $uf = strtoupper(trim($_POST['uf'] ?? ''));
            $cep = trim($_POST['cep'] ?? '');
            // Processar empresa_id - tratar string vazia como null
            $empresa_id_raw = $_POST['empresa_id'] ?? '';
            $empresa_id = ($empresa_id_raw !== '' && $empresa_id_raw !== '0' && $empresa_id_raw !== null) ? (int)$empresa_id_raw : null;
            
            // Processar banco_id - tratar string vazia como null
            $banco_id_raw = $_POST['banco_id'] ?? '';
            $banco_id = ($banco_id_raw !== '' && $banco_id_raw !== '0' && $banco_id_raw !== null) ? (int)$banco_id_raw : null;
            
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            if ($id <= 0) {
                jsonResponseEmp(false, 'ID inválido.');
            }

            if ($nome === '') {
                jsonResponseEmp(false, 'O campo Nome do Empreendimento é obrigatório.');
            }

            // Verificar se existe
            $stmt = $pdo->prepare("SELECT id FROM empreendimentos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                jsonResponseEmp(false, 'Empreendimento não encontrado.');
            }

            // Validar empresa_id se fornecido
            if ($empresa_id !== null && $empresa_id > 0) {
                $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = :id AND tipo_cadastro = 'Empresa'");
                $stmt->bindParam(':id', $empresa_id, PDO::PARAM_INT);
                $stmt->execute();
                if (!$stmt->fetch()) {
                    jsonResponseEmp(false, 'Empresa selecionada não encontrada ou inválida. Verifique se a empresa existe e está cadastrada corretamente.');
                }
            }

            // Validar banco_id se fornecido
            if ($banco_id !== null && $banco_id > 0) {
                $stmt = $pdo->prepare("SELECT id FROM bancos WHERE id = :id");
                $stmt->bindParam(':id', $banco_id, PDO::PARAM_INT);
                $stmt->execute();
                if (!$stmt->fetch()) {
                    jsonResponseEmp(false, 'Banco selecionado não encontrado.');
                }
            }

            $stmt = $pdo->prepare("
                UPDATE empreendimentos
                SET nome = :nome,
                    descricao = :descricao,
                    endereco = :endereco,
                    bairro = :bairro,
                    cidade = :cidade,
                    uf = :uf,
                    cep = :cep,
                    empresa_id = :empresa_id,
                    banco_id = :banco_id,
                    ativo = :ativo,
                    data_atualizacao = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':uf', $uf);
            $stmt->bindParam(':cep', $cep);
            // Usar PDO::PARAM_NULL quando o valor for null, senão PDO::PARAM_INT
            if ($empresa_id === null) {
                $stmt->bindValue(':empresa_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':empresa_id', $empresa_id, PDO::PARAM_INT);
            }
            if ($banco_id === null) {
                $stmt->bindValue(':banco_id', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':banco_id', $banco_id, PDO::PARAM_INT);
            }
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponseEmp(true, 'Empreendimento atualizado com sucesso.');
            break;

        case 'delete':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                jsonResponseEmp(false, 'ID inválido.');
            }

            $stmt = $pdo->prepare("DELETE FROM empreendimentos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponseEmp(true, 'Empreendimento excluído com sucesso.');
            break;

        default:
            jsonResponseEmp(false, 'Ação inválida.');
    }
} catch (Exception $e) {
    registrarLog('ERRO', 'Erro no CRUD de empreendimentos: ' . $e->getMessage(), [
        'action' => $action,
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ]);

    jsonResponseEmp(false, 'Erro ao processar a requisição de empreendimentos. Detalhes: ' . $e->getMessage());
}
