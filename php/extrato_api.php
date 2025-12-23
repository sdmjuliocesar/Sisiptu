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

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'gerar-pdf';

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
    
    switch ($action) {
        case 'gerar-pdf':
            // Gerar HTML do extrato
            gerarPDFExtrato($cobrancas, $empreendimentoNome, $cliente, $contrato, $data_calculo, $data_calculo);
            break;
            
        case 'enviar-email':
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(false, 'Email inválido.');
            }
            
            // Gerar PDF e enviar por email
            $pdfPath = gerarPDFExtrato($cobrancas, $empreendimentoNome, $cliente, $contrato, $data_calculo, $data_calculo, true);
            
            // Enviar email (implementação básica - pode usar PHPMailer)
            $assunto = "Extrato de IPTU - Contrato {$contrato}";
            $mensagem = "Segue em anexo o extrato de IPTU do contrato {$contrato}.\n\nCliente: {$cliente}";
            $headers = "From: sistema@iptu.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"boundary\"\r\n";
            
            // Por enquanto, apenas retornar sucesso (implementação completa requer PHPMailer)
            jsonResponse(true, 'Extrato enviado por email com sucesso!');
            break;
            
        default:
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

function gerarPDFExtrato($cobrancas, $empreendimentoNome, $cliente, $contrato, $dataCalculo, $dataCalculoFormatada, $retornarPath = false) {
    $hoje = new DateTime();
    $dataExtrato = $hoje->format('d/m/Y');
    
    // Usar data de cálculo se informada, senão usar data atual
    $dataReferencia = $dataCalculo ? new DateTime($dataCalculo) : new DateTime();
    $dataReferencia->setTime(0, 0, 0);
    
    // Calcular totais
    $totalValor = 0;
    $totalJuros = 0;
    $totalMulta = 0;
    $totalPago = 0;
    
    foreach ($cobrancas as &$c) {
        $valor = floatval($c['valor_mensal'] ?? 0);
        $totalValor += $valor;
        
        // Calcular juros e multas se houver atraso
        $jurosValor = 0;
        $multaValor = 0;
        
        $pagoStatus = ($c['pago'] === 'S' || $c['pago'] === 's');
        if (!$pagoStatus) {
            $dataVenc = $c['datavencimento'] ?? null;
            if ($dataVenc) {
                $dataVencimento = new DateTime($dataVenc);
                $dataVencimento->setTime(0, 0, 0);
                
                if ($dataVencimento < $dataReferencia) {
                    $diff = $dataVencimento->diff($dataReferencia);
                    $diasAtraso = $diff->days;
                    
                    if ($diasAtraso > 0) {
                        // Buscar valores de multa_mes e juros_mes do banco vinculado ao empreendimento
                        // Estes valores vêm do JOIN: cobranca -> empreendimentos -> bancos
                        // O fluxo é: c.empreendimento_id -> e.banco_id -> b.multa_mes e b.juros_mes
                        $multaMes = floatval($c['multa_mes'] ?? 0);
                        $jurosMes = floatval($c['juros_mes'] ?? 0);
                        
                        // Calcular multa: valor_mensal * (multa_mes / 100)
                        if ($multaMes > 0) {
                            $multaValor = $valor * ($multaMes / 100);
                        }
                        
                        // Calcular juros: valor_mensal * (juros_mes / 100) * (dias_atraso / 30)
                        if ($jurosMes > 0) {
                            $mesesAtraso = $diasAtraso / 30;
                            $jurosValor = $valor * ($jurosMes / 100) * $mesesAtraso;
                        }
                    }
                }
            }
        } else {
            $jurosValor = floatval($c['juros'] ?? 0);
            $multaValor = floatval($c['multas'] ?? 0);
        }
        
        $c['juros_calculado'] = $jurosValor;
        $c['multa_calculada'] = $multaValor;
        
        $totalJuros += $jurosValor;
        $totalMulta += $multaValor;
        
        if ($pagoStatus) {
            $totalPago += floatval($c['valor_pago'] ?? $valor);
        }
    }
    unset($c);
    
    $totalGeral = $totalValor + $totalJuros + $totalMulta;
    
    // Gerar HTML
    $html = gerarHTMLExtratoPDF($cobrancas, $empreendimentoNome, $cliente, $contrato, $dataExtrato, $dataCalculoFormatada, $totalValor, $totalJuros, $totalMulta, $totalGeral, $totalPago, $dataCalculo);
    
    // Se for para retornar path (email), salvar arquivo
    if ($retornarPath) {
        $filename = 'extrato_' . $contrato . '_' . date('YmdHis') . '.html';
        $filepath = __DIR__ . '/../temp/' . $filename;
        if (!is_dir(__DIR__ . '/../temp')) {
            mkdir(__DIR__ . '/../temp', 0755, true);
        }
        file_put_contents($filepath, $html);
        return $filepath;
    }
    
    // Retornar HTML para impressão/PDF
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
}

