<?php
// API Cobrança Automática (restaurada do Git + compatibilidade de colunas)
// - actions: pesquisar-titulos (GET), processar (POST JSON)
// - Gera CNAB quando empreendimento tem banco configurado (banco_id) e php/cnab está presente

if (!ob_get_level()) {
    ob_start();
}

ini_set('display_errors', '0');
error_reporting(E_ALL);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (function_exists('logError')) {
            try {
                logError('Erro fatal capturado no shutdown', [
                    'type' => $error['type'],
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                ]);
            } catch (Throwable $e) {
                error_log("Erro fatal: " . $error['message'] . " em " . $error['file'] . ":" . $error['line']);
            }
        } else {
            error_log("Erro fatal: " . $error['message'] . " em " . $error['file'] . ":" . $error['line']);
        }

        if (ob_get_length()) {
            @ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro fatal no servidor: ' . $error['message'] . ' em ' . $error['file'] . ':' . $error['line'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

session_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/logger.php';

function jsonResponse(bool $sucesso, string $mensagem, array $extra = [], int $status = 200): void
{
    if (ob_get_length()) {
        @ob_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    jsonResponse(false, 'Acesso não autorizado. Faça login novamente.', [], 401);
}

function tableHasColumn(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS existe
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = :table
          AND column_name = :column
    ");
    $stmt->execute([':table' => $table, ':column' => $column]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && (int)$row['existe'] > 0;
}

// Ler action de GET ou POST(JSON)
$action = '';
$data = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $action = is_array($data) ? ($data['action'] ?? '') : '';
} else {
    $action = $_REQUEST['action'] ?? '';
}

try {
    $pdo = getConnection();

    // Compatibilidade de colunas da tabela cobranca
    $hasDatavencimento = tableHasColumn($pdo, 'cobranca', 'datavencimento');
    $hasDataVencimento = tableHasColumn($pdo, 'cobranca', 'data_vencimento');
    $colVenc = $hasDatavencimento ? 'c.datavencimento' : ($hasDataVencimento ? 'c.data_vencimento' : 'NULL');

    $hasTitulo = tableHasColumn($pdo, 'cobranca', 'titulo');
    $hasAnoRef = tableHasColumn($pdo, 'cobranca', 'ano_referencia');
    $hasPago = tableHasColumn($pdo, 'cobranca', 'pago');
    $hasObs = tableHasColumn($pdo, 'cobranca', 'observacao');
    $hasSituacao = tableHasColumn($pdo, 'cobranca', 'situacao');
    $hasDataEnvio = tableHasColumn($pdo, 'cobranca', 'dataenvio');

    switch ($action) {
        case 'pesquisar-titulos': {
            $empreendimento_id = isset($_GET['empreendimento_id']) && $_GET['empreendimento_id'] !== '' ? (int)$_GET['empreendimento_id'] : null;
            $periodo_inicio = isset($_GET['periodo_inicio']) ? trim((string)$_GET['periodo_inicio']) : null;
            $periodo_fim = isset($_GET['periodo_fim']) ? trim((string)$_GET['periodo_fim']) : null;
            $titulo = isset($_GET['titulo']) ? trim((string)$_GET['titulo']) : null;
            $contrato = isset($_GET['contrato']) ? trim((string)$_GET['contrato']) : null;

            if (!$empreendimento_id) {
                jsonResponse(false, 'Empreendimento é obrigatório.');
            }
            if (!$periodo_inicio || !$periodo_fim) {
                jsonResponse(false, 'Período de referência é obrigatório.');
            }

            $sql = "
                SELECT 
                    c.id,
                    c.empreendimento_id,
                    e.nome AS empreendimento_nome,
                    c.modulo_id,
                    m.nome AS modulo_nome,
                    c.contrato,
                    c.cliente_nome,
                    c.parcelamento,
                    c.valor_mensal,
                    {$colVenc} AS datavencimento,
                    " . ($hasSituacao ? 'c.situacao' : "NULL::text AS situacao") . ",
                    " . ($hasPago ? 'c.pago' : "NULL::text AS pago") . ",
                    " . ($hasObs ? 'c.observacao' : "NULL::text AS observacao");

            if ($hasTitulo) {
                $sql .= ", c.titulo";
            }
            if ($hasAnoRef) {
                $sql .= ", c.ano_referencia";
            }

            $sql .= "
                FROM cobranca c
                LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
                LEFT JOIN modulos m ON m.id = c.modulo_id
                WHERE c.empreendimento_id = :empreendimento_id
                  AND {$colVenc} >= :periodo_inicio
                  AND {$colVenc} <= :periodo_fim";

            if ($hasPago) {
                $sql .= " AND (c.pago IS NULL OR c.pago = '' OR c.pago = 'N' OR c.pago = 'n')";
            }

            $params = [
                ':empreendimento_id' => $empreendimento_id,
                ':periodo_inicio' => $periodo_inicio,
                ':periodo_fim' => $periodo_fim,
            ];

            if ($titulo) {
                if ($hasTitulo) {
                    if (is_numeric($titulo)) {
                        $sql .= " AND (c.titulo IS NOT NULL AND (CAST(c.titulo AS TEXT) = :titulo_exato OR CAST(c.titulo AS TEXT) LIKE :titulo_like))";
                        $params[':titulo_exato'] = (string)$titulo;
                        $params[':titulo_like'] = '%' . $titulo . '%';
                    } else {
                        $sql .= " AND (c.titulo IS NOT NULL AND CAST(c.titulo AS TEXT) ILIKE :titulo_like)";
                        $params[':titulo_like'] = '%' . $titulo . '%';
                    }
                } else {
                    $sql .= " AND CAST(c.id AS TEXT) LIKE :titulo_like";
                    $params[':titulo_like'] = '%' . $titulo . '%';
                }
            }

            if ($contrato) {
                $sql .= " AND c.contrato LIKE :contrato";
                $params[':contrato'] = '%' . $contrato . '%';
            }

            $sql .= " ORDER BY {$colVenc} ASC, c.parcelamento ASC";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $titulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse(true, 'Pesquisa realizada com sucesso.', [
                'titulos' => $titulos,
                'total' => count($titulos),
            ]);
            break;
        }

        case 'processar': {
            try {
                logError('Iniciando processamento de cobrança automática', [
                    'action' => 'processar',
                    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
                    'usuario' => $_SESSION['usuario'] ?? 'N/A',
                    'usuario_id' => $_SESSION['usuario_id'] ?? 'N/A',
                ]);
            } catch (Throwable $e) {
                // ignore
            }

            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            if (!$data || !is_array($data)) {
                jsonResponse(false, 'Dados inválidos. Erro JSON: ' . json_last_error_msg());
            }

            $empreendimento_id = isset($data['empreendimento_id']) ? (int)$data['empreendimento_id'] : null;
            $remissao_boletos = isset($data['remissao_boletos']) ? (int)$data['remissao_boletos'] : 0;
            $titulos = isset($data['titulos']) && is_array($data['titulos']) ? $data['titulos'] : [];

            if (!$empreendimento_id) {
                jsonResponse(false, 'Empreendimento é obrigatório.');
            }
            if (empty($titulos)) {
                jsonResponse(false, 'Nenhum título selecionado para processar.');
            }

            // Buscar banco do empreendimento
            $stmt = $pdo->prepare("SELECT banco_id FROM empreendimentos WHERE id = :id");
            $stmt->bindValue(':id', $empreendimento_id, PDO::PARAM_INT);
            $stmt->execute();
            $empreendimento = $stmt->fetch(PDO::FETCH_ASSOC);
            $banco_id = $empreendimento['banco_id'] ?? null;

            if (!$banco_id) {
                jsonResponse(false, 'Empreendimento não possui banco configurado. Configure um banco no cadastro de empreendimentos para gerar remessa CNAB.');
            }

            $autoloadPath = __DIR__ . '/cnab/autoload.php';
            if (!file_exists($autoloadPath)) {
                jsonResponse(false, 'Sistema CNAB não encontrado. Verifique a instalação.');
            }
            require_once $autoloadPath;
            if (!class_exists('CnabFactory')) {
                jsonResponse(false, 'Sistema CNAB inválido (CnabFactory não carregou).');
            }

            $pdo->beginTransaction();
            try {
                $processados = 0;
                $erros = [];
                $arquivoCnab = null;

                // Buscar dados do banco
                $stmtBanco = $pdo->prepare("
                    SELECT id, cedente, cnpj_cpf, banco, conta, agencia, num_banco, carteira,
                           operacao_cc, codigo_cedente, operacao_cedente, caminho_remessa, multa_mes, juros_mes
                    FROM bancos
                    WHERE id = :id
                ");
                $stmtBanco->bindValue(':id', (int)$banco_id, PDO::PARAM_INT);
                $stmtBanco->execute();
                $banco = $stmtBanco->fetch(PDO::FETCH_ASSOC);
                if (!$banco) {
                    throw new Exception('Banco não encontrado no banco de dados.');
                }
                if (empty($banco['caminho_remessa'])) {
                    throw new Exception('Caminho de remessa não configurado para este banco. Configure o caminho no cadastro de bancos.');
                }

                // Preparar dados do banco para CNAB (mesma lógica do Git)
                $agenciaCompleta = $banco['agencia'] ?? '';
                $agenciaParts = explode('-', $agenciaCompleta);
                $agencia = preg_replace('/[^0-9]/', '', $agenciaParts[0]);
                $dvAgencia = '';
                if (count($agenciaParts) > 1) {
                    $dvAgencia = preg_replace('/[^0-9]/', '', $agenciaParts[1]);
                } else {
                    $agenciaLimpa = preg_replace('/[^0-9]/', '', $agenciaCompleta);
                    if (strlen($agenciaLimpa) > 4) {
                        $dvAgencia = substr($agenciaLimpa, -1);
                        $agencia = substr($agenciaLimpa, 0, -1);
                    }
                }

                $contaCompleta = $banco['conta'] ?? '';
                $contaParts = explode('-', $contaCompleta);
                $conta = preg_replace('/[^0-9]/', '', $contaParts[0]);
                $dvConta = '';
                if (count($contaParts) > 1) {
                    $dvConta = preg_replace('/[^0-9]/', '', $contaParts[1]);
                } else {
                    $contaLimpa = preg_replace('/[^0-9]/', '', $contaCompleta);
                    if (strlen($contaLimpa) > 7) {
                        $dvConta = substr($contaLimpa, -1);
                        $conta = substr($contaLimpa, 0, -1);
                    }
                }

                $dadosBanco = [
                    'agencia' => $agencia,
                    'dv_agencia' => $dvAgencia,
                    'conta' => $conta,
                    'dv_conta' => $dvConta,
                    // documento do cedente (usado em alguns layouts CNAB, ex.: Itaú registro 1)
                    'cnpj_cpf' => $banco['cnpj_cpf'] ?? '',
                    'codigo_cedente' => $banco['codigo_cedente'] ?? '',
                    'cedente' => $banco['cedente'] ?? '',
                    'carteira' => $banco['carteira'] ?? '',
                    'num_banco' => $banco['num_banco'] ?? $banco['id'],
                    'multa_mes' => $banco['multa_mes'] ?? 0,
                    'juros_mes' => $banco['juros_mes'] ?? 0,
                ];

                $codigoBanco = $banco['num_banco'] ?? '001';
                if (empty($codigoBanco) || strlen((string)$codigoBanco) < 3) {
                    $nomeBanco = strtoupper($banco['banco'] ?? '');
                    if (strpos($nomeBanco, 'BRASIL') !== false) $codigoBanco = '001';
                    elseif (strpos($nomeBanco, 'BRADESCO') !== false) $codigoBanco = '237';
                    elseif (strpos($nomeBanco, 'ITAU') !== false || strpos($nomeBanco, 'ITÁU') !== false) $codigoBanco = '341';
                    elseif (strpos($nomeBanco, 'SANTANDER') !== false) $codigoBanco = '033';
                    elseif (strpos($nomeBanco, 'CAIXA') !== false) $codigoBanco = '104';
                    elseif (strpos($nomeBanco, 'SICREDI') !== false) $codigoBanco = '748';
                    elseif (strpos($nomeBanco, 'INTER') !== false) $codigoBanco = '077';
                    else $codigoBanco = '001';
                }

                if (!CnabFactory::isBancoSuportado($codigoBanco)) {
                    throw new Exception("Banco com código {$codigoBanco} não é suportado.");
                }

                $cnab = CnabFactory::criar($codigoBanco, 400);
                if (!$cnab) {
                    throw new Exception('Não foi possível criar instância do CNAB.');
                }

                // Buscar dados completos dos títulos (precisa garantir datavencimento para CNAB)
                $titulosIds = array_map(function ($t) {
                    return isset($t['id']) ? (int)$t['id'] : 0;
                }, $titulos);
                $titulosIds = array_values(array_filter($titulosIds, fn($id) => $id > 0));
                if (empty($titulosIds)) {
                    throw new Exception('Nenhum título válido encontrado.');
                }

                $placeholders = implode(',', array_fill(0, count($titulosIds), '?'));
                $stmtTitulos = $pdo->prepare("
                    SELECT 
                        c.*,
                        {$colVenc} AS datavencimento,
                        e.nome as empreendimento_nome, 
                        m.nome as modulo_nome,
                        cli.endereco as endereco_cliente,
                        cli.bairro as bairro_cliente,
                        cli.cidade as cidade_cliente,
                        cli.uf as uf_cliente,
                        cli.cep as cep_cliente,
                        cli.cpf_cnpj as cpf_cnpj_cliente,
                        cli.nome as cliente_nome_completo
                    FROM cobranca c
                    LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
                    LEFT JOIN modulos m ON m.id = c.modulo_id
                    LEFT JOIN clientes cli ON cli.cpf_cnpj = c.cpf_cnpj
                    WHERE c.id IN ({$placeholders})
                ");
                $stmtTitulos->execute($titulosIds);
                $titulosCompletos = $stmtTitulos->fetchAll(PDO::FETCH_ASSOC);
                if (empty($titulosCompletos)) {
                    throw new Exception('Nenhum título encontrado.');
                }

                // Gerar arquivo CNAB
                $caminhoRemessa = trim((string)$banco['caminho_remessa']);
                try {
                    $arquivoCnab = $cnab->gerarRemessa($dadosBanco, $titulosCompletos, $caminhoRemessa);
                } catch (Throwable $eCnab) {
                    $erros[] = $eCnab->getMessage();
                    $arquivoCnab = null;
                    try {
                        logError('Erro ao gerar CNAB na cobrança automática', [
                            'error' => $eCnab->getMessage(),
                            'trace' => $eCnab->getTraceAsString(),
                            'caminho_remessa' => $caminhoRemessa,
                        ], $eCnab);
                    } catch (Throwable $eLog) {
                        // ignore
                    }
                }

                // Marcar títulos como enviados/processados
                foreach ($titulos as $titulo) {
                    $titulo_id = isset($titulo['id']) ? (int)$titulo['id'] : 0;
                    if ($titulo_id <= 0) {
                        $erros[] = 'ID do título inválido';
                        continue;
                    }

                    $situacao = $arquivoCnab ? 'ENVIADO' : 'PROCESSADO';
                    $sets = [];
                    $params = [':id' => $titulo_id];

                    if ($hasDataEnvio) {
                        $sets[] = "dataenvio = CURRENT_DATE";
                    }
                    if ($hasSituacao) {
                        $sets[] = "situacao = :situacao";
                        $params[':situacao'] = $situacao;
                    }

                    if (!empty($sets)) {
                        $sqlUpdate = "UPDATE cobranca SET " . implode(', ', $sets) . " WHERE id = :id";
                        $stmtUpdate = $pdo->prepare($sqlUpdate);
                        foreach ($params as $k => $v) {
                            $stmtUpdate->bindValue($k, $v, ($k === ':id') ? PDO::PARAM_INT : PDO::PARAM_STR);
                        }
                        $stmtUpdate->execute();
                    }
                    $processados++;
                }

                $pdo->commit();

                $mensagem = "✅ Processados {$processados} título(s) com sucesso.";
                if ($arquivoCnab) {
                    $mensagem .= "\n\n📄 Arquivo CNAB de remessa gerado com sucesso!";
                    $mensagem .= "\n📁 Arquivo: " . basename($arquivoCnab);
                    if (file_exists($arquivoCnab)) {
                        $mensagem .= "\n💾 Tamanho: " . number_format(filesize($arquivoCnab), 0, ',', '.') . " bytes";
                    }
                } else {
                    $mensagem .= "\n\n⚠️ Atenção: Não foi possível gerar o arquivo CNAB de remessa.";
                }
                if (!empty($erros)) {
                    $mensagem .= "\n\n❌ Erro(s) encontrado(s):";
                    foreach ($erros as $erro) {
                        $mensagem .= "\n   • " . $erro;
                    }
                }

                $resp = [
                    'processados' => $processados,
                    'total' => count($titulos),
                    'erros' => $erros,
                    'remessa_gerada' => (bool)$arquivoCnab,
                ];
                if ($arquivoCnab) {
                    $resp['arquivo_cnab'] = basename($arquivoCnab);
                    $resp['caminho_cnab'] = $arquivoCnab;
                }

                jsonResponse(true, $mensagem, $resp);
            } catch (Throwable $e) {
                try {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                } catch (Throwable $eRollback) {
                    // ignore
                }
                try {
                    logError('Erro ao processar cobrança automática', [
                        'action' => $action,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'empreendimento_id' => $empreendimento_id ?? null,
                        'banco_id' => $banco_id ?? null,
                    ], $e);
                } catch (Throwable $eLog) {
                    // ignore
                }
                jsonResponse(false, 'Erro ao processar títulos: ' . $e->getMessage(), [], 500);
            }

            break;
        }

        default:
            jsonResponse(false, 'Ação não reconhecida.', [], 400);
    }
} catch (PDOException $e) {
    try {
        logError('Erro PDO na cobrança automática', [
            'action' => $action,
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
        ], $e);
    } catch (Throwable $eLog) {
        // ignore
    }
    jsonResponse(false, 'Erro ao processar: ' . $e->getMessage(), [], 500);
} catch (Throwable $e) {
    try {
        logError('Erro geral na cobrança automática', [
            'action' => $action,
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
        ], $e);
    } catch (Throwable $eLog) {
        // ignore
    }
    jsonResponse(false, 'Erro ao processar: ' . $e->getMessage(), [], 500);
}

