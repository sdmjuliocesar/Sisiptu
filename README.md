# Sistema de Gestão de IPTU (SISIPTU)

Sistema de gestão de IPTU com página de login.

## Estrutura de Pastas

```
SISIPTU/
├── index.html          # Página principal de login
├── css/
│   └── style.css      # Estilos CSS
├── js/
│   └── script.js      # JavaScript para validação e AJAX
├── php/
│   └── login.php      # Processamento do login
├── img/
│   └── iptu-pagamento.jpg  # Imagem de pagamento de IPTU (adicionar)
├── config/
│   └── database.php   # Configurações do banco de dados
└── README.md          # Este arquivo
```

## Configuração

1. **Banco de Dados**: Edite o arquivo `config/database.php` com suas credenciais do banco de dados.

2. **Imagem**: Adicione uma imagem de pagamento de IPTU na pasta `img/` com o nome `iptu-pagamento.jpg`.

## Credenciais de Teste

- **Usuário**: admin
- **Senha**: admin123

## Requisitos

- PHP 7.0 ou superior
- MySQL/MariaDB
- Servidor web (Apache/Nginx) ou XAMPP

## Instalação

1. Coloque os arquivos na pasta `htdocs` do XAMPP
2. Configure o banco de dados em `config/database.php`
3. Acesse `http://localhost/SISIPTU/`

## Importação de Clientes

O sistema permite a importação em massa de clientes através de arquivos CSV ou TXT.

### Formato do Arquivo

O arquivo deve conter as seguintes colunas na ordem especificada:

1. **CPF/CNPJ** (obrigatório) - CPF ou CNPJ do cliente, será validado automaticamente
2. **Nome** (obrigatório) - Nome completo do cliente
3. **Tipo Cadastro** - Tipo de cadastro (ex: Pessoa Física, Pessoa Jurídica)
4. **CEP** - CEP do endereço
5. **Endereço** - Logradouro e número
6. **Bairro** - Bairro
7. **Cidade** - Cidade
8. **UF** - Estado (sigla de 2 letras)
9. **Cód Município** - Código do município
10. **Data Nasc** - Data de nascimento (formato: DD/MM/YYYY ou YYYY-MM-DD)
11. **Profissão** - Profissão
12. **Identidade** - Número da identidade
13. **Estado Civil** - Estado civil
14. **Nacionalidade** - Nacionalidade
15. **Regime Casamento** - Regime de casamento
16. **Email** - E-mail
17. **Site** - Site/Website
18. **Tel Comercial** - Telefone comercial
19. **Tel Celular1** - Telefone celular principal
20. **Tel Celular2** - Telefone celular secundário
21. **Tel Residencial** - Telefone residencial
22. **CPF Cônjuge** - CPF do cônjuge
23. **Nome Cônjuge** - Nome do cônjuge
24. **Ativo** - Status ativo (true/1 ou false/0, padrão: true)

### Observações

- Apenas CPF/CNPJ e Nome são campos obrigatórios
- Se já existir um cliente com o mesmo CPF/CNPJ, o registro será ignorado
- O CPF/CNPJ será validado automaticamente antes da importação
- Campos opcionais podem ser deixados em branco
- O arquivo pode usar vírgula, ponto e vírgula, tabulação ou pipe como delimitador



