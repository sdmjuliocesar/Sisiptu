<?php
session_start();

// Incluir arquivos de configuração
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/logger.php';

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';
    
    // Validação básica
    if (empty($usuario) || empty($senha)) {
        // Registrar tentativa de login com campos vazios
        registrarLogin($usuario ?: 'N/A', false, 'Campos vazios');
        
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Por favor, preencha todos os campos.'
        ]);
        exit;
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
            registrarLog('LOGIN', "Login realizado com sucesso para o usuário: {$usuario}", array_merge([
                'usuario' => $usuario,
                'sucesso' => true,
                'motivo' => 'Senha correta (comparação direta)'
            ], $detalhesVerificacao));
            
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Login realizado com sucesso!',
                'redirect' => '/SISIPTU/dashboard.php'
            ]);
        } else {
            // Credenciais inválidas - registrar detalhes da verificação
            $motivo = !$usuarioEncontrado 
                ? 'Usuário não encontrado no banco de dados' 
                : 'Senha incorreta (comparação direta)';
            
            registrarLog('LOGIN', "Tentativa de login falhou para o usuário: {$usuario} - {$motivo}", array_merge([
                'usuario' => $usuario,
                'sucesso' => false,
                'motivo' => $motivo
            ], $detalhesVerificacao));
            
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Usuário ou senha incorretos.'
            ]);
        }
        
    } catch (PDOException $e) {
        // Registrar erro de conexão com o banco
        $erroMsg = 'Erro na conexão com banco de dados: ' . $e->getMessage();
        registrarLog('ERRO', $erroMsg, [
            'usuario' => $usuario,
            'erro' => $e->getMessage(),
            'codigo' => $e->getCode()
        ]);
        
        // Em caso de erro na conexão, usar autenticação simples para demonstração
        // REMOVER EM PRODUÇÃO - usar apenas banco de dados
        
        // Autenticação simples para demonstração (usuário: admin, senha: admin123)
        if ($usuario === 'admin' && $senha === 'admin123') {
            $_SESSION['usuario_id'] = 1;
            $_SESSION['usuario'] = 'admin';
            $_SESSION['logado'] = true;
            
            // Registrar login bem-sucedido (modo fallback)
            registrarLogin($usuario, true, 'Autenticação via fallback - Erro no BD: ' . $e->getMessage());
            
            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Login realizado com sucesso!',
                'redirect' => '/SISIPTU/dashboard.php'
            ]);
        } else {
            // Registrar tentativa falha
            registrarLogin($usuario, false, 'Erro na conexão com banco de dados: ' . $e->getMessage());
            
            echo json_encode([
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
                
                echo json_encode([
                    'sucesso' => true,
                    'mensagem' => 'Login realizado com sucesso! (Aviso: Erro no sistema de logs)',
                    'redirect' => '/SISIPTU/dashboard.php'
                ]);
                exit;
            }
        }
        
        // Registrar tentativa de login falha devido ao erro
        try {
            registrarLogin($usuario, false, 'Erro geral: ' . $e->getMessage(), $dadosErro);
        } catch (Exception $logEx) {
            error_log("[LOGIN] Erro ao registrar login falho: " . $logEx->getMessage());
        }
        
        echo json_encode([
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

