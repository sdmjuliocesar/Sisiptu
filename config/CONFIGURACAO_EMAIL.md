# 📧 Configuração de Email (Sendmail) - XAMPP Windows

Este guia explica como configurar o envio de emails no XAMPP para Windows.

## 🔧 Opções de Configuração

### **Opção 1: Usar SMTP Direto (Recomendado para Produção)**

Esta é a melhor opção para produção. Usa um servidor SMTP real (Gmail, Outlook, etc.).

#### Passo 1: Instalar PHPMailer (Recomendado)

```bash
composer require phpmailer/phpmailer
```

Ou baixe manualmente de: https://github.com/PHPMailer/PHPMailer

#### Passo 2: Configurar no código PHP

Atualize a função `enviarEmailExtrato()` em `php/extrato_api.php` para usar SMTP.

---

### **Opção 2: Fake Sendmail (Mais Simples para Testes)**

Esta opção simula o sendmail no Windows.

#### Passo 1: Baixar Fake Sendmail

1. Baixe o arquivo `sendmail.zip` de: https://www.glob.com.au/sendmail/
2. Extraia na pasta: `C:\xampp\sendmail\`
3. Você terá: `C:\xampp\sendmail\sendmail.exe`

#### Passo 2: Configurar php.ini

1. Abra o arquivo `php.ini` (geralmente em `C:\xampp\php\php.ini`)
2. Localize a seção `[mail function]`
3. Configure assim:

```ini
[mail function]
; Para Windows
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = seu-email@gmail.com

; Caminho do sendmail (Fake Sendmail)
sendmail_path = "C:\xampp\sendmail\sendmail.exe -t"

; Ou use o Mercury Mail (se instalado)
; sendmail_path = "C:\xampp\MercuryMail\sendmail.exe -t"
```

#### Passo 3: Configurar sendmail.ini

Crie/edite o arquivo `C:\xampp\sendmail\sendmail.ini`:

```ini
[sendmail]
; Para Gmail
smtp_server=smtp.gmail.com
smtp_port=587
error_logfile=error.log
debug_logfile=debug.log
auth_username=seu-email@gmail.com
auth_password=sua-senha-app
force_sender=seu-email@gmail.com

; Para Outlook/Hotmail
; smtp_server=smtp-mail.outlook.com
; smtp_port=587
; auth_username=seu-email@outlook.com
; auth_password=sua-senha

; Para servidor SMTP próprio
; smtp_server=mail.seudominio.com.br
; smtp_port=587
; auth_username=seu-email@seudominio.com.br
; auth_password=sua-senha
```

**⚠️ IMPORTANTE para Gmail:**
- Use uma **Senha de App** (não a senha normal)
- Ative a verificação em 2 etapas
- Gere senha de app em: https://myaccount.google.com/apppasswords

#### Passo 4: Reiniciar Apache

Reinicie o Apache no painel do XAMPP.

---

### **Opção 3: Mercury Mail (Incluído no XAMPP)**

O XAMPP inclui o Mercury Mail, mas requer configuração adicional.

#### Passo 1: Iniciar Mercury Mail

1. Abra o painel do XAMPP
2. Clique em "Config" ao lado de Mercury
3. Configure o servidor SMTP

#### Passo 2: Configurar php.ini

```ini
[mail function]
sendmail_path = "C:\xampp\MercuryMail\sendmail.exe -t"
```

---

## 🧪 Testar Configuração

### Teste 1: Script PHP de Teste

Crie o arquivo `test_email.php` na raiz do projeto:

```php
<?php
$para = "seu-email-teste@gmail.com";
$assunto = "Teste de Email";
$mensagem = "Este é um teste de envio de email do sistema.";
$headers = "From: sistema@iptu.com\r\n";
$headers .= "Reply-To: sistema@iptu.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

if (mail($para, $assunto, $mensagem, $headers)) {
    echo "✅ Email enviado com sucesso!";
} else {
    echo "❌ Erro ao enviar email.";
    echo "<br>Verifique os logs em: C:\\xampp\\sendmail\\error.log";
}
?>
```

Acesse: `http://localhost/SISIPTU/test_email.php`

### Teste 2: Verificar Logs

Se usar Fake Sendmail, verifique os logs:
- `C:\xampp\sendmail\error.log` - Erros
- `C:\xampp\sendmail\debug.log` - Debug

### Teste 3: Verificar Configuração PHP

Crie `phpinfo_email.php`:

```php
<?php
phpinfo();
?>
```

Procure pela seção `mail function` e verifique:
- `sendmail_path`
- `SMTP`
- `smtp_port`
- `sendmail_from`

---

## 🔒 Configurações de Segurança

### Para Gmail:

1. **Ative a verificação em 2 etapas**
2. **Gere uma Senha de App:**
   - Acesse: https://myaccount.google.com/apppasswords
   - Selecione "Email" e "Outro (nome personalizado)"
   - Digite "SISIPTU"
   - Use a senha gerada no `sendmail.ini`

### Para Outlook/Hotmail:

1. Use a senha normal da conta
2. Pode ser necessário ativar "Aplicativos menos seguros" (não recomendado)

---

## 🚀 Configuração Recomendada para Produção

Para produção, recomenda-se usar **PHPMailer com SMTP**:

1. **Mais confiável**
2. **Melhor tratamento de erros**
3. **Suporte a TLS/SSL**
4. **Não depende de sendmail**

Exemplo de implementação com PHPMailer:

```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'seu-email@gmail.com';
    $mail->Password = 'sua-senha-app';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    $mail->setFrom('sistema@iptu.com', 'Sistema IPTU');
    $mail->addAddress($emailDestino);
    $mail->addAttachment($arquivoAnexo);
    
    $mail->Subject = "Extrato de IPTU - Contrato {$contrato}";
    $mail->Body = $mensagemTexto;
    
    $mail->send();
    return ['sucesso' => true, 'mensagem' => 'Email enviado!'];
} catch (Exception $e) {
    return ['sucesso' => false, 'mensagem' => $mail->ErrorInfo];
}
```

---

## ❌ Solução de Problemas

### Erro: "mail() function not available"

**Solução:** Verifique se a função `mail()` está habilitada no `php.ini`:
```ini
disable_functions = ; (remova mail da lista se estiver)
```

### Erro: "Could not instantiate mail function"

**Solução:** 
1. Verifique o caminho do `sendmail_path` no `php.ini`
2. Verifique se o arquivo `sendmail.exe` existe
3. Verifique permissões da pasta

### Email não chega / Vai para spam

**Soluções:**
1. Verifique a pasta de spam
2. Configure SPF/DKIM no servidor (produção)
3. Use um email válido no `From:`
4. Evite palavras como "teste", "spam" no assunto

### Erro de autenticação (Gmail)

**Solução:**
- Use Senha de App (não senha normal)
- Verifique se a verificação em 2 etapas está ativa
- Tente gerar nova senha de app

---

## 📝 Checklist de Configuração

- [ ] Sendmail instalado ou SMTP configurado
- [ ] `php.ini` configurado corretamente
- [ ] `sendmail.ini` configurado (se usar Fake Sendmail)
- [ ] Credenciais de email configuradas
- [ ] Apache reiniciado
- [ ] Teste de envio realizado
- [ ] Logs verificados (se houver erro)

---

## 📞 Suporte

Se continuar com problemas:
1. Verifique os logs do sendmail
2. Verifique os logs do Apache/PHP
3. Teste com script simples primeiro
4. Considere usar PHPMailer para mais controle

