<?php
/**
 * Sistema de logs do SISIPTU
 *
 * - Mantém compatibilidade com funções usadas no código:
 *   - registrarLog($tipo, $mensagem, array $dados = [])
 *   - registrarLogin($usuario, $sucesso, $motivo = '', array $dados = [])
 *   - logError($mensagem, array $contexto = [], ?Throwable $exception = null)
 *
 * Observação: este arquivo não deve gerar saída (echo/print).
 */

// Diretório base de logs (na raiz do projeto)
if (!defined('LOG_DIR')) {
    define('LOG_DIR', __DIR__ . '/../logs/');
}

/**
 * Garante que o diretório de logs existe.
 */
function garantirDiretorioLogs(): bool
{
    if (is_dir(LOG_DIR)) {
        return true;
    }

    // Tentar criar diretório (recursivo)
    return @mkdir(LOG_DIR, 0755, true) || is_dir(LOG_DIR);
}

/**
 * Sanitiza dados potencialmente sensíveis antes de logar.
 */
function sanitizarDadosLog(array $dados): array
{
    $chavesSensiveis = [
        'senha', 'password', 'pass', 'pwd',
        'token', 'authorization', 'auth', 'cookie',
        'db_pass', 'DB_PASS',
    ];

    foreach ($dados as $k => $v) {
        if (is_string($k)) {
            $keyLower = strtolower($k);
            foreach ($chavesSensiveis as $sensivel) {
                $sensivelLower = strtolower($sensivel);
                if ($keyLower === $sensivelLower || str_contains($keyLower, $sensivelLower)) {
                    $dados[$k] = '[REDACTED]';
                    continue 2;
                }
            }
        }

        // Evitar objetos/arrays muito grandes ou recursivos
        if (is_object($v)) {
            $dados[$k] = '[object ' . get_class($v) . ']';
        }
    }

    return $dados;
}

/**
 * Registra uma linha de log no arquivo diário.
 */
function registrarLog(string $tipo, string $mensagem, array $dados = []): bool
{
    try {
        if (!garantirDiretorioLogs()) {
            // Fallback para error_log do PHP se não conseguir criar pasta
            error_log("[SISIPTU][{$tipo}] Falha ao criar/acessar LOG_DIR: " . LOG_DIR);
            error_log("[SISIPTU][{$tipo}] " . $mensagem);
            return false;
        }

        // Direcionar error_log do PHP para um arquivo central (útil para fatal errors fora de try/catch)
        @ini_set('log_errors', '1');
        @ini_set('error_log', LOG_DIR . 'php_errors.log');

        $agora = date('Y-m-d H:i:s');
        $arquivo = LOG_DIR . 'sisiptu-' . date('Y-m-d') . '.log';

        $payload = [
            'ts' => $agora,
            'tipo' => $tipo,
            'mensagem' => $mensagem,
        ];

        if (!empty($dados)) {
            $payload['dados'] = sanitizarDadosLog($dados);
        }

        // Incluir dados básicos do request quando disponíveis
        if (PHP_SAPI !== 'cli') {
            $payload['req'] = [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                'uri' => $_SERVER['REQUEST_URI'] ?? null,
            ];
        }

        $linha = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($linha === false) {
            $linha = '{"ts":"' . $agora . '","tipo":"' . $tipo . '","mensagem":"Falha ao codificar JSON do log"}';
        }

        $linha .= PHP_EOL;

        return file_put_contents($arquivo, $linha, FILE_APPEND | LOCK_EX) !== false;
    } catch (Throwable $e) {
        // Nunca deixar o log quebrar o fluxo da aplicação
        error_log("[SISIPTU][LOGGER] Exceção ao registrar log: " . $e->getMessage());
        return false;
    }
}

/**
 * Atalho para logar erros com contexto e exceção.
 */
function logError(string $mensagem, array $contexto = [], ?Throwable $exception = null): bool
{
    if ($exception) {
        $contexto = array_merge($contexto, [
            'exception' => [
                'tipo' => get_class($exception),
                'mensagem' => $exception->getMessage(),
                'codigo' => $exception->getCode(),
                'arquivo' => $exception->getFile(),
                'linha' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ],
        ]);
    }

    return registrarLog('ERRO', $mensagem, $contexto);
}

/**
 * Log específico de login (sem registrar senha).
 */
function registrarLogin(string $usuario, bool $sucesso, string $motivo = '', array $dados = []): bool
{
    $base = [
        'usuario' => $usuario,
        'sucesso' => $sucesso,
    ];
    if ($motivo !== '') {
        $base['motivo'] = $motivo;
    }

    // Não aceitar registrar qualquer campo "senha" que por acaso venha em $dados
    unset($dados['senha'], $dados['password'], $dados['pass']);

    return registrarLog('LOGIN', 'Evento de login', array_merge($base, $dados));
}