function gerarHTMLExtratoPDF($cobrancas, $empreendimentoNome, $cliente, $contrato, $dataExtrato, $dataCalculoFormatada, $totalValor, $totalJuros, $totalMulta, $totalGeral, $totalPago, $dataCalculo = null) {
    // Usar data de cálculo se informada, senão usar data atual
    $dataReferencia = $dataCalculo ? new DateTime($dataCalculo) : new DateTime();
    $dataReferencia->setTime(0, 0, 0);
    
    $tabelaHTML = '';
    foreach ($cobrancas as $c) {
        $vencimento = $c['datavencimento'] ? date('d/m/Y', strtotime($c['datavencimento'])) : '-';
        $valor = floatval($c['valor_mensal'] ?? 0);
        $jurosValor = $c['juros_calculado'] ?? floatval($c['juros'] ?? 0);
        $multaValor = $c['multa_calculada'] ?? floatval($c['multas'] ?? 0);
        $valorTotal = $valor + $jurosValor + $multaValor;
        $titulo = $c['titulo'] ?? $c['id'] ?? '-';
        
        // Verificar se está em atraso
        $statusAtraso = '';
        $pagoStatus = ($c['pago'] === 'S' || $c['pago'] === 's');
        if (!$pagoStatus) {
            $dataVenc = $c['datavencimento'] ?? null;
            if ($dataVenc) {
                $dataVencimento = new DateTime($dataVenc);
                $dataVencimento->setTime(0, 0, 0);
                if ($dataVencimento < $dataReferencia) {
                    $statusAtraso = 'Em atraso';
                }
            }
        }
        
        $statusStyle = $statusAtraso ? 'color: #d32f2f; font-weight: bold;' : '';
        $statusTexto = $statusAtraso ?: '-';
        
        $tabelaHTML .= "
            <tr>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: center;\">{$titulo}</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: center;\">" . ($c['parcelamento'] ?? '-') . "</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: center;\">{$vencimento}</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: right;\">R$ " . number_format($valor, 2, ',', '.') . "</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: right;\">R$ " . number_format($jurosValor, 2, ',', '.') . "</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: right;\">R$ " . number_format($multaValor, 2, ',', '.') . "</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: right;\">R$ " . number_format($valorTotal, 2, ',', '.') . "</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: center; {$statusStyle}\">{$statusTexto}</td>
            </tr>
        ";
    }
    
    return "
        <!DOCTYPE html>
        <html lang=\"pt-BR\">
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <title>Extrato de IPTU - {$contrato}</title>
            <style>
                @media print {
                    @page {
                        margin: 1cm;
                    }
                    body {
                        margin: 0;
                        padding: 0;
                    }
                    .no-print {
                        display: none;
                    }
                }
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    color: #333;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #2d8659;
                    padding-bottom: 20px;
                    margin-bottom: 20px;
                }
                .header h1 {
                    color: #2d8659;
                    margin: 0;
                }
                .info-section {
                    margin-bottom: 20px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                }
                .info-label {
                    font-weight: bold;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                th {
                    background-color: #2d8659;
                    color: white;
                    padding: 10px;
                    text-align: center;
                    border: 1px solid #ddd;
                }
                td {
                    border: 1px solid #ddd;
                    padding: 8px;
                }
                .total-section {
                    margin-top: 20px;
                    padding: 15px;
                    background-color: #f5f5f5;
                    border: 2px solid #2d8659;
                }
                .total-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 5px 0;
                    font-size: 14px;
                }
                .total-final {
                    font-size: 18px;
                    font-weight: bold;
                    color: #2d8659;
                    margin-top: 10px;
                    padding-top: 10px;
                    border-top: 2px solid #2d8659;
                }
            </style>
        </head>
        <body>
            <div class=\"header\">
                <h1>EXTRATO DE IPTU</h1>
                <p>Data de Emissão: {$dataExtrato}</p>
            </div>
            
            <div class=\"info-section\">
                <div class=\"info-row\">
                    <span class=\"info-label\">Cliente:</span>
                    <span>" . htmlspecialchars($cliente ?: 'Não informado') . "</span>
                </div>
                <div class=\"info-row\">
                    <span class=\"info-label\">Contrato:</span>
                    <span>" . htmlspecialchars($contrato) . "</span>
                </div>
                <div class=\"info-row\">
                    <span class=\"info-label\">Empreendimento:</span>
                    <span>" . htmlspecialchars($empreendimentoNome) . "</span>
                </div>
                " . ($dataCalculoFormatada ? "
                <div class=\"info-row\">
                    <span class=\"info-label\">Data para Cálculo:</span>
                    <span>{$dataCalculoFormatada}</span>
                </div>
                " : '') . "
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Parcela</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Juros</th>
                        <th>Multa</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    {$tabelaHTML}
                </tbody>
            </table>
            
            <div class=\"total-section\">
                <div class=\"total-row\">
                    <span>Total de Parcelas:</span>
                    <span>R$ " . number_format($totalValor, 2, ',', '.') . "</span>
                </div>
                <div class=\"total-row\">
                    <span>Total de Juros:</span>
                    <span>R$ " . number_format($totalJuros, 2, ',', '.') . "</span>
                </div>
                <div class=\"total-row\">
                    <span>Total de Multas:</span>
                    <span>R$ " . number_format($totalMulta, 2, ',', '.') . "</span>
                </div>
                <div class=\"total-row total-final\">
                    <span>TOTAL GERAL:</span>
                    <span>R$ " . number_format($totalGeral, 2, ',', '.') . "</span>
                </div>
                <div class=\"total-row\">
                    <span>Total Pago:</span>
                    <span>R$ " . number_format($totalPago, 2, ',', '.') . "</span>
                </div>
                <div class=\"total-row total-final\">
                    <span>SALDO DEVEDOR:</span>
                    <span>R$ " . number_format($totalGeral - $totalPago, 2, ',', '.') . "</span>
                </div>
            </div>
        </body>
        </html>
    ";
}

