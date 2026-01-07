<?php
// Iniciar output buffering para evitar saída prematura
ob_start();

// Desabilitar exibição de erros na saída
ini_set('display_errors', 0);
error_reporting(E_ALL);

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
            // Ler dados do POST JSON
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                jsonResponse(false, 'Dados inválidos.');
            }
            
            $empreendimento_id = isset($data['empreendimento_id']) ? (int)$data['empreendimento_id'] : null;
            $periodo_inicio = isset($data['periodo_inicio']) ? trim($data['periodo_inicio']) : null;
            $periodo_fim = isset($data['periodo_fim']) ? trim($data['periodo_fim']) : null;
            $remissao_boletos = isset($data['remissao_boletos']) ? (int)$data['remissao_boletos'] : 0;
            $titulos = isset($data['titulos']) && is_array($data['titulos']) ? $data['titulos'] : [];
            
            // Validações
            if (!$empreendimento_id) {
                jsonResponse(false, 'Empreendimento é obrigatório.');
            }
            
            if (empty($titulos)) {
                jsonResponse(false, 'Nenhum título selecionado para processar.');
            }
            
            // Iniciar transação
            $pdo->beginTransaction();
            
            try {
                $processados = 0;
                $erros = [];
                
                foreach ($titulos as $titulo) {
                    $titulo_id = isset($titulo['id']) ? (int)$titulo['id'] : null;
                    
                    if (!$titulo_id) {
                        $erros[] = 'ID do título inválido';
                        continue;
                    }
                    
                    // Aqui você pode adicionar a lógica de processamento
                    // Por exemplo, marcar como processado, gerar boleto, etc.
                    // Por enquanto, vamos apenas registrar no log
                    
                    logError('Título processado na cobrança automática', [
                        'titulo_id' => $titulo_id,
                        'empreendimento_id' => $empreendimento_id,
                        'periodo_inicio' => $periodo_inicio,
                        'periodo_fim' => $periodo_fim,
                        'remissao_boletos' => $remissao_boletos,
                        'titulo' => $titulo
                    ]);
                    
                    $processados++;
                }
                
                // Commit da transação
                $pdo->commit();
                
                $mensagem = "Processados {$processados} título(s) com sucesso.";
                if (!empty($erros)) {
                    $mensagem .= " Erros: " . implode(', ', $erros);
                }
                
                jsonResponse(true, $mensagem, [
                    'processados' => $processados,
                    'total' => count($titulos),
                    'erros' => $erros
                ]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                // Limpar qualquer output antes de relançar exceção
                ob_clean();
                throw $e;
            }
            break;
            
        default:
            jsonResponse(false, 'Ação não reconhecida.');
            break;
    }
    
} catch (PDOException $e) {
    // Limpar qualquer output antes de enviar erro
    ob_clean();
    logError('Erro na pesquisa de títulos para cobrança automática', [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    jsonResponse(false, 'Erro ao pesquisar títulos: ' . $e->getMessage());
} catch (Exception $e) {
    // Limpar qualquer output antes de enviar erro
    ob_clean();
    logError('Erro geral na pesquisa de títulos para cobrança automática', [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    jsonResponse(false, 'Erro ao pesquisar títulos: ' . $e->getMessage());
}

