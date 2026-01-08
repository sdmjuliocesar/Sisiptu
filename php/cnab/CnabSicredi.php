<?php
/**
 * Implementação CNAB 400 para Sicredi
 */
class CnabSicredi extends CnabAbstract {
    public function __construct(int $versaoCnab = 400) {
        $this->codigoBanco = '748';
        $this->nomeBanco = 'Sicredi';
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
        $linha .= $this->formatarNumerico($dadosBanco['conta'], 8); // 032-039
        $linha .= $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1); // 040
        $linha .= str_repeat('0', 1); // 041
        $linha .= $this->formatarAlfanumerico($dadosBanco['cedente'], 30); // 042-071
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 072-074
        $linha .= $this->formatarAlfanumerico('SICREDI', 15); // 075-089
        $linha .= date('dmy'); // 090-095
        $linha .= str_repeat(' ', 8); // 096-103
        $linha .= str_repeat(' ', 7); // 104-110
        $linha .= str_pad($totalTitulos, 6, '0', STR_PAD_LEFT); // 111-116
        $linha .= str_repeat(' ', 278); // 117-394
        $linha .= str_pad('1', 6, '0', STR_PAD_LEFT); // 395-400
        
        return $linha;
    }
    
    private function gerarRegistroTitulo(array $dadosBanco, array $titulo, int $sequencial): string {
        $linha = '';
        $linha .= '1'; // 001
        $linha .= $this->formatarAlfanumerico($dadosBanco['agencia'], 4); // 002-005
        $linha .= $this->formatarNumerico($dadosBanco['dv_agencia'] ?? '', 1); // 006
        $linha .= $this->formatarNumerico($dadosBanco['conta'], 8); // 007-014
        $linha .= $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1); // 015
        $linha .= str_repeat('0', 1); // 016
        $linha .= $this->formatarAlfanumerico($titulo['nosso_numero'] ?? $titulo['id'], 10); // 017-026
        $linha .= '01'; // 027-028
        $linha .= '01'; // 029-030
        $linha .= $this->formatarAlfanumerico($titulo['contrato'] ?? '', 10); // 031-040
        $linha .= $this->formatarData($titulo['datavencimento']); // 041-048
        $linha .= $this->formatarValor($titulo['valor_mensal'], 13); // 049-061
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 062-064
        $linha .= '00000'; // 065-069
        $linha .= '01'; // 070-071
        $linha .= 'N'; // 072
        $linha .= $this->formatarData(date('Y-m-d')); // 073-080
        $linha .= '00'; // 081-082
        $linha .= '00'; // 083-084
        $linha .= $this->formatarValor($titulo['juros_calculado'] ?? 0, 13); // 085-097
        $linha .= $this->formatarData($titulo['datavencimento']); // 098-105
        $linha .= $this->formatarValor(0, 13); // 106-118
        $linha .= $this->formatarValor(0, 13); // 119-131
        $linha .= $this->formatarValor($titulo['multa_calculada'] ?? 0, 13); // 132-144
        $nomeCliente = $this->obterNomeCliente($titulo);
        $linha .= $this->formatarAlfanumerico($nomeCliente, 30); // 145-174
        $enderecoCompleto = $this->montarEnderecoCliente($titulo, 40);
        $linha .= $this->formatarAlfanumerico($enderecoCompleto, 40); // 175-214
        $linha .= str_repeat(' ', 12); // 215-226
        $cep = $this->obterCepCliente($titulo);
        $linha .= $this->formatarAlfanumerico($cep, 8); // 227-234
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

