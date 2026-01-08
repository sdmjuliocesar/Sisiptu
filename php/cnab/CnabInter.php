<?php
/**
 * Implementação CNAB 400 para Banco Inter
 */
class CnabInter extends CnabAbstract {
    public function __construct(int $versaoCnab = 400) {
        $this->codigoBanco = '077';
        $this->nomeBanco = 'Banco Inter';
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
        $linha .= '0'; // 001 - Tipo de registro
        $linha .= '1'; // 002 - Tipo de operação
        $linha .= 'REMESSA'; // 003-009 - Identificação do tipo de operação
        $linha .= '01'; // 010-011 - Identificação do tipo de serviço
        $linha .= $this->formatarAlfanumerico('COBRANCA', 15); // 012-026 - Identificação por extenso do serviço
        $linha .= $this->formatarNumerico($dadosBanco['agencia'], 4); // 027-030 - Agência mantenedora da conta
        $linha .= $this->formatarNumerico($dadosBanco['dv_agencia'] ?? '', 1); // 031 - Dígito verificador da agência
        $linha .= $this->formatarNumerico($dadosBanco['conta'], 8); // 032-039 - Número da conta corrente
        $linha .= $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1); // 040 - Dígito verificador da conta
        $linha .= str_repeat('0', 6); // 041-046 - Complemento do registro
        $linha .= $this->formatarAlfanumerico($dadosBanco['cedente'], 30); // 047-076 - Nome do cedente
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 077-079 - Código do banco
        $linha .= $this->formatarAlfanumerico('BANCO INTER', 15); // 080-094 - Nome do banco
        $linha .= date('dmy'); // 095-100 - Data de gravação do arquivo
        $linha .= str_repeat(' ', 8); // 101-108 - Identificação do sistema
        $linha .= str_repeat(' ', 7); // 109-115 - Complemento do registro
        $linha .= str_pad($totalTitulos, 6, '0', STR_PAD_LEFT); // 116-121 - Número sequencial de remessa
        $linha .= str_repeat(' ', 273); // 122-394 - Complemento do registro
        $linha .= str_pad('1', 6, '0', STR_PAD_LEFT); // 395-400 - Número sequencial do registro
        
        return $linha;
    }
    
    /**
     * Gera registro de título (detalhe)
     */
    private function gerarRegistroTitulo(array $dadosBanco, array $titulo, int $sequencial): string {
        $linha = '';
        $linha .= '1'; // 001 - Tipo de registro
        $linha .= str_repeat('0', 16); // 002-017 - Zeros
        $linha .= $this->formatarAlfanumerico($dadosBanco['agencia'], 4); // 018-021 - Agência mantenedora da conta
        $linha .= $this->formatarNumerico($dadosBanco['dv_agencia'] ?? '', 1); // 022 - DV agência
        $linha .= $this->formatarNumerico($dadosBanco['conta'], 8); // 023-030 - Conta corrente
        $linha .= $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1); // 031 - DV conta
        $linha .= str_repeat('0', 4); // 032-035 - Zeros
        $linha .= $this->formatarAlfanumerico($titulo['nosso_numero'] ?? $titulo['id'], 11); // 036-046 - Identificação do título no banco
        $linha .= $this->formatarNumerico($dadosBanco['carteira'] ?? '112', 2); // 047-048 - Código da carteira (Inter usa 112)
        $linha .= '01'; // 049-050 - Código da ocorrência
        $linha .= $this->formatarAlfanumerico($titulo['contrato'] ?? '', 10); // 051-060 - Seu número
        $linha .= $this->formatarData($titulo['datavencimento']); // 061-068 - Data de vencimento
        $linha .= $this->formatarValor($titulo['valor_mensal'], 13); // 069-081 - Valor do título
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 082-084 - Banco cobrador
        $linha .= '00000'; // 085-089 - Agência cobradora
        $linha .= '01'; // 090-091 - Espécie de documento
        $linha .= 'N'; // 092 - Aceite
        $linha .= $this->formatarData(date('Y-m-d')); // 093-100 - Data de emissão
        $linha .= '00'; // 101-102 - Instrução 1
        $linha .= '00'; // 103-104 - Instrução 2
        $linha .= $this->formatarValor($titulo['juros_calculado'] ?? 0, 13); // 105-117 - Valor de juros por dia de atraso
        $linha .= $this->formatarData($titulo['datavencimento']); // 118-125 - Data limite para desconto
        $linha .= $this->formatarValor(0, 13); // 126-138 - Valor do desconto
        $linha .= $this->formatarValor(0, 13); // 139-151 - Valor do IOF
        $linha .= $this->formatarValor($titulo['multa_calculada'] ?? 0, 13); // 152-164 - Valor do abatimento
        $nomeCliente = $this->obterNomeCliente($titulo);
        $linha .= $this->formatarAlfanumerico($nomeCliente, 25); // 165-189 - Identificação do sacado
        $enderecoCompleto = $this->montarEnderecoCliente($titulo, 40);
        $linha .= $this->formatarAlfanumerico($enderecoCompleto, 40); // 190-229 - Endereço do sacado
        $linha .= str_repeat(' ', 12); // 230-241 - Primeira mensagem
        $cep = $this->obterCepCliente($titulo);
        $linha .= $this->formatarAlfanumerico($cep, 8); // 242-249 - CEP
        $linha .= str_repeat(' ', 60); // 250-309 - Sacador/Avalista
        $linha .= str_repeat('0', 6); // 310-315 - Número de dias para protesto
        $linha .= ' '; // 316 - Complemento do registro
        $linha .= str_repeat(' ', 61); // 317-377 - Complemento do registro
        $linha .= str_pad($sequencial, 6, '0', STR_PAD_LEFT); // 378-383 - Número sequencial do registro
        
        return $linha;
    }
    
    /**
     * Gera o trailer do arquivo
     */
    private function gerarTrailer(array $dadosBanco, int $totalTitulos, int $sequencial): string {
        $linha = '';
        $linha .= '9'; // 001 - Tipo de registro
        $linha .= str_repeat(' ', 393); // 002-394 - Complemento do registro
        $linha .= str_pad($sequencial, 6, '0', STR_PAD_LEFT); // 395-400 - Número sequencial do registro
        
        return $linha;
    }
    
    /**
     * Gera nome do arquivo de remessa
     */
    private function gerarNomeArquivo(array $dadosBanco): string {
        $data = date('dmy');
        $hora = date('His');
        $agencia = $this->formatarNumerico($dadosBanco['agencia'], 4);
        $conta = $this->formatarNumerico($dadosBanco['conta'], 8);
        
        return "CB{$this->codigoBanco}{$data}{$hora}.REM";
    }
}

