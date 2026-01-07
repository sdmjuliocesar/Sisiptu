# Script PowerShell para instalar Composer e mPDF
# Execute como Administrador: PowerShell -ExecutionPolicy Bypass -File INSTALAR_COMPOSER_E_MPDF.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Instalador de Composer e mPDF" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verificar se o Composer já está instalado
$composerInstalled = Get-Command composer -ErrorAction SilentlyContinue

if ($composerInstalled) {
    Write-Host "✅ Composer já está instalado!" -ForegroundColor Green
    Write-Host "Versão: " -NoNewline
    composer --version
} else {
    Write-Host "📦 Composer não encontrado. Instalando..." -ForegroundColor Yellow
    
    # Verificar se o PHP está no PATH
    $phpInstalled = Get-Command php -ErrorAction SilentlyContinue
    
    if (-not $phpInstalled) {
        Write-Host "❌ PHP não encontrado no PATH!" -ForegroundColor Red
        Write-Host "Por favor, adicione o PHP do XAMPP ao PATH do sistema." -ForegroundColor Yellow
        Write-Host "Caminho típico: C:\xampp\php" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Ou execute este comando manualmente:" -ForegroundColor Yellow
        Write-Host '$env:Path += ";C:\xampp\php"' -ForegroundColor Cyan
        exit 1
    }
    
    Write-Host "✅ PHP encontrado!" -ForegroundColor Green
    php --version
    Write-Host ""
    
    # Baixar e instalar Composer
    Write-Host "📥 Baixando instalador do Composer..." -ForegroundColor Yellow
    
    $composerInstaller = "$env:TEMP\composer-setup.php"
    $composerUrl = "https://getcomposer.org/installer"
    
    try {
        Invoke-WebRequest -Uri $composerUrl -OutFile $composerInstaller -UseBasicParsing
        Write-Host "✅ Download concluído!" -ForegroundColor Green
        
        Write-Host "🔧 Instalando Composer..." -ForegroundColor Yellow
        php $composerInstaller
        
        # Mover composer.phar para pasta do projeto ou PATH global
        if (Test-Path "composer.phar") {
            Write-Host "✅ Composer instalado localmente!" -ForegroundColor Green
            Write-Host "Para usar globalmente, mova composer.phar para uma pasta no PATH" -ForegroundColor Yellow
        }
        
        Remove-Item $composerInstaller -ErrorAction SilentlyContinue
    } catch {
        Write-Host "❌ Erro ao baixar/instalar Composer: $_" -ForegroundColor Red
        Write-Host ""
        Write-Host "Instalação manual:" -ForegroundColor Yellow
        Write-Host "1. Baixe: https://getcomposer.org/Composer-Setup.exe" -ForegroundColor Cyan
        Write-Host "2. Execute o instalador" -ForegroundColor Cyan
        exit 1
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Instalando mPDF..." -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Navegar para o diretório do projeto
$projectPath = Split-Path -Parent $PSScriptRoot
Set-Location $projectPath

Write-Host "📂 Diretório do projeto: $projectPath" -ForegroundColor Yellow
Write-Host ""

# Verificar se composer.phar existe localmente
if (Test-Path "composer.phar") {
    Write-Host "📦 Usando Composer local (composer.phar)..." -ForegroundColor Yellow
    php composer.phar require mpdf/mpdf
} elseif ($composerInstalled) {
    Write-Host "📦 Usando Composer global..." -ForegroundColor Yellow
    composer require mpdf/mpdf
} else {
    Write-Host "❌ Composer não encontrado!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Opções:" -ForegroundColor Yellow
    Write-Host "1. Instale o Composer manualmente: https://getcomposer.org/download/" -ForegroundColor Cyan
    Write-Host "2. Ou use a instalação manual do mPDF (veja INSTALAR_BIBLIOTECA_PDF.md)" -ForegroundColor Cyan
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Verificação" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verificar se mPDF foi instalado
if (Test-Path "vendor\mpdf\mpdf") {
    Write-Host "✅ mPDF instalado com sucesso!" -ForegroundColor Green
    Write-Host "Localização: vendor\mpdf\mpdf" -ForegroundColor Green
} else {
    Write-Host "⚠️  mPDF pode não ter sido instalado corretamente." -ForegroundColor Yellow
    Write-Host "Verifique os erros acima." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "✅ Concluído!" -ForegroundColor Green
Write-Host ""
Write-Host "Próximos passos:" -ForegroundColor Yellow
Write-Host "1. Reinicie o Apache no XAMPP" -ForegroundColor Cyan
Write-Host "2. Teste o envio de email com extrato" -ForegroundColor Cyan
Write-Host "3. Verifique se o PDF está sendo anexado" -ForegroundColor Cyan
Write-Host ""





