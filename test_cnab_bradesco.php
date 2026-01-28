<?php
/**
 * Script de teste para CnabBradesco
 * Gera um arquivo CNAB 400 de remessa com dados fictícios
 */

// Carregar autoloader
require_once __DIR__ . '/php/cnab/autoload.php';

// Definir diretório de saída (pasta atual)
$diretorioSaida = __DIR__ . '/test_remessa';

echo "=== TESTE DE GERAÇÃO CNAB 400 - BRADESCO ===\n\n";

try {
    // Criar instância do CnabBradesco
    $cnab = new CnabBradesco(400);
    echo "✓ Instância CnabBradesco criada\n";
    
    // Dados fictícios do banco Bradesco
    $dadosBanco = [
        'agencia' => '1234',
        'dv_agencia' => '5',
        'conta' => '1234567',
        'dv_conta' => '8',
        'codigo_cedente' => '12345678',
        'cedente' => 'EMPRESA TESTE LTDA',
        'carteira' => '06'
    ];
    
    echo "✓ Dados do banco configurados:\n";
    echo "  - Agência: {$dadosBanco['agencia']}-{$dadosBanco['dv_agencia']}\n";
    echo "  - Conta: {$dadosBanco['conta']}-{$dadosBanco['dv_conta']}\n";
    echo "  - Cedente: {$dadosBanco['cedente']}\n";
    echo "  - Carteira: {$dadosBanco['carteira']}\n\n";
    
    // Criar títulos fictícios
    $titulos = [
        [
            'id' => 1,
            'nosso_numero' => '12345678901',
            'contrato' => 'CT001',
            'datavencimento' => '2024-12-15',
            'valor_mensal' => 1500.00,
            'juros_calculado' => 2.50,
            'multa_calculada' => 75.00,
            'cliente_nome_completo' => 'JOÃO DA SILVA',
            'endereco_cliente' => 'RUA DAS FLORES, 123',
            'bairro_cliente' => 'CENTRO',
            'cidade_cliente' => 'SÃO PAULO',
            'uf_cliente' => 'SP',
            'cep_cliente' => '01234567'
        ],
        [
            'id' => 2,
            'nosso_numero' => '12345678902',
            'contrato' => 'CT002',
            'datavencimento' => '2024-12-20',
            'valor_mensal' => 2300.50,
            'juros_calculado' => 3.75,
            'multa_calculada' => 115.00,
            'cliente_nome_completo' => 'MARIA SANTOS OLIVEIRA',
            'endereco_cliente' => 'AVENIDA PAULISTA, 1000',
            'bairro_cliente' => 'BELA VISTA',
            'cidade_cliente' => 'SÃO PAULO',
            'uf_cliente' => 'SP',
            'cep_cliente' => '01310100'
        ],
        [
            'id' => 3,
            'nosso_numero' => '12345678903',
            'contrato' => 'CT003',
            'datavencimento' => '2024-12-25',
            'valor_mensal' => 850.25,
            'juros_calculado' => 1.25,
            'multa_calculada' => 42.50,
            'cliente_nome_completo' => 'PEDRO COSTA',
            'endereco_cliente' => 'RUA DOS JARDINS, 456',
            'bairro_cliente' => 'JARDIM AMÉRICA',
            'cidade_cliente' => 'RIO DE JANEIRO',
            'uf_cliente' => 'RJ',
            'cep_cliente' => '20040020'
        ],
        [
            'id' => 4,
            'nosso_numero' => '12345678904',
            'contrato' => 'CT004',
            'datavencimento' => '2025-01-05',
            'valor_mensal' => 3200.00,
            'juros_calculado' => 5.00,
            'multa_calculada' => 160.00,
            'cliente_nome_completo' => 'ANA PAULA FERREIRA',
            'endereco_cliente' => 'RUA DAS ACÁCIAS, 789',
            'bairro_cliente' => 'VILA NOVA',
            'cidade_cliente' => 'BELO HORIZONTE',
            'uf_cliente' => 'MG',
            'cep_cliente' => '30130100'
        ],
        [
            'id' => 5,
            'nosso_numero' => '12345678905',
            'contrato' => 'CT005',
            'datavencimento' => '2025-01-10',
            'valor_mensal' => 1750.75,
            'juros_calculado' => 2.90,
            'multa_calculada' => 87.50,
            'cliente_nome_completo' => 'CARLOS ALBERTO MENDES',
            'endereco_cliente' => 'AVENIDA ATLÂNTICA, 2000',
            'bairro_cliente' => 'COPACABANA',
            'cidade_cliente' => 'RIO DE JANEIRO',
            'uf_cliente' => 'RJ',
            'cep_cliente' => '22021000'
        ]
    ];
    
    echo "✓ " . count($titulos) . " títulos criados:\n";
    foreach ($titulos as $idx => $titulo) {
        echo "  " . ($idx + 1) . ". Contrato: {$titulo['contrato']} | Valor: R$ " . number_format($titulo['valor_mensal'], 2, ',', '.') . " | Vencimento: {$titulo['datavencimento']}\n";
    }
    echo "\n";
    
    // Gerar arquivo de remessa
    echo "Gerando arquivo de remessa...\n";
    $caminhoArquivo = $cnab->gerarRemessa($dadosBanco, $titulos, $diretorioSaida);
    
    echo "✓ Arquivo gerado com sucesso!\n";
    echo "  Caminho: {$caminhoArquivo}\n";
    echo "  Tamanho: " . number_format(filesize($caminhoArquivo), 0, ',', '.') . " bytes\n\n";
    
    // Exibir conteúdo do arquivo
    echo "=== CONTEÚDO DO ARQUIVO ===\n\n";
    $conteudo = file_get_contents($caminhoArquivo);
    $linhas = explode("\r\n", $conteudo);
    
    foreach ($linhas as $num => $linha) {
        if (empty(trim($linha))) continue;
        
        $tipo = '';
        if ($num === 0) {
            $tipo = ' [HEADER]';
        } elseif ($num === count($linhas) - 2) {
            $tipo = ' [TRAILER]';
        } else {
            $tipo = ' [TÍTULO ' . ($num) . ']';
        }
        
        echo "Linha " . str_pad($num + 1, 3, '0', STR_PAD_LEFT) . $tipo . " (" . strlen($linha) . " caracteres):\n";
        echo $linha . "\n\n";
        
        // Detalhar campos importantes do header
        if ($num === 0) {
            echo "  Detalhamento do Header:\n";
            echo "  - Tipo de registro: " . substr($linha, 0, 1) . "\n";
            echo "  - Tipo de operação: " . substr($linha, 1, 1) . "\n";
            echo "  - Tipo de serviço: " . substr($linha, 2, 7) . "\n";
            echo "  - Forma de lançamento: " . substr($linha, 9, 2) . "\n";
            echo "  - Tipo de cobrança: " . substr($linha, 11, 15) . "\n";
            echo "  - Agência: " . substr($linha, 26, 4) . "\n";
            echo "  - DV Agência: " . substr($linha, 30, 1) . "\n";
            echo "  - Conta: " . substr($linha, 31, 7) . "\n";
            echo "  - DV Conta: " . substr($linha, 38, 1) . "\n";
            echo "  - Cedente: " . trim(substr($linha, 45, 30)) . "\n";
            echo "  - Código do banco: " . substr($linha, 75, 3) . "\n";
            echo "  - Nome do banco: " . trim(substr($linha, 78, 15)) . "\n";
            echo "  - Data de geração: " . substr($linha, 93, 6) . "\n";
            echo "  - Total de títulos: " . substr($linha, 114, 6) . "\n";
            echo "\n";
        }
        
        // Detalhar campos importantes dos títulos
        if ($num > 0 && $num < count($linhas) - 2) {
            echo "  Detalhamento do Título:\n";
            echo "  - Tipo de registro: " . substr($linha, 0, 1) . "\n";
            echo "  - Agência: " . substr($linha, 1, 4) . "\n";
            echo "  - DV Agência: " . substr($linha, 5, 1) . "\n";
            echo "  - Conta: " . substr($linha, 6, 7) . "\n";
            echo "  - DV Conta: " . substr($linha, 13, 1) . "\n";
            echo "  - Nosso Número: " . substr($linha, 20, 11) . "\n";
            echo "  - Carteira: " . substr($linha, 31, 2) . "\n";
            echo "  - Ocorrência: " . substr($linha, 33, 2) . "\n";
            echo "  - Seu Número (Contrato): " . trim(substr($linha, 35, 10)) . "\n";
            echo "  - Data Vencimento: " . substr($linha, 45, 8) . "\n";
            $valor = substr($linha, 53, 13) / 100;
            echo "  - Valor: R$ " . number_format($valor, 2, ',', '.') . "\n";
            echo "  - Código do banco: " . substr($linha, 66, 3) . "\n";
            echo "  - Data de emissão: " . substr($linha, 77, 8) . "\n";
            $juros = substr($linha, 89, 13) / 100;
            echo "  - Juros por dia: R$ " . number_format($juros, 2, ',', '.') . "\n";
            $multa = substr($linha, 136, 13) / 100;
            echo "  - Multa: R$ " . number_format($multa, 2, ',', '.') . "\n";
            echo "  - Nome do sacado: " . trim(substr($linha, 149, 25)) . "\n";
            echo "  - Endereço: " . trim(substr($linha, 174, 40)) . "\n";
            echo "  - CEP: " . substr($linha, 226, 8) . "\n";
            echo "  - Sequencial: " . substr($linha, 378, 6) . "\n";
            echo "\n";
        }
        
        // Detalhar trailer
        if ($num === count($linhas) - 2) {
            echo "  Detalhamento do Trailer:\n";
            echo "  - Tipo de registro: " . substr($linha, 0, 1) . "\n";
            echo "  - Sequencial: " . substr($linha, 394, 6) . "\n";
            echo "\n";
        }
    }
    
    echo "=== TESTE CONCLUÍDO COM SUCESSO ===\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

