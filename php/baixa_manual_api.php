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

        case 'pesquisar-contrato-completo':
            $empreendimento_id = isset($_GET['empreendimento_id']) ? (int)$_GET['empreendimento_id'] : 0;
            $modulo_id = isset($_GET['modulo_id']) ? (int)$_GET['modulo_id'] : 0;
            $contrato = isset($_GET['contrato']) ? trim($_GET['contrato']) : '';

            if ($empreendimento_id <= 0 || $modulo_id <= 0 || $contrato === '') {
                jsonResponseBaixa(false, 'Empreendimento, Módulo e Contrato são obrigatórios.');
            }

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
                ORDER BY c.datavencimento ASC, c.id ASC
            ";

            $params = [
                ':empreendimento_id' => $empreendimento_id,
                ':modulo_id' => $modulo_id,
                ':contrato' => $contrato
            ];

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $cobrancas = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

            $sql = "
                SELECT COUNT(*) as total
                FROM cobranca
                WHERE empreendimento_id = :empreendimento_id
                  AND modulo_id = :modulo_id
                  AND contrato = :contrato
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
            $stmt->bindParam(':modulo_id', $modulo_id, PDO::PARAM_INT);
            $stmt->bindParam(':contrato', $contrato);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado['total'] > 0) {
                jsonResponseBaixa(true, 'Contrato válido.');
            } else {
                jsonResponseBaixa(false, 'Contrato não encontrado para este Empreendimento e Módulo.');
            }
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
                    c.titulo,
                    c.empreendimento_id,
                    c.modulo_id,
                    c.contrato,
                    c.datavencimento,
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
                    c.observacao
                FROM cobranca c
                WHERE c.empreendimento_id = :empreendimento_id
                  AND c.modulo_id = :modulo_id
                  AND c.contrato = :contrato
                  AND c.titulo = :titulo
                LIMIT 1
            ";

            $params = [
                ':empreendimento_id' => $empreendimento_id,
                ':modulo_id' => $modulo_id,
                ':contrato' => $contrato,
                ':titulo' => $titulo
            ];

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

            // Buscar dados da cobrança
            $stmt = $pdo->prepare("
                SELECT id, valor_mensal, datavencimento, multas, juros
                FROM cobranca
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
            $stmt->execute();
            $cobranca = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cobranca) {
                jsonResponseBaixa(false, 'Cobrança não encontrada.');
            }

            $valorMensal = (float)($cobranca['valor_mensal'] ?? 0);
            $dataVencimento = $cobranca['datavencimento'];
            $dataPagamento = new DateTime($data_pagamento);
            $dataVenc = new DateTime($dataVencimento);

            // Calcular dias de atraso
            $diasAtraso = $dataVenc->diff($dataPagamento)->days;
            if ($dataVenc < $dataPagamento) {
                // Calcular multa (2% do valor)
                $multa = $valorMensal * 0.02;
                
                // Calcular juros (0,033% ao dia)
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

            if (!in_array($tipo_operacao, ['baixar', 'estornar'])) {
                jsonResponseBaixa(false, 'Tipo de operação inválido. Deve ser "baixar" ou "estornar".');
            }

            // Verificar se a cobrança existe
            $stmt = $pdo->prepare("SELECT id, pago FROM cobranca WHERE id = :id");
            $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
            $stmt->execute();
            $cobranca = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cobranca) {
                jsonResponseBaixa(false, 'Cobrança não encontrada.');
            }

            // Preparar observação
            $observacaoAtual = '';
            $stmt = $pdo->prepare("SELECT observacao FROM cobranca WHERE id = :id");
            $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
            $stmt->execute();
            $cobrancaAtual = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($cobrancaAtual && $cobrancaAtual['observacao']) {
                $observacaoAtual = $cobrancaAtual['observacao'] . "\n";
            }
            
            if ($tipo_operacao === 'estornar') {
                // ESTORNO: Limpar todos os campos
                $observacaoAtual .= date('d/m/Y H:i') . ' - ESTORNO MANUAL';
                if ($observacao) {
                    $observacaoAtual .= ': ' . $observacao;
                }

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

                jsonResponseBaixa(true, 'Parcela estornada com sucesso!');
            } else {
                // BAIXA: Validar datas e atualizar com valores fornecidos
                if (!$data_pagamento || !$data_baixa) {
                    jsonResponseBaixa(false, 'Data de pagamento e data de baixa são obrigatórias para baixar a parcela.');
                }

                // Converter valores para float
                $multa = (float)$multa;
                $juros = (float)$juros;
                $tarifa_bancaria = (float)$tarifa_bancaria;
                $desconto = (float)$desconto;

                $observacaoAtual .= date('d/m/Y H:i') . ' - BAIXA MANUAL';
                if ($observacao) {
                    $observacaoAtual .= ': ' . $observacao;
                }
                if ($forma_pagamento) {
                    $observacaoAtual .= ' | Forma: ' . $forma_pagamento;
                }
                if ($local_pagamento) {
                    $observacaoAtual .= ' | Local: ' . $local_pagamento;
                }

                // Atualizar cobrança com todos os campos
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
                $stmt->bindParam(':multas', $multa);
                $stmt->bindParam(':juros', $juros);
                $stmt->bindParam(':tarifa_bancaria', $tarifa_bancaria);
                $stmt->bindParam(':desconto', $desconto);
                $stmt->bindParam(':forma_pagamento', $forma_pagamento);
                $stmt->bindParam(':local_pagamento', $local_pagamento);
                $stmt->bindParam(':usuario', $usuario);
                $stmt->bindParam(':observacao', $observacaoAtual);
                $stmt->bindParam(':id', $cobranca_id, PDO::PARAM_INT);
                $stmt->execute();

                jsonResponseBaixa(true, 'Parcela baixada com sucesso!');
            }
            break;

        case 'baixar-estornar':
            $cobranca_id = isset($_POST['cobranca_id']) ? (int)$_POST['cobranca_id'] : 0;
            $tipo_operacao = isset($_POST['tipo_operacao']) ? trim($_POST['tipo_operacao']) : '';
            $data_pagamento = isset($_POST['data_pagamento']) ? trim($_POST['data_pagamento']) : '';
            $data_baixa = isset($_POST['data_baixa']) ? trim($_POST['data_baixa']) : '';
            $observacao = isset($_POST['observacao']) ? trim($_POST['observacao']) : '';
            $usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : (isset($_SESSION['usuario_nome']) ? $_SESSION['usuario_nome'] : 'Sistema');

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

