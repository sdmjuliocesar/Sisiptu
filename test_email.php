<?php
/**
 * Script de teste para envio de email
 * Acesse: http://localhost/SISIPTU/test_email.php
 */

// Configurações - AJUSTE AQUI
$emailDestino = "sdmjuliocesar@gmail.com"; // Seu email para teste
$emailRemetente = "sdmjuliocesar@gmail.com"; // Email remetente

echo "<h2>🧪 Teste de Envio de Email</h2>";
echo "<hr>";

// Verificar se a função mail() está disponível
if (!function_exists('mail')) {
    echo "<p style='color: red;'>❌ <strong>ERRO:</strong> A função mail() não está disponível no PHP.</p>";
    echo "<p>Verifique o arquivo php.ini e remova 'mail' da lista de disable_functions se necessário.</p>";
    exit;
}

echo "<p>✅ Função mail() está disponível.</p>";

// Verificar configurações do PHP
echo "<h3>📋 Configurações Atuais:</h3>";
echo "<ul>";
echo "<li><strong>SMTP:</strong> " . ini_get('SMTP') . "</li>";
echo "<li><strong>smtp_port:</strong> " . ini_get('smtp_port') . "</li>";
echo "<li><strong>sendmail_from:</strong> " . ini_get('sendmail_from') . "</li>";
echo "<li><strong>sendmail_path:</strong> " . ini_get('sendmail_path') . "</li>";
echo "</ul>";

// Teste de envio
echo "<h3>📧 Testando Envio de Email...</h3>";

$assunto = "Teste de Email - Sistema IPTU";
$mensagem = "Este é um email de teste do sistema IPTU.\n\n";
$mensagem .= "Se você recebeu este email, a configuração está funcionando corretamente!\n\n";
$mensagem .= "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
$mensagem .= "Servidor: " . $_SERVER['SERVER_NAME'] . "\n";

$headers = "From: {$emailRemetente}\r\n";
$headers .= "Reply-To: {$emailRemetente}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

// Tentar enviar
$resultado = @mail($emailDestino, $assunto, $mensagem, $headers);

if ($resultado) {
    echo "<p style='color: green; font-size: 18px;'>✅ <strong>SUCESSO!</strong> Email enviado com sucesso!</p>";
    echo "<p>Verifique a caixa de entrada (e spam) do email: <strong>{$emailDestino}</strong></p>";
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ <strong>ERRO:</strong> Falha ao enviar email.</p>";
    echo "<h4>Possíveis causas:</h4>";
    echo "<ul>";
    echo "<li>Sendmail não configurado corretamente</li>";
    echo "<li>Credenciais SMTP incorretas</li>";
    echo "<li>Servidor SMTP inacessível</li>";
    echo "<li>Firewall bloqueando conexão</li>";
    echo "</ul>";
    
    // Verificar se existe arquivo de log do sendmail
    $logPath = "C:\\xampp\\sendmail\\error.log";
    if (file_exists($logPath)) {
        echo "<h4>📄 Últimas linhas do log de erro:</h4>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        $lines = file($logPath);
        $lastLines = array_slice($lines, -10);
        echo htmlspecialchars(implode('', $lastLines));
        echo "</pre>";
    } else {
        echo "<p><em>Log não encontrado em: {$logPath}</em></p>";
    }
}

echo "<hr>";
echo "<h3>📝 Próximos Passos:</h3>";
echo "<ol>";
echo "<li>Se o email foi enviado: ✅ Configuração está correta!</li>";
echo "<li>Se houve erro: Verifique o arquivo <code>config/CONFIGURACAO_EMAIL.md</code> para instruções detalhadas</li>";
echo "<li>Verifique os logs em <code>C:\\xampp\\sendmail\\error.log</code></li>";
echo "<li>Teste com um email real antes de usar em produção</li>";
echo "</ol>";

echo "<hr>";
echo "<p><small>Para mais informações, consulte: <code>config/CONFIGURACAO_EMAIL.md</code></small></p>";
?>

