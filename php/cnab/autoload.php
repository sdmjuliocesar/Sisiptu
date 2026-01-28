<?php
/**
 * Autoloader para classes CNAB
 */
spl_autoload_register(function ($classe) {
    $diretorio = __DIR__;
    
    // Verificar se é uma classe CNAB
    if (strpos($classe, 'Cnab') === 0) {
        $arquivo = $diretorio . DIRECTORY_SEPARATOR . $classe . '.php';
        if (file_exists($arquivo)) {
            require_once $arquivo;
        }
    }
});

// Carregar arquivos base
require_once __DIR__ . '/CnabInterface.php';
require_once __DIR__ . '/CnabAbstract.php';
require_once __DIR__ . '/CnabFactory.php';





