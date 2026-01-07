<?php
/**
 * Classe abstrata base para implementações de CNAB
 * Contém métodos comuns e utilitários
 */
abstract class CnabAbstract implements CnabInterface {
    protected $versaoCnab;
    protected $codigoBanco;
    protected $nomeBanco;
    
    /**
     * Formata número removendo caracteres não numéricos
     */
    protected function apenasNumeros($valor): string {
        return preg_replace('/[^0-9]/', '', $valor);
    }
    
    /**
     * Formata valor monetário para o formato CNAB (sem vírgula, com zeros à esquerda)
     */
    protected function formatarValor($valor, $tamanho = 15): string {
        $valor = (float)str_replace(',', '.', $valor);
        $valorFormatado = number_format($valor, 2, '', '');
        return str_pad($valorFormatado, $tamanho, '0', STR_PAD_LEFT);
    }
    
    /**
     * Formata data para o formato CNAB (DDMMYYYY)
     */
    protected function formatarData($data): string {
        if (empty($data)) {
            return str_repeat('0', 8);
        }
        
        // Se já está no formato correto
        if (preg_match('/^\d{8}$/', $data)) {
            return $data;
        }
        
        // Converter de YYYY-MM-DD para DDMMYYYY
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $data, $matches)) {
            return $matches[3] . $matches[2] . $matches[1];
        }
        
        // Converter de DD/MM/YYYY para DDMMYYYY
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $data, $matches)) {
            return $matches[1] . $matches[2] . $matches[3];
        }
        
        return str_repeat('0', 8);
    }
    
    /**
     * Formata campo alfanumérico (preenche com espaços à direita)
     */
    protected function formatarAlfanumerico($valor, $tamanho): string {
        $valor = mb_strtoupper($valor ?? '', 'UTF-8');
        $valor = $this->removerAcentos($valor);
        return str_pad(substr($valor, 0, $tamanho), $tamanho, ' ', STR_PAD_RIGHT);
    }
    
    /**
     * Formata campo numérico (preenche com zeros à esquerda)
     */
    protected function formatarNumerico($valor, $tamanho): string {
        $valor = $this->apenasNumeros($valor ?? '');
        return str_pad($valor, $tamanho, '0', STR_PAD_LEFT);
    }
    
    /**
     * Remove acentos de uma string
     */
    protected function removerAcentos($string): string {
        $acentos = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ç' => 'C',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c'
        ];
        return strtr($string, $acentos);
    }
    
    /**
     * Calcula o dígito verificador do módulo 11
     */
    protected function modulo11($numero, $base = 9): string {
        $numero = $this->apenasNumeros($numero);
        $soma = 0;
        $multiplicador = 2;
        
        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $soma += (int)$numero[$i] * $multiplicador;
            $multiplicador++;
            if ($multiplicador > $base) {
                $multiplicador = 2;
            }
        }
        
        $resto = $soma % 11;
        
        if ($resto < 2) {
            return '0';
        }
        
        return (string)(11 - $resto);
    }
    
    /**
     * Calcula o dígito verificador do módulo 10
     */
    protected function modulo10($numero): string {
        $numero = $this->apenasNumeros($numero);
        $soma = 0;
        $multiplicador = 2;
        
        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $produto = (int)$numero[$i] * $multiplicador;
            if ($produto > 9) {
                $produto = (int)substr((string)$produto, 0, 1) + (int)substr((string)$produto, 1, 1);
            }
            $soma += $produto;
            $multiplicador = ($multiplicador == 2) ? 1 : 2;
        }
        
        $resto = $soma % 10;
        return ($resto == 0) ? '0' : (string)(10 - $resto);
    }
    
    /**
     * Cria o diretório se não existir
     */
    protected function criarDiretorio($caminho): void {
        $diretorio = dirname($caminho);
        if (!is_dir($diretorio)) {
            if (!mkdir($diretorio, 0755, true)) {
                throw new Exception("Não foi possível criar o diretório: {$diretorio}");
            }
        }
    }
    
    /**
     * Retorna o código do banco
     */
    public function getCodigoBanco(): string {
        return $this->codigoBanco;
    }
    
    /**
     * Retorna o nome do banco
     */
    public function getNomeBanco(): string {
        return $this->nomeBanco;
    }
    
    /**
     * Retorna a versão do CNAB
     */
    public function getVersaoCnab(): int {
        return $this->versaoCnab;
    }
    
    /**
     * Validação básica de dados do banco
     */
    public function validarDadosBanco(array $dadosBanco): bool {
        $camposObrigatorios = ['agencia', 'conta', 'codigo_cedente', 'cedente'];
        
        foreach ($camposObrigatorios as $campo) {
            if (empty($dadosBanco[$campo])) {
                throw new Exception("Campo obrigatório ausente: {$campo}");
            }
        }
        
        return true;
    }
    
    /**
     * Validação básica de título
     */
    public function validarTitulo(array $titulo): bool {
        $camposObrigatorios = ['valor_mensal', 'datavencimento'];
        
        foreach ($camposObrigatorios as $campo) {
            if (!isset($titulo[$campo]) || $titulo[$campo] === '') {
                throw new Exception("Campo obrigatório ausente no título: {$campo}");
            }
        }
        
        return true;
    }
}

