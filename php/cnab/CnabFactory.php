<?php
/**
 * Factory para criar instâncias de classes CNAB
 * Centraliza a criação de objetos e facilita a adição de novos bancos
 */
class CnabFactory {
    /**
     * Mapeamento de códigos de banco para classes
     */
    private static $bancos = [
        '001' => 'CnabBancoBrasil',
        '033' => 'CnabSantander',
        '077' => 'CnabInter',
        '104' => 'CnabCaixa',
        '237' => 'CnabBradesco',
        '341' => 'CnabItau',
        '748' => 'CnabSicredi',
    ];
    
    /**
     * Cria uma instância da classe CNAB apropriada baseada no código do banco
     * 
     * @param string $codigoBanco Código do banco (ex: '001', '341')
     * @param int $versaoCnab Versão do CNAB (240 ou 400)
     * @return CnabInterface
     * @throws Exception Se o banco não for suportado
     */
    public static function criar(string $codigoBanco, int $versaoCnab = 400): CnabInterface {
        $codigoBanco = str_pad($codigoBanco, 3, '0', STR_PAD_LEFT);
        
        if (!isset(self::$bancos[$codigoBanco])) {
            throw new Exception("Banco com código {$codigoBanco} não é suportado.");
        }
        
        $classe = self::$bancos[$codigoBanco];
        $arquivo = __DIR__ . '/' . $classe . '.php';
        
        if (!file_exists($arquivo)) {
            throw new Exception("Classe {$classe} não encontrada. Arquivo: {$arquivo}");
        }
        
        require_once $arquivo;
        
        if (!class_exists($classe)) {
            throw new Exception("Classe {$classe} não existe.");
        }
        
        $instancia = new $classe($versaoCnab);
        
        if (!($instancia instanceof CnabInterface)) {
            throw new Exception("Classe {$classe} não implementa CnabInterface.");
        }
        
        return $instancia;
    }
    
    /**
     * Retorna lista de bancos suportados
     * 
     * @return array
     */
    public static function getBancosSuportados(): array {
        return array_keys(self::$bancos);
    }
    
    /**
     * Verifica se um banco é suportado
     * 
     * @param string $codigoBanco
     * @return bool
     */
    public static function isBancoSuportado(string $codigoBanco): bool {
        $codigoBanco = str_pad($codigoBanco, 3, '0', STR_PAD_LEFT);
        return isset(self::$bancos[$codigoBanco]);
    }
    
    /**
     * Registra um novo banco
     * 
     * @param string $codigoBanco
     * @param string $classe
     */
    public static function registrarBanco(string $codigoBanco, string $classe): void {
        $codigoBanco = str_pad($codigoBanco, 3, '0', STR_PAD_LEFT);
        self::$bancos[$codigoBanco] = $classe;
    }
}

