<?php
/**
 * Implementação CNAB 400 para Itaú
 */
class CnabItau extends CnabAbstract {
    public function __construct(int $versaoCnab = 400) {
        $this->codigoBanco = '341';
        $this->nomeBanco = 'Itaú';
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
        $linha .= $this->formatarNumerico($dadosBanco['conta'], 5); // 032-036
        $linha .= $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1); // 037
        $linha .= str_repeat('0', 4); // 038-041
        $linha .= $this->formatarAlfanumerico($dadosBanco['cedente'], 30); // 042-071
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 072-074
        $linha .= $this->formatarAlfanumerico('ITAÚ', 15); // 075-089
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
        $linha .= $this->formatarNumerico($dadosBanco['conta'], 5); // 007-011
        $linha .= $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1); // 012
        $linha .= str_repeat('0', 4); // 013-016
        $linha .= $this->formatarAlfanumerico($titulo['nosso_numero'] ?? $titulo['id'], 8); // 017-024
        $linha .= $this->formatarNumerico($dadosBanco['carteira'] ?? '109', 3); // 025-027
        $linha .= '01'; // 028-029
        $linha .= $this->formatarAlfanumerico($titulo['contrato'] ?? '', 10); // 030-039
        $linha .= $this->formatarData($titulo['datavencimento']); // 040-047
        $linha .= $this->formatarValor($titulo['valor_mensal'], 13); // 048-060
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 061-063
        $linha .= '00000'; // 064-068
        $linha .= '01'; // 069-070
        $linha .= 'N'; // 071
        $linha .= $this->formatarData(date('Y-m-d')); // 072-079
        $linha .= '00'; // 080-081
        $linha .= '00'; // 082-083
        $linha .= $this->formatarValor($titulo['juros_calculado'] ?? 0, 13); // 084-096
        $linha .= $this->formatarData($titulo['datavencimento']); // 097-104
        $linha .= $this->formatarValor(0, 13); // 105-117
        $linha .= $this->formatarValor(0, 13); // 118-130
        $linha .= $this->formatarValor($titulo['multa_calculada'] ?? 0, 13); // 131-143
        $linha .= $this->formatarAlfanumerico($titulo['cliente_nome'] ?? '', 30); // 144-173
        $linha .= str_repeat(' ', 40); // 174-213
        $linha .= str_repeat(' ', 12); // 214-225
        $linha .= $this->formatarAlfanumerico($titulo['cep'] ?? '', 8); // 226-233
        $linha .= str_repeat(' ', 60); // 234-293
        $linha .= str_repeat('0', 6); // 294-299
        $linha .= ' '; // 300
        $linha .= str_repeat(' ', 78); // 301-378
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

