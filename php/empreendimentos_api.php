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

function jsonResponseEmp($sucesso, $mensagem, $extra = []) {
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

function tableHasColumnEmp(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS existe
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = :table
          AND column_name = :column
    ");
    $stmt->execute([':table' => $table, ':column' => $column]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && (int)$row['existe'] > 0;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

try {
    $pdo = getConnection();
    $hasBancoId = tableHasColumnEmp($pdo, 'empreendimentos', 'banco_id');

    switch ($action) {
        case 'list':
            $sql = "
                SELECT id, nome, descricao, endereco, bairro, cidade, uf, cep, ativo, data_criacao, data_atualizacao";
            if ($hasBancoId) {
                $sql .= ", banco_id";
            }
            $sql .= "
                FROM empreendimentos
                ORDER BY id
            ";
            $stmt = $pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponseEmp(true, 'Lista de empreendimentos carregada com sucesso.', ['empreendimentos' => $rows]);
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                jsonResponseEmp(false, 'ID inválido.');
            }

            $sql = "
                SELECT id, nome, descricao, endereco, bairro, cidade, uf, cep, ativo";
            if ($hasBancoId) {
                $sql .= ", banco_id";
            }
            $sql .= "
                FROM empreendimentos
                WHERE id = :id
            ";
            $stmt = $pdo->prepare($sql);
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
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';
            $banco_id = (isset($_POST['banco_id']) && $_POST['banco_id'] !== '') ? (int)$_POST['banco_id'] : null;

            if ($nome === '') {
                jsonResponseEmp(false, 'O campo Nome do Empreendimento é obrigatório.');
            }

            if ($hasBancoId) {
                $stmt = $pdo->prepare("
                    INSERT INTO empreendimentos (nome, descricao, endereco, bairro, cidade, uf, cep, ativo, banco_id)
                    VALUES (:nome, :descricao, :endereco, :bairro, :cidade, :uf, :cep, :ativo, :banco_id)
                ");
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO empreendimentos (nome, descricao, endereco, bairro, cidade, uf, cep, ativo)
                    VALUES (:nome, :descricao, :endereco, :bairro, :cidade, :uf, :cep, :ativo)
                ");
            }
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':uf', $uf);
            $stmt->bindParam(':cep', $cep);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            if ($hasBancoId) {
                if ($banco_id === null) {
                    $stmt->bindValue(':banco_id', null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue(':banco_id', $banco_id, PDO::PARAM_INT);
                }
            }
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
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';
            $banco_id = (isset($_POST['banco_id']) && $_POST['banco_id'] !== '') ? (int)$_POST['banco_id'] : null;

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

            $sql = "
                UPDATE empreendimentos
                SET nome = :nome,
                    descricao = :descricao,
                    endereco = :endereco,
                    bairro = :bairro,
                    cidade = :cidade,
                    uf = :uf,
                    cep = :cep,
                    ativo = :ativo,"; 
            if ($hasBancoId) {
                $sql .= "
                    banco_id = :banco_id,";
            }
            $sql .= "
                    data_atualizacao = CURRENT_TIMESTAMP
                WHERE id = :id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':uf', $uf);
            $stmt->bindParam(':cep', $cep);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            if ($hasBancoId) {
                if ($banco_id === null) {
                    $stmt->bindValue(':banco_id', null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue(':banco_id', $banco_id, PDO::PARAM_INT);
                }
            }
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
