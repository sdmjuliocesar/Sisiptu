<?php
/**
 * Script de diagnóstico para verificar por que o PDF não está sendo gerado
 * Acesse: http://localhost/SISIPTU/diagnostico_pdf.php
 */

require_once __DIR__ . '/php/database.php';
require_once __DIR__ . '/php/logger.php';

echo "<h2>🔍 Diagnóstico de Geração de PDF</h2>";
echo "<hr>";

// Verificar estrutura
echo "<h3>📂 Estrutura de Pastas:</h3>";
$mpdfPath = __DIR__ . '/vendor/mpdf/mpdf';
echo "<ul>";
echo "<li><strong>Pasta mPDF:</strong> " . ($mpdfPath) . "</li>";
echo "<li><strong>Existe:</strong> " . (is_dir($mpdfPath) ? "✅ Sim" : "❌ Não") . "</li>";
echo "<li><strong>Arquivo Mpdf.php:</strong> " . ($mpdfPath . '/src/Mpdf.php') . "</li>";
echo "<li><strong>Existe:</strong> " . (file_exists($mpdfPath . '/src/Mpdf.php') ? "✅ Sim" : "❌ Não") . "</li>";
echo "</ul>";

// Tentar carregar
echo "<h3>🔧 Tentando Carregar mPDF:</h3>";

// Carregar como no extrato_api.php
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<p>✅ Autoload do Composer carregado</p>";
} else {
    echo "<p style='color: red;'>❌ Autoload do Composer não encontrado em: " . __DIR__ . '/vendor/autoload.php' . "</p>";
}

// Verificar classe (usar autoloader)
echo "<h3>✅ Verificação de Classe:</h3>";
if (class_exists('\\Mpdf\\Mpdf', true)) {
    echo "<p style='color: green; font-size: 18px;'>✅ Classe \\Mpdf\\Mpdf encontrada!</p>";
    
    // Verificar dependências (usar autoloader)
    echo "<h3>📦 Verificando Dependências:</h3>";
    $dependencias = [
        ['DeepCopy\\DeepCopy', 'class', 'myclabs/deep-copy'],
        ['FPDF', 'class', 'setasign/fpdf'],
        ['setasign\\Fpdi\\Fpdi', 'class', 'setasign/fpdi'],
        ['Psr\\Http\\Message\\MessageInterface', 'interface', 'psr/http-message'],
        ['Psr\\Log\\LoggerInterface', 'interface', 'psr/log'],
    ];
    
    echo "<ul>";
    foreach ($dependencias as $item) {
        list($class, $type, $nome) = $item;
        // Usar autoloader (true) para carregar as classes
        if ($type === 'interface') {
            $existe = interface_exists($class, true);
        } else {
            $existe = class_exists($class, true);
        }
        echo "<li><strong>$nome:</strong> " . ($existe ? "✅ Encontrado ($class)" : "❌ Não encontrado");
        if (!$existe) {
            // Tentar verificar se o arquivo existe
            $vendorPath = __DIR__ . '/vendor/' . $nome;
            if (is_dir($vendorPath)) {
                echo " (pasta existe, mas classe não carregada)";
            }
        }
        echo "</li>";
    }
    echo "</ul>";
    
    // Verificar paragonie/random_compat (é uma função, não uma classe)
    echo "<h3>🔧 Verificando Funções:</h3>";
    echo "<ul>";
    $randomCompatPath = __DIR__ . '/vendor/paragonie/random_compat';
    $randomCompatExists = is_dir($randomCompatPath);
    $randomBytesExists = function_exists('random_bytes');
    echo "<li><strong>paragonie/random_compat:</strong> " . ($randomCompatExists ? "✅ Pasta existe" : "❌ Pasta não encontrada") . "</li>";
    echo "<li><strong>random_bytes():</strong> " . ($randomBytesExists ? "✅ Função disponível" : "❌ Função não encontrada") . "</li>";
    echo "</ul>";
    
    // Verificar se os pacotes estão no vendor
    echo "<h3>📂 Verificando Pacotes no vendor/:</h3>";
    $pacotes = [
        'myclabs/deep-copy' => 'myclabs/deep-copy',
        'paragonie/random_compat' => 'paragonie/random_compat',
        'setasign/fpdi' => 'setasign/fpdi',
        'psr/http-message' => 'psr/http-message',
        'psr/log' => 'psr/log',
    ];
    
    echo "<ul>";
    foreach ($pacotes as $nome => $path) {
        $vendorPath = __DIR__ . '/vendor/' . $path;
        $existe = is_dir($vendorPath);
        echo "<li><strong>$nome:</strong> " . ($existe ? "✅ Pasta existe" : "❌ Pasta não encontrada") . "</li>";
    }
    echo "</ul>";
    
    // Tentar criar instância
    echo "<h3>🧪 Teste de Instanciação:</h3>";
    try {
        $tempDir = __DIR__ . '/temp/mpdf_temp';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'tempDir' => $tempDir,
        ]);
        
        echo "<p style='color: green;'>✅ Instância criada com sucesso!</p>";
        
        // Teste de geração
        $mpdf->WriteHTML('<h1>Teste</h1><p>Teste de PDF</p>');
        $testPath = __DIR__ . '/temp/test_' . date('YmdHis') . '.pdf';
        $mpdf->Output($testPath, 'F');
        
        if (file_exists($testPath)) {
            echo "<p style='color: green;'>✅ PDF de teste gerado com sucesso!</p>";
            echo "<p><a href='temp/" . basename($testPath) . "' target='_blank'>📄 Abrir PDF</a></p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow: auto;'>";
        echo htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    } catch (Throwable $e) {
        echo "<p style='color: red;'>❌ Erro Fatal: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow: auto;'>";
        echo htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    }
    
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ Classe \\Mpdf\\Mpdf NÃO encontrada!</p>";
    
    // Verificar classes carregadas
    $classes = get_declared_classes();
    $mpdfClasses = array_filter($classes, function($class) {
        return stripos($class, 'mpdf') !== false;
    });
    
    if (count($mpdfClasses) > 0) {
        echo "<p>Classes relacionadas encontradas:</p>";
        echo "<ul>";
        foreach ($mpdfClasses as $class) {
            echo "<li>$class</li>";
        }
        echo "</ul>";
    }
}

echo "<hr>";
echo "<h3>📝 Próximos Passos:</h3>";
echo "<ol>";
echo "<li>Se houver ❌ erros de dependências: Instale via Composer ou manualmente (veja config/INSTALAR_DEPENDENCIAS_MPDF.md)</li>";
echo "<li>Se a classe não for encontrada: Verifique se o mPDF está na pasta correta</li>";
echo "<li>Verifique os logs em: logs/erro_*.log</li>";
echo "</ol>";

?>

