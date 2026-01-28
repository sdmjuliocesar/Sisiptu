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

function jsonResponseMod($sucesso, $mensagem, $extra = []) {
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
                SELECT m.id,
                       m.nome,
                       m.empreendimento_id,
                       e.nome AS empreendimento_nome,
                       m.ativo,
                       m.data_criacao,
                       m.data_atualizacao
                FROM modulos m
                JOIN empreendimentos e ON e.id = m.empreendimento_id
                ORDER BY m.id
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponseMod(true, 'Lista de módulos carregada com sucesso.', ['modulos' => $rows]);
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                jsonResponseMod(false, 'ID inválido.');
            }

            $stmt = $pdo->prepare("
                SELECT id, nome, empreendimento_id, ativo
                FROM modulos
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                jsonResponseMod(false, 'Módulo não encontrado.');
            }

            jsonResponseMod(true, 'Módulo carregado com sucesso.', ['modulo' => $row]);
            break;

        case 'create':
            $nome = trim($_POST['nome'] ?? '');
            $empreendimento_id = isset($_POST['empreendimento_id']) ? (int)$_POST['empreendimento_id'] : 0;
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            if ($nome === '' || $empreendimento_id <= 0) {
                jsonResponseMod(false, 'Preencha o Nome do Módulo e selecione um Empreendimento.');
            }

            // Verificar se empreendimento existe
            $stmt = $pdo->prepare("SELECT id FROM empreendimentos WHERE id = :id");
            $stmt->bindParam(':id', $empreendimento_id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                jsonResponseMod(false, 'Empreendimento selecionado não existe.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO modulos (nome, empreendimento_id, ativo)
                VALUES (:nome, :empreendimento_id, :ativo)
            ");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->execute();

            jsonResponseMod(true, 'Módulo criado com sucesso.');
            break;

        case 'update':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $nome = trim($_POST['nome'] ?? '');
            $empreendimento_id = isset($_POST['empreendimento_id']) ? (int)$_POST['empreendimento_id'] : 0;
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            if ($id <= 0) {
                jsonResponseMod(false, 'ID inválido.');
            }

            if ($nome === '' || $empreendimento_id <= 0) {
                jsonResponseMod(false, 'Preencha o Nome do Módulo e selecione um Empreendimento.');
            }

            // Verificar se módulo existe
            $stmt = $pdo->prepare("SELECT id FROM modulos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                jsonResponseMod(false, 'Módulo não encontrado.');
            }

            // Verificar se empreendimento existe
            $stmt = $pdo->prepare("SELECT id FROM empreendimentos WHERE id = :id");
            $stmt->bindParam(':id', $empreendimento_id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                jsonResponseMod(false, 'Empreendimento selecionado não existe.');
            }

            $stmt = $pdo->prepare("
                UPDATE modulos
                SET nome = :nome,
                    empreendimento_id = :empreendimento_id,
                    ativo = :ativo,
                    data_atualizacao = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponseMod(true, 'Módulo atualizado com sucesso.');
            break;

        case 'delete':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                jsonResponseMod(false, 'ID inválido.');
            }

            $stmt = $pdo->prepare("DELETE FROM modulos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponseMod(true, 'Módulo excluído com sucesso.');
            break;

        default:
            jsonResponseMod(false, 'Ação inválida.');
    }
} catch (PDOException $e) {
    // Capturar erro de violação de chave estrangeira
    if ($e->getCode() === '23503') { // PostgreSQL foreign key violation error code
        jsonResponseMod(false, 'Não é possível excluir o módulo pois ele possui contratos vinculados.');
    } else {
        registrarLog('ERRO', 'Erro no CRUD de módulos: ' . $e->getMessage(), [
            'action' => $action,
            'erro' => $e->getMessage(),
            'arquivo' => $e->getFile(),
            'linha' => $e->getLine(),
        ]);
        jsonResponseMod(false, 'Erro ao processar a requisição de módulos. Detalhes: ' . $e->getMessage());
    }
} catch (Exception $e) {
    registrarLog('ERRO', 'Erro no CRUD de módulos: ' . $e->getMessage(), [
        'action' => $action,
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ]);
    jsonResponseMod(false, 'Erro ao processar a requisição de módulos. Detalhes: ' . $e->getMessage());
}
