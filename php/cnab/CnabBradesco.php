<?php
/**
 * Implementação CNAB 400 para Bradesco
 */
class CnabBradesco extends CnabAbstract {
    public function __construct(int $versaoCnab = 400) {
        $this->codigoBanco = '237';
        $this->nomeBanco = 'Bradesco';
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
        $linha .= $this->formatarNumerico($dadosBanco['conta'], 7); // 032-038
        $linha .= $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1); // 039
        $linha .= str_repeat('0', 6); // 040-045
        $linha .= $this->formatarAlfanumerico($dadosBanco['cedente'], 30); // 046-075
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 076-078
        $linha .= $this->formatarAlfanumerico('BRADESCO', 15); // 079-093
        $linha .= date('dmy'); // 094-099
        $linha .= str_repeat(' ', 8); // 100-107
        $linha .= str_repeat(' ', 7); // 108-114
        $linha .= str_pad($totalTitulos, 6, '0', STR_PAD_LEFT); // 115-120
        $linha .= str_repeat(' ', 274); // 121-394
        $linha .= str_pad('1', 6, '0', STR_PAD_LEFT); // 395-400
        
        return $linha;
    }
    
    private function gerarRegistroTitulo(array $dadosBanco, array $titulo, int $sequencial): string {
        $linha = '';
        $linha .= '1'; // 001 - Tipo de registro
        $linha .= $this->formatarNumerico($dadosBanco['agencia'], 4); // 002-005 - Agência mantenedora da conta
        $linha .= $this->formatarNumerico($dadosBanco['dv_agencia'] ?? '', 1); // 006 - Dígito verificador da agência
        $linha .= $this->formatarNumerico($dadosBanco['conta'], 7); // 007-013 - Número da conta corrente
        $linha .= $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1); // 014 - Dígito verificador da conta
        $linha .= str_repeat('0', 6); // 015-020 - Zeros
        // Nosso número deve ser numérico e ter 11 caracteres
        $nossoNumero = $this->apenasNumeros($titulo['nosso_numero'] ?? $titulo['id'] ?? '');
        $linha .= $this->formatarNumerico($nossoNumero, 11); // 021-031 - Identificação do título no banco
        $linha .= $this->formatarNumerico($dadosBanco['carteira'] ?? '06', 2); // 032-033 - Código da carteira
        $linha .= '01'; // 034-035 - Código da ocorrência
        $linha .= $this->formatarAlfanumerico($titulo['contrato'] ?? '', 10); // 036-045 - Seu número
        $linha .= $this->formatarData($titulo['datavencimento']); // 046-053 - Data de vencimento
        $linha .= $this->formatarValor($titulo['valor_mensal'], 13); // 054-066 - Valor do título
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 067-069 - Código do banco cobrador
        $linha .= '00000'; // 070-074 - Agência cobradora
        $linha .= '01'; // 075-076 - Espécie de documento
        $linha .= 'N'; // 077 - Aceite
        $linha .= $this->formatarData(date('Y-m-d')); // 078-085 - Data de emissão do título
        $linha .= '00'; // 086-087 - Primeira instrução
        $linha .= '00'; // 088-089 - Segunda instrução
        $linha .= $this->formatarValor($titulo['juros_calculado'] ?? 0, 13); // 090-102 - Valor de juros por dia de atraso
        $linha .= $this->formatarData($titulo['datavencimento']); // 103-110 - Data limite para desconto
        $linha .= $this->formatarValor(0, 13); // 111-123 - Valor do desconto
        $linha .= $this->formatarValor(0, 13); // 124-136 - Valor do IOF
        $linha .= $this->formatarValor($titulo['multa_calculada'] ?? 0, 13); // 137-149 - Valor do abatimento
        $nomeCliente = $this->obterNomeCliente($titulo);
        $linha .= $this->formatarAlfanumerico($nomeCliente, 25); // 150-174 - Identificação do sacado
        $enderecoCompleto = $this->montarEnderecoCliente($titulo, 40);
        $linha .= $this->formatarAlfanumerico($enderecoCompleto, 40); // 175-214 - Endereço do sacado
        $linha .= str_repeat(' ', 12); // 215-226 - Primeira mensagem
        $cep = $this->obterCepCliente($titulo);
        $linha .= $this->formatarAlfanumerico($cep, 8); // 227-234 - CEP do sacado
        $linha .= str_repeat(' ', 60); // 235-294 - Sacador/Avalista
        $linha .= str_repeat('0', 6); // 295-300 - Número de dias para protesto
        $linha .= ' '; // 301 - Complemento do registro
        $linha .= str_repeat(' ', 77); // 302-378 - Complemento do registro
        $linha .= str_pad($sequencial, 6, '0', STR_PAD_LEFT); // 379-384 - Número sequencial do registro
        
        // Validar que a linha tem exatamente 400 caracteres
        $tamanho = strlen($linha);
        if ($tamanho != 400) {
            throw new Exception("Linha de registro de título incompleta. Esperado: 400 caracteres, encontrado: {$tamanho} caracteres. Linha: " . substr($linha, 0, 100) . "...");
        }
        
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

