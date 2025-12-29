# Script PowerShell para instalar Composer e mPDF
# Execute como Administrador: PowerShell -ExecutionPolicy Bypass -File instalar_composer_e_mpdf.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Instalador de Composer e mPDF" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verificar se o PHP esta no PATH
Write-Host "Verificando PHP..." -ForegroundColor Yellow
$phpPath = Get-Command php -ErrorAction SilentlyContinue

if (-not $phpPath) {
    Write-Host "PHP nao encontrado no PATH!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Adicionando PHP do XAMPP ao PATH desta sessao..." -ForegroundColor Yellow
    
    $xamppPhp = "C:\xampp\php"
    if (Test-Path $xamppPhp) {
        $env:Path = $env:Path + ";" + $xamppPhp
        Write-Host "PHP adicionado ao PATH: $xamppPhp" -ForegroundColor Green
    } else {
        Write-Host "XAMPP nao encontrado em C:\xampp\php" -ForegroundColor Red
        Write-Host "Por favor, adicione o PHP manualmente ao PATH do sistema" -ForegroundColor Yellow
        exit 1
    }
} else {
    Write-Host "PHP encontrado: $($phpPath.Source)" -ForegroundColor Green
    php --version
    Write-Host ""
}

# Verificar se Composer ja esta instalado
Write-Host "Verificando Composer..." -ForegroundColor Yellow
$composerPath = Get-Command composer -ErrorAction SilentlyContinue

if ($composerPath) {
    Write-Host "Composer ja esta instalado!" -ForegroundColor Green
    composer --version
    Write-Host ""
} else {
    Write-Host "Composer nao encontrado. Instalando..." -ForegroundColor Yellow
    Write-Host ""
    
    # Baixar instalador do Composer
    $composerInstaller = "$env:TEMP\composer-setup.php"
    $composerUrl = "https://getcomposer.org/installer"
    
    try {
        Write-Host "Baixando instalador do Composer..." -ForegroundColor Yellow
        Invoke-WebRequest -Uri $composerUrl -OutFile $composerInstaller -UseBasicParsing
        
        Write-Host "Instalando Composer..." -ForegroundColor Yellow
        php $composerInstaller
        
        # Verificar se composer.phar foi criado
        if (Test-Path "composer.phar") {
            Write-Host "Composer instalado localmente (composer.phar)" -ForegroundColor Green
        } else {
            Write-Host "composer.phar nao foi criado." -ForegroundColor Yellow
            Write-Host "Para instalacao global, baixe: https://getcomposer.org/Composer-Setup.exe" -ForegroundColor Cyan
        }
        
        Remove-Item $composerInstaller -ErrorAction SilentlyContinue
        
    } catch {
        Write-Host "Erro ao instalar Composer: $_" -ForegroundColor Red
        Write-Host ""
        Write-Host "Instalacao manual:" -ForegroundColor Yellow
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

# Navegar para o diretorio do projeto
$projectPath = $PSScriptRoot
Set-Location $projectPath

Write-Host "Diretorio do projeto: $projectPath" -ForegroundColor Yellow
Write-Host ""

# Verificar se composer.phar existe localmente
if (Test-Path "composer.phar") {
    Write-Host "Usando Composer local (composer.phar)..." -ForegroundColor Yellow
    php composer.phar require mpdf/mpdf --no-interaction
} elseif ($composerPath) {
    Write-Host "Usando Composer global..." -ForegroundColor Yellow
    composer require mpdf/mpdf --no-interaction
} else {
    Write-Host "Composer nao encontrado!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Opcoes:" -ForegroundColor Yellow
    Write-Host "1. Instale o Composer: https://getcomposer.org/download/" -ForegroundColor Cyan
    Write-Host "2. Ou use a instalacao manual do mPDF" -ForegroundColor Cyan
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Verificacao" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verificar se mPDF foi instalado
if (Test-Path "vendor\mpdf\mpdf") {
    Write-Host "mPDF instalado com sucesso!" -ForegroundColor Green
    Write-Host "Localizacao: vendor\mpdf\mpdf" -ForegroundColor Green
    
    # Verificar dependencias
    Write-Host ""
    Write-Host "Verificando dependencias:" -ForegroundColor Yellow
    $deps = @(
        "vendor\myclabs\deep-copy",
        "vendor\setasign\fpdi",
        "vendor\psr\http-message",
        "vendor\psr\log"
    )
    
    foreach ($dep in $deps) {
        if (Test-Path $dep) {
            $depName = Split-Path $dep -Leaf
            Write-Host "  $depName encontrado" -ForegroundColor Green
        } else {
            $depName = Split-Path $dep -Leaf
            Write-Host "  $depName nao encontrado" -ForegroundColor Yellow
        }
    }
} else {
    Write-Host "mPDF pode nao ter sido instalado corretamente." -ForegroundColor Yellow
    Write-Host "Verifique os erros acima." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Concluido!" -ForegroundColor Green
Write-Host ""
Write-Host "Proximos passos:" -ForegroundColor Yellow
Write-Host "1. Reinicie o Apache no XAMPP" -ForegroundColor Cyan
Write-Host "2. Teste o envio de email com extrato" -ForegroundColor Cyan
Write-Host "3. Verifique se o PDF esta sendo anexado" -ForegroundColor Cyan
Write-Host ""
$testUrl = "http://localhost/SISIPTU/diagnostico_pdf.php"
Write-Host "Para testar, acesse: $testUrl" -ForegroundColor Cyan
Write-Host ""
