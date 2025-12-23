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

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

try {
    $pdo = getConnection();

    switch ($action) {
        case 'list':
            // Verificar se a coluna banco_id existe antes de fazer o JOIN
            try {
                $checkColumn = $pdo->query("
                    SELECT COUNT(*) 
                    FROM information_schema.columns 
                    WHERE table_schema = 'public'
                    AND table_name = 'empreendimentos' 
                    AND column_name = 'banco_id'
                ")->fetchColumn();
                
                $hasBancoId = ($checkColumn > 0);
            } catch (Exception $e) {
                $hasBancoId = false;
            }
            
            if ($hasBancoId) {
                $stmt = $pdo->query("
                    SELECT e.id, e.nome, e.descricao, e.endereco, e.bairro, e.cidade, e.uf, e.cep, e.banco_id, e.ativo, e.data_criacao, e.data_atualizacao,
                           b.banco as banco_nome, b.conta as banco_conta
                    FROM empreendimentos e
                    LEFT JOIN bancos b ON e.banco_id = b.id
                    ORDER BY e.id
                ");
            } else {
                // Se a coluna não existe, fazer SELECT sem banco_id e sem JOIN
                $stmt = $pdo->query("
                    SELECT e.id, e.nome, e.descricao, e.endereco, e.bairro, e.cidade, e.uf, e.cep, e.ativo, e.data_criacao, e.data_atualizacao,
                           NULL as banco_id, NULL as banco_nome, NULL as banco_conta
                    FROM empreendimentos e
                    ORDER BY e.id
                ");
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponseEmp(true, 'Lista de empreendimentos carregada com sucesso.', ['empreendimentos' => $rows]);
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                jsonResponseEmp(false, 'ID inválido.');
            }

            // Verificar se a coluna banco_id existe
            try {
                $checkColumn = $pdo->query("
                    SELECT COUNT(*) 
                    FROM information_schema.columns 
                    WHERE table_schema = 'public'
                    AND table_name = 'empreendimentos' 
                    AND column_name = 'banco_id'
                ")->fetchColumn();
                
                $hasBancoId = ($checkColumn > 0);
            } catch (Exception $e) {
                $hasBancoId = false;
            }
            
            if ($hasBancoId) {
                $stmt = $pdo->prepare("
                    SELECT e.id, e.nome, e.descricao, e.endereco, e.bairro, e.cidade, e.uf, e.cep, e.banco_id, e.ativo,
                           b.banco as banco_nome, b.conta as banco_conta
                    FROM empreendimentos e
                    LEFT JOIN bancos b ON e.banco_id = b.id
                    WHERE e.id = :id
                ");
            } else {
                $stmt = $pdo->prepare("
                    SELECT e.id, e.nome, e.descricao, e.endereco, e.bairro, e.cidade, e.uf, e.cep, e.ativo,
                           NULL as banco_id, NULL as banco_nome, NULL as banco_conta
                    FROM empreendimentos e
                    WHERE e.id = :id
                ");
            }
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
            $banco_id = isset($_POST['banco_id']) && $_POST['banco_id'] !== '' ? (int)$_POST['banco_id'] : null;
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            if ($nome === '') {
                jsonResponseEmp(false, 'O campo Nome do Empreendimento é obrigatório.');
            }

            // Verificar se a coluna banco_id existe
            try {
                $checkColumn = $pdo->query("
                    SELECT COUNT(*) 
                    FROM information_schema.columns 
                    WHERE table_schema = 'public'
                    AND table_name = 'empreendimentos' 
                    AND column_name = 'banco_id'
                ")->fetchColumn();
                
                $hasBancoId = ($checkColumn > 0);
            } catch (Exception $e) {
                $hasBancoId = false;
            }
            
            if ($hasBancoId) {
                $stmt = $pdo->prepare("
                    INSERT INTO empreendimentos (nome, descricao, endereco, bairro, cidade, uf, cep, banco_id, ativo)
                    VALUES (:nome, :descricao, :endereco, :bairro, :cidade, :uf, :cep, :banco_id, :ativo)
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
            if ($hasBancoId) {
                $stmt->bindParam(':banco_id', $banco_id, PDO::PARAM_INT);
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
            $banco_id = isset($_POST['banco_id']) && $_POST['banco_id'] !== '' ? (int)$_POST['banco_id'] : null;
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

            // Verificar se a coluna banco_id existe
            try {
                $checkColumn = $pdo->query("
                    SELECT COUNT(*) 
                    FROM information_schema.columns 
                    WHERE table_schema = 'public'
                    AND table_name = 'empreendimentos' 
                    AND column_name = 'banco_id'
                ")->fetchColumn();
                
                $hasBancoId = ($checkColumn > 0);
            } catch (Exception $e) {
                $hasBancoId = false;
            }
            
            if ($hasBancoId) {
                $stmt = $pdo->prepare("
                    UPDATE empreendimentos
                    SET nome = :nome,
                        descricao = :descricao,
                        endereco = :endereco,
                        bairro = :bairro,
                        cidade = :cidade,
                        uf = :uf,
                        cep = :cep,
                        banco_id = :banco_id,
                        ativo = :ativo,
                        data_atualizacao = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
            } else {
                $stmt = $pdo->prepare("
                    UPDATE empreendimentos
                    SET nome = :nome,
                        descricao = :descricao,
                        endereco = :endereco,
                        bairro = :bairro,
                        cidade = :cidade,
                        uf = :uf,
                        cep = :cep,
                        ativo = :ativo,
                        data_atualizacao = CURRENT_TIMESTAMP
                    WHERE id = :id
                ");
            }
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':descricao', $descricao);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':uf', $uf);
            $stmt->bindParam(':cep', $cep);
            if ($hasBancoId) {
                $stmt->bindParam(':banco_id', $banco_id, PDO::PARAM_INT);
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
} catch (PDOException $e) {
    // Capturar erro de violação de chave estrangeira
    if ($e->getCode() === '23001') { // PostgreSQL restrict violation error code
        jsonResponseEmp(false, 'Não é possível excluir o empreendimento pois ele possui módulos vinculados.');
    } else {
        registrarLog('ERRO', 'Erro no CRUD de empreendimentos: ' . $e->getMessage(), [
            'action' => $action,
            'erro' => $e->getMessage(),
            'arquivo' => $e->getFile(),
            'linha' => $e->getLine(),
        ]);
        jsonResponseEmp(false, 'Erro ao processar a requisição de empreendimentos. Detalhes: ' . $e->getMessage());
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
