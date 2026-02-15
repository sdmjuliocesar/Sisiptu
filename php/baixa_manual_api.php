<?php
// API Baixa Manual (restaurada do Git, com compatibilidade de colunas)
ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/logger.php';

function jsonResponseBaixa(bool $sucesso, string $mensagem, array $extra = [], int $statusCode = 200): void
{
    if (ob_get_length()) {
        @ob_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    jsonResponseBaixa(false, 'Acesso não autorizado. Faça login novamente.', [], 401);
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

function tableHasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = :t
          AND column_name = :c
        LIMIT 1
    ");
    $stmt->bindValue(':t', $table, PDO::PARAM_STR);
    $stmt->bindValue(':c', $column, PDO::PARAM_STR);
    $stmt->execute();
    return (bool)$stmt->fetchColumn();
}

try {
    $pdo = getConnection();

    // Detectar colunas para compatibilidade
    $hasTitulo = tableHasColumn($pdo, 'cobranca', 'titulo');
    $hasDatavencimento = tableHasColumn($pdo, 'cobranca', 'datavencimento');
    $hasDataVencimento = tableHasColumn($pdo, 'cobranca', 'data_vencimento');

    $colVenc = $hasDatavencimento ? 'c.datavencimento' : ($hasDataVencimento ? 'c.data_vencimento' : 'NULL');
    $selectTitulo = $hasTitulo ? 'c.titulo' : "c.id::text";

    switch ($action) {
        case 'pesquisar-contrato':
        case 'pesquisar-contrato-completo':
            $empreendimento_id = isset($_GET['empreendimento_id']) ? (int)$_GET['empreendimento_id'] : 0;
            $modulo_id = isset($_GET['modulo_id']) ? (int)$_GET['modulo_id'] : 0;
            $contrato = isset($_GET['contrato']) ? trim($_GET['contrato']) : '';
            $titulo = isset($_GET['titulo']) ? trim($_GET['titulo']) : '';

            if ($empreendimento_id <= 0 || $modulo_id <= 0 || $contrato === '') {
                jsonResponseBaixa(false, 'Empreendimento, Módulo e Contrato são obrigatórios.');
            }

            $sql = "
                SELECT 
                    c.id,
                    {$selectTitulo} AS titulo,
                    c.empreendimento_id,
                    e.nome AS empreendimento_nome,
                    c.modulo_id,
                    m.nome AS modulo_nome,
                    c.contrato,
                    c.cliente_nome,
                    c.cpf_cnpj,
                    {$colVenc} AS datavencimento,
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
            ";

            $params = [
                ':empreendimento_id' => $empreendimento_id,
                ':modulo_id' => $modulo_id,
                ':contrato' => $contrato,
            ];

            if ($action === 'pesquisar-contrato' && $titulo !== '') {
                if ($hasTitulo) {
                    $sql .= " AND (c.titulo = :titulo OR c.titulo LIKE :titulo_like)";
                    $params[':titulo'] = $titulo;
                    $params[':titulo_like'] = '%' . $titulo . '%';
                } else {
                    $sql .= " AND c.id = :id";
                    $params[':id'] = (int)$titulo;
                }
            }

            $sql .= " ORDER BY {$colVenc} ASC NULLS LAST, c.id ASC";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $cobrancas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($action === 'pesquisar-contrato') {
                if (empty($cobrancas)) {
                    jsonResponseBaixa(false, 'Contrato não encontrado ou não possui parcelas cadastradas.');
                }
                jsonResponseBaixa(true, 'Contrato encontrado com sucesso.', ['cobrancas' => $cobrancas]);
            }

            jsonResponseBaixa(true, 'Pesquisa realizada com sucesso.', ['cobrancas' => $cobrancas]);
            break;

        case 'validar-contrato':
            $empreendimento_id = isset($_GET['empreendimento_id']) ? (int)$_GET['empreendimento_id'] : 0;
            $modulo_id = isset($_GET['modulo_id']) ? (int)$_GET['modulo_id'] : 0;
            $contrato = isset($_GET['contrato']) ? trim($_GET['contrato']) : '';

            if ($empreendimento_id <= 0 || $modulo_id <= 0) {
                jsonResponseBaixa(false, 'Empreendimento e Módulo são obrigatórios.');
            }
            if ($contrato === '') {
                jsonResponseBaixa(false, 'Contrato é obrigatório.');
            }

            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM cobranca
                WHERE empreendimento_id = :empreendimento_id
                  AND modulo_id = :modulo_id
                  AND contrato = :contrato
            ");
            $stmt->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
            $stmt->bindParam(':modulo_id', $modulo_id, PDO::PARAM_INT);
            $stmt->bindParam(':contrato', $contrato);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            jsonResponseBaixa(($resultado['total'] ?? 0) > 0, (($resultado['total'] ?? 0) > 0) ? 'Contrato válido.' : 'Contrato não encontrado para este Empreendimento e Módulo.');
            break;

        case 'buscar-por-titulo':
            $empreendimento_id = isset($_GET['empreendimento_id']) ? (int)$_GET['empreendimento_id'] : 0;
            $modulo_id = isset($_GET['modulo_id']) ? (int)$_GET['modulo_id'] : 0;
            $contrato = isset($_GET['contrato']) ? trim($_GET['contrato']) : '';
            $titulo = isset($_GET['titulo']) ? trim($_GET['titulo']) : '';

            if ($empreendimento_id <= 0 || $modulo_id <= 0 || $contrato === '' || $titulo === '') {
                jsonResponseBaixa(false, 'Todos os campos são obrigatórios.');
            }

            $sql = "
                SELECT 
                    c.id,
                    {$selectTitulo} AS titulo,
                    c.empreendimento_id,
                    c.modulo_id,
                    c.contrato,
                    {$colVenc} AS datavencimento,
                    c.valor_mensal,
                    c.multas,
                    c.juros,
                    c.pago,
                    c.datapagamento,
                    c.databaixa,
                    c.tarifa_bancaria,
                    c.desconto,
                    c.forma_pagamento,
                    c.local_pagamento,
                    c.observacao,
                    c.cliente_nome
                FROM cobranca c
                WHERE c.empreendimento_id = :empreendimento_id
                  AND c.modulo_id = :modulo_id
                  AND c.contrato = :contrato
            ";

            $params = [
                ':empreendimento_id' => $empreendimento_id,
                ':modulo_id' => $modulo_id,
                ':contrato' => $contrato,
            ];

            if ($hasTitulo) {
                $sql .= " AND (c.titulo = :titulo OR c.id = :id)";
                $params[':titulo'] = $titulo;
                $params[':id'] = (int)$titulo;
            } else {
                $sql .= " AND c.id = :id";
                $params[':id'] = (int)$titulo;
            }

            $sql .= " LIMIT 1";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $cobranca = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cobranca) {
                jsonResponseBaixa(false, 'Cobrança não encontrada.');
            }

            jsonResponseBaixa(true, 'Cobrança encontrada.', ['cobranca' => $cobranca]);
            break;

        case 'calcular-juros-multas':
            $cobranca_id = isset($_GET['cobranca_id']) ? (int)$_GET['cobranca_id'] : 0;
            $data_pagamento = isset($_GET['data_pagamento']) ? trim($_GET['data_pagamento']) : '';

            if ($cobranca_id <= 0 || $data_pagamento === '') {
                jsonResponseBaixa(false, 'ID da cobrança e data de pagamento são obrigatórios.');
            }

            $stmt = $pdo->prepare("
                SELECT id, valor_mensal, {$colVenc} AS datavencimento, multas, juros
                FROM cobranca c
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
            $stmt->execute();
            $cobranca = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cobranca || empty($cobranca['datavencimento'])) {
                jsonResponseBaixa(false, 'Cobrança não encontrada ou sem data de vencimento.');
            }

            $valorMensal = (float)($cobranca['valor_mensal'] ?? 0);
            $dataVencimento = $cobranca['datavencimento'];
            $dataPagamento = new DateTime($data_pagamento);
            $dataVenc = new DateTime($dataVencimento);

            $diasAtraso = $dataVenc->diff($dataPagamento)->days;
            if ($dataVenc < $dataPagamento) {
                // Multa 2% e juros 0,033% ao dia (layout do Git)
                $multa = $valorMensal * 0.02;
                $juros = $valorMensal * (0.033 / 100) * $diasAtraso;
                jsonResponseBaixa(true, 'Juros e multas calculados.', [
                    'multa' => round($multa, 2),
                    'juros' => round($juros, 2),
                    'dias_atraso' => $diasAtraso
                ]);
            } else {
                jsonResponseBaixa(true, 'Parcela não está em atraso.', [
                    'multa' => 0,
                    'juros' => 0,
                    'dias_atraso' => 0
                ]);
            }
            break;

        case 'salvar-baixa-completa':
            $cobranca_id = isset($_POST['cobranca_id']) ? (int)$_POST['cobranca_id'] : 0;
            $tipo_operacao = isset($_POST['tipo_operacao']) ? trim($_POST['tipo_operacao']) : 'baixar';
            $data_pagamento = isset($_POST['data_pagamento']) ? trim($_POST['data_pagamento']) : '';
            $data_baixa = isset($_POST['data_baixa']) ? trim($_POST['data_baixa']) : '';
            $multa = isset($_POST['multa']) ? str_replace(',', '.', trim($_POST['multa'])) : '0';
            $juros = isset($_POST['juros']) ? str_replace(',', '.', trim($_POST['juros'])) : '0';
            $tarifa_bancaria = isset($_POST['tarifa_bancaria']) ? str_replace(',', '.', trim($_POST['tarifa_bancaria'])) : '0';
            $desconto = isset($_POST['desconto']) ? str_replace(',', '.', trim($_POST['desconto'])) : '0';
            $forma_pagamento = isset($_POST['forma_pagamento']) ? trim($_POST['forma_pagamento']) : '';
            $local_pagamento = isset($_POST['local_pagamento']) ? trim($_POST['local_pagamento']) : '';
            $observacao = isset($_POST['observacao']) ? trim($_POST['observacao']) : '';
            $usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : (isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Sistema');

            if ($cobranca_id <= 0) {
                jsonResponseBaixa(false, 'ID da cobrança inválido.');
            }
            if (!in_array($tipo_operacao, ['baixar', 'estornar'], true)) {
                jsonResponseBaixa(false, 'Tipo de operação inválido. Deve ser "baixar" ou "estornar".');
            }

            $stmt = $pdo->prepare("SELECT id, pago, observacao FROM cobranca WHERE id = :id");
            $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
            $stmt->execute();
            $cobranca = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cobranca) {
                jsonResponseBaixa(false, 'Cobrança não encontrada.');
            }

            $observacaoAtual = ($cobranca['observacao'] ?? '');
            if ($observacaoAtual !== '') $observacaoAtual .= "\n";

            if ($tipo_operacao === 'estornar') {
                $observacaoAtual .= date('d/m/Y H:i') . ' - ESTORNO MANUAL' . ($observacao ? (': ' . $observacao) : '');
                $sql = "
                    UPDATE cobranca SET
                        pago = 'N',
                        datapagamento = NULL,
                        databaixa = NULL,
                        multas = 0,
                        juros = 0,
                        tarifa_bancaria = 0,
                        desconto = 0,
                        forma_pagamento = NULL,
                        local_pagamento = NULL,
                        usuario = :usuario,
                        observacao = :observacao
                    WHERE id = :id
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':usuario', $usuario);
                $stmt->bindParam(':observacao', $observacaoAtual);
                $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
                $stmt->execute();

                registrarLog('INFO', 'Baixa manual estornada (salvar-baixa-completa)', ['cobranca_id' => $cobranca_id, 'usuario' => $usuario]);
                jsonResponseBaixa(true, 'Parcela estornada com sucesso!');
            }

            // baixar
            if (!$data_pagamento || !$data_baixa) {
                jsonResponseBaixa(false, 'Data de pagamento e data de baixa são obrigatórias para baixar a parcela.');
            }
            if (($cobranca['pago'] ?? 'N') === 'S') {
                jsonResponseBaixa(false, 'Atenção: Este título já foi baixado anteriormente e não pode ser processado de novo.', ['codigo' => 'TITULO_JA_BAIXADO'], 409);
            }

            $multaF = (float)$multa;
            $jurosF = (float)$juros;
            $tarifaF = (float)$tarifa_bancaria;
            $descontoF = (float)$desconto;

            $observacaoAtual .= date('d/m/Y H:i') . ' - BAIXA MANUAL';
            if ($observacao) $observacaoAtual .= ': ' . $observacao;
            if ($forma_pagamento) $observacaoAtual .= ' | Forma: ' . $forma_pagamento;
            if ($local_pagamento) $observacaoAtual .= ' | Local: ' . $local_pagamento;

            $sql = "
                UPDATE cobranca SET
                    pago = 'S',
                    datapagamento = :datapagamento,
                    databaixa = :databaixa,
                    multas = :multas,
                    juros = :juros,
                    tarifa_bancaria = :tarifa_bancaria,
                    desconto = :desconto,
                    forma_pagamento = :forma_pagamento,
                    local_pagamento = :local_pagamento,
                    usuario = :usuario,
                    observacao = :observacao
                WHERE id = :id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':datapagamento', $data_pagamento);
            $stmt->bindParam(':databaixa', $data_baixa);
            $stmt->bindValue(':multas', number_format($multaF, 2, '.', ''), PDO::PARAM_STR);
            $stmt->bindValue(':juros', number_format($jurosF, 2, '.', ''), PDO::PARAM_STR);
            $stmt->bindValue(':tarifa_bancaria', number_format($tarifaF, 2, '.', ''), PDO::PARAM_STR);
            $stmt->bindValue(':desconto', number_format($descontoF, 2, '.', ''), PDO::PARAM_STR);
            $stmt->bindParam(':forma_pagamento', $forma_pagamento);
            $stmt->bindParam(':local_pagamento', $local_pagamento);
            $stmt->bindParam(':usuario', $usuario);
            $stmt->bindParam(':observacao', $observacaoAtual);
            $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
            $stmt->execute();

            registrarLog('INFO', 'Baixa manual realizada (salvar-baixa-completa)', ['cobranca_id' => $cobranca_id, 'usuario' => $usuario]);
            jsonResponseBaixa(true, 'Parcela baixada com sucesso!');
            break;

        case 'baixar-estornar':
            $cobranca_id = isset($_POST['cobranca_id']) ? (int)$_POST['cobranca_id'] : 0;
            $tipo_operacao = isset($_POST['tipo_operacao']) ? trim($_POST['tipo_operacao']) : '';
            $data_pagamento = isset($_POST['data_pagamento']) ? trim($_POST['data_pagamento']) : '';
            $data_baixa = isset($_POST['data_baixa']) ? trim($_POST['data_baixa']) : '';
            $observacao = isset($_POST['observacao']) ? trim($_POST['observacao']) : '';
            $usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : (isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Sistema');

            if ($cobranca_id <= 0) jsonResponseBaixa(false, 'ID da cobrança inválido.');
            if (!in_array($tipo_operacao, ['baixar', 'estornar'], true)) jsonResponseBaixa(false, 'Tipo de operação inválido.');

            $stmt = $pdo->prepare("SELECT id, pago, observacao FROM cobranca WHERE id = :id");
            $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
            $stmt->execute();
            $cobranca = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$cobranca) jsonResponseBaixa(false, 'Cobrança não encontrada.');

            if ($tipo_operacao === 'baixar' && ($cobranca['pago'] ?? 'N') === 'S') {
                jsonResponseBaixa(false, 'Esta cobrança já está paga. Use a opção "Estornar" para reverter.');
            }
            if ($tipo_operacao === 'estornar' && ($cobranca['pago'] ?? 'N') !== 'S') {
                jsonResponseBaixa(false, 'Esta cobrança não está paga. Não é possível estornar.');
            }

            $pago = $tipo_operacao === 'baixar' ? 'S' : 'N';
            if ($tipo_operacao === 'estornar') {
                $data_pagamento = null;
                $data_baixa = null;
            } else {
                if (!$data_pagamento || !$data_baixa) {
                    jsonResponseBaixa(false, 'Data de pagamento e data de baixa são obrigatórias.');
                }
            }

            $sql = "
                UPDATE cobranca SET
                    pago = :pago,
                    datapagamento = :datapagamento,
                    databaixa = :databaixa,
                    usuario = :usuario
            ";

            if ($observacao) {
                $observacaoAtual = ($cobranca['observacao'] ?? '');
                if ($observacaoAtual !== '') $observacaoAtual .= "\n";
                $observacaoAtual .= date('d/m/Y H:i') . ' - ' . ($tipo_operacao === 'baixar' ? 'BAIXA' : 'ESTORNO') . ': ' . $observacao;
                $sql .= ", observacao = :observacao";
            }
            $sql .= " WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':pago', $pago);
            $stmt->bindParam(':datapagamento', $data_pagamento);
            $stmt->bindParam(':databaixa', $data_baixa);
            $stmt->bindParam(':usuario', $usuario);
            if ($observacao) $stmt->bindParam(':observacao', $observacaoAtual);
            $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
            $stmt->execute();

            jsonResponseBaixa(true, $tipo_operacao === 'baixar' ? 'Cobrança baixada (liquidada) com sucesso!' : 'Cobrança estornada com sucesso!');
            break;

        default:
            jsonResponseBaixa(false, 'Ação inválida.', [], 400);
    }
} catch (PDOException $e) {
    logError('Erro no processamento de baixa manual', [
        'action' => $action,
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ], $e);
    // orientar quando faltar colunas/migrações
    if (stripos($e->getMessage(), 'column') !== false || stripos($e->getMessage(), 'coluna') !== false) {
        jsonResponseBaixa(false, 'Erro ao processar a baixa manual: faltam colunas no banco. Execute os scripts SQL em `config/` (cobrança/baixa manual).', [], 500);
    }
    jsonResponseBaixa(false, 'Erro ao processar a requisição de baixa manual. Detalhes: ' . $e->getMessage(), [], 500);
} catch (Throwable $e) {
    logError('Erro geral no processamento de baixa manual', [
        'action' => $action,
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
    ], $e);
    jsonResponseBaixa(false, 'Erro ao processar a requisição de baixa manual. Detalhes: ' . $e->getMessage(), [], 500);
}

