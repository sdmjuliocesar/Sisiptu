<?php
session_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/logger.php';

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

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'pesquisar';

try {
    $pdo = getConnection();
    
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
    
    // Verificar se a coluna data_vencimento existe
    $colunaDataVencimentoExiste = false;
    try {
        $stmtCheck = $pdo->query("
            SELECT COUNT(*) as existe
            FROM information_schema.columns
            WHERE table_schema = 'public'
              AND table_name = 'cobranca'
              AND column_name = 'data_vencimento'
        ");
        $check = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $colunaDataVencimentoExiste = ($check && $check['existe'] > 0);
    } catch (Exception $e) {
        $colunaDataVencimentoExiste = false;
    }

    switch ($action) {
        case 'pesquisar':
            // Coletar filtros (todos opcionais para consulta)
            $ano_referencia = isset($_GET['ano_referencia']) && $_GET['ano_referencia'] !== '' ? (int)$_GET['ano_referencia'] : null;
            $empreendimento_id = isset($_GET['empreendimento_id']) && $_GET['empreendimento_id'] !== '' ? (int)$_GET['empreendimento_id'] : null;
            $modulo_id = isset($_GET['modulo_id']) && $_GET['modulo_id'] !== '' ? (int)$_GET['modulo_id'] : null;
            $contrato = isset($_GET['contrato']) ? trim($_GET['contrato']) : null;
            $cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : null;
            $data_calculo = isset($_GET['data_calculo']) ? trim($_GET['data_calculo']) : null;
            $filtro_titulo = isset($_GET['filtro_titulo']) ? trim($_GET['filtro_titulo']) : 'todos';
            $ordem = isset($_GET['ordem']) ? trim($_GET['ordem']) : 'vencimento';
            
            // Montar query
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
            
            if ($colunaDataVencimentoExiste) {
                $sql .= ", c.data_vencimento";
            }
            
            $sql .= "
                FROM cobranca c
                LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
                LEFT JOIN modulos m ON m.id = c.modulo_id
                LEFT JOIN bancos b ON b.id = e.banco_id
                WHERE 1=1";
            
            // Aplicar filtro de status de pagamento
            // Por padrão, excluir parcelas pagas (exceto se o filtro for 'pagos')
            if ($filtro_titulo !== 'pagos') {
                $sql .= " AND (c.pago IS NULL OR c.pago = '' OR c.pago = 'N' OR c.pago = 'n')";
            } else {
                // Se o filtro for 'pagos', mostrar apenas parcelas pagas
                $sql .= " AND (c.pago = 'S' OR c.pago = 's')";
            }
            
            // Aplicar filtros opcionais
            if ($empreendimento_id) {
                $sql .= " AND c.empreendimento_id = :empreendimento_id";
            }
            
            if ($modulo_id) {
                $sql .= " AND c.modulo_id = :modulo_id";
            }
            
            if ($contrato) {
                $sql .= " AND c.contrato LIKE :contrato";
            }
            
            if ($cliente) {
                $sql .= " AND c.cliente_nome ILIKE :cliente";
            }
            
            if ($colunaAnoRefExiste && $ano_referencia) {
                $sql .= " AND c.ano_referencia = :ano_referencia";
            }
            
            // Aplicar filtro de título (data de vencimento)
            $hoje = date('Y-m-d');
            if ($filtro_titulo === 'vencidos') {
                $sql .= " AND c.datavencimento < :hoje";
            } elseif ($filtro_titulo === 'a-vencer') {
                $sql .= " AND c.datavencimento >= :hoje";
            }
            // 'todos' e 'pagos' não adicionam filtro de data
            
            // Aplicar ordenação
            switch($ordem) {
                case 'parcela':
                    $sql .= " ORDER BY c.parcelamento ASC, c.datavencimento ASC";
                    break;
                case 'pagamento':
                    // Ordenar por data de pagamento (se existir) ou por pago
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
            
            // Bind dos parâmetros opcionais
            if ($empreendimento_id) {
                $stmt->bindParam(':empreendimento_id', $empreendimento_id, PDO::PARAM_INT);
            }
            
            if ($modulo_id) {
                $stmt->bindParam(':modulo_id', $modulo_id, PDO::PARAM_INT);
            }
            
            if ($contrato) {
                $contratoLike = '%' . $contrato . '%';
                $stmt->bindParam(':contrato', $contratoLike, PDO::PARAM_STR);
            }
            
            if ($cliente) {
                $clienteLike = '%' . $cliente . '%';
                $stmt->bindParam(':cliente', $clienteLike, PDO::PARAM_STR);
            }
            
            if ($colunaAnoRefExiste && $ano_referencia) {
                $stmt->bindParam(':ano_referencia', $ano_referencia, PDO::PARAM_INT);
            }
            
            if ($filtro_titulo === 'vencidos' || $filtro_titulo === 'a-vencer') {
                $stmt->bindParam(':hoje', $hoje, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $cobrancas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Garantir que datavencimento seja retornada no formato YYYY-MM-DD (sem timezone)
            foreach ($cobrancas as &$cobranca) {
                if (isset($cobranca['datavencimento']) && $cobranca['datavencimento']) {
                    // Se for string, garantir formato YYYY-MM-DD (remover hora se houver)
                    $dataVenc = $cobranca['datavencimento'];
                    if (is_string($dataVenc)) {
                        if (strpos($dataVenc, ' ') !== false) {
                            $cobranca['datavencimento'] = substr($dataVenc, 0, 10);
                        } elseif (strpos($dataVenc, 'T') !== false) {
                            $cobranca['datavencimento'] = substr($dataVenc, 0, 10);
                        }
                    }
                }
                // Também formatar data_vencimento se existir
                if (isset($cobranca['data_vencimento']) && $cobranca['data_vencimento']) {
                    $dataVenc = $cobranca['data_vencimento'];
                    if (is_string($dataVenc)) {
                        if (strpos($dataVenc, ' ') !== false) {
                            $cobranca['data_vencimento'] = substr($dataVenc, 0, 10);
                        } elseif (strpos($dataVenc, 'T') !== false) {
                            $cobranca['data_vencimento'] = substr($dataVenc, 0, 10);
                        }
                    }
                }
            }
            unset($cobranca); // Limpar referência
            
            registrarLog('INFO', 'Pesquisa realizada na manutenção de IPTU', [
                'action' => 'pesquisar',
                'ano_referencia' => $ano_referencia,
                'empreendimento_id' => $empreendimento_id,
                'modulo_id' => $modulo_id,
                'contrato' => $contrato,
                'total_encontrado' => count($cobrancas),
                'usuario' => $_SESSION['usuario'] ?? 'N/A'
            ]);
            
            jsonResponse(true, 'Pesquisa realizada com sucesso.', ['cobrancas' => $cobrancas]);
            break;
            
        case 'get':
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($id <= 0) {
                registrarLog('ERRO', 'Tentativa de buscar título com ID inválido - Manutenção IPTU', [
                    'action' => 'get',
                    'id_recebido' => isset($_GET['id']) ? $_GET['id'] : null,
                    'usuario' => $_SESSION['usuario'] ?? 'N/A'
                ]);
                jsonResponse(false, 'ID inválido.');
            }
            
            // Montar query para buscar título
            $sql = "
                SELECT 
                    c.id,
                    c.empreendimento_id,
                    c.modulo_id,
                    c.contrato,
                    c.parcelamento,
                    c.valor_mensal,
                    c.datavencimento,
                    c.situacao,
                    c.pago,
                    c.observacao,
                    c.juros,
                    c.multas,
                    e.banco_id,
                    b.multa_mes,
                    b.juros_mes";
            
            if ($colunaAnoRefExiste) {
                $sql .= ", c.ano_referencia";
            }
            
            if ($colunaDataVencimentoExiste) {
                $sql .= ", c.data_vencimento";
            }
            
            $sql .= "
                FROM cobranca c
                LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
                LEFT JOIN modulos m ON m.id = c.modulo_id
                LEFT JOIN bancos b ON b.id = e.banco_id
                WHERE c.id = :id
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $cobranca = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cobranca) {
                registrarLog('INFO', 'Tentativa de buscar título inexistente - Manutenção IPTU', [
                    'action' => 'get',
                    'id' => $id,
                    'usuario' => $_SESSION['usuario'] ?? 'N/A'
                ]);
                jsonResponse(false, 'Título não encontrado.');
            }
            
            // Garantir que data_vencimento seja retornada no formato YYYY-MM-DD (sem timezone)
            if (isset($cobranca['data_vencimento']) && $cobranca['data_vencimento']) {
                // Se for um objeto DateTime, converter para string YYYY-MM-DD
                if ($cobranca['data_vencimento'] instanceof DateTime) {
                    $cobranca['data_vencimento'] = $cobranca['data_vencimento']->format('Y-m-d');
                } else {
                    // Se for string, garantir formato YYYY-MM-DD (remover hora se houver)
                    $dataVenc = $cobranca['data_vencimento'];
                    if (strpos($dataVenc, ' ') !== false) {
                        $cobranca['data_vencimento'] = substr($dataVenc, 0, 10);
                    } elseif (strpos($dataVenc, 'T') !== false) {
                        $cobranca['data_vencimento'] = substr($dataVenc, 0, 10);
                    }
                }
            }
            
            registrarLog('INFO', 'Título carregado na manutenção de IPTU', [
                'action' => 'get',
                'id' => $id,
                'usuario' => $_SESSION['usuario'] ?? 'N/A'
            ]);
            
            jsonResponse(true, 'Título encontrado.', ['cobranca' => $cobranca]);
            break;
            
        case 'update':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                registrarLog('ERRO', 'Tentativa de atualização com ID inválido - Manutenção IPTU', [
                    'action' => 'update',
                    'id_recebido' => isset($_POST['id']) ? $_POST['id'] : null,
                    'usuario' => $_SESSION['usuario'] ?? 'N/A'
                ]);
                jsonResponse(false, 'ID inválido.');
            }
            
            // Processar dia_vencimento - pode ser DATE (YYYY-MM-DD) ou INTEGER (dia do mês)
            $dia_vencimento = null;
            $data_vencimento = null;
            
            if (isset($_POST['dia_vencimento']) && $_POST['dia_vencimento'] !== '') {
                $dia_vencimento_input = trim($_POST['dia_vencimento']);
                // Verificar se é uma data no formato YYYY-MM-DD
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia_vencimento_input)) {
                    // É uma data completa no formato YYYY-MM-DD
                    // Usar diretamente como string, SEM conversões que podem alterar timezone
                    $dia_vencimento = $dia_vencimento_input;
                    // Se data_vencimento existe, também atualizar esse campo
                    if ($colunaDataVencimentoExiste) {
                        $data_vencimento = $dia_vencimento_input;
                    }
                } elseif (is_numeric($dia_vencimento_input) && $dia_vencimento_input >= 1 && $dia_vencimento_input <= 31) {
                    // É um número (dia do mês), converter para data usando ano_referencia se disponível
                    // Buscar ano_referencia do registro
                    $stmtAno = $pdo->prepare("SELECT ano_referencia FROM cobranca WHERE id = :id");
                    $stmtAno->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmtAno->execute();
                    $resultAno = $stmtAno->fetch(PDO::FETCH_ASSOC);
                    $ano = $resultAno && isset($resultAno['ano_referencia']) ? (int)$resultAno['ano_referencia'] : date('Y');
                    $mes = date('m');
                    $dia = str_pad((int)$dia_vencimento_input, 2, '0', STR_PAD_LEFT);
                    $dia_vencimento = sprintf('%d-%s-%s', $ano, $mes, $dia);
                }
            }
            
            // Processar valor_mensal
            $valor_mensal = null;
            if (isset($_POST['valor_mensal']) && $_POST['valor_mensal'] !== '') {
                $valor_mensal_input = trim($_POST['valor_mensal']);
                
                // O JavaScript já envia com ponto como separador decimal (ex: "208.33")
                // Se contém vírgula, tratar como separador decimal (caso venha direto do formulário)
                if (strpos($valor_mensal_input, ',') !== false) {
                    // Separar parte inteira e decimal
                    $partes = explode(',', $valor_mensal_input);
                    $parteInteira = str_replace('.', '', $partes[0]); // Remove pontos da parte inteira (separadores de milhar)
                    $parteDecimal = isset($partes[1]) ? $partes[1] : '';
                    // Reconstruir: parte inteira + ponto + parte decimal
                    $valor_mensal_input = $parteInteira . '.' . $parteDecimal;
                } else if (strpos($valor_mensal_input, '.') !== false) {
                    // Se tem ponto, verificar se é separador decimal (apenas um ponto) ou separador de milhar (múltiplos pontos)
                    $pontos = substr_count($valor_mensal_input, '.');
                    if ($pontos > 1) {
                        // Múltiplos pontos = separadores de milhar, remover todos exceto o último
                        // Exemplo: "2.208.33" -> "2208.33"
                        $ultimoPonto = strrpos($valor_mensal_input, '.');
                        $parteInteira = str_replace('.', '', substr($valor_mensal_input, 0, $ultimoPonto));
                        $parteDecimal = substr($valor_mensal_input, $ultimoPonto + 1);
                        $valor_mensal_input = $parteInteira . '.' . $parteDecimal;
                    }
                    // Se tem apenas um ponto, manter (é separador decimal)
                }
                
                if (is_numeric($valor_mensal_input)) {
                    $valor_mensal = floatval($valor_mensal_input);
                }
            }
            
            $observacao = isset($_POST['observacao']) ? trim($_POST['observacao']) : null;
            
            // Construir UPDATE dinamicamente
            $updates = [];
            $params = [':id' => $id];
            
            if ($valor_mensal !== null) {
                $updates[] = "valor_mensal = :valor_mensal";
                $params[':valor_mensal'] = $valor_mensal;
            }
            
            // Atualizar data_vencimento (campo DATE) se existir e se foi informada uma data completa
            // Este é o campo principal para armazenar a data completa
            if ($data_vencimento !== null && $colunaDataVencimentoExiste) {
                // Usar TO_DATE com formato explícito 'YYYY-MM-DD' para evitar problemas de timezone
                // TO_DATE garante que a string seja interpretada exatamente como data, sem conversão de timezone
                $updates[] = "data_vencimento = TO_DATE(:data_vencimento, 'YYYY-MM-DD')";
                $params[':data_vencimento'] = $data_vencimento;
            }
            
            // Atualizar dataVencimento (campo renomeado de dia_vencimento)
            // Se for uma data completa (YYYY-MM-DD), usar TO_DATE para garantir que seja salva corretamente
            if ($dia_vencimento !== null) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia_vencimento)) {
                    // É uma data completa - usar TO_DATE para garantir que seja salva corretamente
                    // TO_DATE com formato explícito evita problemas de timezone
                    $updates[] = "datavencimento = TO_DATE(:dia_vencimento, 'YYYY-MM-DD')";
                    $params[':dia_vencimento'] = $dia_vencimento;
                } else {
                    // Não é uma data completa, usar diretamente
                    $updates[] = "datavencimento = :dia_vencimento";
                    $params[':dia_vencimento'] = $dia_vencimento;
                }
            }
            
            if ($observacao !== null) {
                $updates[] = "observacao = :observacao";
                $params[':observacao'] = $observacao;
            }
            
            if (empty($updates)) {
                registrarLog('INFO', 'Tentativa de atualização sem campos - Manutenção IPTU', [
                    'id' => $id,
                    'usuario' => $_SESSION['usuario'] ?? 'N/A'
                ]);
                jsonResponse(false, 'Nenhum campo para atualizar.');
            }
            
            $updates[] = "data_atualizacao = CURRENT_TIMESTAMP";
            
            $sql = "UPDATE cobranca SET " . implode(', ', $updates) . " WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                if ($key === ':valor_mensal' && $value !== null) {
                    // Valor decimal
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                } elseif (($key === ':dia_vencimento' || $key === ':data_vencimento') && $value !== null) {
                    // Se for data, passar como string - o CAST no SQL garante conversão correta
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                } elseif (is_int($value)) {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            
            registrarLog('INFO', 'Título atualizado na manutenção de IPTU', [
                'action' => 'update',
                'id' => $id,
                'valor_mensal' => $valor_mensal,
                'dia_vencimento' => $dia_vencimento,
                'observacao' => $observacao,
                'campos_atualizados' => $updates,
                'usuario' => $_SESSION['usuario'] ?? 'N/A'
            ]);
            
            jsonResponse(true, 'Título atualizado com sucesso.');
            break;
            
        case 'delete':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id <= 0) {
                registrarLog('ERRO', 'Tentativa de excluir título com ID inválido - Manutenção IPTU', [
                    'action' => 'delete',
                    'id_recebido' => isset($_POST['id']) ? $_POST['id'] : null,
                    'usuario' => $_SESSION['usuario'] ?? 'N/A'
                ]);
                jsonResponse(false, 'ID inválido.');
            }
            
            // Buscar informações do título antes de excluir para o log
            $stmtInfo = $pdo->prepare("SELECT empreendimento_id, modulo_id, contrato, parcelamento FROM cobranca WHERE id = :id");
            $stmtInfo->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtInfo->execute();
            $infoTitulo = $stmtInfo->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("DELETE FROM cobranca WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            registrarLog('INFO', 'Título excluído na manutenção de IPTU', [
                'action' => 'delete',
                'id' => $id,
                'empreendimento_id' => $infoTitulo['empreendimento_id'] ?? null,
                'modulo_id' => $infoTitulo['modulo_id'] ?? null,
                'contrato' => $infoTitulo['contrato'] ?? null,
                'parcelamento' => $infoTitulo['parcelamento'] ?? null,
                'usuario' => $_SESSION['usuario'] ?? 'N/A'
            ]);
            
            jsonResponse(true, 'Título excluído com sucesso.');
            break;
            
        default:
            jsonResponse(false, 'Ação não reconhecida.');
            break;
    }
    
} catch (PDOException $e) {
    registrarLog('ERRO', 'Erro na manutenção de IPTU', [
        'action' => $action,
        'error' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'error_info' => $e->errorInfo ?? null,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'post_data' => !empty($_POST) ? $_POST : null,
        'get_data' => !empty($_GET) ? $_GET : null,
        'usuario' => $_SESSION['usuario'] ?? 'N/A',
        'usuario_id' => $_SESSION['usuario_id'] ?? 'N/A'
    ]);
    jsonResponse(false, 'Erro ao realizar operação: ' . $e->getMessage());
} catch (Exception $e) {
    registrarLog('ERRO', 'Erro geral na manutenção de IPTU', [
        'action' => $action,
        'error' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'post_data' => !empty($_POST) ? $_POST : null,
        'get_data' => !empty($_GET) ? $_GET : null,
        'usuario' => $_SESSION['usuario'] ?? 'N/A',
        'usuario_id' => $_SESSION['usuario_id'] ?? 'N/A'
    ]);
    jsonResponse(false, 'Erro ao realizar operação: ' . $e->getMessage());
}

