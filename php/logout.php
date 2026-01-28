<?php
session_start();

// Registrar logout no log
if (isset($_SESSION['usuario'])) {
    require_once __DIR__ . '/logger.php';
    
    $tempoLogado = 0;
    if (isset($_SESSION['login_time'])) {
        $tempoLogado = time() - $_SESSION['login_time'];
    }
    
    registrarLog('LOGOUT', "Usuário {$_SESSION['usuario']} fez logout", [
        'usuario' => $_SESSION['usuario'],
        'tempo_logado_segundos' => $tempoLogado,
        'tempo_logado_formatado' => gmdate('H:i:s', $tempoLogado)
    ]);
}

// Destruir sessão
session_unset();
session_destroy();

// Redirecionar para página de login
header('Location: /SISIPTU/index.html');
exit;
?>


