# 📄 Instalação Manual do mPDF (Sem Composer)

Se você não tem o Composer instalado, pode instalar o mPDF manualmente.

## 📥 Passo 1: Baixar mPDF

1. Acesse: https://github.com/mpdf/mpdf/releases
2. Baixe a versão mais recente (ex: `mpdf-8.2.0.zip`)
3. Extraia o arquivo ZIP

## 📂 Passo 2: Copiar Arquivos

1. Crie a pasta `vendor` na raiz do projeto (se não existir):
   ```
   C:\xampp\htdocs\SISIPTU\vendor\
   ```

2. Copie a pasta `mpdf` extraída para:
   ```
   C:\xampp\htdocs\SISIPTU\vendor\mpdf\mpdf\
   ```

3. A estrutura final deve ser:
   ```
   SISIPTU/
   └── vendor/
       └── mpdf/
           └── mpdf/
               ├── src/
               │   └── Mpdf.php
               ├── data/
               └── ...
   ```

## 🔧 Passo 3: Verificar Instalação

Após copiar os arquivos, o sistema deve detectar automaticamente o mPDF.

## ✅ Teste

1. Acesse a tela de Consulta de Cobranças
2. Gere um extrato
3. Envie por email
4. Verifique se o email contém o arquivo PDF anexado

---

## 🚀 Alternativa: Instalar Composer

Se preferir usar o Composer (recomendado):

### Opção A: Instalador Windows
1. Baixe: https://getcomposer.org/Composer-Setup.exe
2. Execute o instalador
3. Siga as instruções

### Opção B: Via PowerShell (Script)
Execute o script: `config/INSTALAR_COMPOSER_E_MPDF.ps1`

```powershell
# Como Administrador
PowerShell -ExecutionPolicy Bypass -File config/INSTALAR_COMPOSER_E_MPDF.ps1
```

---

## ❓ Problemas Comuns

### Erro: "Class 'Mpdf\Mpdf' not found"
- Verifique se a pasta está em: `vendor/mpdf/mpdf/`
- Verifique se o arquivo `src/Mpdf.php` existe
- Reinicie o Apache

### Erro: "Permission denied"
- Verifique permissões da pasta `vendor`
- Execute como Administrador se necessário

---

## 📞 Suporte

Se continuar com problemas, verifique os logs:
- `logs/erro_*.log`

