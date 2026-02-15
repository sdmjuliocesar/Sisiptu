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
                // Registro 1 (detalhe principal)
                $registro1 = $this->gerarRegistroTitulo($dadosBanco, $titulo, $sequencial);
                fwrite($arquivo, $registro1 . "\r\n");
                $sequencial++;

                // Registro 2 (multa) - conforme solicitado
                $registro2 = $this->gerarRegistroMulta($dadosBanco, $titulo, $sequencial);
                fwrite($arquivo, $registro2 . "\r\n");
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
        // REGISTRO 0 (HEADER) - layout conforme posições informadas:
        // - 27 a 30: código da agência (4 posições)
        // - 31 a 32: completar com "00"
        // - 33 a 38: número da conta (6 posições)
        // - 39 a 46: 8 espaços
        // - 101 a 394: espaços
        // - 395 a 400: número sequencial
        $linha .= '0'; // 001
        $linha .= '1'; // 002
        $linha .= 'REMESSA'; // 003-009
        $linha .= '01'; // 010-011
        $linha .= $this->formatarAlfanumerico('COBRANCA', 15); // 012-026
        $linha .= $this->formatarNumerico($dadosBanco['agencia'], 4); // 027-030
        $linha .= '00'; // 031-032
        $linha .= $this->formatarNumerico($dadosBanco['conta'], 6); // 033-038
        $linha .= str_repeat(' ', 8); // 039-046
        $linha .= $this->formatarAlfanumerico($dadosBanco['cedente'], 30); // 047-076
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 077-079
        $linha .= $this->formatarAlfanumerico('ITAÚ', 15); // 080-094
        $linha .= date('dmy'); // 095-100
        $linha .= str_repeat(' ', 294); // 101-394
        $linha .= str_pad('1', 6, '0', STR_PAD_LEFT); // 395-400

        // Garantir exatamente 400 caracteres
        if (strlen($linha) > 400) {
            $linha = substr($linha, 0, 400);
        } elseif (strlen($linha) < 400) {
            $linha = str_pad($linha, 400, ' ', STR_PAD_RIGHT);
        }
        
        return $linha;
    }

    /**
     * Registro 2 (multa) - Itaú CNAB 400
     *
     * Especificação solicitada:
     * - 1 a 1: 2
     * - 2 a 2: 2
     * - 3 a 10: data da multa = 1 dia após vencimento (DDMMAAAA)
     * - 11 a 23: valor/percentual da multa (banco) em 13 posições numéricas
     * - 24 a 394: espaços
     * - 395 a 400: sequencial
     */
    private function gerarRegistroMulta(array $dadosBanco, array $titulo, int $sequencial): string
    {
        $dataMulta = $this->formatarData($this->adicionarDiasYmd($titulo['datavencimento'] ?? '', 1)); // DDMMYYYY (8)

        // Multa do banco (multa_mes) - enviar como percentual com 2 casas, sem separador, zeros à esquerda
        $multaMes = (float)str_replace(',', '.', (string)($dadosBanco['multa_mes'] ?? 0));
        $multaFormat = str_pad(str_replace('.', '', number_format($multaMes, 2, '.', '')), 13, '0', STR_PAD_LEFT);

        $linha = '';
        $linha .= '2'; // 001
        $linha .= '2'; // 002
        $linha .= $this->formatarNumerico($dataMulta, 8); // 003-010
        $linha .= $this->formatarNumerico($multaFormat, 13); // 011-023
        $linha .= str_repeat(' ', 371); // 024-394
        $linha .= str_pad((string)$sequencial, 6, '0', STR_PAD_LEFT); // 395-400

        // Garantir exatamente 400 caracteres
        if (strlen($linha) > 400) {
            $linha = substr($linha, 0, 400);
        } elseif (strlen($linha) < 400) {
            $linha = str_pad($linha, 400, ' ', STR_PAD_RIGHT);
        }

        return $linha;
    }
    
    private function gerarRegistroTitulo(array $dadosBanco, array $titulo, int $sequencial): string {
        // Registro 1 (DETALHE) - Itaú CNAB 400 (cobrança)
        // Baseado no layout: config/itau/cnab400/cobranca.yml (segmento_1)

        $linha = '';

        // Cedente (inscrição)
        $docCedente = $this->apenasNumeros($dadosBanco['cnpj_cpf'] ?? '');
        $codigoInscricaoCedente = (strlen($docCedente) === 11) ? '01' : '02'; // 01=CPF, 02=CNPJ
        $numeroInscricaoCedente = str_pad(substr($docCedente, 0, 14), 14, '0', STR_PAD_LEFT);

        // Pagador (inscrição)
        $docPagador = $this->apenasNumeros($titulo['cpf_cnpj_cliente'] ?? $titulo['cpf_cnpj'] ?? '');
        $codigoInscricaoPagador = (strlen($docPagador) === 11) ? '01' : '02';
        $numeroInscricaoPagador = str_pad(substr($docPagador, 0, 14), 14, '0', STR_PAD_LEFT);

        // Datas (ddmmyy)
        $venc6 = $this->formatarData6($titulo['datavencimento'] ?? '');
        $emissao6 = $this->formatarData6(date('Y-m-d'));
        $dataMora6 = $this->formatarData6($this->adicionarDiasYmd($titulo['datavencimento'] ?? '', 1));

        // Mora (juros por dia) a partir do juros ao mês do banco
        $valorTitulo = (float)str_replace(',', '.', (string)($titulo['valor_mensal'] ?? 0));
        $jurosMes = (float)str_replace(',', '.', (string)($dadosBanco['juros_mes'] ?? 0));
        $moraDia = 0.0;
        if ($valorTitulo > 0 && $jurosMes > 0) {
            $moraDia = ($valorTitulo * ($jurosMes / 100.0)) / 30.0;
        }

        // Campos principais
        $nossoNumero = $titulo['nosso_numero'] ?? $titulo['id'] ?? '';
        $nossoNumero = $this->formatarNumerico($nossoNumero, 8);

        $numeroCarteira = $this->formatarNumerico($dadosBanco['carteira'] ?? '109', 3);
        $tipoCarteira = $dadosBanco['tipo_carteira'] ?? 'I'; // X(1)

        // 038-062: Número do título (25 posições, preencher com espaços)
        $numeroTitulo25 = (string)($titulo['titulo'] ?? $titulo['id'] ?? '');
        $numeroTitulo25 = $this->formatarAlfanumerico($numeroTitulo25, 25);

        // 111-120: Código do título (10 posições, numérico)
        $codigoTitulo10 = $titulo['id'] ?? ($titulo['titulo'] ?? '');
        $codigoTitulo10 = $this->formatarNumerico($codigoTitulo10, 10);

        // Logradouro/bairro/cidade/uf
        $logradouro = $this->formatarAlfanumerico($this->montarEnderecoCliente($titulo, 40), 40);
        $bairro = $this->formatarAlfanumerico($titulo['bairro_cliente'] ?? '', 12);
        $cep = $this->formatarNumerico($this->obterCepCliente($titulo), 8);
        $cidade = $this->formatarAlfanumerico($titulo['cidade_cliente'] ?? '', 15);
        $estado = $this->formatarAlfanumerico($titulo['uf_cliente'] ?? '', 2);

        $nomePagador = $this->formatarAlfanumerico($this->obterNomeCliente($titulo), 30);

        // Montagem conforme posições
        $linha .= '1'; // 001
        $linha .= $this->formatarNumerico($codigoInscricaoCedente, 2); // 002-003
        $linha .= $this->formatarNumerico($numeroInscricaoCedente, 14); // 004-017
        $linha .= $this->formatarNumerico($dadosBanco['agencia'] ?? '', 4); // 018-021
        $linha .= '00'; // 022-023
        // 024-029: número da conta (6 posições)
        $linha .= $this->formatarNumerico($dadosBanco['conta'] ?? '', 6); // 024-029
        // 030-033: preencher com 4 zeros
        $linha .= '0000'; // 030-033
        // 034-037: preencher com 4 zeros (instrução/alegação)
        $linha .= '0000'; // 034-037 instrucao_alegacao
        $linha .= $numeroTitulo25; // 038-062
        $linha .= $nossoNumero; // 063-070
        $linha .= str_repeat('0', 13); // 071-083 quantidade_moeda
        $linha .= $numeroCarteira; // 084-086
        $linha .= str_repeat(' ', 21); // 087-107 uso_banco
        $linha .= $this->formatarAlfanumerico($tipoCarteira, 1); // 108
        $linha .= '01'; // 109-110 codigo_ocorrencia
        $linha .= $codigoTitulo10; // 111-120
        $linha .= $venc6; // 121-126 vencimento
        $linha .= $this->formatarValor($titulo['valor_mensal'] ?? 0, 13); // 127-139 valor
        $linha .= $this->formatarNumerico($this->codigoBanco, 3); // 140-142
        $linha .= '00000'; // 143-147 agencia_cobradora
        $linha .= '01'; // 148-149 especie
        $linha .= 'N'; // 150 aceite
        $linha .= $emissao6; // 151-156 data_emissao
        $linha .= '9494'; // 157-160 instrucao_1 + instrucao_2
        $linha .= $this->formatarValor($moraDia, 13); // 161-173 juros_1_dia (mora)
        $linha .= str_repeat('0', 6); // 174-179 desconto_ate
        $linha .= $this->formatarValor(0, 13); // 180-192 valor_desconto
        $linha .= $this->formatarValor(0, 13); // 193-205 valor_iof
        $linha .= $this->formatarValor(0, 13); // 206-218 abatimento
        $linha .= $this->formatarNumerico($codigoInscricaoPagador, 2); // 219-220
        $linha .= $this->formatarNumerico($numeroInscricaoPagador, 14); // 221-234
        $linha .= $nomePagador; // 235-264
        $linha .= str_repeat(' ', 10); // 265-274
        $linha .= $logradouro; // 275-314
        $linha .= $bairro; // 315-326
        $linha .= $cep; // 327-334
        $linha .= $cidade; // 335-349
        $linha .= $estado; // 350-351
        $linha .= $this->formatarAlfanumerico('', 30); // 352-381 sacador_avalista
        $linha .= str_repeat(' ', 4); // 382-385
        $linha .= $dataMora6; // 386-391 data_mora = vencimento + 1 dia
        $linha .= '00'; // 392-393 prazo
        $linha .= ' '; // 394
        $linha .= str_pad((string)$sequencial, 6, '0', STR_PAD_LEFT); // 395-400

        // Garantir exatamente 400 caracteres
        if (strlen($linha) > 400) {
            $linha = substr($linha, 0, 400);
        } elseif (strlen($linha) < 400) {
            $linha = str_pad($linha, 400, ' ', STR_PAD_RIGHT);
        }

        return $linha;
    }

    /**
     * Formata data para DDMMYY (6 posições)
     */
    private function formatarData6($data): string
    {
        if (empty($data)) {
            return str_repeat('0', 6);
        }

        // ddmmyy
        if (preg_match('/^\d{6}$/', (string)$data)) {
            return (string)$data;
        }

        // YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string)$data, $m)) {
            return $m[3] . $m[2] . substr($m[1], -2);
        }

        // DD/MM/YYYY
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', (string)$data, $m)) {
            return $m[1] . $m[2] . substr($m[3], -2);
        }

        // DDMMYYYY
        if (preg_match('/^(\d{2})(\d{2})(\d{4})$/', (string)$data, $m)) {
            return $m[1] . $m[2] . substr($m[3], -2);
        }

        return str_repeat('0', 6);
    }

    /**
     * Soma dias a uma data e retorna em YYYY-MM-DD
     */
    private function adicionarDiasYmd(string $data, int $dias): string
    {
        if ($data === '') return '';
        try {
            $dt = new DateTime($data);
            $dt->modify(($dias >= 0 ? '+' : '') . $dias . ' day');
            return $dt->format('Y-m-d');
        } catch (Throwable $e) {
            return '';
        }
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

