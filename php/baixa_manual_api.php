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

function jsonResponseBaixa($sucesso, $mensagem, $extra = []) {
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

try {
    $pdo = getConnection();

    switch ($action) {
        case 'pesquisar-contrato':
            $empreendimento_id = isset($_GET['empreendimento_id']) ? (int)$_GET['empreendimento_id'] : 0;
            $modulo_id = isset($_GET['modulo_id']) ? (int)$_GET['modulo_id'] : 0;
            $contrato = isset($_GET['contrato']) ? trim($_GET['contrato']) : '';
            $titulo = isset($_GET['titulo']) ? trim($_GET['titulo']) : '';

            // Validar campos obrigatórios
            if ($empreendimento_id <= 0) {
                jsonResponseBaixa(false, 'Empreendimento é obrigatório.');
            }

            if ($modulo_id <= 0) {
                jsonResponseBaixa(false, 'Módulo é obrigatório.');
            }

            if ($contrato === '') {
                jsonResponseBaixa(false, 'Contrato é obrigatório.');
            }

            if ($titulo === '') {
                jsonResponseBaixa(false, 'Número do Título é obrigatório.');
            }

            // Buscar todas as parcelas do contrato
            $sql = "
                SELECT 
                    c.id,
                    c.titulo,
                    c.empreendimento_id,
                    e.nome AS empreendimento_nome,
                    c.modulo_id,
                    m.nome AS modulo_nome,
                    c.contrato,
                    c.cliente_nome,
                    c.cpf_cnpj,
                    c.datavencimento,
                    c.valor_mensal,
                    c.multas,
                    c.juros,
                    c.situacao,
                    c.pago,
                    c.datapagamento,
                    c.databaixa,
                    c.observacao
                FROM cobranca c
                LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
                LEFT JOIN modulos m ON m.id = c.modulo_id
                WHERE c.empreendimento_id = :empreendimento_id
                  AND c.modulo_id = :modulo_id
                  AND c.contrato = :contrato
                  AND (c.titulo = :titulo OR c.titulo LIKE :titulo_like)
                ORDER BY c.datavencimento ASC, c.id ASC
            ";

            $params = [
                ':empreendimento_id' => $empreendimento_id,
                ':modulo_id' => $modulo_id,
                ':contrato' => $contrato,
                ':titulo' => $titulo,
                ':titulo_like' => '%' . $titulo . '%'
            ];

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $cobrancas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($cobrancas)) {
                jsonResponseBaixa(false, 'Contrato não encontrado ou não possui parcelas cadastradas.');
            }

            jsonResponseBaixa(true, 'Contrato encontrado com sucesso.', ['cobrancas' => $cobrancas]);
            break;

        case 'pesquisar':
            $empreendimento_id = isset($_GET['empreendimento_id']) ? (int)$_GET['empreendimento_id'] : 0;
            $modulo_id = isset($_GET['modulo_id']) ? (int)$_GET['modulo_id'] : 0;
            $contrato = isset($_GET['contrato']) ? trim($_GET['contrato']) : '';
            $titulo = isset($_GET['titulo']) ? trim($_GET['titulo']) : '';
            $status_pago = isset($_GET['status_pago']) ? trim($_GET['status_pago']) : '';

            $sql = "
                SELECT 
                    c.id,
                    c.titulo,
                    c.empreendimento_id,
                    e.nome AS empreendimento_nome,
                    c.modulo_id,
                    m.nome AS modulo_nome,
                    c.contrato,
                    c.cliente_nome,
                    c.cpf_cnpj,
                    c.datavencimento,
                    c.valor_mensal,
                    c.multas,
                    c.juros,
                    c.situacao,
                    c.pago,
                    c.datapagamento,
                    c.databaixa,
                    c.observacao
                FROM cobranca c
                LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
                LEFT JOIN modulos m ON m.id = c.modulo_id
                WHERE 1=1
            ";

            $params = [];

            if ($empreendimento_id > 0) {
                $sql .= " AND c.empreendimento_id = :empreendimento_id";
                $params[':empreendimento_id'] = $empreendimento_id;
            }

            if ($modulo_id > 0) {
                $sql .= " AND c.modulo_id = :modulo_id";
                $params[':modulo_id'] = $modulo_id;
            }

            if ($contrato) {
                $sql .= " AND c.contrato = :contrato";
                $params[':contrato'] = $contrato;
            }

            if ($titulo) {
                $sql .= " AND (c.titulo = :titulo OR c.titulo LIKE :titulo_like)";
                $params[':titulo'] = $titulo;
                $params[':titulo_like'] = '%' . $titulo . '%';
            }

            if ($status_pago === 'S') {
                $sql .= " AND c.pago = 'S'";
            } elseif ($status_pago === 'N') {
                $sql .= " AND (c.pago = 'N' OR c.pago IS NULL)";
            }

            $sql .= " ORDER BY c.datavencimento DESC, c.id DESC";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $cobrancas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponseBaixa(true, 'Pesquisa realizada com sucesso.', ['cobrancas' => $cobrancas]);
            break;

        case 'baixar-estornar':
            $cobranca_id = isset($_POST['cobranca_id']) ? (int)$_POST['cobranca_id'] : 0;
            $tipo_operacao = isset($_POST['tipo_operacao']) ? trim($_POST['tipo_operacao']) : '';
            $data_pagamento = isset($_POST['data_pagamento']) ? trim($_POST['data_pagamento']) : '';
            $data_baixa = isset($_POST['data_baixa']) ? trim($_POST['data_baixa']) : '';
            $observacao = isset($_POST['observacao']) ? trim($_POST['observacao']) : '';
            $usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';

            if ($cobranca_id <= 0) {
                jsonResponseBaixa(false, 'ID da cobrança inválido.');
            }

            if (!in_array($tipo_operacao, ['baixar', 'estornar'])) {
                jsonResponseBaixa(false, 'Tipo de operação inválido.');
            }

            if (!$data_pagamento || !$data_baixa) {
                jsonResponseBaixa(false, 'Data de pagamento e data de baixa são obrigatórias.');
            }

            // Verificar se a cobrança existe
            $stmt = $pdo->prepare("SELECT id, pago FROM cobranca WHERE id = :id");
            $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
            $stmt->execute();
            $cobranca = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cobranca) {
                jsonResponseBaixa(false, 'Cobrança não encontrada.');
            }

            // Validar operação
            if ($tipo_operacao === 'baixar' && $cobranca['pago'] === 'S') {
                jsonResponseBaixa(false, 'Esta cobrança já está paga. Use a opção "Estornar" para reverter.');
            }

            if ($tipo_operacao === 'estornar' && ($cobranca['pago'] !== 'S' && $cobranca['pago'] !== '1')) {
                jsonResponseBaixa(false, 'Esta cobrança não está paga. Não é possível estornar.');
            }

            // Atualizar cobrança
            $pago = $tipo_operacao === 'baixar' ? 'S' : 'N';
            
            // Se estornar, limpar datas de pagamento e baixa
            if ($tipo_operacao === 'estornar') {
                $data_pagamento = null;
                $data_baixa = null;
            }

            $sql = "
                UPDATE cobranca SET
                    pago = :pago,
                    datapagamento = :datapagamento,
                    databaixa = :databaixa,
                    usuario = :usuario
            ";

            // Adicionar observação se fornecida
            if ($observacao) {
                $observacaoAtual = '';
                $stmt = $pdo->prepare("SELECT observacao FROM cobranca WHERE id = :id");
                $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
                $stmt->execute();
                $cobrancaAtual = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($cobrancaAtual && $cobrancaAtual['observacao']) {
                    $observacaoAtual = $cobrancaAtual['observacao'] . "\n";
                }
                $observacaoAtual .= date('d/m/Y H:i') . ' - ' . ($tipo_operacao === 'baixar' ? 'BAIXA' : 'ESTORNO') . ': ' . $observacao;
                $sql .= ", observacao = :observacao";
            }

            $sql .= " WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':pago', $pago);
            $stmt->bindParam(':datapagamento', $data_pagamento);
            $stmt->bindParam(':databaixa', $data_baixa);
            $stmt->bindParam(':usuario', $usuario);
            if ($observacao) {
                $stmt->bindParam(':observacao', $observacaoAtual);
            }
            $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
            $stmt->execute();

            $mensagem = $tipo_operacao === 'baixar' 
                ? 'Cobrança baixada (liquidada) com sucesso!' 
                : 'Cobrança estornada com sucesso!';

            jsonResponseBaixa(true, $mensagem);
            break;

        default:
            jsonResponseBaixa(false, 'Ação inválida.');
    }
} catch (PDOException $e) {
    registrarLog('ERRO', 'Erro no processamento de baixa manual: ' . $e->getMessage(), [
        'action' => $action,
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ]);

    jsonResponseBaixa(false, 'Erro ao processar a requisição de baixa manual. Detalhes: ' . $e->getMessage());
} catch (Exception $e) {
    registrarLog('ERRO', 'Erro geral no processamento de baixa manual: ' . $e->getMessage(), [
        'action' => $action,
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ]);

    jsonResponseBaixa(false, 'Erro ao processar a requisição de baixa manual. Detalhes: ' . $e->getMessage());
}

