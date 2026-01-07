<?php
/**
 * Implementação CNAB 400 para Santander
 */
class CnabSantander extends CnabAbstract {
    public function __construct(int $versaoCnab = 400) {
        $this->codigoBanco = '033';
        $this->nomeBanco = 'Santander';
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
        $linha .= $this->formatarNumerico($dadosBanco['codigo_cedente'] ?? '', 20); // 027-046
        $linha .= $this->formatarAlfanumerico($dadosBanco['cedente'], 30); // 047-076
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 077-079
        $linha .= $this->formatarAlfanumerico('SANTANDER', 15); // 080-094
        $linha .= date('dmy'); // 095-100
        $linha .= str_repeat(' ', 8); // 101-108
        $linha .= str_repeat(' ', 7); // 109-115
        $linha .= str_pad($totalTitulos, 6, '0', STR_PAD_LEFT); // 116-121
        $linha .= str_repeat(' ', 273); // 122-394
        $linha .= str_pad('1', 6, '0', STR_PAD_LEFT); // 395-400
        
        return $linha;
    }
    
    private function gerarRegistroTitulo(array $dadosBanco, array $titulo, int $sequencial): string {
        $linha = '';
        $linha .= '1'; // 001
        $linha .= $this->formatarNumerico($dadosBanco['codigo_cedente'] ?? '', 20); // 002-021
        $linha .= $this->formatarAlfanumerico($titulo['nosso_numero'] ?? $titulo['id'], 12); // 022-033
        $linha .= '01'; // 034-035
        $linha .= $this->formatarAlfanumerico($titulo['contrato'] ?? '', 10); // 036-045
        $linha .= $this->formatarData($titulo['datavencimento']); // 046-053
        $linha .= $this->formatarValor($titulo['valor_mensal'], 13); // 054-066
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 067-069
        $linha .= '00000'; // 070-074
        $linha .= '01'; // 075-076
        $linha .= 'N'; // 077
        $linha .= $this->formatarData(date('Y-m-d')); // 078-085
        $linha .= '00'; // 086-087
        $linha .= '00'; // 088-089
        $linha .= $this->formatarValor($titulo['juros_calculado'] ?? 0, 13); // 090-102
        $linha .= $this->formatarData($titulo['datavencimento']); // 103-110
        $linha .= $this->formatarValor(0, 13); // 111-123
        $linha .= $this->formatarValor(0, 13); // 124-136
        $linha .= $this->formatarValor($titulo['multa_calculada'] ?? 0, 13); // 137-149
        $linha .= $this->formatarAlfanumerico($titulo['cliente_nome'] ?? '', 25); // 150-174
        $linha .= str_repeat(' ', 40); // 175-214
        $linha .= str_repeat(' ', 12); // 215-226
        $linha .= $this->formatarAlfanumerico($titulo['cep'] ?? '', 8); // 227-234
        $linha .= str_repeat(' ', 60); // 235-294
        $linha .= str_repeat('0', 6); // 295-300
        $linha .= ' '; // 301
        $linha .= str_repeat(' ', 77); // 302-378
        $linha .= str_pad($sequencial, 6, '0', STR_PAD_LEFT); // 379-384
        
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

