<?php
// Iniciar buffer de saída para capturar qualquer output inesperado
ob_start();

// Desabilitar exibição de erros no output (mas manter no log)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();

// Incluir arquivos de configuração
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../config/logger.php';

// Limpar qualquer output capturado antes de definir o header JSON
ob_clean();

// Definir header JSON
header('Content-Type: application/json; charset=utf-8');

/**
 * Função helper para retornar JSON e encerrar o script
 */
function retornarJson($dados) {
    ob_clean(); // Limpar qualquer output antes de enviar JSON
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    ob_end_flush();
    exit;
}

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
    
    // Validação básica
    if (empty($usuario) || empty($senha)) {
        // Registrar tentativa de login com campos vazios
        try {
            registrarLogin($usuario ?: 'N/A', false, 'Campos vazios');
        } catch (Exception $e) {
            // Ignorar erros de log, não devem impedir a resposta
        }
        
        retornarJson([
            'sucesso' => false,
            'mensagem' => 'Por favor, preencha todos os campos.'
        ]);
    }
    
    try {
        // Conectar ao banco de dados
        $pdo = getConnection();
        
        // Buscar usuário no banco de dados
        $stmt = $pdo->prepare("SELECT id, usuario, senha FROM usuarios WHERE usuario = :usuario");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar se usuário foi encontrado
        $usuarioEncontrado = !empty($user);
        $senhaCorreta = false;
        $detalhesVerificacao = [];
        
        if ($usuarioEncontrado) {
            // Comparar senha diretamente (sem criptografia)
            $senhaCorreta = ($senha === $user['senha']);
            
            // Preparar detalhes para o log
            $detalhesVerificacao = [
                'usuario_encontrado' => true,
                'usuario_id' => $user['id'],
                'senha_correta' => $senhaCorreta,
                'senha_fornecida_length' => strlen($senha),
                'senha_banco_length' => strlen($user['senha']),
                'comparacao_direta' => true
            ];
        } else {
            // Usuário não encontrado
            $detalhesVerificacao = [
                'usuario_encontrado' => false,
                'senha_correta' => false,
                'senha_fornecida_length' => strlen($senha)
            ];
        }
        
        // Verificar credenciais
        if ($usuarioEncontrado && $senhaCorreta) {
            // Login bem-sucedido
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['logado'] = true;
            
            // Registrar login bem-sucedido com detalhes da verificação
            try {
                registrarLog('LOGIN', "Login realizado com sucesso para o usuário: {$usuario}", array_merge([
                    'usuario' => $usuario,
                    'sucesso' => true,
                    'motivo' => 'Senha correta (comparação direta)'
                ], $detalhesVerificacao));
            } catch (Exception $e) {
                // Ignorar erros de log, não devem impedir o login
            }
            
            retornarJson([
                'sucesso' => true,
                'mensagem' => 'Login realizado com sucesso!',
                'redirect' => '/SISIPTU/dashboard.php'
            ]);
        } else {
            // Credenciais inválidas - registrar detalhes da verificação
            $motivo = !$usuarioEncontrado 
                ? 'Usuário não encontrado no banco de dados' 
                : 'Senha incorreta (comparação direta)';
            
            try {
                registrarLog('LOGIN', "Tentativa de login falhou para o usuário: {$usuario} - {$motivo}", array_merge([
                    'usuario' => $usuario,
                    'sucesso' => false,
                    'motivo' => $motivo
                ], $detalhesVerificacao));
            } catch (Exception $e) {
                // Ignorar erros de log, não devem impedir a resposta
            }
            
            retornarJson([
                'sucesso' => false,
                'mensagem' => 'Usuário ou senha incorretos.'
            ]);
        }
        
    } catch (PDOException $e) {
        // Registrar erro de conexão com o banco
        $erroMsg = 'Erro na conexão com banco de dados: ' . $e->getMessage();
        try {
            registrarLog('ERRO', $erroMsg, [
                'usuario' => $usuario,
                'erro' => $e->getMessage(),
                'codigo' => $e->getCode()
            ]);
        } catch (Exception $logEx) {
            // Ignorar erros de log
        }
        
        // Em caso de erro na conexão, usar autenticação simples para demonstração
        // REMOVER EM PRODUÇÃO - usar apenas banco de dados
        
        // Autenticação simples para demonstração (usuário: admin, senha: admin123)
        if ($usuario === 'admin' && $senha === 'admin123') {
            $_SESSION['usuario_id'] = 1;
            $_SESSION['usuario'] = 'admin';
            $_SESSION['logado'] = true;
            
            // Registrar login bem-sucedido (modo fallback)
            try {
                registrarLogin($usuario, true, 'Autenticação via fallback - Erro no BD: ' . $e->getMessage());
            } catch (Exception $logEx) {
                // Ignorar erros de log
            }
            
            retornarJson([
                'sucesso' => true,
                'mensagem' => 'Login realizado com sucesso!',
                'redirect' => '/SISIPTU/dashboard.php'
            ]);
        } else {
            // Registrar tentativa falha
            try {
                registrarLogin($usuario, false, 'Erro na conexão com banco de dados: ' . $e->getMessage());
            } catch (Exception $logEx) {
                // Ignorar erros de log
            }
            
            retornarJson([
                'sucesso' => false,
                'mensagem' => 'Usuário ou senha incorretos.'
            ]);
        }
    } catch (Exception $e) {
        // Preparar informações detalhadas do erro
        $erroDetalhado = 'Erro geral no login: ' . $e->getMessage();
        $dadosErro = [
            'usuario' => $usuario,
            'erro' => $e->getMessage(),
            'codigo' => $e->getCode(),
            'arquivo' => $e->getFile(),
            'linha' => $e->getLine(),
            'tipo_erro' => get_class($e),
            'trace' => $e->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Tentar registrar o log (mesmo que possa falhar)
        $logRegistrado = false;
        try {
            $logRegistrado = registrarLog('ERRO', $erroDetalhado, $dadosErro);
        } catch (Exception $logException) {
            // Se falhar ao registrar log, usar error_log do PHP como fallback
            error_log("ERRO CRÍTICO NO LOGIN - Não foi possível registrar no log: " . $logException->getMessage());
            error_log("ERRO ORIGINAL: " . $erroDetalhado);
            error_log("DADOS DO ERRO: " . json_encode($dadosErro, JSON_UNESCAPED_UNICODE));
        }
        
        // Se não conseguiu registrar no log, tentar novamente com error_log
        if (!$logRegistrado) {
            error_log("[LOGIN ERRO] " . $erroDetalhado);
            error_log("[LOGIN ERRO DADOS] " . json_encode($dadosErro, JSON_UNESCAPED_UNICODE));
        }
        
        // Verificar se é erro de conexão do logger ou outro erro
        $mensagemErro = 'Erro ao processar login.';
        
        // Se for erro relacionado ao logger, não falhar o login por isso
        if (strpos($e->getMessage(), 'log') !== false || strpos($e->getMessage(), 'logger') !== false) {
            // Tentar login mesmo com erro no logger (modo fallback)
            if ($usuario === 'admin' && $senha === 'admin123') {
                $_SESSION['usuario_id'] = 1;
                $_SESSION['usuario'] = 'admin';
                $_SESSION['logado'] = true;
                
                // Registrar no error_log do PHP como fallback
                error_log("[LOGIN FALLBACK] Login realizado com erro no sistema de logs para usuário: " . $usuario);
                
                retornarJson([
                    'sucesso' => true,
                    'mensagem' => 'Login realizado com sucesso! (Aviso: Erro no sistema de logs)',
                    'redirect' => '/SISIPTU/dashboard.php'
                ]);
            }
        }
        
        // Registrar tentativa de login falha devido ao erro
        try {
            registrarLogin($usuario, false, 'Erro geral: ' . $e->getMessage(), $dadosErro);
        } catch (Exception $logEx) {
            error_log("[LOGIN] Erro ao registrar login falho: " . $logEx->getMessage());
        }
        
        retornarJson([
            'sucesso' => false,
            'mensagem' => $mensagemErro . ' Detalhes: ' . $e->getMessage()
        ]);
    }
} else {
    // Se não for POST, redirecionar para a página de login
    header('Location: ../index.html');
    exit;
}
?>

