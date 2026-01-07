<?php
session_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../config/logger.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar se o usuário está logado
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

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

try {
    $pdo = getConnection();

    switch ($action) {
        case 'list':
            $stmt = $pdo->query("SELECT id, nome, usuario, email, ativo, data_criacao, data_atualizacao FROM usuarios ORDER BY id");
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponse(true, 'Lista de usuários carregada com sucesso.', ['usuarios' => $usuarios]);
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                jsonResponse(false, 'ID inválido.');
            }

            $stmt = $pdo->prepare("SELECT id, nome, usuario, email, ativo FROM usuarios WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                jsonResponse(false, 'Usuário não encontrado.');
            }

            jsonResponse(true, 'Usuário carregado com sucesso.', ['usuario' => $usuario]);
            break;

        case 'verificar-usuario':
            $usuario = trim($_GET['usuario'] ?? '');
            if ($usuario === '') {
                jsonResponse(false, 'Nome de usuário não informado.');
            }

            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            $existe = $stmt->fetch();

            if ($existe) {
                jsonResponse(false, 'Já existe um usuário com este nome de usuário.');
            }

            jsonResponse(true, 'Nome de usuário disponível.');
            break;

        case 'create':
            $nome = trim($_POST['nome'] ?? '');
            $usuario = trim($_POST['usuario'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $email = trim($_POST['email'] ?? '');
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            if ($nome === '' || $usuario === '' || $senha === '') {
                jsonResponse(false, 'Nome, Usuário e Senha são obrigatórios.');
            }

            // Verificar se usuário já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario");
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();
            if ($stmt->fetch()) {
                jsonResponse(false, 'Já existe um usuário com este nome de usuário.');
            }

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nome, usuario, senha, email, ativo)
                VALUES (:nome, :usuario, :senha, :email, :ativo)
            ");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':usuario', $usuario);
            // Senha em texto plano, conforme solicitado (sem criptografia)
            $stmt->bindParam(':senha', $senha);
            $stmt->bindParam(':email', $email);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->execute();

            jsonResponse(true, 'Usuário criado com sucesso.');
            break;

        case 'update':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $nome = trim($_POST['nome'] ?? '');
            $usuario = trim($_POST['usuario'] ?? '');
            $senha = $_POST['senha'] ?? null; // se vazio, mantém a atual
            $email = trim($_POST['email'] ?? '');
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            if ($id <= 0) {
                jsonResponse(false, 'ID inválido.');
            }

            if ($nome === '' || $usuario === '') {
                jsonResponse(false, 'Nome e Usuário são obrigatórios.');
            }

            // Verificar se usuário existe
            $stmt = $pdo->prepare("SELECT id, senha FROM usuarios WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $usuarioAtual = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuarioAtual) {
                jsonResponse(false, 'Usuário não encontrado.');
            }

            // Verificar se já existe outro usuário com mesmo nome de usuário
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = :usuario AND id <> :id");
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetch()) {
                jsonResponse(false, 'Já existe outro usuário com este nome de usuário.');
            }

            // Se senha não foi informada, mantém a atual
            if ($senha === null || $senha === '') {
                $senha = $usuarioAtual['senha'];
            }

            $stmt = $pdo->prepare("
                UPDATE usuarios
                SET nome = :nome,
                    usuario = :usuario,
                    senha = :senha,
                    email = :email,
                    ativo = :ativo,
                    data_atualizacao = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':senha', $senha);
            $stmt->bindParam(':email', $email);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponse(true, 'Usuário atualizado com sucesso.');
            break;

        case 'delete':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                jsonResponse(false, 'ID inválido.');
            }

            // Impedir que o usuário logado se exclua a si mesmo
            if (isset($_SESSION['usuario_id']) && (int)$_SESSION['usuario_id'] === $id) {
                jsonResponse(false, 'Você não pode excluir o usuário atualmente logado.');
            }

            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponse(true, 'Usuário excluído com sucesso.');
            break;

        default:
            jsonResponse(false, 'Ação inválida.');
    }
} catch (Exception $e) {
    registrarLog('ERRO', 'Erro no CRUD de usuários: ' . $e->getMessage(), [
        'action' => $action,
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ]);

    jsonResponse(false, 'Erro ao processar a requisição de usuários. Detalhes: ' . $e->getMessage());
}


