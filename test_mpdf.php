<?php
/**
 * Script de teste para verificar se o mPDF está instalado corretamente
 * Acesse: http://localhost/SISIPTU/test_mpdf.php
 */

echo "<h2>🧪 Teste de Instalação do mPDF</h2>";
echo "<hr>";

// Verificar estrutura de pastas
$vendorPath = __DIR__ . '/vendor';
$mpdfPath = $vendorPath . '/mpdf/mpdf';
$mpdfSrcPath = $mpdfPath . '/src/Mpdf.php';

echo "<h3>📂 Verificação de Estrutura:</h3>";
echo "<ul>";

if (is_dir($vendorPath)) {
    echo "<li style='color: green;'>✅ Pasta vendor existe</li>";
} else {
    echo "<li style='color: red;'>❌ Pasta vendor não encontrada: $vendorPath</li>";
    echo "</ul>";
    exit;
}

if (is_dir($mpdfPath)) {
    echo "<li style='color: green;'>✅ Pasta mpdf/mpdf existe</li>";
} else {
    echo "<li style='color: red;'>❌ Pasta mpdf/mpdf não encontrada: $mpdfPath</li>";
    echo "</ul>";
    exit;
}

if (file_exists($mpdfSrcPath)) {
    echo "<li style='color: green;'>✅ Arquivo Mpdf.php encontrado</li>";
} else {
    echo "<li style='color: red;'>❌ Arquivo Mpdf.php não encontrado: $mpdfSrcPath</li>";
    echo "</ul>";
    exit;
}

echo "</ul>";

// Tentar carregar mPDF
echo "<h3>🔧 Tentando Carregar mPDF:</h3>";

// Carregar autoload se existir
if (file_exists($vendorPath . '/autoload.php')) {
    require_once $vendorPath . '/autoload.php';
    echo "<p style='color: green;'>✅ Autoload do Composer carregado</p>";
}

// Tentar carregar mPDF manualmente
if (file_exists($mpdfPath . '/vendor/autoload.php')) {
    require_once $mpdfPath . '/vendor/autoload.php';
    echo "<p style='color: green;'>✅ Autoload do mPDF carregado</p>";
}

if (file_exists($mpdfSrcPath)) {
    require_once $mpdfSrcPath;
    echo "<p style='color: green;'>✅ Arquivo Mpdf.php carregado</p>";
}

// Verificar se a classe existe
echo "<h3>✅ Verificação de Classe:</h3>";

if (class_exists('\\Mpdf\\Mpdf')) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ SUCESSO! Classe \\Mpdf\\Mpdf encontrada!</p>";
    
    // Tentar criar uma instância
    try {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P'
        ]);
        
        echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ Instância do mPDF criada com sucesso!</p>";
        echo "<p style='color: green;'>✅ mPDF está funcionando corretamente!</p>";
        
        // Teste de geração de PDF
        echo "<h3>📄 Teste de Geração de PDF:</h3>";
        $mpdf->WriteHTML('<h1>Teste de PDF</h1><p>Se você está vendo este PDF, o mPDF está funcionando!</p>');
        
        $testPdfPath = __DIR__ . '/temp/test_mpdf_' . date('YmdHis') . '.pdf';
        if (!is_dir(__DIR__ . '/temp')) {
            mkdir(__DIR__ . '/temp', 0755, true);
        }
        
        $mpdf->Output($testPdfPath, 'F');
        
        if (file_exists($testPdfPath)) {
            echo "<p style='color: green;'>✅ PDF de teste gerado com sucesso!</p>";
            echo "<p><a href='temp/" . basename($testPdfPath) . "' target='_blank' style='background: #2d8659; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📄 Abrir PDF de Teste</a></p>";
        } else {
            echo "<p style='color: orange;'>⚠️ PDF não foi gerado, mas a instância foi criada.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro ao criar instância: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    }
    
} else {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ ERRO! Classe \\Mpdf\\Mpdf não encontrada!</p>";
    echo "<p>Possíveis causas:</p>";
    echo "<ul>";
    echo "<li>Arquivo não foi carregado corretamente</li>";
    echo "<li>Namespace incorreto</li>";
    echo "<li>Dependências faltando</li>";
    echo "</ul>";
    
    // Verificar namespace
    echo "<h4>Verificação de Namespace:</h4>";
    $classes = get_declared_classes();
    $mpdfClasses = array_filter($classes, function($class) {
        return stripos($class, 'mpdf') !== false;
    });
    
    if (count($mpdfClasses) > 0) {
        echo "<p>Classes mPDF encontradas:</p>";
        echo "<ul>";
        foreach ($mpdfClasses as $class) {
            echo "<li>$class</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>Nenhuma classe mPDF encontrada.</p>";
    }
}

echo "<hr>";
echo "<h3>📝 Próximos Passos:</h3>";
echo "<ol>";
echo "<li>Se tudo estiver ✅ verde: O mPDF está funcionando! Teste o envio de email.</li>";
echo "<li>Se houver ❌ erros: Verifique os logs em <code>logs/erro_*.log</code></li>";
echo "<li>Reinicie o Apache no XAMPP após qualquer alteração</li>";
echo "</ol>";

?>









