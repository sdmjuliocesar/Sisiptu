// Variáveis globais
// Usa a variável loginTime definida em PHP (dashboard.php).
// Se não existir (fallback), define com o horário atual.
if (typeof loginTime === 'undefined') {
    loginTime = Math.floor(Date.now() / 1000);
}
let tempoLogadoInterval;

// Função de logout (definida globalmente antes de tudo)
function logout() {
    // Exibir mensagem de confirmação
    const confirmar = confirm('Deseja realmente sair do sistema?\n\nClique em "OK" para sair ou "Cancelar" para permanecer.');
    
    if (confirmar) {
        // Limpar intervalo do contador de tempo
        if (typeof tempoLogadoInterval !== 'undefined' && tempoLogadoInterval) {
            clearInterval(tempoLogadoInterval);
        }
        
        // Redirecionar para página de logout
        window.location.href = '/SISIPTU/php/logout.php';
    } else {
        // Usuário cancelou, não fazer nada
        return false;
    }
}

// Garantir que a função está disponível globalmente
window.logout = logout;

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    atualizarDataAtual();
    atualizarTempoLogado();
    iniciarAtualizacaoTempo();
    configurarMenu();
});

// Atualizar data atual
function atualizarDataAtual() {
    const agora = new Date();
    const opcoes = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    };
    const dataFormatada = agora.toLocaleDateString('pt-BR', opcoes);
    document.getElementById('data-atual').textContent = dataFormatada;
}

// Atualizar tempo logado
function atualizarTempoLogado() {
    const agora = Math.floor(Date.now() / 1000);
    const tempoDecorrido = agora - loginTime;
    
    const horas = Math.floor(tempoDecorrido / 3600);
    const minutos = Math.floor((tempoDecorrido % 3600) / 60);
    const segundos = tempoDecorrido % 60;
    
    const tempoFormatado = 
        String(horas).padStart(2, '0') + ':' +
        String(minutos).padStart(2, '0') + ':' +
        String(segundos).padStart(2, '0');
    
    document.getElementById('tempo-logado').textContent = tempoFormatado;
}

// Iniciar atualização automática do tempo
function iniciarAtualizacaoTempo() {
    atualizarTempoLogado();
    tempoLogadoInterval = setInterval(atualizarTempoLogado, 1000);
}

// Configurar menu lateral
function configurarMenu() {
    // Seletores melhorados
    const menuWithSubmenu = document.querySelectorAll('.menu-item-with-submenu > .menu-item');
    const submenuItems = document.querySelectorAll('.submenu-item');
    const allMenuItems = document.querySelectorAll('.sidebar-menu > ul > li > .menu-item');
    
    // Menu com submenu (Cadastro, IPTU)
    menuWithSubmenu.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const parent = this.closest('.menu-item-with-submenu');
            if (!parent) {
                console.error('Parent não encontrado');
                return;
            }
            
            const isActive = parent.classList.contains('active');
            
            // Fechar todos os submenus primeiro
            document.querySelectorAll('.menu-item-with-submenu').forEach(m => {
                if (m !== parent) {
                    m.classList.remove('active');
                }
            });
            
            // Remover active de todos os itens principais
            allMenuItems.forEach(mi => {
                if (mi !== this) {
                    mi.classList.remove('active');
                }
            });
            
            // Remover active de todos os submenu items
            submenuItems.forEach(smi => smi.classList.remove('active'));
            
            // Abrir/fechar o submenu clicado
            if (isActive) {
                parent.classList.remove('active');
            } else {
                parent.classList.add('active');
                // Forçar display do submenu
                const submenu = parent.querySelector('.submenu');
                if (submenu) {
                    submenu.style.display = 'block';
                }
            }
        });
    });
    
    // Itens de submenu
    submenuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover classe active de todos os itens
            allMenuItems.forEach(mi => mi.classList.remove('active'));
            submenuItems.forEach(smi => smi.classList.remove('active'));
            
            // Adicionar classe active ao item clicado
            this.classList.add('active');
            
            // Carregar conteúdo da página
            const page = this.getAttribute('data-page');
            carregarPagina(page);
        });
    });
    
    // Menu items normais (sem submenu) - Início, IPTU, Cobrança, Relatórios
    allMenuItems.forEach(item => {
        // Pular itens que têm submenu
        if (item.closest('.menu-item-with-submenu')) {
            return;
        }
        
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Fechar todos os submenus
            document.querySelectorAll('.menu-item-with-submenu').forEach(m => {
                m.classList.remove('active');
            });
            
            // Remover classe active de todos os itens
            allMenuItems.forEach(mi => mi.classList.remove('active'));
            submenuItems.forEach(smi => smi.classList.remove('active'));
            
            // Adicionar classe active ao item clicado
            this.classList.add('active');
            
            // Carregar conteúdo da página
            const page = this.getAttribute('data-page');
            carregarPagina(page);
        });
    });
}

// ---------- CRUD Usuários ----------

function inicializarCadastroUsuarios() {
    const form = document.getElementById('form-usuario');
    const btnNovo = document.getElementById('btn-novo-usuario');
    const mensagem = document.getElementById('usuarios-mensagem');
    const tabelaBody = document.querySelector('#tabela-usuarios tbody');

    if (!form || !tabelaBody) return;

    // Carregar lista inicial
    carregarUsuarios();

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('usuario-id').value;
        const nome = document.getElementById('usuario-nome').value.trim();
        const usuario = document.getElementById('usuario-usuario').value.trim();
        const senha = document.getElementById('usuario-senha').value;
        const email = document.getElementById('usuario-email').value.trim();
        const ativo = document.getElementById('usuario-ativo').checked ? '1' : '0';

        if (!nome || !usuario) {
            mostrarMensagemUsuarios('Preencha pelo menos Nome Completo e Usuário.', 'erro');
            return;
        }

        const formData = new FormData();
        formData.append('nome', nome);
        formData.append('usuario', usuario);
        formData.append('senha', senha);
        formData.append('email', email);
        formData.append('ativo', ativo);

        let action = 'create';
        if (id) {
            action = 'update';
            formData.append('id', id);
        }
        formData.append('action', action);

        fetch('/SISIPTU/php/usuarios_api.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    mostrarMensagemUsuarios(data.mensagem, 'sucesso');
                    form.reset();
                    document.getElementById('usuario-ativo').checked = true;
                    document.getElementById('usuario-id').value = '';
                    carregarUsuarios();
                } else {
                    mostrarMensagemUsuarios(data.mensagem, 'erro');
                }
            })
            .catch(err => {
                console.error(err);
                mostrarMensagemUsuarios('Erro ao salvar usuário.', 'erro');
            });
    });

    if (btnNovo) {
        btnNovo.addEventListener('click', function () {
            form.reset();
            document.getElementById('usuario-ativo').checked = true;
            document.getElementById('usuario-id').value = '';
            mostrarMensagemUsuarios('', null);
        });
    }

    // Delegação de eventos para botões de editar/excluir
    tabelaBody.addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        if (!id) return;

        if (btn.classList.contains('btn-edit')) {
            editarUsuario(id);
        } else if (btn.classList.contains('btn-delete')) {
            excluirUsuario(id);
        }
    });
}

function mostrarMensagemUsuarios(texto, tipo) {
    const msg = document.getElementById('usuarios-mensagem');
    if (!msg) return;

    if (!texto || !tipo) {
        msg.style.display = 'none';
        msg.textContent = '';
        msg.className = 'mensagem';
        return;
    }

    msg.textContent = texto;
    msg.className = 'mensagem ' + (tipo === 'sucesso' ? 'sucesso' : 'erro');
    msg.style.display = 'block';
}

