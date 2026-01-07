<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Conexão - SISIPTU</title>
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
            max-width: 600px;
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
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #2d8659;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .btn:hover {
            background: #1a5d3f;
        }
        
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🔍 Teste de Conexão com Banco de Dados</h1>
        
        <?php
        // Verificar extensões PHP necessárias
        echo '<div class="info">';
        echo '<h3>🔧 Verificação de Extensões PHP:</h3>';
        
        $extensions = ['pdo', 'pdo_pgsql'];
        $allOk = true;
        
        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                echo '<div class="info-item"><span class="info-label">✅ ' . $ext . ':</span> Habilitada</div>';
            } else {
                echo '<div class="info-item"><span class="info-label">❌ ' . $ext . ':</span> <strong style="color: #721c24;">NÃO HABILITADA</strong></div>';
                $allOk = false;
            }
        }
        
        if (!$allOk) {
            echo '<div class="result error" style="margin-top: 15px;">';
            echo '<p><strong>Atenção:</strong> Algumas extensões necessárias não estão habilitadas. Habilite-as no arquivo php.ini</p>';
            echo '</div>';
        }
        echo '</div>';
        
        // Incluir arquivo de configuração
        require_once __DIR__ . '/php/database.php';
        
        echo '<div class="info">';
        echo '<h3>📋 Configurações de Conexão:</h3>';
        echo '<div class="info-item"><span class="info-label">Host:</span> ' . DB_HOST . '</div>';
        echo '<div class="info-item"><span class="info-label">Porta:</span> ' . DB_PORT . '</div>';
        echo '<div class="info-item"><span class="info-label">Banco de Dados:</span> ' . DB_NAME . '</div>';
        echo '<div class="info-item"><span class="info-label">Usuário:</span> ' . DB_USER . '</div>';
        echo '<div class="info-item"><span class="info-label">Senha:</span> <strong style="color: #721c24; font-family: monospace;">' . htmlspecialchars(DB_PASS) . '</strong></div>';
        echo '</div>';
        
        // Testar conexão
        echo '<h3>🔌 Testando Conexão...</h3>';
        
        try {
            $pdo = getConnection();
            
            echo '<div class="result success">';
            echo '<h3>✅ Conexão Estabelecida com Sucesso!</h3>';
            echo '<p>Conectado ao banco de dados PostgreSQL.</p>';
            echo '</div>';
            
            // Testar informações do servidor
            echo '<div class="info">';
            echo '<h3>📊 Informações do Servidor:</h3>';
            
            // Versão do PostgreSQL
            $stmt = $pdo->query("SELECT version()");
            $version = $stmt->fetchColumn();
            echo '<div class="info-item"><span class="info-label">Versão PostgreSQL:</span> ' . htmlspecialchars($version) . '</div>';
            
            // Nome do banco atual
            $stmt = $pdo->query("SELECT current_database()");
            $dbName = $stmt->fetchColumn();
            echo '<div class="info-item"><span class="info-label">Banco Atual:</span> ' . htmlspecialchars($dbName) . '</div>';
            
            // Usuário atual
            $stmt = $pdo->query("SELECT current_user");
            $currentUser = $stmt->fetchColumn();
            echo '<div class="info-item"><span class="info-label">Usuário Atual:</span> ' . htmlspecialchars($currentUser) . '</div>';
            
            // Verificar se a tabela usuarios existe
            $stmt = $pdo->query("SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = 'usuarios'
            )");
            $tableExists = $stmt->fetchColumn();
            
            if ($tableExists) {
                echo '<div class="result success">';
                echo '<h3>✅ Tabela "usuarios" encontrada!</h3>';
                
                // Contar registros
                $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
                $count = $stmt->fetchColumn();
                echo '<p>Total de usuários cadastrados: <strong>' . $count . '</strong></p>';
                
                // Listar usuários (apenas nomes, sem senhas)
                if ($count > 0) {
                    $stmt = $pdo->query("SELECT id, usuario, nome, email, ativo FROM usuarios ORDER BY id");
                    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo '<h4>Usuários cadastrados:</h4>';
                    echo '<pre>';
                    print_r($usuarios);
                    echo '</pre>';
                }
                
                echo '</div>';
            } else {
                echo '<div class="result error">';
                echo '<h3>⚠️ Tabela "usuarios" não encontrada</h3>';
                echo '<p>A tabela ainda não foi criada. Execute o script SQL em <code>config/database.sql</code> (o arquivo database.php está em <code>php/database.php</code>)</p>';
                echo '</div>';
            }
            
            echo '</div>';
            
        } catch (PDOException $e) {
            echo '<div class="result error">';
            echo '<h3>❌ Erro na Conexão</h3>';
            echo '<p><strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
            
            echo '<div class="info">';
            echo '<h3>🔧 Possíveis Soluções:</h3>';
            echo '<ul style="margin-left: 20px; margin-top: 10px;">';
            echo '<li>Verifique se o PostgreSQL está rodando</li>';
            echo '<li>Confirme se as credenciais estão corretas em <code>php/database.php</code></li>';
            echo '<li>Verifique se o banco de dados <strong>' . DB_NAME . '</strong> existe</li>';
            echo '<li>Confirme se o usuário <strong>' . DB_USER . '</strong> tem permissões</li>';
            echo '<li>Verifique se a porta <strong>' . DB_PORT . '</strong> está correta</li>';
            echo '<li>Certifique-se de que a extensão <code>pdo_pgsql</code> está habilitada no PHP</li>';
            echo '</ul>';
            echo '</div>';
        } catch (Exception $e) {
            echo '<div class="result error">';
            echo '<h3>❌ Erro</h3>';
            echo '<p><strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>
        
        <div style="text-align: center;">
            <a href="index.html" class="btn">← Voltar para Login</a>
        </div>
    </div>
</body>
</html>

