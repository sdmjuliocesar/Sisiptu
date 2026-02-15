<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/logger.php';
require_once __DIR__ . '/../config/extrato_token.php';

function jsonResponse($sucesso, $mensagem, $extra = []) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$acaoPublica = ($action === 'publico-pdf');

function base64url_encode_str(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode_str(string $data): string {
    $b64 = strtr($data, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad) $b64 .= str_repeat('=', 4 - $pad);
    $decoded = base64_decode($b64, true);
    return $decoded === false ? '' : $decoded;
}

function gerarTokenExtrato(array $payload, int $ttlSeconds): string {
    $agora = time();
    $payload['iat'] = $agora;
    $payload['exp'] = $agora + max(60, $ttlSeconds);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $p = base64url_encode_str($json ?: '{}');
    $sig = base64url_encode_str(hash_hmac('sha256', $p, EXTRATO_TOKEN_SECRET, true));
    return $p . '.' . $sig;
}

function validarTokenExtrato(string $token): array {
    $partes = explode('.', $token);
    if (count($partes) !== 2) return ['valido' => false, 'erro' => 'Token inválido.'];
    [$p, $sig] = $partes;
    if ($p === '' || $sig === '') return ['valido' => false, 'erro' => 'Token inválido.'];
    $sigEsperada = base64url_encode_str(hash_hmac('sha256', $p, EXTRATO_TOKEN_SECRET, true));
    if (!hash_equals($sigEsperada, $sig)) return ['valido' => false, 'erro' => 'Token inválido.'];

    $json = base64url_decode_str($p);
    $payload = json_decode($json, true);
    if (!is_array($payload)) return ['valido' => false, 'erro' => 'Token inválido.'];
    $exp = isset($payload['exp']) ? (int)$payload['exp'] : 0;
    if ($exp <= 0 || time() > $exp) return ['valido' => false, 'erro' => 'Link expirado. Gere um novo extrato e reenvie.'];

    return ['valido' => true, 'payload' => $payload];
}

function publicError(string $mensagem, int $code = 403): void {
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo $mensagem;
    exit;
}

// Se não for ação pública, exige sessão
if (!$acaoPublica && (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true)) {
    jsonResponse(false, 'Acesso não autorizado. Faça login novamente.');
}

try {
    $pdo = getConnection();

    // Gera um link público assinado (para WhatsApp) - exige login
    if ($action === 'gerar-link-publico') {
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

        $payload = [
            'empreendimento_id' => (int)$empreendimento_id,
            'modulo_id' => (int)$modulo_id,
            'contrato' => (string)$contrato,
            'cliente' => (string)($cliente ?? ''),
            'data_calculo' => (string)($data_calculo ?? ''),
            'filtro_titulo' => (string)($filtro_titulo ?? 'todos'),
            'ordem' => (string)($ordem ?? 'vencimento'),
        ];
        $token = gerarTokenExtrato($payload, defined('EXTRATO_TOKEN_TTL_SECONDS') ? (int)EXTRATO_TOKEN_TTL_SECONDS : 86400);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $url = $scheme . '://' . $host . $scriptDir . '/extrato_api.php?action=publico-pdf&token=' . rawurlencode($token);

        jsonResponse(true, 'Link gerado com sucesso.', ['url' => $url]);
    }

    // Envia extrato por email (exige login)
    if ($action === 'enviar-email') {
        $emailDestino = isset($_REQUEST['email']) ? trim((string)$_REQUEST['email']) : '';
        if (!$emailDestino || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'Email inválido.');
        }

        registrarLog('INFO', 'Tentativa de envio de extrato por email', [
            'email' => $emailDestino,
            'empreendimento_id' => $_REQUEST['empreendimento_id'] ?? null,
            'modulo_id' => $_REQUEST['modulo_id'] ?? null,
            'contrato' => $_REQUEST['contrato'] ?? null,
        ]);
        // Continua o fluxo normal para carregar cobranças e gerar o PDF (mais abaixo),
        // e então envia com mail(). A parte de envio fica logo após a geração do PDF.
    }
    
    // Coletar parâmetros
    if ($acaoPublica) {
        $token = isset($_REQUEST['token']) ? (string)$_REQUEST['token'] : '';
        if (!$token) {
            publicError('Token não informado.');
        }
        $v = validarTokenExtrato($token);
        if (!$v['valido']) {
            publicError($v['erro'] ?? 'Token inválido.');
        }
        $p = $v['payload'];
        $empreendimento_id = isset($p['empreendimento_id']) ? (int)$p['empreendimento_id'] : null;
        $modulo_id = isset($p['modulo_id']) ? (int)$p['modulo_id'] : null;
        $contrato = isset($p['contrato']) ? trim((string)$p['contrato']) : null;
        $cliente = isset($p['cliente']) ? trim((string)$p['cliente']) : null;
        $data_calculo = isset($p['data_calculo']) ? trim((string)$p['data_calculo']) : null;
        $filtro_titulo = isset($p['filtro_titulo']) ? trim((string)$p['filtro_titulo']) : 'todos';
        $ordem = isset($p['ordem']) ? trim((string)$p['ordem']) : 'vencimento';
    } else {
        $empreendimento_id = isset($_REQUEST['empreendimento_id']) && $_REQUEST['empreendimento_id'] !== '' ? (int)$_REQUEST['empreendimento_id'] : null;
        $modulo_id = isset($_REQUEST['modulo_id']) && $_REQUEST['modulo_id'] !== '' ? (int)$_REQUEST['modulo_id'] : null;
        $contrato = isset($_REQUEST['contrato']) ? trim($_REQUEST['contrato']) : null;
        $cliente = isset($_REQUEST['cliente']) ? trim($_REQUEST['cliente']) : null;
        $data_calculo = isset($_REQUEST['data_calculo']) ? trim($_REQUEST['data_calculo']) : null;
        $filtro_titulo = isset($_REQUEST['filtro_titulo']) ? trim($_REQUEST['filtro_titulo']) : 'todos';
        $ordem = isset($_REQUEST['ordem']) ? trim($_REQUEST['ordem']) : 'vencimento';
    }
    
    if (!$empreendimento_id || !$modulo_id || !$contrato) {
        if ($acaoPublica) {
            publicError('Parâmetros inválidos no token.');
        }
        jsonResponse(false, 'Empreendimento, Módulo e Contrato são obrigatórios.');
    }

    // Data referência (para cálculo de juros/multa no extrato)
    $dataReferencia = $data_calculo ? DateTime::createFromFormat('Y-m-d', $data_calculo) : new DateTime('today');
    if (!$dataReferencia) {
        $dataReferencia = new DateTime('today');
    }
    $dataReferencia->setTime(0, 0, 0);
    
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

    // Helpers
    $fmtMoney = static function ($v) {
        $n = is_numeric($v) ? (float)$v : 0.0;
        return number_format($n, 2, ',', '.');
    };
    $fmtDateBR = static function ($ymd) {
        if (!$ymd) return '';
        $dt = DateTime::createFromFormat('Y-m-d', substr((string)$ymd, 0, 10));
        return $dt ? $dt->format('d/m/Y') : (string)$ymd;
    };
    $calcJurosMulta = static function (array $c, DateTime $ref) {
        $valor = is_numeric($c['valor_mensal'] ?? null) ? (float)$c['valor_mensal'] : 0.0;
        $jurosMes = is_numeric($c['juros_mes'] ?? null) ? (float)$c['juros_mes'] : 0.0;   // %
        $multaMes = is_numeric($c['multa_mes'] ?? null) ? (float)$c['multa_mes'] : 0.0;   // %

        $pago = strtoupper((string)($c['pago'] ?? '')) === 'S';
        $venc = DateTime::createFromFormat('Y-m-d', substr((string)($c['datavencimento'] ?? ''), 0, 10));
        if (!$venc) {
            return ['juros' => 0.0, 'multa' => 0.0, 'dias' => 0, 'em_atraso' => false, 'pago' => $pago];
        }
        $venc->setTime(0, 0, 0);

        if ($pago) {
            return ['juros' => 0.0, 'multa' => 0.0, 'dias' => 0, 'em_atraso' => false, 'pago' => true];
        }

        $dias = (int)$venc->diff($ref)->format('%r%a');
        $emAtraso = ($dias > 0);
        if (!$emAtraso) {
            return ['juros' => 0.0, 'multa' => 0.0, 'dias' => 0, 'em_atraso' => false, 'pago' => false];
        }

        // Juros diário aproximado a partir do juros mensal (% / 30)
        $jurosDia = ($jurosMes > 0) ? (($valor * ($jurosMes / 100.0)) / 30.0) : 0.0;
        $juros = $jurosDia * $dias;
        $multa = ($multaMes > 0) ? ($valor * ($multaMes / 100.0)) : 0.0;

        return ['juros' => $juros, 'multa' => $multa, 'dias' => $dias, 'em_atraso' => true, 'pago' => false];
    };

    if ($action === 'gerar-pdf' || $action === 'publico-pdf' || $action === 'enviar-email') {
        // Gerar PDF do extrato
        $autoload = __DIR__ . '/../Vendor/autoload.php';
        if (!file_exists($autoload)) {
            $autoload = __DIR__ . '/../vendor/autoload.php';
        }
        if (!file_exists($autoload)) {
            if ($acaoPublica) publicError('Dependências do PDF não encontradas.', 500);
            jsonResponse(false, 'Dependências do PDF não encontradas (autoload do Composer).');
        }
        require_once $autoload;

        $dataExtrato = (new DateTime())->format('d/m/Y');
        $dataCalcBR = $data_calculo ? $fmtDateBR($data_calculo) : '';

        $rowsHtml = '';
        $totalValor = 0.0;
        $totalJuros = 0.0;
        $totalMulta = 0.0;
        $totalGeral = 0.0;

        foreach ($cobrancas as $c) {
            $valor = is_numeric($c['valor_mensal'] ?? null) ? (float)$c['valor_mensal'] : 0.0;
            $calc = $calcJurosMulta($c, $dataReferencia);
            $juros = $calc['juros'];
            $multa = $calc['multa'];
            $total = $valor + $juros + $multa;

            $totalValor += $valor;
            $totalJuros += $juros;
            $totalMulta += $multa;
            $totalGeral += $total;

            $titulo = $colunaTituloExiste ? (string)($c['titulo'] ?? '') : (string)($c['id'] ?? '');
            $parcela = (string)($c['parcelamento'] ?? '');
            $venc = $fmtDateBR($c['datavencimento'] ?? '');
            $status = $calc['pago'] ? 'PAGO' : ($calc['em_atraso'] ? ('VENCIDO (' . $calc['dias'] . 'd)') : 'EM ABERTO');

            $rowsHtml .= '<tr>'
                . '<td style="text-align:left;">' . htmlspecialchars($titulo) . '</td>'
                . '<td style="text-align:center;">' . htmlspecialchars($parcela) . '</td>'
                . '<td style="text-align:center;">' . htmlspecialchars($venc) . '</td>'
                . '<td style="text-align:right;">' . $fmtMoney($valor) . '</td>'
                . '<td style="text-align:right;">' . $fmtMoney($juros) . '</td>'
                . '<td style="text-align:right;">' . $fmtMoney($multa) . '</td>'
                . '<td style="text-align:right;"><strong>' . $fmtMoney($total) . '</strong></td>'
                . '<td style="text-align:center;">' . htmlspecialchars($status) . '</td>'
                . '</tr>';
        }

        if ($rowsHtml === '') {
            $rowsHtml = '<tr><td colspan="8" style="text-align:center;padding:12px;">Nenhum título encontrado.</td></tr>';
        }

        $html = '
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: Arial, sans-serif; font-size: 10.5pt; color: #111; }
    .top { margin-bottom: 10px; }
    .title { font-size: 14pt; font-weight: bold; margin: 0 0 4px 0; }
    .meta { font-size: 10pt; color: #333; margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #bbb; padding: 6px; }
    th { background: #f2f2f2; font-weight: bold; }
    .totais { margin-top: 10px; width: 100%; }
    .totais td { border: none; padding: 2px 0; }
    .totais .lbl { text-align: right; padding-right: 8px; color: #333; }
    .totais .val { text-align: right; width: 120px; }
  </style>
</head>
<body>
  <div class="top">
    <p class="title">Extrato de Cobranças (IPTU)</p>
    <p class="meta"><strong>Empreendimento:</strong> ' . htmlspecialchars($empreendimentoNome) . '</p>
    <p class="meta"><strong>Contrato:</strong> ' . htmlspecialchars($contrato) . ' &nbsp;&nbsp; <strong>Cliente:</strong> ' . htmlspecialchars($cliente ?: '') . '</p>
    <p class="meta"><strong>Data do extrato:</strong> ' . $dataExtrato . ($dataCalcBR ? (' &nbsp;&nbsp; <strong>Data de cálculo:</strong> ' . htmlspecialchars($dataCalcBR)) : '') . '</p>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:14%;">Título</th>
        <th style="width:9%;">Parcela</th>
        <th style="width:12%;">Vencimento</th>
        <th style="width:12%;">Valor</th>
        <th style="width:12%;">Juros</th>
        <th style="width:12%;">Multa</th>
        <th style="width:13%;">Total</th>
        <th style="width:16%;">Situação</th>
      </tr>
    </thead>
    <tbody>
      ' . $rowsHtml . '
    </tbody>
  </table>

  <table class="totais">
    <tr><td class="lbl"><strong>Total Valor:</strong></td><td class="val">' . $fmtMoney($totalValor) . '</td></tr>
    <tr><td class="lbl"><strong>Total Juros:</strong></td><td class="val">' . $fmtMoney($totalJuros) . '</td></tr>
    <tr><td class="lbl"><strong>Total Multa:</strong></td><td class="val">' . $fmtMoney($totalMulta) . '</td></tr>
    <tr><td class="lbl"><strong>Total Geral:</strong></td><td class="val"><strong>' . $fmtMoney($totalGeral) . '</strong></td></tr>
  </table>
</body>
</html>';

        $filename = 'extrato_iptu_contrato_' . preg_replace('/\D+/', '', (string)$contrato) . '_' . date('Ymd_His') . '.pdf';

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);
        $mpdf->WriteHTML($html);

        $pdfBytes = $mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);

        if ($action === 'enviar-email') {
            $emailDestino = isset($_REQUEST['email']) ? trim((string)$_REQUEST['email']) : '';
            if (!$emailDestino || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(false, 'Email inválido.');
            }

            $assunto = 'Extrato de IPTU - Contrato ' . (string)$contrato;
            $mensagemTexto = "Olá!\n\nSegue em anexo o extrato de IPTU do contrato {$contrato}.\n\nAtenciosamente,\nSISIPTU";

            // From (fallback). Em XAMPP/Windows, se não estiver configurado pode falhar.
            $from = ini_get('sendmail_from');
            if (!$from || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
                $from = 'no-reply@sisiptu.local';
            }

            $boundary = '=_SISIPTU_' . bin2hex(random_bytes(12));
            $headers = [];
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'From: ' . $from;
            $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

            $body = '';
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= $mensagemTexto . "\r\n\r\n";

            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: application/pdf; name=\"{$filename}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
            $body .= chunk_split(base64_encode($pdfBytes)) . "\r\n";
            $body .= "--{$boundary}--\r\n";

            $ok = false;
            $ultimoErroAntes = error_get_last();
            try {
                $ok = @mail($emailDestino, $assunto, $body, implode("\r\n", $headers));
            } catch (Throwable $e) {
                logError('Exceção ao chamar mail() no envio de extrato', [
                    'email' => $emailDestino,
                    'from' => $from,
                    'sendmail_from' => ini_get('sendmail_from'),
                    'sendmail_path' => ini_get('sendmail_path'),
                    'SMTP' => ini_get('SMTP'),
                    'smtp_port' => ini_get('smtp_port'),
                ], $e);
                jsonResponse(false, 'Erro ao enviar email (exceção).');
            }
            $ultimoErroDepois = error_get_last();

            if (!$ok) {
                logError('Falha ao enviar extrato por email', [
                    'email' => $emailDestino,
                    'from' => $from,
                    'sendmail_from' => ini_get('sendmail_from'),
                    'sendmail_path' => ini_get('sendmail_path'),
                    'SMTP' => ini_get('SMTP'),
                    'smtp_port' => ini_get('smtp_port'),
                    'php_last_error_before' => $ultimoErroAntes,
                    'php_last_error_after' => $ultimoErroDepois,
                ], null);
                jsonResponse(false, 'Falha ao enviar email. Verifique a configuração de email do servidor.');
            }

            registrarLog('INFO', 'Extrato enviado por email com sucesso', [
                'email' => $emailDestino,
                'from' => $from,
                'contrato' => $contrato,
            ]);
            jsonResponse(true, 'Extrato enviado por email com sucesso.');
        }

        if (!headers_sent()) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
        }
        echo $pdfBytes;
        exit;
    }
    
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

