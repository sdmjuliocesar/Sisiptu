# Instruções para Fazer Push do Projeto para o GitHub

## 1. Instalar o Git

Se o Git não estiver instalado, baixe e instale:

- **Download**: https://git-scm.com/download/win
- Instale o Git normalmente (aceite as opções padrão)
- Após a instalação, reinicie o terminal/PowerShell

## 2. Configurar o Git (primeira vez)

Abra o PowerShell ou CMD e execute:

```bash
git config --global user.name "Seu Nome"
git config --global user.email "seu.email@exemplo.com"
```

## 3. Inicializar o Repositório e Fazer Push

No diretório do projeto (`C:\xampp\htdocs\SISIPTU`), execute os seguintes comandos:

```bash
# Inicializar o repositório Git
git init

# Adicionar todos os arquivos
git add .

# Criar o primeiro commit
git commit -m "Commit inicial do projeto SISIPTU"

# Adicionar o repositório remoto
git remote add origin https://github.com/sdmjuliocesar/Sisiptu.git

# Fazer push para o GitHub
git push -u origin main
```

**Nota**: Se a branch padrão for `master` em vez de `main`, use:
```bash
git push -u origin master
```

## 4. Para Atualizações Futuras

Após fazer alterações no código:

```bash
# Adicionar arquivos alterados
git add .

# Criar commit
git commit -m "Descrição das alterações"

# Fazer push
git push
```

## 5. Criar um .gitignore (Recomendado)

Crie um arquivo `.gitignore` na raiz do projeto para não enviar arquivos desnecessários:

```
# Arquivos de configuração sensíveis
config/database.php
*.log

# Arquivos temporários
*.tmp
*.bak
*~

# Diretórios de uploads (se não quiser versionar)
uploads/

# Arquivos do sistema
.DS_Store
Thumbs.db
```

## Problemas Comuns

### Erro: "fatal: not a git repository"
- Execute `git init` primeiro

### Erro: "fatal: remote origin already exists"
- Remova o remote existente: `git remote remove origin`
- Adicione novamente: `git remote add origin https://github.com/sdmjuliocesar/Sisiptu.git`

### Erro de autenticação
- O GitHub não aceita mais senhas via HTTPS
- Use um Personal Access Token (PAT) ou configure SSH
- Para criar um PAT: GitHub → Settings → Developer settings → Personal access tokens → Generate new token