function carregarUsuarios() {
    const tabelaBody = document.querySelector('#tabela-usuarios tbody');
    if (!tabelaBody) return;

    tabelaBody.innerHTML = '<tr><td colspan="7">Carregando...</td></tr>';

    fetch('/SISIPTU/php/usuarios_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                tabelaBody.innerHTML = '<tr><td colspan="7">' + (data.mensagem || 'Erro ao carregar usuários.') + '</td></tr>';
                return;
            }

            const usuarios = data.usuarios || [];
            if (usuarios.length === 0) {
                tabelaBody.innerHTML = '<tr><td colspan="7">Nenhum usuário cadastrado.</td></tr>';
                return;
            }

            tabelaBody.innerHTML = usuarios.map(u => {
                return `
                    <tr>
                        <td>${u.id}</td>
                        <td>${u.nome || ''}</td>
                        <td>${u.usuario || ''}</td>
                        <td>${u.email || ''}</td>
                        <td>${u.ativo ? 'Sim' : 'Não'}</td>
                        <td>${u.data_criacao ? u.data_criacao : ''}</td>
                        <td>
                            <div class="acoes">
                                <button type="button" class="btn-small btn-edit" data-id="${u.id}">Editar</button>
                                <button type="button" class="btn-small btn-delete" data-id="${u.id}">Excluir</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        })
        .catch(err => {
            console.error(err);
            tabelaBody.innerHTML = '<tr><td colspan="7">Erro ao carregar usuários.</td></tr>';
        });
}

function editarUsuario(id) {
    fetch('/SISIPTU/php/usuarios_api.php?action=get&id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso || !data.usuario) {
                mostrarMensagemUsuarios(data.mensagem || 'Erro ao carregar usuário.', 'erro');
                return;
            }

            const u = data.usuario;
            document.getElementById('usuario-id').value = u.id;
            document.getElementById('usuario-nome').value = u.nome || '';
            document.getElementById('usuario-usuario').value = u.usuario || '';
            document.getElementById('usuario-senha').value = ''; // não retornamos a senha
            document.getElementById('usuario-email').value = u.email || '';
            document.getElementById('usuario-ativo').checked = !!u.ativo;

            mostrarMensagemUsuarios('Usuário carregado para edição. Altere os dados e clique em Salvar.', 'sucesso');
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemUsuarios('Erro ao carregar usuário.', 'erro');
        });
}

function excluirUsuario(id) {
    if (!confirm('Deseja realmente excluir este usuário?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'delete');

    fetch('/SISIPTU/php/usuarios_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagemUsuarios(data.mensagem, 'sucesso');
                carregarUsuarios();
            } else {
                mostrarMensagemUsuarios(data.mensagem || 'Erro ao excluir usuário.', 'erro');
            }
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemUsuarios('Erro ao excluir usuário.', 'erro');
        });
}

// ---------- CRUD Empreendimentos ----------

function inicializarCadastroEmpreendimentos() {
    const form = document.getElementById('form-empreendimento');
    const btnNovo = document.getElementById('btn-novo-emp');
    const tabelaBody = document.querySelector('#tabela-empreendimentos tbody');

    if (!form || !tabelaBody) return;

    carregarEmpreendimentos();

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('emp-id').value;
        const nome = document.getElementById('emp-nome').value.trim();
        const descricao = document.getElementById('emp-descricao').value.trim();
        const endereco = document.getElementById('emp-endereco').value.trim();
        const bairro = document.getElementById('emp-bairro').value.trim();
        const cidade = document.getElementById('emp-cidade').value.trim();
        const uf = document.getElementById('emp-uf').value.trim().toUpperCase();
        const cep = document.getElementById('emp-cep').value.trim();
        const ativo = document.getElementById('emp-ativo').checked ? '1' : '0';

        if (!nome) {
            mostrarMensagemEmp('Preencha o Nome do Empreendimento.', 'erro');
            return;
        }

        const formData = new FormData();
        formData.append('nome', nome);
        formData.append('descricao', descricao);
        formData.append('endereco', endereco);
        formData.append('bairro', bairro);
        formData.append('cidade', cidade);
        formData.append('uf', uf);
        formData.append('cep', cep);
        formData.append('ativo', ativo);

        let action = 'create';
        if (id) {
            action = 'update';
            formData.append('id', id);
        }
        formData.append('action', action);

        fetch('/SISIPTU/php/empreendimentos_api.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    mostrarMensagemEmp(data.mensagem, 'sucesso');
                    form.reset();
                    document.getElementById('emp-ativo').checked = true;
                    document.getElementById('emp-id').value = '';
                    carregarEmpreendimentos();
                } else {
                    mostrarMensagemEmp(data.mensagem || 'Erro ao salvar empreendimento.', 'erro');
                }
            })
            .catch(err => {
                console.error(err);
                mostrarMensagemEmp('Erro ao salvar empreendimento.', 'erro');
            });
    });

    if (btnNovo) {
        btnNovo.addEventListener('click', function () {
            form.reset();
            document.getElementById('emp-ativo').checked = true;
            document.getElementById('emp-id').value = '';
            mostrarMensagemEmp('', null);
        });
    }

    tabelaBody.addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        if (!id) return;

        if (btn.classList.contains('btn-edit')) {
            editarEmpreendimento(id);
        } else if (btn.classList.contains('btn-delete')) {
            excluirEmpreendimento(id);
        }
    });
}

function mostrarMensagemEmp(texto, tipo) {
    const msg = document.getElementById('emp-mensagem');
    if (!msg) return;

    if (!texto || !tipo) {
        msg.style.display = 'none';
        msg.textContent = '';
        msg.className = 'mensagem';
        return;
    }

    msg.textContent = texto;
    msg.className = 'mensagem ' + (tipo === 'sucesso' ? 'sucesso' : 'erro');
    msg.style.display = 'block';
}

function carregarEmpreendimentos() {
    const tabelaBody = document.querySelector('#tabela-empreendimentos tbody');
    if (!tabelaBody) return;

    tabelaBody.innerHTML = '<tr><td colspan="7">Carregando...</td></tr>';

    fetch('/SISIPTU/php/empreendimentos_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                tabelaBody.innerHTML = '<tr><td colspan="7">' + (data.mensagem || 'Erro ao carregar empreendimentos.') + '</td></tr>';
                return;
            }

            const emps = data.empreendimentos || [];
            if (emps.length === 0) {
                tabelaBody.innerHTML = '<tr><td colspan="7">Nenhum empreendimento cadastrado.</td></tr>';
                return;
            }

            tabelaBody.innerHTML = emps.map(e => {
                return `
                    <tr>
                        <td>${e.id}</td>
                        <td>${e.nome || ''}</td>
                        <td>${e.cidade || ''}</td>
                        <td>${e.uf || ''}</td>
                        <td>${e.ativo ? 'Sim' : 'Não'}</td>
                        <td>${e.data_criacao ? e.data_criacao : ''}</td>
                        <td>
                            <div class="acoes">
                                <button type="button" class="btn-small btn-edit" data-id="${e.id}">Editar</button>
                                <button type="button" class="btn-small btn-delete" data-id="${e.id}">Excluir</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        })
        .catch(err => {
            console.error(err);
            tabelaBody.innerHTML = '<tr><td colspan="7">Erro ao carregar empreendimentos.</td></tr>';
        });
}

function editarEmpreendimento(id) {
    fetch('/SISIPTU/php/empreendimentos_api.php?action=get&id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso || !data.empreendimento) {
                mostrarMensagemEmp(data.mensagem || 'Erro ao carregar empreendimento.', 'erro');
                return;
            }

            const e = data.empreendimento;
            document.getElementById('emp-id').value = e.id;
            document.getElementById('emp-nome').value = e.nome || '';
            document.getElementById('emp-descricao').value = e.descricao || '';
            document.getElementById('emp-endereco').value = e.endereco || '';
            document.getElementById('emp-bairro').value = e.bairro || '';
            document.getElementById('emp-cidade').value = e.cidade || '';
            document.getElementById('emp-uf').value = e.uf || '';
            document.getElementById('emp-cep').value = e.cep || '';
            document.getElementById('emp-ativo').checked = !!e.ativo;

            mostrarMensagemEmp('Empreendimento carregado para edição. Altere os dados e clique em Salvar.', 'sucesso');
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemEmp('Erro ao carregar empreendimento.', 'erro');
        });
}

function excluirEmpreendimento(id) {
    if (!confirm('Deseja realmente excluir este empreendimento?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'delete');

    fetch('/SISIPTU/php/empreendimentos_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagemEmp(data.mensagem, 'sucesso');
                carregarEmpreendimentos();
            } else {
                mostrarMensagemEmp(data.mensagem || 'Erro ao excluir empreendimento.', 'erro');
            }
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemEmp('Erro ao excluir empreendimento.', 'erro');
        });
}

// ---------- CRUD Módulos ----------

function inicializarCadastroModulos() {
    const form = document.getElementById('form-modulo');
    const btnNovo = document.getElementById('btn-novo-mod');
    const tabelaBody = document.querySelector('#tabela-modulos tbody');

    if (!form || !tabelaBody) return;

    carregarEmpreendimentosSelectModulos();
    carregarModulos();

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('mod-id').value;
        const nome = document.getElementById('mod-nome').value.trim();
        const empreendimentoId = document.getElementById('mod-emp-id').value;
        const ativo = document.getElementById('mod-ativo').checked ? '1' : '0';

        if (!nome || !empreendimentoId) {
            mostrarMensagemMod('Preencha o campo Módulo e selecione um Empreendimento.', 'erro');
            return;
        }

        const formData = new FormData();
        formData.append('nome', nome);
        formData.append('empreendimento_id', empreendimentoId);
        formData.append('ativo', ativo);

        let action = 'create';
        if (id) {
            action = 'update';
            formData.append('id', id);
        }
        formData.append('action', action);

        fetch('/SISIPTU/php/modulos_api.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    mostrarMensagemMod(data.mensagem, 'sucesso');
                    form.reset();
                    document.getElementById('mod-ativo').checked = true;
                    document.getElementById('mod-id').value = '';
                    carregarModulos();
                } else {
                    mostrarMensagemMod(data.mensagem || 'Erro ao salvar módulo.', 'erro');
                }
            })
            .catch(err => {
                console.error(err);
                mostrarMensagemMod('Erro ao salvar módulo.', 'erro');
            });
    });

    if (btnNovo) {
        btnNovo.addEventListener('click', function () {
            form.reset();
            document.getElementById('mod-ativo').checked = true;
            document.getElementById('mod-id').value = '';
            mostrarMensagemMod('', null);
        });
    }

    tabelaBody.addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        if (!id) return;

        if (btn.classList.contains('btn-edit')) {
            editarModulo(id);
        } else if (btn.classList.contains('btn-delete')) {
            excluirModulo(id);
        }
    });
}

