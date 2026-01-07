<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: index.html');
    exit;
}

// Registrar tempo de login
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

$usuario = $_SESSION['usuario'] ?? 'Usuário';
$usuario_id = $_SESSION['usuario_id'] ?? 0;
$login_time = $_SESSION['login_time'];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestão de IPTU</title>
    <link rel="icon" type="image/x-icon" href="/SISIPTU/favicon.ico">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Menu Lateral -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>Gestão de IPTU</h1>
            </div>
            
            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <a href="#" class="menu-item active" data-page="home">
                            <span class="icon">🏠</span>
                            <span class="text">Início</span>
                        </a>
                    </li>
                    <li class="menu-item-with-submenu">
                        <a href="#" class="menu-item" data-page="cadastro" id="menu-cadastro">
                            <span class="icon">📝</span>
                            <span class="text">Cadastro</span>
                            <span class="arrow">▼</span>
                        </a>
                        <ul class="submenu" id="submenu-cadastro">
                            <li>
                                <a href="#" class="submenu-item" data-page="cadastro-clientes">
                                    <span class="icon">👥</span>
                                    <span class="text">Clientes</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="cadastro-empresas">
                                    <span class="icon">🏭</span>
                                    <span class="text">Empresas</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="cadastro-empreendimentos">
                                    <span class="icon">🏢</span>
                                    <span class="text">Empreendimentos</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="cadastro-modulos">
                                    <span class="icon">📦</span>
                                    <span class="text">Módulos</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="cadastro-usuarios">
                                    <span class="icon">👤</span>
                                    <span class="text">Usuários</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="cadastro-bancos">
                                    <span class="icon">🏦</span>
                                    <span class="text">Bancos</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="cadastro-contratos">
                                    <span class="icon">📋</span>
                                    <span class="text">Contrato</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item-with-submenu">
                        <a href="#" class="menu-item" data-page="iptu" id="menu-iptu">
                            <span class="icon">🏛️</span>
                            <span class="text">IPTU</span>
                            <span class="arrow">▼</span>
                        </a>
                        <ul class="submenu" id="submenu-iptu">
                            <li>
                                <a href="#" class="submenu-item" data-page="iptu-importar-cadastro">
                                    <span class="icon">📥</span>
                                    <span class="text">Importar Cadastro</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="iptu-pesquisar-importados">
                                    <span class="icon">🔍</span>
                                    <span class="text">Pesquisar Importados</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="iptu-pesquisar-contratos">
                                    <span class="icon">🔍</span>
                                    <span class="text">Pesquisar Contratos</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="iptu-gerar-iptu">
                                    <span class="icon">📄</span>
                                    <span class="text">Gerar IPTU</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="iptu-manutencao-iptu">
                                    <span class="icon">🔧</span>
                                    <span class="text">Manutenção IPTU</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="menu-item-with-submenu">
                        <a href="#" class="menu-item" data-page="cobranca" id="menu-cobranca">
                            <span class="icon">💰</span>
                            <span class="text">Cobrança</span>
                            <span class="arrow">▼</span>
                        </a>
                        <ul class="submenu" id="submenu-cobranca">
                            <li>
                                <a href="#" class="submenu-item" data-page="cobranca-consulta">
                                    <span class="icon">🔍</span>
                                    <span class="text">Consulta</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="cobranca-baixa-manual">
                                    <span class="icon">✏️</span>
                                    <span class="text">Baixa Manual</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="cobranca-automatica">
                                    <span class="icon">⚙️</span>
                                    <span class="text">Cobrança Automática</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="submenu-item" data-page="cobranca-retorno-bancario">
                                    <span class="icon">🏦</span>
                                    <span class="text">Retorno Bancário</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li>
                        <a href="#" class="menu-item" data-page="relatorios">
                            <span class="icon">📊</span>
                            <span class="text">Relatórios</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Conteúdo Principal -->
        <main class="main-content">
            <header class="content-header">
                <h2 id="page-title">Bem-vindo ao Sistema</h2>
                <div class="header-actions">
                    <button class="btn-logout" onclick="logout()">Sair</button>
                </div>
            </header>

            <div class="content-body" id="content-body">
                <!-- Conteúdo será carregado dinamicamente aqui -->
                <div class="welcome-section">
                    <div class="welcome-card">
                        <h3>Bem-vindo, <?php echo htmlspecialchars($usuario); ?>!</h3>
                        <p>Sistema de Gestão de IPTU</p>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2d8659;">
                            <h4 style="color: #2d8659; margin-bottom: 10px;">📋 Informações do Usuário</h4>
                            <p><strong>Usuário:</strong> <?php echo htmlspecialchars($usuario); ?></p>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon">📋</div>
                                <div class="stat-info">
                                    <h4>Cadastros</h4>
                                    <p class="stat-number">0</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">💰</div>
                                <div class="stat-info">
                                    <h4>Cobranças</h4>
                                    <p class="stat-number">0</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">📊</div>
                                <div class="stat-info">
                                    <h4>Relatórios</h4>
                                    <p class="stat-number">0</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Rodapé -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-info">
                <span class="footer-item">
                    <strong>Usuário:</strong> <span id="footer-usuario"><?php echo htmlspecialchars($usuario); ?></span>
                </span>
                <span class="footer-separator">|</span>
                <span class="footer-item">
                    <strong>Tempo logado:</strong> <span id="tempo-logado">00:00:00</span>
                </span>
                <span class="footer-separator">|</span>
                <span class="footer-item">
                    <strong>Data:</strong> <span id="data-atual"></span>
                </span>
            </div>
        </div>
    </footer>

    <script>
        // Passar variável PHP para JavaScript
        const loginTime = <?php echo $login_time; ?>;
        
        // Função de logout (backup caso o script não carregue)
        function logout() {
            const confirmar = confirm('Deseja realmente sair do sistema?\n\nClique em "OK" para sair ou "Cancelar" para permanecer.');
            if (confirmar) {
                window.location.href = '/SISIPTU/php/logout.php';
            }
        }
    </script>
    <script src="js/dashboard.js"></script>
</body>
</html>

