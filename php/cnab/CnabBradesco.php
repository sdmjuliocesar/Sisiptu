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
                // Registro tipo 1 (título)
                $registroTipo1 = $this->gerarRegistroTitulo($dadosBanco, $titulo, $sequencial);
                fwrite($arquivo, $registroTipo1 . "\r\n");
                $sequencial++;
                
                // Registro tipo 2 (mensagens / descontos), logo após o tipo 1
                $registroTipo2 = $this->gerarRegistroTipo2($dadosBanco, $titulo, $sequencial);
                fwrite($arquivo, $registroTipo2 . "\r\n");
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
        $linha .= $this->formatarNumerico($dadosBanco['codigo_cedente'] ?? '', 20); // 027-046 - Código do cedente (20 posições)
        $linha .= $this->formatarAlfanumerico($dadosBanco['cedente'], 30); // 047-076
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 077-079
        $linha .= $this->formatarAlfanumerico('BRADESCO', 15); // 080-094
        $linha .= date('dmy'); // 095-100
        $linha .= str_repeat(' ', 8); // 101-108
        $linha .= 'MX'; // 109-110 - Identificação MX
        // Número da remessa (7 dígitos)
        $numeroRemessa = $dadosBanco['numero_remessa'] ?? str_pad(substr(time(), -7), 7, '0', STR_PAD_LEFT);
        $linha .= $this->formatarNumerico($numeroRemessa, 7); // 111-117 - Número da remessa
        $linha .= str_repeat(' ', 277); // 118-394 - Brancos
        $linha .= str_pad('1', 6, '0', STR_PAD_LEFT); // 395-400
        
        return $linha;
    }
    
    private function gerarRegistroTitulo(array $dadosBanco, array $titulo, int $sequencial): string {
        $linha = '';
        $linha .= '1'; // 001 - Tipo de registro
        $linha .= str_repeat('0', 19); // 002-020 - Zeros
        
        $carteira = $this->formatarNumerico($dadosBanco['carteira'] ?? '06', 3);
        $agencia = $this->formatarNumerico($dadosBanco['agencia'], 4);
        $dvAgencia = $this->formatarNumerico($dadosBanco['dv_agencia'] ?? '', 1);
        $conta = $this->formatarNumerico($dadosBanco['conta'], 7);
        $dvConta = $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1);
        $carteiraAgenciaConta = $carteira . $agencia . $dvAgencia . $conta . $dvConta;
        $linha .= str_pad($carteiraAgenciaConta, 17, '0', STR_PAD_RIGHT); // 021-037
        
        $codigoTitulo = $titulo['id'] ?? '';
        $codigoTituloFormatado = $this->formatarNumerico($codigoTitulo, 25);
        $linha .= substr($codigoTituloFormatado, 0, 25); // 038-062
        
        $linha .= '000'; // 063-065
        $linha .= '2'; // 066
        
        $percentualMulta = $dadosBanco['multa_mes'] ?? 0;
        $percentualMultaFormatado = str_pad(str_replace('.', '', number_format((float)$percentualMulta, 2, '.', '')), 4, '0', STR_PAD_LEFT);
        $linha .= $this->formatarNumerico($percentualMultaFormatado, 4); // 067-070
        
        $linha .= str_repeat('0', 11); // 071-081
        $linha .= str_repeat('0', 11); // 082-092
        $linha .= '1'; // 093
        $linha .= 'N'; // 094
        $linha .= str_repeat(' ', 14); // 095-108
        $linha .= '01'; // 109-110
        
        $linha .= $this->formatarNumerico($codigoTitulo, 10); // 111-120
        $linha .= $this->formatarData2Digitos($titulo['datavencimento']); // 121-126
        $linha .= $this->formatarValor($titulo['valor_mensal'], 13); // 127-139
        $linha .= str_repeat('0', 8); // 140-147
        $linha .= '01'; // 148-149
        $linha .= 'N'; // 150
        $linha .= $this->formatarData2Digitos(date('Y-m-d')); // 151-156
        $linha .= str_repeat('0', 4); // 157-160
        $linha .= $this->formatarValor($titulo['juros_calculado'] ?? 0, 13); // 161-173
        $linha .= str_repeat('0', 45); // 174-218
        
        $cpfCnpj = $this->apenasNumeros($titulo['cpf_cnpj_cliente'] ?? $titulo['cpf_cnpj'] ?? '');
        $tipoDocumento = (strlen($cpfCnpj) == 11) ? '01' : '02';
        $linha .= $tipoDocumento; // 219-220
        $linha .= $this->formatarNumerico($cpfCnpj, 14); // 221-234
        
        $nomeCliente = $this->obterNomeCliente($titulo);
        $linha .= $this->formatarAlfanumerico($nomeCliente, 40); // 235-274
        
        $enderecoCompleto = $this->montarEnderecoCliente($titulo, 40);
        $linha .= $this->formatarAlfanumerico($enderecoCompleto, 40); // 275-314
        $linha .= str_repeat(' ', 12); // 315-326
        $cep = $this->obterCepCliente($titulo);
        $linha .= $this->formatarAlfanumerico($cep, 8); // 327-334
        $linha .= str_repeat(' ', 60); // 335-394
        $linha .= str_pad($sequencial, 6, '0', STR_PAD_LEFT); // 395-400
        
        $tamanho = strlen($linha);
        if ($tamanho > 400) {
            $linha = substr($linha, 0, 400);
        } elseif ($tamanho < 400) {
            $linha = str_pad($linha, 400, ' ', STR_PAD_RIGHT);
        }
        
        if (strlen($linha) != 400) {
            throw new Exception("Linha de registro de título incompleta. Esperado: 400 caracteres, encontrado: " . strlen($linha));
        }
        
        return $linha;
    }
    
    /**
     * Gera registro tipo 2 (mensagens / descontos) - CNAB 400 Bradesco
     */
    private function gerarRegistroTipo2(array $dadosBanco, array $titulo, int $sequencial): string {
        $linha = '';
        $linha .= '2'; // 001
        $linha .= str_repeat(' ', 365); // 002-366
        
        $carteira = $this->formatarNumerico($dadosBanco['carteira'] ?? '06', 3);
        $agencia = $this->formatarNumerico($dadosBanco['agencia'], 4);
        $dvAgencia = $this->formatarNumerico($dadosBanco['dv_agencia'] ?? '', 1);
        $conta = $this->formatarNumerico($dadosBanco['conta'], 7);
        $dvConta = $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1);
        $carteiraAgenciaConta = $carteira . $agencia . $dvAgencia . $conta . $dvConta;
        $linha .= $carteiraAgenciaConta; // 367-382
        
        $linha .= str_repeat('0', 12); // 383-394
        $linha .= str_pad($sequencial, 6, '0', STR_PAD_LEFT); // 395-400
        
        if (strlen($linha) != 400) {
            throw new Exception("Linha de registro tipo 2 incompleta. Esperado: 400 caracteres, encontrado: " . strlen($linha));
        }
        
        return $linha;
    }
    
    /**
     * Formata data com 2 dígitos no ano (DDMMYY)
     */
    private function formatarData2Digitos($data): string {
        if (empty($data)) {
            return str_repeat('0', 6);
        }
        
        if (preg_match('/^\d{6}$/', $data)) {
            return $data;
        }
        
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $data, $matches)) {
            $ano = substr($matches[1], -2);
            return $matches[3] . $matches[2] . $ano;
        }
        
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $data, $matches)) {
            $ano = substr($matches[3], -2);
            return $matches[1] . $matches[2] . $ano;
        }
        
        if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', $data, $matches)) {
            $ano = substr($matches[3], -2);
            return $matches[1] . $matches[2] . $ano;
        }
        
        return str_repeat('0', 6);
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