function carregarEmpreendimentosSelectModulos() {
    const select = document.getElementById('mod-emp-id');
    if (!select) return;

    // Limpar e adicionar opção padrão
    select.innerHTML = '<option value="">Selecione...</option>';

    fetch('/SISIPTU/php/empreendimentos_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                return;
            }

            const emps = data.empreendimentos || [];
            emps.forEach(e => {
                const opt = document.createElement('option');
                opt.value = e.id;
                opt.textContent = e.nome;
                select.appendChild(opt);
            });
        })
        .catch(err => {
            console.error(err);
        });
}

function mostrarMensagemMod(texto, tipo) {
    const msg = document.getElementById('mod-mensagem');
    if (!msg) return;

    if (!texto || !tipo) {
        msg.style.display = 'none';
        msg.textContent = '';
        msg.className = 'mensagem';
        return;
    }

    msg.textContent = texto;
    msg.className = 'mensagem ' + (tipo === 'sucesso' ? 'sucesso' : 'erro');
    msg.style.display = 'block';
}

function carregarModulos() {
    const tabelaBody = document.querySelector('#tabela-modulos tbody');
    if (!tabelaBody) return;

    tabelaBody.innerHTML = '<tr><td colspan="6">Carregando...</td></tr>';

    fetch('/SISIPTU/php/modulos_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                tabelaBody.innerHTML = '<tr><td colspan="6">' + (data.mensagem || 'Erro ao carregar módulos.') + '</td></tr>';
                return;
            }

            const mods = data.modulos || [];
            if (mods.length === 0) {
                tabelaBody.innerHTML = '<tr><td colspan="6">Nenhum módulo cadastrado.</td></tr>';
                return;
            }

            tabelaBody.innerHTML = mods.map(m => {
                return `
                    <tr>
                        <td>${m.id}</td>
                        <td>${m.nome || ''}</td>
                        <td>${m.empreendimento_nome || ''}</td>
                        <td>${m.ativo ? 'Sim' : 'Não'}</td>
                        <td>${m.data_criacao ? m.data_criacao : ''}</td>
                        <td>
                            <div class="acoes">
                                <button type="button" class="btn-small btn-edit" data-id="${m.id}">Editar</button>
                                <button type="button" class="btn-small btn-delete" data-id="${m.id}">Excluir</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        })
        .catch(err => {
            console.error(err);
            tabelaBody.innerHTML = '<tr><td colspan="6">Erro ao carregar módulos.</td></tr>';
        });
}

function editarModulo(id) {
    fetch('/SISIPTU/php/modulos_api.php?action=get&id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso || !data.modulo) {
                mostrarMensagemMod(data.mensagem || 'Erro ao carregar módulo.', 'erro');
                return;
            }

            const m = data.modulo;
            document.getElementById('mod-id').value = m.id;
            document.getElementById('mod-nome').value = m.nome || '';
            document.getElementById('mod-emp-id').value = m.empreendimento_id || '';
            document.getElementById('mod-ativo').checked = !!m.ativo;

            mostrarMensagemMod('Módulo carregado para edição. Altere os dados e clique em Salvar.', 'sucesso');
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemMod('Erro ao carregar módulo.', 'erro');
        });
}

function excluirModulo(id) {
    if (!confirm('Deseja realmente excluir este módulo?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'delete');

    fetch('/SISIPTU/php/modulos_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagemMod(data.mensagem, 'sucesso');
                carregarModulos();
            } else {
                mostrarMensagemMod(data.mensagem || 'Erro ao excluir módulo.', 'erro');
            }
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemMod('Erro ao excluir módulo.', 'erro');
        });
}

// ---------- CRUD Clientes ----------

function inicializarCadastroClientes() {
    const form = document.getElementById('form-cliente');
    const btnNovo = document.getElementById('btn-novo-cli');
    const btnBuscar = document.getElementById('btn-buscar-cli');
    const btnLimparBusca = document.getElementById('btn-limpar-busca-cli');
    const tabelaBody = document.querySelector('#tabela-clientes tbody');

    if (!form || !tabelaBody) return;

    // Máscara / formatação de CPF/CNPJ
    const inputCpfCnpj = document.getElementById('cli-cpf-cnpj');
    if (inputCpfCnpj) {
        inputCpfCnpj.addEventListener('input', function () {
            let v = this.value.replace(/[^0-9]/g, '');
            // Limitar a 14 dígitos (CNPJ)
            if (v.length > 14) v = v.slice(0, 14);

            // Aplicar máscara conforme o tamanho
            if (v.length <= 11) {
                // CPF: 000.000.000-00
                if (v.length > 9) {
                    v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, '$1.$2.$3-$4');
                } else if (v.length > 6) {
                    v = v.replace(/(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
                } else if (v.length > 3) {
                    v = v.replace(/(\d{3})(\d{0,3})/, '$1.$2');
                }
            } else {
                // CNPJ: 00.000.000/0000-00
                v = v.replace(
                    /(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2}).*/,
                    '$1.$2.$3/$4-$5'
                );
            }

            this.value = v;
        });
    }

    carregarClientes();

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('cli-id').value;
        const cpf_cnpj = document.getElementById('cli-cpf-cnpj').value.trim();
        const nome = document.getElementById('cli-nome').value.trim();
        const tipo_cadastro = document.getElementById('cli-tipo-cadastro').value.trim();
        const cep = document.getElementById('cli-cep').value.trim();
        const endereco = document.getElementById('cli-endereco').value.trim();
        const bairro = document.getElementById('cli-bairro').value.trim();
        const cidade = document.getElementById('cli-cidade').value.trim();
        const uf = document.getElementById('cli-uf').value.trim().toUpperCase();
        const cod_municipio = document.getElementById('cli-cod-municipio').value.trim();
        const data_nasc = document.getElementById('cli-data-nasc').value.trim();
        const profissao = document.getElementById('cli-profissao').value.trim();
        const identidade = document.getElementById('cli-identidade').value.trim();
        const estado_civil = document.getElementById('cli-estado-civil').value.trim();
        const nacionalidade = document.getElementById('cli-nacionalidade').value.trim();
        const regime_casamento = document.getElementById('cli-regime-casamento').value.trim();
        const email = document.getElementById('cli-email').value.trim();
        const site = document.getElementById('cli-site').value.trim();
        const tel_comercial = document.getElementById('cli-tel-comercial').value.trim();
        const tel_celular1 = document.getElementById('cli-tel-cel1').value.trim();
        const tel_celular2 = document.getElementById('cli-tel-cel2').value.trim();
        const tel_residencial = document.getElementById('cli-tel-residencial').value.trim();
        const ativo = document.getElementById('cli-ativo').checked ? '1' : '0';

        if (!cpf_cnpj || !nome) {
            mostrarMensagemCli('Preencha CPF/CNPJ e Nome do Cliente.', 'erro');
            return;
        }

        // Validação de CPF/CNPJ no frontend
        const docLimpo = cpf_cnpj.replace(/[^0-9]/g, '');
        const campoCpf = document.getElementById('cli-cpf-cnpj');

        // Deve ter 11 (CPF) ou 14 (CNPJ) dígitos numéricos
        if (docLimpo.length !== 11 && docLimpo.length !== 14) {
            if (campoCpf) {
                campoCpf.classList.add('input-error');
                campoCpf.value = '';
                campoCpf.placeholder = 'Digite 11 (CPF) ou 14 (CNPJ) dígitos';
                campoCpf.focus();
            }
            mostrarMensagemCli('CPF/CNPJ deve ter 11 (CPF) ou 14 (CNPJ) dígitos.', 'erro');
            return;
        }
        if (!validarCpfCnpjJs(docLimpo)) {
            if (campoCpf) {
                campoCpf.classList.add('input-error');
                campoCpf.value = '';
                campoCpf.placeholder = 'CPF/CNPJ inválido';
                campoCpf.focus();
            }
            mostrarMensagemCli('CPF/CNPJ inválido.', 'erro');
            return;
        }
        // Se chegou aqui, está válido: limpa estilo de erro/placeholder
        if (campoCpf) {
            campoCpf.classList.remove('input-error');
            campoCpf.placeholder = '';
        }

        const formData = new FormData();
        formData.append('cpf_cnpj', cpf_cnpj);
        formData.append('nome', nome);
        formData.append('tipo_cadastro', tipo_cadastro);
        formData.append('cep', cep);
        formData.append('endereco', endereco);
        formData.append('bairro', bairro);
        formData.append('cidade', cidade);
        formData.append('uf', uf);
        formData.append('cod_municipio', cod_municipio);
        formData.append('data_nasc', data_nasc);
        formData.append('profissao', profissao);
        formData.append('identidade', identidade);
        formData.append('estado_civil', estado_civil);
        formData.append('nacionalidade', nacionalidade);
        formData.append('regime_casamento', regime_casamento);
        formData.append('email', email);
        formData.append('site', site);
        formData.append('tel_comercial', tel_comercial);
        formData.append('tel_celular1', tel_celular1);
        formData.append('tel_celular2', tel_celular2);
        formData.append('tel_residencial', tel_residencial);
        formData.append('ativo', ativo);

        let action = 'create';
        if (id) {
            action = 'update';
            formData.append('id', id);
        }
        formData.append('action', action);

        fetch('/SISIPTU/php/clientes_api.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    mostrarMensagemCli(data.mensagem, 'sucesso');
                    form.reset();
                    document.getElementById('cli-ativo').checked = true;
                    document.getElementById('cli-id').value = '';
                    carregarClientes();
                } else {
                    const campoCpfDup = document.getElementById('cli-cpf-cnpj');
                    const msg = data.mensagem || 'Erro ao salvar cliente.';
                    // Se for mensagem de cliente já cadastrado, marcar o campo CPF/CNPJ
                    if (
                        campoCpfDup &&
                        msg &&
                        msg.includes('Já existe') &&
                        msg.includes('CPF/CNPJ')
                    ) {
                        campoCpfDup.classList.add('input-error');
                        campoCpfDup.value = '';
                        campoCpfDup.placeholder = msg;
                        campoCpfDup.focus();
                    }
                    mostrarMensagemCli(msg, 'erro');
                }
            })
            .catch(err => {
                console.error(err);
                mostrarMensagemCli('Erro ao salvar cliente.', 'erro');
            });
    });

    if (btnNovo) {
        btnNovo.addEventListener('click', function () {
            form.reset();
            document.getElementById('cli-ativo').checked = true;
            document.getElementById('cli-id').value = '';
            mostrarMensagemCli('', null);
        });
    }

    if (btnBuscar) {
        btnBuscar.addEventListener('click', function () {
            const q = document.getElementById('cli-busca').value.trim();
            carregarClientes(q);
        });
    }

    if (btnLimparBusca) {
        btnLimparBusca.addEventListener('click', function () {
            document.getElementById('cli-busca').value = '';
            carregarClientes();
        });
    }

    tabelaBody.addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        if (!id) return;

        if (btn.classList.contains('btn-edit')) {
            editarCliente(id);
        } else if (btn.classList.contains('btn-delete')) {
            excluirCliente(id);
        }
    });
}

function mostrarMensagemCli(texto, tipo) {
    const msg = document.getElementById('cli-mensagem');
    if (!msg) return;

    if (!texto || !tipo) {
        msg.style.display = 'none';
        msg.textContent = '';
        msg.className = 'mensagem';
        return;
    }

    msg.className = 'mensagem ' + (tipo === 'sucesso' ? 'sucesso' : 'erro');
    msg.style.display = 'block';

    // Limpar conteúdo anterior e montar com botão OK para melhor visualização
    msg.innerHTML = '';
    const span = document.createElement('span');
    span.textContent = texto;
    msg.appendChild(span);

    // Para erros, adiciona um botão OK para o usuário fechar a mensagem
    if (tipo !== 'sucesso') {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'OK';
        btn.style.marginLeft = '10px';
        btn.className = 'btn-small btn-message-ok';
        btn.addEventListener('click', function () {
            msg.style.display = 'none';
        });
        msg.appendChild(btn);
    }
}

function carregarClientes(q) {
    const tabelaBody = document.querySelector('#tabela-clientes tbody');
    if (!tabelaBody) return;

    tabelaBody.innerHTML = '<tr><td colspan="9">Carregando...</td></tr>';

    let url = '/SISIPTU/php/clientes_api.php?action=list';
    if (q && q !== '') {
        url += '&q=' + encodeURIComponent(q);
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                tabelaBody.innerHTML = '<tr><td colspan="9">' + (data.mensagem || 'Erro ao carregar clientes.') + '</td></tr>';
                return;
            }

            const clientes = data.clientes || [];
            if (clientes.length === 0) {
                tabelaBody.innerHTML = '<tr><td colspan="9">Nenhum cliente encontrado.</td></tr>';
                return;
            }

            tabelaBody.innerHTML = clientes.map(c => {
                return `
                    <tr>
                        <td>${c.id}</td>
                        <td>${c.cpf_cnpj || ''}</td>
                        <td>${c.nome || ''}</td>
                        <td>${c.cidade || ''}</td>
                        <td>${c.uf || ''}</td>
                        <td>${c.email || ''}</td>
                        <td>${c.tel_celular1 || ''}</td>
                        <td>${c.ativo ? 'Sim' : 'Não'}</td>
                        <td>
                            <div class="acoes">
                                <button type="button" class="btn-small btn-edit" data-id="${c.id}">Editar</button>
                                <button type="button" class="btn-small btn-delete" data-id="${c.id}">Excluir</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        })
        .catch(err => {
            console.error(err);
            tabelaBody.innerHTML = '<tr><td colspan="9">Erro ao carregar clientes.</td></tr>';
        });
}

