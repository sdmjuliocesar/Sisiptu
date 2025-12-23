<?php
/**
 * Sistema de Log para o SISIPTU
 * Registra eventos importantes do sistema
 */

// Definir diretório de logs
define('LOG_DIR', __DIR__ . '/../logs/');

/**
 * Função para registrar logs
 * @param string $tipo Tipo de log (login, erro, info, etc.)
 * @param string $mensagem Mensagem a ser registrada
 * @param array $dados Dados adicionais para o log
 */
function registrarLog($tipo, $mensagem, $dados = []) {
    try {
        // Criar diretório de logs se não existir
        if (!is_dir(LOG_DIR)) {
            if (!mkdir(LOG_DIR, 0755, true)) {
                error_log("Erro ao criar diretório de logs: " . LOG_DIR);
                return false;
            }
        }
        
        // Verificar se o diretório é gravável
        if (!is_writable(LOG_DIR)) {
            // Tentar alterar permissões
            @chmod(LOG_DIR, 0755);
            if (!is_writable(LOG_DIR)) {
                error_log("Diretório de logs não é gravável: " . LOG_DIR);
                return false;
            }
        }
        
        // Nome do arquivo de log baseado na data e tipo
        $tipoArquivo = strtolower($tipo);
        $arquivoLog = LOG_DIR . $tipoArquivo . '_' . date('Y-m-d') . '.log';
        
        // Obter IP do usuário
        $ip = obterIP();
        
        // Preparar dados do log
        $timestamp = date('Y-m-d H:i:s');
        $logData = [
            'timestamp' => $timestamp,
            'tipo' => $tipo,
            'mensagem' => $mensagem,
            'ip' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'N/A',
            'dados' => $dados
        ];
        
        // Formatar linha do log
        $linhaLog = sprintf(
            "[%s] [%s] %s | IP: %s | %s\n",
            $timestamp,
            strtoupper($tipo),
            $mensagem,
            $ip,
            json_encode($dados, JSON_UNESCAPED_UNICODE)
        );
        
        // Escrever no arquivo de log
        $resultado = @file_put_contents($arquivoLog, $linhaLog, FILE_APPEND | LOCK_EX);
        
        if ($resultado === false) {
            error_log("Erro ao escrever no arquivo de log: " . $arquivoLog);
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erro no sistema de log: " . $e->getMessage());
        return false;
    }
}

/**
 * Função para obter o IP real do usuário
 * @return string
 */
function obterIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Se houver múltiplos IPs, pegar o primeiro
    if (strpos($ip, ',') !== false) {
        $ip = explode(',', $ip)[0];
    }
    
    return trim($ip);
}

/**
 * Função para registrar tentativa de login
 * @param string $usuario Nome do usuário
 * @param bool $sucesso Se o login foi bem-sucedido
 * @param string $motivo Motivo da falha (se houver)
 * @param array $detalhes Detalhes adicionais (password_verify result, etc.)
 */
function registrarLogin($usuario, $sucesso, $motivo = '', $detalhes = []) {
    $tipo = $sucesso ? 'SUCCESS' : 'FAILED';
    $mensagem = $sucesso 
        ? "Login realizado com sucesso para o usuário: {$usuario}"
        : "Tentativa de login falhou para o usuário: {$usuario}";
    
    if (!$sucesso && $motivo) {
        $mensagem .= " - Motivo: {$motivo}";
    }
    
    $dadosLog = [
        'usuario' => $usuario,
        'sucesso' => $sucesso,
        'motivo' => $motivo
    ];
    
    // Mesclar detalhes adicionais se fornecidos
    if (!empty($detalhes)) {
        $dadosLog = array_merge($dadosLog, $detalhes);
    }
    
    registrarLog('LOGIN', $mensagem, $dadosLog);
}

/**
 * Função simplificada para registrar erros
 * @param string $mensagem Mensagem de erro
 * @param array $dados Dados adicionais do erro
 * @param Exception|null $exception Exceção capturada (opcional)
 */
function logError($mensagem, $dados = [], $exception = null) {
    $dadosErro = $dados;
    
    // Se uma exceção foi fornecida, adicionar informações dela
    if ($exception !== null) {
        $dadosErro = array_merge($dadosErro, [
            'exception_message' => $exception->getMessage(),
            'exception_code' => $exception->getCode(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'exception_trace' => $exception->getTraceAsString(),
            'exception_class' => get_class($exception)
        ]);
    }
    
    // Adicionar informações do contexto da requisição
    $dadosErro = array_merge($dadosErro, [
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'post_data' => !empty($_POST) ? $_POST : null,
        'get_data' => !empty($_GET) ? $_GET : null
    ]);
    
    // Registrar usando a função principal
    registrarLog('ERRO', $mensagem, $dadosErro);
    
    // Também registrar no error_log do PHP como fallback
    error_log("[SISIPTU ERRO] " . $mensagem . " | " . json_encode($dadosErro, JSON_UNESCAPED_UNICODE));
}
?>

