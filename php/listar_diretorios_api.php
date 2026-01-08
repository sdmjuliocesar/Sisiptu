<?php
/**
 * API para listar discos e diretórios do Windows
 * Retorna caminhos absolutos (ex: C:\pasta\pasta1)
 */

// Iniciar output buffering
ob_start();

session_start();

require_once __DIR__ . '/database.php';

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
    ob_clean();
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'listar-discos':
            // Listar todos os discos do Windows
            $discos = [];
            
            // No Windows, os discos são de A: até Z:
            for ($letra = 'A'; $letra <= 'Z'; $letra++) {
                $disco = $letra . ':\\';
                if (is_dir($disco) && is_readable($disco)) {
                    $discos[] = [
                        'caminho' => $disco,
                        'nome' => $disco,
                        'tipo' => 'disco'
                    ];
                }
            }
            
            jsonResponse(true, 'Discos listados com sucesso.', [
                'discos' => $discos
            ]);
            break;
            
        case 'listar-pastas':
            // Listar pastas de um diretório específico
            $caminho = $_GET['caminho'] ?? '';
            
            if (empty($caminho)) {
                // Se não informar caminho, retornar discos
                $discos = [];
                for ($letra = 'A'; $letra <= 'Z'; $letra++) {
                    $disco = $letra . ':\\';
                    if (is_dir($disco) && is_readable($disco)) {
                        $discos[] = [
                            'caminho' => $disco,
                            'nome' => $disco,
                            'tipo' => 'disco'
                        ];
                    }
                }
                jsonResponse(true, 'Discos listados.', [
                    'pastas' => $discos,
                    'caminho_atual' => ''
                ]);
                exit;
            }
            
            // Normalizar caminho
            $caminho = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminho);
            $caminho = rtrim($caminho, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            
            // Validar caminho
            if (!is_dir($caminho)) {
                jsonResponse(false, 'Diretório não encontrado: ' . $caminho);
            }
            
            if (!is_readable($caminho)) {
                jsonResponse(false, 'Sem permissão para ler o diretório: ' . $caminho);
            }
            
            // Listar pastas
            $pastas = [];
            $handle = @opendir($caminho);
            
            if ($handle === false) {
                jsonResponse(false, 'Erro ao abrir diretório: ' . $caminho);
            }
            
            while (($entry = readdir($handle)) !== false) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                
                $caminhoCompleto = $caminho . $entry;
                
                // Apenas diretórios
                if (is_dir($caminhoCompleto)) {
                    // Verificar se é acessível
                    if (@is_readable($caminhoCompleto)) {
                        $pastas[] = [
                            'caminho' => $caminhoCompleto,
                            'nome' => $entry,
                            'tipo' => 'pasta'
                        ];
                    }
                }
            }
            
            closedir($handle);
            
            // Ordenar por nome
            usort($pastas, function($a, $b) {
                return strcasecmp($a['nome'], $b['nome']);
            });
            
            // Adicionar opção "Voltar" se não estiver na raiz do disco
            $pastasComVoltar = [];
            if (strlen($caminho) > 3) { // Não está na raiz do disco (ex: C:\)
                $caminhoPai = dirname($caminho);
                if ($caminhoPai !== $caminho) {
                    $pastasComVoltar[] = [
                        'caminho' => $caminhoPai,
                        'nome' => '.. (Voltar)',
                        'tipo' => 'voltar'
                    ];
                }
            }
            
            $pastasComVoltar = array_merge($pastasComVoltar, $pastas);
            
            jsonResponse(true, 'Pastas listadas com sucesso.', [
                'pastas' => $pastasComVoltar,
                'caminho_atual' => $caminho
            ]);
            break;
            
        default:
            jsonResponse(false, 'Ação não reconhecida.');
            break;
    }
    
} catch (Exception $e) {
    jsonResponse(false, 'Erro: ' . $e->getMessage());
}

