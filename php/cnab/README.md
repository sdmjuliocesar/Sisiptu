# Sistema de Geração CNAB

Sistema escalável e independente para geração de arquivos CNAB 240 e 400 para múltiplos bancos.

## Estrutura

```
php/cnab/
├── CnabInterface.php      # Interface base para todas as implementações
├── CnabAbstract.php        # Classe abstrata com métodos utilitários
├── CnabFactory.php         # Factory para criar instâncias de bancos
├── CnabBancoBrasil.php    # Implementação Banco do Brasil (001)
├── CnabBradesco.php       # Implementação Bradesco (237)
├── CnabItau.php           # Implementação Itaú (341)
├── CnabSantander.php      # Implementação Santander (033)
├── CnabCaixa.php          # Implementação Caixa (104)
├── CnabSicredi.php        # Implementação Sicredi (748)
└── autoload.php           # Autoloader para classes CNAB
```

## Bancos Suportados

- **001** - Banco do Brasil
- **033** - Santander
- **104** - Caixa Econômica Federal
- **237** - Bradesco
- **341** - Itaú
- **748** - Sicredi

## Como Usar

### Via API

```php
POST /php/cnab_api.php
{
    "action": "gerar-remessa",
    "banco_id": 1,
    "titulos": [
        {"id": 123},
        {"id": 456}
    ]
}
```

### Via Código PHP

```php
require_once 'php/cnab/autoload.php';

// Criar instância do banco
$cnab = CnabFactory::criar('001', 400); // Banco do Brasil, CNAB 400

// Dados do banco
$dadosBanco = [
    'agencia' => '1234',              // Agência (obrigatório)
    'conta' => '567890',              // Conta corrente (obrigatório)
    'codigo_cedente' => '12345',      // Código do cedente (obrigatório)
    'cedente' => 'Nome do Cedente',   // Nome do cedente (obrigatório)
    'carteira' => '21'
];

// Títulos
$titulos = [
    [
        'id' => 1,
        'valor_mensal' => 1000.00,
        'datavencimento' => '2024-01-15',
        'cliente_nome' => 'João Silva',
        'contrato' => '12345'
    ]
];

// Gerar arquivo
$caminhoArquivo = $cnab->gerarRemessa($dadosBanco, $titulos, '/caminho/remessa');
```

## Integração com Cobrança Automática

O sistema está integrado com a tela de "Cobrança Automática". Quando o checkbox "Remissão de Boletos" está marcado, o sistema automaticamente:

1. Busca o banco configurado no empreendimento
2. Gera o arquivo CNAB com os títulos selecionados
3. Salva o arquivo no diretório configurado em `caminho_remessa`
4. Atualiza o status dos títulos para "ENVIADO"

