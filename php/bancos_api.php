<?php
// Iniciar output buffering para evitar saída prematura
ob_start();

// Desabilitar exibição de erros na saída
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/logger.php';

// Limpar buffer antes de enviar JSON
ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Acesso não autorizado. Faça login novamente.'
    ]);
    exit;
}

function jsonResponseBanco($sucesso, $mensagem, $extra = []) {
    // Limpar qualquer saída anterior
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

// Funções de validação CPF/CNPJ (copiadas de clientes_api.php)
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
            $stmt = $pdo->query("
                SELECT id, cedente, cnpj_cpf, banco, conta, agencia, num_banco, carteira,
                       operacao_cc, apelido, convenio, multa_mes, tarifa_bancaria, juros_mes,
                       prazo_devolucao, codigo_cedente, operacao_cedente, emissao_via_banco,
                       integracao_bancaria, instrucoes_bancarias, caminho_remessa, caminho_retorno,
                       ativo, data_criacao, data_atualizacao
                FROM bancos
                ORDER BY id DESC
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            jsonResponseBanco(true, 'Lista de bancos carregada com sucesso.', ['bancos' => $rows]);
            break;

        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                jsonResponseBanco(false, 'ID inválido.');
            }

            $stmt = $pdo->prepare("
                SELECT id, cedente, cnpj_cpf, banco, conta, agencia, num_banco, carteira,
                       operacao_cc, apelido, convenio, multa_mes, tarifa_bancaria, juros_mes,
                       prazo_devolucao, codigo_cedente, operacao_cedente, emissao_via_banco,
                       integracao_bancaria, instrucoes_bancarias, caminho_remessa, caminho_retorno, ativo
                FROM bancos
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                jsonResponseBanco(false, 'Banco não encontrado.');
            }

            jsonResponseBanco(true, 'Banco carregado com sucesso.', ['banco' => $row]);
            break;

        case 'create':
            $cedente = trim($_POST['cedente'] ?? '');
            $cnpj_cpf = trim($_POST['cnpj_cpf'] ?? '');
            $banco = trim($_POST['banco'] ?? '');
            $conta = trim($_POST['conta'] ?? '');
            $agencia = trim($_POST['agencia'] ?? '');
            $num_banco = trim($_POST['num_banco'] ?? '');
            $carteira = trim($_POST['carteira'] ?? '');
            $operacao_cc = trim($_POST['operacao_cc'] ?? '');
            $apelido = trim($_POST['apelido'] ?? '');
            $convenio = trim($_POST['convenio'] ?? '');
            // Processar valores numéricos, removendo espaços e convertendo vírgula para ponto
            $multa_mes = isset($_POST['multa_mes']) && trim($_POST['multa_mes']) !== '' ? 
                (float)str_replace(',', '.', trim($_POST['multa_mes'])) : null;
            $tarifa_bancaria = isset($_POST['tarifa_bancaria']) && trim($_POST['tarifa_bancaria']) !== '' ? 
                (float)str_replace(',', '.', trim($_POST['tarifa_bancaria'])) : null;
            $juros_mes = isset($_POST['juros_mes']) && trim($_POST['juros_mes']) !== '' ? 
                (float)str_replace(',', '.', trim($_POST['juros_mes'])) : null;
            $prazo_devolucao = isset($_POST['prazo_devolucao']) && trim($_POST['prazo_devolucao']) !== '' ? 
                (int)trim($_POST['prazo_devolucao']) : null;
            $codigo_cedente = trim($_POST['codigo_cedente'] ?? '');
            $operacao_cedente = trim($_POST['operacao_cedente'] ?? '');
            $emissao_via_banco = isset($_POST['emissao_via_banco']) && $_POST['emissao_via_banco'] === '1';
            $integracao_bancaria = isset($_POST['integracao_bancaria']) && $_POST['integracao_bancaria'] === '1';
            $instrucoes_bancarias = trim($_POST['instrucoes_bancarias'] ?? '');
            $caminho_remessa = trim($_POST['caminho_remessa'] ?? '');
            $caminho_retorno = trim($_POST['caminho_retorno'] ?? '');
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            // Validação CPF/CNPJ
            if ($cnpj_cpf !== '') {
                if (!validarCpfCnpj($cnpj_cpf)) {
                    jsonResponseBanco(false, 'CPF/CNPJ inválido.');
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO bancos (
                    cedente, cnpj_cpf, banco, conta, agencia, num_banco, carteira,
                    operacao_cc, apelido, convenio, multa_mes, tarifa_bancaria, juros_mes,
                    prazo_devolucao, codigo_cedente, operacao_cedente, emissao_via_banco,
                    integracao_bancaria, instrucoes_bancarias, caminho_remessa, caminho_retorno, ativo
                )
                VALUES (
                    :cedente, :cnpj_cpf, :banco, :conta, :agencia, :num_banco, :carteira,
                    :operacao_cc, :apelido, :convenio, :multa_mes, :tarifa_bancaria, :juros_mes,
                    :prazo_devolucao, :codigo_cedente, :operacao_cedente, :emissao_via_banco,
                    :integracao_bancaria, :instrucoes_bancarias, :caminho_remessa, :caminho_retorno, :ativo
                )
            ");
            $stmt->bindParam(':cedente', $cedente);
            $stmt->bindParam(':cnpj_cpf', $cnpj_cpf);
            $stmt->bindParam(':banco', $banco);
            $stmt->bindParam(':conta', $conta);
            $stmt->bindParam(':agencia', $agencia);
            $stmt->bindParam(':num_banco', $num_banco);
            $stmt->bindParam(':carteira', $carteira);
            $stmt->bindParam(':operacao_cc', $operacao_cc);
            $stmt->bindParam(':apelido', $apelido);
            $stmt->bindParam(':convenio', $convenio);
            $stmt->bindValue(':multa_mes', $multa_mes, PDO::PARAM_NULL);
            $stmt->bindValue(':tarifa_bancaria', $tarifa_bancaria, PDO::PARAM_NULL);
            $stmt->bindValue(':juros_mes', $juros_mes, PDO::PARAM_NULL);
            $stmt->bindValue(':prazo_devolucao', $prazo_devolucao, PDO::PARAM_NULL);
            $stmt->bindParam(':codigo_cedente', $codigo_cedente);
            $stmt->bindParam(':operacao_cedente', $operacao_cedente);
            $stmt->bindValue(':emissao_via_banco', $emissao_via_banco, PDO::PARAM_BOOL);
            $stmt->bindValue(':integracao_bancaria', $integracao_bancaria, PDO::PARAM_BOOL);
            $stmt->bindParam(':instrucoes_bancarias', $instrucoes_bancarias);
            $stmt->bindParam(':caminho_remessa', $caminho_remessa);
            $stmt->bindParam(':caminho_retorno', $caminho_retorno);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->execute();

            jsonResponseBanco(true, 'Conta corrente criada com sucesso.');
            break;

        case 'update':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                jsonResponseBanco(false, 'ID inválido.');
            }

            // Verificar se existe
            $stmt = $pdo->prepare("SELECT id FROM bancos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if (!$stmt->fetch()) {
                jsonResponseBanco(false, 'Banco não encontrado.');
            }

            $cedente = trim($_POST['cedente'] ?? '');
            $cnpj_cpf = trim($_POST['cnpj_cpf'] ?? '');
            $banco = trim($_POST['banco'] ?? '');
            $conta = trim($_POST['conta'] ?? '');
            $agencia = trim($_POST['agencia'] ?? '');
            $num_banco = trim($_POST['num_banco'] ?? '');
            $carteira = trim($_POST['carteira'] ?? '');
            $operacao_cc = trim($_POST['operacao_cc'] ?? '');
            $apelido = trim($_POST['apelido'] ?? '');
            $convenio = trim($_POST['convenio'] ?? '');
            // Processar valores numéricos, removendo espaços e convertendo vírgula para ponto
            $multa_mes = isset($_POST['multa_mes']) && trim($_POST['multa_mes']) !== '' ? 
                (float)str_replace(',', '.', trim($_POST['multa_mes'])) : null;
            $tarifa_bancaria = isset($_POST['tarifa_bancaria']) && trim($_POST['tarifa_bancaria']) !== '' ? 
                (float)str_replace(',', '.', trim($_POST['tarifa_bancaria'])) : null;
            $juros_mes = isset($_POST['juros_mes']) && trim($_POST['juros_mes']) !== '' ? 
                (float)str_replace(',', '.', trim($_POST['juros_mes'])) : null;
            $prazo_devolucao = isset($_POST['prazo_devolucao']) && trim($_POST['prazo_devolucao']) !== '' ? 
                (int)trim($_POST['prazo_devolucao']) : null;
            $codigo_cedente = trim($_POST['codigo_cedente'] ?? '');
            $operacao_cedente = trim($_POST['operacao_cedente'] ?? '');
            $emissao_via_banco = isset($_POST['emissao_via_banco']) && $_POST['emissao_via_banco'] === '1';
            $integracao_bancaria = isset($_POST['integracao_bancaria']) && $_POST['integracao_bancaria'] === '1';
            $instrucoes_bancarias = trim($_POST['instrucoes_bancarias'] ?? '');
            $caminho_remessa = trim($_POST['caminho_remessa'] ?? '');
            $caminho_retorno = trim($_POST['caminho_retorno'] ?? '');
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            // Validação CPF/CNPJ
            if ($cnpj_cpf !== '') {
                if (!validarCpfCnpj($cnpj_cpf)) {
                    jsonResponseBanco(false, 'CPF/CNPJ inválido.');
                }
            }

            $stmt = $pdo->prepare("
                UPDATE bancos
                SET cedente = :cedente,
                    cnpj_cpf = :cnpj_cpf,
                    banco = :banco,
                    conta = :conta,
                    agencia = :agencia,
                    num_banco = :num_banco,
                    carteira = :carteira,
                    operacao_cc = :operacao_cc,
                    apelido = :apelido,
                    convenio = :convenio,
                    multa_mes = :multa_mes,
                    tarifa_bancaria = :tarifa_bancaria,
                    juros_mes = :juros_mes,
                    prazo_devolucao = :prazo_devolucao,
                    codigo_cedente = :codigo_cedente,
                    operacao_cedente = :operacao_cedente,
                    emissao_via_banco = :emissao_via_banco,
                    integracao_bancaria = :integracao_bancaria,
                    instrucoes_bancarias = :instrucoes_bancarias,
                    caminho_remessa = :caminho_remessa,
                    caminho_retorno = :caminho_retorno,
                    ativo = :ativo,
                    data_atualizacao = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->bindParam(':cedente', $cedente);
            $stmt->bindParam(':cnpj_cpf', $cnpj_cpf);
            $stmt->bindParam(':banco', $banco);
            $stmt->bindParam(':conta', $conta);
            $stmt->bindParam(':agencia', $agencia);
            $stmt->bindParam(':num_banco', $num_banco);
            $stmt->bindParam(':carteira', $carteira);
            $stmt->bindParam(':operacao_cc', $operacao_cc);
            $stmt->bindParam(':apelido', $apelido);
            $stmt->bindParam(':convenio', $convenio);
            $stmt->bindValue(':multa_mes', $multa_mes, PDO::PARAM_NULL);
            $stmt->bindValue(':tarifa_bancaria', $tarifa_bancaria, PDO::PARAM_NULL);
            $stmt->bindValue(':juros_mes', $juros_mes, PDO::PARAM_NULL);
            $stmt->bindValue(':prazo_devolucao', $prazo_devolucao, PDO::PARAM_NULL);
            $stmt->bindParam(':codigo_cedente', $codigo_cedente);
            $stmt->bindParam(':operacao_cedente', $operacao_cedente);
            $stmt->bindValue(':emissao_via_banco', $emissao_via_banco, PDO::PARAM_BOOL);
            $stmt->bindValue(':integracao_bancaria', $integracao_bancaria, PDO::PARAM_BOOL);
            $stmt->bindParam(':instrucoes_bancarias', $instrucoes_bancarias);
            $stmt->bindParam(':caminho_remessa', $caminho_remessa);
            $stmt->bindParam(':caminho_retorno', $caminho_retorno);
            $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponseBanco(true, 'Conta corrente atualizada com sucesso.');
            break;

        case 'delete':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                jsonResponseBanco(false, 'ID inválido.');
            }

            $stmt = $pdo->prepare("DELETE FROM bancos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponseBanco(true, 'Conta corrente excluída com sucesso.');
            break;

        default:
            jsonResponseBanco(false, 'Ação inválida.');
    }
} catch (PDOException $e) {
    // Capturar erro de violação de chave estrangeira
    if ($e->getCode() === '23503') { // PostgreSQL foreign key violation error code
        jsonResponseBanco(false, 'Não é possível excluir o banco pois ele possui empreendimentos vinculados.');
    } else {
        registrarLog('ERRO', 'Erro no CRUD de bancos: ' . $e->getMessage(), [
            'action' => $action,
            'erro' => $e->getMessage(),
            'arquivo' => $e->getFile(),
            'linha' => $e->getLine(),
        ]);
        jsonResponseBanco(false, 'Erro ao processar a requisição de bancos. Detalhes: ' . $e->getMessage());
    }
} catch (Exception $e) {
    registrarLog('ERRO', 'Erro no CRUD de bancos: ' . $e->getMessage(), [
        'action' => $action,
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ]);
    jsonResponseBanco(false, 'Erro ao processar a requisição de bancos. Detalhes: ' . $e->getMessage());
}
