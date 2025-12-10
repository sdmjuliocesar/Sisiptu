<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Sistema de Log - SISIPTU</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #a8e6cf 0%, #7ed8a8 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .test-container {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 800px;
            width: 100%;
        }
        
        h1 {
            color: #2d8659;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .result {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            margin-top: 20px;
        }
        
        .info-item {
            margin: 10px 0;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .info-label {
            font-weight: bold;
            color: #2d8659;
        }
        
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin-top: 10px;
            font-size: 12px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2d8659;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #1a5d3f;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>📝 Teste de Sistema de Log</h1>
        
        <?php
        require_once __DIR__ . '/config/logger.php';
        
        // Verificar diretório de logs
        echo '<div class="info">';
        echo '<h3>📁 Verificação do Diretório de Logs:</h3>';
        echo '<div class="info-item"><span class="info-label">Caminho:</span> ' . LOG_DIR . '</div>';
        
        if (is_dir(LOG_DIR)) {
            echo '<div class="info-item"><span class="info-label">Status:</span> ✅ Diretório existe</div>';
            
            if (is_writable(LOG_DIR)) {
                echo '<div class="info-item"><span class="info-label">Permissões:</span> ✅ Diretório é gravável</div>';
            } else {
                echo '<div class="info-item"><span class="info-label">Permissões:</span> ❌ Diretório NÃO é gravável</div>';
                echo '<div class="result error">Tente alterar as permissões da pasta logs para 755 ou 777</div>';
            }
        } else {
            echo '<div class="info-item"><span class="info-label">Status:</span> ❌ Diretório não existe</div>';
            echo '<div class="result error">Tentando criar diretório...</div>';
            
            if (@mkdir(LOG_DIR, 0755, true)) {
                echo '<div class="result success">✅ Diretório criado com sucesso!</div>';
            } else {
                echo '<div class="result error">❌ Erro ao criar diretório. Verifique as permissões.</div>';
            }
        }
        echo '</div>';
        
        // Testar escrita de log
        echo '<h3>🧪 Testando Escrita de Log...</h3>';
        
        $testeSucesso = registrarLog('TESTE', 'Teste de escrita de log do sistema', [
            'teste' => true,
            'timestamp_teste' => time()
        ]);
        
        if ($testeSucesso) {
            echo '<div class="result success">';
            echo '<h3>✅ Log de teste criado com sucesso!</h3>';
            echo '</div>';
        } else {
            echo '<div class="result error">';
            echo '<h3>❌ Erro ao criar log de teste</h3>';
            echo '<p>Verifique as permissões da pasta logs e os logs de erro do PHP.</p>';
            echo '</div>';
        }
        
        // Testar função de login
        echo '<h3>🔐 Testando Log de Login...</h3>';
        
        $testeLogin1 = registrarLogin('usuario_teste', true, 'Teste de login bem-sucedido');
        $testeLogin2 = registrarLogin('usuario_teste', false, 'Teste de login falhado');
        
        if ($testeLogin1 && $testeLogin2) {
            echo '<div class="result success">';
            echo '<h3>✅ Logs de login criados com sucesso!</h3>';
            echo '</div>';
        } else {
            echo '<div class="result error">';
            echo '<h3>❌ Erro ao criar logs de login</h3>';
            echo '</div>';
        }
        
        // Listar arquivos de log
        echo '<div class="info">';
        echo '<h3>📄 Arquivos de Log:</h3>';
        
        if (is_dir(LOG_DIR)) {
            $arquivos = glob(LOG_DIR . '*.log');
            
            if (count($arquivos) > 0) {
                echo '<p>Total de arquivos de log: <strong>' . count($arquivos) . '</strong></p>';
                
                foreach ($arquivos as $arquivo) {
                    $nomeArquivo = basename($arquivo);
                    $tamanho = filesize($arquivo);
                    $dataModificacao = date('Y-m-d H:i:s', filemtime($arquivo));
                    
                    echo '<div class="info-item">';
                    echo '<span class="info-label">📄 ' . $nomeArquivo . ':</span><br>';
                    echo 'Tamanho: ' . number_format($tamanho / 1024, 2) . ' KB | ';
                    echo 'Modificado: ' . $dataModificacao;
                    echo '</div>';
                }
                
                // Mostrar conteúdo do último arquivo de log
                if (count($arquivos) > 0) {
                    $ultimoArquivo = $arquivos[count($arquivos) - 1];
                    echo '<h4>Conteúdo do último arquivo de log:</h4>';
                    echo '<pre>';
                    echo htmlspecialchars(file_get_contents($ultimoArquivo));
                    echo '</pre>';
                }
            } else {
                echo '<div class="result error">Nenhum arquivo de log encontrado.</div>';
            }
        }
        echo '</div>';
        ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="index.html" class="btn">← Voltar para Login</a>
            <button onclick="location.reload()" class="btn">🔄 Atualizar Teste</button>
        </div>
    </div>
</body>
</html>



