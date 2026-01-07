# Script PowerShell para instalar mPDF manualmente
# Execute: PowerShell -ExecutionPolicy Bypass -File instalar_mpdf.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Instalador de mPDF (Manual)" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$projectPath = $PSScriptRoot
$vendorPath = Join-Path $projectPath "vendor"
$mpdfPath = Join-Path $vendorPath "mpdf\mpdf"

Write-Host "📂 Projeto: $projectPath" -ForegroundColor Yellow
Write-Host ""

# Verificar se já está instalado
if (Test-Path $mpdfPath) {
    Write-Host "⚠️  mPDF já parece estar instalado em: $mpdfPath" -ForegroundColor Yellow
    $resposta = Read-Host "Deseja reinstalar? (S/N)"
    if ($resposta -ne "S" -and $resposta -ne "s") {
        Write-Host "Instalação cancelada." -ForegroundColor Yellow
        exit 0
    }
}

# Criar pasta vendor se não existir
if (-not (Test-Path $vendorPath)) {
    Write-Host "📁 Criando pasta vendor..." -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $vendorPath -Force | Out-Null
}

# Criar pasta mpdf
$mpdfParentPath = Join-Path $vendorPath "mpdf"
if (-not (Test-Path $mpdfParentPath)) {
    Write-Host "📁 Criando pasta mpdf..." -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $mpdfParentPath -Force | Out-Null
}

Write-Host ""
Write-Host "📥 Baixando mPDF..." -ForegroundColor Yellow
Write-Host "Acesse: https://github.com/mpdf/mpdf/releases" -ForegroundColor Cyan
Write-Host "Baixe a versão mais recente (ex: mpdf-8.2.0.zip)" -ForegroundColor Cyan
Write-Host ""

$zipPath = Read-Host "Digite o caminho completo do arquivo ZIP baixado (ou pressione Enter para pular)"

if ([string]::IsNullOrWhiteSpace($zipPath)) {
    Write-Host ""
    Write-Host "⚠️  Download manual necessário." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "INSTRUÇÕES MANUAIS:" -ForegroundColor Cyan
    Write-Host "1. Baixe mPDF de: https://github.com/mpdf/mpdf/releases" -ForegroundColor White
    Write-Host "2. Extraia o ZIP" -ForegroundColor White
    Write-Host "3. Copie a pasta 'mpdf' extraída para:" -ForegroundColor White
    Write-Host "   $mpdfParentPath" -ForegroundColor Green
    Write-Host "4. A estrutura deve ser: vendor\mpdf\mpdf\src\Mpdf.php" -ForegroundColor White
    Write-Host ""
    exit 0
}

# Verificar se o arquivo existe
if (-not (Test-Path $zipPath)) {
    Write-Host "❌ Arquivo não encontrado: $zipPath" -ForegroundColor Red
    exit 1
}

# Extrair ZIP
Write-Host "📦 Extraindo arquivo..." -ForegroundColor Yellow

try {
    # Limpar pasta antiga se existir
    if (Test-Path $mpdfPath) {
        Remove-Item $mpdfPath -Recurse -Force
    }
    
    # Extrair
    Expand-Archive -Path $zipPath -DestinationPath $mpdfParentPath -Force
    
    # Verificar estrutura
    $mpdfSrcPath = Join-Path $mpdfPath "src\Mpdf.php"
    if (Test-Path $mpdfSrcPath) {
        Write-Host "✅ mPDF instalado com sucesso!" -ForegroundColor Green
        Write-Host "Localização: $mpdfPath" -ForegroundColor Green
    } else {
        # Pode estar em subpasta
        $subfolders = Get-ChildItem $mpdfParentPath -Directory
        if ($subfolders.Count -eq 1) {
            $actualPath = $subfolders[0].FullName
            $actualSrcPath = Join-Path $actualPath "src\Mpdf.php"
            if (Test-Path $actualSrcPath) {
                # Mover para o local correto
                if (Test-Path $mpdfPath) {
                    Remove-Item $mpdfPath -Recurse -Force
                }
                Move-Item $actualPath $mpdfPath -Force
                Write-Host "✅ mPDF instalado com sucesso!" -ForegroundColor Green
            } else {
                Write-Host "⚠️  Estrutura de pastas inesperada. Verifique manualmente." -ForegroundColor Yellow
            }
        } else {
            Write-Host "⚠️  Estrutura de pastas inesperada. Verifique manualmente." -ForegroundColor Yellow
        }
    }
} catch {
    Write-Host "❌ Erro ao extrair: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "Tente extrair manualmente e copiar para: $mpdfParentPath" -ForegroundColor Yellow
    exit 1
}

Write-Host ""
Write-Host "✅ Concluído!" -ForegroundColor Green
Write-Host ""
Write-Host "Próximos passos:" -ForegroundColor Yellow
Write-Host "1. Reinicie o Apache no XAMPP" -ForegroundColor Cyan
Write-Host "2. Teste o envio de email com extrato" -ForegroundColor Cyan
Write-Host "3. Verifique se o PDF está sendo anexado" -ForegroundColor Cyan
Write-Host ""





