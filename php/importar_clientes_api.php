<?php
// Suprimir qualquer saída antes do JSON
if (ob_get_level()) {
    ob_clean();
} else {
    ob_start();
}

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
    if (ob_get_level()) {
        ob_clean();
    }
    echo json_encode(array_merge([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem,
    ], $extra));
    exit;
}

function validarCpfCnpj($valor) {
    $doc = preg_replace('/\D/', '', $valor ?? '');
    if (strlen($doc) === 11) {
        return validarCPF($doc);
    }
    if (strlen($doc) === 14) {
        return validarCNPJ($doc);
    }
    return false;
}

function validarCPF($cpf) {
    if (strlen($cpf) != 11) return false;
    if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += intval($cpf[$i]) * (10 - $i);
    }
    $resto = ($soma * 10) % 11;
    if ($resto == 10 || $resto == 11) $resto = 0;
    if ($resto != intval($cpf[9])) return false;

    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += intval($cpf[$i]) * (11 - $i);
    }
    $resto = ($soma * 10) % 11;
    if ($resto == 10 || $resto == 11) $resto = 0;
    if ($resto != intval($cpf[10])) return false;

    return true;
}

function validarCNPJ($cnpj) {
    if (strlen($cnpj) != 14) return false;
    if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

    $tamanho = 12;
    $numeros = substr($cnpj, 0, $tamanho);
    $digitos = substr($cnpj, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;

    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += intval($numeros[$tamanho - $i]) * $pos--;
        if ($pos < 2) $pos = 9;
    }
    $resultado = $soma % 11 < 2 ? 0 : 11 - ($soma % 11);
    if ($resultado != intval($digitos[0])) return false;

    $tamanho = 13;
    $numeros = substr($cnpj, 0, $tamanho);
    $soma = 0;
    $pos = $tamanho - 7;

    for ($i = $tamanho; $i >= 1; $i--) {
        $soma += intval($numeros[$tamanho - $i]) * $pos--;
        if ($pos < 2) $pos = 9;
    }
    $resultado = $soma % 11 < 2 ? 0 : 11 - ($soma % 11);
    if ($resultado != intval($digitos[1])) return false;

    return true;
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
            
            // Mapeamento de campos para clientes
            // Ordem esperada: CPF/CNPJ, Nome, Tipo Cadastro, CEP, Endereço, Bairro, Cidade, UF, Cód Município, 
            // Data Nasc, Profissão, Identidade, Estado Civil, Nacionalidade, Regime Casamento, 
            // Email, Site, Tel Comercial, Tel Celular1, Tel Celular2, Tel Residencial, CPF Cônjuge, Nome Cônjuge, Ativo
            
            $importados = 0;
            $erros = 0;
            $ignorados = 0;
            $errosDetalhes = [];
            $totalLinhas = count($linhas);
            
            foreach ($linhas as $indice => $linha) {
                $numeroLinha = $indice + 1 + ($primeiraLinhaCabecalho ? 1 : 0);
                
                try {
                    // Mapear campos (ajustar índices conforme estrutura do arquivo)
                    $cpf_cnpj = isset($linha[0]) ? trim($linha[0]) : '';
                    $nome = isset($linha[1]) ? trim($linha[1]) : '';
                    $tipo_cadastro = isset($linha[2]) ? trim($linha[2]) : null;
                    $cep = isset($linha[3]) ? trim($linha[3]) : null;
                    $endereco = isset($linha[4]) ? trim($linha[4]) : null;
                    $bairro = isset($linha[5]) ? trim($linha[5]) : null;
                    $cidade = isset($linha[6]) ? trim($linha[6]) : null;
                    $uf = isset($linha[7]) ? strtoupper(trim($linha[7])) : null;
                    $cod_municipio = isset($linha[8]) ? trim($linha[8]) : null;
                    $data_nasc = isset($linha[9]) && $linha[9] !== '' ? trim($linha[9]) : null;
                    $profissao = isset($linha[10]) ? trim($linha[10]) : null;
                    $identidade = isset($linha[11]) ? trim($linha[11]) : null;
                    $estado_civil = isset($linha[12]) ? trim($linha[12]) : null;
                    $nacionalidade = isset($linha[13]) ? trim($linha[13]) : null;
                    $regime_casamento = isset($linha[14]) ? trim($linha[14]) : null;
                    $email = isset($linha[15]) ? trim($linha[15]) : null;
                    $site = isset($linha[16]) ? trim($linha[16]) : null;
                    $tel_comercial = isset($linha[17]) ? trim($linha[17]) : null;
                    $tel_celular1 = isset($linha[18]) ? trim($linha[18]) : null;
                    $tel_celular2 = isset($linha[19]) ? trim($linha[19]) : null;
                    $tel_residencial = isset($linha[20]) ? trim($linha[20]) : null;
                    $cpf_conjuge = isset($linha[21]) ? trim($linha[21]) : null;
                    $nome_conjuge = isset($linha[22]) ? trim($linha[22]) : null;
                    
                    // Processar campo ativo - garantir que seja sempre boolean
                    // Verificar se a coluna existe (índice 23 = 24ª coluna)
                    $ativo_valor = '';
                    if (isset($linha[23]) && $linha[23] !== null && $linha[23] !== '') {
                        $ativo_valor = trim($linha[23]);
                    }
                    
                    // Converter para boolean - se vazio ou não informado, usar true como padrão
                    if ($ativo_valor === '' || $ativo_valor === null) {
                        $ativo = true;
                    } else {
                        // Converter para boolean baseado no valor
                        $ativo_valor_lower = strtolower($ativo_valor);
                        $ativo = !in_array($ativo_valor_lower, ['false', '0', 'não', 'nao', 'no', 'f', 'n', 'off', 'desativado', 'inativo']);
                    }
                    
                    // Garantir que seja boolean puro (true ou false), nunca string ou null
                    $ativo = ($ativo === true || $ativo === 1 || $ativo === '1' || $ativo === 'true') ? true : false;
                    
                    // Validar campos obrigatórios
                    if (empty($cpf_cnpj)) {
                        throw new Exception("CPF/CNPJ é obrigatório");
                    }
                    
                    if (empty($nome)) {
                        throw new Exception("Nome é obrigatório");
                    }
                    
                    // Validar CPF/CNPJ
                    if (!validarCpfCnpj($cpf_cnpj)) {
                        throw new Exception("CPF/CNPJ inválido: {$cpf_cnpj}");
                    }
                    
                    // Limpar CPF/CNPJ (remover formatação)
                    $cpf_cnpj_limpo = preg_replace('/[^0-9]/', '', $cpf_cnpj);
                    
                    // Validar email se fornecido
                    if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Email inválido: {$email}");
                    }
                    
                    // Validar UF se fornecido (deve ter 2 caracteres)
                    if ($uf !== null && $uf !== '' && strlen($uf) !== 2) {
                        throw new Exception("UF inválido: deve conter 2 caracteres");
                    }
                    
                    // Verificar se já existe cliente com o mesmo CPF/CNPJ
                    $stmtVerificar = $conn->prepare("SELECT id, nome FROM clientes WHERE REPLACE(REPLACE(REPLACE(cpf_cnpj, '.', ''), '/', ''), '-', '') = ? LIMIT 1");
                    $stmtVerificar->execute([$cpf_cnpj_limpo]);
                    $clienteExistente = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
                    
                    if ($clienteExistente) {
                        $ignorados++;
                        $errosDetalhes[] = "Linha {$numeroLinha}: Registro ignorado - Já existe cliente (ID: {$clienteExistente['id']}, Nome: {$clienteExistente['nome']}) com o CPF/CNPJ: {$cpf_cnpj}";
                        continue; // Pular para próxima linha sem importar
                    }
                    
                    // Processar data de nascimento
                    $data_nasc_db = null;
                    if ($data_nasc !== null && $data_nasc !== '') {
                        // Tentar converter para formato de data
                        $data_nasc_formatada = null;
                        // Aceitar formatos: YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY
                        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data_nasc)) {
                            $data_nasc_formatada = $data_nasc;
                        } elseif (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $data_nasc, $matches)) {
                            $data_nasc_formatada = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                        }
                        
                        if ($data_nasc_formatada) {
                            $timestamp = strtotime($data_nasc_formatada);
                            if ($timestamp !== false) {
                                $data_nasc_db = date('Y-m-d', $timestamp);
                            }
                        }
                    }
                    
                    // Converter strings vazias para null
                    $tipo_cadastro = ($tipo_cadastro !== null && trim($tipo_cadastro) !== '') ? trim($tipo_cadastro) : null;
                    $cep = ($cep !== null && trim($cep) !== '') ? trim($cep) : null;
                    $endereco = ($endereco !== null && trim($endereco) !== '') ? trim($endereco) : null;
                    $bairro = ($bairro !== null && trim($bairro) !== '') ? trim($bairro) : null;
                    $cidade = ($cidade !== null && trim($cidade) !== '') ? trim($cidade) : null;
                    $uf = ($uf !== null && trim($uf) !== '') ? strtoupper(trim($uf)) : null;
                    $cod_municipio = ($cod_municipio !== null && trim($cod_municipio) !== '') ? trim($cod_municipio) : null;
                    $profissao = ($profissao !== null && trim($profissao) !== '') ? trim($profissao) : null;
                    $identidade = ($identidade !== null && trim($identidade) !== '') ? trim($identidade) : null;
                    $estado_civil = ($estado_civil !== null && trim($estado_civil) !== '') ? trim($estado_civil) : null;
                    $nacionalidade = ($nacionalidade !== null && trim($nacionalidade) !== '') ? trim($nacionalidade) : null;
                    $regime_casamento = ($regime_casamento !== null && trim($regime_casamento) !== '') ? trim($regime_casamento) : null;
                    $email = ($email !== null && trim($email) !== '') ? trim($email) : null;
                    $site = ($site !== null && trim($site) !== '') ? trim($site) : null;
                    $tel_comercial = ($tel_comercial !== null && trim($tel_comercial) !== '') ? trim($tel_comercial) : null;
                    $tel_celular1 = ($tel_celular1 !== null && trim($tel_celular1) !== '') ? trim($tel_celular1) : null;
                    $tel_celular2 = ($tel_celular2 !== null && trim($tel_celular2) !== '') ? trim($tel_celular2) : null;
                    $tel_residencial = ($tel_residencial !== null && trim($tel_residencial) !== '') ? trim($tel_residencial) : null;
                    $cpf_conjuge = ($cpf_conjuge !== null && trim($cpf_conjuge) !== '') ? trim($cpf_conjuge) : null;
                    $nome_conjuge = ($nome_conjuge !== null && trim($nome_conjuge) !== '') ? trim($nome_conjuge) : null;
                    
                    // Inserir cliente
                    $stmt = $conn->prepare("
                        INSERT INTO clientes (
                            cpf_cnpj, nome, tipo_cadastro, cep, endereco, bairro, cidade, uf,
                            cod_municipio, data_nasc, profissao, identidade, estado_civil,
                            nacionalidade, regime_casamento, email, site, tel_comercial,
                            tel_celular1, tel_celular2, tel_residencial, cpf_conjuge, nome_conjuge,
                            ativo
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    try {
                        // Usar bindValue para garantir tipos corretos, especialmente para boolean
                        $stmt->bindValue(1, $cpf_cnpj_limpo, PDO::PARAM_STR);
                        $stmt->bindValue(2, $nome, PDO::PARAM_STR);
                        $stmt->bindValue(3, $tipo_cadastro, $tipo_cadastro === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(4, $cep, $cep === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(5, $endereco, $endereco === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(6, $bairro, $bairro === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(7, $cidade, $cidade === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(8, $uf, $uf === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(9, $cod_municipio, $cod_municipio === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(10, $data_nasc_db, $data_nasc_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(11, $profissao, $profissao === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(12, $identidade, $identidade === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(13, $estado_civil, $estado_civil === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(14, $nacionalidade, $nacionalidade === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(15, $regime_casamento, $regime_casamento === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(16, $email, $email === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(17, $site, $site === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(18, $tel_comercial, $tel_comercial === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(19, $tel_celular1, $tel_celular1 === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(20, $tel_celular2, $tel_celular2 === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(21, $tel_residencial, $tel_residencial === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(22, $cpf_conjuge, $cpf_conjuge === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        $stmt->bindValue(23, $nome_conjuge, $nome_conjuge === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                        // Garantir que ativo seja sempre boolean válido (true ou false), nunca string vazia
                        // Converter explicitamente para boolean antes de passar para o bindValue
                        $ativo_final = (bool)$ativo;
                        // Usar bindValue com valor boolean explícito
                        $stmt->bindValue(24, $ativo_final ? true : false, PDO::PARAM_BOOL);
                        
                        $stmt->execute();
                        
                        $importados++;
                    } catch (PDOException $e) {
                        // Capturar erros específicos do PDO
                        $errorInfo = $stmt->errorInfo();
                        $errorMsg = $e->getMessage();
                        $errorCode = $e->getCode();
                        
                        // Melhorar mensagem de erro para SQL
                        if (strpos($errorMsg, 'Invalid parameter number') !== false || $errorCode == 'HY093') {
                            $errorMsg = "Erro SQL: Número de parâmetros incorreto na query (verifique se todos os campos estão corretos)";
                        } elseif (strpos($errorMsg, 'duplicate key') !== false || strpos($errorMsg, 'UNIQUE') !== false || strpos($errorMsg, '23505') !== false) {
                            $errorMsg = "Cliente já existe no banco de dados (CPF/CNPJ duplicado)";
                        } elseif (strpos($errorMsg, 'not null') !== false || strpos($errorMsg, '23502') !== false) {
                            $errorMsg = "Erro SQL: Campo obrigatório não informado";
                        } elseif (strpos($errorMsg, 'foreign key') !== false || strpos($errorMsg, '23503') !== false) {
                            $errorMsg = "Erro SQL: Referência inválida (chave estrangeira)";
                        } elseif (!empty($errorInfo[2])) {
                            $errorMsg = "Erro SQL: " . $errorInfo[2];
                        } else {
                            $errorMsg = "Erro ao inserir cliente no banco de dados: " . $errorMsg;
                        }
                        
                        // Log do erro completo para debug
                        logError('Erro ao inserir cliente na importação', [
                            'linha' => $numeroLinha,
                            'cpf_cnpj' => $cpf_cnpj_limpo,
                            'nome' => $nome,
                            'error_code' => $errorCode,
                            'error_info' => $errorInfo,
                            'sql_state' => $errorInfo[0] ?? null
                        ], $e);
                        
                        throw new Exception($errorMsg);
                    }
                } catch (Exception $e) {
                    $erros++;
                    $mensagemErro = $e->getMessage();
                    // Remover duplicação de "Linha X:" se já existir
                    if (preg_match('/^Linha \d+: Linha \d+:/', $mensagemErro)) {
                        $mensagemErro = preg_replace('/^Linha \d+: /', '', $mensagemErro);
                    }
                    $errosDetalhes[] = "Linha {$numeroLinha}: " . $mensagemErro;
                }
            }
            
            $mensagem = "Importação concluída. {$importados} clientes importados";
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
    logError('Erro PDO ao processar importação de clientes', [
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
    logError('Erro geral ao processar importação de clientes', [
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

