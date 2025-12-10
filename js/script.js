document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const mensagem = document.getElementById('mensagem');
    const btnLogin = document.querySelector('.btn-login');
    
    // Validação do formulário antes do envio
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevenir envio padrão
        
        const usuario = document.getElementById('usuario').value.trim();
        const senha = document.getElementById('senha').value;
        
        // Limpar mensagens anteriores
        mensagem.className = 'mensagem';
        mensagem.textContent = '';
        mensagem.style.display = 'none';
        
        // Validação básica
        if (usuario === '') {
            mostrarMensagem('Por favor, preencha o campo usuário.', 'erro');
            return false;
        }
        
        if (senha === '') {
            mostrarMensagem('Por favor, preencha o campo senha.', 'erro');
            return false;
        }
        
        if (senha.length < 4) {
            mostrarMensagem('A senha deve ter pelo menos 4 caracteres.', 'erro');
            return false;
        }
        
        // Desabilitar botão durante o processamento
        btnLogin.disabled = true;
        btnLogin.textContent = 'Entrando...';
        
        // Enviar dados via AJAX
        const formData = new FormData(loginForm);
        
        fetch('php/login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagem(data.mensagem, 'sucesso');
                // Redirecionar após 1 segundo
                setTimeout(function() {
                    if (data.redirect) {
                        // Usar caminho relativo correto
                        window.location.href = data.redirect;
                    } else {
                        // Caminho absoluto a partir da raiz do servidor
                        window.location.href = '/SISIPTU/dashboard.php';
                    }
                }, 1000);
            } else {
                mostrarMensagem(data.mensagem, 'erro');
                btnLogin.disabled = false;
                btnLogin.textContent = 'Entrar';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarMensagem('Erro ao processar login. Tente novamente.', 'erro');
            btnLogin.disabled = false;
            btnLogin.textContent = 'Entrar';
        });
    });
    
    // Função para mostrar mensagens
    function mostrarMensagem(texto, tipo) {
        mensagem.textContent = texto;
        mensagem.className = 'mensagem ' + tipo;
        mensagem.style.display = 'block';
        
        // Remover mensagem após 5 segundos (apenas para erros)
        if (tipo === 'erro') {
            setTimeout(function() {
                mensagem.style.display = 'none';
            }, 5000);
        }
    }
    
    // Adicionar efeito de foco nos campos
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
    
    // Funcionalidade de mostrar/ocultar senha
    const togglePassword = document.getElementById('togglePassword');
    const senhaInput = document.getElementById('senha');
    
    if (togglePassword && senhaInput) {
        togglePassword.addEventListener('click', function() {
            const type = senhaInput.getAttribute('type') === 'password' ? 'text' : 'password';
            senhaInput.setAttribute('type', type);
            
            // Alternar classe para mudar o ícone
            this.classList.toggle('active');
            
            // Atualizar aria-label
            if (type === 'text') {
                this.setAttribute('aria-label', 'Ocultar senha');
                this.querySelector('.eye-icon').textContent = '👁️‍🗨️';
            } else {
                this.setAttribute('aria-label', 'Mostrar senha');
                this.querySelector('.eye-icon').textContent = '👁️';
            }
        });
    }
});

