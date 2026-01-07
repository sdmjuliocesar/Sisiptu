<?php
session_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../config/logger.php';

// Carregar mPDF se disponível
$mpdfDisponivel = false;
if (file_exists(__DIR__ . '/../Vendor/autoload.php')) {
    require_once __DIR__ . '/../Vendor/autoload.php';
    if (class_exists('\\Mpdf\\Mpdf')) {
        $mpdfDisponivel = true;
    }
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Acesso não autorizado. Faça login novamente.'
    ]);
    exit;
}

function jsonResponse($sucesso, $mensagem, $extra = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

function calcularJurosMultas(&$cobrancas, $dataCalculo) {
    if (empty($cobrancas)) return;
    
    $dataCalculoObj = new DateTime($dataCalculo);
    $dataCalculoObj->setTime(0, 0, 0);
    
    foreach ($cobrancas as &$c) {
        // Só calcular se não estiver pago
        if (isset($c['pago']) && ($c['pago'] === 'S' || $c['pago'] === 's')) {
            $c['juros_calculado'] = 0;
            $c['multa_calculada'] = 0;
            continue;
        }
        
        $dataVenc = $c['datavencimento'] ?? null;
        if (!$dataVenc) {
            $c['juros_calculado'] = 0;
            $c['multa_calculada'] = 0;
            continue;
        }
        
        $dataVencimento = new DateTime($dataVenc);
        $dataVencimento->setTime(0, 0, 0);
        
        // Só calcular se houver atraso
        if ($dataVencimento >= $dataCalculoObj) {
            $c['juros_calculado'] = 0;
            $c['multa_calculada'] = 0;
            continue;
        }
        
        // Calcular dias de atraso
        $diff = $dataCalculoObj->diff($dataVencimento);
        $diffDays = (int)$diff->format('%a');
        
        if ($diffDays <= 0) {
            $c['juros_calculado'] = 0;
            $c['multa_calculada'] = 0;
            continue;
        }
        
        $valorMensal = (float)($c['valor_mensal'] ?? 0);
        $multaMes = (float)($c['multa_mes'] ?? 0);
        $jurosMes = (float)($c['juros_mes'] ?? 0);
        
        // Calcular multa (percentual sobre o valor)
        $multa = 0;
        if ($multaMes > 0 && $valorMensal > 0) {
            $multa = $valorMensal * ($multaMes / 100);
        }
        
        // Calcular juros (percentual mensal proporcional aos dias)
        $juros = 0;
        if ($jurosMes > 0 && $valorMensal > 0) {
            $mesesAtraso = $diffDays / 30;
            $juros = $valorMensal * ($jurosMes / 100) * $mesesAtraso;
        }
        
        $c['juros_calculado'] = $juros;
        $c['multa_calculada'] = $multa;
    }
}

function gerarPDFDoHTML($html, $contrato) {
    global $mpdfDisponivel;
    
    if (!$mpdfDisponivel) {
        return null;
    }
    
    try {
        // Configurar diretório temporário do mPDF
        $tempDir = __DIR__ . '/../Vendor/mpdf/mpdf/tmp';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9,
            'tempDir' => $tempDir
        ]);
        
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S'); // Retorna como string
    } catch (Exception $e) {
        logError('Erro ao gerar PDF', [
            'contrato' => $contrato,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}

function gerarHTMLExtrato($cobrancas, $empreendimentoNome, $cliente, $contrato, $dataCalculo) {
    $hoje = new DateTime();
    $dataExtrato = $hoje->format('d/m/Y');
    $dataCalculoFormatada = $dataCalculo ? date('d/m/Y', strtotime($dataCalculo)) : '';
    
    // Calcular totais
    $totalValor = 0;
    $totalJuros = 0;
    $totalMulta = 0;
    $totalPago = 0;
    
    foreach ($cobrancas as $c) {
        $totalValor += (float)($c['valor_mensal'] ?? 0);
        $jurosValor = $c['juros_calculado'] ?? (float)($c['juros'] ?? 0);
        $multaValor = $c['multa_calculada'] ?? (float)($c['multas'] ?? 0);
        $totalJuros += $jurosValor;
        $totalMulta += $multaValor;
        if (isset($c['pago']) && ($c['pago'] === 'S' || $c['pago'] === 's')) {
            $totalPago += (float)($c['valor_pago'] ?? $c['valor_mensal'] ?? 0);
        }
    }
    
    $totalGeral = $totalValor + $totalJuros + $totalMulta;
    
    // Gerar tabela
    $tabelaHTML = '';
    $dataReferencia = $dataCalculo ? new DateTime($dataCalculo) : new DateTime();
    $dataReferencia->setTime(0, 0, 0);
    
    foreach ($cobrancas as $c) {
        $vencimento = $c['datavencimento'] ?? $c['data_vencimento'] ?? '';
        $vencimentoFormatado = $vencimento ? date('d/m/Y', strtotime($vencimento)) : '-';
        $valor = (float)($c['valor_mensal'] ?? 0);
        $jurosValor = $c['juros_calculado'] ?? (float)($c['juros'] ?? 0);
        $multaValor = $c['multa_calculada'] ?? (float)($c['multas'] ?? 0);
        $valorTotal = $valor + $jurosValor + $multaValor;
        
        // Verificar se está em atraso
        $statusAtraso = '';
        $pagoStatus = isset($c['pago']) && ($c['pago'] === 'S' || $c['pago'] === 's');
        if (!$pagoStatus && $vencimento) {
            $dataVencimento = new DateTime($vencimento);
            $dataVencimento->setTime(0, 0, 0);
            if ($dataVencimento < $dataReferencia) {
                $statusAtraso = 'Em atraso';
            }
        }
        
        $titulo = $c['titulo'] ?? $c['id'] ?? '-';
        $parcela = $c['parcelamento'] ?? '-';
        
        $tabelaHTML .= "
            <tr>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: center;\">{$titulo}</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: center;\">{$parcela}</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: center;\">{$vencimentoFormatado}</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: right;\">R$ " . number_format($valor, 2, ',', '.') . "</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: right;\">R$ " . number_format($jurosValor, 2, ',', '.') . "</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: right;\">R$ " . number_format($multaValor, 2, ',', '.') . "</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: right;\">R$ " . number_format($valorTotal, 2, ',', '.') . "</td>
                <td style=\"border: 1px solid #ddd; padding: 8px; text-align: center; " . ($statusAtraso ? "color: #d32f2f; font-weight: bold;" : "") . "\">{$statusAtraso}</td>
            </tr>
        ";
    }
    
    $clienteDisplay = $cliente ?: 'Não informado';
    $dataCalculoHTML = $dataCalculoFormatada ? "
                <div class=\"info-row\">
                    <span class=\"info-label\">Data para Cálculo:</span>
                    <span>{$dataCalculoFormatada}</span>
                </div>
    " : '';
    
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
                    <span>{$clienteDisplay}</span>
                </div>
                <div class=\"info-row\">
                    <span class=\"info-label\">Contrato:</span>
                    <span>{$contrato}</span>
                </div>
                <div class=\"info-row\">
                    <span class=\"info-label\">Empreendimento:</span>
                    <span>{$empreendimentoNome}</span>
                </div>
                {$dataCalculoHTML}
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
    
    // Calcular juros e multas se houver data de cálculo
    if ($data_calculo) {
        calcularJurosMultas($cobrancas, $data_calculo);
    }
    
    // Processar ações
    switch ($action) {
        case 'gerar-pdf':
            // Gerar HTML do extrato para impressão
            $htmlExtrato = gerarHTMLExtrato($cobrancas, $empreendimentoNome, $cliente, $contrato, $data_calculo);
            
            // Retornar HTML para impressão
            header('Content-Type: text/html; charset=utf-8');
            echo $htmlExtrato;
            exit;
            
        case 'enviar-email':
            $emailDestino = isset($_POST['email']) ? trim($_POST['email']) : '';
            
            if (empty($emailDestino) || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(false, 'Email inválido.');
            }
            
            // Gerar HTML do extrato
            $htmlExtrato = gerarHTMLExtrato($cobrancas, $empreendimentoNome, $cliente, $contrato, $data_calculo);
            
            // Converter HTML para PDF
            $pdfContent = gerarPDFDoHTML($htmlExtrato, $contrato);
            
            if (!$pdfContent) {
                // Se não conseguiu gerar PDF, tentar enviar HTML como fallback
                logError('Erro ao gerar PDF, enviando HTML como fallback', [
                    'contrato' => $contrato,
                    'email' => $emailDestino
                ]);
                jsonResponse(false, 'Erro ao gerar PDF. Verifique se o mPDF está instalado corretamente.');
            }
            
            // Enviar email
            $assunto = "Extrato de IPTU - Contrato: {$contrato}";
            
            // Corpo do email em HTML
            $corpoEmail = "
            <html>
            <body>
                <p>Segue em anexo o extrato de IPTU do contrato <strong>{$contrato}</strong>.</p>
                <p><strong>Cliente:</strong> " . ($cliente ?: 'Não informado') . "</p>
                <p><strong>Empreendimento:</strong> {$empreendimentoNome}</p>
                " . ($data_calculo ? "<p><strong>Data para Cálculo:</strong> " . date('d/m/Y', strtotime($data_calculo)) . "</p>" : "") . "
                <p><strong>Instruções:</strong> Baixe o arquivo PDF anexo antes de abrir.</p>
                <hr>
                <p style='font-size: 12px; color: #666;'>Este é um email automático, por favor não responda.</p>
            </body>
            </html>
            ";
            
            // Preparar email multipart com anexo PDF
            $boundary = md5(time());
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "From: Sistema SISIPTU <noreply@sisiptu.com>\r\n";
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
            
            $corpoEmailCompleto = "--{$boundary}\r\n";
            $corpoEmailCompleto .= "Content-Type: text/html; charset=UTF-8\r\n";
            $corpoEmailCompleto .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $corpoEmailCompleto .= $corpoEmail . "\r\n";
            
            // Anexar extrato PDF
            $nomeArquivo = "extrato_iptu_{$contrato}_" . date('YmdHis') . ".pdf";
            $corpoEmailCompleto .= "--{$boundary}\r\n";
            $corpoEmailCompleto .= "Content-Type: application/pdf\r\n";
            $corpoEmailCompleto .= "Content-Disposition: attachment; filename=\"{$nomeArquivo}\"\r\n";
            $corpoEmailCompleto .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $corpoEmailCompleto .= chunk_split(base64_encode($pdfContent)) . "\r\n";
            $corpoEmailCompleto .= "--{$boundary}--\r\n";
            
            // Enviar email
            $enviado = mail($emailDestino, $assunto, $corpoEmailCompleto, $headers);
            
            if ($enviado) {
                jsonResponse(true, "Extrato enviado por email com sucesso para: {$emailDestino}");
            } else {
                logError('Erro ao enviar email', [
                    'email' => $emailDestino,
                    'contrato' => $contrato
                ]);
                jsonResponse(false, 'Erro ao enviar email. Verifique a configuração do servidor de email.');
            }
            break;
            
        default:
            if ($action) {
                jsonResponse(false, 'Ação não reconhecida.');
            }
            break;
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

