<?php
// Suprimir qualquer saída antes do JSON
if (ob_get_level()) {
    ob_clean();
} else {
    ob_start();
}

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

function jsonResponseContrato($sucesso, $mensagem, $extra = []) {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

try {
    // Suprimir warnings e notices que podem aparecer antes do JSON
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    
    $conn = getConnection();
    
    switch ($action) {
        case 'verificar-contrato':
            // Verificar se contrato já existe usando empreendimento, modulo e contrato
            $empreendimento_id = isset($_REQUEST['empreendimento_id']) ? trim($_REQUEST['empreendimento_id']) : '';
            $modulo_id = isset($_REQUEST['modulo_id']) ? trim($_REQUEST['modulo_id']) : '';
            $contrato = isset($_REQUEST['contrato']) ? trim($_REQUEST['contrato']) : '';
            $contrato_id = isset($_REQUEST['contrato_id']) ? intval($_REQUEST['contrato_id']) : 0; // Para update, ignorar o próprio registro
            
            if (empty($empreendimento_id) || empty($modulo_id) || empty($contrato)) {
                jsonResponseContrato(false, 'Empreendimento, módulo e contrato são obrigatórios para verificação.');
            }
            
            // Buscar empreendimento e módulo para obter os nomes
            $stmtEmp = $conn->prepare("SELECT nome FROM empreendimentos WHERE id = ?");
            $stmtEmp->execute([$empreendimento_id]);
            $empreendimento = $stmtEmp->fetch(PDO::FETCH_ASSOC);
            
            $stmtMod = $conn->prepare("SELECT nome FROM modulos WHERE id = ?");
            $stmtMod->execute([$modulo_id]);
            $modulo = $stmtMod->fetch(PDO::FETCH_ASSOC);
            
            // Verificar se contrato já existe
            $sql = "SELECT c.*, 
                           e.nome as empreendimento_nome,
                           m.nome as modulo_nome,
                           cli.id as cliente_id,
                           cli.nome as cliente_nome,
                           cli.email as cliente_email,
                           cli.tel_celular1 as cliente_tel_celular1,
                           cli.tel_celular2 as cliente_tel_celular2,
                           cli.tel_comercial as cliente_tel_comercial,
                           cli.tel_residencial as cliente_tel_residencial
                    FROM contratos c
                    LEFT JOIN empreendimentos e ON c.empreendimento_id = e.id
                    LEFT JOIN modulos m ON c.modulo_id = m.id
                    LEFT JOIN clientes cli ON c.cliente_id = cli.id
                    WHERE c.empreendimento_id = ? AND c.modulo_id = ? AND c.contrato = ?";
            
            if ($contrato_id > 0) {
                $sql .= " AND c.id != ?";
            }
            
            $stmt = $conn->prepare($sql);
            if ($contrato_id > 0) {
                $stmt->execute([$empreendimento_id, $modulo_id, $contrato, $contrato_id]);
            } else {
                $stmt->execute([$empreendimento_id, $modulo_id, $contrato]);
            }
            
            $contratoExistente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($contratoExistente) {
                jsonResponseContrato(true, 'Contrato encontrado', [
                    'existe' => true,
                    'contrato' => $contratoExistente
                ]);
            } else {
                jsonResponseContrato(true, 'Contrato não encontrado', [
                    'existe' => false
                ]);
            }
            break;
            
        case 'create':
            $empreendimento_id = isset($_POST['empreendimento_id']) ? trim($_POST['empreendimento_id']) : '';
            $modulo_id = isset($_POST['modulo_id']) ? trim($_POST['modulo_id']) : '';
            $cliente_id = isset($_POST['cliente_id']) && $_POST['cliente_id'] !== '' ? intval($_POST['cliente_id']) : null;
            $contrato = isset($_POST['contrato']) ? trim($_POST['contrato']) : '';
            $area = isset($_POST['area']) ? trim($_POST['area']) : null;
            $inscricao = isset($_POST['inscricao']) ? trim($_POST['inscricao']) : null;
            $metragem = isset($_POST['metragem']) && $_POST['metragem'] !== '' ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['metragem'])) : null;
            $vrm2 = isset($_POST['vrm2']) && $_POST['vrm2'] !== '' ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['vrm2'])) : null;
            $valor_venal = isset($_POST['valor_venal']) && $_POST['valor_venal'] !== '' ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['valor_venal'])) : null;
            $aliquota = isset($_POST['aliquota']) && $_POST['aliquota'] !== '' ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['aliquota'])) : null;
            $tx_coleta_lixo = isset($_POST['tx_coleta_lixo']) && $_POST['tx_coleta_lixo'] !== '' ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['tx_coleta_lixo'])) : null;
            $desconto_a_vista = isset($_POST['desconto_a_vista']) && $_POST['desconto_a_vista'] !== '' ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['desconto_a_vista'])) : null;
            $parcelamento = isset($_POST['parcelamento']) && $_POST['parcelamento'] !== '' ? intval($_POST['parcelamento']) : null;
            $obs = isset($_POST['obs']) ? trim($_POST['obs']) : null;
            $valor_mensal = isset($_POST['valor_mensal']) && $_POST['valor_mensal'] !== '' ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['valor_mensal'])) : null;
            $valor_anual = isset($_POST['valor_anual']) && $_POST['valor_anual'] !== '' ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['valor_anual'])) : null;
            $cpf_cnpj = isset($_POST['cpf_cnpj']) ? trim($_POST['cpf_cnpj']) : null;
            $situacao = isset($_POST['situacao']) ? trim($_POST['situacao']) : null;
            
            // Validações
            if (empty($empreendimento_id) || empty($modulo_id) || empty($contrato)) {
                jsonResponseContrato(false, 'Empreendimento, módulo e contrato são obrigatórios.');
            }
            
            // Verificar se contrato já existe
            $stmtVerifica = $conn->prepare("SELECT id FROM contratos WHERE empreendimento_id = ? AND modulo_id = ? AND contrato = ?");
            $stmtVerifica->execute([$empreendimento_id, $modulo_id, $contrato]);
            if ($stmtVerifica->fetch()) {
                jsonResponseContrato(false, 'Já existe um contrato cadastrado com este Empreendimento, Módulo e Contrato.');
            }
            
            // Buscar nomes do empreendimento e módulo
            $stmtEmp = $conn->prepare("SELECT nome FROM empreendimentos WHERE id = ?");
            $stmtEmp->execute([$empreendimento_id]);
            $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);
            $empreendimento_nome = $emp ? $emp['nome'] : '';
            
            $stmtMod = $conn->prepare("SELECT nome FROM modulos WHERE id = ?");
            $stmtMod->execute([$modulo_id]);
            $mod = $stmtMod->fetch(PDO::FETCH_ASSOC);
            $modulo_nome = $mod ? $mod['nome'] : '';
            
            // Converter valores vazios para null e tratar valores numéricos
            $metragem_val = ($metragem !== null && $metragem !== '' && $metragem !== '0') ? floatval($metragem) : null;
            $vrm2_val = ($vrm2 !== null && $vrm2 !== '' && $vrm2 !== '0') ? floatval($vrm2) : null;
            $valor_venal_val = ($valor_venal !== null && $valor_venal !== '' && $valor_venal !== '0') ? floatval($valor_venal) : null;
            $aliquota_val = ($aliquota !== null && $aliquota !== '' && $aliquota !== '0') ? floatval($aliquota) : null;
            $tx_coleta_lixo_val = ($tx_coleta_lixo !== null && $tx_coleta_lixo !== '' && $tx_coleta_lixo !== '0') ? floatval($tx_coleta_lixo) : null;
            $desconto_a_vista_val = ($desconto_a_vista !== null && $desconto_a_vista !== '' && $desconto_a_vista !== '0') ? floatval($desconto_a_vista) : null;
            $valor_mensal_val = ($valor_mensal !== null && $valor_mensal !== '' && $valor_mensal !== '0') ? floatval($valor_mensal) : null;
            $valor_anual_val = ($valor_anual !== null && $valor_anual !== '' && $valor_anual !== '0') ? floatval($valor_anual) : null;
            
            // Converter strings vazias para null
            $area = ($area !== null && trim($area) !== '') ? trim($area) : null;
            $inscricao = ($inscricao !== null && trim($inscricao) !== '') ? trim($inscricao) : null;
            $obs = ($obs !== null && trim($obs) !== '') ? trim($obs) : null;
            $cpf_cnpj = ($cpf_cnpj !== null && trim($cpf_cnpj) !== '') ? trim($cpf_cnpj) : null;
            $situacao = ($situacao !== null && trim($situacao) !== '') ? trim($situacao) : null;
            
            $sql = "INSERT INTO contratos (
                        empreendimento_id, modulo_id, cliente_id, contrato, area, inscricao, metragem, vrm2, 
                        valor_venal, aliquota, tx_coleta_lixo, desconto_a_vista, 
                        parcelamento, obs, valor_mensal, valor_anual, cpf_cnpj, situacao
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            try {
                $stmt = $conn->prepare($sql);
                $resultado = $stmt->execute([
                    intval($empreendimento_id), 
                    intval($modulo_id), 
                    $cliente_id,
                    $contrato, 
                    $area, 
                    $inscricao,
                    $metragem_val, 
                    $vrm2_val, 
                    $valor_venal_val, 
                    $aliquota_val,
                    $tx_coleta_lixo_val, 
                    $desconto_a_vista_val, 
                    $parcelamento, 
                    $obs,
                    $valor_mensal_val, 
                    $valor_anual_val, 
                    $cpf_cnpj, 
                    $situacao
                ]);
                
                if (!$resultado) {
                    $errorInfo = $stmt->errorInfo();
                    throw new Exception('Erro ao executar query: ' . ($errorInfo[2] ?? 'Erro desconhecido'));
                }
            } catch (PDOException $e) {
                registrarLog('ERRO', 'Erro ao inserir contrato: ' . $e->getMessage(), [
                    'sql' => $sql, 
                    'trace' => $e->getTraceAsString(),
                    'errorInfo' => $e->errorInfo ?? null
                ]);
                jsonResponseContrato(false, 'Erro ao salvar contrato: ' . $e->getMessage());
            } catch (Exception $e) {
                registrarLog('ERRO', 'Erro ao inserir contrato: ' . $e->getMessage(), [
                    'sql' => $sql, 
                    'trace' => $e->getTraceAsString()
                ]);
                jsonResponseContrato(false, 'Erro ao salvar contrato: ' . $e->getMessage());
            }
            
            $id = $conn->lastInsertId();
            
            // Buscar o registro criado com os nomes
            $stmtGet = $conn->prepare("SELECT c.*, 
                                              e.nome as empreendimento_nome,
                                              m.nome as modulo_nome
                                       FROM contratos c
                                       LEFT JOIN empreendimentos e ON c.empreendimento_id = e.id
                                       LEFT JOIN modulos m ON c.modulo_id = m.id
                                       WHERE c.id = ?");
            $stmtGet->execute([$id]);
            $novoContrato = $stmtGet->fetch(PDO::FETCH_ASSOC);
            
            jsonResponseContrato(true, 'Contrato cadastrado com sucesso!', ['contrato' => $novoContrato]);
            break;
            
        case 'update':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            if ($id <= 0) {
                jsonResponseContrato(false, 'ID do contrato inválido.');
            }
            
            $empreendimento_id = isset($_POST['empreendimento_id']) ? trim($_POST['empreendimento_id']) : '';
            $modulo_id = isset($_POST['modulo_id']) ? trim($_POST['modulo_id']) : '';
            $cliente_id = isset($_POST['cliente_id']) && $_POST['cliente_id'] !== '' ? intval($_POST['cliente_id']) : null;
            $contrato = isset($_POST['contrato']) ? trim($_POST['contrato']) : '';
            $area = isset($_POST['area']) ? trim($_POST['area']) : null;
            $inscricao = isset($_POST['inscricao']) ? trim($_POST['inscricao']) : null;
            $metragem = isset($_POST['metragem']) ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['metragem'])) : null;
            $vrm2 = isset($_POST['vrm2']) ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['vrm2'])) : null;
            $valor_venal = isset($_POST['valor_venal']) ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['valor_venal'])) : null;
            $aliquota = isset($_POST['aliquota']) ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['aliquota'])) : null;
            $tx_coleta_lixo = isset($_POST['tx_coleta_lixo']) ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['tx_coleta_lixo'])) : null;
            $desconto_a_vista = isset($_POST['desconto_a_vista']) ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['desconto_a_vista'])) : null;
            $parcelamento = isset($_POST['parcelamento']) && $_POST['parcelamento'] !== '' ? intval($_POST['parcelamento']) : null;
            $obs = isset($_POST['obs']) ? trim($_POST['obs']) : null;
            $valor_mensal = isset($_POST['valor_mensal']) ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['valor_mensal'])) : null;
            $valor_anual = isset($_POST['valor_anual']) ? str_replace(',', '.', preg_replace('/[^\d,.-]/', '', $_POST['valor_anual'])) : null;
            $cpf_cnpj = isset($_POST['cpf_cnpj']) ? trim($_POST['cpf_cnpj']) : null;
            $situacao = isset($_POST['situacao']) ? trim($_POST['situacao']) : null;
            
            // Validações
            if (empty($empreendimento_id) || empty($modulo_id) || empty($contrato)) {
                jsonResponseContrato(false, 'Empreendimento, módulo e contrato são obrigatórios.');
            }
            
            // Verificar se contrato já existe (exceto o próprio registro)
            $stmtVerifica = $conn->prepare("SELECT id FROM contratos WHERE empreendimento_id = ? AND modulo_id = ? AND contrato = ? AND id != ?");
            $stmtVerifica->execute([$empreendimento_id, $modulo_id, $contrato, $id]);
            if ($stmtVerifica->fetch()) {
                jsonResponseContrato(false, 'Já existe um contrato cadastrado com este Empreendimento, Módulo e Contrato.');
            }
            
            $sql = "UPDATE contratos SET 
                        empreendimento_id = ?, modulo_id = ?, cliente_id = ?, contrato = ?, area = ?, inscricao = ?, 
                        metragem = ?, vrm2 = ?, valor_venal = ?, aliquota = ?, tx_coleta_lixo = ?, 
                        desconto_a_vista = ?, parcelamento = ?, obs = ?, 
                        valor_mensal = ?, valor_anual = ?, cpf_cnpj = ?, situacao = ?
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $empreendimento_id, $modulo_id, $cliente_id, $contrato, $area, $inscricao,
                $metragem !== null && $metragem !== '' ? floatval($metragem) : null,
                $vrm2 !== null && $vrm2 !== '' ? floatval($vrm2) : null,
                $valor_venal !== null && $valor_venal !== '' ? floatval($valor_venal) : null,
                $aliquota !== null && $aliquota !== '' ? floatval($aliquota) : null,
                $tx_coleta_lixo !== null && $tx_coleta_lixo !== '' ? floatval($tx_coleta_lixo) : null,
                $desconto_a_vista !== null && $desconto_a_vista !== '' ? floatval($desconto_a_vista) : null,
                $parcelamento, $obs,
                $valor_mensal !== null && $valor_mensal !== '' ? floatval($valor_mensal) : null,
                $valor_anual !== null && $valor_anual !== '' ? floatval($valor_anual) : null,
                $cpf_cnpj, $situacao, $id
            ]);
            
            jsonResponseContrato(true, 'Contrato atualizado com sucesso!');
            break;
            
        case 'delete':
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if ($id <= 0) {
                jsonResponseContrato(false, 'ID do contrato inválido.');
            }
            
            // Buscar os dados do contrato antes de excluir (para excluir as parcelas relacionadas)
            $stmtGet = $conn->prepare("SELECT empreendimento_id, modulo_id, contrato FROM contratos WHERE id = ?");
            $stmtGet->execute([$id]);
            $contrato = $stmtGet->fetch(PDO::FETCH_ASSOC);
            
            if (!$contrato) {
                jsonResponseContrato(false, 'Contrato não encontrado.');
            }
            
            // Iniciar transação para garantir que ambas as exclusões sejam executadas ou nenhuma
            $conn->beginTransaction();
            
            try {
                // Primeiro: excluir todas as parcelas (cobrancas) relacionadas ao contrato
                $empreendimento_id = $contrato['empreendimento_id'];
                $modulo_id = $contrato['modulo_id'];
                $contrato_codigo = $contrato['contrato'];
                
                $stmtDeleteCobrancas = $conn->prepare("
                    DELETE FROM cobranca 
                    WHERE empreendimento_id = ? 
                      AND modulo_id = ? 
                      AND contrato = ?
                ");
                $stmtDeleteCobrancas->execute([$empreendimento_id, $modulo_id, $contrato_codigo]);
                $parcelasExcluidas = $stmtDeleteCobrancas->rowCount();
                
                // Segundo: excluir o contrato
                $stmtDeleteContrato = $conn->prepare("DELETE FROM contratos WHERE id = ?");
                $stmtDeleteContrato->execute([$id]);
                
                // Confirmar transação
                $conn->commit();
                
                $mensagem = 'Contrato excluído com sucesso!';
                if ($parcelasExcluidas > 0) {
                    $mensagem .= " ($parcelasExcluidas parcela(s) relacionada(s) também foram excluída(s).)";
                }
                
                jsonResponseContrato(true, $mensagem);
            } catch (Exception $e) {
                // Reverter transação em caso de erro
                $conn->rollBack();
                registrarLog('ERRO', 'Erro ao excluir contrato e parcelas: ' . $e->getMessage(), [
                    'contrato_id' => $id,
                    'trace' => $e->getTraceAsString()
                ]);
                jsonResponseContrato(false, 'Erro ao excluir contrato: ' . $e->getMessage());
            }
            break;
            
        case 'read':
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            if ($id <= 0) {
                jsonResponseContrato(false, 'ID do contrato inválido.');
            }
            
            $stmt = $conn->prepare("SELECT c.*, 
                                           e.nome as empreendimento_nome,
                                           m.nome as modulo_nome,
                                           cli.id as cliente_id,
                                           cli.nome as cliente_nome,
                                           cli.cpf_cnpj as cliente_cpf_cnpj
                                    FROM contratos c
                                    LEFT JOIN empreendimentos e ON c.empreendimento_id = e.id
                                    LEFT JOIN modulos m ON c.modulo_id = m.id
                                    LEFT JOIN clientes cli ON c.cliente_id = cli.id
                                    WHERE c.id = ?");
            $stmt->execute([$id]);
            $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$contrato) {
                jsonResponseContrato(false, 'Contrato não encontrado.');
            }
            
            jsonResponseContrato(true, 'Contrato encontrado', ['contrato' => $contrato]);
            break;
            
        case 'list':
        default:
            $search = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : (isset($_REQUEST['search']) ? trim($_REQUEST['search']) : '');
            
            $sql = "SELECT c.*, 
                           e.nome as empreendimento_nome,
                           m.nome as modulo_nome,
                           cli.nome as cliente_nome
                    FROM contratos c
                    LEFT JOIN empreendimentos e ON c.empreendimento_id = e.id
                    LEFT JOIN modulos m ON c.modulo_id = m.id
                    LEFT JOIN clientes cli ON c.cliente_id = cli.id
                    WHERE 1=1";
            
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (
                    c.contrato ILIKE ? OR 
                    c.inscricao ILIKE ? OR 
                    e.nome ILIKE ? OR
                    m.nome ILIKE ? OR
                    cli.nome ILIKE ?
                )";
                $searchParam = '%' . $search . '%';
                $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
            }
            
            $sql .= " ORDER BY c.data_criacao DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total de contratos na tabela
            $stmtCount = $conn->query("SELECT COUNT(*) as total FROM contratos");
            $countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $total = (int)$countResult['total'];
            
            jsonResponseContrato(true, 'Contratos listados com sucesso', [
                'contratos' => $contratos,
                'total' => $total
            ]);
            break;
    }
    
} catch (PDOException $e) {
    if (ob_get_level()) {
        ob_clean();
    }
    $errorMsg = 'Erro PDO em contratos_api.php: ' . $e->getMessage() . ' | Arquivo: ' . $e->getFile() . ' | Linha: ' . $e->getLine();
    $errorData = [
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    if (isset($sql)) {
        $errorData['sql'] = $sql;
    }
    registrarLog('ERRO', $errorMsg, $errorData);
    jsonResponseContrato(false, 'Erro ao processar a requisição de contratos. Detalhes: ' . $e->getMessage());
} catch (Exception $e) {
    if (ob_get_level()) {
        ob_clean();
    }
    $errorMsg = 'Erro em contratos_api.php: ' . $e->getMessage() . ' | Arquivo: ' . $e->getFile() . ' | Linha: ' . $e->getLine();
    $errorData = [
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ];
    if (isset($sql)) {
        $errorData['sql'] = $sql;
    }
    registrarLog('ERRO', $errorMsg, $errorData);
    jsonResponseContrato(false, 'Erro ao processar a requisição de contratos. Detalhes: ' . $e->getMessage());
}