function editarCliente(id) {
    fetch('/SISIPTU/php/clientes_api.php?action=get&id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso || !data.cliente) {
                mostrarMensagemCli(data.mensagem || 'Erro ao carregar cliente.', 'erro');
                return;
            }

            const c = data.cliente;
            document.getElementById('cli-id').value = c.id;
            document.getElementById('cli-cpf-cnpj').value = c.cpf_cnpj || '';
            document.getElementById('cli-nome').value = c.nome || '';
            document.getElementById('cli-tipo-cadastro').value = c.tipo_cadastro || '';
            document.getElementById('cli-cep').value = c.cep || '';
            document.getElementById('cli-endereco').value = c.endereco || '';
            document.getElementById('cli-bairro').value = c.bairro || '';
            document.getElementById('cli-cidade').value = c.cidade || '';
            document.getElementById('cli-uf').value = c.uf || '';
            document.getElementById('cli-cod-municipio').value = c.cod_municipio || '';
            document.getElementById('cli-data-nasc').value = c.data_nasc || '';
            document.getElementById('cli-profissao').value = c.profissao || '';
            document.getElementById('cli-identidade').value = c.identidade || '';
            document.getElementById('cli-estado-civil').value = c.estado_civil || '';
            document.getElementById('cli-nacionalidade').value = c.nacionalidade || '';
            document.getElementById('cli-regime-casamento').value = c.regime_casamento || '';
            document.getElementById('cli-email').value = c.email || '';
            document.getElementById('cli-site').value = c.site || '';
            document.getElementById('cli-tel-comercial').value = c.tel_comercial || '';
            document.getElementById('cli-tel-cel1').value = c.tel_celular1 || '';
            document.getElementById('cli-tel-cel2').value = c.tel_celular2 || '';
            document.getElementById('cli-tel-residencial').value = c.tel_residencial || '';
            document.getElementById('cli-ativo').checked = !!c.ativo;

            mostrarMensagemCli('Cliente carregado para edição. Altere os dados e clique em Salvar.', 'sucesso');
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemCli('Erro ao carregar cliente.', 'erro');
        });
}

function excluirCliente(id) {
    if (!confirm('Deseja realmente excluir este cliente?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'delete');

    fetch('/SISIPTU/php/clientes_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagemCli(data.mensagem, 'sucesso');
                carregarClientes();
            } else {
                mostrarMensagemCli(data.mensagem || 'Erro ao excluir cliente.', 'erro');
            }
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemCli('Erro ao excluir cliente.', 'erro');
        });
}

// ---------- CRUD Bancos ----------

