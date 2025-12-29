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

function jsonResponse($sucesso, $mensagem, $extra = []) {
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

try {
    $pdo = getConnection();
    
    // Coletar parâmetros
    $empreendimento_id = isset($_REQUEST['empreendimento_id']) && $_REQUEST['empreendimento_id'] !== '' ? (int)$_REQUEST['empreendimento_id'] : null;
    $modulo_id = isset($_REQUEST['modulo_id']) && $_REQUEST['modulo_id'] !== '' ? (int)$_REQUEST['modulo_id'] : null;
    $contrato = isset($_REQUEST['contrato']) ? trim($_REQUEST['contrato']) : null;
    $cliente = isset($_REQUEST['cliente']) ? trim($_REQUEST['cliente']) : null;
    $data_calculo = isset($_REQUEST['data_calculo']) ? trim($_REQUEST['data_calculo']) : null;
    $filtro_titulo = isset($_REQUEST['filtro_titulo']) ? trim($_REQUEST['filtro_titulo']) : 'todos';
    $ordem = isset($_REQUEST['ordem']) ? trim($_REQUEST['ordem']) : 'vencimento';
    
    if (!$empreendimento_id || !$modulo_id || !$contrato) {
        jsonResponse(false, 'Empreendimento, Módulo e Contrato são obrigatórios.');
    }
    
    // Verificar se a coluna ano_referencia existe
    $colunaAnoRefExiste = false;
    try {
        $stmtCheck = $pdo->query("
            SELECT COUNT(*) as existe
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = 'cobranca'
              AND column_name = 'ano_referencia'
        ");
        $check = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $colunaAnoRefExiste = ($check && $check['existe'] > 0);
    } catch (Exception $e) {
        $colunaAnoRefExiste = false;
    }
    
    // Verificar se a coluna titulo existe
    $colunaTituloExiste = false;
    try {
        $stmtCheck = $pdo->query("
            SELECT COUNT(*) as existe
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = 'cobranca'
              AND column_name = 'titulo'
        ");
        $check = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $colunaTituloExiste = ($check && $check['existe'] > 0);
    } catch (Exception $e) {
        $colunaTituloExiste = false;
    }
    
    // Buscar cobranças
    $sql = "
        SELECT 
            c.id,
            c.empreendimento_id,
            e.nome AS empreendimento_nome,
            e.banco_id,
            c.modulo_id,
            m.nome AS modulo_nome,
            c.contrato,
            c.cliente_nome,
            c.parcelamento,
            c.valor_mensal,
            c.datavencimento,
            c.situacao,
            c.pago,
            c.observacao,
            c.data_criacao,
            c.juros,
            c.multas,
            b.multa_mes,
            b.juros_mes";
    
    if ($colunaAnoRefExiste) {
        $sql .= ", c.ano_referencia";
    }
    
    if ($colunaTituloExiste) {
        $sql .= ", c.titulo";
    }
    
    $sql .= "
        FROM cobranca c
        LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
        LEFT JOIN modulos m ON m.id = c.modulo_id
        LEFT JOIN bancos b ON b.id = e.banco_id
        WHERE c.empreendimento_id = :empreendimento_id
          AND c.modulo_id = :modulo_id
          AND c.contrato = :contrato";
    
    // Aplicar filtro de título
    $hoje = date('Y-m-d');
    if ($filtro_titulo === 'pagos') {
        $sql .= " AND (c.pago = 'S' OR c.pago = 's')";
    } elseif ($filtro_titulo === 'vencidos') {
        $sql .= " AND (c.pago IS NULL OR c.pago = '' OR c.pago = 'N' OR c.pago = 'n')";
        $sql .= " AND c.datavencimento < :hoje";
    } elseif ($filtro_titulo === 'a-vencer') {
        $sql .= " AND (c.pago IS NULL OR c.pago = '' OR c.pago = 'N' OR c.pago = 'n')";
        $sql .= " AND c.datavencimento >= :hoje";
    }
    
    // Aplicar ordenação
    switch($ordem) {
        case 'parcela':
            $sql .= " ORDER BY c.parcelamento ASC, c.datavencimento ASC";
            break;
        case 'pagamento':
            $sql .= " ORDER BY c.pago DESC, c.datavencimento ASC";
            break;
        case 'titulo':
            if ($colunaTituloExiste) {
                $sql .= " ORDER BY c.titulo ASC, c.parcelamento ASC";
            } else {
                $sql .= " ORDER BY c.id ASC, c.parcelamento ASC";
            }
            break;
        case 'vencimento':
        default:
            $sql .= " ORDER BY c.datavencimento ASC, c.parcelamento ASC";
            break;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
    $stmt->bindParam(':modulo_id', $modulo_id, PDO::PARAM_INT);
    $stmt->bindParam(':contrato', $contrato, PDO::PARAM_STR);
    
    if ($filtro_titulo === 'vencidos' || $filtro_titulo === 'a-vencer') {
        $stmt->bindParam(':hoje', $hoje, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $cobrancas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar dados do empreendimento
    $stmtEmp = $pdo->prepare("SELECT nome FROM empreendimentos WHERE id = :id");
    $stmtEmp->bindParam(':id', $empreendimento_id, PDO::PARAM_INT);
    $stmtEmp->execute();
    $empreendimento = $stmtEmp->fetch(PDO::FETCH_ASSOC);
    $empreendimentoNome = $empreendimento ? $empreendimento['nome'] : '';
    
    if ($action) {
        jsonResponse(false, 'Ação não reconhecida.');
    }
    
} catch (PDOException $e) {
    logError('Erro ao gerar extrato', [
        'action' => $action,
        'error' => $e->getMessage()
    ], $e);
    jsonResponse(false, 'Erro ao gerar extrato: ' . $e->getMessage());
} catch (Exception $e) {
    logError('Erro geral ao gerar extrato', [
        'action' => $action,
        'error' => $e->getMessage()
    ], $e);
    jsonResponse(false, 'Erro ao gerar extrato: ' . $e->getMessage());
}

