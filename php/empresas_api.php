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

function jsonResponseEmpresa($sucesso, $mensagem, $extra = []) {
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

function validarCNPJ($cnpj) {
    $cnpj = preg_replace('/\D/', '', $cnpj ?? '');
    if (strlen($cnpj) != 14) return false;
    if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

    $tamanho = 12;
    $numeros = substr($cnpj, 0, $tamanho);
    $digitos = substr($cnpj, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;

    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += intval($numeros[$tamanho - $i]) * $pos--;
        if ($pos < 2) $pos = 9;
    }
    $resultado = $soma % 11 < 2 ? 0 : 11 - ($soma % 11);
    if ($resultado != intval($digitos[0])) return false;

    $tamanho = 13;
    $numeros = substr($cnpj, 0, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;

    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += intval($numeros[$tamanho - $i]) * $pos--;
        if ($pos < 2) $pos = 9;
    }
    $resultado = $soma % 11 < 2 ? 0 : 11 - ($soma % 11);
    if ($resultado != intval($digitos[1])) return false;

    return true;
}

try {
    $pdo = getConnection();

    switch ($action) {
        case 'list':
            $q = trim($_GET['q'] ?? '');
            if ($q !== '') {
                $like = '%' . $q . '%';
                $stmt = $pdo->prepare("
                    SELECT id, cpf_cnpj, nome, cidade, uf, email, tel_comercial, tel_celular1, ativo, data_criacao, data_atualizacao
                    FROM clientes
                    WHERE tipo_cadastro = 'Empresa'
                    AND (nome ILIKE :q OR cpf_cnpj ILIKE :q)
                    ORDER BY nome
                ");
                $stmt->bindParam(':q', $like);
                $stmt->execute();
            } else {
                $stmt = $pdo->query("
                    SELECT id, cpf_cnpj, nome, cidade, uf, email, tel_comercial, tel_celular1, ativo, data_criacao, data_atualizacao
                    FROM clientes
                    WHERE tipo_cadastro = 'Empresa'
                    ORDER BY nome
                ");
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponseEmpresa(true, 'Lista de empresas carregada com sucesso.', ['empresas' => $rows]);
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                jsonResponseEmpresa(false, 'ID inválido.');
            }

            $stmt = $pdo->prepare("
                SELECT id, cpf_cnpj, nome, cep, endereco, bairro, cidade, uf, cod_municipio,
                       email, site, tel_comercial, tel_celular1, tel_celular2, ativo
                FROM clientes
                WHERE id = :id AND tipo_cadastro = 'Empresa'
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                jsonResponseEmpresa(false, 'Empresa não encontrada.');
            }

            jsonResponseEmpresa(true, 'Empresa carregada com sucesso.', ['empresa' => $row]);
            break;

        case 'verificar-cnpj':
            $cnpj = preg_replace('/\D/', '', trim($_POST['cnpj'] ?? ''));
            $empresa_id = isset($_POST['empresa_id']) ? (int)$_POST['empresa_id'] : 0;

            if (strlen($cnpj) != 14) {
                jsonResponseEmpresa(false, 'CNPJ inválido. Deve conter 14 dígitos.');
            }

            if (!validarCNPJ($cnpj)) {
                jsonResponseEmpresa(false, 'CNPJ inválido. Verifique os dígitos verificadores.');
            }

            $stmt = $pdo->prepare("
                SELECT id, nome, cpf_cnpj
                FROM clientes
                WHERE cpf_cnpj = :cnpj AND tipo_cadastro = 'Empresa'
            ");
            $stmt->bindParam(':cnpj', $cnpj);
            $stmt->execute();
            $existe = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existe && $existe['id'] != $empresa_id) {
                jsonResponseEmpresa(false, 'CNPJ já cadastrado para a empresa: ' . $existe['nome'], [
                    'empresa_existente' => $existe
                ]);
            }

            jsonResponseEmpresa(true, 'CNPJ válido e disponível.');
            break;

        case 'create':
            $cnpj = preg_replace('/\D/', '', trim($_POST['cnpj'] ?? ''));
            $razao_social = trim($_POST['razao_social'] ?? '');
            $nome_fantasia = trim($_POST['nome_fantasia'] ?? '');
            $cep = trim($_POST['cep'] ?? '');
            $endereco = trim($_POST['endereco'] ?? '');
            $bairro = trim($_POST['bairro'] ?? '');
            $cidade = trim($_POST['cidade'] ?? '');
            $uf = strtoupper(trim($_POST['uf'] ?? ''));
            $cod_municipio = trim($_POST['cod_municipio'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $site = trim($_POST['site'] ?? '');
            $tel_comercial = trim($_POST['tel_comercial'] ?? '');
            $tel_celular1 = trim($_POST['tel_celular1'] ?? '');
            $tel_celular2 = trim($_POST['tel_celular2'] ?? '');
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            if (strlen($cnpj) != 14) {
                jsonResponseEmpresa(false, 'CNPJ inválido. Deve conter 14 dígitos.');
            }

            if (!validarCNPJ($cnpj)) {
                jsonResponseEmpresa(false, 'CNPJ inválido. Verifique os dígitos verificadores.');
            }

            if ($razao_social === '') {
                jsonResponseEmpresa(false, 'O campo Razão Social é obrigatório.');
            }

            // Verificar se CNPJ já existe
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE cpf_cnpj = :cnpj AND tipo_cadastro = 'Empresa'");
            $stmt->bindParam(':cnpj', $cnpj);
            $stmt->execute();
            if ($stmt->fetch()) {
                jsonResponseEmpresa(false, 'CNPJ já cadastrado para outra empresa.');
            }

            // Usar nome_fantasia se fornecido, senão usar razao_social
            $nome = $nome_fantasia ?: $razao_social;

            $stmt = $pdo->prepare("
                INSERT INTO clientes (
                    cpf_cnpj, nome, tipo_cadastro, cep, endereco, bairro, cidade, uf, cod_municipio,
                    email, site, tel_comercial, tel_celular1, tel_celular2, ativo
                )
                VALUES (
                    :cpf_cnpj, :nome, 'Empresa', :cep, :endereco, :bairro, :cidade, :uf, :cod_municipio,
                    :email, :site, :tel_comercial, :tel_celular1, :tel_celular2, :ativo
                )
            ");
            $stmt->bindParam(':cpf_cnpj', $cnpj);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':cep', $cep);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':uf', $uf);
            $stmt->bindParam(':cod_municipio', $cod_municipio);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':site', $site);
            $stmt->bindParam(':tel_comercial', $tel_comercial);
            $stmt->bindParam(':tel_celular1', $tel_celular1);
            $stmt->bindParam(':tel_celular2', $tel_celular2);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->execute();

            jsonResponseEmpresa(true, 'Empresa criada com sucesso.');
            break;

        case 'update':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $cnpj = preg_replace('/\D/', '', trim($_POST['cnpj'] ?? ''));
            $razao_social = trim($_POST['razao_social'] ?? '');
            $nome_fantasia = trim($_POST['nome_fantasia'] ?? '');
            $cep = trim($_POST['cep'] ?? '');
            $endereco = trim($_POST['endereco'] ?? '');
            $bairro = trim($_POST['bairro'] ?? '');
            $cidade = trim($_POST['cidade'] ?? '');
            $uf = strtoupper(trim($_POST['uf'] ?? ''));
            $cod_municipio = trim($_POST['cod_municipio'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $site = trim($_POST['site'] ?? '');
            $tel_comercial = trim($_POST['tel_comercial'] ?? '');
            $tel_celular1 = trim($_POST['tel_celular1'] ?? '');
            $tel_celular2 = trim($_POST['tel_celular2'] ?? '');
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            if ($id <= 0) {
                jsonResponseEmpresa(false, 'ID inválido.');
            }

            if (strlen($cnpj) != 14) {
                jsonResponseEmpresa(false, 'CNPJ inválido. Deve conter 14 dígitos.');
            }

            if (!validarCNPJ($cnpj)) {
                jsonResponseEmpresa(false, 'CNPJ inválido. Verifique os dígitos verificadores.');
            }

            if ($razao_social === '') {
                jsonResponseEmpresa(false, 'O campo Razão Social é obrigatório.');
            }

            // Verificar se existe
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = :id AND tipo_cadastro = 'Empresa'");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                jsonResponseEmpresa(false, 'Empresa não encontrada.');
            }

            // Verificar se CNPJ já existe em outra empresa
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE cpf_cnpj = :cnpj AND tipo_cadastro = 'Empresa' AND id != :id");
            $stmt->bindParam(':cnpj', $cnpj);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetch()) {
                jsonResponseEmpresa(false, 'CNPJ já cadastrado para outra empresa.');
            }

            // Usar nome_fantasia se fornecido, senão usar razao_social
            $nome = $nome_fantasia ?: $razao_social;

            $stmt = $pdo->prepare("
                UPDATE clientes
                SET cpf_cnpj = :cpf_cnpj,
                    nome = :nome,
                    cep = :cep,
                    endereco = :endereco,
                    bairro = :bairro,
                    cidade = :cidade,
                    uf = :uf,
                    cod_municipio = :cod_municipio,
                    email = :email,
                    site = :site,
                    tel_comercial = :tel_comercial,
                    tel_celular1 = :tel_celular1,
                    tel_celular2 = :tel_celular2,
                    ativo = :ativo,
                    data_atualizacao = CURRENT_TIMESTAMP
                WHERE id = :id AND tipo_cadastro = 'Empresa'
            ");
            $stmt->bindParam(':cpf_cnpj', $cnpj);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':cep', $cep);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':uf', $uf);
            $stmt->bindParam(':cod_municipio', $cod_municipio);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':site', $site);
            $stmt->bindParam(':tel_comercial', $tel_comercial);
            $stmt->bindParam(':tel_celular1', $tel_celular1);
            $stmt->bindParam(':tel_celular2', $tel_celular2);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponseEmpresa(true, 'Empresa atualizada com sucesso.');
            break;

        case 'delete':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                jsonResponseEmpresa(false, 'ID inválido.');
            }

            // Verificar se existe
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = :id AND tipo_cadastro = 'Empresa'");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                jsonResponseEmpresa(false, 'Empresa não encontrada.');
            }

            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id AND tipo_cadastro = 'Empresa'");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponseEmpresa(true, 'Empresa excluída com sucesso.');
            break;

        default:
            jsonResponseEmpresa(false, 'Ação inválida.');
    }
} catch (Exception $e) {
    registrarLog('ERRO', 'Erro no CRUD de empresas: ' . $e->getMessage(), [
        'action' => $action,
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ]);

    jsonResponseEmpresa(false, 'Erro ao processar a requisição de empresas. Detalhes: ' . $e->getMessage());
}
?>

