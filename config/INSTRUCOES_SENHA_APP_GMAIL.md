# 🔐 Como Gerar Senha de Aplicativo do Gmail

O erro que você está recebendo indica que o Gmail precisa de uma **Senha de Aplicativo** ao invés da senha normal da conta.

## ⚠️ Erro Atual:
```
Application-specific password required. For more information, go to
https://support.google.com/mail/?p=InvalidSecondFactor
```

## 📋 Passo a Passo para Gerar Senha de Aplicativo

### Passo 1: Ativar Verificação em 2 Etapas (se ainda não tiver)

1. Acesse: https://myaccount.google.com/security
2. Procure por "Verificação em duas etapas"
3. Clique em "Ativar" e siga as instruções
4. Você precisará confirmar com seu telefone

### Passo 2: Gerar Senha de Aplicativo

1. **Acesse diretamente:**
   - https://myaccount.google.com/apppasswords
   - Ou vá em: Conta Google → Segurança → Senhas de app

2. **Selecione as opções:**
   - **App:** Selecione "Email"
   - **Dispositivo:** Selecione "Outro (nome personalizado)"
   - **Nome:** Digite "SISIPTU" ou "XAMPP Sendmail"

3. **Clique em "Gerar"**

4. **Copie a senha gerada:**
   - Será uma senha de 16 caracteres (sem espaços)
   - Exemplo: `abcd efgh ijkl mnop`
   - Use sem espaços: `abcdefghijklmnop`

### Passo 3: Atualizar sendmail.ini

1. Abra o arquivo: `C:\xampp\sendmail\sendmail.ini`

2. Substitua a linha:
   ```ini
   auth_password=Linda1607*
   ```
   
   Por:
   ```ini
   auth_password=abcdefghijklmnop
   ```
   (Use a senha de 16 caracteres que você copiou)

3. **Salve o arquivo**

4. **Reinicie o Apache** no painel do XAMPP

### Passo 4: Testar Novamente

1. Acesse: `http://localhost/SISIPTU/test_email.php`
2. Verifique se o email foi enviado com sucesso

---

## 🔄 Alternativa: Desativar Verificação em 2 Etapas (NÃO RECOMENDADO)

Se você não quiser usar verificação em 2 etapas:

1. Acesse: https://myaccount.google.com/security
2. Desative "Verificação em duas etapas"
3. Use a senha normal no `sendmail.ini`

⚠️ **ATENÇÃO:** Isso reduz a segurança da sua conta Google. É melhor usar Senha de Aplicativo.

---

## ❓ Problemas Comuns

### "Não consigo acessar a página de senhas de app"

**Causa:** Verificação em 2 etapas não está ativada

**Solução:** Ative primeiro a verificação em 2 etapas (Passo 1 acima)

### "A senha não funciona"

**Causas possíveis:**
1. Copiou com espaços - remova todos os espaços
2. Copiou caracteres errados - copie novamente
3. Senha expirada - gere uma nova

**Solução:** Gere uma nova senha de aplicativo

### "Ainda recebo o mesmo erro"

**Soluções:**
1. Verifique se salvou o arquivo `sendmail.ini`
2. Reinicie o Apache
3. Verifique se o caminho está correto: `C:\xampp\sendmail\sendmail.ini`
4. Verifique os logs em: `C:\xampp\sendmail\error.log`

---

## 📝 Resumo Rápido

1. ✅ Ative verificação em 2 etapas no Gmail
2. ✅ Gere senha de aplicativo em: https://myaccount.google.com/apppasswords
3. ✅ Copie a senha de 16 caracteres (sem espaços)
4. ✅ Cole no arquivo `C:\xampp\sendmail\sendmail.ini` na linha `auth_password=`
5. ✅ Reinicie o Apache
6. ✅ Teste novamente

---

## 🔗 Links Úteis

- Gerar Senha de App: https://myaccount.google.com/apppasswords
- Segurança da Conta: https://myaccount.google.com/security
- Ajuda do Gmail: https://support.google.com/mail/?p=InvalidSecondFactor




