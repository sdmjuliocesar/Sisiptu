<?php
/**
 * API para geração de arquivos CNAB
 */
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../config/logger.php';
require_once __DIR__ . '/cnab/autoload.php';

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
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

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
        case 'gerar-remessa':
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                jsonResponse(false, 'Dados inválidos.');
            }
            
            $banco_id = isset($data['banco_id']) ? (int)$data['banco_id'] : null;
            $titulos = isset($data['titulos']) && is_array($data['titulos']) ? $data['titulos'] : [];
            
            if (!$banco_id) {
                jsonResponse(false, 'ID do banco é obrigatório.');
            }
            
            if (empty($titulos)) {
                jsonResponse(false, 'Nenhum título informado para gerar remessa.');
            }
            
            // Buscar dados do banco
            $stmt = $pdo->prepare("
                SELECT id, cedente, cnpj_cpf, banco, conta, agencia, num_banco, carteira,
                       operacao_cc, codigo_cedente, operacao_cedente, caminho_remessa
                FROM bancos
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $banco_id, PDO::PARAM_INT);
            $stmt->execute();
            $banco = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$banco) {
                jsonResponse(false, 'Banco não encontrado.');
            }
            
            if (empty($banco['caminho_remessa'])) {
                jsonResponse(false, 'Caminho de remessa não configurado para este banco.');
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
                // Tentar identificar pelo nome do banco
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
                    $codigoBanco = '001'; // Default: Banco do Brasil
                }
            }
            
            // Verificar se o banco é suportado
            if (!CnabFactory::isBancoSuportado($codigoBanco)) {
                jsonResponse(false, "Banco com código {$codigoBanco} não é suportado para geração de CNAB.");
            }
            
            // Criar instância do CNAB
            $cnab = CnabFactory::criar($codigoBanco, 400);
            
            // Buscar dados completos dos títulos
            $titulosIds = array_map(function($t) {
                return isset($t['id']) ? (int)$t['id'] : 0;
            }, $titulos);
            
            if (empty($titulosIds)) {
                jsonResponse(false, 'IDs dos títulos inválidos.');
            }
            
            $placeholders = implode(',', array_fill(0, count($titulosIds), '?'));
            $stmt = $pdo->prepare("
                SELECT c.*, e.nome as empreendimento_nome, m.nome as modulo_nome
                FROM cobranca c
                LEFT JOIN empreendimentos e ON e.id = c.empreendimento_id
                LEFT JOIN modulos m ON m.id = c.modulo_id
                WHERE c.id IN ({$placeholders})
            ");
            $stmt->execute($titulosIds);
            $titulosCompletos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($titulosCompletos)) {
                jsonResponse(false, 'Nenhum título encontrado com os IDs informados.');
            }
            
            // Gerar arquivo CNAB
            $caminhoArquivo = $cnab->gerarRemessa($dadosBanco, $titulosCompletos, $banco['caminho_remessa']);
            
            // Atualizar status dos títulos (opcional - marcar como enviado)
            foreach ($titulosIds as $tituloId) {
                $stmtUpdate = $pdo->prepare("
                    UPDATE cobranca 
                    SET dataenvio = CURRENT_DATE,
                        situacao = 'ENVIADO'
                    WHERE id = :id
                ");
                $stmtUpdate->bindParam(':id', $tituloId, PDO::PARAM_INT);
                $stmtUpdate->execute();
            }
            
            logError('Arquivo CNAB gerado com sucesso', [
                'banco_id' => $banco_id,
                'codigo_banco' => $codigoBanco,
                'total_titulos' => count($titulosCompletos),
                'caminho_arquivo' => $caminhoArquivo
            ]);
            
            jsonResponse(true, 'Arquivo CNAB gerado com sucesso.', [
                'arquivo' => basename($caminhoArquivo),
                'caminho_completo' => $caminhoArquivo,
                'total_titulos' => count($titulosCompletos),
                'banco' => $banco['banco'],
                'codigo_banco' => $codigoBanco
            ]);
            break;
            
        case 'listar-bancos-suportados':
            $bancos = CnabFactory::getBancosSuportados();
            jsonResponse(true, 'Bancos suportados listados com sucesso.', [
                'bancos' => $bancos
            ]);
            break;
            
        default:
            jsonResponse(false, 'Ação não reconhecida.');
            break;
    }
    
} catch (Exception $e) {
    ob_clean();
    logError('Erro ao gerar arquivo CNAB', [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    jsonResponse(false, 'Erro ao gerar arquivo CNAB: ' . $e->getMessage());
}

