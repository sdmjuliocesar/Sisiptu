<?php
/**
 * Configurações de conexão com o banco de dados PostgreSQL
 * Ajuste estas configurações conforme seu ambiente
 */

// Configurações do banco de dados PostgreSQL
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'sisiptu');
define('DB_USER', 'postgres');
define('DB_PASS', 'Linda1607*');

/**
 * Função para obter conexão com o banco de dados PostgreSQL
 * @return PDO
 */
function getConnection() {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Em produção, logar o erro sem expor detalhes
        throw new Exception("Erro na conexão com o banco de dados.");
    }
}
?>