function mostrarMensagemBanco(texto, tipo) {
    const el = document.getElementById('mensagem-banco');
    if (!el) return;
    if (!texto) {
        el.innerHTML = '';
        el.className = 'mensagem';
        return;
    }
    el.className = 'mensagem ' + (tipo === 'sucesso' ? 'mensagem-sucesso' : 'mensagem-erro');
    el.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
            <span>${texto}</span>
            <button type="button" class="btn-message-ok" onclick="this.parentNode.parentNode.innerHTML='';">
                OK
            </button>
        </div>
    `;
}

function inicializarCadastroBancos() {
    const form = document.getElementById('form-banco');
    const btnNovo = document.getElementById('btn-novo-banco');
    const tabelaBody = document.getElementById('tabela-bancos-body');

    if (!form || !tabelaBody) return;

    // Máscara e validação CPF/CNPJ
    const inputCpfCnpj = document.getElementById('banco-cnpj-cpf');
    if (inputCpfCnpj) {
        inputCpfCnpj.addEventListener('input', function () {
            let v = this.value.replace(/[^0-9]/g, '');
            if (v.length > 14) v = v.slice(0, 14);

            if (v.length <= 11) {
                if (v.length > 9) {
                    v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{0,2})/, '$1.$2.$3-$4');
                } else if (v.length > 6) {
                    v = v.replace(/(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
                } else if (v.length > 3) {
                    v = v.replace(/(\d{3})(\d{0,3})/, '$1.$2');
                }
            } else {
                v = v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2}).*/, '$1.$2.$3/$4-$5');
            }
            this.value = v;
        });
    }

    // Máscara para campos numéricos (Conta, Agência, Número do Banco)
    const campoConta = document.getElementById('banco-conta');
    if (campoConta) {
        campoConta.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    const campoAgencia = document.getElementById('banco-agencia');
    if (campoAgencia) {
        campoAgencia.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    const campoNumBanco = document.getElementById('banco-num-banco');
    if (campoNumBanco) {
        campoNumBanco.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    const campoCarteira = document.getElementById('banco-carteira');
    if (campoCarteira) {
        campoCarteira.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9A-Za-z]/g, '');
        });
    }

    // Referências para validação de campos obrigatórios
    const campoCedente = document.getElementById('banco-cedente');
    const campoBanco = document.getElementById('banco-banco');

    // Formatação monetária para multa, tarifa e juros
    const camposMonetarios = ['banco-multa-mes', 'banco-tarifa-bancaria', 'banco-juros-mes'];
    camposMonetarios.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            campo.addEventListener('input', function () {
                let v = this.value.replace(/[^0-9,]/g, '').replace(',', '.');
                if (v === '') {
                    this.value = '';
                    return;
                }
                const num = parseFloat(v);
                if (!isNaN(num)) {
                    this.value = num.toFixed(2).replace('.', ',');
                }
            });
            campo.addEventListener('blur', function () {
                let v = this.value.replace(/[^0-9,]/g, '').replace(',', '.');
                if (v === '') {
                    this.value = '';
                    return;
                }
                const num = parseFloat(v);
                if (!isNaN(num)) {
                    this.value = num.toFixed(2).replace('.', ',');
                }
            });
        }
    });

    // Função auxiliar para extrair caminho do diretório
    function extrairCaminhoDiretorio(files) {
        if (!files || files.length === 0) return '';
        
        const primeiroArquivo = files[0];
        let caminhoFinal = '';
        
        // Tentar obter caminho completo (funciona em Electron e alguns navegadores)
        if (primeiroArquivo.path) {
            // Caminho completo disponível (ex: C:\pasta\arquivo.txt)
            const caminhoCompleto = primeiroArquivo.path;
            const ultimaBarra = caminhoCompleto.lastIndexOf('\\') || caminhoCompleto.lastIndexOf('/');
            if (ultimaBarra > 0) {
                caminhoFinal = caminhoCompleto.substring(0, ultimaBarra);
            } else {
                caminhoFinal = caminhoCompleto;
            }
        } else if (primeiroArquivo.webkitRelativePath) {
            // Caminho relativo (ex: pasta/subpasta/arquivo.txt)
            const caminhoRelativo = primeiroArquivo.webkitRelativePath;
            const ultimaBarra = caminhoRelativo.lastIndexOf('/');
            if (ultimaBarra > 0) {
                caminhoFinal = caminhoRelativo.substring(0, ultimaBarra);
            } else {
                caminhoFinal = caminhoRelativo;
            }
        } else {
            // Fallback: usar o nome do arquivo como referência
            caminhoFinal = 'Diretório selecionado';
        }
        
        return caminhoFinal;
    }

    // Seleção de diretório para Remessa
    const btnProcurarRemessa = document.getElementById('btn-procurar-remessa');
    const fileRemessa = document.getElementById('file-remessa');
    const inputRemessa = document.getElementById('banco-remessa');
    
    if (btnProcurarRemessa && fileRemessa && inputRemessa) {
        btnProcurarRemessa.addEventListener('click', function() {
            fileRemessa.value = ''; // Limpar seleção anterior
            fileRemessa.click();
        });
        
        fileRemessa.addEventListener('change', function(e) {
            const files = e.target.files;
            if (files.length > 0) {
                const caminho = extrairCaminhoDiretorio(files);
                inputRemessa.value = caminho;
            }
        });
    }

    // Seleção de diretório para Retorno
    const btnProcurarRetorno = document.getElementById('btn-procurar-retorno');
    const fileRetorno = document.getElementById('file-retorno');
    const inputRetorno = document.getElementById('banco-retorno');
    
    if (btnProcurarRetorno && fileRetorno && inputRetorno) {
        btnProcurarRetorno.addEventListener('click', function() {
            fileRetorno.value = ''; // Limpar seleção anterior
            fileRetorno.click();
        });
        
        fileRetorno.addEventListener('change', function(e) {
            const files = e.target.files;
            if (files.length > 0) {
                const caminho = extrairCaminhoDiretorio(files);
                inputRetorno.value = caminho;
            }
        });
    }

    carregarBancos();

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('banco-id').value;
        const cedente = document.getElementById('banco-cedente').value.trim();
        const banco = document.getElementById('banco-banco').value.trim();
        const cnpj_cpf = document.getElementById('banco-cnpj-cpf').value.trim();
        
        // Validação de campos obrigatórios
        if (!cedente) {
            mostrarMensagemBanco('O campo Cedente é obrigatório.', 'erro');
            if (campoCedente) campoCedente.focus();
            return;
        }

        if (!banco) {
            mostrarMensagemBanco('O campo Banco é obrigatório.', 'erro');
            if (campoBanco) campoBanco.focus();
            return;
        }
        
        // Validação CPF/CNPJ
        if (cnpj_cpf !== '') {
            const docLimpo = cnpj_cpf.replace(/[^0-9]/g, '');
            if (docLimpo.length !== 11 && docLimpo.length !== 14) {
                mostrarMensagemBanco('CPF/CNPJ deve ter 11 (CPF) ou 14 (CNPJ) dígitos.', 'erro');
                inputCpfCnpj.focus();
                return;
            }
            if (!validarCpfCnpjJs(docLimpo)) {
                mostrarMensagemBanco('CPF/CNPJ inválido.', 'erro');
                inputCpfCnpj.focus();
                return;
            }
        }

        const formData = new FormData(form);
        
        // Converter valores monetários de vírgula para ponto
        const multa = formData.get('multa_mes');
        const tarifa = formData.get('tarifa_bancaria');
        const juros = formData.get('juros_mes');
        if (multa) formData.set('multa_mes', multa.replace(',', '.'));
        if (tarifa) formData.set('tarifa_bancaria', tarifa.replace(',', '.'));
        if (juros) formData.set('juros_mes', juros.replace(',', '.'));
        
        let action = 'create';
        if (id) {
            action = 'update';
            formData.append('id', id);
        }
        formData.append('action', action);

        fetch('/SISIPTU/php/bancos_api.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    mostrarMensagemBanco(data.mensagem, 'sucesso');
                    form.reset();
                    document.getElementById('banco-emissao-via-banco').checked = true;
                    document.getElementById('banco-ativo').checked = true;
                    document.getElementById('banco-id').value = '';
                    carregarBancos();
                } else {
                    mostrarMensagemBanco(data.mensagem || 'Erro ao salvar conta corrente.', 'erro');
                }
            })
            .catch(err => {
                console.error(err);
                mostrarMensagemBanco('Erro ao salvar conta corrente.', 'erro');
            });
    });

    if (btnNovo) {
        btnNovo.addEventListener('click', function () {
            form.reset();
            document.getElementById('banco-emissao-via-banco').checked = true;
            document.getElementById('banco-ativo').checked = true;
            document.getElementById('banco-id').value = '';
            mostrarMensagemBanco('', null);
        });
    }
}

function carregarBancos() {
    const tabelaBody = document.getElementById('tabela-bancos-body');
    if (!tabelaBody) return;

    tabelaBody.innerHTML = '<tr><td colspan="8">Carregando...</td></tr>';

    fetch('/SISIPTU/php/bancos_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                tabelaBody.innerHTML = '<tr><td colspan="8">Erro ao carregar contas correntes.</td></tr>';
                return;
            }

            const bancos = data.bancos || [];
            if (!bancos.length) {
                tabelaBody.innerHTML = '<tr><td colspan="8">Nenhuma conta corrente cadastrada.</td></tr>';
                return;
            }

            tabelaBody.innerHTML = bancos.map(b => {
                return `
                    <tr>
                        <td>${b.id}</td>
                        <td>${b.cedente || ''}</td>
                        <td>${b.cnpj_cpf || ''}</td>
                        <td>${b.banco || ''}</td>
                        <td>${b.conta || ''}</td>
                        <td>${b.agencia || ''}</td>
                        <td>${b.ativo ? 'Sim' : 'Não'}</td>
                        <td>
                            <div class="acoes">
                                <button type="button" class="btn-small btn-edit" data-id="${b.id}">Editar</button>
                                <button type="button" class="btn-small btn-delete" data-id="${b.id}">Excluir</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            tabelaBody.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', () => editarBanco(btn.getAttribute('data-id')));
            });
            tabelaBody.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', () => excluirBanco(btn.getAttribute('data-id')));
            });
        })
        .catch(err => {
            console.error(err);
            tabelaBody.innerHTML = '<tr><td colspan="8">Erro ao carregar contas correntes.</td></tr>';
        });
}

