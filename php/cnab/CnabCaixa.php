<?php
/**
 * Implementação CNAB 400 para Caixa Econômica Federal
 */
class CnabCaixa extends CnabAbstract {
    public function __construct(int $versaoCnab = 400) {
        $this->codigoBanco = '104';
        $this->nomeBanco = 'Caixa Econômica Federal';
        $this->versaoCnab = $versaoCnab;
    }
    
    public function gerarRemessa(array $dadosBanco, array $titulos, string $caminhoDestino): string {
        $this->validarDadosBanco($dadosBanco);
        
        foreach ($titulos as $titulo) {
            $this->validarTitulo($titulo);
        }
        
        $this->criarDiretorio($caminhoDestino);
        
        $nomeArquivo = $this->gerarNomeArquivo($dadosBanco);
        $caminhoCompleto = rtrim($caminhoDestino, '/\\') . DIRECTORY_SEPARATOR . $nomeArquivo;
        
        $arquivo = fopen($caminhoCompleto, 'w');
        if (!$arquivo) {
            throw new Exception("Não foi possível criar o arquivo: {$caminhoCompleto}");
        }
        
        try {
            $header = $this->gerarHeader($dadosBanco, count($titulos));
            fwrite($arquivo, $header . "\r\n");
            
            $sequencial = 2;
            foreach ($titulos as $titulo) {
                $registro = $this->gerarRegistroTitulo($dadosBanco, $titulo, $sequencial);
                fwrite($arquivo, $registro . "\r\n");
                $sequencial++;
            }
            
            $trailer = $this->gerarTrailer($dadosBanco, count($titulos), $sequencial);
            fwrite($arquivo, $trailer . "\r\n");
            
        } finally {
            fclose($arquivo);
        }
        
        return $caminhoCompleto;
    }
    
    private function gerarHeader(array $dadosBanco, int $totalTitulos): string {
        $linha = '';
        $linha .= '0'; // 001
        $linha .= '1'; // 002
        $linha .= 'REMESSA'; // 003-009
        $linha .= '01'; // 010-011
        $linha .= $this->formatarAlfanumerico('COBRANCA', 15); // 012-026
        $linha .= $this->formatarNumerico($dadosBanco['agencia'], 4); // 027-030
        $linha .= $this->formatarNumerico($dadosBanco['dv_agencia'] ?? '', 1); // 031
        $linha .= $this->formatarNumerico($dadosBanco['codigo_cedente'] ?? '', 6); // 032-037
        $linha .= str_repeat('0', 7); // 038-044
        $linha .= $this->formatarAlfanumerico($dadosBanco['cedente'], 30); // 045-074
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 075-077
        $linha .= $this->formatarAlfanumerico('CAIXA', 15); // 078-092
        $linha .= date('dmy'); // 093-098
        $linha .= str_repeat(' ', 8); // 099-106
        $linha .= str_repeat(' ', 7); // 107-113
        $linha .= str_pad($totalTitulos, 6, '0', STR_PAD_LEFT); // 114-119
        $linha .= str_repeat(' ', 275); // 120-394
        $linha .= str_pad('1', 6, '0', STR_PAD_LEFT); // 395-400
        
        return $linha;
    }
    
    private function gerarRegistroTitulo(array $dadosBanco, array $titulo, int $sequencial): string {
        $linha = '';
        $linha .= '1'; // 001
        $linha .= $this->formatarAlfanumerico($dadosBanco['agencia'], 4); // 002-005
        $linha .= $this->formatarNumerico($dadosBanco['dv_agencia'] ?? '', 1); // 006
        $linha .= $this->formatarNumerico($dadosBanco['codigo_cedente'] ?? '', 6); // 007-012
        $linha .= str_repeat('0', 7); // 013-019
        $linha .= $this->formatarAlfanumerico($titulo['nosso_numero'] ?? $titulo['id'], 17); // 020-036
        $linha .= '24'; // 037-038
        $linha .= '01'; // 039-040
        $linha .= $this->formatarAlfanumerico($titulo['contrato'] ?? '', 10); // 041-050
        $linha .= $this->formatarData($titulo['datavencimento']); // 051-058
        $linha .= $this->formatarValor($titulo['valor_mensal'], 13); // 059-071
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 072-074
        $linha .= '00000'; // 075-079
        $linha .= '01'; // 080-081
        $linha .= 'N'; // 082
        $linha .= $this->formatarData(date('Y-m-d')); // 083-090
        $linha .= '00'; // 091-092
        $linha .= '00'; // 093-094
        $linha .= $this->formatarValor($titulo['juros_calculado'] ?? 0, 13); // 095-107
        $linha .= $this->formatarData($titulo['datavencimento']); // 108-115
        $linha .= $this->formatarValor(0, 13); // 116-128
        $linha .= $this->formatarValor(0, 13); // 129-141
        $linha .= $this->formatarValor($titulo['multa_calculada'] ?? 0, 13); // 142-154
        $linha .= $this->formatarAlfanumerico($titulo['cliente_nome'] ?? '', 30); // 155-184
        $linha .= str_repeat(' ', 40); // 185-224
        $linha .= str_repeat(' ', 12); // 225-236
        $linha .= $this->formatarAlfanumerico($titulo['cep'] ?? '', 8); // 237-244
        $linha .= str_repeat(' ', 60); // 245-304
        $linha .= str_repeat('0', 6); // 305-310
        $linha .= ' '; // 311
        $linha .= str_repeat(' ', 77); // 312-388
        $linha .= str_pad($sequencial, 6, '0', STR_PAD_LEFT); // 389-394
        
        return $linha;
    }
    
    private function gerarTrailer(array $dadosBanco, int $totalTitulos, int $sequencial): string {
        $linha = '';
        $linha .= '9'; // 001
        $linha .= str_repeat(' ', 393); // 002-394
        $linha .= str_pad($sequencial, 6, '0', STR_PAD_LEFT); // 395-400
        
        return $linha;
    }
    
    private function gerarNomeArquivo(array $dadosBanco): string {
        $data = date('dmy');
        $hora = date('His');
        return "CB{$this->codigoBanco}{$data}{$hora}.REM";
    }
}

