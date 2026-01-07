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

function jsonResponse($sucesso, $mensagem, $extra = []) {
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'listar-arquivos';

try {
    $conn = getConnection();
    
    switch ($action) {
        case 'listar-diretorios':
            // Listar diretórios disponíveis no diretório base
            $caminhoBase = dirname(__DIR__);
            $baseReal = realpath($caminhoBase);
            
            if ($baseReal === false) {
                jsonResponse(false, 'Diretório base não encontrado.');
            }
            
            $diretorios = [];
            $handle = opendir($baseReal);
            
            if ($handle) {
                while (($entry = readdir($handle)) !== false) {
                    if ($entry === '.' || $entry === '..') {
                        continue;
                    }
                    
                    $caminhoCompleto = $baseReal . DIRECTORY_SEPARATOR . $entry;
                    
                    if (is_dir($caminhoCompleto)) {
                        // Converter caminho absoluto para relativo
                        $diretorioRelativo = str_replace($baseReal . DIRECTORY_SEPARATOR, '', $caminhoCompleto);
                        $diretorioRelativo = str_replace('\\', '/', $diretorioRelativo);
                        $diretorios[] = $diretorioRelativo;
                    }
                }
                closedir($handle);
            }
            
            sort($diretorios);
            
            jsonResponse(true, 'Diretórios listados com sucesso.', ['diretorios' => $diretorios]);
            break;
            
        case 'listar-arquivos':
            $diretorio = isset($_GET['diretorio']) ? trim($_GET['diretorio']) : '';
            
            if (empty($diretorio)) {
                jsonResponse(false, 'Diretório não informado.');
            }
            
            // Construir caminho completo (relativo ao diretório raiz do projeto)
            // __DIR__ = C:\xampp\htdocs\SISIPTU\php
            // dirname(__DIR__) = C:\xampp\htdocs\SISIPTU
            $caminhoBase = dirname(__DIR__);
            
            // Normalizar o caminho do diretório (remover barras no início e fim, normalizar separadores)
            $diretorio = trim($diretorio, '/\\');
            $diretorio = str_replace('\\', '/', $diretorio);
            
            // Construir caminho completo
            $caminhoCompleto = $caminhoBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $diretorio);
            
            // Validar se o caminho não está tentando sair do diretório base (segurança)
            $caminhoReal = realpath($caminhoCompleto);
            $baseReal = realpath($caminhoBase);
            
            if ($caminhoReal === false || strpos($caminhoReal, $baseReal) !== 0) {
                jsonResponse(false, 'Diretório inválido ou fora do diretório permitido: ' . $diretorio);
            }
            
            // Validar se o diretório existe
            if (!is_dir($caminhoReal)) {
                jsonResponse(false, 'Diretório não encontrado: ' . $diretorio);
            }
            
            // Listar arquivos .txt e .csv
            $arquivos = [];
            $handle = opendir($caminhoReal);
            
            if ($handle) {
                while (($entry = readdir($handle)) !== false) {
                    if ($entry === '.' || $entry === '..') {
                        continue;
                    }
                    
                    $caminhoArquivo = $caminhoReal . DIRECTORY_SEPARATOR . $entry;
                    
                    if (is_file($caminhoArquivo)) {
                        $extensao = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                        if (in_array($extensao, ['txt', 'csv'])) {
                            $arquivos[] = $entry;
                        }
                    }
                }
                closedir($handle);
            }
            
            sort($arquivos);
            
            jsonResponse(true, 'Arquivos listados com sucesso.', ['arquivos' => $arquivos]);
            break;
            
        case 'preview':
            $diretorio = isset($_GET['diretorio']) ? trim($_GET['diretorio']) : '';
            $arquivo = isset($_GET['arquivo']) ? trim($_GET['arquivo']) : '';
            $delimitador = isset($_GET['delimitador']) ? $_GET['delimitador'] : ',';
            $primeiraLinhaCabecalho = isset($_GET['primeira_linha_cabecalho']) && $_GET['primeira_linha_cabecalho'] === '1';
            
            if (empty($diretorio) || empty($arquivo)) {
                jsonResponse(false, 'Diretório e arquivo são obrigatórios.');
            }
            
            // Construir caminho completo
            $caminhoBase = dirname(__DIR__);
            
            // Normalizar o caminho do diretório
            $diretorio = trim($diretorio, '/\\');
            $diretorio = str_replace('\\', '/', $diretorio);
            $arquivo = basename($arquivo); // Segurança: apenas o nome do arquivo
            
            $caminhoCompleto = $caminhoBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $diretorio) . DIRECTORY_SEPARATOR . $arquivo;
            
            // Validar segurança do caminho
            $caminhoReal = realpath(dirname($caminhoCompleto));
            $baseReal = realpath($caminhoBase);
            
            if ($caminhoReal === false || strpos($caminhoReal, $baseReal) !== 0) {
                jsonResponse(false, 'Caminho inválido ou fora do diretório permitido.');
            }
            
            $caminhoArquivo = $caminhoReal . DIRECTORY_SEPARATOR . $arquivo;
            
            // Validar se o arquivo existe
            if (!file_exists($caminhoArquivo)) {
                jsonResponse(false, 'Arquivo não encontrado: ' . $arquivo);
            }
            
            // Ler arquivo
            $linhas = [];
            $handle = fopen($caminhoArquivo, 'r');
            
            if ($handle === false) {
                jsonResponse(false, 'Erro ao abrir o arquivo.');
            }
            
            // Ler máximo de 21 linhas para preview
            $contador = 0;
            while (($linha = fgetcsv($handle, 0, $delimitador)) !== false && $contador < 21) {
                $linhas[] = $linha;
                $contador++;
            }
            
            fclose($handle);
            
            jsonResponse(true, 'Preview carregado com sucesso.', [
                'linhas' => $linhas,
                'total_linhas_arquivo' => count(file($caminhoArquivo))
            ]);
            break;
            
        case 'importar':
            $diretorio = isset($_POST['diretorio']) ? trim($_POST['diretorio']) : '';
            $arquivo = isset($_POST['arquivo']) ? trim($_POST['arquivo']) : '';
            $delimitador = isset($_POST['delimitador']) ? $_POST['delimitador'] : ',';
            $primeiraLinhaCabecalho = isset($_POST['primeira_linha_cabecalho']) && $_POST['primeira_linha_cabecalho'] === '1';
            
            if (empty($diretorio) || empty($arquivo)) {
                jsonResponse(false, 'Diretório e arquivo são obrigatórios.');
            }
            
            // Construir caminho completo
            $caminhoBase = dirname(__DIR__);
            
            // Normalizar o caminho do diretório
            $diretorio = trim($diretorio, '/\\');
            $diretorio = str_replace('\\', '/', $diretorio);
            $arquivo = basename($arquivo); // Segurança: apenas o nome do arquivo
            
            $caminhoCompleto = $caminhoBase . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $diretorio) . DIRECTORY_SEPARATOR . $arquivo;
            
            // Validar segurança do caminho
            $caminhoReal = realpath(dirname($caminhoCompleto));
            $baseReal = realpath($caminhoBase);
            
            if ($caminhoReal === false || strpos($caminhoReal, $baseReal) !== 0) {
                jsonResponse(false, 'Caminho inválido ou fora do diretório permitido.');
            }
            
            $caminhoArquivo = $caminhoReal . DIRECTORY_SEPARATOR . $arquivo;
            
            // Validar se o arquivo existe
            if (!file_exists($caminhoArquivo)) {
                jsonResponse(false, 'Arquivo não encontrado: ' . $arquivo);
            }
            
            // Ler arquivo completo
            $linhas = [];
            $handle = fopen($caminhoArquivo, 'r');
            
            if ($handle === false) {
                jsonResponse(false, 'Erro ao abrir o arquivo.');
            }
            
            while (($linha = fgetcsv($handle, 0, $delimitador)) !== false) {
                $linhas[] = $linha;
            }
            
            fclose($handle);
            
            if (empty($linhas)) {
                jsonResponse(false, 'Arquivo vazio ou sem dados válidos.');
            }
            
            // Se primeira linha é cabeçalho, remover
            if ($primeiraLinhaCabecalho) {
                array_shift($linhas);
            }
            
            // Mapeamento de campos (ajustar conforme necessário)
            // Assumindo que o CSV tem as colunas na ordem: contrato, cpf_cnpj, cliente_nome, area, metragem, vrm2, inscricao, valor_venal, aliquota, tx_coleta_lixo, desconto_a_vista, parcelamento, obs, situacao
            // Você pode ajustar este mapeamento conforme a estrutura do seu arquivo
            
            $importados = 0;
            $erros = 0;
            $ignorados = 0;
            $errosDetalhes = [];
            $totalLinhas = count($linhas);
            
            foreach ($linhas as $indice => $linha) {
                $numeroLinha = $indice + 1 + ($primeiraLinhaCabecalho ? 1 : 0);
                
                try {
                    // Mapear campos (ajustar índices conforme estrutura do arquivo)
                    $contrato = isset($linha[0]) ? trim($linha[0]) : '';
                    $cpf_cnpj = isset($linha[1]) ? trim($linha[1]) : '';
                    $cliente_nome = isset($linha[2]) ? trim($linha[2]) : '';
                    $area = isset($linha[3]) ? trim($linha[3]) : null;
                    $metragem = isset($linha[4]) && $linha[4] !== '' ? floatval(str_replace(',', '.', $linha[4])) : null;
                    $vrm2 = isset($linha[5]) && $linha[5] !== '' ? floatval(str_replace(',', '.', $linha[5])) : null;
                    $inscricao = isset($linha[6]) ? trim($linha[6]) : null;
                    $valor_venal = isset($linha[7]) && $linha[7] !== '' ? floatval(str_replace(',', '.', $linha[7])) : null;
                    $aliquota = isset($linha[8]) && $linha[8] !== '' ? floatval(str_replace(',', '.', $linha[8])) : null;
                    $tx_coleta_lixo = isset($linha[9]) && $linha[9] !== '' ? floatval(str_replace(',', '.', $linha[9])) : null;
                    $desconto_a_vista = isset($linha[10]) && $linha[10] !== '' ? floatval(str_replace(',', '.', $linha[10])) : null;
                    $parcelamento = isset($linha[11]) && $linha[11] !== '' ? intval($linha[11]) : null;
                    $obs = isset($linha[12]) ? trim($linha[12]) : null;
                    $situacao = isset($linha[13]) ? trim($linha[13]) : null;
                    
                    // Validar campos obrigatórios
                    if (empty($contrato)) {
                        throw new Exception("Linha {$numeroLinha}: Contrato é obrigatório");
                    }
                    
                    // Validar se já existe contrato com o mesmo CPF/CNPJ
                    if (!empty($cpf_cnpj)) {
                        $stmtVerificar = $conn->prepare("SELECT id, contrato FROM contratos WHERE cpf_cnpj = ? LIMIT 1");
                        $stmtVerificar->execute([$cpf_cnpj]);
                        $contratoExistente = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
                        
                        if ($contratoExistente) {
                            $ignorados++;
                            $errosDetalhes[] = "Linha {$numeroLinha}: Registro ignorado - Já existe contrato (ID: {$contratoExistente['id']}, Contrato: {$contratoExistente['contrato']}) com o CPF/CNPJ: {$cpf_cnpj}";
                            continue; // Pular para próxima linha sem importar
                        }
                    }
                    
                    // Buscar ou criar cliente pelo CPF/CNPJ
                    $cliente_id = null;
                    if (!empty($cpf_cnpj)) {
                        $stmtCliente = $conn->prepare("SELECT id FROM clientes WHERE cpf_cnpj = ? LIMIT 1");
                        $stmtCliente->execute([$cpf_cnpj]);
                        $cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);
                        
                        if ($cliente) {
                            $cliente_id = $cliente['id'];
                        } else if (!empty($cliente_nome)) {
                            // Criar cliente se não existir
                            $stmtInsertCliente = $conn->prepare("
                                INSERT INTO clientes (cpf_cnpj, nome, data_criacao) 
                                VALUES (?, ?, CURRENT_TIMESTAMP) 
                                RETURNING id
                            ");
                            $stmtInsertCliente->execute([$cpf_cnpj, $cliente_nome]);
                            $clienteNovo = $stmtInsertCliente->fetch(PDO::FETCH_ASSOC);
                            $cliente_id = $clienteNovo['id'];
                        }
                    }
                    
                    // Calcular valores se necessário
                    $valor_mensal = null;
                    $valor_anual = null;
                    
                    if ($valor_venal !== null && $aliquota !== null) {
                        $valor_anual = ($valor_venal * $aliquota) / 100;
                        if ($parcelamento !== null && $parcelamento > 0) {
                            $valor_mensal = $valor_anual / $parcelamento;
                        }
                    }
                    
                    // Inserir contrato
                    // Nota: empreendimento_id e modulo_id precisam ser definidos ou mapeados do arquivo
                    // Por enquanto, deixando como NULL - você pode ajustar conforme necessário
                    $stmt = $conn->prepare("
                        INSERT INTO contratos (
                            empreendimento_id, modulo_id, cliente_id, contrato, area, inscricao, metragem, vrm2, 
                            valor_venal, aliquota, tx_coleta_lixo, desconto_a_vista, 
                            parcelamento, obs, valor_mensal, valor_anual, cpf_cnpj, situacao
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        null, // empreendimento_id - ajustar conforme necessário
                        null, // modulo_id - ajustar conforme necessário
                        $cliente_id,
                        $contrato,
                        $area,
                        $inscricao,
                        $metragem,
                        $vrm2,
                        $valor_venal,
                        $aliquota,
                        $tx_coleta_lixo,
                        $desconto_a_vista,
                        $parcelamento,
                        $obs,
                        $valor_mensal,
                        $valor_anual,
                        $cpf_cnpj,
                        $situacao
                    ]);
                    
                    $importados++;
                } catch (Exception $e) {
                    $erros++;
                    $errosDetalhes[] = "Linha {$numeroLinha}: " . $e->getMessage();
                }
            }
            
            $mensagem = "Importação concluída. {$importados} contratos importados";
            if ($ignorados > 0) {
                $mensagem .= ", {$ignorados} registros ignorados (já existentes)";
            }
            if ($erros > 0) {
                $mensagem .= ", {$erros} erros";
            }
            
            jsonResponse(true, $mensagem, [
                'total_linhas' => $totalLinhas,
                'importados' => $importados,
                'ignorados' => $ignorados,
                'erros' => $erros,
                'erros_detalhes' => $errosDetalhes
            ]);
            break;
            
        default:
            jsonResponse(false, 'Ação inválida.');
    }
    
} catch (PDOException $e) {
    logError('Erro PDO ao processar importação de contratos', [
        'action' => $action
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
    logError('Erro geral ao processar importação de contratos', [
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
}

