# 📄 Instalação de Biblioteca PDF

Para gerar extratos em PDF, é necessário instalar uma biblioteca PHP. O sistema suporta as seguintes bibliotecas:

## 🔧 Opções Disponíveis

### **Opção 1: mPDF (Recomendado)**

mPDF é uma biblioteca popular e fácil de usar.

#### Instalação via Composer:

```bash
cd C:\xampp\htdocs\SISIPTU
composer require mpdf/mpdf
```

#### Instalação Manual:

1. Baixe mPDF de: https://github.com/mpdf/mpdf/releases
2. Extraia na pasta: `C:\xampp\htdocs\SISIPTU\vendor\mpdf\mpdf\`
3. Inclua no arquivo `php/extrato_api.php`:

```php
require_once __DIR__ . '/../vendor/mpdf/mpdf/src/Mpdf.php';
```

---

### **Opção 2: DomPDF**

DomPDF é outra opção popular.

#### Instalação via Composer:

```bash
cd C:\xampp\htdocs\SISIPTU
composer require dompdf/dompdf
```

#### Instalação Manual:

1. Baixe DomPDF de: https://github.com/dompdf/dompdf/releases
2. Extraia na pasta: `C:\xampp\htdocs\SISIPTU\vendor\dompdf\dompdf\`
3. Inclua no arquivo `php/extrato_api.php`:

```php
require_once __DIR__ . '/../vendor/dompdf/dompdf/autoload.inc.php';
```

---

### **Opção 3: TCPDF**

TCPDF é uma biblioteca mais antiga mas ainda funcional.

#### Instalação via Composer:

```bash
cd C:\xampp\htdocs\SISIPTU
composer require tecnickcom/tcpdf
```

#### Instalação Manual:

1. Baixe TCPDF de: https://github.com/tecnickcom/TCPDF/releases
2. Extraia na pasta: `C:\xampp\htdocs\SISIPTU\vendor\tecnickcom\tcpdf\`
3. Inclua no arquivo `php/extrato_api.php`:

```php
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
```

---

## 📝 Verificação

Após instalar uma biblioteca, verifique se está funcionando:

1. Acesse a tela de Consulta de Cobranças
2. Gere um extrato
3. Envie por email
4. Verifique se o email contém dois anexos:
   - Arquivo HTML
   - Arquivo PDF

---

## ⚠️ Nota Importante

Se nenhuma biblioteca estiver instalada:
- O sistema continuará funcionando normalmente
- Apenas o arquivo HTML será enviado por email
- O arquivo PDF não será gerado

---

## 🚀 Recomendação

**Recomendamos usar mPDF** por ser:
- Fácil de instalar
- Bem documentado
- Suporta bem caracteres especiais (acentos)
- Boa qualidade de saída

---

## 📞 Suporte

Se tiver problemas na instalação:
1. Verifique se o Composer está instalado
2. Verifique as permissões da pasta `vendor`
3. Verifique os logs em `logs/erro_*.log`









