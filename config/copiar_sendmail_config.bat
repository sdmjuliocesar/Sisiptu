@echo off
REM ============================================
REM Script para copiar configuração do sendmail
REM ============================================

echo.
echo ============================================
echo Copiando configuracao do sendmail...
echo ============================================
echo.

REM Verificar se a pasta sendmail existe
if not exist "C:\xampp\sendmail\" (
    echo Criando pasta C:\xampp\sendmail\...
    mkdir "C:\xampp\sendmail\"
)

REM Copiar arquivo de configuração
if exist "%~dp0sendmail.ini" (
    echo Copiando sendmail.ini para C:\xampp\sendmail\...
    copy /Y "%~dp0sendmail.ini" "C:\xampp\sendmail\sendmail.ini"
    echo.
    echo ✅ Arquivo copiado com sucesso!
    echo.
    echo IMPORTANTE:
    echo 1. Verifique se a senha no arquivo esta correta
    echo 2. Se usar Gmail, gere uma SENHA DE APLICATIVO
    echo    Acesse: https://myaccount.google.com/apppasswords
    echo 3. Reinicie o Apache no XAMPP
    echo.
) else (
    echo ❌ ERRO: Arquivo sendmail.ini nao encontrado!
    echo Verifique se o arquivo existe em: %~dp0sendmail.ini
    echo.
)

pause

