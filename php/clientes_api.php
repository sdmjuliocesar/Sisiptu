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

function jsonResponseCli($sucesso, $mensagem, $extra = []) {
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

function validarCpfCnpj($valor) {
    $doc = preg_replace('/\D/', '', $valor ?? '');
    if (strlen($doc) === 11) {
        return validarCPF($doc);
    }
    if (strlen($doc) === 14) {
        return validarCNPJ($doc);
    }
    return false;
}

function validarCPF($cpf) {
    if (strlen($cpf) != 11) return false;
    if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += intval($cpf[$i]) * (10 - $i);
    }
    $resto = ($soma * 10) % 11;
    if ($resto == 10 || $resto == 11) $resto = 0;
    if ($resto != intval($cpf[9])) return false;

    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += intval($cpf[$i]) * (11 - $i);
    }
    $resto = ($soma * 10) % 11;
    if ($resto == 10 || $resto == 11) $resto = 0;
    if ($resto != intval($cpf[10])) return false;

    return true;
}

function validarCNPJ($cnpj) {
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
                    SELECT id, cpf_cnpj, nome, tipo_cadastro, cidade, uf, email, tel_celular1,
                           ativo, data_criacao, data_atualizacao
                    FROM clientes
                    WHERE nome ILIKE :q OR cpf_cnpj ILIKE :q
                    ORDER BY nome
                ");
                $stmt->bindParam(':q', $like);
                $stmt->execute();
            } else {
                $stmt = $pdo->query("
                    SELECT id, cpf_cnpj, nome, tipo_cadastro, cidade, uf, email, tel_celular1,
                           ativo, data_criacao, data_atualizacao
                    FROM clientes
                    ORDER BY nome
                ");
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponseCli(true, 'Lista de clientes carregada com sucesso.', ['clientes' => $rows]);
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                jsonResponseCli(false, 'ID inválido.');
            }

            $stmt = $pdo->prepare("
                SELECT id, cpf_cnpj, nome, tipo_cadastro, cep, endereco, bairro, cidade, uf,
                       cod_municipio, data_nasc, profissao, identidade, estado_civil,
                       nacionalidade, regime_casamento, email, site, tel_comercial,
                       tel_celular1, tel_celular2, tel_residencial, cpf_conjuge, nome_conjuge,
                       ativo
                FROM clientes
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                jsonResponseCli(false, 'Cliente não encontrado.');
            }

            jsonResponseCli(true, 'Cliente carregado com sucesso.', ['cliente' => $row]);
            break;
            
        case 'get-by-cpf-cnpj':
            $cpf_cnpj = isset($_GET['cpf_cnpj']) ? preg_replace('/[^0-9]/', '', trim($_GET['cpf_cnpj'])) : '';
            if (empty($cpf_cnpj)) {
                jsonResponseCli(false, 'CPF/CNPJ não informado.');
            }

            $stmt = $pdo->prepare("
                SELECT id, cpf_cnpj, nome, tipo_cadastro
                FROM clientes
                WHERE REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = :cpf_cnpj
                LIMIT 1
            ");
            $stmt->bindParam(':cpf_cnpj', $cpf_cnpj, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                jsonResponseCli(false, 'Cliente não encontrado.');
            }

            jsonResponseCli(true, 'Cliente encontrado.', ['cliente' => $row]);
            break;
            
        case 'verificar-cliente':
            $cpf_cnpj = isset($_REQUEST['cpf_cnpj']) ? trim($_REQUEST['cpf_cnpj']) : '';
            $cliente_id = isset($_REQUEST['cliente_id']) ? intval($_REQUEST['cliente_id']) : 0;
            
            if (empty($cpf_cnpj)) {
                jsonResponseCli(false, 'CPF/CNPJ não informado.');
            }
            
            // Validar CPF/CNPJ
            if (!validarCpfCnpj($cpf_cnpj)) {
                jsonResponseCli(false, 'CPF/CNPJ inválido.');
            }
            
            $cpf_cnpj_limpo = preg_replace('/[^0-9]/', '', $cpf_cnpj);
            
            $sql = "SELECT id, cpf_cnpj, nome, tipo_cadastro, cidade, uf, email, tel_celular1, ativo
                    FROM clientes
                    WHERE REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = :cpf_cnpj";
            
            if ($cliente_id > 0) {
                $sql .= " AND id != :cliente_id";
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':cpf_cnpj', $cpf_cnpj_limpo, PDO::PARAM_STR);
            if ($cliente_id > 0) {
                $stmt->bindParam(':cliente_id', $cliente_id, PDO::PARAM_INT);
            }
            $stmt->execute();
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($cliente) {
                jsonResponseCli(true, 'Cliente encontrado', [
                    'existe' => true,
                    'cliente' => $cliente
                ]);
            } else {
                jsonResponseCli(true, 'Cliente não encontrado', [
                    'existe' => false
                ]);
            }
            break;

        case 'create':
            $cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $tipo_cadastro = trim($_POST['tipo_cadastro'] ?? '');
            $cep = trim($_POST['cep'] ?? '');
            $endereco = trim($_POST['endereco'] ?? '');
            $bairro = trim($_POST['bairro'] ?? '');
            $cidade = trim($_POST['cidade'] ?? '');
            $uf = strtoupper(trim($_POST['uf'] ?? ''));
            $cod_municipio = trim($_POST['cod_municipio'] ?? '');
            $data_nasc = trim($_POST['data_nasc'] ?? '');
            $profissao = trim($_POST['profissao'] ?? '');
            $identidade = trim($_POST['identidade'] ?? '');
            $estado_civil = trim($_POST['estado_civil'] ?? '');
            $nacionalidade = trim($_POST['nacionalidade'] ?? '');
            $regime_casamento = trim($_POST['regime_casamento'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $site = trim($_POST['site'] ?? '');
            $tel_comercial = trim($_POST['tel_comercial'] ?? '');
            $tel_celular1 = trim($_POST['tel_celular1'] ?? '');
            $tel_celular2 = trim($_POST['tel_celular2'] ?? '');
            $tel_residencial = trim($_POST['tel_residencial'] ?? '');
            $cpf_conjuge = trim($_POST['cpf_conjuge'] ?? '');
            $nome_conjuge = trim($_POST['nome_conjuge'] ?? '');
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            if ($cpf_cnpj === '' || $nome === '') {
                jsonResponseCli(false, 'CPF/CNPJ e Nome do Cliente são obrigatórios.');
            }

            if (!validarCpfCnpj($cpf_cnpj)) {
                jsonResponseCli(false, 'CPF/CNPJ inválido.');
            }

            // Verificar se já existe cliente com mesmo CPF/CNPJ
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE cpf_cnpj = :cpf_cnpj");
            $stmt->bindParam(':cpf_cnpj', $cpf_cnpj);
            $stmt->execute();
            if ($stmt->fetch()) {
                jsonResponseCli(false, 'Já existe um cliente cadastrado com este CPF/CNPJ.');
            }

            // Se data_nasc vier vazia, usar NULL
            $data_nasc_db = $data_nasc === '' ? null : $data_nasc;

            $stmt = $pdo->prepare("
                INSERT INTO clientes (
                    cpf_cnpj, nome, tipo_cadastro, cep, endereco, bairro, cidade, uf,
                    cod_municipio, data_nasc, profissao, identidade, estado_civil,
                    nacionalidade, regime_casamento, email, site, tel_comercial,
                    tel_celular1, tel_celular2, tel_residencial, cpf_conjuge, nome_conjuge,
                    ativo
                )
                VALUES (
                    :cpf_cnpj, :nome, :tipo_cadastro, :cep, :endereco, :bairro, :cidade, :uf,
                    :cod_municipio, :data_nasc, :profissao, :identidade, :estado_civil,
                    :nacionalidade, :regime_casamento, :email, :site, :tel_comercial,
                    :tel_celular1, :tel_celular2, :tel_residencial, :cpf_conjuge, :nome_conjuge,
                    :ativo
                )
            ");
            $stmt->bindParam(':cpf_cnpj', $cpf_cnpj);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':tipo_cadastro', $tipo_cadastro);
            $stmt->bindParam(':cep', $cep);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':uf', $uf);
            $stmt->bindParam(':cod_municipio', $cod_municipio);
            if ($data_nasc_db === null) {
                $stmt->bindValue(':data_nasc', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':data_nasc', $data_nasc_db);
            }
            $stmt->bindParam(':profissao', $profissao);
            $stmt->bindParam(':identidade', $identidade);
            $stmt->bindParam(':estado_civil', $estado_civil);
            $stmt->bindParam(':nacionalidade', $nacionalidade);
            $stmt->bindParam(':regime_casamento', $regime_casamento);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':site', $site);
            $stmt->bindParam(':tel_comercial', $tel_comercial);
            $stmt->bindParam(':tel_celular1', $tel_celular1);
            $stmt->bindParam(':tel_celular2', $tel_celular2);
            $stmt->bindParam(':tel_residencial', $tel_residencial);
            $stmt->bindParam(':cpf_conjuge', $cpf_conjuge);
            $stmt->bindParam(':nome_conjuge', $nome_conjuge);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->execute();

            jsonResponseCli(true, 'Cliente criado com sucesso.');
            break;

        case 'update':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $tipo_cadastro = trim($_POST['tipo_cadastro'] ?? '');
            $cep = trim($_POST['cep'] ?? '');
            $endereco = trim($_POST['endereco'] ?? '');
            $bairro = trim($_POST['bairro'] ?? '');
            $cidade = trim($_POST['cidade'] ?? '');
            $uf = strtoupper(trim($_POST['uf'] ?? ''));
            $cod_municipio = trim($_POST['cod_municipio'] ?? '');
            $data_nasc = trim($_POST['data_nasc'] ?? '');
            $profissao = trim($_POST['profissao'] ?? '');
            $identidade = trim($_POST['identidade'] ?? '');
            $estado_civil = trim($_POST['estado_civil'] ?? '');
            $nacionalidade = trim($_POST['nacionalidade'] ?? '');
            $regime_casamento = trim($_POST['regime_casamento'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $site = trim($_POST['site'] ?? '');
            $tel_comercial = trim($_POST['tel_comercial'] ?? '');
            $tel_celular1 = trim($_POST['tel_celular1'] ?? '');
            $tel_celular2 = trim($_POST['tel_celular2'] ?? '');
            $tel_residencial = trim($_POST['tel_residencial'] ?? '');
            $cpf_conjuge = trim($_POST['cpf_conjuge'] ?? '');
            $nome_conjuge = trim($_POST['nome_conjuge'] ?? '');
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            if ($id <= 0) {
                jsonResponseCli(false, 'ID inválido.');
            }

            if ($cpf_cnpj === '' || $nome === '') {
                jsonResponseCli(false, 'CPF/CNPJ e Nome do Cliente são obrigatórios.');
            }

            if (!validarCpfCnpj($cpf_cnpj)) {
                jsonResponseCli(false, 'CPF/CNPJ inválido.');
            }

            // Verificar se já existe outro cliente com mesmo CPF/CNPJ
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE cpf_cnpj = :cpf_cnpj AND id <> :id");
            $stmt->bindParam(':cpf_cnpj', $cpf_cnpj);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetch()) {
                jsonResponseCli(false, 'Já existe outro cliente cadastrado com este CPF/CNPJ.');
            }

            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                jsonResponseCli(false, 'Cliente não encontrado.');
            }

            // Se data_nasc vier vazia, usar NULL
            $data_nasc_db = $data_nasc === '' ? null : $data_nasc;

            $stmt = $pdo->prepare("
                UPDATE clientes
                SET cpf_cnpj = :cpf_cnpj,
                    nome = :nome,
                    tipo_cadastro = :tipo_cadastro,
                    cep = :cep,
                    endereco = :endereco,
                    bairro = :bairro,
                    cidade = :cidade,
                    uf = :uf,
                    cod_municipio = :cod_municipio,
                    data_nasc = :data_nasc,
                    profissao = :profissao,
                    identidade = :identidade,
                    estado_civil = :estado_civil,
                    nacionalidade = :nacionalidade,
                    regime_casamento = :regime_casamento,
                    email = :email,
                    site = :site,
                    tel_comercial = :tel_comercial,
                    tel_celular1 = :tel_celular1,
                    tel_celular2 = :tel_celular2,
                    tel_residencial = :tel_residencial,
                    cpf_conjuge = :cpf_conjuge,
                    nome_conjuge = :nome_conjuge,
                    ativo = :ativo,
                    data_atualizacao = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->bindParam(':cpf_cnpj', $cpf_cnpj);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':tipo_cadastro', $tipo_cadastro);
            $stmt->bindParam(':cep', $cep);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':bairro', $bairro);
            $stmt->bindParam(':cidade', $cidade);
            $stmt->bindParam(':uf', $uf);
            $stmt->bindParam(':cod_municipio', $cod_municipio);
            if ($data_nasc_db === null) {
                $stmt->bindValue(':data_nasc', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':data_nasc', $data_nasc_db);
            }
            $stmt->bindParam(':profissao', $profissao);
            $stmt->bindParam(':identidade', $identidade);
            $stmt->bindParam(':estado_civil', $estado_civil);
            $stmt->bindParam(':nacionalidade', $nacionalidade);
            $stmt->bindParam(':regime_casamento', $regime_casamento);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':site', $site);
            $stmt->bindParam(':tel_comercial', $tel_comercial);
            $stmt->bindParam(':tel_celular1', $tel_celular1);
            $stmt->bindParam(':tel_celular2', $tel_celular2);
            $stmt->bindParam(':tel_residencial', $tel_residencial);
            $stmt->bindParam(':cpf_conjuge', $cpf_conjuge);
            $stmt->bindParam(':nome_conjuge', $nome_conjuge);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponseCli(true, 'Cliente atualizado com sucesso.');
            break;

        case 'delete':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                jsonResponseCli(false, 'ID inválido.');
            }

            // Verificar se o cliente possui contratos cadastrados
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contratos WHERE cliente_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['total'] > 0) {
                jsonResponseCli(false, 'Não é possível excluir este cliente pois ele possui ' . $result['total'] . ' contrato(s) cadastrado(s). Remova os contratos antes de excluir o cliente.');
            }

            try {
                $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                jsonResponseCli(true, 'Cliente excluído com sucesso.');
            } catch (PDOException $e) {
                // Verificar se é erro de foreign key
                if ($e->getCode() === '23503' || strpos($e->getMessage(), 'viola restrição de chave estrangeira') !== false) {
                    // Verificar se há contratos
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM contratos WHERE cliente_id = :id");
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result && $result['total'] > 0) {
                        jsonResponseCli(false, 'Não é possível excluir este cliente pois ele possui ' . $result['total'] . ' contrato(s) cadastrado(s). Remova os contratos antes de excluir o cliente.');
                    } else {
                        jsonResponseCli(false, 'Não é possível excluir este cliente pois ele possui registros relacionados no sistema.');
                    }
                } else {
                    throw $e; // Re-lançar se não for erro de foreign key
                }
            }
            break;

        default:
            jsonResponseCli(false, 'Ação inválida.');
    }
} catch (Exception $e) {
    registrarLog('ERRO', 'Erro no CRUD de clientes: ' . $e->getMessage(), [
        'action' => $action,
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ]);

    jsonResponseCli(false, 'Erro ao processar a requisição de clientes. Detalhes: ' . $e->getMessage());
}
