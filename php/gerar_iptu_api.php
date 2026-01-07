<?php
// Iniciar output buffering para evitar saída prematura
ob_start();

// Desabilitar exibição de erros na saída
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../config/logger.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Acesso não autorizado. Faça login novamente.'
    ]);
    exit;
}

// Limpar buffer antes de enviar JSON
ob_clean();
header('Content-Type: application/json; charset=utf-8');

function jsonResponseIptu($sucesso, $mensagem, $extra = []) {
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

try {
    $pdo = getConnection();

    switch ($action) {
        case 'list':
            // Listar parcelas individuais com filtros (empreendimento_id, modulo_id, contrato, ano_referencia)
            try {
                $empreendimento_id = isset($_GET['empreendimento_id']) && $_GET['empreendimento_id'] !== '' ? (int)$_GET['empreendimento_id'] : null;
                $modulo_id = isset($_GET['modulo_id']) && $_GET['modulo_id'] !== '' ? (int)$_GET['modulo_id'] : null;
                $contrato = isset($_GET['contrato']) ? trim($_GET['contrato']) : null;
                $ano_referencia = isset($_GET['ano_referencia']) && $_GET['ano_referencia'] !== '' ? (int)$_GET['ano_referencia'] : null;

                // Verificar se todos os filtros foram informados
                if (!$empreendimento_id || !$modulo_id || !$contrato) {
                    jsonResponseIptu(true, 'Informe Empreendimento, Módulo e Contrato para visualizar os registros.', ['cobrancas' => []]);
                    break;
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

                $sql = "
                    SELECT 
                        c.id,
                        c.empreendimento_id,
                        e.nome AS empreendimento_nome,
                        c.modulo_id,
                        m.nome AS modulo_nome,
                        c.contrato,
                        c.cpf_cnpj,
                        c.cliente_nome,
                        c.parcelamento,
                        c.valor_mensal,
                        c.datavencimento,
                        c.pago,
                        c.usuario,
                        c.situacao,
                        c.titulo,
                        c.data_criacao";
                
                if ($colunaAnoRefExiste) {
                    $sql .= ", c.ano_referencia";
                }
                
                $sql .= "
                    FROM cobranca c
                    LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
                    LEFT JOIN modulos m ON m.id = c.modulo_id
                    WHERE c.empreendimento_id = :empreendimento_id
                      AND c.modulo_id = :modulo_id
                      AND c.contrato = :contrato";
                
                if ($ano_referencia && $colunaAnoRefExiste) {
                    $sql .= " AND c.ano_referencia = :ano_referencia";
                }
                
                $sql .= " ORDER BY c.datavencimento ASC, c.parcelamento ASC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
                $stmt->bindParam(':modulo_id', $modulo_id, PDO::PARAM_INT);
                $stmt->bindParam(':contrato', $contrato, PDO::PARAM_STR);
                if ($ano_referencia && $colunaAnoRefExiste) {
                    $stmt->bindParam(':ano_referencia', $ano_referencia, PDO::PARAM_INT);
                }
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Garantir que datavencimento seja retornada no formato YYYY-MM-DD (sem timezone)
                foreach ($rows as &$row) {
                    if (isset($row['datavencimento']) && $row['datavencimento']) {
                        // Se for string, garantir formato YYYY-MM-DD (remover hora se houver)
                        $dataVenc = $row['datavencimento'];
                        if (is_string($dataVenc)) {
                            if (strpos($dataVenc, ' ') !== false) {
                                $row['datavencimento'] = substr($dataVenc, 0, 10);
                            } elseif (strpos($dataVenc, 'T') !== false) {
                                $row['datavencimento'] = substr($dataVenc, 0, 10);
                            }
                        }
                    }
                }
                unset($row); // Limpar referência
                
                jsonResponseIptu(true, 'Lista carregada com sucesso.', ['cobrancas' => $rows]);
            } catch (PDOException $e) {
                logError('Erro ao listar cobranças', [], $e);
                jsonResponseIptu(false, 'Erro ao listar cobranças: ' . $e->getMessage());
            }
            break;

        case 'verificar-parcelas':
            // Verificar se já existem parcelas geradas para o contrato/ano_referencia
            try {
                $empreendimento_id = isset($_GET['empreendimento_id']) && $_GET['empreendimento_id'] !== '' ? (int)$_GET['empreendimento_id'] : null;
                $modulo_id = isset($_GET['modulo_id']) && $_GET['modulo_id'] !== '' ? (int)$_GET['modulo_id'] : null;
                $contrato = isset($_GET['contrato']) ? trim($_GET['contrato']) : null;
                $ano_referencia = isset($_GET['ano_referencia']) && $_GET['ano_referencia'] !== '' ? (int)$_GET['ano_referencia'] : null;

                // Verificar se todos os filtros foram informados (ano_referencia é obrigatório)
                if (!$empreendimento_id || !$modulo_id || !$contrato || !$ano_referencia) {
                    jsonResponseIptu(false, 'Informe Ano Referência, Empreendimento, Módulo e Contrato para verificar parcelas.');
                    break;
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

                // Buscar as parcelas existentes (não apenas contar)
                $sql = "
                    SELECT 
                        c.id,
                        c.empreendimento_id,
                        e.nome AS empreendimento_nome,
                        c.modulo_id,
                        m.nome AS modulo_nome,
                        c.contrato,
                        c.cpf_cnpj,
                        c.cliente_nome,
                        c.parcelamento,
                        c.valor_mensal,
                        c.datavencimento,
                        c.pago,
                        c.usuario,
                        c.situacao,
                        c.titulo,
                        c.data_criacao";
                
                if ($colunaAnoRefExiste) {
                    $sql .= ", c.ano_referencia";
                }
                
                $sql .= "
                    FROM cobranca c
                    LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
                    LEFT JOIN modulos m ON m.id = c.modulo_id
                    WHERE c.empreendimento_id = :empreendimento_id
                      AND c.modulo_id = :modulo_id
                      AND c.contrato = :contrato";
                
                if ($colunaAnoRefExiste) {
                    $sql .= " AND c.ano_referencia = :ano_referencia";
                }
                
                $sql .= " ORDER BY c.datavencimento ASC, c.parcelamento ASC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
                $stmt->bindParam(':modulo_id', $modulo_id, PDO::PARAM_INT);
                $stmt->bindParam(':contrato', $contrato, PDO::PARAM_STR);
                if ($colunaAnoRefExiste) {
                    $stmt->bindParam(':ano_referencia', $ano_referencia, PDO::PARAM_INT);
                }
                $stmt->execute();
                $parcelas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Garantir que datavencimento seja retornada no formato YYYY-MM-DD (sem timezone)
                foreach ($parcelas as &$parcela) {
                    if (isset($parcela['datavencimento']) && $parcela['datavencimento']) {
                        // Se for string, garantir formato YYYY-MM-DD (remover hora se houver)
                        $dataVenc = $parcela['datavencimento'];
                        if (is_string($dataVenc)) {
                            if (strpos($dataVenc, ' ') !== false) {
                                $parcela['datavencimento'] = substr($dataVenc, 0, 10);
                            } elseif (strpos($dataVenc, 'T') !== false) {
                                $parcela['datavencimento'] = substr($dataVenc, 0, 10);
                            }
                        }
                    }
                }
                unset($parcela); // Limpar referência
                
                $total = count($parcelas);
                
                jsonResponseIptu(true, $total > 0 ? 'Parcelas já geradas para este contrato.' : 'Nenhuma parcela encontrada.', [
                    'existe' => $total > 0,
                    'total' => $total,
                    'parcelas' => $parcelas
                ]);
            } catch (PDOException $e) {
                logError('Erro ao verificar parcelas', [], $e);
                jsonResponseIptu(false, 'Erro ao verificar parcelas: ' . $e->getMessage());
            }
            break;

        case 'get':
            // Buscar parcelas de um contrato específico
            $empreendimento_id = isset($_GET['empreendimento_id']) ? (int)$_GET['empreendimento_id'] : 0;
            $modulo_id = isset($_GET['modulo_id']) ? (int)$_GET['modulo_id'] : 0;
            $contrato = trim($_GET['contrato'] ?? '');
            
            if ($empreendimento_id <= 0 || $modulo_id <= 0 || $contrato === '') {
                jsonResponseIptu(false, 'Parâmetros inválidos.');
            }

            $stmt = $pdo->prepare("
                SELECT c.*,
                       e.nome AS empreendimento_nome,
                       m.nome AS modulo_nome
                FROM cobranca c
                LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
                LEFT JOIN modulos m ON m.id = c.modulo_id
                WHERE c.empreendimento_id = :empreendimento_id 
                  AND c.modulo_id = :modulo_id 
                  AND c.contrato = :contrato
                ORDER BY c.datavencimento ASC, c.parcelamento ASC
            ");
            $stmt->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
            $stmt->bindParam(':modulo_id', $modulo_id, PDO::PARAM_INT);
            $stmt->bindParam(':contrato', $contrato, PDO::PARAM_STR);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Garantir que datavencimento seja retornada no formato YYYY-MM-DD (sem timezone)
            foreach ($rows as &$row) {
                if (isset($row['datavencimento']) && $row['datavencimento']) {
                    // Se for string, garantir formato YYYY-MM-DD (remover hora se houver)
                    $dataVenc = $row['datavencimento'];
                    if (is_string($dataVenc)) {
                        if (strpos($dataVenc, ' ') !== false) {
                            $row['datavencimento'] = substr($dataVenc, 0, 10);
                        } elseif (strpos($dataVenc, 'T') !== false) {
                            $row['datavencimento'] = substr($dataVenc, 0, 10);
                        }
                    }
                }
            }
            unset($row); // Limpar referência

            if (empty($rows)) {
                jsonResponseIptu(false, 'Nenhuma parcela encontrada para este contrato.');
            }

            jsonResponseIptu(true, 'Parcelas carregadas com sucesso.', ['parcelas' => $rows]);
            break;

        case 'create':
            $empreendimento_id = isset($_POST['empreendimento_id']) && $_POST['empreendimento_id'] !== '' ? (int)$_POST['empreendimento_id'] : null;
            $modulo_id = isset($_POST['modulo_id']) && $_POST['modulo_id'] !== '' ? (int)$_POST['modulo_id'] : null;
            $contrato_codigo = trim($_POST['contrato_codigo'] ?? '');
            $contrato_descricao = trim($_POST['contrato_descricao'] ?? '');
            $ano_referencia = isset($_POST['ano_referencia']) && $_POST['ano_referencia'] !== '' ? (int)$_POST['ano_referencia'] : null;
            $valor_total_iptu = isset($_POST['valor_total_iptu']) && $_POST['valor_total_iptu'] !== '' ? 
                str_replace(',', '.', str_replace('.', '', $_POST['valor_total_iptu'])) : null;
            $parcelamento_quantidade = isset($_POST['parcelamento_quantidade']) && $_POST['parcelamento_quantidade'] !== '' ? (int)$_POST['parcelamento_quantidade'] : null;
            $parcelamento_tipo = trim($_POST['parcelamento_tipo'] ?? '');
            $primeira_vencimento = isset($_POST['primeira_vencimento']) && $_POST['primeira_vencimento'] !== '' ? $_POST['primeira_vencimento'] : null;
            $observacoes = trim($_POST['observacoes'] ?? '');
            $ativo = isset($_POST['ativo']) && $_POST['ativo'] === '1';

            // Não precisa mais inserir na tabela gerar_iptu, apenas na cobranca

            // Buscar dados do contrato para inserir na tabela cobranca
            if ($empreendimento_id && $modulo_id && $contrato_codigo && $parcelamento_quantidade && $parcelamento_quantidade > 0) {
                try {
                    $stmtContrato = $pdo->prepare("
                        SELECT c.*, cli.cpf_cnpj, cli.nome as cliente_nome
                        FROM contratos c
                        LEFT JOIN clientes cli ON c.cliente_id = cli.id
                        WHERE c.empreendimento_id = :empreendimento_id 
                          AND c.modulo_id = :modulo_id 
                          AND c.contrato = :contrato
                    ");
                    $stmtContrato->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
                    $stmtContrato->bindParam(':modulo_id', $modulo_id, PDO::PARAM_INT);
                    $stmtContrato->bindParam(':contrato', $contrato_codigo, PDO::PARAM_STR);
                    $stmtContrato->execute();
                    $contrato = $stmtContrato->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $eContrato) {
                    logError('Erro ao buscar contrato para cobrança', [
                        'empreendimento_id' => $empreendimento_id,
                        'modulo_id' => $modulo_id,
                        'contrato_codigo' => $contrato_codigo
                    ], $eContrato);
                    $contrato = null; // Continuar sem inserir cobrança se houver erro
                }

                if (!$contrato) {
                    logError('Contrato não encontrado para inserir cobrança', [
                        'empreendimento_id' => $empreendimento_id,
                        'modulo_id' => $modulo_id,
                        'contrato_codigo' => $contrato_codigo
                    ]);
                }
                
                if ($contrato && $primeira_vencimento && $parcelamento_quantidade > 0) {
                    // Verificar se já existem cobranças para este contrato
                    try {
                        $stmtVerificar = $pdo->prepare("
                            SELECT COUNT(*) as total
                            FROM cobranca
                            WHERE empreendimento_id = :empreendimento_id 
                              AND modulo_id = :modulo_id 
                              AND contrato = :contrato
                        ");
                        $stmtVerificar->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
                        $stmtVerificar->bindParam(':modulo_id', $modulo_id, PDO::PARAM_INT);
                        $stmtVerificar->bindParam(':contrato', $contrato_codigo, PDO::PARAM_STR);
                        $stmtVerificar->execute();
                        $existentes = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
                        
                        if ($existentes && $existentes['total'] > 0) {
                            jsonResponseIptu(false, 'Já existem cobranças cadastradas para este contrato. Delete as existentes antes de criar novas.');
                        }
                    } catch (PDOException $eVerificar) {
                        logError('Erro ao verificar cobranças existentes', [
                            'empreendimento_id' => $empreendimento_id,
                            'modulo_id' => $modulo_id,
                            'contrato_codigo' => $contrato_codigo
                        ], $eVerificar);
                        // Continuar mesmo com erro na verificação
                    }
                    
                    try {
                        // Converter valor da parcela (remover formatação)
                        $valorParcelaStr = str_replace(',', '.', str_replace('.', '', $parcelamento_tipo));
                        $valorParcela = $valorParcelaStr !== '' ? (float)$valorParcelaStr : 0;

                        // Preparar data de vencimento inicial
                        if (empty($primeira_vencimento)) {
                            throw new Exception('Data de primeira vencimento não informada.');
                        }
                        $dataVencimento = new DateTime($primeira_vencimento);
                    } catch (Exception $eData) {
                        logError('Erro ao processar data de vencimento', [
                            'primeira_vencimento' => $primeira_vencimento,
                            'parcelamento_quantidade' => $parcelamento_quantidade
                        ], $eData);
                        // Continuar sem inserir cobrança se houver erro na data
                        $dataVencimento = null;
                    }

                    // Obter usuário logado
                    $usuarioLogado = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
                    
                    // Verificar se a coluna dataenvio existe
                    $colunaDataenvioExiste = false;
                    try {
                        $stmtCheck = $pdo->query("
                            SELECT COUNT(*) as existe
                            FROM information_schema.columns
                            WHERE table_schema = 'public'
                              AND table_name = 'cobranca'
                              AND column_name = 'dataenvio'
                        ");
                        $check = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                        $colunaDataenvioExiste = ($check && $check['existe'] > 0);
                    } catch (Exception $e) {
                        // Se houver erro ao verificar, assumir que não existe
                        $colunaDataenvioExiste = false;
                    }
                    
                    // Verificar se a coluna ano_referencia existe
                    $colunaAnoReferenciaExiste = false;
                    try {
                        $stmtCheckAnoRef = $pdo->query("
                            SELECT COUNT(*) as existe
                            FROM information_schema.columns
                            WHERE table_schema = 'public'
                              AND table_name = 'cobranca'
                              AND column_name = 'ano_referencia'
                        ");
                        $checkAnoRef = $stmtCheckAnoRef->fetch(PDO::FETCH_ASSOC);
                        $colunaAnoReferenciaExiste = ($checkAnoRef && $checkAnoRef['existe'] > 0);
                    } catch (Exception $e) {
                        $colunaAnoReferenciaExiste = false;
                    }
                    
                    // Inserir registros na tabela cobranca baseado no parcelamento
                    if ($colunaDataenvioExiste && $colunaAnoReferenciaExiste) {
                        $stmtCobranca = $pdo->prepare("
                            INSERT INTO cobranca (
                                empreendimento_id, modulo_id, contrato, cpf_cnpj, cliente_nome,
                                area, metragem, vrm2, inscricao, valor_venal, aliquota,
                                tx_coleta_lixo, datavencimento, desconto_vista, valor_anual,
                                parcelamento, valor_mensal, observacao, situacao, pago, usuario, dataenvio, ano_referencia
                            ) VALUES (
                                :empreendimento_id, :modulo_id, :contrato, :cpf_cnpj, :cliente_nome,
                                :area, :metragem, :vrm2, :inscricao, :valor_venal, :aliquota,
                                :tx_coleta_lixo, :dia_vencimento, :desconto_vista, :valor_anual,
                                :parcelamento, :valor_mensal, :observacao, :situacao, :pago, :usuario, :dataenvio, :ano_referencia
                            )
                        ");
                    } elseif ($colunaDataenvioExiste) {
                        $stmtCobranca = $pdo->prepare("
                            INSERT INTO cobranca (
                                empreendimento_id, modulo_id, contrato, cpf_cnpj, cliente_nome,
                                area, metragem, vrm2, inscricao, valor_venal, aliquota,
                                tx_coleta_lixo, datavencimento, desconto_vista, valor_anual,
                                parcelamento, valor_mensal, observacao, situacao, pago, usuario, dataenvio
                            ) VALUES (
                                :empreendimento_id, :modulo_id, :contrato, :cpf_cnpj, :cliente_nome,
                                :area, :metragem, :vrm2, :inscricao, :valor_venal, :aliquota,
                                :tx_coleta_lixo, :dia_vencimento, :desconto_vista, :valor_anual,
                                :parcelamento, :valor_mensal, :observacao, :situacao, :pago, :usuario, :dataenvio
                            )
                        ");
                    } elseif ($colunaAnoReferenciaExiste) {
                        $stmtCobranca = $pdo->prepare("
                            INSERT INTO cobranca (
                                empreendimento_id, modulo_id, contrato, cpf_cnpj, cliente_nome,
                                area, metragem, vrm2, inscricao, valor_venal, aliquota,
                                tx_coleta_lixo, datavencimento, desconto_vista, valor_anual,
                                parcelamento, valor_mensal, observacao, situacao, pago, usuario, ano_referencia
                            ) VALUES (
                                :empreendimento_id, :modulo_id, :contrato, :cpf_cnpj, :cliente_nome,
                                :area, :metragem, :vrm2, :inscricao, :valor_venal, :aliquota,
                                :tx_coleta_lixo, :dia_vencimento, :desconto_vista, :valor_anual,
                                :parcelamento, :valor_mensal, :observacao, :situacao, :pago, :usuario, :ano_referencia
                            )
                        ");
                    } else {
                        $stmtCobranca = $pdo->prepare("
                            INSERT INTO cobranca (
                                empreendimento_id, modulo_id, contrato, cpf_cnpj, cliente_nome,
                                area, metragem, vrm2, inscricao, valor_venal, aliquota,
                                tx_coleta_lixo, datavencimento, desconto_vista, valor_anual,
                                parcelamento, valor_mensal, observacao, situacao, pago, usuario
                            ) VALUES (
                                :empreendimento_id, :modulo_id, :contrato, :cpf_cnpj, :cliente_nome,
                                :area, :metragem, :vrm2, :inscricao, :valor_venal, :aliquota,
                                :tx_coleta_lixo, :dia_vencimento, :desconto_vista, :valor_anual,
                                :parcelamento, :valor_mensal, :observacao, :situacao, :pago, :usuario
                            )
                        ");
                    }

                    if ($dataVencimento !== null) {
                        try {
                            // Extrair o dia fixo da data inicial
                            $diaFixo = (int)$dataVencimento->format('d');
                            
                            for ($i = 1; $i <= $parcelamento_quantidade; $i++) {
                                // Calcular data de vencimento seguindo as regras:
                                // 1. Dia Fixo: O dia de vencimento deve ser sempre o mesmo da data inicial
                                // 2. Regra de Meses Curtos: Se o dia for 29, 30 ou 31 e o mês não possuir esse dia, usar o último dia do mês
                                // 3. Progressão Mensal: Avançar exatamente um mês por vez
                                
                                $dataVenc = clone $dataVencimento;
                                
                                // Avançar (i - 1) meses a partir da data inicial
                                // Exemplo: parcela 1 = 0 meses, parcela 2 = 1 mês, parcela 3 = 2 meses
                                if ($i > 1) {
                                    $dataVenc->modify('+' . ($i - 1) . ' month');
                                }
                                
                                // Obter ano e mês após o avanço
                                    $ano = (int)$dataVenc->format('Y');
                                    $mes = (int)$dataVenc->format('m');
                                
                                // Verificar o último dia do mês atual
                                    $ultimoDiaMes = (int)$dataVenc->format('t');
                                
                                // Aplicar regra de meses curtos: se o dia fixo não existe no mês, usar o último dia
                                if ($diaFixo > $ultimoDiaMes) {
                                    // Dia fixo não existe neste mês (ex: 31 em fevereiro)
                                    // Usar o último dia do mês
                                    $diaFinal = $ultimoDiaMes;
                                } else {
                                    // Dia fixo existe no mês, usar o dia fixo
                                    $diaFinal = $diaFixo;
                                }
                                
                                // Definir a data final
                                $dataVenc->setDate($ano, $mes, $diaFinal);
                                
                                $dataVencStr = $dataVenc->format('Y-m-d');

                            // Preparar valores, convertendo vazios para null
                            $cpfCnpj = !empty($contrato['cpf_cnpj']) ? $contrato['cpf_cnpj'] : null;
                            $clienteNome = !empty($contrato['cliente_nome']) ? $contrato['cliente_nome'] : null;
                            $area = !empty($contrato['area']) ? $contrato['area'] : null;
                            $inscricao = !empty($contrato['inscricao']) ? $contrato['inscricao'] : null;
                            $situacao = !empty($contrato['situacao']) ? $contrato['situacao'] : null;
                            
                            $metragem = isset($contrato['metragem']) && $contrato['metragem'] !== '' && $contrato['metragem'] !== null ? (float)$contrato['metragem'] : null;
                            $vrm2 = isset($contrato['vrm2']) && $contrato['vrm2'] !== '' && $contrato['vrm2'] !== null ? (float)$contrato['vrm2'] : null;
                            $valorVenal = isset($contrato['valor_venal']) && $contrato['valor_venal'] !== '' && $contrato['valor_venal'] !== null ? (float)$contrato['valor_venal'] : null;
                            $aliquota = isset($contrato['aliquota']) && $contrato['aliquota'] !== '' && $contrato['aliquota'] !== null ? (float)$contrato['aliquota'] : null;
                            $txColetaLixo = isset($contrato['tx_coleta_lixo']) && $contrato['tx_coleta_lixo'] !== '' && $contrato['tx_coleta_lixo'] !== null ? (float)$contrato['tx_coleta_lixo'] : null;
                            // Verificar se o campo é desconto_a_vista ou desconto_vista
                            $descontoVista = null;
                            if (isset($contrato['desconto_a_vista']) && $contrato['desconto_a_vista'] !== '' && $contrato['desconto_a_vista'] !== null) {
                                $descontoVista = (float)$contrato['desconto_a_vista'];
                            } elseif (isset($contrato['desconto_vista']) && $contrato['desconto_vista'] !== '' && $contrato['desconto_vista'] !== null) {
                                $descontoVista = (float)$contrato['desconto_vista'];
                            }
                            $valorAnual = isset($contrato['valor_anual']) && $contrato['valor_anual'] !== '' && $contrato['valor_anual'] !== null ? (float)$contrato['valor_anual'] : null;
                            
                            // dia_vencimento agora é DATE, usar a data calculada
                            $diaVencimento = $dataVencStr; // Usar a data de vencimento calculada

                            $stmtCobranca->bindValue(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
                            $stmtCobranca->bindValue(':modulo_id', $modulo_id, PDO::PARAM_INT);
                            $stmtCobranca->bindValue(':contrato', $contrato_codigo, PDO::PARAM_STR);
                            $stmtCobranca->bindValue(':cpf_cnpj', $cpfCnpj, $cpfCnpj !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':cliente_nome', $clienteNome, $clienteNome !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':area', $area, $area !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':metragem', $metragem, $metragem !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':vrm2', $vrm2, $vrm2 !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':inscricao', $inscricao, $inscricao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':valor_venal', $valorVenal, $valorVenal !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':aliquota', $aliquota, $aliquota !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':tx_coleta_lixo', $txColetaLixo, $txColetaLixo !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':dia_vencimento', $diaVencimento, $diaVencimento !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':desconto_vista', $descontoVista, $descontoVista !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':valor_anual', $valorAnual, $valorAnual !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':parcelamento', $i, PDO::PARAM_INT); // Número da parcela
                            $stmtCobranca->bindValue(':valor_mensal', $valorParcela > 0 ? $valorParcela : null, $valorParcela > 0 ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            // Usar obs do contrato se disponível, senão usar observacoes do gerar_iptu
                            $observacaoFinal = !empty($contrato['obs']) ? $contrato['obs'] : ($observacoes !== '' ? $observacoes : null);
                            $stmtCobranca->bindValue(':observacao', $observacaoFinal, PDO::PARAM_STR);
                            $stmtCobranca->bindValue(':situacao', $situacao, $situacao !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            $stmtCobranca->bindValue(':pago', 'N', PDO::PARAM_STR); // Sempre inicia como não pago
                            $stmtCobranca->bindValue(':usuario', $usuarioLogado, $usuarioLogado !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
                            // dataenvio pode ser preenchida posteriormente, inicialmente NULL (apenas se a coluna existir)
                            if ($colunaDataenvioExiste) {
                                $stmtCobranca->bindValue(':dataenvio', null, PDO::PARAM_NULL);
                            }
                            // ano_referencia (apenas se a coluna existir)
                            if ($colunaAnoReferenciaExiste) {
                                $stmtCobranca->bindValue(':ano_referencia', $ano_referencia, $ano_referencia !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                            }

                            $stmtCobranca->execute();
                            }
                        } catch (PDOException $eCobranca) {
                            $errorInfo = $eCobranca->errorInfo ?? [];
                            logError('Erro ao inserir registros na tabela cobranca', [
                                'empreendimento_id' => $empreendimento_id,
                                'modulo_id' => $modulo_id,
                                'contrato_codigo' => $contrato_codigo,
                                'parcelamento_quantidade' => $parcelamento_quantidade,
                                'parcela_atual' => $i,
                                'valor_parcela' => $valorParcela,
                                'sql_error_info' => $errorInfo
                            ], $eCobranca);
                            // Relançar apenas se for um erro crítico de constraint
                            if (strpos($eCobranca->getMessage(), 'foreign key') !== false || 
                                strpos($eCobranca->getMessage(), 'constraint') !== false ||
                                strpos($eCobranca->getMessage(), 'violates') !== false) {
                                throw $eCobranca; // Relançar erros de constraint
                            }
                            // Para outros erros, apenas logar e continuar
                        }
                    }
                }
            }

            // Mensagem de sucesso
            $mensagem = 'Registro criado com sucesso.';
            if ($parcelamento_quantidade > 0) {
                if ($contrato) {
                    $mensagem .= ' ' . $parcelamento_quantidade . ' parcela(s) inserida(s) na tabela de cobrança.';
                } else {
                    $mensagem .= ' Aviso: Contrato não encontrado, parcelas não foram inseridas na tabela de cobrança.';
                }
            }
            jsonResponseIptu(true, $mensagem);
            break;

        case 'update':
            // Atualizar uma parcela específica na tabela cobranca
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                jsonResponseIptu(false, 'ID inválido.');
            }

            $valor_mensal = isset($_POST['valor_mensal']) && $_POST['valor_mensal'] !== '' ? 
                str_replace(',', '.', str_replace('.', '', $_POST['valor_mensal'])) : null;
            $dia_vencimento = isset($_POST['dia_vencimento']) && $_POST['dia_vencimento'] !== '' ? $_POST['dia_vencimento'] : null;
            $situacao = trim($_POST['situacao'] ?? '');
            $observacao = trim($_POST['observacao'] ?? '');

            if ($valor_mensal !== null) {
                $valor_mensal = (float)$valor_mensal;
            }

            $stmt = $pdo->prepare("
                UPDATE cobranca SET
                    valor_mensal = :valor_mensal,
                    datavencimento = :dia_vencimento,
                    situacao = :situacao,
                    observacao = :observacao
                WHERE id = :id
            ");

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':valor_mensal', $valor_mensal, $valor_mensal !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':dia_vencimento', $dia_vencimento, $dia_vencimento !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':situacao', $situacao !== '' ? $situacao : null, PDO::PARAM_STR);
            $stmt->bindValue(':observacao', $observacao !== '' ? $observacao : null, PDO::PARAM_STR);

            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                jsonResponseIptu(false, 'Parcela não encontrada.');
            }

            jsonResponseIptu(true, 'Parcela atualizada com sucesso.');
            break;

        case 'delete':
            // Deletar uma parcela específica ou todas as parcelas de um contrato
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $empreendimento_id = isset($_POST['empreendimento_id']) && $_POST['empreendimento_id'] !== '' ? (int)$_POST['empreendimento_id'] : null;
            $modulo_id = isset($_POST['modulo_id']) && $_POST['modulo_id'] !== '' ? (int)$_POST['modulo_id'] : null;
            $contrato = trim($_POST['contrato'] ?? '');

            if ($id > 0) {
                // Deletar parcela específica
                $stmt = $pdo->prepare("DELETE FROM cobranca WHERE id = :id");
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->rowCount() === 0) {
                    jsonResponseIptu(false, 'Parcela não encontrada.');
                }

                jsonResponseIptu(true, 'Parcela excluída com sucesso.');
            } elseif ($empreendimento_id && $modulo_id && $contrato !== '') {
                // Deletar todas as parcelas de um contrato
                $stmt = $pdo->prepare("
                    DELETE FROM cobranca 
                    WHERE empreendimento_id = :empreendimento_id 
                      AND modulo_id = :modulo_id 
                      AND contrato = :contrato
                ");
                $stmt->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
                $stmt->bindParam(':modulo_id', $modulo_id, PDO::PARAM_INT);
                $stmt->bindParam(':contrato', $contrato, PDO::PARAM_STR);
                $stmt->execute();

                if ($stmt->rowCount() === 0) {
                    jsonResponseIptu(false, 'Nenhuma parcela encontrada para este contrato.');
                }

                jsonResponseIptu(true, 'Todas as parcelas do contrato foram excluídas com sucesso.');
            } else {
                jsonResponseIptu(false, 'Parâmetros inválidos.');
            }
            break;

        default:
            jsonResponseIptu(false, 'Ação inválida.');
    }
} catch (PDOException $e) {
    logError('Erro PDO ao processar requisição de gerar_iptu', [
        'action' => $action,
        'sql_error_info' => $e->errorInfo ?? []
    ], $e);
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao processar a requisição. Detalhes: ' . $e->getMessage()
    ]);
    exit;
} catch (Exception $e) {
    logError('Erro geral ao processar requisição de gerar_iptu', [
        'action' => $action
    ], $e);
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro ao processar a requisição. Tente novamente.'
    ]);
    exit;
} catch (Error $e) {
    logError('Erro fatal ao processar requisição de gerar_iptu', [
        'action' => $action
    ], $e);
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro fatal ao processar a requisição. Verifique os logs.'
    ]);
    exit;
}


