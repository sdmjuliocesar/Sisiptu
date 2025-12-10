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