function editarBanco(id) {
    fetch('/SISIPTU/php/bancos_api.php?action=get&id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso || !data.banco) {
                mostrarMensagemBanco(data.mensagem || 'Erro ao carregar conta corrente.', 'erro');
                return;
            }

            const b = data.banco;
            document.getElementById('banco-id').value = b.id;
            document.getElementById('banco-cedente').value = b.cedente || '';
            
            // Aplicar máscara CPF/CNPJ
            let cnpjCpfFormatado = b.cnpj_cpf || '';
            if (cnpjCpfFormatado) {
                const docLimpo = cnpjCpfFormatado.replace(/[^0-9]/g, '');
                if (docLimpo.length === 11) {
                    cnpjCpfFormatado = docLimpo.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                } else if (docLimpo.length === 14) {
                    cnpjCpfFormatado = docLimpo.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
                }
            }
            document.getElementById('banco-cnpj-cpf').value = cnpjCpfFormatado;
            
            document.getElementById('banco-banco').value = b.banco || '';
            document.getElementById('banco-conta').value = b.conta || '';
            document.getElementById('banco-agencia').value = b.agencia || '';
            document.getElementById('banco-num-banco').value = b.num_banco || '';
            document.getElementById('banco-carteira').value = b.carteira || '';
            document.getElementById('banco-operacao-cc').value = b.operacao_cc || '';
            document.getElementById('banco-apelido').value = b.apelido || '';
            document.getElementById('banco-convenio').value = b.convenio || '';
            
            // Formatar valores monetários
            document.getElementById('banco-multa-mes').value = b.multa_mes ? parseFloat(b.multa_mes).toFixed(2).replace('.', ',') : '';
            document.getElementById('banco-tarifa-bancaria').value = b.tarifa_bancaria ? parseFloat(b.tarifa_bancaria).toFixed(2).replace('.', ',') : '';
            document.getElementById('banco-juros-mes').value = b.juros_mes ? parseFloat(b.juros_mes).toFixed(2).replace('.', ',') : '';
            
            document.getElementById('banco-prazo-devolucao').value = b.prazo_devolucao || '';
            document.getElementById('banco-codigo-cedente').value = b.codigo_cedente || '';
            document.getElementById('banco-operacao-cedente').value = b.operacao_cedente || '';
            document.getElementById('banco-emissao-via-banco').checked = !!b.emissao_via_banco;
            document.getElementById('banco-integracao-bancaria').checked = !!b.integracao_bancaria;
            document.getElementById('banco-instrucoes-bancarias').value = b.instrucoes_bancarias || '';
            document.getElementById('banco-remessa').value = b.caminho_remessa || '';
            document.getElementById('banco-retorno').value = b.caminho_retorno || '';
            document.getElementById('banco-ativo').checked = !!b.ativo;

            mostrarMensagemBanco('Conta corrente carregada para edição. Altere os dados e clique em Salvar.', 'sucesso');
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemBanco('Erro ao carregar conta corrente.', 'erro');
        });
}

function excluirBanco(id) {
    if (!confirm('Deseja realmente excluir esta conta corrente?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'delete');

    fetch('/SISIPTU/php/bancos_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagemBanco(data.mensagem, 'sucesso');
                carregarBancos();
            } else {
                mostrarMensagemBanco(data.mensagem || 'Erro ao excluir conta corrente.', 'erro');
            }
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemBanco('Erro ao excluir conta corrente.', 'erro');
        });
}

// ---------- Validação CPF/CNPJ (frontend) ----------

function validarCpfCnpjJs(valor) {
    if (!valor) return false;
    const doc = valor.replace(/[^0-9]/g, '');
    if (doc.length === 11) {
        return validarCPFJs(doc);
    }
    if (doc.length === 14) {
        return validarCNPJJs(doc);
    }
    return false;
}

function validarCPFJs(cpf) {
    if (!cpf || cpf.length !== 11) return false;
    if (/^(\d)\1+$/.test(cpf)) return false;

    let soma = 0;
    for (let i = 0; i < 9; i++) {
        soma += parseInt(cpf.charAt(i), 10) * (10 - i);
    }
    let resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.charAt(9), 10)) return false;

    soma = 0;
    for (let i = 0; i < 10; i++) {
        soma += parseInt(cpf.charAt(i), 10) * (11 - i);
    }
    resto = (soma * 10) % 11;
    if (resto === 10 || resto === 11) resto = 0;
    if (resto !== parseInt(cpf.charAt(10), 10)) return false;

    return true;
}

function validarCNPJJs(cnpj) {
    if (!cnpj || cnpj.length !== 14) return false;
    if (/^(\d)\1+$/.test(cnpj)) return false;

    let tamanho = 12;
    let numeros = cnpj.substring(0, tamanho);
    let digitos = cnpj.substring(tamanho);
    let soma = 0;
    let pos = tamanho - 7;

    for (let i = tamanho; i >= 1; i--) {
        soma += parseInt(numeros.charAt(tamanho - i), 10) * pos--;
        if (pos < 2) pos = 9;
    }
    let resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
    if (resultado !== parseInt(digitos.charAt(0), 10)) return false;

    tamanho = 13;
    numeros = cnpj.substring(0, tamanho);
    soma = 0;
    pos = tamanho - 7;
    for (let i = tamanho; i >= 1; i--) {
        soma += parseInt(numeros.charAt(tamanho - i), 10) * pos--;
        if (pos < 2) pos = 9;
    }
    resultado = soma % 11 < 2 ? 0 : 11 - (soma % 11);
    if (resultado !== parseInt(digitos.charAt(1), 10)) return false;

    return true;
}

