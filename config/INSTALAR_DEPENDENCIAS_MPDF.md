# 📦 Instalar Dependências do mPDF

O mPDF requer algumas dependências que não vêm com a instalação manual. Você precisa instalá-las.

## ⚠️ Problema

O mPDF precisa das seguintes dependências:
- `myclabs/deep-copy`
- `paragonie/random_compat`
- `php-http/message-factory`
- `psr/http-message`
- `psr/log`
- `setasign/fpdi`

## 🔧 Solução: Instalar via Composer (Recomendado)

### Passo 1: Instalar Composer

1. Baixe: https://getcomposer.org/Composer-Setup.exe
2. Execute o instalador
3. Siga as instruções

### Passo 2: Instalar mPDF com dependências

```bash
cd C:\xampp\htdocs\SISIPTU
composer require mpdf/mpdf
```

Isso instalará o mPDF **com todas as dependências necessárias**.

---

## 🔄 Alternativa: Instalação Manual das Dependências

Se não quiser usar o Composer, você precisa baixar cada dependência manualmente:

### 1. myclabs/deep-copy
- URL: https://github.com/myclabs/DeepCopy/releases
- Copiar para: `vendor/myclabs/deep-copy/`

### 2. paragonie/random_compat
- URL: https://github.com/paragonie/random_compat/releases
- Copiar para: `vendor/paragonie/random_compat/`

### 3. setasign/fpdi
- URL: https://github.com/Setasign/FPDI/releases
- Copiar para: `vendor/setasign/fpdi/`

### 4. psr/http-message
- URL: https://github.com/php-fig/http-message/releases
- Copiar para: `vendor/psr/http-message/`

### 5. psr/log
- URL: https://github.com/php-fig/log/releases
- Copiar para: `vendor/psr/log/`

### 6. php-http/message-factory
- URL: https://github.com/php-http/message-factory/releases
- Copiar para: `vendor/php-http/message-factory/`

---

## ✅ Verificação

Após instalar, verifique os logs:
- `logs/erro_*.log` - deve mostrar se o PDF foi gerado

---

## 💡 Recomendação

**Use o Composer!** É muito mais fácil e garante que todas as dependências corretas sejam instaladas.

