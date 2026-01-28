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
        // Número da remessa (7 dígitos) - sequencial ou número específico fornecido
        $numeroRemessa = $dadosBanco['numero_remessa'] ?? str_pad(substr(time(), -7), 7, '0', STR_PAD_LEFT);
        $linha .= $this->formatarNumerico($numeroRemessa, 7); // 111-117 - Número da remessa
        // Entre o MX e o número sequencial de registro, preencher 277 campos em branco (118-394)
        $linha .= str_repeat(' ', 277); // 118-394 - Brancos
        $linha .= str_pad('1', 6, '0', STR_PAD_LEFT); // 395-400
        
        return $linha;
    }
    
    private function gerarRegistroTitulo(array $dadosBanco, array $titulo, int $sequencial): string {
        $linha = '';
        $linha .= '1'; // 001 - Tipo de registro
        $linha .= str_repeat('0', 19); // 002-020 - Zeros
        // Posições 21-37: Carteira + Agência + Conta (17 posições) - formatar com zeros à direita
        $carteira = $this->formatarNumerico($dadosBanco['carteira'] ?? '06', 3); // 3 posições
        $agencia = $this->formatarNumerico($dadosBanco['agencia'], 4);
        $dvAgencia = $this->formatarNumerico($dadosBanco['dv_agencia'] ?? '', 1);
        $conta = $this->formatarNumerico($dadosBanco['conta'], 7);
        $dvConta = $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1);
        // Montar: carteira(3) + agencia(4) + dv_agencia(1) + conta(7) + dv_conta(1) = 16 caracteres
        // Preencher com zeros à direita até completar 17 posições (21-37)
        $carteiraAgenciaConta = $carteira . $agencia . $dvAgencia . $conta . $dvConta;
        $linha .= str_pad($carteiraAgenciaConta, 17, '0', STR_PAD_RIGHT); // 021-037 - Carteira + Agência + Conta (zeros à direita)
        // Posições 38-62: Código do título (ID da tabela cobranca) - 25 posições (exatamente)
        $codigoTitulo = $titulo['id'] ?? '';
        $codigoTituloFormatado = $this->formatarNumerico($codigoTitulo, 25);
        // Garantir que tenha exatamente 25 caracteres (truncar se necessário)
        $linha .= substr($codigoTituloFormatado, 0, 25); // 038-062 - Código do título (ID) - exatamente 25 posições
        $linha .= '000'; // 063-065 - Zeros
        $linha .= '2'; // 066 - Número 2
        // Percentual de multa (4 posições) - formato: 2 casas decimais (ex: 2.00 = 0200)
        $percentualMulta = $dadosBanco['multa_mes'] ?? 0;
        // Converter para formato numérico com 2 casas decimais e remover ponto decimal
        // Exemplo: 2.00 -> 0200, 10.50 -> 1050
        $percentualMultaFormatado = str_pad(str_replace('.', '', number_format((float)$percentualMulta, 2, '.', '')), 4, '0', STR_PAD_LEFT);
        $linha .= $this->formatarNumerico($percentualMultaFormatado, 4); // 067-070 - Percentual de multa
        $linha .= str_repeat('0', 11); // 071-081 - 11 zeros
        $linha .= str_repeat('0', 11); // 082-092 - 11 zeros
        $linha .= '1'; // 093 - Número 1
        $linha .= 'N'; // 094 - Letra N
        $linha .= str_repeat(' ', 14); // 095-108 - Brancos
        $linha .= '01'; // 109-110 - Código 01
        // Posições 111-120: Código do título (ID da tabela cobranca) - 10 posições
        $codigoTitulo = $titulo['id'] ?? '';
        $linha .= $this->formatarNumerico($codigoTitulo, 10); // 111-120 - Código do título (ID)
        // Posições 121-126: Data de vencimento do título (6 posições - formato ddmmyy)
        $linha .= $this->formatarData2Digitos($titulo['datavencimento']); // 121-126 - Data de vencimento
        // Posições 127-139: Valor do título (13 posições)
        $linha .= $this->formatarValor($titulo['valor_mensal'], 13); // 127-139 - Valor do título
        $linha .= str_repeat('0', 8); // 140-147 - 8 zeros
        $linha .= '01'; // 148-149 - Código 01
        $linha .= 'N'; // 150 - Letra N
        // Posições 151-156: Data do dia (6 posições - formato ddmmyy)
        $linha .= $this->formatarData2Digitos(date('Y-m-d')); // 151-156 - Data do dia
        $linha .= str_repeat('0', 4); // 157-160 - 4 zeros
        // Posições 161-173: Valor de juros calculado ao dia em atraso (13 posições)
        $linha .= $this->formatarValor($titulo['juros_calculado'] ?? 0, 13); // 161-173 - Valor de juros por dia de atraso
        // Posições 174-218: Preencher com zeros (45 posições)
        $linha .= str_repeat('0', 45); // 174-218 - 45 zeros
        // Posições 219-220: Tipo de documento (01 = CPF, 02 = CNPJ)
        $cpfCnpj = $this->apenasNumeros($titulo['cpf_cnpj_cliente'] ?? $titulo['cpf_cnpj'] ?? '');
        $tipoDocumento = (strlen($cpfCnpj) == 11) ? '01' : '02'; // 11 dígitos = CPF, 14 dígitos = CNPJ
        $linha .= $tipoDocumento; // 219-220 - Tipo de documento
        // Posições 221-234: CPF ou CNPJ (14 posições numéricas)
        $linha .= $this->formatarNumerico($cpfCnpj, 14); // 221-234 - CPF ou CNPJ
        // Posições 235-274: Nome do cliente (40 posições)
        $nomeCliente = $this->obterNomeCliente($titulo);
        $linha .= $this->formatarAlfanumerico($nomeCliente, 40); // 235-274 - Nome do cliente
        // Posições 275-314: Endereço (40 posições)
        $enderecoCompleto = $this->montarEnderecoCliente($titulo, 40);
        $linha .= $this->formatarAlfanumerico($enderecoCompleto, 40); // 275-314 - Endereço do sacado
        // Posições 315-326: 12 espaços
        $linha .= str_repeat(' ', 12); // 315-326 - 12 espaços
        // Posições 327-334: CEP (8 posições)
        $cep = $this->obterCepCliente($titulo);
        $linha .= $this->formatarAlfanumerico($cep, 8); // 327-334 - CEP do sacado
        $linha .= str_repeat(' ', 60); // 335-394 - Sacador/Avalista
        $linha .= str_pad($sequencial, 6, '0', STR_PAD_LEFT); // 395-400 - Número sequencial do registro
        
        // Validar e corrigir se necessário para garantir exatamente 400 caracteres
        $tamanho = strlen($linha);
        if ($tamanho > 400) {
            // Se exceder, truncar para exatamente 400
            $linha = substr($linha, 0, 400);
        } elseif ($tamanho < 400) {
            // Se faltar, completar com espaços
            $linha = str_pad($linha, 400, ' ', STR_PAD_RIGHT);
        }
        
        // Validação final
        $tamanhoFinal = strlen($linha);
        if ($tamanhoFinal != 400) {
            throw new Exception("Linha de registro de título incompleta. Esperado: 400 caracteres, encontrado: {$tamanhoFinal} caracteres após correção. Linha: " . substr($linha, 0, 100) . "...");
        }
        
        return $linha;
    }
    
    /**
     * Gera registro tipo 2 (mensagens / descontos) - CNAB 400 Bradesco
     */
    private function gerarRegistroTipo2(array $dadosBanco, array $titulo, int $sequencial): string {
        $linha = '';
        
        // 001-001: Tipo de registro = '2'
        $linha .= '2'; // 001 - Tipo de registro (fixo 2)
        
        // 002-366: em branco (365 posições)
        $linha .= str_repeat(' ', 365); // 002-366 - Brancos
        
        // 367-382: Carteira + Agência + Conta igual ao registro 1 (16 posições)
        // Reaproveitando a mesma lógica do registro título
        $carteira = $this->formatarNumerico($dadosBanco['carteira'] ?? '06', 3); // 3 posições
        $agencia = $this->formatarNumerico($dadosBanco['agencia'], 4);          // 4 posições
        $dvAgencia = $this->formatarNumerico($dadosBanco['dv_agencia'] ?? '', 1); // 1 posição
        $conta = $this->formatarNumerico($dadosBanco['conta'], 7);              // 7 posições
        $dvConta = $this->formatarNumerico($dadosBanco['dv_conta'] ?? '', 1);   // 1 posição
        // Total: 3 + 4 + 1 + 7 + 1 = 16
        $carteiraAgenciaConta = $carteira . $agencia . $dvAgencia . $conta . $dvConta;
        $linha .= $carteiraAgenciaConta; // 367-382 - Carteira + Agência + Conta
        
        // 383-394: preencher com zeros (12 posições)
        $linha .= str_repeat('0', 12); // 383-394 - Zeros
        
        // 395-400: Número sequencial de registro
        $linha .= str_pad($sequencial, 6, '0', STR_PAD_LEFT); // 395-400 - Nº sequencial
        
        // Validação final
        $tamanhoFinal = strlen($linha);
        if ($tamanhoFinal != 400) {
            throw new Exception(
                "Linha de registro tipo 2 incompleta. Esperado: 400 caracteres, encontrado: {$tamanhoFinal} caracteres. Linha: " .
                substr($linha, 0, 100) . "..."
            );
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
        
        // Se já está no formato correto (6 dígitos)
        if (preg_match('/^\d{6}$/', $data)) {
            return $data;
        }
        
        // Converter de YYYY-MM-DD para DDMMYY
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $data, $matches)) {
            $ano = substr($matches[1], -2); // Últimos 2 dígitos do ano
            return $matches[3] . $matches[2] . $ano;
        }
        
        // Converter de DD/MM/YYYY para DDMMYY
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $data, $matches)) {
            $ano = substr($matches[3], -2); // Últimos 2 dígitos do ano
            return $matches[1] . $matches[2] . $ano;
        }
        
        // Se já está no formato DDMMYYYY (8 dígitos), converter para DDMMYY
        if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', $data, $matches)) {
            $ano = substr($matches[3], -2); // Últimos 2 dígitos do ano
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

