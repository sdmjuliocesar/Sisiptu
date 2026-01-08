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
    'agencia' => '1234',
    'conta' => '567890',
    'codigo_cedente' => '12345',
    'cedente' => 'Nome do Cedente',
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

## Adicionar Novo Banco

1. Criar nova classe estendendo `CnabAbstract`:

```php
class CnabNovoBanco extends CnabAbstract {
    public function __construct(int $versaoCnab = 400) {
        $this->codigoBanco = '999';
        $this->nomeBanco = 'Novo Banco';
        $this->versaoCnab = $versaoCnab;
    }
    
    public function gerarRemessa(array $dadosBanco, array $titulos, string $caminhoDestino): string {
        // Implementar lógica específica do banco
    }
    
    // Implementar métodos privados:
    // - gerarHeader()
    // - gerarRegistroTitulo()
    // - gerarTrailer()
    // - gerarNomeArquivo()
}
```

2. Registrar no Factory:

```php
// Em CnabFactory.php
private static $bancos = [
    // ... bancos existentes
    '999' => 'CnabNovoBanco',
];
```

## Formato dos Dados

### Dados do Banco

```php
[
    'agencia' => '1234',              // Agência (obrigatório)
    'dv_agencia' => '5',              // Dígito verificador da agência
    'conta' => '567890',              // Conta corrente (obrigatório)
    'dv_conta' => '1',                // Dígito verificador da conta
    'codigo_cedente' => '12345',      // Código do cedente (obrigatório)
    'cedente' => 'Nome do Cedente',   // Nome do cedente (obrigatório)
    'carteira' => '21',               // Carteira
    'num_banco' => '001'              // Número do banco
]
```

### Dados do Título

```php
[
    'id' => 123,                      // ID do título
    'valor_mensal' => 1000.00,        // Valor (obrigatório)
    'datavencimento' => '2024-01-15', // Data de vencimento (obrigatório)
    'cliente_nome' => 'João Silva',   // Nome do cliente
    'contrato' => '12345',            // Número do contrato
    'nosso_numero' => '123456',       // Nosso número
    'juros_calculado' => 10.50,       // Juros calculados
    'multa_calculada' => 5.00,        // Multa calculada
    'cep' => '12345678'               // CEP do cliente
]
```

## Métodos Utilitários Disponíveis

A classe `CnabAbstract` fornece métodos úteis:

- `apenasNumeros($valor)` - Remove caracteres não numéricos
- `formatarValor($valor, $tamanho)` - Formata valor monetário
- `formatarData($data)` - Formata data para DDMMYYYY
- `formatarAlfanumerico($valor, $tamanho)` - Formata campo alfanumérico
- `formatarNumerico($valor, $tamanho)` - Formata campo numérico
- `removerAcentos($string)` - Remove acentos
- `modulo11($numero, $base)` - Calcula dígito verificador módulo 11
- `modulo10($numero)` - Calcula dígito verificador módulo 10

## Integração com Cobrança Automática

O sistema está integrado com a tela de "Cobrança Automática". Quando o checkbox "Remissão de Boletos" está marcado, o sistema automaticamente:

1. Busca o banco configurado no empreendimento
2. Gera o arquivo CNAB com os títulos selecionados
3. Salva o arquivo no diretório configurado em `caminho_remessa`
4. Atualiza o status dos títulos para "ENVIADO"

## Logs

Todas as operações são registradas em `logs/erro_*.log` usando a função `logError()`.

## Notas

- O sistema suporta CNAB 400 por padrão. CNAB 240 pode ser implementado criando novas classes ou adicionando lógica condicional.
- Cada banco pode ter especificidades no formato. Consulte a documentação oficial do banco para ajustes.
- Os arquivos gerados seguem o padrão: `CB{codigo_banco}{data}{hora}.REM`