// Carregar conteúdo da página
function carregarPagina(page) {
    const contentBody = document.getElementById('content-body');
    const pageTitle = document.getElementById('page-title');
    
    let titulo = '';
    let conteudo = '';
    
    switch(page) {
        case 'home':
            titulo = 'Bem-vindo ao Sistema';
            conteudo = `
                <div class="welcome-section">
                    <div class="welcome-card">
                        <h3>Bem-vindo ao Sistema de Gestão de IPTU</h3>
                        <p>Selecione uma opção no menu lateral para começar.</p>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2d8659;">
                            <h4 style="color: #2d8659; margin-bottom: 10px;">📋 Informações do Usuário</h4>
                            <p><strong>Usuário:</strong> <span id="footer-usuario-copy">${document.getElementById('footer-usuario').textContent}</span></p>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon">📋</div>
                                <div class="stat-info">
                                    <h4>Cadastros</h4>
                                    <p class="stat-number">0</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">💰</div>
                                <div class="stat-info">
                                    <h4>Cobranças</h4>
                                    <p class="stat-number">0</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">📊</div>
                                <div class="stat-info">
                                    <h4>Relatórios</h4>
                                    <p class="stat-number">0</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            break;
            
        case 'cadastro':
            titulo = 'Cadastro';
            conteudo = `
                <div class="page-content">
                    <h3>📝 Módulo de Cadastro</h3>
                    <p>Selecione uma opção no menu lateral para acessar os cadastros.</p>
                    <div style="margin-top: 20px;">
                        <p><strong>Opções disponíveis:</strong></p>
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li>👥 Clientes</li>
                            <li>🏢 Empreendimentos</li>
                            <li>📦 Módulos</li>
                            <li>👤 Usuários</li>
                            <li>🏦 Bancos</li>
                        </ul>
                    </div>
                </div>
            `;
            break;
            
        case 'cadastro-clientes':
            titulo = 'Cadastro - Clientes';
            conteudo = `
                <div class="page-content" id="clientes-page">
                    <h3>👥 Cadastro de Clientes</h3>
                    <p>Cadastre, altere, exclua e pesquise clientes.</p>
                    
                    <div class="form-section">
                        <form id="form-cliente">
                            <input type="hidden" id="cli-id" name="id">
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-cpf-cnpj">CPF/CNPJ - Cliente</label>
                                    <input type="text" id="cli-cpf-cnpj" name="cpf_cnpj" maxlength="18" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-nome">Nome do Cliente</label>
                                    <input type="text" id="cli-nome" name="nome" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-tipo-cadastro">Tipo de Cadastro</label>
                                    <select id="cli-tipo-cadastro" name="tipo_cadastro">
                                        <option value="">Selecione...</option>
                                        <option value="Cliente">Cliente</option>
                                        <option value="Empresa">Empresa</option>
                                        <option value="Empreendimento">Empreendimento</option>
                                        <option value="Interviniente">Interviniente</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-cep">CEP</label>
                                    <input type="text" id="cli-cep" name="cep">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-endereco">Endereço</label>
                                    <input type="text" id="cli-endereco" name="endereco">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-bairro">Bairro</label>
                                    <input type="text" id="cli-bairro" name="bairro">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-cidade">Cidade</label>
                                    <input type="text" id="cli-cidade" name="cidade">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-uf">UF</label>
                                    <input type="text" id="cli-uf" name="uf" maxlength="2" style="text-transform: uppercase;">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-cod-municipio">Cod. Município</label>
                                    <input type="text" id="cli-cod-municipio" name="cod_municipio">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-data-nasc">Data de Nasc.</label>
                                    <input type="date" id="cli-data-nasc" name="data_nasc">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-profissao">Profissão</label>
                                    <input type="text" id="cli-profissao" name="profissao">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-identidade">Cart. Identidade</label>
                                    <input type="text" id="cli-identidade" name="identidade">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-estado-civil">Estado Civil</label>
                                    <input type="text" id="cli-estado-civil" name="estado_civil">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-nacionalidade">Nacionalidade</label>
                                    <input type="text" id="cli-nacionalidade" name="nacionalidade">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-regime-casamento">Regime de Casamento</label>
                                    <input type="text" id="cli-regime-casamento" name="regime_casamento">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-email">E-mail</label>
                                    <input type="email" id="cli-email" name="email">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-site">Site</label>
                                    <input type="text" id="cli-site" name="site">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-tel-comercial">Telefone Comercial</label>
                                    <input type="text" id="cli-tel-comercial" name="tel_comercial">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-tel-cel1">Telefone Celular 1</label>
                                    <input type="text" id="cli-tel-cel1" name="tel_celular1">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-tel-cel2">Telefone Celular 2</label>
                                    <input type="text" id="cli-tel-cel2" name="tel_celular2">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-tel-residencial">Telefone Residencial</label>
                                    <input type="text" id="cli-tel-residencial" name="tel_residencial">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline checkbox-group">
                                    <label>
                                        <input type="checkbox" id="cli-ativo" name="ativo" value="1" checked>
                                        Ativo
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary" id="btn-salvar-cli">Salvar</button>
                                <button type="button" class="btn-secondary" id="btn-novo-cli">Novo</button>
                            </div>
                            
                            <div id="cli-mensagem" class="mensagem" style="margin-top: 10px; display: none;"></div>
                        </form>
                    </div>
                    
                    <div class="table-section">
                        <h4>Pesquisa de Clientes</h4>
                        <div class="form-row" style="margin-bottom: 10px;">
                            <div class="form-group-inline">
                                <label for="cli-busca">Pesquisar por Nome ou CPF/CNPJ</label>
                                <input type="text" id="cli-busca" placeholder="Digite parte do nome ou CPF/CNPJ">
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn-primary" id="btn-buscar-cli">Pesquisar</button>
                                <button type="button" class="btn-secondary" id="btn-limpar-busca-cli">Limpar</button>
                            </div>
                        </div>
                        
                        <div class="table-wrapper">
                            <table class="table" id="tabela-clientes">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>CPF/CNPJ</th>
                                        <th>Nome</th>
                                        <th>Cidade</th>
                                        <th>UF</th>
                                        <th>E-mail</th>
                                        <th>Celular</th>
                                        <th>Ativo</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Linhas serão carregadas via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            setTimeout(inicializarCadastroClientes, 0);
            break;
            
        case 'cadastro-empreendimentos':
            titulo = 'Cadastro - Empreendimentos';
            conteudo = `
                <div class="page-content" id="empreendimentos-page">
                    <h3>🏢 Cadastro de Empreendimentos</h3>
                    <p>Cadastre, altere, exclua e visualize empreendimentos.</p>
                    
                    <div class="form-section">
                        <form id="form-empreendimento">
                            <input type="hidden" id="emp-id" name="id">
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="emp-nome">Nome do Empreendimento</label>
                                    <input type="text" id="emp-nome" name="nome" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="emp-cidade">Cidade</label>
                                    <input type="text" id="emp-cidade" name="cidade">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="emp-endereco">Endereço</label>
                                    <input type="text" id="emp-endereco" name="endereco">
                                </div>
                                <div class="form-group-inline">
                                    <label for="emp-bairro">Bairro</label>
                                    <input type="text" id="emp-bairro" name="bairro">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="emp-uf">UF</label>
                                    <input type="text" id="emp-uf" name="uf" maxlength="2" style="text-transform: uppercase;">
                                </div>
                                <div class="form-group-inline">
                                    <label for="emp-cep">CEP</label>
                                    <input type="text" id="emp-cep" name="cep">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="emp-descricao">Descrição / Observações</label>
                                    <input type="text" id="emp-descricao" name="descricao">
                                </div>
                                <div class="form-group-inline checkbox-group">
                                    <label>
                                        <input type="checkbox" id="emp-ativo" name="ativo" value="1" checked>
                                        Ativo
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary" id="btn-salvar-emp">Salvar</button>
                                <button type="button" class="btn-secondary" id="btn-novo-emp">Novo</button>
                            </div>
                            
                            <div id="emp-mensagem" class="mensagem" style="margin-top: 10px; display: none;"></div>
                        </form>
                    </div>
                    
                    <div class="table-section">
                        <h4>Empreendimentos Cadastrados</h4>
                        <div class="table-wrapper">
                            <table class="table" id="tabela-empreendimentos">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>Cidade</th>
                                        <th>UF</th>
                                        <th>Ativo</th>
                                        <th>Data Criação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Linhas serão carregadas via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            // Inicializar CRUD de empreendimentos
            setTimeout(inicializarCadastroEmpreendimentos, 0);
            break;
            
        case 'cadastro-modulos':
            titulo = 'Cadastro - Módulos';
            conteudo = `
                <div class="page-content" id="modulos-page">
                    <h3>📦 Cadastro de Módulos</h3>
                    <p>Cadastre e associe módulos a empreendimentos.</p>
                    
                    <div class="form-section">
                        <form id="form-modulo">
                            <input type="hidden" id="mod-id" name="id">
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="mod-nome">Módulo</label>
                                    <input type="text" id="mod-nome" name="nome" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="mod-emp-id">Empreendimento</label>
                                    <select id="mod-emp-id" name="empreendimento_id" required>
                                        <option value="">Selecione...</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline checkbox-group">
                                    <label>
                                        <input type="checkbox" id="mod-ativo" name="ativo" value="1" checked>
                                        Ativo
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary" id="btn-salvar-mod">Salvar</button>
                                <button type="button" class="btn-secondary" id="btn-novo-mod">Novo</button>
                            </div>
                            
                            <div id="mod-mensagem" class="mensagem" style="margin-top: 10px; display: none;"></div>
                        </form>
                    </div>
                    
                    <div class="table-section">
                        <h4>Módulos Cadastrados</h4>
                        <div class="table-wrapper">
                            <table class="table" id="tabela-modulos">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Módulo</th>
                                        <th>Empreendimento</th>
                                        <th>Ativo</th>
                                        <th>Data Criação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Linhas serão carregadas via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            setTimeout(inicializarCadastroModulos, 0);
            break;
            
        case 'cadastro-usuarios':
            titulo = 'Cadastro - Usuários';
            conteudo = `
                <div class="page-content" id="usuarios-page">
                    <h3>👤 Cadastro de Usuários</h3>
                    <p>Cadastre, altere, exclua e visualize usuários do sistema.</p>
                    
                    <div class="form-section">
                        <form id="form-usuario">
                            <input type="hidden" id="usuario-id" name="id">
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="usuario-nome">Nome Completo</label>
                                    <input type="text" id="usuario-nome" name="nome" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="usuario-usuario">Usuário</label>
                                    <input type="text" id="usuario-usuario" name="usuario" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="usuario-senha">Senha (sem criptografia)</label>
                                    <input type="text" id="usuario-senha" name="senha">
                                </div>
                                <div class="form-group-inline">
                                    <label for="usuario-email">E-mail</label>
                                    <input type="email" id="usuario-email" name="email">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline checkbox-group">
                                    <label>
                                        <input type="checkbox" id="usuario-ativo" name="ativo" value="1" checked>
                                        Ativo
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary" id="btn-salvar-usuario">Salvar</button>
                                <button type="button" class="btn-secondary" id="btn-novo-usuario">Novo</button>
                            </div>
                            
                            <div id="usuarios-mensagem" class="mensagem" style="margin-top: 10px; display: none;"></div>
                        </form>
                    </div>
                    
                    <div class="table-section">
                        <h4>Usuários Cadastrados</h4>
                        <div class="table-wrapper">
                            <table class="table" id="tabela-usuarios">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome Completo</th>
                                        <th>Usuário</th>
                                        <th>E-mail</th>
                                        <th>Ativo</th>
                                        <th>Data Criação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Linhas serão carregadas via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            // Inicializar lógica do CRUD de usuários
            setTimeout(inicializarCadastroUsuarios, 0);
            break;
            break;
            
        case 'cadastro-bancos':
            titulo = 'Cadastro - Bancos';
            conteudo = `
                <div class="page-content" id="bancos-page">
                    <h3>🏦 Cadastro de Contas Correntes</h3>
                    
                    <div class="form-section">
                        <form id="form-banco">
                            <input type="hidden" id="banco-id" name="id">
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="banco-cedente">Cedente <span class="required">*</span></label>
                                    <input type="text" id="banco-cedente" name="cedente" placeholder="Nome do cedente" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-cnpj-cpf">CNPJ / CPF</label>
                                    <input type="text" id="banco-cnpj-cpf" name="cnpj_cpf" maxlength="18" placeholder="00.000.000/0000-00 ou 000.000.000-00">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-banco">Banco <span class="required">*</span></label>
                                    <input type="text" id="banco-banco" name="banco" placeholder="Nome do banco" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="banco-conta">Conta</label>
                                    <input type="text" id="banco-conta" name="conta" placeholder="Número da conta" maxlength="20">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-agencia">Agência</label>
                                    <input type="text" id="banco-agencia" name="agencia" placeholder="0000" maxlength="10">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-num-banco">Número do Banco</label>
                                    <input type="text" id="banco-num-banco" name="num_banco" placeholder="000" maxlength="10">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-carteira">Carteira</label>
                                    <input type="text" id="banco-carteira" name="carteira" placeholder="Código da carteira" maxlength="20">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="banco-operacao-cc">Operação C\\C</label>
                                    <input type="text" id="banco-operacao-cc" name="operacao_cc">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-apelido">Apelido</label>
                                    <input type="text" id="banco-apelido" name="apelido">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-convenio">Convênio</label>
                                    <input type="text" id="banco-convenio" name="convenio">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-multa-mes">Multa ao Mês (%)</label>
                                    <input type="text" id="banco-multa-mes" name="multa_mes" class="input-monetario" placeholder="0,00">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="banco-tarifa-bancaria">Tarifa Bancária</label>
                                    <input type="text" id="banco-tarifa-bancaria" name="tarifa_bancaria" class="input-monetario" placeholder="0,00">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-juros-mes">Juros ao Mês (%)</label>
                                    <input type="text" id="banco-juros-mes" name="juros_mes" class="input-monetario" placeholder="0,00">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-prazo-devolucao">Prazo Devolução (dias)</label>
                                    <input type="number" id="banco-prazo-devolucao" name="prazo_devolucao" placeholder="0" min="0" max="999" step="1">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-codigo-cedente">Código Cedente</label>
                                    <input type="text" id="banco-codigo-cedente" name="codigo_cedente">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="banco-operacao-cedente">Operação Cedente</label>
                                    <input type="text" id="banco-operacao-cedente" name="operacao_cedente">
                                </div>
                                <div class="form-group-inline checkbox-group">
                                    <label>
                                        <input type="checkbox" id="banco-emissao-via-banco" name="emissao_via_banco" value="1" checked>
                                        Emissão Via Banco
                                    </label>
                                </div>
                                <div class="form-group-inline checkbox-group">
                                    <label>
                                        <input type="checkbox" id="banco-integracao-bancaria" name="integracao_bancaria" value="1">
                                        Integração Bancária
                                    </label>
                                </div>
                                <div class="form-group-inline checkbox-group">
                                    <label>
                                        <input type="checkbox" id="banco-ativo" name="ativo" value="1" checked>
                                        Ativo
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-full">
                                    <label for="banco-instrucoes-bancarias">Instruções Bancárias</label>
                                    <textarea id="banco-instrucoes-bancarias" name="instrucoes_bancarias" rows="8" placeholder="Digite as instruções bancárias aqui..."></textarea>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="banco-remessa">Cam.Remessa (Diretório)</label>
                                    <div style="display: flex; gap: 5px;">
                                        <input type="text" id="banco-remessa" name="caminho_remessa" placeholder="Selecione o diretório ou digite o caminho..." style="flex: 1;">
                                        <button type="button" id="btn-procurar-remessa" class="btn-browse" title="Procurar diretório">📁</button>
                                        <input type="file" id="file-remessa" webkitdirectory directory multiple style="display: none;">
                                    </div>
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-retorno">Cam.Retorno (Diretório)</label>
                                    <div style="display: flex; gap: 5px;">
                                        <input type="text" id="banco-retorno" name="caminho_retorno" placeholder="Selecione o diretório ou digite o caminho..." style="flex: 1;">
                                        <button type="button" id="btn-procurar-retorno" class="btn-browse" title="Procurar diretório">📁</button>
                                        <input type="file" id="file-retorno" webkitdirectory directory multiple style="display: none;">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Salvar</button>
                                <button type="button" id="btn-novo-banco" class="btn-secondary">Novo</button>
                            </div>
                            
                            <div id="mensagem-banco" class="mensagem"></div>
                        </form>
                    </div>
                    
                    <div class="table-section">
                        <h4>Contas Correntes Cadastradas</h4>
                        <div class="table-wrapper">
                            <table class="table" id="tabela-bancos">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cedente</th>
                                        <th>CNPJ/CPF</th>
                                        <th>Banco</th>
                                        <th>Conta</th>
                                        <th>Agência</th>
                                        <th>Ativo</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-bancos-body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            setTimeout(inicializarCadastroBancos, 0);
            break;
            
        case 'iptu':
            titulo = 'IPTU';
            conteudo = `
                <div class="page-content">
                    <h3>🏛️ Módulo de IPTU</h3>
                    <p>Selecione uma opção no menu lateral para acessar as funcionalidades do IPTU.</p>
                    <div style="margin-top: 20px;">
                        <p><strong>Opções disponíveis:</strong></p>
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li>📥 Importar Cadastro</li>
                            <li>📋 Itens Importados</li>
                            <li>📄 Gerar Parcelas</li>
                            <li>🔍 Pesquisar Contratos</li>
                            <li>🔧 Manutenção IPTU</li>
                        </ul>
                    </div>
                </div>
            `;
            break;
            
        case 'iptu-importar-cadastro':
            titulo = 'IPTU - Importar Cadastro';
            conteudo = `
                <div class="page-content">
                    <h3>📥 Importar Cadastro</h3>
                    <p>Esta seção será utilizada para importar cadastros de contribuintes e imóveis para o sistema de IPTU.</p>
                    <p>Funcionalidades em desenvolvimento...</p>
                </div>
            `;
            break;
            
        case 'iptu-itens-importados':
            titulo = 'IPTU - Itens Importados';
            conteudo = `
                <div class="page-content">
                    <h3>📋 Itens Importados</h3>
                    <p>Esta seção será utilizada para visualizar e gerenciar os itens que foram importados para o sistema de IPTU.</p>
                    <p>Funcionalidades em desenvolvimento...</p>
                </div>
            `;
            break;
            
        case 'iptu-gerar-parcelas':
            titulo = 'IPTU - Gerar Parcelas';
            conteudo = `
                <div class="page-content">
                    <h3>📄 Gerar Parcelas</h3>
                    <p>Esta seção será utilizada para gerar as parcelas de IPTU para os contribuintes.</p>
                    <p>Funcionalidades em desenvolvimento...</p>
                </div>
            `;
            break;
            
        case 'iptu-pesquisar-contratos':
            titulo = 'IPTU - Pesquisar Contratos';
            conteudo = `
                <div class="page-content">
                    <h3>🔍 Pesquisar Contratos</h3>
                    <p>Esta seção será utilizada para pesquisar e visualizar contratos de IPTU cadastrados no sistema.</p>
                    <p>Funcionalidades em desenvolvimento...</p>
                </div>
            `;
            break;
            
        case 'iptu-manutencao-iptu':
            titulo = 'IPTU - Manutenção IPTU';
            conteudo = `
                <div class="page-content">
                    <h3>🔧 Manutenção IPTU</h3>
                    <p>Esta seção será utilizada para realizar manutenções e ajustes nos cadastros de IPTU.</p>
                    <p>Funcionalidades em desenvolvimento...</p>
                </div>
            `;
            break;
            
        case 'cobranca':
            titulo = 'Cobrança';
            conteudo = `
                <div class="page-content">
                    <h3>💰 Módulo de Cobrança</h3>
                    <p>Esta seção será utilizada para gerenciar as cobranças de IPTU, gerar boletos e acompanhar pagamentos.</p>
                    <p>Funcionalidades em desenvolvimento...</p>
                </div>
            `;
            break;
            
        case 'relatorios':
            titulo = 'Relatórios';
            conteudo = `
                <div class="page-content">
                    <h3>📊 Módulo de Relatórios</h3>
                    <p>Esta seção será utilizada para gerar relatórios diversos sobre cadastros, cobranças e pagamentos de IPTU.</p>
                    <p>Funcionalidades em desenvolvimento...</p>
                </div>
            `;
            break;
            
        default:
            titulo = 'Página não encontrada';
            conteudo = '<div class="page-content"><p>Página não encontrada.</p></div>';
    }
    
    pageTitle.textContent = titulo;
    contentBody.innerHTML = conteudo;
}


