<?php
// Iniciar output buffering para evitar saída prematura
if (!ob_get_level()) {
    ob_start();
}

// Desabilitar exibição de erros na saída
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Registrar handler de erros fatais
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // Tentar registrar no log antes de enviar resposta
        if (function_exists('logError')) {
            try {
                logError('Erro fatal capturado no shutdown', [
                    'type' => $error['type'],
                    'message' => $error['message'],
                    'file' => $error['file'],
                    'line' => $error['line']
                ]);
            } catch (Exception $e) {
                error_log("Erro fatal: " . $error['message'] . " em " . $error['file'] . ":" . $error['line']);
            }
        } else {
            error_log("Erro fatal: " . $error['message'] . " em " . $error['file'] . ":" . $error['line']);
        }
        
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro fatal no servidor: ' . $error['message'] . ' em ' . $error['file'] . ':' . $error['line']
        ]);
        exit;
    }
});

session_start();

require_once __DIR__ . '/database.php';
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

function jsonResponse($sucesso, $mensagem, $extra = []) {
    // Limpar qualquer saída anterior
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

// Ler action de GET ou POST
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $action = isset($data['action']) ? $data['action'] : '';
} else {
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
}

try {
    $pdo = getConnection();
    
    switch ($action) {
        case 'pesquisar-titulos':
            $empreendimento_id = isset($_GET['empreendimento_id']) && $_GET['empreendimento_id'] !== '' ? (int)$_GET['empreendimento_id'] : null;
            $periodo_inicio = isset($_GET['periodo_inicio']) ? trim($_GET['periodo_inicio']) : null;
            $periodo_fim = isset($_GET['periodo_fim']) ? trim($_GET['periodo_fim']) : null;
            $titulo = isset($_GET['titulo']) ? trim($_GET['titulo']) : null;
            $contrato = isset($_GET['contrato']) ? trim($_GET['contrato']) : null;
            
            // Validações
            if (!$empreendimento_id) {
                jsonResponse(false, 'Empreendimento é obrigatório.');
            }
            
            if (!$periodo_inicio || !$periodo_fim) {
                jsonResponse(false, 'Período de referência é obrigatório.');
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
            
            // Montar query
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
                    c.datavencimento,
                    c.situacao,
                    c.pago,
                    c.observacao";
            
            if ($colunaTituloExiste) {
                $sql .= ", c.titulo";
            }
            
            if ($colunaAnoRefExiste) {
                $sql .= ", c.ano_referencia";
            }
            
            $sql .= "
                FROM cobranca c
                LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
                LEFT JOIN modulos m ON m.id = c.modulo_id
                WHERE c.empreendimento_id = :empreendimento_id
                  AND c.datavencimento >= :periodo_inicio
                  AND c.datavencimento <= :periodo_fim
                  AND (c.pago IS NULL OR c.pago = '' OR c.pago = 'N' OR c.pago = 'n')";
            
            $params = [
                ':empreendimento_id' => $empreendimento_id,
                ':periodo_inicio' => $periodo_inicio,
                ':periodo_fim' => $periodo_fim
            ];
            
            // Filtros opcionais
            if ($titulo) {
                if ($colunaTituloExiste) {
                    // Usar CAST para garantir que funciona com qualquer tipo de dado
                    // Buscar tanto por igualdade exata quanto por LIKE
                    if (is_numeric($titulo)) {
                        // Se for numérico, tentar busca exata primeiro, depois LIKE
                        $sql .= " AND (c.titulo IS NOT NULL AND (CAST(c.titulo AS TEXT) = :titulo_exato OR CAST(c.titulo AS TEXT) LIKE :titulo_like))";
                        $params[':titulo_exato'] = (string)$titulo;
                        $params[':titulo_like'] = '%' . $titulo . '%';
                    } else {
                        // Se for texto, usar ILIKE (case-insensitive)
                        $sql .= " AND (c.titulo IS NOT NULL AND CAST(c.titulo AS TEXT) ILIKE :titulo_like)";
                        $params[':titulo_like'] = '%' . $titulo . '%';
                    }
                } else {
                    // Se não existe coluna título, buscar pelo ID
                    $sql .= " AND CAST(c.id AS TEXT) LIKE :titulo_like";
                    $params[':titulo_like'] = '%' . $titulo . '%';
                }
            }
            
            if ($contrato) {
                $sql .= " AND c.contrato LIKE :contrato";
                $params[':contrato'] = '%' . $contrato . '%';
            }
            
            $sql .= " ORDER BY c.datavencimento ASC, c.parcelamento ASC";
            
            $stmt = $pdo->prepare($sql);
            
            // Bind dos parâmetros
            foreach ($params as $key => $value) {
                if (is_int($value)) {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } elseif (is_numeric($value) && strpos($key, '_num') !== false) {
                    // Se for um parâmetro numérico explicitamente marcado
                    $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            $titulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            jsonResponse(true, 'Pesquisa realizada com sucesso.', [
                'titulos' => $titulos,
                'total' => count($titulos)
            ]);
            break;
            
        case 'processar':
            // Log inicial do processamento
            logError('Iniciando processamento de cobrança automática', [
                'action' => 'processar',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
                'input_size' => strlen(file_get_contents('php://input'))
            ]);
            
            // Ler dados do POST JSON
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                $jsonError = json_last_error_msg();
                logError('Erro ao decodificar JSON no processamento', [
                    'json_error' => $jsonError,
                    'input_preview' => substr($input, 0, 500)
                ]);
                jsonResponse(false, 'Dados inválidos. Erro JSON: ' . $jsonError);
            }
            
            $empreendimento_id = isset($data['empreendimento_id']) ? (int)$data['empreendimento_id'] : null;
            $periodo_inicio = isset($data['periodo_inicio']) ? trim($data['periodo_inicio']) : null;
            $periodo_fim = isset($data['periodo_fim']) ? trim($data['periodo_fim']) : null;
            $remissao_boletos = isset($data['remissao_boletos']) ? (int)$data['remissao_boletos'] : 0;
            $titulos = isset($data['titulos']) && is_array($data['titulos']) ? $data['titulos'] : [];
            
            logError('Dados recebidos para processamento', [
                'empreendimento_id' => $empreendimento_id,
                'periodo_inicio' => $periodo_inicio,
                'periodo_fim' => $periodo_fim,
                'total_titulos' => count($titulos),
                'remissao_boletos' => $remissao_boletos
            ]);
            
            // Validações
            if (!$empreendimento_id) {
                logError('Validação falhou: empreendimento_id ausente');
                jsonResponse(false, 'Empreendimento é obrigatório.');
            }
            
            if (empty($titulos)) {
                logError('Validação falhou: nenhum título selecionado');
                jsonResponse(false, 'Nenhum título selecionado para processar.');
            }
            
            // Buscar banco do empreendimento
            try {
                $stmt = $pdo->prepare("
                    SELECT banco_id
                    FROM empreendimentos
                    WHERE id = :id
                ");
                $stmt->bindParam(':id', $empreendimento_id, PDO::PARAM_INT);
                $stmt->execute();
                $empreendimento = $stmt->fetch(PDO::FETCH_ASSOC);
                
                logError('Empreendimento consultado', [
                    'empreendimento_id' => $empreendimento_id,
                    'banco_id' => $empreendimento['banco_id'] ?? null
                ]);
            } catch (Exception $e) {
                logError('Erro ao buscar empreendimento', [
                    'empreendimento_id' => $empreendimento_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], $e);
                jsonResponse(false, 'Erro ao buscar empreendimento: ' . $e->getMessage());
            }
            
            $banco_id = $empreendimento['banco_id'] ?? null;
            
            // Verificar se há banco configurado para gerar remessa
            if (!$banco_id) {
                jsonResponse(false, 'Empreendimento não possui banco configurado. Configure um banco no cadastro de empreendimentos para gerar remessa CNAB.');
            }
            
            // Carregar autoload CNAB antes de iniciar transação
            if ($banco_id) {
                $autoloadPath = __DIR__ . '/cnab/autoload.php';
                logError('Verificando autoload CNAB', [
                    'banco_id' => $banco_id,
                    'autoload_path' => $autoloadPath,
                    'file_exists' => file_exists($autoloadPath)
                ]);
                
                if (!file_exists($autoloadPath)) {
                    logError('Autoload CNAB não encontrado', [
                        'path' => $autoloadPath,
                        'dir_exists' => is_dir(__DIR__ . '/cnab')
                    ]);
                    jsonResponse(false, 'Sistema CNAB não encontrado. Verifique a instalação.');
                }
                
                try {
                    require_once $autoloadPath;
                    logError('Autoload CNAB carregado com sucesso', [
                        'CnabFactory_exists' => class_exists('CnabFactory')
                    ]);
                } catch (Exception $e) {
                    logError('Erro ao carregar autoload CNAB', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ], $e);
                    jsonResponse(false, 'Erro ao carregar sistema CNAB: ' . $e->getMessage());
                } catch (Error $e) {
                    logError('Erro fatal ao carregar autoload CNAB', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ], $e);
                    jsonResponse(false, 'Erro fatal ao carregar sistema CNAB: ' . $e->getMessage());
                }
            }
            
            // Iniciar transação
            $pdo->beginTransaction();
            
            try {
                $processados = 0;
                $erros = [];
                $arquivoCnab = null;
                
                // Sempre gerar arquivo CNAB quando houver banco vinculado
                if ($banco_id) {
                    logError('Iniciando geração de arquivo CNAB', [
                        'banco_id' => $banco_id
                    ]);
                    
                    try {
                        // Buscar dados do banco
                        $stmtBanco = $pdo->prepare("
                            SELECT id, cedente, cnpj_cpf, banco, conta, agencia, num_banco, carteira,
                                   operacao_cc, codigo_cedente, operacao_cedente, caminho_remessa
                            FROM bancos
                            WHERE id = :id
                        ");
                        $stmtBanco->bindParam(':id', $banco_id, PDO::PARAM_INT);
                        $stmtBanco->execute();
                        $banco = $stmtBanco->fetch(PDO::FETCH_ASSOC);
                        
                        logError('Dados do banco consultados', [
                            'banco_id' => $banco_id,
                            'banco_encontrado' => !empty($banco),
                            'caminho_remessa' => $banco['caminho_remessa'] ?? 'N/A'
                        ]);
                        
                        if (!$banco) {
                            throw new Exception('Banco não encontrado no banco de dados.');
                        }
                        
                        if (empty($banco['caminho_remessa'])) {
                            throw new Exception('Caminho de remessa não configurado para este banco. Configure o caminho no cadastro de bancos.');
                        }
                        
                        // Preparar dados do banco para CNAB
                        $dadosBanco = [
                            'agencia' => $banco['agencia'] ?? '',
                            'dv_agencia' => substr($banco['agencia'] ?? '', -1),
                            'conta' => $banco['conta'] ?? '',
                            'dv_conta' => substr($banco['conta'] ?? '', -1),
                            'codigo_cedente' => $banco['codigo_cedente'] ?? '',
                            'cedente' => $banco['cedente'] ?? '',
                            'carteira' => $banco['carteira'] ?? '',
                            'num_banco' => $banco['num_banco'] ?? $banco['id']
                        ];
                        
                        // Determinar código do banco
                        $codigoBanco = $banco['num_banco'] ?? '001';
                        if (empty($codigoBanco) || strlen($codigoBanco) < 3) {
                            $nomeBanco = strtoupper($banco['banco'] ?? '');
                            if (strpos($nomeBanco, 'BRASIL') !== false) {
                                $codigoBanco = '001';
                            } elseif (strpos($nomeBanco, 'BRADESCO') !== false) {
                                $codigoBanco = '237';
                            } elseif (strpos($nomeBanco, 'ITAU') !== false || strpos($nomeBanco, 'ITÁU') !== false) {
                                $codigoBanco = '341';
                            } elseif (strpos($nomeBanco, 'SANTANDER') !== false) {
                                $codigoBanco = '033';
                            } elseif (strpos($nomeBanco, 'CAIXA') !== false) {
                                $codigoBanco = '104';
                            } elseif (strpos($nomeBanco, 'SICREDI') !== false) {
                                $codigoBanco = '748';
                            } elseif (strpos($nomeBanco, 'INTER') !== false) {
                                $codigoBanco = '077';
                            } else {
                                $codigoBanco = '001';
                            }
                        }
                        
                        logError('Verificando CnabFactory', [
                            'class_exists' => class_exists('CnabFactory'),
                            'codigo_banco' => $codigoBanco
                        ]);
                        
                        if (!class_exists('CnabFactory')) {
                            logError('CnabFactory não existe', [
                                'codigo_banco' => $codigoBanco
                            ]);
                            throw new Exception('Classe CnabFactory não foi carregada. Verifique o autoload.');
                        }
                        
                        if (!CnabFactory::isBancoSuportado($codigoBanco)) {
                            logError('Banco não suportado', [
                                'codigo_banco' => $codigoBanco,
                                'bancos_suportados' => CnabFactory::getBancosSuportados()
                            ]);
                            throw new Exception("Banco com código {$codigoBanco} não é suportado.");
                        }
                        
                        logError('Criando instância CNAB', [
                            'codigo_banco' => $codigoBanco,
                            'versao_cnab' => 400
                        ]);
                        
                        // Criar instância do CNAB
                        $cnab = CnabFactory::criar($codigoBanco, 400);
                        
                        if (!$cnab) {
                            logError('Falha ao criar instância CNAB', [
                                'codigo_banco' => $codigoBanco
                            ]);
                            throw new Exception('Não foi possível criar instância do CNAB.');
                        }
                        
                        logError('Instância CNAB criada com sucesso', [
                            'codigo_banco' => $cnab->getCodigoBanco(),
                            'nome_banco' => $cnab->getNomeBanco()
                        ]);
                        
                        // Buscar dados completos dos títulos
                        $titulosIds = array_map(function($t) {
                            return isset($t['id']) ? (int)$t['id'] : 0;
                        }, $titulos);
                        
                        $placeholders = implode(',', array_fill(0, count($titulosIds), '?'));
                        $stmtTitulos = $pdo->prepare("
                            SELECT 
                                c.*, 
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
                        
                        // Normalizar caminho de remessa
                        $caminhoRemessa = trim($banco['caminho_remessa']);
                        
                        logError('Preparando para gerar arquivo CNAB', [
                            'caminho_remessa' => $caminhoRemessa,
                            'total_titulos' => count($titulosCompletos),
                            'dados_banco_keys' => array_keys($dadosBanco)
                        ]);
                        
                        // Gerar arquivo CNAB
                        try {
                            $arquivoCnab = $cnab->gerarRemessa($dadosBanco, $titulosCompletos, $caminhoRemessa);
                            logError('Arquivo CNAB gerado com sucesso', [
                                'arquivo' => $arquivoCnab,
                                'file_exists' => file_exists($arquivoCnab),
                                'file_size' => file_exists($arquivoCnab) ? filesize($arquivoCnab) : 0
                            ]);
                        } catch (Exception $eCnab) {
                            logError('Erro ao gerar arquivo CNAB', [
                                'error' => $eCnab->getMessage(),
                                'caminho_remessa' => $caminhoRemessa,
                                'trace' => $eCnab->getTraceAsString()
                            ], $eCnab);
                            // Passar a mensagem original sem duplicar
                            throw $eCnab;
                        } catch (Error $eCnab) {
                            logError('Erro fatal ao gerar arquivo CNAB', [
                                'error' => $eCnab->getMessage(),
                                'caminho_remessa' => $caminhoRemessa,
                                'trace' => $eCnab->getTraceAsString()
                            ], $eCnab);
                            // Converter Error para Exception com mensagem clara
                            throw new Exception('Erro fatal: ' . $eCnab->getMessage());
                        }
                        
                        logError('Arquivo CNAB gerado na cobrança automática', [
                            'arquivo' => $arquivoCnab,
                            'banco_id' => $banco_id,
                            'codigo_banco' => $codigoBanco,
                            'total_titulos' => count($titulosCompletos)
                        ]);
                        
                    } catch (Exception $e) {
                        // Extrair mensagem de erro mais clara
                        $mensagemErro = $e->getMessage();
                        
                        // Melhorar mensagens específicas
                        if (strpos($mensagemErro, 'Não foi possível criar o arquivo') !== false) {
                            // Extrair o caminho do erro
                            if (preg_match('/Não foi possível criar o arquivo: (.+)/', $mensagemErro, $matches)) {
                                $caminhoArquivo = $matches[1];
                                $diretorio = dirname($caminhoArquivo);
                                
                                // Verificar se o diretório existe
                                if (!is_dir($diretorio)) {
                                    $mensagemErro = "Diretório não encontrado: {$diretorio}. Verifique se o caminho de remessa está correto no cadastro do banco.";
                                } elseif (!is_writable($diretorio)) {
                                    $mensagemErro = "Sem permissão para criar arquivo no diretório: {$diretorio}. Verifique as permissões do diretório.";
                                } else {
                                    $mensagemErro = "Não foi possível criar o arquivo CNAB no caminho: {$caminhoArquivo}. Verifique as permissões do diretório.";
                                }
                            }
                        } elseif (strpos($mensagemErro, 'Não foi possível criar o diretório') !== false) {
                            $mensagemErro = "Não foi possível criar o diretório de remessa. Verifique o caminho configurado no cadastro do banco e as permissões do sistema.";
                        } elseif (strpos($mensagemErro, 'Diretório não possui permissão') !== false) {
                            $mensagemErro = "O diretório de remessa não possui permissão de escrita. Verifique as permissões do diretório.";
                        }
                        
                        $erros[] = $mensagemErro;
                        logError('Erro ao gerar CNAB na cobrança automática', [
                            'error' => $e->getMessage(),
                            'mensagem_melhorada' => $mensagemErro,
                            'banco_id' => $banco_id,
                            'caminho_remessa' => $caminhoRemessa ?? null,
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Não interromper o processamento, apenas registrar o erro
                    } catch (Error $e) {
                        $mensagemErro = "Erro fatal ao gerar arquivo CNAB: " . $e->getMessage();
                        $erros[] = $mensagemErro;
                        logError('Erro fatal ao gerar CNAB na cobrança automática', [
                            'error' => $e->getMessage(),
                            'banco_id' => $banco_id,
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                }
                
                // Processar cada título
                foreach ($titulos as $titulo) {
                    $titulo_id = isset($titulo['id']) ? (int)$titulo['id'] : null;
                    
                    if (!$titulo_id) {
                        $erros[] = 'ID do título inválido';
                        continue;
                    }
                    
                    // Marcar título como processado (sempre marcar como ENVIADO se CNAB foi gerado)
                    $situacao = ($arquivoCnab) ? 'ENVIADO' : 'PROCESSADO';
                    $stmtUpdate = $pdo->prepare("
                        UPDATE cobranca 
                        SET dataenvio = CURRENT_DATE,
                            situacao = :situacao
                        WHERE id = :id
                    ");
                    $stmtUpdate->bindParam(':id', $titulo_id, PDO::PARAM_INT);
                    $stmtUpdate->bindValue(':situacao', $situacao);
                    $stmtUpdate->execute();
                    
                    logError('Título processado na cobrança automática', [
                        'titulo_id' => $titulo_id,
                        'empreendimento_id' => $empreendimento_id,
                        'situacao' => $situacao,
                        'arquivo_cnab' => $arquivoCnab ? basename($arquivoCnab) : null
                    ]);
                    
                    $processados++;
                }
                
                // Commit da transação
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
                    $mensagem .= "\n\n💡 Verifique:";
                    $mensagem .= "\n   • Se o caminho de remessa está correto no cadastro do banco";
                    $mensagem .= "\n   • Se o diretório existe e possui permissão de escrita";
                    $mensagem .= "\n   • Se há espaço suficiente em disco";
                }
                
                $responseData = [
                    'processados' => $processados,
                    'total' => count($titulos),
                    'erros' => $erros
                ];
                
                if ($arquivoCnab) {
                    $responseData['arquivo_cnab'] = basename($arquivoCnab);
                    $responseData['caminho_cnab'] = $arquivoCnab;
                    $responseData['remessa_gerada'] = true;
                } else {
                    $responseData['remessa_gerada'] = false;
                }
                
                jsonResponse(true, $mensagem, $responseData);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                // Limpar qualquer output antes de enviar erro
                ob_clean();
                logError('Erro ao processar cobrança automática', [
                    'action' => $action,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'empreendimento_id' => $empreendimento_id ?? null
                ]);
                jsonResponse(false, 'Erro ao processar títulos: ' . $e->getMessage());
            } catch (Error $e) {
                $pdo->rollBack();
                // Limpar qualquer output antes de enviar erro
                ob_clean();
                logError('Erro fatal ao processar cobrança automática', [
                    'action' => $action,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'empreendimento_id' => $empreendimento_id ?? null
                ]);
                jsonResponse(false, 'Erro fatal ao processar títulos: ' . $e->getMessage());
            }
            break;
            
        default:
            jsonResponse(false, 'Ação não reconhecida.');
            break;
    }
    
} catch (PDOException $e) {
    // Limpar qualquer output antes de enviar erro
    ob_clean();
    logError('Erro PDO na cobrança automática', [
        'action' => $action,
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'sql_state' => isset($e->errorInfo[0]) ? $e->errorInfo[0] : null,
        'driver_code' => isset($e->errorInfo[1]) ? $e->errorInfo[1] : null,
        'driver_message' => isset($e->errorInfo[2]) ? $e->errorInfo[2] : null
    ], $e);
    jsonResponse(false, 'Erro ao processar: ' . $e->getMessage());
} catch (Exception $e) {
    // Limpar qualquer output antes de enviar erro
    ob_clean();
    logError('Erro geral na cobrança automática', [
        'action' => $action,
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'class' => get_class($e)
    ], $e);
    jsonResponse(false, 'Erro ao processar: ' . $e->getMessage());
} catch (Error $e) {
    // Limpar qualquer output antes de enviar erro
    ob_clean();
    logError('Erro fatal na cobrança automática', [
        'action' => $action,
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'class' => get_class($e)
    ], $e);
    jsonResponse(false, 'Erro fatal ao processar: ' . $e->getMessage());
}

