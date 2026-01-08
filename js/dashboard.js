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

// Função auxiliar para formatar data no padrão DD/MM/AAAA
// Evita conversões de timezone que podem alterar o dia
function formatarData(data) {
    if (!data) return '';
    try {
        // Se a data já está no formato YYYY-MM-DD (string), converter diretamente sem usar Date
        // Isso evita problemas de timezone que alteram o dia
        if (typeof data === 'string' && data.match(/^\d{4}-\d{2}-\d{2}$/)) {
            // Formato YYYY-MM-DD - converter diretamente para DD/MM/YYYY
            const partes = data.split('-');
            if (partes.length === 3) {
                return `${partes[2]}/${partes[1]}/${partes[0]}`;
            }
        }
        
        // Se contém espaço ou T (datetime), pegar apenas a parte da data
        if (typeof data === 'string' && (data.includes(' ') || data.includes('T'))) {
            const dataParte = data.substring(0, 10);
            if (dataParte.match(/^\d{4}-\d{2}-\d{2}$/)) {
                const partes = dataParte.split('-');
                return `${partes[2]}/${partes[1]}/${partes[0]}`;
            }
        }
        
        // Tentar usar Date apenas se não for formato YYYY-MM-DD
        const dataObj = new Date(data);
        if (isNaN(dataObj.getTime())) return '';
        const dia = String(dataObj.getDate()).padStart(2, '0');
        const mes = String(dataObj.getMonth() + 1).padStart(2, '0');
        const ano = dataObj.getFullYear();
        return `${dia}/${mes}/${ano}`;
    } catch (e) {
        return '';
    }
}

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

        // Se for criação (não tem ID), verificar se o usuário já existe
        if (!id) {
            fetch('/SISIPTU/php/usuarios_api.php?action=verificar-usuario&usuario=' + encodeURIComponent(usuario))
                .then(r => r.json())
                .then(data => {
                    if (!data.sucesso) {
                        // Usuário já existe, mostrar mensagem e limpar tela
                        mostrarMensagemUsuarios(data.mensagem || 'Já existe um usuário com este nome de usuário.', 'erro');
                        form.reset();
                        document.getElementById('usuario-ativo').checked = true;
                        document.getElementById('usuario-id').value = '';
                        return;
                    }

                    // Usuário não existe, prosseguir com o salvamento
                    salvarUsuario(id, nome, usuario, senha, email, ativo);
                })
                .catch(err => {
                    console.error(err);
                    mostrarMensagemUsuarios('Erro ao verificar usuário.', 'erro');
                });
        } else {
            // Se for atualização, salvar diretamente
            salvarUsuario(id, nome, usuario, senha, email, ativo);
        }
    });

    function salvarUsuario(id, nome, usuario, senha, email, ativo) {
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
                    // Se for erro de usuário existente e for criação, limpar tela
                    if (!id && (data.mensagem.includes('já existe') || data.mensagem.includes('Já existe'))) {
                        mostrarMensagemUsuarios(data.mensagem, 'erro');
                        form.reset();
                        document.getElementById('usuario-ativo').checked = true;
                        document.getElementById('usuario-id').value = '';
                    } else {
                        mostrarMensagemUsuarios(data.mensagem, 'erro');
                    }
                }
            })
            .catch(err => {
                console.error(err);
                mostrarMensagemUsuarios('Erro ao salvar usuário.', 'erro');
            });
    }

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
        msg.innerHTML = '';
        msg.className = 'mensagem';
        return;
    }

    msg.className = 'mensagem ' + (tipo === 'sucesso' ? 'sucesso' : 'erro');
    msg.style.display = 'block';
    
    // Limpar conteúdo anterior e adicionar texto e botão de fechar
    msg.innerHTML = '';
    const span = document.createElement('span');
    span.textContent = texto;
    msg.appendChild(span);
    
    // Adicionar botão OK para fechar a mensagem
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'OK';
    btn.style.marginLeft = '10px';
    btn.className = 'btn-small btn-message-ok';
    btn.addEventListener('click', function () {
        msg.style.display = 'none';
        msg.innerHTML = '';
    });
    msg.appendChild(btn);
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
                const dataCriacao = formatarData(u.data_criacao);
                return `
                    <tr>
                        <td>${u.id}</td>
                        <td>${u.nome || ''}</td>
                        <td>${u.usuario || ''}</td>
                        <td>${u.email || ''}</td>
                        <td>${u.ativo ? 'Sim' : 'Não'}</td>
                        <td>${dataCriacao}</td>
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

    // Carregar bancos no dropdown
    carregarBancosSelectEmpreendimentos();
    
    // Carregar empresas no dropdown
    carregarEmpresasSelectEmpreendimentos();
    
    carregarEmpreendimentos();

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('emp-id').value;
        const empresa_id = document.getElementById('emp-empresa').value || '';
        const nome = document.getElementById('emp-nome').value.trim();
        const banco_id = document.getElementById('emp-banco').value || '';
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
        formData.append('empresa_id', empresa_id);
        formData.append('nome', nome);
        formData.append('banco_id', banco_id);
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
            document.getElementById('emp-empresa').value = '';
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
                const dataCriacao = formatarData(e.data_criacao);
                return `
                    <tr>
                        <td>${e.id}</td>
                        <td>${e.nome || ''}</td>
                        <td>${e.cidade || ''}</td>
                        <td>${e.uf || ''}</td>
                        <td>${e.ativo ? 'Sim' : 'Não'}</td>
                        <td>${dataCriacao}</td>
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
            document.getElementById('emp-empresa').value = e.empresa_id || '';
            document.getElementById('emp-nome').value = e.nome || '';
            document.getElementById('emp-banco').value = e.banco_id || '';
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

function carregarEmpresasSelectEmpreendimentos() {
    const select = document.getElementById('emp-empresa');
    if (!select) {
        console.warn('Elemento emp-empresa não encontrado. Tentando novamente...');
        // Tentar novamente após um pequeno delay
        setTimeout(carregarEmpresasSelectEmpreendimentos, 100);
        return;
    }

    // Limpar e adicionar opção padrão
    select.innerHTML = '<option value="">Selecione a empresa...</option>';

    // Buscar empresas usando a API de empresas
    fetch('/SISIPTU/php/empresas_api.php?action=list')
        .then(r => {
            if (!r.ok) {
                throw new Error(`HTTP error! status: ${r.status}`);
            }
            const contentType = r.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return r.text().then(text => {
                    console.error('Resposta não é JSON:', text);
                    throw new Error('Resposta não é JSON');
                });
            }
            return r.json();
        })
        .then(data => {
            if (!data || !data.sucesso) {
                console.warn('Erro ao carregar empresas:', data?.mensagem || 'Resposta inválida');
                return;
            }

            // Filtrar apenas empresas ativas
            const empresas = (data.empresas || []).filter(e => e.ativo !== false);
            
            if (empresas.length === 0) {
                console.info('Nenhuma empresa cadastrada.');
                return;
            }
            
            empresas.forEach(empresa => {
                const option = document.createElement('option');
                option.value = empresa.id;
                option.textContent = empresa.nome || `Empresa ${empresa.id}`;
                select.appendChild(option);
            });
        })
        .catch(err => {
            console.error('Erro ao carregar empresas:', err);
        });
}

function carregarBancosSelectEmpreendimentos() {
    const select = document.getElementById('emp-banco');
    if (!select) {
        console.warn('Elemento emp-banco não encontrado. Tentando novamente...');
        // Tentar novamente após um pequeno delay
        setTimeout(carregarBancosSelectEmpreendimentos, 100);
        return;
    }

    // Limpar e adicionar opção padrão
    select.innerHTML = '<option value="" style="color: #000;">Banco</option>';

    fetch('/SISIPTU/php/bancos_api.php?action=list')
        .then(r => {
            if (!r.ok) {
                throw new Error(`HTTP error! status: ${r.status}`);
            }
            const contentType = r.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return r.text().then(text => {
                    console.error('Resposta não é JSON:', text);
                    throw new Error('Resposta não é JSON');
                });
            }
            return r.json();
        })
        .then(data => {
            if (!data || !data.sucesso) {
                console.warn('Erro ao carregar bancos:', data?.mensagem || 'Resposta inválida');
                return;
            }

            const bancos = data.bancos || [];
            if (bancos.length === 0) {
                console.info('Nenhum banco cadastrado.');
                return;
            }

            bancos.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                // Mostrar nome do banco e conta
                const texto = b.banco ? `${b.banco} - ${b.conta || 'Sem conta'}` : `Banco ID ${b.id} - ${b.conta || 'Sem conta'}`;
                opt.textContent = texto;
                opt.style.color = '#000';
                select.appendChild(opt);
            });
        })
        .catch(err => {
            console.error('Erro ao carregar bancos:', err);
            // Não mostrar erro ao usuário, apenas logar no console
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
                const dataCriacao = formatarData(m.data_criacao);
                return `
                    <tr>
                        <td>${m.id}</td>
                        <td>${m.nome || ''}</td>
                        <td>${m.empreendimento_nome || ''}</td>
                        <td>${m.ativo ? 'Sim' : 'Não'}</td>
                        <td>${dataCriacao}</td>
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
    let timeoutVerificacaoCliente = null;
    let validandoCpfCnpj = false; // Flag para evitar loops durante validação
    window.clienteEditandoId = null;
    
    // Obter ID do cliente sendo editado
    const hiddenId = document.getElementById('cli-id');
    if (hiddenId) {
        window.clienteEditandoId = hiddenId.value || null;
    }
    
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
            
            // Limpar timeout anterior
            if (timeoutVerificacaoCliente) {
                clearTimeout(timeoutVerificacaoCliente);
            }
            
            // Verificar após 500ms de inatividade (apenas se não estiver validando)
            if (!validandoCpfCnpj) {
                timeoutVerificacaoCliente = setTimeout(() => {
                    if (!validandoCpfCnpj) {
                        verificarClienteExistente();
                    }
                }, 500);
            }
        });
        
        inputCpfCnpj.addEventListener('blur', function() {
            // Não executar se estiver validando (evitar loops)
            if (validandoCpfCnpj) return;
            
            if (timeoutVerificacaoCliente) {
                clearTimeout(timeoutVerificacaoCliente);
            }
            
            // Validar se tem menos de 11 dígitos
            const cpfCnpj = this.value.trim();
            if (cpfCnpj) {
                const docLimpo = cpfCnpj.replace(/[^0-9]/g, '');
                if (docLimpo.length > 0 && docLimpo.length < 11) {
                    mostrarMensagemCli('CPF/CNPJ deve ter no mínimo 11 dígitos. Digite um CPF (11 dígitos) ou CNPJ (14 dígitos).', 'erro');
                    // Usar setTimeout para evitar loop com o alert
                    setTimeout(() => {
                        if (!validandoCpfCnpj) {
                            this.focus();
                        }
                    }, 100);
                    return;
                }
            }
            
            // Aguardar um pouco antes de verificar para evitar conflito com alert
            setTimeout(() => {
                if (!validandoCpfCnpj) {
                    verificarClienteExistente();
                }
            }, 150);
        });
    }
    
    function verificarClienteExistente() {
        if (!inputCpfCnpj || validandoCpfCnpj) return;
        
        const cpfCnpj = inputCpfCnpj.value.trim();
        if (!cpfCnpj) return;
        
        const docLimpo = cpfCnpj.replace(/[^0-9]/g, '');
        
        // Validar CPF/CNPJ
        if (docLimpo.length === 11) {
            if (!validarCPFJs(docLimpo)) {
                validandoCpfCnpj = true; // Prevenir loops
                alert('CPF inválido! Por favor, verifique o número digitado.');
                // Aguardar o alert fechar completamente antes de focar
                setTimeout(() => {
                    validandoCpfCnpj = false;
                    if (inputCpfCnpj) {
                        inputCpfCnpj.focus();
                        inputCpfCnpj.select();
                    }
                }, 200);
                return;
            }
        } else if (docLimpo.length === 14) {
            if (!validarCNPJJs(docLimpo)) {
                validandoCpfCnpj = true; // Prevenir loops
                alert('CNPJ inválido! Por favor, verifique o número digitado.');
                // Aguardar o alert fechar completamente antes de focar
                setTimeout(() => {
                    validandoCpfCnpj = false;
                    if (inputCpfCnpj) {
                        inputCpfCnpj.focus();
                        inputCpfCnpj.select();
                    }
                }, 200);
                return;
            }
        } else if (docLimpo.length > 0 && docLimpo.length < 11) {
            validandoCpfCnpj = true; // Prevenir loops
            alert('CPF/CNPJ deve ter 11 (CPF) ou 14 (CNPJ) dígitos.');
            // Aguardar o alert fechar completamente antes de focar
            setTimeout(() => {
                validandoCpfCnpj = false;
                if (inputCpfCnpj) {
                    inputCpfCnpj.focus();
                    inputCpfCnpj.select();
                }
            }, 200);
            return;
        } else {
            // Ainda não completou a digitação ou está vazio
            return;
        }
        
        // Verificar se cliente já existe
        const url = `/SISIPTU/php/clientes_api.php?action=verificar-cliente&cpf_cnpj=${encodeURIComponent(cpfCnpj)}${window.clienteEditandoId ? '&cliente_id=' + window.clienteEditandoId : ''}`;
        
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data && data.sucesso && data.existe) {
                    const cliente = data.cliente;
                    let mensagem = '⚠️ Cliente já cadastrado!\n\n';
                    mensagem += `CPF/CNPJ: ${cliente.cpf_cnpj || 'N/A'}\n`;
                    mensagem += `Nome: ${cliente.nome || 'N/A'}\n`;
                    mensagem += `Tipo de Cadastro: ${cliente.tipo_cadastro || 'N/A'}\n`;
                    mensagem += `Cidade: ${cliente.cidade || 'N/A'}\n`;
                    mensagem += `UF: ${cliente.uf || 'N/A'}\n`;
                    mensagem += `E-mail: ${cliente.email || 'N/A'}\n`;
                    mensagem += `Telefone: ${cliente.tel_celular1 || 'N/A'}\n`;
                    mensagem += `Status: ${cliente.ativo ? 'Ativo' : 'Inativo'}\n`;
                    
                    alert(mensagem);
                    
                    // Limpar a tela após clicar em OK
                    if (form) {
                        form.reset();
                        if (hiddenId) hiddenId.value = '';
                        window.clienteEditandoId = null;
                        document.getElementById('cli-ativo').checked = true;
                        carregarClientes();
                    }
                }
            })
            .catch(err => {
                console.error('Erro ao verificar cliente:', err);
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
            window.clienteEditandoId = null;
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

// ---------- CRUD Empresas ----------

function inicializarCadastroEmpresas() {
    const form = document.getElementById('form-empresa');
    const btnNovo = document.getElementById('btn-novo-empresa');
    const btnBuscar = document.getElementById('btn-buscar-empresa');
    const btnLimparBusca = document.getElementById('btn-limpar-busca-empresa');
    const tabelaBody = document.querySelector('#tabela-empresas tbody');

    if (!form || !tabelaBody) return;

    // Máscara / formatação de CNPJ
    const inputCnpj = document.getElementById('empresa-cnpj');
    let timeoutVerificacaoCnpj = null;
    let validandoCnpj = false;
    window.empresaEditandoId = null;
    
    const hiddenId = document.getElementById('empresa-id');
    if (hiddenId) {
        window.empresaEditandoId = hiddenId.value || null;
    }
    
    if (inputCnpj) {
        inputCnpj.addEventListener('input', function () {
            let v = this.value.replace(/[^0-9]/g, '');
            // Limitar a 14 dígitos (CNPJ)
            if (v.length > 14) v = v.slice(0, 14);

            // Aplicar máscara CNPJ: 00.000.000/0000-00
            if (v.length > 12) {
                v = v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{0,2}).*/, '$1.$2.$3/$4-$5');
            } else if (v.length > 8) {
                v = v.replace(/(\d{2})(\d{3})(\d{3})(\d{0,4})/, '$1.$2.$3/$4');
            } else if (v.length > 5) {
                v = v.replace(/(\d{2})(\d{3})(\d{0,3})/, '$1.$2.$3');
            } else if (v.length > 2) {
                v = v.replace(/(\d{2})(\d{0,3})/, '$1.$2');
            }

            this.value = v;
            
            if (timeoutVerificacaoCnpj) {
                clearTimeout(timeoutVerificacaoCnpj);
            }
            
            if (!validandoCnpj && v.replace(/[^0-9]/g, '').length === 14) {
                timeoutVerificacaoCnpj = setTimeout(() => {
                    validarCnpjEmpresa(v);
                }, 500);
            }
        });
    }
    
    carregarEmpresas();

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const id = document.getElementById('empresa-id').value;
        const cnpj = document.getElementById('empresa-cnpj').value.replace(/[^0-9]/g, '');
        const razao_social = document.getElementById('empresa-razao-social').value.trim();
        const nome_fantasia = document.getElementById('empresa-nome-fantasia').value.trim();
        const cep = document.getElementById('empresa-cep').value.trim();
        const endereco = document.getElementById('empresa-endereco').value.trim();
        const bairro = document.getElementById('empresa-bairro').value.trim();
        const cidade = document.getElementById('empresa-cidade').value.trim();
        const uf = document.getElementById('empresa-uf').value.trim().toUpperCase();
        const cod_municipio = document.getElementById('empresa-cod-municipio').value.trim();
        const email = document.getElementById('empresa-email').value.trim();
        const site = document.getElementById('empresa-site').value.trim();
        const tel_comercial = document.getElementById('empresa-tel-comercial').value.trim();
        const tel_celular1 = document.getElementById('empresa-tel-cel1').value.trim();
        const tel_celular2 = document.getElementById('empresa-tel-cel2').value.trim();
        const ativo = document.getElementById('empresa-ativo').checked ? '1' : '0';

        if (!cnpj || cnpj.length !== 14) {
            mostrarMensagemEmpresas('Preencha o CNPJ corretamente (14 dígitos).', 'erro');
            return;
        }

        if (!razao_social) {
            mostrarMensagemEmpresas('Preencha a Razão Social.', 'erro');
            return;
        }

        const formData = new FormData();
        formData.append('cnpj', cnpj);
        formData.append('razao_social', razao_social);
        formData.append('nome_fantasia', nome_fantasia);
        formData.append('cep', cep);
        formData.append('endereco', endereco);
        formData.append('bairro', bairro);
        formData.append('cidade', cidade);
        formData.append('uf', uf);
        formData.append('cod_municipio', cod_municipio);
        formData.append('email', email);
        formData.append('site', site);
        formData.append('tel_comercial', tel_comercial);
        formData.append('tel_celular1', tel_celular1);
        formData.append('tel_celular2', tel_celular2);
        formData.append('ativo', ativo);

        let action = 'create';
        if (id) {
            action = 'update';
            formData.append('id', id);
            formData.append('empresa_id', id);
        }
        formData.append('action', action);

        fetch('/SISIPTU/php/empresas_api.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    mostrarMensagemEmpresas(data.mensagem, 'sucesso');
                    form.reset();
                    document.getElementById('empresa-ativo').checked = true;
                    document.getElementById('empresa-id').value = '';
                    window.empresaEditandoId = null;
                    carregarEmpresas();
                } else {
                    mostrarMensagemEmpresas(data.mensagem || 'Erro ao salvar empresa.', 'erro');
                }
            })
            .catch(err => {
                console.error(err);
                mostrarMensagemEmpresas('Erro ao salvar empresa.', 'erro');
            });
    });

    if (btnNovo) {
        btnNovo.addEventListener('click', function () {
            form.reset();
            document.getElementById('empresa-ativo').checked = true;
            document.getElementById('empresa-id').value = '';
            window.empresaEditandoId = null;
            mostrarMensagemEmpresas('', null);
        });
    }

    if (btnBuscar) {
        btnBuscar.addEventListener('click', function () {
            const q = document.getElementById('empresas-busca').value.trim();
            carregarEmpresas(q);
        });
    }

    if (btnLimparBusca) {
        btnLimparBusca.addEventListener('click', function () {
            document.getElementById('empresas-busca').value = '';
            carregarEmpresas();
        });
    }

    tabelaBody.addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;

        const id = btn.getAttribute('data-id');
        if (!id) return;

        if (btn.classList.contains('btn-edit')) {
            editarEmpresa(id);
        } else if (btn.classList.contains('btn-delete')) {
            excluirEmpresa(id);
        }
    });
}

function validarCnpjEmpresa(cnpj) {
    const cnpjLimpo = cnpj.replace(/[^0-9]/g, '');
    if (cnpjLimpo.length !== 14) return;

    validandoCnpj = true;
    const formData = new FormData();
    formData.append('cnpj', cnpjLimpo);
    if (window.empresaEditandoId) {
        formData.append('empresa_id', window.empresaEditandoId);
    }

    fetch('/SISIPTU/php/empresas_api.php?action=verificar-cnpj', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            validandoCnpj = false;
            if (!data.sucesso) {
                mostrarMensagemEmpresas(data.mensagem, 'erro');
            }
        })
        .catch(err => {
            validandoCnpj = false;
            console.error('Erro ao validar CNPJ:', err);
        });
}

function carregarEmpresas(q) {
    const tabelaBody = document.querySelector('#tabela-empresas tbody');
    if (!tabelaBody) return;

    tabelaBody.innerHTML = '<tr><td colspan="10">Carregando...</td></tr>';

    let url = '/SISIPTU/php/empresas_api.php?action=list';
    if (q) {
        url += '&q=' + encodeURIComponent(q);
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                tabelaBody.innerHTML = '<tr><td colspan="10">' + (data.mensagem || 'Erro ao carregar empresas.') + '</td></tr>';
                return;
            }

            const empresas = data.empresas || [];
            if (empresas.length === 0) {
                tabelaBody.innerHTML = '<tr><td colspan="10">Nenhuma empresa cadastrada.</td></tr>';
                return;
            }

            tabelaBody.innerHTML = empresas.map(e => {
                const cnpjFormatado = e.cpf_cnpj ? e.cpf_cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5') : '';
                const telefone = e.tel_comercial || e.tel_celular1 || 'N/A';
                return `
                    <tr>
                        <td>${e.id}</td>
                        <td>${cnpjFormatado}</td>
                        <td>${e.nome || ''}</td>
                        <td>${e.nome || ''}</td>
                        <td>${e.cidade || ''}</td>
                        <td>${e.uf || ''}</td>
                        <td>${e.email || ''}</td>
                        <td>${telefone}</td>
                        <td>${e.ativo ? 'Sim' : 'Não'}</td>
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
            tabelaBody.innerHTML = '<tr><td colspan="10">Erro ao carregar empresas.</td></tr>';
        });
}

function editarEmpresa(id) {
    fetch('/SISIPTU/php/empresas_api.php?action=get&id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso || !data.empresa) {
                mostrarMensagemEmpresas(data.mensagem || 'Erro ao carregar empresa.', 'erro');
                return;
            }

            const e = data.empresa;
            window.empresaEditandoId = e.id;
            document.getElementById('empresa-id').value = e.id;
            
            // Formatar CNPJ
            const cnpj = e.cpf_cnpj ? e.cpf_cnpj.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5') : '';
            document.getElementById('empresa-cnpj').value = cnpj;
            
            // Para empresas, o campo 'nome' pode ser nome_fantasia ou razao_social
            // Vamos usar razao_social como nome principal
            document.getElementById('empresa-razao-social').value = e.nome || '';
            document.getElementById('empresa-nome-fantasia').value = e.nome || '';
            document.getElementById('empresa-cep').value = e.cep || '';
            document.getElementById('empresa-endereco').value = e.endereco || '';
            document.getElementById('empresa-bairro').value = e.bairro || '';
            document.getElementById('empresa-cidade').value = e.cidade || '';
            document.getElementById('empresa-uf').value = e.uf || '';
            document.getElementById('empresa-cod-municipio').value = e.cod_municipio || '';
            document.getElementById('empresa-email').value = e.email || '';
            document.getElementById('empresa-site').value = e.site || '';
            document.getElementById('empresa-tel-comercial').value = e.tel_comercial || '';
            document.getElementById('empresa-tel-cel1').value = e.tel_celular1 || '';
            document.getElementById('empresa-tel-cel2').value = e.tel_celular2 || '';
            document.getElementById('empresa-ativo').checked = !!e.ativo;

            mostrarMensagemEmpresas('Empresa carregada para edição. Altere os dados e clique em Salvar.', 'sucesso');
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemEmpresas('Erro ao carregar empresa.', 'erro');
        });
}

function excluirEmpresa(id) {
    if (!confirm('Tem certeza que deseja excluir esta empresa?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id', id);
    formData.append('action', 'delete');

    fetch('/SISIPTU/php/empresas_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagemEmpresas(data.mensagem, 'sucesso');
                carregarEmpresas();
            } else {
                mostrarMensagemEmpresas(data.mensagem || 'Erro ao excluir empresa.', 'erro');
            }
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemEmpresas('Erro ao excluir empresa.', 'erro');
        });
}

function mostrarMensagemEmpresas(texto, tipo) {
    const msg = document.getElementById('empresas-mensagem');
    if (!msg) return;
    
    if (!texto || !tipo) {
        msg.style.display = 'none';
        msg.textContent = '';
        msg.className = 'mensagem';
        return;
    }
    
    msg.textContent = texto;
    msg.className = 'mensagem ' + tipo;
    msg.style.display = 'block';
    
    if (tipo === 'sucesso') {
        setTimeout(() => {
            msg.style.display = 'none';
        }, 3000);
    }
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
            window.clienteEditandoId = c.id;
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
        el.style.display = 'none';
        return;
    }
    const tipoClass = tipo === 'sucesso' ? 'sucesso' : (tipo === 'info' ? 'info' : 'erro');
    el.className = 'mensagem ' + tipoClass;
    el.style.display = 'block';
    el.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;">
            <span>${texto}</span>
            <button type="button" class="btn-message-ok" onclick="this.parentNode.parentNode.innerHTML=''; this.parentNode.parentNode.style.display='none';">
                OK
            </button>
        </div>
    `;
    
    // Auto-ocultar após 8 segundos se for info ou sucesso
    if (tipo === 'info' || tipo === 'sucesso') {
        setTimeout(() => {
            if (el) {
                el.innerHTML = '';
                el.className = 'mensagem';
                el.style.display = 'none';
            }
        }, 8000);
    }
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
            let timeoutFormat = null;
            
            campo.addEventListener('input', function () {
                // Remover tudo exceto números e vírgula
                let v = this.value.replace(/[^0-9,]/g, '');
                
                // Garantir apenas uma vírgula
                const partes = v.split(',');
                if (partes.length > 2) {
                    v = partes[0] + ',' + partes.slice(1).join('');
                }
                
                // Limitar a 2 casas decimais após a vírgula
                if (partes.length === 2 && partes[1].length > 2) {
                    v = partes[0] + ',' + partes[1].substring(0, 2);
                }
                
                this.value = v;
                
                // Limpar timeout anterior
                if (timeoutFormat) {
                    clearTimeout(timeoutFormat);
                }
                
                // Formatar após 500ms de inatividade
                timeoutFormat = setTimeout(() => {
                    formatarValorMonetario(this);
                }, 500);
            });
            
            campo.addEventListener('blur', function () {
                if (timeoutFormat) {
                    clearTimeout(timeoutFormat);
                }
                formatarValorMonetario(this);
            });
            
            function formatarValorMonetario(campo) {
                let v = campo.value.replace(/[^0-9,]/g, '').replace(',', '.');
                if (v === '' || v === '.') {
                    campo.value = '';
                    return;
                }
                const num = parseFloat(v);
                if (!isNaN(num) && num >= 0) {
                    campo.value = num.toFixed(2).replace('.', ',');
                }
            }
        }
    });

    // Função auxiliar para extrair caminho do diretório
    function extrairCaminhoDiretorio(files) {
        if (!files || files.length === 0) return '';
        
        const primeiroArquivo = files[0];
        let caminhoDiretorio = '';
        
        // Tentar obter caminho completo (funciona em Electron e alguns navegadores)
        if (primeiroArquivo.path) {
            // Caminho completo disponível (ex: C:\pasta\arquivo.txt)
            const caminhoCompleto = primeiroArquivo.path;
            const ultimaBarra = Math.max(
                caminhoCompleto.lastIndexOf('\\'),
                caminhoCompleto.lastIndexOf('/')
            );
            if (ultimaBarra > 0) {
                caminhoDiretorio = caminhoCompleto.substring(0, ultimaBarra);
            } else {
                caminhoDiretorio = caminhoCompleto;
            }
            return caminhoDiretorio;
        }
        
        // Tentar usar webkitRelativePath para extrair o diretório base selecionado
        if (primeiroArquivo.webkitRelativePath) {
            // Quando um diretório é selecionado, todos os arquivos têm webkitRelativePath
            // começando com o nome do diretório selecionado
            // Exemplo: se selecionar "C:\MeusDocumentos\Remessa", os arquivos terão "Remessa/arquivo.txt"
            
            // Pegar todos os caminhos relativos
            const caminhos = Array.from(files).map(f => f.webkitRelativePath || '').filter(c => c);
            
            if (caminhos.length === 0) return '';
            
            // Encontrar o prefixo comum até a primeira barra (diretório base selecionado)
            const primeiroCaminho = caminhos[0];
            const primeiraBarra = primeiroCaminho.indexOf('/');
            
            if (primeiraBarra > 0) {
                // O diretório base é a primeira parte antes da primeira barra
                const nomeDiretorio = primeiroCaminho.substring(0, primeiraBarra);
                
                // Verificar se todos os arquivos começam com o mesmo diretório
                const todosMesmoDiretorio = caminhos.every(c => 
                    c.startsWith(nomeDiretorio + '/') || c === nomeDiretorio
                );
                
                if (todosMesmoDiretorio) {
                    // Retornar apenas o nome do diretório selecionado
                    // Nota: Em navegadores web, não podemos obter o caminho completo por segurança
                    // O usuário precisará digitar o caminho completo manualmente ou usar um caminho relativo
                    return nomeDiretorio;
                } else {
                    // Se não, encontrar o prefixo comum mais longo
                    let prefixoComum = caminhos[0];
                    for (let i = 1; i < caminhos.length; i++) {
                        const caminho = caminhos[i];
                        let j = 0;
                        while (j < prefixoComum.length && j < caminho.length && 
                               prefixoComum[j] === caminho[j]) {
                            j++;
                        }
                        prefixoComum = prefixoComum.substring(0, j);
                    }
                    
                    // Extrair o diretório base do prefixo comum
                    const ultimaBarra = prefixoComum.lastIndexOf('/');
                    if (ultimaBarra > 0) {
                        return prefixoComum.substring(0, ultimaBarra);
                    } else if (ultimaBarra === -1) {
                        return prefixoComum;
                    }
                    return '';
                }
            } else {
                // Não há barra no caminho, então o arquivo está diretamente no diretório selecionado
                return primeiroCaminho;
            }
        }
        
        // Fallback: retornar string vazia
        return '';
    }

    // Configuração do campo Remessa
    const inputRemessa = document.getElementById('banco-remessa');
    const btnBuscarRemessa = document.getElementById('btn-buscar-remessa');
    const listaDiretoriosRemessa = document.getElementById('banco-lista-diretorios-remessa');
    
    if (inputRemessa && listaDiretoriosRemessa) {
        // Carregar lista de diretórios ao inicializar
        carregarListaDiretoriosBanco('remessa');
        
        // Event listener para mostrar/ocultar lista ao focar no input
        inputRemessa.addEventListener('focus', function() {
            if (listaDiretoriosRemessa.innerHTML.trim() !== '') {
                listaDiretoriosRemessa.style.display = 'block';
            }
        });
        
        inputRemessa.addEventListener('input', function() {
            carregarListaDiretoriosBanco('remessa');
        });
        
        inputRemessa.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                listaDiretoriosRemessa.style.display = 'none';
            }
        });
        
        // Fechar lista ao clicar fora
        document.addEventListener('click', function(e) {
            if (inputRemessa && listaDiretoriosRemessa && 
                !inputRemessa.contains(e.target) && 
                !listaDiretoriosRemessa.contains(e.target) &&
                btnBuscarRemessa && !btnBuscarRemessa.contains(e.target)) {
                listaDiretoriosRemessa.style.display = 'none';
            }
        });
    }
    
    // Função para selecionar diretório Remessa (caminho absoluto)
    window.selecionarDiretorioRemessa = function(diretorio) {
        if (inputRemessa) {
            // Garantir que é caminho absoluto (Windows)
            let caminho = diretorio.trim();
            // Normalizar separadores
            caminho = caminho.replace(/\//g, '\\');
            // Garantir que termina com \ se for diretório
            if (caminho.length > 0 && !caminho.endsWith('\\') && caminho.length > 3) {
                caminho += '\\';
            }
            inputRemessa.value = caminho;
        }
        if (listaDiretoriosRemessa) {
            listaDiretoriosRemessa.style.display = 'none';
        }
    };
    
    // Botão Buscar Remessa
    if (btnBuscarRemessa) {
        btnBuscarRemessa.addEventListener('click', function() {
            carregarListaDiretoriosBanco('remessa');
            if (inputRemessa && listaDiretoriosRemessa) {
                if (listaDiretoriosRemessa.innerHTML.trim() !== '') {
                    listaDiretoriosRemessa.style.display = 'block';
                }
            }
        });
    }
    
    // Configuração do campo Retorno
    const inputRetorno = document.getElementById('banco-retorno');
    const btnBuscarRetorno = document.getElementById('btn-buscar-retorno');
    const listaDiretoriosRetorno = document.getElementById('banco-lista-diretorios-retorno');
    
    if (inputRetorno && listaDiretoriosRetorno) {
        // Carregar lista de diretórios ao inicializar
        carregarListaDiretoriosBanco('retorno');
        
        // Event listener para mostrar/ocultar lista ao focar no input
        inputRetorno.addEventListener('focus', function() {
            if (listaDiretoriosRetorno.innerHTML.trim() !== '') {
                listaDiretoriosRetorno.style.display = 'block';
            }
        });
        
        inputRetorno.addEventListener('input', function() {
            carregarListaDiretoriosBanco('retorno');
        });
        
        inputRetorno.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                listaDiretoriosRetorno.style.display = 'none';
            }
        });
        
        // Fechar lista ao clicar fora
        document.addEventListener('click', function(e) {
            if (inputRetorno && listaDiretoriosRetorno && 
                !inputRetorno.contains(e.target) && 
                !listaDiretoriosRetorno.contains(e.target) &&
                btnBuscarRetorno && !btnBuscarRetorno.contains(e.target)) {
                listaDiretoriosRetorno.style.display = 'none';
            }
        });
    }
    
    // Função para selecionar diretório Retorno (caminho absoluto)
    window.selecionarDiretorioRetorno = function(diretorio) {
        if (inputRetorno) {
            // Garantir que é caminho absoluto (Windows)
            let caminho = diretorio.trim();
            // Normalizar separadores
            caminho = caminho.replace(/\//g, '\\');
            // Garantir que termina com \ se for diretório
            if (caminho.length > 0 && !caminho.endsWith('\\') && caminho.length > 3) {
                caminho += '\\';
            }
            inputRetorno.value = caminho;
        }
        if (listaDiretoriosRetorno) {
            listaDiretoriosRetorno.style.display = 'none';
        }
    };
    
    // Botão Buscar Retorno
    if (btnBuscarRetorno) {
        btnBuscarRetorno.addEventListener('click', function() {
            carregarListaDiretoriosBanco('retorno');
            if (inputRetorno && listaDiretoriosRetorno) {
                if (listaDiretoriosRetorno.innerHTML.trim() !== '') {
                    listaDiretoriosRetorno.style.display = 'block';
                }
            }
        });
    }
    
    // Função para carregar lista de diretórios (discos e pastas do Windows)
    function carregarListaDiretoriosBanco(tipo) {
        const input = tipo === 'remessa' ? inputRemessa : inputRetorno;
        const lista = tipo === 'remessa' ? listaDiretoriosRemessa : listaDiretoriosRetorno;
        
        if (!lista) return;
        
        // Obter caminho atual do input ou começar pelos discos
        const caminhoAtual = input ? input.value.trim() : '';
        
        // Construir URL da API
        let url = '/SISIPTU/php/listar_diretorios_api.php?action=listar-pastas';
        if (caminhoAtual) {
            url += '&caminho=' + encodeURIComponent(caminhoAtual);
        }
        
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.sucesso) {
                    lista.innerHTML = '<div style="padding: 10px; color: #d32f2f;">Erro: ' + (data.mensagem || 'Erro desconhecido') + '</div>';
                    lista.style.display = 'block';
                    return;
                }
                
                const pastas = data.pastas || [];
                if (pastas.length === 0) {
                    lista.innerHTML = '<div style="padding: 10px; color: #666;">Nenhuma pasta encontrada.</div>';
                    lista.style.display = 'block';
                    return;
                }
                
                const funcaoNavegar = tipo === 'remessa' ? 'navegarDiretorioRemessa' : 'navegarDiretorioRetorno';
                const funcaoSelecionar = tipo === 'remessa' ? 'selecionarDiretorioRemessa' : 'selecionarDiretorioRetorno';
                
                // Mostrar caminho atual
                let html = '';
                if (data.caminho_atual) {
                    html += `<div style="padding: 8px 12px; background: #f5f5f5; border-bottom: 2px solid #ddd; font-weight: bold; color: #2d8659;">
                        📍 ${data.caminho_atual}
                    </div>`;
                }
                
                // Listar pastas
                html += pastas.map(pasta => {
                    const caminhoEscapado = pasta.caminho.replace(/'/g, "\\'").replace(/\\/g, '\\\\');
                    const nome = pasta.nome || pasta.caminho;
                    const icone = pasta.tipo === 'disco' ? '💿' : pasta.tipo === 'voltar' ? '⬆️' : '📁';
                    const estiloVoltar = pasta.tipo === 'voltar' ? 'font-weight: bold; color: #1976d2;' : '';
                    
                    return `
                        <div class="ic-item-diretorio" style="padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 8px; transition: background-color 0.2s; ${estiloVoltar}" 
                             onmouseover="this.style.backgroundColor='#e3f2fd'; this.style.color='#1976d2';" 
                             onmouseout="this.style.backgroundColor=''; this.style.color='';"
                             onclick="${pasta.tipo === 'voltar' || pasta.tipo === 'disco' || pasta.tipo === 'pasta' ? funcaoNavegar + "('" + caminhoEscapado + "')" : funcaoSelecionar + "('" + caminhoEscapado + "')"}">
                            <span style="font-size: 16px;">${icone}</span>
                            <span style="flex: 1;">${nome}</span>
                            ${pasta.tipo === 'pasta' || pasta.tipo === 'disco' ? '<span style="color: #999; font-size: 12px;">▶</span>' : ''}
                        </div>
                    `;
                }).join('');
                
                // Adicionar botão "Selecionar este diretório" se houver caminho atual
                if (data.caminho_atual && data.caminho_atual.length > 3) {
                    const caminhoEscapado = data.caminho_atual.replace(/'/g, "\\'").replace(/\\/g, '\\\\');
                    html += `
                        <div style="padding: 12px; background: #e8f5e9; border-top: 2px solid #4caf50;">
                            <button type="button" onclick="${funcaoSelecionar}('${caminhoEscapado}')" 
                                    style="width: 100%; padding: 8px; background: #4caf50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                                ✓ Selecionar este diretório
                            </button>
                        </div>
                    `;
                }
                
                lista.innerHTML = html;
                
                // Mostrar lista
                lista.style.display = 'block';
            })
            .catch(err => {
                console.error('Erro ao carregar diretórios:', err);
                lista.innerHTML = '<div style="padding: 10px; color: #d32f2f;">Erro ao carregar diretórios. Verifique o console.</div>';
                lista.style.display = 'block';
            });
    }
    
    // Função para navegar em um diretório (sem selecionar)
    window.navegarDiretorioRemessa = function(caminho) {
        if (inputRemessa) {
            inputRemessa.value = caminho;
        }
        carregarListaDiretoriosBanco('remessa');
    };
    
    window.navegarDiretorioRetorno = function(caminho) {
        if (inputRetorno) {
            inputRetorno.value = caminho;
        }
        carregarListaDiretoriosBanco('retorno');
    };

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
        const prazo = formData.get('prazo_devolucao');
        
        // Remover pontos de milhar e substituir vírgula por ponto
        if (multa && multa.trim() !== '') {
            const multaLimpa = multa.replace(/\./g, '').replace(',', '.');
            formData.set('multa_mes', multaLimpa);
        } else {
            formData.set('multa_mes', '');
        }
        
        if (tarifa && tarifa.trim() !== '') {
            const tarifaLimpa = tarifa.replace(/\./g, '').replace(',', '.');
            formData.set('tarifa_bancaria', tarifaLimpa);
        } else {
            formData.set('tarifa_bancaria', '');
        }
        
        if (juros && juros.trim() !== '') {
            const jurosLimpo = juros.replace(/\./g, '').replace(',', '.');
            formData.set('juros_mes', jurosLimpo);
        } else {
            formData.set('juros_mes', '');
        }
        
        // Prazo já é número, apenas garantir que está correto
        if (prazo && prazo.trim() !== '') {
            formData.set('prazo_devolucao', prazo);
        } else {
            formData.set('prazo_devolucao', '');
        }
        
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

// ========== CRUD Gerar IPTU ==========
function inicializarGerarIptu() {
    const form = document.getElementById('form-gerar-iptu');
    const btnNovo = document.getElementById('btn-novo-gerar-iptu');
    const tabelaBody = document.getElementById('tabela-gerar-iptu-body');

    if (!form || !tabelaBody) return;

    // Carregar selects de Empreendimento e Módulo
    carregarEmpreendimentosSelectGerarIptu();
    carregarModulosSelectGerarIptu();
    
    // Inicializar tabela vazia (só carrega quando filtros estiverem preenchidos)
    tabelaBody.innerHTML = '<tr><td colspan="10">Informe Empreendimento, Módulo e Contrato para visualizar os registros.</td></tr>';
    
    // Preencher campo 1ª Vencimento com a data de hoje
    const primeiraVencimentoInput = document.getElementById('gi-primeira-vencimento');
    if (primeiraVencimentoInput) {
        const hoje = new Date();
        const ano = hoje.getFullYear();
        const mes = String(hoje.getMonth() + 1).padStart(2, '0');
        const dia = String(hoje.getDate()).padStart(2, '0');
        primeiraVencimentoInput.value = `${ano}-${mes}-${dia}`;
    }

    // Formatação monetária para valor total
    const valorTotal = document.getElementById('gi-valor-total');
    if (valorTotal) {
        let timeoutFormat = null;
        
        valorTotal.addEventListener('input', function() {
            // Remover tudo exceto números e vírgula
            let v = this.value.replace(/[^0-9,]/g, '');
            
            // Garantir apenas uma vírgula
            const partes = v.split(',');
            if (partes.length > 2) {
                v = partes[0] + ',' + partes.slice(1).join('');
            }
            
            // Limitar a 2 casas decimais após a vírgula
            if (partes.length === 2 && partes[1].length > 2) {
                v = partes[0] + ',' + partes[1].substring(0, 2);
            }
            
            this.value = v;
            
            // Limpar timeout anterior
            if (timeoutFormat) {
                clearTimeout(timeoutFormat);
            }
            
            // Formatar após 500ms de inatividade
            timeoutFormat = setTimeout(() => {
                formatarValorMonetario(this);
            }, 500);
        });

        valorTotal.addEventListener('blur', function() {
            if (timeoutFormat) {
                clearTimeout(timeoutFormat);
            }
            formatarValorMonetario(this);
        });
        
        function formatarValorMonetario(campo) {
            let v = campo.value.replace(/[^0-9,]/g, '').replace(',', '.');
            if (v === '' || v === '.') {
                campo.value = '';
                return;
            }
            const num = parseFloat(v);
            if (!isNaN(num) && num >= 0) {
                campo.value = num.toFixed(2).replace('.', ',');
            }
        }
    }

    // Event listener do formulário
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        salvarGerarIptu();
    });

    // Botão Novo
    if (btnNovo) {
        btnNovo.addEventListener('click', function() {
            form.reset();
            document.getElementById('gi-id').value = '';
            document.getElementById('gi-ativo').checked = true;
            mostrarMensagemGerarIptu('', '');
            
            // Resetar selects e aplicar cor preta
            const selectEmp = document.getElementById('gi-empreendimento');
            const selectMod = document.getElementById('gi-modulo');
            if (selectEmp) {
                selectEmp.value = '';
                selectEmp.style.color = '#000';
            }
            if (selectMod) {
                selectMod.value = '';
                selectMod.style.color = '#000';
                carregarModulosSelectGerarIptu();
            }
            
            // Resetar campos de contrato
            const contratoCodigo = document.getElementById('gi-contrato-codigo');
            const contratoDescricao = document.getElementById('gi-contrato-descricao');
            if (contratoCodigo) contratoCodigo.value = '';
            if (contratoDescricao) {
                contratoDescricao.value = '';
                contratoDescricao.readOnly = false;
                contratoDescricao.style.backgroundColor = '';
            }
            
            // Resetar valor da parcela
            const valorParcelaInput = document.getElementById('gi-parcelamento-tipo');
            if (valorParcelaInput) valorParcelaInput.value = '';
            
            // Preencher campo 1ª Vencimento com a data de hoje
            const primeiraVencimentoInput = document.getElementById('gi-primeira-vencimento');
            if (primeiraVencimentoInput) {
                const hoje = new Date();
                const ano = hoje.getFullYear();
                const mes = String(hoje.getMonth() + 1).padStart(2, '0');
                const dia = String(hoje.getDate()).padStart(2, '0');
                primeiraVencimentoInput.value = `${ano}-${mes}-${dia}`;
            }
            
            // Limpar grid - mostrar mensagem inicial
            const tabelaBody = document.getElementById('tabela-gerar-iptu-body');
            if (tabelaBody) {
                tabelaBody.innerHTML = '<tr><td colspan="10">Informe Empreendimento, Módulo e Contrato para visualizar os registros.</td></tr>';
            }
        });
    }

    // Botão Excluir Parcelas
    const btnExcluirParcelas = document.getElementById('btn-excluir-parcelas-gerar-iptu');
    if (btnExcluirParcelas) {
        btnExcluirParcelas.addEventListener('click', function() {
            // Obter valores dos campos necessários
            const empreendimentoId = document.getElementById('gi-empreendimento')?.value || '';
            const moduloId = document.getElementById('gi-modulo')?.value || '';
            const contratoCodigo = document.getElementById('gi-contrato-codigo')?.value.trim() || '';
            
            // Validar se os campos obrigatórios estão preenchidos
            if (!empreendimentoId || !moduloId || !contratoCodigo) {
                alert('⚠️ Atenção!\n\nPor favor, preencha Empreendimento, Módulo e Contrato antes de excluir as parcelas.');
                return;
            }
            
            // Confirmar exclusão
            const confirmacao = confirm('⚠️ ATENÇÃO!\n\nTem certeza que deseja excluir TODAS as parcelas deste contrato?\n\nEsta ação não pode ser desfeita!');
            
            if (!confirmacao) {
                return; // Usuário cancelou
            }
            
            // Preparar dados para envio
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('empreendimento_id', empreendimentoId);
            formData.append('modulo_id', moduloId);
            formData.append('contrato', contratoCodigo);
            
            // Desabilitar botão durante a requisição
            btnExcluirParcelas.disabled = true;
            btnExcluirParcelas.textContent = 'Excluindo...';
            
            fetch('/SISIPTU/php/gerar_iptu_api.php', {
                method: 'POST',
                body: formData
            })
                .then(r => {
                    if (!r.ok) {
                        throw new Error(`HTTP error! status: ${r.status}`);
                    }
                    const contentType = r.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return r.text().then(text => {
                            throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
                        });
                    }
                    return r.json();
                })
                .then(data => {
                    if (data.sucesso) {
                        mostrarMensagemGerarIptu(data.mensagem || 'Parcelas excluídas com sucesso!', 'sucesso');
                        // Recarregar o grid (que deve ficar vazio agora)
                        carregarGerarIptu();
                    } else {
                        mostrarMensagemGerarIptu(data.mensagem || 'Erro ao excluir parcelas.', 'erro');
                    }
                })
                .catch(err => {
                    console.error('Erro ao excluir parcelas:', err);
                    mostrarMensagemGerarIptu('Erro ao processar a exclusão de parcelas: ' + err.message, 'erro');
                })
                .finally(() => {
                    // Reabilitar botão
                    btnExcluirParcelas.disabled = false;
                    btnExcluirParcelas.textContent = 'Excluir Parcelas';
                });
        });
    }

    // Event listeners da tabela
    tabelaBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-delete')) {
            const id = e.target.getAttribute('data-id');
            if (id) {
                excluirGerarIptu(id);
            }
        }
    });

    // Quando selecionar empreendimento, filtrar módulos
    const selectEmp = document.getElementById('gi-empreendimento');
    if (selectEmp) {
        // Aplicar cor preta inicial
        selectEmp.style.color = '#000';
        
        selectEmp.addEventListener('change', function() {
            const empId = this.value;
            this.style.color = '#000';
            carregarModulosSelectGerarIptu(empId);
            // Limpar campos de contrato quando mudar empreendimento
            const contratoCodigo = document.getElementById('gi-contrato-codigo');
            const contratoDescricao = document.getElementById('gi-contrato-descricao');
            if (contratoCodigo) contratoCodigo.value = '';
            if (contratoDescricao) {
                contratoDescricao.value = '';
                contratoDescricao.readOnly = false;
                contratoDescricao.style.backgroundColor = '';
            }
            // Recarregar tabela
            carregarGerarIptu();
        });
    }
    
    // Aplicar cor preta no módulo
    const selectMod = document.getElementById('gi-modulo');
    if (selectMod) {
        selectMod.style.color = '#000';
        
        selectMod.addEventListener('change', function() {
            this.style.color = '#000';
            // Limpar campos de contrato quando mudar módulo
            const contratoCodigo = document.getElementById('gi-contrato-codigo');
            const contratoDescricao = document.getElementById('gi-contrato-descricao');
            if (contratoCodigo) contratoCodigo.value = '';
            if (contratoDescricao) {
                contratoDescricao.value = '';
                contratoDescricao.readOnly = false;
                contratoDescricao.style.backgroundColor = '';
            }
            // Recarregar tabela
            carregarGerarIptu();
        });
    }
    
    // Validação de contrato e busca de cliente
    const contratoCodigoInput = document.getElementById('gi-contrato-codigo');
    const contratoDescricaoInput = document.getElementById('gi-contrato-descricao');
    let timeoutValidacaoContrato = null;
    let validandoContratoGerarIptu = false; // Flag para evitar loops durante validação
    
    if (contratoCodigoInput) {
        contratoCodigoInput.addEventListener('blur', function() {
            // Não executar se estiver validando (evitar loops)
            if (validandoContratoGerarIptu) return;
            
            if (timeoutValidacaoContrato) {
                clearTimeout(timeoutValidacaoContrato);
            }
            
            // Aguardar um pouco antes de verificar para evitar conflito com alert
            setTimeout(() => {
                if (!validandoContratoGerarIptu) {
                    validarContratoGerarIptu();
                }
            }, 150);
        });
        contratoCodigoInput.addEventListener('input', function() {
            if (timeoutValidacaoContrato) {
                clearTimeout(timeoutValidacaoContrato);
            }
            
            // Verificar após 500ms de inatividade (apenas se não estiver validando)
            if (!validandoContratoGerarIptu) {
                timeoutValidacaoContrato = setTimeout(() => {
                    if (!validandoContratoGerarIptu) {
                        validarContratoGerarIptu();
                    }
                }, 500);
            }
        });
    }
    
    function validarContratoGerarIptu() {
        if (validandoContratoGerarIptu) return; // Prevenir execuções simultâneas
        
        const empreendimentoId = selectEmp ? selectEmp.value : '';
        const moduloId = selectMod ? selectMod.value : '';
        const contratoCodigo = contratoCodigoInput ? contratoCodigoInput.value.trim() : '';
        const anoReferencia = document.getElementById('gi-ano-referencia') ? document.getElementById('gi-ano-referencia').value.trim() : '';
        
        // Verificar se todos os campos obrigatórios estão preenchidos
        if (!empreendimentoId || !moduloId || !contratoCodigo || !anoReferencia) {
            if (contratoDescricaoInput) {
                contratoDescricaoInput.value = '';
                contratoDescricaoInput.readOnly = false;
                contratoDescricaoInput.style.backgroundColor = '';
            }
            return;
        }
        
        validandoContratoGerarIptu = true; // Marcar como validando
        
        // Primeiro verificar se o contrato existe
        const urlContrato = `/SISIPTU/php/contratos_api.php?action=verificar-contrato&empreendimento_id=${encodeURIComponent(empreendimentoId)}&modulo_id=${encodeURIComponent(moduloId)}&contrato=${encodeURIComponent(contratoCodigo)}`;
        
        fetch(urlContrato)
            .then(r => {
                if (!r.ok) {
                    throw new Error(`HTTP error! status: ${r.status}`);
                }
                const contentType = r.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    return r.text().then(text => {
                        console.error('Resposta não é JSON:', text);
                        throw new Error('Resposta do servidor não é JSON válido');
                    });
                }
                return r.json();
            })
            .then(data => {
                if (data && data.sucesso && data.existe && data.contrato) {
                    // Contrato existe, mostrar nome do cliente
                    const contrato = data.contrato;
                    if (contratoDescricaoInput) {
                        contratoDescricaoInput.value = contrato.cliente_nome || '';
                        contratoDescricaoInput.readOnly = true;
                        contratoDescricaoInput.style.backgroundColor = '#f5f5f5';
                    }
                    
                    // Agora verificar se já existe cobrança com os 4 campos
                    const urlCobranca = `/SISIPTU/php/gerar_iptu_api.php?action=verificar-parcelas&empreendimento_id=${encodeURIComponent(empreendimentoId)}&modulo_id=${encodeURIComponent(moduloId)}&contrato=${encodeURIComponent(contratoCodigo)}&ano_referencia=${encodeURIComponent(anoReferencia)}`;
                    
                    return fetch(urlCobranca)
                        .then(r => {
                            if (!r.ok) {
                                throw new Error(`HTTP error! status: ${r.status}`);
                            }
                            return r.json();
                        })
                        .then(dataCobranca => {
                            validandoContratoGerarIptu = false; // Liberar flag
                            
                            if (dataCobranca && dataCobranca.sucesso) {
                                if (dataCobranca.existe && dataCobranca.total > 0) {
                                    // Já existem parcelas, mostrar no grid
                                    if (dataCobranca.parcelas && dataCobranca.parcelas.length > 0) {
                                        mostrarParcelasNoGrid(dataCobranca.parcelas);
                                        mostrarMensagemGerarIptu(`Já existem ${dataCobranca.total} parcela(s) gerada(s) para este contrato e ano de referência.`, 'aviso');
                                    } else {
                                        // Carregar do servidor
                                        carregarGerarIptu();
                                    }
                                } else {
                                    // Não existem parcelas, permitir continuar cadastrando
                                    carregarGerarIptu(); // Limpar grid ou mostrar mensagem
                                    mostrarMensagemGerarIptu('Contrato validado. Você pode continuar cadastrando as parcelas.', 'sucesso');
                                }
                            } else {
                                // Erro ao verificar cobrança, mas contrato é válido
                                carregarGerarIptu();
                            }
                        })
                        .catch(err => {
                            validandoContratoGerarIptu = false;
                            console.error('Erro ao verificar cobrança:', err);
                            // Mesmo com erro, se o contrato é válido, permitir continuar
                            carregarGerarIptu();
                        });
                } else {
                    // Contrato não existe
                    validandoContratoGerarIptu = false;
                    mostrarMensagemGerarIptu('⚠️ Erro! Contrato não encontrado na tabela de contratos. Por favor, verifique se o contrato está cadastrado.', 'erro');
                    // Focar no campo de contrato
                    setTimeout(() => {
                        if (contratoCodigoInput) {
                            contratoCodigoInput.focus();
                            contratoCodigoInput.select();
                        }
                        if (contratoDescricaoInput) {
                            contratoDescricaoInput.value = '';
                            contratoDescricaoInput.readOnly = false;
                            contratoDescricaoInput.style.backgroundColor = '';
                        }
                    }, 100);
                }
            })
            .catch(err => {
                validandoContratoGerarIptu = false; // Liberar flag em caso de erro
                console.error('Erro ao validar contrato:', err);
                mostrarMensagemGerarIptu('⚠️ Erro! Erro ao validar contrato. Tente novamente.', 'erro');
                // Limpar campo de descrição
                setTimeout(() => {
                    if (contratoDescricaoInput) {
                        contratoDescricaoInput.value = '';
                        contratoDescricaoInput.readOnly = false;
                        contratoDescricaoInput.style.backgroundColor = '';
                    }
                }, 100);
            });
    }
    
    function mostrarParcelasNoGrid(parcelas) {
        const tabelaBody = document.getElementById('tabela-gerar-iptu-body');
        if (!tabelaBody) return;
        
        if (parcelas.length === 0) {
            tabelaBody.innerHTML = '<tr><td colspan="10">Nenhum registro encontrado.</td></tr>';
            return;
        }
        
        tabelaBody.innerHTML = parcelas.map((r, index) => {
            const valorFormatado = r.valor_mensal ? 
                parseFloat(r.valor_mensal).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0,00';
            // Usar data_vencimento ou datavencimento (campo renomeado)
            const dataVencRaw = r.data_vencimento || r.datavencimento || r.dia_vencimento;
            const dataVenc = formatarData(dataVencRaw) || '-';
            const pagoStatus = r.pago === 'S' || r.pago === 's' ? 'Sim' : 'Não';
            
            return `
                <tr>
                    <td>${r.titulo || r.id || '-'}</td>
                    <td>${r.empreendimento_nome || '-'}</td>
                    <td>${r.modulo_nome || '-'}</td>
                    <td>${r.contrato || ''} ${r.cliente_nome ? '- ' + r.cliente_nome : ''}</td>
                    <td>${r.ano_referencia || '-'}</td>
                    <td>R$ ${valorFormatado}</td>
                    <td>${r.parcelamento || '-'}</td>
                    <td>${dataVenc}</td>
                    <td>-</td>
                    <td>
                        <div class="acoes">
                            <button type="button" class="btn-small btn-delete" data-id="${r.id}">Excluir</button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    // Limitar campo Ano Referência a 4 dígitos
    const anoReferenciaInput = document.getElementById('gi-ano-referencia');
    if (anoReferenciaInput) {
        anoReferenciaInput.addEventListener('input', function() {
            let valor = this.value.replace(/[^0-9]/g, ''); // Remover caracteres não numéricos
            if (valor.length > 4) {
                valor = valor.substring(0, 4); // Limitar a 4 dígitos
            }
            this.value = valor;
        });
        
        anoReferenciaInput.addEventListener('blur', function() {
            let valor = parseInt(this.value) || 0;
            if (valor < 2000) {
                this.value = '2000';
            } else if (valor > 2100) {
                this.value = '2100';
            } else if (this.value.length < 4 && this.value !== '') {
                // Garantir que tenha 4 dígitos se preenchido
                this.value = valor.toString().padStart(4, '0');
            }
            // Verificar contrato e parcelas quando o ano mudar (se todos os campos estiverem preenchidos)
            if (selectEmp && selectEmp.value && selectMod && selectMod.value && contratoCodigoInput && contratoCodigoInput.value.trim()) {
                validarContratoGerarIptu();
            } else {
                carregarGerarIptu();
            }
        });
    }
    
    // Cálculo do valor da parcela
    const valorTotalInput = document.getElementById('gi-valor-total');
    const parcelamentoQtdInput = document.getElementById('gi-parcelamento-qtd');
    const valorParcelaInput = document.getElementById('gi-parcelamento-tipo');
    
    function calcularValorParcela() {
        if (!valorTotalInput || !parcelamentoQtdInput || !valorParcelaInput) return;
        
        const valorTotalStr = valorTotalInput.value.replace(/[^0-9,]/g, '').replace(',', '.');
        const parcelamentoQtd = parseInt(parcelamentoQtdInput.value) || 0;
        
        if (valorTotalStr && parcelamentoQtd > 0) {
            const valorTotal = parseFloat(valorTotalStr);
            if (!isNaN(valorTotal) && valorTotal > 0) {
                const valorParcela = valorTotal / parcelamentoQtd;
                // Formatar como valor monetário
                valorParcelaInput.value = valorParcela.toFixed(2).replace('.', ',');
            } else {
                valorParcelaInput.value = '';
            }
        } else {
            valorParcelaInput.value = '';
        }
    }
    
    // Formatação monetária para o campo valor da parcela (apenas visual, já que é readonly)
    if (valorParcelaInput) {
        valorParcelaInput.addEventListener('focus', function() {
            // Não permitir edição
            this.blur();
        });
    }
    
    if (valorTotalInput) {
        valorTotalInput.addEventListener('input', function() {
            calcularValorParcela();
        });
        valorTotalInput.addEventListener('blur', function() {
            calcularValorParcela();
        });
    }
    
    if (parcelamentoQtdInput) {
        parcelamentoQtdInput.addEventListener('input', function() {
            calcularValorParcela();
        });
        parcelamentoQtdInput.addEventListener('blur', function() {
            calcularValorParcela();
        });
    }
}

// ---------- CRUD Contratos ----------

function inicializarCadastroContratos() {
    const form = document.getElementById('form-contrato');
    const btnNovo = document.getElementById('btn-novo-contrato');
    const tabelaBody = document.getElementById('tbody-contratos');

    if (!form || !tabelaBody) return;

    let contratoEditandoId = null;

    // Carregar empreendimentos e módulos
    carregarEmpreendimentosSelectContratos();
    
    // Quando selecionar empreendimento, filtrar módulos
    const selectEmp = document.getElementById('ct-empreendimento');
    if (selectEmp) {
        selectEmp.addEventListener('change', function() {
            const empId = this.value;
            carregarModulosSelectContratos(empId);
            verificarContratoExistente();
        });
    }

    // Máscaras e formatação (valor_mensal não está aqui pois é readonly e calculado automaticamente)
    const camposMonetarios = ['ct-vrm2', 'ct-valor-venal', 'ct-tx-coleta-lixo', 'ct-valor-anual', 'ct-desconto-vista'];
    camposMonetarios.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            let timeoutFormat = null;
            
            // Permitir digitação livre durante o input
            campo.addEventListener('input', function() {
                // Remover tudo exceto números e vírgula
                let v = this.value.replace(/[^0-9,]/g, '');
                
                // Garantir apenas uma vírgula
                const partes = v.split(',');
                if (partes.length > 2) {
                    v = partes[0] + ',' + partes.slice(1).join('');
                }
                
                // Limitar a 2 casas decimais após a vírgula
                if (partes.length === 2 && partes[1].length > 2) {
                    v = partes[0] + ',' + partes[1].substring(0, 2);
                }
                
                this.value = v;
                
                // Limpar timeout anterior
                if (timeoutFormat) {
                    clearTimeout(timeoutFormat);
                }
                
                // Formatar após 500ms de inatividade
                timeoutFormat = setTimeout(() => {
                    formatarValorMonetario(this);
                }, 500);
            });
            
            // Formatar ao sair do campo
            campo.addEventListener('blur', function() {
                if (timeoutFormat) {
                    clearTimeout(timeoutFormat);
                }
                formatarValorMonetario(this);
            });
            
            function formatarValorMonetario(campo) {
                let v = campo.value.replace(/[^0-9,]/g, '').replace(',', '.');
                if (v === '' || v === '.') {
                    campo.value = '';
                    return;
                }
                const num = parseFloat(v);
                if (!isNaN(num) && num >= 0) {
                    // Formatar com 2 casas decimais, permitindo valores grandes
                    campo.value = num.toFixed(2).replace('.', ',');
                }
            }
        }
    });

    // Formatação de metragem com 2 dígitos após vírgula (DECIMAL 15,2)
    const campoMetragem = document.getElementById('ct-metragem');
    if (campoMetragem) {
        let timeoutMetragem = null;
        
        campoMetragem.addEventListener('input', function() {
            // Limpar caracteres não numéricos, exceto vírgula
            let v = this.value.replace(/[^0-9,]/g, '');
            
            // Limitar a 15 dígitos antes da vírgula e 2 após
            const partes = v.split(',');
            if (partes[0].length > 15) {
                partes[0] = partes[0].substring(0, 15);
            }
            if (partes.length > 1 && partes[1].length > 2) {
                partes[1] = partes[1].substring(0, 2);
            }
            v = partes.join(',');
            
            // Limpar timeout anterior
            if (timeoutMetragem) {
                clearTimeout(timeoutMetragem);
            }
            
            // Atualizar o valor sem formatação imediata
            this.value = v;
            
            // Formatar após um pequeno delay
            timeoutMetragem = setTimeout(() => {
                formatarMetragem(this);
            }, 500);
        });
        
        campoMetragem.addEventListener('blur', function() {
            // Limpar qualquer timeout pendente e formatar imediatamente no blur
            if (timeoutMetragem) {
                clearTimeout(timeoutMetragem);
            }
            formatarMetragem(this);
        });
        
        function formatarMetragem(campo) {
            let v = campo.value.replace(/[^0-9,]/g, '');
            if (v === '' || v === ',') {
                campo.value = '';
                return;
            }
            const num = parseFloat(v.replace(',', '.'));
            if (!isNaN(num) && num >= 0) {
                // Formatar com 2 casas decimais, permitindo valores grandes (até 15 dígitos)
                campo.value = num.toFixed(2).replace('.', ',');
            }
        }
    }

    const camposNumericos = ['ct-aliquota'];
    camposNumericos.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            campo.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9,.-]/g, '');
            });
        }
    });
    
    // Cálculo automático de valor mensal (valor anual / parcelamento)
    const valorAnualInput = document.getElementById('ct-valor-anual');
    const parcelamentoInput = document.getElementById('ct-parcelamento');
    const valorMensalInput = document.getElementById('ct-valor-mensal');

    function calcularValorMensal() {
        if (!valorAnualInput || !parcelamentoInput || !valorMensalInput) return;

        const valorAnualStr = valorAnualInput.value.replace(/[^0-9,.-]/g, '').replace(',', '.');
        const parcelamento = parseInt(parcelamentoInput.value);

        if (valorAnualStr && parcelamento && parcelamento > 0) {
            const valorAnual = parseFloat(valorAnualStr);
            if (!isNaN(valorAnual) && valorAnual > 0) {
                const valorMensal = valorAnual / parcelamento;
                valorMensalInput.value = valorMensal.toFixed(2).replace('.', ',');
            } else {
                valorMensalInput.value = '';
            }
        } else {
            valorMensalInput.value = '';
        }
    }

    if (valorAnualInput) {
        valorAnualInput.addEventListener('input', calcularValorMensal);
        valorAnualInput.addEventListener('blur', calcularValorMensal);
    }

    if (parcelamentoInput) {
        parcelamentoInput.addEventListener('input', calcularValorMensal);
        parcelamentoInput.addEventListener('blur', calcularValorMensal);
    }

    // Máscara e validação de CPF/CNPJ
    const cpfCnpjInput = document.getElementById('ct-cpf-cnpj');
    const clienteNomeInput = document.getElementById('ct-cliente');
    const clienteIdInput = document.getElementById('ct-cliente-id');
    let timeoutBuscaCliente = null;
    let validandoCpfCnpjContrato = false; // Flag para evitar loops durante validação
    
    if (cpfCnpjInput) {
        // Aplicar máscara de CPF/CNPJ (mesma da tela de clientes)
        cpfCnpjInput.addEventListener('input', function () {
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
            
            // Limpar timeout anterior
            if (timeoutBuscaCliente) {
                clearTimeout(timeoutBuscaCliente);
            }
            
            // Verificar após 500ms de inatividade (apenas se não estiver validando)
            if (!validandoCpfCnpjContrato) {
                timeoutBuscaCliente = setTimeout(() => {
                    if (!validandoCpfCnpjContrato) {
                        validarCpfCnpjContrato();
                    }
                }, 500);
            }
        });
        
        // Validação no blur (mesma da tela de clientes)
        cpfCnpjInput.addEventListener('blur', function() {
            // Não executar se estiver validando (evitar loops)
            if (validandoCpfCnpjContrato) return;
            
            if (timeoutBuscaCliente) {
                clearTimeout(timeoutBuscaCliente);
            }
            
            // Validar se tem menos de 11 dígitos
            const cpfCnpj = this.value.trim();
            if (cpfCnpj) {
                const docLimpo = cpfCnpj.replace(/[^0-9]/g, '');
                if (docLimpo.length > 0 && docLimpo.length < 11) {
                    // Usar alert ao invés de mostrarMensagemContrato para manter consistência
                    alert('CPF/CNPJ deve ter no mínimo 11 dígitos. Digite um CPF (11 dígitos) ou CNPJ (14 dígitos).');
                    // Usar setTimeout para evitar loop com o alert
                    setTimeout(() => {
                        if (!validandoCpfCnpjContrato) {
                            this.focus();
                        }
                    }, 100);
                    return;
                }
            }
            
            // Aguardar um pouco antes de verificar para evitar conflito com alert
            setTimeout(() => {
                if (!validandoCpfCnpjContrato) {
                    validarCpfCnpjContrato();
                }
            }, 150);
        });
    }
    
    function validarCpfCnpjContrato() {
        if (!cpfCnpjInput || validandoCpfCnpjContrato) return;
        
        const cpfCnpj = cpfCnpjInput.value.trim();
        if (!cpfCnpj) {
            if (clienteNomeInput) clienteNomeInput.value = '';
            if (clienteIdInput) clienteIdInput.value = '';
            return;
        }
        
        const docLimpo = cpfCnpj.replace(/[^0-9]/g, '');
        
        // Validar CPF/CNPJ (mesma validação da tela de clientes)
        if (docLimpo.length === 11) {
            if (!validarCPFJs(docLimpo)) {
                validandoCpfCnpjContrato = true; // Prevenir loops
                alert('CPF inválido! Por favor, verifique o número digitado.');
                // Aguardar o alert fechar completamente antes de focar
                setTimeout(() => {
                    validandoCpfCnpjContrato = false;
                    if (cpfCnpjInput) {
                        cpfCnpjInput.focus();
                        cpfCnpjInput.select();
                    }
                    if (clienteNomeInput) clienteNomeInput.value = '';
                    if (clienteIdInput) clienteIdInput.value = '';
                }, 200);
                return;
            }
        } else if (docLimpo.length === 14) {
            if (!validarCNPJJs(docLimpo)) {
                validandoCpfCnpjContrato = true; // Prevenir loops
                alert('CNPJ inválido! Por favor, verifique o número digitado.');
                // Aguardar o alert fechar completamente antes de focar
                setTimeout(() => {
                    validandoCpfCnpjContrato = false;
                    if (cpfCnpjInput) {
                        cpfCnpjInput.focus();
                        cpfCnpjInput.select();
                    }
                    if (clienteNomeInput) clienteNomeInput.value = '';
                    if (clienteIdInput) clienteIdInput.value = '';
                }, 200);
                return;
            }
        } else if (docLimpo.length > 0 && docLimpo.length < 11) {
            validandoCpfCnpjContrato = true; // Prevenir loops
            alert('CPF/CNPJ deve ter 11 (CPF) ou 14 (CNPJ) dígitos.');
            // Aguardar o alert fechar completamente antes de focar
            setTimeout(() => {
                validandoCpfCnpjContrato = false;
                if (cpfCnpjInput) {
                    cpfCnpjInput.focus();
                    cpfCnpjInput.select();
                }
                if (clienteNomeInput) clienteNomeInput.value = '';
                if (clienteIdInput) clienteIdInput.value = '';
            }, 200);
            return;
        } else {
            // Ainda não completou a digitação ou está vazio
            if (clienteNomeInput) clienteNomeInput.value = '';
            if (clienteIdInput) clienteIdInput.value = '';
            return;
        }
        
        // Se chegou aqui, o CPF/CNPJ é válido, buscar cliente
        buscarClientePorCpfCnpj();
    }
    
    function buscarClientePorCpfCnpj() {
        if (!cpfCnpjInput || !cpfCnpjInput.value.trim()) {
            if (clienteNomeInput) clienteNomeInput.value = '';
            if (clienteIdInput) clienteIdInput.value = '';
            return;
        }
        
        const cpfCnpj = cpfCnpjInput.value.replace(/[^0-9]/g, '');
        if (cpfCnpj.length !== 11 && cpfCnpj.length !== 14) {
            if (clienteNomeInput) clienteNomeInput.value = '';
            if (clienteIdInput) clienteIdInput.value = '';
            return;
        }
        
        fetch(`/SISIPTU/php/clientes_api.php?action=get-by-cpf-cnpj&cpf_cnpj=${encodeURIComponent(cpfCnpj)}`)
            .then(r => r.json())
            .then(data => {
                if (data && data.sucesso && data.cliente) {
                    if (clienteNomeInput) clienteNomeInput.value = data.cliente.nome || '';
                    if (clienteIdInput) clienteIdInput.value = data.cliente.id || '';
                } else {
                    if (clienteNomeInput) clienteNomeInput.value = '';
                    if (clienteIdInput) clienteIdInput.value = '';
                }
            })
            .catch(err => {
                console.error('Erro ao buscar cliente:', err);
                if (clienteNomeInput) clienteNomeInput.value = '';
                if (clienteIdInput) clienteIdInput.value = '';
            });
    }

    // Validação de contrato existente
    const empreendimentoSelect = document.getElementById('ct-empreendimento');
    const moduloSelect = document.getElementById('ct-modulo');
    const contratoInput = document.getElementById('ct-contrato');
    
    let timeoutVerificacao = null;
    
    function verificarContratoExistente() {
        const empreendimentoId = empreendimentoSelect ? empreendimentoSelect.value : '';
        const moduloId = moduloSelect ? moduloSelect.value : '';
        const contratoCodigo = contratoInput ? contratoInput.value.trim() : '';
        
        if (!empreendimentoId || !moduloId || !contratoCodigo) {
            return;
        }
        
        // Limpar timeout anterior
        if (timeoutVerificacao) {
            clearTimeout(timeoutVerificacao);
        }
        
        // Aguardar 500ms após parar de digitar
        timeoutVerificacao = setTimeout(() => {
            const url = `/SISIPTU/php/contratos_api.php?action=verificar-contrato&empreendimento_id=${encodeURIComponent(empreendimentoId)}&modulo_id=${encodeURIComponent(moduloId)}&contrato=${encodeURIComponent(contratoCodigo)}${contratoEditandoId ? '&contrato_id=' + contratoEditandoId : ''}`;
            
            fetch(url)
                .then(r => {
                    if (!r.ok) {
                        throw new Error(`HTTP error! status: ${r.status}`);
                    }
                    const contentType = r.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        return r.text().then(text => {
                            console.error('Resposta não é JSON:', text);
                            throw new Error('Resposta do servidor não é JSON válido');
                        });
                    }
                    return r.json();
                })
                .then(data => {
                    if (data && data.sucesso && data.existe) {
                        const contrato = data.contrato;
                        let mensagem = '⚠️ Contrato já cadastrado!\n\n';
                        mensagem += `Empreendimento: ${contrato.empreendimento_nome || 'N/A'}\n`;
                        mensagem += `Módulo: ${contrato.modulo_nome || 'N/A'}\n`;
                        mensagem += `Contrato: ${contrato.contrato || 'N/A'}\n`;
                        mensagem += `Inscrição: ${contrato.inscricao || 'N/A'}\n`;
                        mensagem += `Valor Venal: R$ ${contrato.valor_venal ? parseFloat(contrato.valor_venal).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : 'N/A'}\n`;
                        mensagem += `Parcelamento: ${contrato.parcelamento || 'N/A'}\n`;
                        
                        alert(mensagem);
                        
                        // Limpar a tela após clicar em OK
                        if (form) {
                            form.reset();
                            contratoEditandoId = null;
                            // Limpar estilos de erro
                            document.querySelectorAll('#form-contrato input, #form-contrato select, #form-contrato textarea').forEach(el => {
                                el.style.borderColor = '';
                                el.style.boxShadow = '';
                            });
                            // Recarregar a lista de contratos
                            carregarContratos();
                        }
                    }
                })
                .catch(err => {
                    console.error('Erro ao verificar contrato:', err);
                    // Não mostrar erro ao usuário, apenas logar no console
                });
        }, 500);
    }
    
    // Adicionar listeners para verificação
    if (moduloSelect) {
        moduloSelect.addEventListener('change', verificarContratoExistente);
    }
    if (contratoInput) {
        contratoInput.addEventListener('input', verificarContratoExistente);
        contratoInput.addEventListener('blur', verificarContratoExistente);
    }

    // Botão Novo
    if (btnNovo) {
        btnNovo.addEventListener('click', function() {
            form.reset();
            contratoEditandoId = null;
            carregarContratos();
            // Limpar estilos de erro
            document.querySelectorAll('#form-contrato input, #form-contrato select, #form-contrato textarea').forEach(el => {
                el.style.borderColor = '';
                el.style.boxShadow = '';
            });
        });
    }
    
    // Limpar estilos de erro quando o usuário começar a preencher
    document.querySelectorAll('#form-contrato input, #form-contrato select, #form-contrato textarea').forEach(el => {
        el.addEventListener('input', function() {
            this.style.borderColor = '';
            this.style.boxShadow = '';
        });
        el.addEventListener('change', function() {
            this.style.borderColor = '';
            this.style.boxShadow = '';
        });
    });
    
    // Botões de pesquisa
    const btnBuscar = document.getElementById('btn-buscar-contrato');
    const btnLimparBusca = document.getElementById('btn-limpar-busca-contrato');
    const campoBusca = document.getElementById('ct-busca');
    
    if (btnBuscar) {
        btnBuscar.addEventListener('click', function() {
            const q = campoBusca ? campoBusca.value.trim() : '';
            carregarContratos(q);
        });
    }
    
    if (btnLimparBusca) {
        btnLimparBusca.addEventListener('click', function() {
            if (campoBusca) {
                campoBusca.value = '';
            }
            carregarContratos();
        });
    }
    
    // Permitir pesquisar ao pressionar Enter
    if (campoBusca) {
        campoBusca.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const q = campoBusca.value.trim();
                carregarContratos(q);
            }
        });
    }

    // Form submit
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        salvarContrato();
    });


    function salvarContrato() {
        // Validar CPF/CNPJ se preenchido (mesma validação da tela de clientes)
        const cpf_cnpj = cpfCnpjInput ? cpfCnpjInput.value.trim() : '';
        if (cpf_cnpj) {
            const docLimpo = cpf_cnpj.replace(/[^0-9]/g, '');
            const campoCpf = cpfCnpjInput;

            // Deve ter 11 (CPF) ou 14 (CNPJ) dígitos numéricos
            if (docLimpo.length !== 11 && docLimpo.length !== 14) {
                if (campoCpf) {
                    campoCpf.classList.add('input-error');
                    campoCpf.style.borderColor = '#dc3545';
                    campoCpf.style.boxShadow = '0 0 0 2px rgba(220, 53, 69, 0.15)';
                    campoCpf.focus();
                }
                alert('CPF/CNPJ deve ter 11 (CPF) ou 14 (CNPJ) dígitos.');
                return;
            }
            if (!validarCpfCnpjJs(docLimpo)) {
                if (campoCpf) {
                    campoCpf.classList.add('input-error');
                    campoCpf.style.borderColor = '#dc3545';
                    campoCpf.style.boxShadow = '0 0 0 2px rgba(220, 53, 69, 0.15)';
                    campoCpf.focus();
                }
                alert('CPF/CNPJ inválido.');
                return;
            }
            // Se chegou aqui, está válido: limpa estilo de erro
            if (campoCpf) {
                campoCpf.classList.remove('input-error');
                campoCpf.style.borderColor = '';
                campoCpf.style.boxShadow = '';
            }
        }
        
        // Validar campos obrigatórios
        const camposObrigatorios = [
            { id: 'ct-empreendimento', nome: 'Empreendimento' },
            { id: 'ct-modulo', nome: 'Módulo' },
            { id: 'ct-contrato', nome: 'Contrato' },
            { id: 'ct-area', nome: 'Lote/Qda ou Area' },
            { id: 'ct-metragem', nome: 'Metragem' },
            { id: 'ct-vrm2', nome: 'Vr m²' },
            { id: 'ct-inscricao', nome: 'Inscrição' },
            { id: 'ct-valor-venal', nome: 'Valor Venal' },
            { id: 'ct-aliquota', nome: 'Alíquota' },
            { id: 'ct-tx-coleta-lixo', nome: 'Tx Coleta Lixo' },
            { id: 'ct-desconto-vista', nome: 'Valor c\\Desc.' },
            { id: 'ct-valor-anual', nome: 'Valor Anual' },
            { id: 'ct-parcelamento', nome: 'Parcelamento' },
            { id: 'ct-obs', nome: 'Observação' }
        ];
        
        let camposVazios = [];
        camposObrigatorios.forEach(campo => {
            const elemento = document.getElementById(campo.id);
            if (elemento) {
                const valor = elemento.value.trim();
                if (!valor || valor === '') {
                    camposVazios.push(campo.nome);
                    elemento.style.borderColor = '#dc3545';
                    elemento.style.boxShadow = '0 0 0 2px rgba(220, 53, 69, 0.15)';
                } else {
                    elemento.style.borderColor = '';
                    elemento.style.boxShadow = '';
                }
            }
        });
        
        if (camposVazios.length > 0) {
            alert('Por favor, preencha todos os campos obrigatórios:\n\n' + camposVazios.join('\n'));
            return;
        }
        
        const formData = new FormData(form);
        if (contratoEditandoId) {
            formData.append('id', contratoEditandoId);
        }

        const url = '/SISIPTU/php/contratos_api.php?action=' + (contratoEditandoId ? 'update' : 'create');
        
        // Debug: mostrar dados que serão enviados
        console.log('Enviando dados do formulário:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(r => {
            if (!r.ok) {
                return r.text().then(text => {
                    console.error('Erro HTTP:', r.status, text);
                    throw new Error(`HTTP error! status: ${r.status} - ${text.substring(0, 200)}`);
                });
            }
            const contentType = r.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                return r.text().then(text => {
                    console.error('Resposta não é JSON:', text);
                    throw new Error('Resposta do servidor não é JSON válido: ' + text.substring(0, 200));
                });
            }
            return r.json();
        })
        .then(data => {
            if (data && data.sucesso) {
                alert(data.mensagem);
                form.reset();
                contratoEditandoId = null;
                carregarContratos();
                // Atualizar contador após salvar
                setTimeout(() => {
                    const contadorElement = document.getElementById('ct-contador-contratos');
                    if (contadorElement) {
                        fetch('/SISIPTU/php/contratos_api.php?action=list')
                            .then(r => r.json())
                            .then(data => {
                                if (data && data.sucesso && data.total !== undefined) {
                                    contadorElement.textContent = data.total;
                                }
                            })
                            .catch(err => console.error('Erro ao atualizar contador:', err));
                    }
                }, 500);
            } else {
                alert('Erro: ' + (data ? data.mensagem : 'Resposta inválida do servidor'));
            }
        })
        .catch(err => {
            console.error('Erro ao salvar contrato:', err);
            alert('Erro ao processar a requisição. Verifique o console para mais detalhes.');
        });
    }

    function carregarContratos(q) {
        tabelaBody.innerHTML = '<tr><td colspan="8">Carregando...</td></tr>';

        let url = '/SISIPTU/php/contratos_api.php?action=list';
        if (q && q !== '') {
            url += '&q=' + encodeURIComponent(q);
        }

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.sucesso) {
                    tabelaBody.innerHTML = '<tr><td colspan="8">' + (data.mensagem || 'Erro ao carregar contratos.') + '</td></tr>';
                    return;
                }

                // Atualizar contador de contratos
                const contadorElement = document.getElementById('ct-contador-contratos');
                if (contadorElement && data.total !== undefined) {
                    contadorElement.textContent = data.total;
                }

                const contratos = data.contratos || [];
                if (contratos.length === 0) {
                    tabelaBody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Nenhum contrato encontrado.</td></tr>';
                    return;
                }

                tabelaBody.innerHTML = contratos.map(c => {
                    return `
                        <tr>
                            <td>${c.contrato || '-'}</td>
                            <td>${c.empreendimento_nome || '-'}</td>
                            <td>${c.modulo_nome || '-'}</td>
                            <td>${c.cliente_nome || '-'}</td>
                            <td>${c.inscricao || '-'}</td>
                            <td>${c.valor_venal ? 'R$ ' + parseFloat(c.valor_venal).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                            <td>${c.valor_mensal ? 'R$ ' + parseFloat(c.valor_mensal).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '-'}</td>
                            <td>
                                <button class="btn-edit" onclick="editarContrato(${c.id})">✏️ Editar</button>
                                <button class="btn-delete" onclick="excluirContrato(${c.id})">🗑️ Excluir</button>
                            </td>
                        </tr>
                    `;
                }).join('');
            })
            .catch(err => {
                console.error(err);
                tabelaBody.innerHTML = '<tr><td colspan="8">Erro ao carregar contratos.</td></tr>';
            });
    }

    window.editarContrato = function(id) {
        fetch(`/SISIPTU/php/contratos_api.php?action=read&id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (!data.sucesso || !data.contrato) {
                    alert('Erro ao carregar contrato.');
                    return;
                }

                const c = data.contrato;
                contratoEditandoId = c.id;
                
                document.getElementById('ct-empreendimento').value = c.empreendimento_id || '';
                carregarModulosSelectContratos(c.empreendimento_id, c.modulo_id);
                
                // Carregar CPF/CNPJ e cliente
                if (c.cpf_cnpj || c.cliente_cpf_cnpj) {
                    const cpfCnpjInput = document.getElementById('ct-cpf-cnpj');
                    if (cpfCnpjInput) {
                        cpfCnpjInput.value = c.cpf_cnpj || c.cliente_cpf_cnpj || '';
                        // Buscar cliente automaticamente
                        if (typeof buscarClientePorCpfCnpj === 'function') {
                            setTimeout(buscarClientePorCpfCnpj, 100);
                        }
                    }
                }
                
                // Carregar cliente se já estiver vinculado
                if (c.cliente_id && c.cliente_nome) {
                    const clienteNomeInput = document.getElementById('ct-cliente');
                    const clienteIdInput = document.getElementById('ct-cliente-id');
                    if (clienteNomeInput) clienteNomeInput.value = c.cliente_nome;
                    if (clienteIdInput) clienteIdInput.value = c.cliente_id;
                }
                
                document.getElementById('ct-contrato').value = c.contrato || '';
                document.getElementById('ct-area').value = c.area || '';
                document.getElementById('ct-inscricao').value = c.inscricao || '';
                document.getElementById('ct-metragem').value = c.metragem || '';
                document.getElementById('ct-vrm2').value = c.vrm2 ? parseFloat(c.vrm2).toFixed(2).replace('.', ',') : '';
                document.getElementById('ct-valor-venal').value = c.valor_venal ? parseFloat(c.valor_venal).toFixed(2).replace('.', ',') : '';
                document.getElementById('ct-aliquota').value = c.aliquota || '';
                document.getElementById('ct-tx-coleta-lixo').value = c.tx_coleta_lixo ? parseFloat(c.tx_coleta_lixo).toFixed(2).replace('.', ',') : '';
                document.getElementById('ct-valor-anual').value = c.valor_anual ? parseFloat(c.valor_anual).toFixed(2).replace('.', ',') : '';
                document.getElementById('ct-parcelamento').value = c.parcelamento || '';
                // Calcular valor mensal automaticamente
                const valorAnual = c.valor_anual ? parseFloat(c.valor_anual) : 0;
                const parcelamento = c.parcelamento ? parseInt(c.parcelamento) : 0;
                if (valorAnual > 0 && parcelamento > 0) {
                    const valorMensal = valorAnual / parcelamento;
                    document.getElementById('ct-valor-mensal').value = valorMensal.toFixed(2).replace('.', ',');
                } else {
                    document.getElementById('ct-valor-mensal').value = '';
                }
                document.getElementById('ct-desconto-vista').value = c.desconto_a_vista ? parseFloat(c.desconto_a_vista).toFixed(2).replace('.', ',') : '';
                document.getElementById('ct-obs').value = c.obs || '';

                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(err => {
                console.error(err);
                alert('Erro ao carregar contrato.');
            });
    };

    window.excluirContrato = function(id) {
        const confirmar = confirm('⚠️ ATENÇÃO!\n\nTem certeza que deseja excluir este contrato?\n\nEsta ação irá excluir:\n- O contrato selecionado\n- Todas as parcelas relacionadas na tabela de cobrança\n\nEsta ação NÃO pode ser desfeita!\n\nClique em "OK" para confirmar ou "Cancelar" para cancelar.');
        
        if (!confirmar) {
            return;
        }

        fetch(`/SISIPTU/php/contratos_api.php?action=delete&id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    alert(data.mensagem);
                    carregarContratos();
                    // Atualizar contador após excluir
                    setTimeout(() => {
                        const contadorElement = document.getElementById('ct-contador-contratos');
                        if (contadorElement) {
                            fetch('/SISIPTU/php/contratos_api.php?action=list')
                                .then(r => r.json())
                                .then(data => {
                                    if (data && data.sucesso && data.total !== undefined) {
                                        contadorElement.textContent = data.total;
                                    }
                                })
                                .catch(err => console.error('Erro ao atualizar contador:', err));
                        }
                    }, 500);
                } else {
                    alert('Erro: ' + data.mensagem);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Erro ao excluir contrato.');
            });
    };

    // Função para atualizar contador de contratos
    function atualizarContadorContratos() {
        fetch('/SISIPTU/php/contratos_api.php?action=list')
            .then(r => r.json())
            .then(data => {
                if (data && data.sucesso && data.total !== undefined) {
                    const contadorElement = document.getElementById('ct-contador-contratos');
                    if (contadorElement) {
                        contadorElement.textContent = data.total;
                    }
                }
            })
            .catch(err => {
                console.error('Erro ao carregar contador de contratos:', err);
            });
    }
    
    // Carregar lista inicial
    carregarContratos();
    // Atualizar contador
    atualizarContadorContratos();
}

function carregarEmpreendimentosSelectContratos() {
    const select = document.getElementById('ct-empreendimento');
    if (!select) return;

    select.innerHTML = '<option value="">Selecione o empreendimento</option>';

    fetch('/SISIPTU/php/empreendimentos_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) return;

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

function carregarModulosSelectContratos(empreendimentoId = null, moduloIdSelecionado = null) {
    const select = document.getElementById('ct-modulo');
    if (!select) return;

    select.innerHTML = '<option value="">Selecione o módulo</option>';

    let url = '/SISIPTU/php/modulos_api.php?action=list';
    if (empreendimentoId) {
        url += '&empreendimento_id=' + encodeURIComponent(empreendimentoId);
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) return;

            const mods = data.modulos || [];
            if (empreendimentoId) {
                mods.forEach(m => {
                    if (m.empreendimento_id == empreendimentoId) {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.nome;
                        if (moduloIdSelecionado && m.id == moduloIdSelecionado) {
                            opt.selected = true;
                        }
                        select.appendChild(opt);
                    }
                });
            } else {
                mods.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.nome;
                    select.appendChild(opt);
                });
            }
        })
        .catch(err => {
            console.error(err);
        });
}

function carregarEmpreendimentosSelectGerarIptu() {
    const select = document.getElementById('gi-empreendimento');
    if (!select) return;

    const optDefault = document.createElement('option');
    optDefault.value = '';
    optDefault.textContent = 'Selecione o empreendimento';
    optDefault.style.color = '#000';
    select.innerHTML = '';
    select.appendChild(optDefault);

    fetch('/SISIPTU/php/empreendimentos_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) return;

            const emps = data.empreendimentos || [];
            emps.forEach(e => {
                const opt = document.createElement('option');
                opt.value = e.id;
                opt.textContent = e.nome;
                select.appendChild(opt);
            });
            
            // Aplicar cor preta
            select.style.color = '#000';
            
            select.addEventListener('change', function() {
                this.style.color = '#000';
            });
        })
        .catch(err => {
            console.error(err);
        });
}

function carregarModulosSelectGerarIptu(empreendimentoId = null) {
    const select = document.getElementById('gi-modulo');
    if (!select) return;

    const optDefault = document.createElement('option');
    optDefault.value = '';
    optDefault.textContent = 'Selecione o módulo';
    optDefault.style.color = '#000';
    select.innerHTML = '';
    select.appendChild(optDefault);

    let url = '/SISIPTU/php/modulos_api.php?action=list';
    if (empreendimentoId) {
        url += '&empreendimento_id=' + encodeURIComponent(empreendimentoId);
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) return;

            const mods = data.modulos || [];
            if (empreendimentoId) {
                // Filtrar apenas módulos do empreendimento selecionado
                mods.forEach(m => {
                    if (m.empreendimento_id == empreendimentoId) {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.nome;
                        select.appendChild(opt);
                    }
                });
            } else {
                mods.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.nome;
                    select.appendChild(opt);
                });
            }
            
            // Aplicar cor preta
            select.style.color = '#000';
            
            select.addEventListener('change', function() {
                this.style.color = '#000';
            });
        })
        .catch(err => {
            console.error(err);
        });
}

function salvarGerarIptu() {
    const form = document.getElementById('form-gerar-iptu');
    if (!form) return;

    // Preservar valores dos campos antes de criar FormData
    const id = document.getElementById('gi-id')?.value || '';
    const empreendimentoId = document.getElementById('gi-empreendimento')?.value || '';
    const moduloId = document.getElementById('gi-modulo')?.value || '';
    const contratoCodigo = document.getElementById('gi-contrato-codigo')?.value || '';
    const contratoDescricao = document.getElementById('gi-contrato-descricao')?.value || '';
    const anoReferencia = document.getElementById('gi-ano-referencia')?.value || '';
    const valorTotal = document.getElementById('gi-valor-total')?.value || '';
    const parcelamentoQtd = document.getElementById('gi-parcelamento-qtd')?.value || '';
    const parcelamentoTipo = document.getElementById('gi-parcelamento-tipo')?.value || '';
    const primeiraVencimento = document.getElementById('gi-primeira-vencimento')?.value || '';
    const observacoes = document.getElementById('gi-observacoes')?.value || '';
    const ativo = document.getElementById('gi-ativo')?.checked ? '1' : '0';

    const formData = new FormData(form);
    formData.append('action', id ? 'update' : 'create');
    if (id) {
        formData.append('id', id);
    }

    fetch('/SISIPTU/php/gerar_iptu_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => {
            if (!r.ok) {
                throw new Error(`HTTP error! status: ${r.status}`);
            }
            const contentType = r.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return r.text().then(text => {
                    throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
                });
            }
            return r.json();
        })
        .then(data => {
            if (data.sucesso) {
                mostrarMensagemGerarIptu(data.mensagem, 'sucesso');
                // Não limpar campos e grid após salvar - apenas recarregar a tabela
                // Restaurar valores dos campos caso tenham sido limpos
                if (empreendimentoId) document.getElementById('gi-empreendimento').value = empreendimentoId;
                if (moduloId) document.getElementById('gi-modulo').value = moduloId;
                if (contratoCodigo) document.getElementById('gi-contrato-codigo').value = contratoCodigo;
                if (contratoDescricao) {
                    const descInput = document.getElementById('gi-contrato-descricao');
                    if (descInput) {
                        descInput.value = contratoDescricao;
                        descInput.readOnly = true;
                        descInput.style.backgroundColor = '#f5f5f5';
                    }
                }
                if (anoReferencia) document.getElementById('gi-ano-referencia').value = anoReferencia;
                if (valorTotal) document.getElementById('gi-valor-total').value = valorTotal;
                if (parcelamentoQtd) document.getElementById('gi-parcelamento-qtd').value = parcelamentoQtd;
                if (parcelamentoTipo) document.getElementById('gi-parcelamento-tipo').value = parcelamentoTipo;
                if (primeiraVencimento) document.getElementById('gi-primeira-vencimento').value = primeiraVencimento;
                if (observacoes) document.getElementById('gi-observacoes').value = observacoes;
                document.getElementById('gi-ativo').checked = ativo === '1';
                
                // Limpar ID após salvar (novo registro foi criado)
                if (!id) {
                    document.getElementById('gi-id').value = '';
                }
                
                carregarGerarIptu();
            } else {
                mostrarMensagemGerarIptu(data.mensagem || 'Erro ao salvar registro.', 'erro');
            }
        })
        .catch(err => {
            console.error('Erro ao salvar gerar IPTU:', err);
            mostrarMensagemGerarIptu('Erro ao processar a requisição de gerar IPTU: ' + err.message, 'erro');
        });
}

function carregarGerarIptu() {
    const tabelaBody = document.getElementById('tabela-gerar-iptu-body');
    if (!tabelaBody) return;

    // Obter valores dos filtros - usar getElementById diretamente para garantir que os valores sejam lidos
    const selectEmp = document.getElementById('gi-empreendimento');
    const selectMod = document.getElementById('gi-modulo');
    const inputContrato = document.getElementById('gi-contrato-codigo');
    const inputAnoReferencia = document.getElementById('gi-ano-referencia');
    
    const empreendimentoId = selectEmp ? selectEmp.value : '';
    const moduloId = selectMod ? selectMod.value : '';
    const contrato = inputContrato ? inputContrato.value.trim() : '';
    const anoReferencia = inputAnoReferencia ? inputAnoReferencia.value.trim() : '';

    // Verificar se todos os filtros estão preenchidos
    if (!empreendimentoId || !moduloId || !contrato || !anoReferencia) {
        // Não limpar o grid se os campos estiverem vazios - apenas mostrar mensagem
        // Mas não limpar se já houver dados na tabela
        if (tabelaBody.innerHTML.trim() === '' || tabelaBody.innerHTML.includes('Informe Ano Referência') || tabelaBody.innerHTML.includes('Informe Empreendimento') || tabelaBody.innerHTML.includes('Carregando')) {
            tabelaBody.innerHTML = '<tr><td colspan="10">Informe Ano Referência, Empreendimento, Módulo e Contrato para visualizar os registros.</td></tr>';
        }
        return;
    }

    tabelaBody.innerHTML = '<tr><td colspan="10">Carregando...</td></tr>';

    // Construir URL com filtros
    const params = new URLSearchParams({
        action: 'list',
        empreendimento_id: empreendimentoId,
        modulo_id: moduloId,
        contrato: contrato
    });
    
    // Adicionar ano_referencia se estiver preenchido
    if (anoReferencia) {
        params.append('ano_referencia', anoReferencia);
    }

    fetch('/SISIPTU/php/gerar_iptu_api.php?' + params.toString())
        .then(r => r.json())
        .then(data => {
            console.log('Dados retornados da API:', data); // Debug
            if (!data.sucesso) {
                tabelaBody.innerHTML = '<tr><td colspan="10">' + (data.mensagem || 'Erro ao carregar registros.') + '</td></tr>';
                return;
            }

            const registros = data.cobrancas || [];
            console.log('Registros processados:', registros); // Debug
            if (registros.length === 0) {
                tabelaBody.innerHTML = '<tr><td colspan="10">Nenhum registro encontrado para os filtros informados.</td></tr>';
                return;
            }

            tabelaBody.innerHTML = registros.map((r, index) => {
                const valorFormatado = r.valor_mensal ? 
                    parseFloat(r.valor_mensal).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0,00';
                // Usar data_vencimento ou datavencimento (campo renomeado)
                const dataVencRaw = r.data_vencimento || r.datavencimento || r.dia_vencimento;
                const dataVenc = formatarData(dataVencRaw) || '-';
                const pagoStatus = r.pago === 'S' || r.pago === 's' ? 'Sim' : 'Não';
                
                return `
                    <tr>
                        <td>${r.titulo || r.id || index + 1}</td>
                        <td>${r.empreendimento_nome || '-'}</td>
                        <td>${r.modulo_nome || '-'}</td>
                        <td>${r.contrato || ''} ${r.cliente_nome ? '- ' + r.cliente_nome : ''}</td>
                        <td>${r.ano_referencia || '-'}</td>
                        <td>R$ ${valorFormatado}</td>
                        <td>${r.parcelamento || '-'}</td>
                        <td>${dataVenc}</td>
                        <td>-</td>
                        <td>
                            <div class="acoes">
                                <button type="button" class="btn-small btn-delete" 
                                    data-id="${r.id}">Excluir</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        })
        .catch(err => {
            console.error(err);
            tabelaBody.innerHTML = '<tr><td colspan="10">Erro ao carregar registros.</td></tr>';
        });
}

function editarGerarIptu(id) {
    fetch('/SISIPTU/php/gerar_iptu_api.php?action=get&id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                mostrarMensagemGerarIptu(data.mensagem || 'Erro ao carregar registro.', 'erro');
                return;
            }

            const g = data.gerar_iptu;
            document.getElementById('gi-id').value = g.id;
            document.getElementById('gi-empreendimento').value = g.empreendimento_id || '';
            
            // Carregar módulos do empreendimento selecionado
            if (g.empreendimento_id) {
                carregarModulosSelectGerarIptu(g.empreendimento_id).then(() => {
                    document.getElementById('gi-modulo').value = g.modulo_id || '';
                });
            } else {
                document.getElementById('gi-modulo').value = g.modulo_id || '';
            }
            
            document.getElementById('gi-contrato-codigo').value = g.contrato_codigo || '';
            const contratoDescricaoInput = document.getElementById('gi-contrato-descricao');
            if (contratoDescricaoInput) {
                contratoDescricaoInput.value = g.contrato_descricao || '';
                // Se tem descrição, significa que o contrato existe, então desabilitar
                if (g.contrato_descricao) {
                    contratoDescricaoInput.readOnly = true;
                    contratoDescricaoInput.style.backgroundColor = '#f5f5f5';
                } else {
                    contratoDescricaoInput.readOnly = false;
                    contratoDescricaoInput.style.backgroundColor = '';
                }
            }
            document.getElementById('gi-ano-referencia').value = g.ano_referencia || '';
            
            // Formatar valor monetário
            if (g.valor_total_iptu) {
                const valor = parseFloat(g.valor_total_iptu).toFixed(2).replace('.', ',');
                document.getElementById('gi-valor-total').value = valor;
            } else {
                document.getElementById('gi-valor-total').value = '';
            }
            
            document.getElementById('gi-parcelamento-qtd').value = g.parcelamento_quantidade || '';
            // Calcular valor da parcela ao editar
            setTimeout(() => {
                const valorTotalInput = document.getElementById('gi-valor-total');
                const parcelamentoQtdInput = document.getElementById('gi-parcelamento-qtd');
                const valorParcelaInput = document.getElementById('gi-parcelamento-tipo');
                if (valorTotalInput && parcelamentoQtdInput && valorParcelaInput) {
                    const valorTotalStr = valorTotalInput.value.replace(/[^0-9,]/g, '').replace(',', '.');
                    const parcelamentoQtd = parseInt(parcelamentoQtdInput.value) || 0;
                    if (valorTotalStr && parcelamentoQtd > 0) {
                        const valorTotal = parseFloat(valorTotalStr);
                        if (!isNaN(valorTotal) && valorTotal > 0) {
                            const valorParcela = valorTotal / parcelamentoQtd;
                            valorParcelaInput.value = valorParcela.toFixed(2).replace('.', ',');
                        }
                    }
                }
            }, 100);
            document.getElementById('gi-primeira-vencimento').value = g.primeira_vencimento || '';
            document.getElementById('gi-observacoes').value = g.observacoes || '';
            document.getElementById('gi-ativo').checked = !!g.ativo;

            mostrarMensagemGerarIptu('Registro carregado para edição. Altere os dados e clique em Salvar.', 'sucesso');
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemGerarIptu('Erro ao carregar registro.', 'erro');
        });
}

function verificarParcelasExistentes() {
    const empreendimentoId = document.getElementById('gi-empreendimento')?.value || '';
    const moduloId = document.getElementById('gi-modulo')?.value || '';
    const contrato = document.getElementById('gi-contrato-codigo')?.value.trim() || '';
    const anoReferencia = document.getElementById('gi-ano-referencia')?.value.trim() || '';
    
    // Verificar se todos os campos obrigatórios estão preenchidos
    if (!empreendimentoId || !moduloId || !contrato) {
        return;
    }
    
    // Construir URL para verificar parcelas
    const params = new URLSearchParams({
        action: 'verificar-parcelas',
        empreendimento_id: empreendimentoId,
        modulo_id: moduloId,
        contrato: contrato
    });
    
    // Adicionar ano_referencia se estiver preenchido
    if (anoReferencia) {
        params.append('ano_referencia', anoReferencia);
    }
    
    fetch(`/SISIPTU/php/gerar_iptu_api.php?${params.toString()}`)
        .then(r => {
            if (!r.ok) {
                throw new Error(`HTTP error! status: ${r.status}`);
            }
            return r.json();
        })
        .then(data => {
            if (data && data.sucesso) {
                if (data.existe && data.total > 0) {
                    // Mostrar mensagem informativa
                    mostrarMensagemGerarIptu(`⚠️ Atenção: Já existem ${data.total} parcela(s) gerada(s) para este contrato${anoReferencia ? ' e ano de referência ' + anoReferencia : ''}.`, 'aviso');
                }
            }
        })
        .catch(err => {
            console.error('Erro ao verificar parcelas existentes:', err);
            // Não mostrar erro ao usuário, apenas logar no console
        })
        .catch(err => {
            console.error('Erro ao verificar parcelas:', err);
        });
}

function excluirGerarIptu(id) {
    if (!confirm('Tem certeza que deseja excluir esta parcela?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);

    fetch('/SISIPTU/php/gerar_iptu_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagemGerarIptu(data.mensagem, 'sucesso');
                carregarGerarIptu();
            } else {
                mostrarMensagemGerarIptu(data.mensagem || 'Erro ao excluir registro.', 'erro');
            }
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemGerarIptu('Erro ao excluir registro.', 'erro');
        });
}

function mostrarMensagemGerarIptu(texto, tipo) {
    const msg = document.getElementById('gi-mensagem');
    if (!msg) return;

    if (!texto || texto === '') {
        msg.style.display = 'none';
        msg.innerHTML = '';
        msg.className = 'mensagem';
        return;
    }

    msg.className = 'mensagem ' + tipo;
    msg.style.display = 'block';
    msg.style.position = 'relative';
    msg.style.zIndex = '1000';
    msg.style.cursor = 'default';
    
    // Limpar conteúdo anterior e adicionar texto e botão de fechar
    msg.innerHTML = '';
    const span = document.createElement('span');
    span.textContent = texto;
    span.style.display = 'inline-block';
    span.style.cursor = 'default';
    
    // Permitir fechar com duplo clique na mensagem
    msg.addEventListener('dblclick', function(e) {
        e.preventDefault();
        e.stopPropagation();
        msg.style.display = 'none';
        msg.innerHTML = '';
        msg.className = 'mensagem';
    });
    
    msg.appendChild(span);
    
    // Adicionar botão OK para fechar a mensagem (sempre visível)
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'OK';
    btn.style.marginLeft = '10px';
    btn.style.padding = '5px 15px';
    btn.style.cursor = 'pointer';
    btn.style.border = '1px solid #ccc';
    btn.style.borderRadius = '4px';
    btn.style.backgroundColor = '#007bff';
    btn.style.color = 'white';
    btn.style.fontSize = '14px';
    btn.style.pointerEvents = 'auto';
    btn.style.zIndex = '1001';
    btn.className = 'btn-small btn-message-ok';
    
    // Função para fechar a mensagem
    const fecharMensagem = function(e) {
        e.preventDefault();
        e.stopPropagation();
        msg.style.display = 'none';
        msg.innerHTML = '';
        msg.className = 'mensagem';
    };
    
    btn.addEventListener('click', fecharMensagem);
    btn.addEventListener('mousedown', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });
    
    msg.appendChild(btn);

    // Adicionar listener para fechar com ESC
    const fecharComEsc = function(e) {
        if (e.key === 'Escape' && msg && msg.style.display === 'block') {
            msg.style.display = 'none';
            msg.innerHTML = '';
            msg.className = 'mensagem';
            document.removeEventListener('keydown', fecharComEsc);
        }
    };
    document.addEventListener('keydown', fecharComEsc);

    // Para mensagens de sucesso, também fechar automaticamente após 5 segundos
    if (tipo === 'sucesso') {
        setTimeout(() => {
            if (msg && msg.style.display === 'block') {
                msg.style.display = 'none';
                msg.innerHTML = '';
                msg.className = 'mensagem';
                document.removeEventListener('keydown', fecharComEsc);
            }
        }, 5000);
    }
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
                            <li>📄 Contrato</li>
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
                                    <input type="text" id="cli-cpf-cnpj" name="cpf_cnpj" placeholder="CPF/CNPJ - Cliente" maxlength="18" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-nome">Nome do Cliente</label>
                                    <input type="text" id="cli-nome" name="nome" placeholder="Nome do Cliente" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-tipo-cadastro">Tipo de Cadastro</label>
                                    <select id="cli-tipo-cadastro" name="tipo_cadastro" title="Tipo de Cadastro">
                                        <option value="">Tipo de Cadastro</option>
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
                                    <input type="text" id="cli-cep" name="cep" placeholder="CEP">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-endereco">Endereço</label>
                                    <input type="text" id="cli-endereco" name="endereco" placeholder="Endereço">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-bairro">Bairro</label>
                                    <input type="text" id="cli-bairro" name="bairro" placeholder="Bairro">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-cidade">Cidade</label>
                                    <input type="text" id="cli-cidade" name="cidade" placeholder="Cidade">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-uf">UF</label>
                                    <input type="text" id="cli-uf" name="uf" placeholder="UF" maxlength="2" style="text-transform: uppercase;">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-cod-municipio">Cod. Município</label>
                                    <input type="text" id="cli-cod-municipio" name="cod_municipio" placeholder="Cod. Município">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-data-nasc">Data de Nasc.</label>
                                    <input type="date" id="cli-data-nasc" name="data_nasc" title="Data de Nasc.">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-profissao">Profissão</label>
                                    <input type="text" id="cli-profissao" name="profissao" placeholder="Profissão">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-identidade">Cart. Identidade</label>
                                    <input type="text" id="cli-identidade" name="identidade" placeholder="Cart. Identidade">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-estado-civil">Estado Civil</label>
                                    <input type="text" id="cli-estado-civil" name="estado_civil" placeholder="Estado Civil">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-nacionalidade">Nacionalidade</label>
                                    <input type="text" id="cli-nacionalidade" name="nacionalidade" placeholder="Nacionalidade">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-regime-casamento">Regime de Casamento</label>
                                    <input type="text" id="cli-regime-casamento" name="regime_casamento" placeholder="Regime de Casamento">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-email">E-mail</label>
                                    <input type="email" id="cli-email" name="email" placeholder="E-mail">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-site">Site</label>
                                    <input type="text" id="cli-site" name="site" placeholder="Site">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-tel-comercial">Telefone Comercial</label>
                                    <input type="text" id="cli-tel-comercial" name="tel_comercial" placeholder="Telefone Comercial">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-tel-cel1">Telefone Celular 1</label>
                                    <input type="text" id="cli-tel-cel1" name="tel_celular1" placeholder="Telefone Celular 1">
                                </div>
                                <div class="form-group-inline">
                                    <label for="cli-tel-cel2">Telefone Celular 2</label>
                                    <input type="text" id="cli-tel-cel2" name="tel_celular2" placeholder="Telefone Celular 2">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="cli-tel-residencial">Telefone Residencial</label>
                                    <input type="text" id="cli-tel-residencial" name="tel_residencial" placeholder="Telefone Residencial">
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
            
        case 'cadastro-empresas':
            titulo = 'Cadastro - Empresas';
            conteudo = `
                <div class="page-content" id="empresas-page">
                    <h3>🏭 Cadastro de Empresas</h3>
                    <p>Cadastre, altere, exclua e pesquise empresas.</p>
                    
                    <div class="form-section">
                        <form id="form-empresa">
                            <input type="hidden" id="empresa-id" name="id">
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="empresa-cnpj">CNPJ</label>
                                    <input type="text" id="empresa-cnpj" name="cnpj" placeholder="CNPJ" maxlength="18" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="empresa-razao-social">Razão Social</label>
                                    <input type="text" id="empresa-razao-social" name="razao_social" placeholder="Razão Social" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="empresa-nome-fantasia">Nome Fantasia</label>
                                    <input type="text" id="empresa-nome-fantasia" name="nome_fantasia" placeholder="Nome Fantasia">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="empresa-cep">CEP</label>
                                    <input type="text" id="empresa-cep" name="cep" placeholder="CEP">
                                </div>
                                <div class="form-group-inline">
                                    <label for="empresa-endereco">Endereço</label>
                                    <input type="text" id="empresa-endereco" name="endereco" placeholder="Endereço">
                                </div>
                                <div class="form-group-inline">
                                    <label for="empresa-bairro">Bairro</label>
                                    <input type="text" id="empresa-bairro" name="bairro" placeholder="Bairro">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="empresa-cidade">Cidade</label>
                                    <input type="text" id="empresa-cidade" name="cidade" placeholder="Cidade">
                                </div>
                                <div class="form-group-inline">
                                    <label for="empresa-uf">UF</label>
                                    <input type="text" id="empresa-uf" name="uf" placeholder="UF" maxlength="2" style="text-transform: uppercase;">
                                </div>
                                <div class="form-group-inline">
                                    <label for="empresa-cod-municipio">Cod. Município</label>
                                    <input type="text" id="empresa-cod-municipio" name="cod_municipio" placeholder="Cod. Município">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="empresa-email">E-mail</label>
                                    <input type="email" id="empresa-email" name="email" placeholder="E-mail">
                                </div>
                                <div class="form-group-inline">
                                    <label for="empresa-site">Site</label>
                                    <input type="text" id="empresa-site" name="site" placeholder="Site">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="empresa-tel-comercial">Telefone Comercial</label>
                                    <input type="text" id="empresa-tel-comercial" name="tel_comercial" placeholder="Telefone Comercial">
                                </div>
                                <div class="form-group-inline">
                                    <label for="empresa-tel-cel1">Telefone Celular 1</label>
                                    <input type="text" id="empresa-tel-cel1" name="tel_celular1" placeholder="Telefone Celular 1">
                                </div>
                                <div class="form-group-inline">
                                    <label for="empresa-tel-cel2">Telefone Celular 2</label>
                                    <input type="text" id="empresa-tel-cel2" name="tel_celular2" placeholder="Telefone Celular 2">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline checkbox-group">
                                    <label>
                                        <input type="checkbox" id="empresa-ativo" name="ativo" value="1" checked>
                                        Ativo
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary" id="btn-salvar-empresa">Salvar</button>
                                <button type="button" class="btn-secondary" id="btn-novo-empresa">Novo</button>
                            </div>
                            
                            <div id="empresas-mensagem" class="mensagem" style="margin-top: 10px; display: none;"></div>
                        </form>
                    </div>
                    
                    <div class="table-section">
                        <h4>Pesquisa de Empresas</h4>
                        <div class="form-row" style="margin-bottom: 10px;">
                            <div class="form-group-inline">
                                <label for="empresas-busca">Pesquisar por Razão Social, Nome Fantasia ou CNPJ</label>
                                <input type="text" id="empresas-busca" placeholder="Digite parte do nome ou CNPJ">
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn-primary" id="btn-buscar-empresa">Pesquisar</button>
                                <button type="button" class="btn-secondary" id="btn-limpar-busca-empresa">Limpar</button>
                            </div>
                        </div>
                        
                        <div class="table-wrapper">
                            <table class="table" id="tabela-empresas">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>CNPJ</th>
                                        <th>Razão Social</th>
                                        <th>Nome Fantasia</th>
                                        <th>Cidade</th>
                                        <th>UF</th>
                                        <th>E-mail</th>
                                        <th>Telefone</th>
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
            setTimeout(inicializarCadastroEmpresas, 0);
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
                                    <label for="emp-empresa"><strong>Empresa</strong></label>
                                    <select id="emp-empresa" name="empresa_id" title="Empresa">
                                        <option value="">Selecione a empresa...</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="emp-nome"><strong>Nome do Empreendimento</strong></label>
                                    <input type="text" id="emp-nome" name="nome" placeholder="Nome do Empreendimento" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="emp-banco">Banco</label>
                                    <select id="emp-banco" name="banco_id" title="Banco">
                                        <option value="" style="color: #000;">Banco</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="emp-cidade">Cidade</label>
                                    <input type="text" id="emp-cidade" name="cidade" placeholder="Cidade">
                                </div>
                                <div class="form-group-inline">
                                    <label for="emp-uf">UF</label>
                                    <input type="text" id="emp-uf" name="uf" placeholder="UF" maxlength="2" style="text-transform: uppercase;">
                                </div>
                                <div class="form-group-inline">
                                    <label for="emp-cep">CEP</label>
                                    <input type="text" id="emp-cep" name="cep" placeholder="CEP">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="emp-endereco">Endereço</label>
                                    <input type="text" id="emp-endereco" name="endereco" placeholder="Endereço">
                                </div>
                                <div class="form-group-inline">
                                    <label for="emp-bairro">Bairro</label>
                                    <input type="text" id="emp-bairro" name="bairro" placeholder="Bairro">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="emp-descricao">Descrição / Observações</label>
                                    <input type="text" id="emp-descricao" name="descricao" placeholder="Descrição / Observações">
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
                                    <input type="text" id="mod-nome" name="nome" placeholder="Módulo" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="mod-emp-id">Empreendimento</label>
                                    <select id="mod-emp-id" name="empreendimento_id" title="Empreendimento" required>
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
                                    <input type="text" id="usuario-nome" name="nome" placeholder="Nome Completo" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="usuario-usuario">Usuário</label>
                                    <input type="text" id="usuario-usuario" name="usuario" placeholder="Usuário" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="usuario-senha">Senha (sem criptografia)</label>
                                    <input type="text" id="usuario-senha" name="senha" placeholder="Senha">
                                </div>
                                <div class="form-group-inline">
                                    <label for="usuario-email">E-mail</label>
                                    <input type="email" id="usuario-email" name="email" placeholder="E-mail">
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
                                    <input type="text" id="banco-cedente" name="cedente" placeholder="Cedente" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-cnpj-cpf">CNPJ / CPF</label>
                                    <input type="text" id="banco-cnpj-cpf" name="cnpj_cpf" maxlength="18" placeholder="CNPJ / CPF">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-banco">Banco <span class="required">*</span></label>
                                    <input type="text" id="banco-banco" name="banco" placeholder="Banco" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="banco-conta">Conta</label>
                                    <input type="text" id="banco-conta" name="conta" placeholder="Conta" maxlength="20">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-agencia">Agência</label>
                                    <input type="text" id="banco-agencia" name="agencia" placeholder="Agência" maxlength="10">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-num-banco">Número do Banco</label>
                                    <input type="text" id="banco-num-banco" name="num_banco" placeholder="Número do Banco" maxlength="10">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-carteira">Carteira</label>
                                    <input type="text" id="banco-carteira" name="carteira" placeholder="Carteira" maxlength="20">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="banco-operacao-cc">Operação C\\C</label>
                                    <input type="text" id="banco-operacao-cc" name="operacao_cc" placeholder="Operação C/C">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-apelido">Apelido</label>
                                    <input type="text" id="banco-apelido" name="apelido" placeholder="Apelido">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-convenio">Convênio</label>
                                    <input type="text" id="banco-convenio" name="convenio" placeholder="Convênio">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-multa-mes">Multa ao Mês (%)</label>
                                    <input type="text" id="banco-multa-mes" name="multa_mes" class="input-monetario" placeholder="Multa ao Mês (%)">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="banco-tarifa-bancaria">Tarifa Bancária</label>
                                    <input type="text" id="banco-tarifa-bancaria" name="tarifa_bancaria" class="input-monetario" placeholder="Tarifa Bancária">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-juros-mes">Juros ao Mês (%)</label>
                                    <input type="text" id="banco-juros-mes" name="juros_mes" class="input-monetario" placeholder="Juros ao Mês (%)">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-prazo-devolucao">Prazo Devolução (dias)</label>
                                    <input type="number" id="banco-prazo-devolucao" name="prazo_devolucao" placeholder="Prazo Devolução (dias)" min="0" max="999" step="1">
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-codigo-cedente">Código Cedente</label>
                                    <input type="text" id="banco-codigo-cedente" name="codigo_cedente" placeholder="Código Cedente">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="banco-operacao-cedente">Operação Cedente</label>
                                    <input type="text" id="banco-operacao-cedente" name="operacao_cedente" placeholder="Operação Cedente">
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
                                    <textarea id="banco-instrucoes-bancarias" name="instrucoes_bancarias" rows="8" placeholder="Instruções Bancárias"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="banco-remessa" style="color: #2d8659; font-weight: bold; margin-bottom: 8px; display: block;">Cam.Remessa (Diretório)</label>
                                    <div style="position: relative;">
                                        <div style="display: flex; gap: 5px; align-items: center;">
                                            <div style="position: relative; flex: 1;">
                                                <input type="text" id="banco-remessa" name="caminho_remessa" placeholder="Caminho do diretório (ex: uploads/remessas)" style="width: 100%; padding: 8px 35px 8px 12px; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa;">
                                                <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #999; font-size: 14px;">🔄</span>
                                            </div>
                                            <button type="button" id="btn-buscar-remessa" class="btn-secondary" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Buscar</button>
                                        </div>
                                        <div id="banco-lista-diretorios-remessa" style="display: none; position: absolute; top: 100%; left: 0; right: 70px; margin-top: 2px; background: white; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-height: 300px; overflow-y: auto; z-index: 1000;">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group-inline">
                                    <label for="banco-retorno" style="color: #2d8659; font-weight: bold; margin-bottom: 8px; display: block;">Cam.Retorno (Diretório)</label>
                                    <div style="position: relative;">
                                        <div style="display: flex; gap: 5px; align-items: center;">
                                            <div style="position: relative; flex: 1;">
                                                <input type="text" id="banco-retorno" name="caminho_retorno" placeholder="Caminho do diretório (ex: uploads/retornos)" style="width: 100%; padding: 8px 35px 8px 12px; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa;">
                                                <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #999; font-size: 14px;">🔄</span>
                                            </div>
                                            <button type="button" id="btn-buscar-retorno" class="btn-secondary" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Buscar</button>
                                        </div>
                                        <div id="banco-lista-diretorios-retorno" style="display: none; position: absolute; top: 100%; left: 0; right: 70px; margin-top: 2px; background: white; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-height: 300px; overflow-y: auto; z-index: 1000;">
                                        </div>
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
            
        case 'cadastro-contrato':
            titulo = 'Cadastro - Contrato';
            conteudo = `
                <div class="page-content">
                    <h3>📄 Cadastro de Contratos</h3>
                    <p>Esta seção será utilizada para cadastrar e gerenciar contratos no sistema.</p>
                    <p>Funcionalidades em desenvolvimento...</p>
                </div>
            `;
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
            titulo = 'IPTU - Importar Clientes';
            conteudo = `
                <div class="page-content" id="importar-clientes-page">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0;">📥 Importar Clientes</h3>
                        <button type="button" id="btn-help-importar" class="btn-secondary" style="padding: 8px 16px;">❓ Ajuda - Formato do Arquivo</button>
                    </div>
                    
                    <div id="ic-help-section" style="display: none; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
                        <h4 style="margin-top: 0; color: #2d8659;">📋 Formato do Arquivo para Importação</h4>
                        <p><strong>O arquivo deve ser um arquivo de texto (.txt) ou CSV (.csv) com as colunas separadas por delimitador.</strong></p>
                        
                        <div style="margin: 15px 0;">
                            <h5 style="color: #495057;">Estrutura das Colunas (na ordem):</h5>
                            <ol style="line-height: 1.8;">
                                <li><strong>CPF/CNPJ</strong> - CPF ou CNPJ do cliente (obrigatório, será validado)</li>
                                <li><strong>Nome</strong> - Nome completo do cliente (obrigatório)</li>
                                <li><strong>Tipo Cadastro</strong> - Tipo de cadastro (ex: Pessoa Física, Pessoa Jurídica)</li>
                                <li><strong>CEP</strong> - CEP do endereço</li>
                                <li><strong>Endereço</strong> - Logradouro e número</li>
                                <li><strong>Bairro</strong> - Bairro</li>
                                <li><strong>Cidade</strong> - Cidade</li>
                                <li><strong>UF</strong> - Estado (sigla de 2 letras)</li>
                                <li><strong>Cód Município</strong> - Código do município</li>
                                <li><strong>Data Nasc</strong> - Data de nascimento (formato: DD/MM/YYYY ou YYYY-MM-DD)</li>
                                <li><strong>Profissão</strong> - Profissão</li>
                                <li><strong>Identidade</strong> - Número da identidade</li>
                                <li><strong>Estado Civil</strong> - Estado civil</li>
                                <li><strong>Nacionalidade</strong> - Nacionalidade</li>
                                <li><strong>Regime Casamento</strong> - Regime de casamento</li>
                                <li><strong>Email</strong> - E-mail</li>
                                <li><strong>Site</strong> - Site/Website</li>
                                <li><strong>Tel Comercial</strong> - Telefone comercial</li>
                                <li><strong>Tel Celular1</strong> - Telefone celular principal</li>
                                <li><strong>Tel Celular2</strong> - Telefone celular secundário</li>
                                <li><strong>Tel Residencial</strong> - Telefone residencial</li>
                                <li><strong>CPF Cônjuge</strong> - CPF do cônjuge</li>
                                <li><strong>Nome Cônjuge</strong> - Nome do cônjuge</li>
                                <li><strong>Ativo</strong> - Status ativo (true/1 ou false/0, padrão: true)</li>
                            </ol>
                        </div>
                        
                        <div style="margin: 15px 0;">
                            <h5 style="color: #495057;">Exemplo de arquivo CSV (delimitador: vírgula):</h5>
                            <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 3px; overflow-x: auto; font-size: 12px;">CPF/CNPJ,Nome,Tipo Cadastro,CEP,Endereço,Bairro,Cidade,UF,Cód Município,Data Nasc,Profissão,Identidade,Estado Civil,Nacionalidade,Regime Casamento,Email,Site,Tel Comercial,Tel Celular1,Tel Celular2,Tel Residencial,CPF Cônjuge,Nome Cônjuge,Ativo
12345678901,João Silva,Pessoa Física,12345-678,Rua das Flores 123,Centro,São Paulo,SP,3550308,15/05/1980,Engenheiro,123456789,Solteiro,Brasileiro,,joao@email.com,,,(11)98765-4321,,(11)3456-7890,,,true
98765432100,Maria Santos,Pessoa Física,54321-876,Av. Principal 456,Jardim América,Rio de Janeiro,RJ,3304557,20/10/1985,Advogada,987654321,Casada,Brasileira,Comunhão Parcial,maria@email.com,,,(21)99876-5432,,(21)2345-6789,11122233344,José Santos,true</pre>
                        </div>
                        
                        <div style="margin: 15px 0;">
                            <h5 style="color: #495057;">Exemplo de arquivo TXT (delimitador: ponto e vírgula):</h5>
                            <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 3px; overflow-x: auto; font-size: 12px;">CPF/CNPJ;Nome;Tipo Cadastro;CEP;Endereço;Bairro;Cidade;UF;Cód Município;Data Nasc;Profissão;Identidade;Estado Civil;Nacionalidade;Regime Casamento;Email;Site;Tel Comercial;Tel Celular1;Tel Celular2;Tel Residencial;CPF Cônjuge;Nome Cônjuge;Ativo
12345678901;João Silva;Pessoa Física;12345-678;Rua das Flores 123;Centro;São Paulo;SP;3550308;15/05/1980;Engenheiro;123456789;Solteiro;Brasileiro;;joao@email.com;;;(11)98765-4321;;(11)3456-7890;;;true
98765432100;Maria Santos;Pessoa Física;54321-876;Av. Principal 456;Jardim América;Rio de Janeiro;RJ;3304557;20/10/1985;Advogada;987654321;Casada;Brasileira;Comunhão Parcial;maria@email.com;;;(21)99876-5432;;(21)2345-6789;11122233344;José Santos;true</pre>
                        </div>
                        
                        <div style="margin: 15px 0; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 3px;">
                            <h5 style="margin-top: 0; color: #856404;">⚠️ Observações Importantes:</h5>
                            <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
                                <li><strong>Primeira linha:</strong> Se a primeira linha contém cabeçalhos, marque a opção "Primeira linha é cabeçalho"</li>
                                <li><strong>Validação de CPF/CNPJ:</strong> O CPF/CNPJ será validado automaticamente. Se já existir um cliente com o mesmo CPF/CNPJ, o registro será ignorado (não importado)</li>
                                <li><strong>Campos obrigatórios:</strong> CPF/CNPJ e Nome são obrigatórios</li>
                                <li><strong>Data de Nascimento:</strong> Aceita formatos DD/MM/YYYY ou YYYY-MM-DD</li>
                                <li><strong>Delimitador:</strong> Escolha o delimitador correto (vírgula, ponto e vírgula, tabulação ou pipe)</li>
                                <li><strong>Campos opcionais:</strong> Todos os campos, exceto CPF/CNPJ e Nome, são opcionais e podem ser deixados em branco</li>
                                <li><strong>Status Ativo:</strong> Se não informado, o cliente será cadastrado como ativo (true)</li>
                            </ul>
                        </div>
                        
                        <div style="margin-top: 15px; text-align: right;">
                            <button type="button" id="btn-fechar-help" class="btn-secondary" style="padding: 6px 12px;">Fechar Ajuda</button>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <form id="form-importar-clientes">
                            <div class="form-row">
                                <div class="form-group-inline" style="flex: 1;">
                                    <label for="ic-diretorio-arquivo" style="color: #2d8659; font-weight: bold; margin-bottom: 8px; display: block;">Diretório do Servidor</label>
                                    <div style="position: relative;">
                                        <div style="display: flex; gap: 5px; align-items: center;">
                                            <div style="position: relative; flex: 1;">
                                                <input type="text" id="ic-diretorio-arquivo" placeholder="Caminho do diretório (ex: uploads/clientes)" style="width: 100%; padding: 8px 35px 8px 12px; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa;">
                                                <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #999; font-size: 14px;">🔄</span>
                                            </div>
                                            <button type="button" id="btn-buscar-diretorio" class="btn-secondary" style="padding: 8px 16px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">Buscar</button>
                                        </div>
                                        <div id="ic-lista-diretorios" style="display: none; position: absolute; top: 100%; left: 0; right: 70px; margin-top: 2px; background: white; border: 1px solid #ddd; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-height: 300px; overflow-y: auto; z-index: 1000;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline" style="flex: 1;">
                                    <label for="ic-arquivo-selecionado"><strong>Arquivo Selecionado</strong></label>
                                    <select id="ic-arquivo-selecionado" style="width: 100%;">
                                        <option value="">Selecione um arquivo...</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="ic-delimitador"><strong>Delimitador</strong></label>
                                    <select id="ic-delimitador">
                                        <option value=",">Vírgula (,)</option>
                                        <option value=";">Ponto e Vírgula (;)</option>
                                        <option value="\t">Tabulação (TAB)</option>
                                        <option value="|">Pipe (|)</option>
                                    </select>
                                </div>
                                <div class="form-group-inline">
                                    <label for="ic-primeira-linha-cabecalho">
                                        <input type="checkbox" id="ic-primeira-linha-cabecalho" checked>
                                        Primeira linha é cabeçalho
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" id="btn-preview-arquivo" class="btn-secondary">👁️ Visualizar Arquivo</button>
                                <button type="button" id="btn-importar-arquivo" class="btn-primary">📥 Importar Clientes</button>
                            </div>
                            
                            <div id="ic-mensagem" class="mensagem" style="margin-top: 10px; display: none;"></div>
                        </form>
                    </div>
                    
                    <div class="table-section" id="ic-preview-section" style="display: none;">
                        <h4>Pré-visualização do Arquivo</h4>
                        <div class="table-wrapper">
                            <table class="table" id="tabela-preview-importacao">
                                <thead id="tabela-preview-importacao-head">
                                </thead>
                                <tbody id="tabela-preview-importacao-body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="table-section" id="ic-resultado-section" style="display: none;">
                        <h4>Resultado da Importação</h4>
                        <div id="ic-resultado-conteudo"></div>
                    </div>
                </div>
            `;
            setTimeout(inicializarImportarClientes, 0);
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
        case 'iptu-gerar-iptu':
            titulo = 'IPTU - Gerar IPTU';
            conteudo = `
                <div class="page-content" id="gerar-iptu-page">
                    <h3>📄 Gerar IPTU</h3>
                    
                    <div class="form-section">
                        <form id="form-gerar-iptu">
                            <input type="hidden" id="gi-id" name="id">
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="gi-ano-referencia">Ano Referência</label>
                                    <input type="number" id="gi-ano-referencia" name="ano_referencia" placeholder="2024" min="2000" max="2100" step="1" maxlength="4">
                                </div>
                                <div class="form-group-inline">
                                    <label for="gi-empreendimento">Empreendimento</label>
                                    <select id="gi-empreendimento" name="empreendimento_id" class="required">
                                        <option value="" style="color: #dc3545;">Selecione o empreendimento</option>
                                    </select>
                                </div>
                                <div class="form-group-inline">
                                    <label for="gi-modulo">Módulo</label>
                                    <select id="gi-modulo" name="modulo_id" class="required">
                                        <option value="" style="color: #dc3545;">Selecione o módulo</option>
                                    </select>
                                </div>
                                <div class="form-group-inline" style="display: flex; gap: 5px;">
                                    <div style="flex: 0 0 100px;">
                                        <label for="gi-contrato-codigo">Contrato</label>
                                        <input type="text" id="gi-contrato-codigo" name="contrato_codigo" placeholder="Código" maxlength="50">
                                    </div>
                                    <div style="flex: 1;">
                                        <label for="gi-contrato-descricao"><strong>Cliente</strong></label>
                                        <input type="text" id="gi-contrato-descricao" name="contrato_descricao" placeholder="Cliente" maxlength="200" readonly style="background-color: #f5f5f5; color: #000;">
                                    </div>
                                </div>
                            </div>
                            
                            <fieldset style="border: 2px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 5px;">
                                <legend style="font-weight: bold; padding: 0 10px;">Gera Parcelas IPTU/Contrato</legend>
                                
                                <div class="form-row">
                                    <div class="form-group-inline">
                                        <label for="gi-valor-total">Valor Total IPTU</label>
                                        <input type="text" id="gi-valor-total" name="valor_total_iptu" class="input-monetario" placeholder="0,00">
                                    </div>
                                    <div class="form-group-inline" style="display: flex; gap: 5px;">
                                        <div style="flex: 0 0 100px;">
                                            <label for="gi-parcelamento-qtd">Parcelamento</label>
                                            <input type="number" id="gi-parcelamento-qtd" name="parcelamento_quantidade" placeholder="Qtd" min="1" max="999" step="1">
                                        </div>
                                        <div style="flex: 1;">
                                            <label for="gi-parcelamento-tipo"><strong>Valor</strong></label>
                                            <input type="text" id="gi-parcelamento-tipo" name="parcelamento_tipo" placeholder="Valor" class="input-monetario" maxlength="100" readonly style="background: #f5f5f5; color: #000;">
                                        </div>
                                    </div>
                                    <div class="form-group-inline">
                                        <label for="gi-primeira-vencimento">1ª Vencimento</label>
                                        <input type="date" id="gi-primeira-vencimento" name="primeira_vencimento">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group-full">
                                        <label for="gi-observacoes">Observações</label>
                                        <textarea id="gi-observacoes" name="observacoes" rows="3" placeholder="Digite observações aqui..."></textarea>
                                    </div>
                                </div>
                            </fieldset>
                            
                            <div class="form-row">
                                <div class="form-group-inline checkbox-group">
                                    <label>
                                        <input type="checkbox" id="gi-ativo" name="ativo" value="1" checked>
                                        Ativo
                                    </label>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn-primary">Salvar</button>
                                <button type="button" id="btn-novo-gerar-iptu" class="btn-secondary">Novo</button>
                                <button type="button" id="btn-excluir-parcelas-gerar-iptu">🗑️ Excluir Parcelas</button>
                            </div>
                            
                            <div id="gi-mensagem" class="mensagem" style="margin-top: 10px; display: none;"></div>
                        </form>
                    </div>
                    
                    <div class="table-section">
                        <h4>Registros de Gerar IPTU</h4>
                        <div class="table-wrapper">
                            <table class="table" id="tabela-gerar-iptu">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Empreendimento</th>
                                        <th>Módulo</th>
                                        <th>Contrato</th>
                                        <th>Ano Ref.</th>
                                        <th>Valor Total</th>
                                        <th>Parcelamento</th>
                                        <th>Vencimento</th>
                                        <th>Ativo</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-gerar-iptu-body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            setTimeout(inicializarGerarIptu, 0);
            break;
            
        case 'iptu-pesquisar-contratos':
            titulo = 'IPTU - Pesquisar Contratos';
            conteudo = `
                <div class="page-content" id="pesquisar-contratos-page">
                    <h3>🔍 Pesquisar Contratos</h3>
                    <p>Pesquise e visualize informações dos contratos cadastrados no sistema.</p>
                    
                    <div class="form-section">
                        <form id="form-pesquisar-contratos">
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="pc-contrato">Contrato</label>
                                    <input type="text" id="pc-contrato" name="contrato" placeholder="Digite o número do contrato" maxlength="200">
                                </div>
                                <div class="form-group-inline">
                                    <label for="pc-cpf-cnpj">CPF/CNPJ</label>
                                    <input type="text" id="pc-cpf-cnpj" name="cpf_cnpj" placeholder="Digite o CPF/CNPJ" maxlength="18">
                                </div>
                                <div class="form-group-inline">
                                    <label for="pc-inscricao">Inscrição</label>
                                    <input type="text" id="pc-inscricao" name="inscricao" placeholder="Digite a inscrição" maxlength="100">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="pc-empreendimento">Empreendimento</label>
                                    <select id="pc-empreendimento" name="empreendimento_id">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="form-group-inline">
                                    <label for="pc-modulo">Módulo</label>
                                    <select id="pc-modulo" name="modulo_id">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="form-group-inline">
                                    <label for="pc-situacao">Situação</label>
                                    <select id="pc-situacao" name="situacao">
                                        <option value="">Todas</option>
                                        <option value="Ativo">Ativo</option>
                                        <option value="Inativo">Inativo</option>
                                        <option value="Cancelado">Cancelado</option>
                                        <option value="Suspenso">Suspenso</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" id="btn-pesquisar-contratos" class="btn-primary">🔍 Pesquisar</button>
                                <button type="button" id="btn-mostrar-todos-contratos" class="btn-secondary">📋 Mostrar Todos</button>
                                <button type="button" id="btn-limpar-pesquisa-contratos" class="btn-secondary">🗑️ Limpar</button>
                            </div>
                            
                            <div id="pc-mensagem" class="mensagem" style="margin-top: 10px; display: none;"></div>
                        </form>
                    </div>
                    
                    <div class="table-section">
                        <h4>Resultados da Pesquisa</h4>
                        <div class="table-wrapper">
                            <table class="table" id="tabela-pesquisar-contratos">
                                <thead>
                                    <tr>
                                        <th>Empreendimento</th>
                                        <th>Módulo</th>
                                        <th>Lote\Quadra\Área</th>
                                        <th>Contrato</th>
                                        <th>Inscrição</th>
                                        <th>Metragem</th>
                                        <th>Vr m²</th>
                                        <th>Valor Venal</th>
                                        <th>Alíquota</th>
                                        <th>Tx Coleta Lixo</th>
                                        <th>Desconto à Vista</th>
                                        <th>Parcelamento</th>
                                        <th>Valor Anual</th>
                                        <th>Cliente</th>
                                        <th>Situação</th>
                                        <th>Data Criação</th>
                                        <th>Data Atualização</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-pesquisar-contratos-body">
                                    <tr>
                                        <td colspan="17" style="text-align: center; padding: 20px; color: #666;">
                                            Informe os critérios de pesquisa e clique em "Pesquisar" para visualizar os resultados.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            setTimeout(inicializarPesquisarContratos, 0);
            break;
            
        case 'iptu-pesquisar-importados':
            titulo = 'IPTU - Pesquisar Importados';
            conteudo = `
                <div class="page-content" id="pesquisar-importados-page">
                    <h3>🔍 Pesquisar Clientes Importados</h3>
                    <p>Pesquise e visualize informações dos clientes importados no sistema.</p>
                    
                    <div class="form-section">
                        <form id="form-pesquisar-importados">
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="pi-cpf-cnpj">CPF/CNPJ</label>
                                    <input type="text" id="pi-cpf-cnpj" name="cpf_cnpj" placeholder="Digite o CPF/CNPJ" maxlength="18">
                                </div>
                                <div class="form-group-inline" style="flex: 1;">
                                    <label for="pi-nome">Nome</label>
                                    <input type="text" id="pi-nome" name="nome" placeholder="Digite o nome do cliente">
                                </div>
                                <div class="form-group-inline">
                                    <label for="pi-cidade">Cidade</label>
                                    <input type="text" id="pi-cidade" name="cidade" placeholder="Digite a cidade">
                                </div>
                                <div class="form-group-inline">
                                    <label for="pi-uf">UF</label>
                                    <input type="text" id="pi-uf" name="uf" placeholder="UF" maxlength="2" style="text-transform: uppercase;">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="pi-tipo-cadastro">Tipo de Cadastro</label>
                                    <select id="pi-tipo-cadastro" name="tipo_cadastro">
                                        <option value="">Todos</option>
                                        <option value="Cliente">Cliente</option>
                                        <option value="Empresa">Empresa</option>
                                        <option value="Empreendimento">Empreendimento</option>
                                        <option value="Interviniente">Interviniente</option>
                                    </select>
                                </div>
                                <div class="form-group-inline">
                                    <label for="pi-ativo">Situação</label>
                                    <select id="pi-ativo" name="ativo">
                                        <option value="">Todos</option>
                                        <option value="1">Ativo</option>
                                        <option value="0">Inativo</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" id="btn-pesquisar-importados" class="btn-primary">🔍 Pesquisar</button>
                                <button type="button" id="btn-mostrar-todos-importados" class="btn-secondary">📋 Mostrar Todos</button>
                                <button type="button" id="btn-limpar-pesquisa-importados" class="btn-secondary">🗑️ Limpar</button>
                            </div>
                            
                            <div id="pi-mensagem" class="mensagem" style="margin-top: 10px; display: none;"></div>
                        </form>
                    </div>
                    
                    <div class="table-section">
                        <h4>Resultados da Pesquisa</h4>
                        <div class="table-wrapper">
                            <table class="table" id="tabela-pesquisar-importados">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>CPF/CNPJ</th>
                                        <th>Nome</th>
                                        <th>Tipo</th>
                                        <th>CEP</th>
                                        <th>Endereço</th>
                                        <th>Bairro</th>
                                        <th>Cidade</th>
                                        <th>UF</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>Ativo</th>
                                        <th>Data Criação</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-pesquisar-importados-body">
                                    <tr>
                                        <td colspan="13" style="text-align: center; padding: 20px; color: #666;">
                                            Informe os critérios de pesquisa e clique em "Pesquisar" para visualizar os resultados.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            setTimeout(inicializarPesquisarImportados, 0);
            break;
            
        case 'iptu-manutencao-iptu':
            titulo = 'IPTU - Manutenção IPTU';
            conteudo = `
                <div class="page-content" id="manutencao-iptu-page">
                    <h3>🔧 Manutenção IPTU</h3>
                    <p>Pesquise e edite os títulos da tabela de cobrança.</p>
                    
                    <div class="form-section">
                        <form id="form-manutencao-iptu">
                            <div class="form-row">
                                <div class="form-group-inline" style="flex: 0 0 100px;">
                                    <label for="mi-ano-referencia">Ano Referência</label>
                                    <input type="number" id="mi-ano-referencia" name="ano_referencia" placeholder="Ex: 2025" min="2000" max="2100" maxlength="4" style="width: 100%;">
                                </div>
                                <div class="form-group-inline">
                                    <label for="mi-empreendimento">Empreendimento</label>
                                    <select id="mi-empreendimento" name="empreendimento_id">
                                        <option value="">Selecione</option>
                                    </select>
                                </div>
                                <div class="form-group-inline">
                                    <label for="mi-modulo">Módulo</label>
                                    <select id="mi-modulo" name="modulo_id">
                                        <option value="">Selecione</option>
                                    </select>
                                </div>
                                <div class="form-group-inline" style="flex: 0 0 120px;">
                                    <label for="mi-contrato">Contrato</label>
                                    <input type="text" id="mi-contrato" name="contrato" placeholder="Número do contrato" maxlength="7" style="width: 100%;">
                                </div>
                                <div class="form-group-inline" style="flex: 0 0 200px;">
                                    <label for="mi-cliente-nome">Cliente</label>
                                    <input type="text" id="mi-cliente-nome" name="cliente_nome" placeholder="Nome do cliente" readonly disabled style="background-color: #f5f5f5; color: #666; cursor: not-allowed;">
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" id="btn-pesquisar-manutencao" class="btn-primary">🔍 Pesquisar</button>
                                <button type="button" id="btn-limpar-manutencao" class="btn-secondary">🗑️ Limpar</button>
                            </div>
                            
                            <div id="mi-mensagem" class="mensagem" style="margin-top: 10px; display: none;"></div>
                        </form>
                    </div>
                    
                    <div class="table-section">
                        <h4>Títulos Encontrados</h4>
                        <div class="table-wrapper">
                            <table class="table" id="tabela-manutencao-iptu">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Ano Ref.</th>
                                        <th>Empreendimento</th>
                                        <th>Módulo</th>
                                        <th>Contrato</th>
                                        <th>Parcela</th>
                                        <th>Vencimento</th>
                                        <th>Valor</th>
                                        <th>Observação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-manutencao-iptu-body">
                                    <tr>
                                        <td colspan="10" style="text-align: center; padding: 20px; color: #666;">
                                            Informe os critérios de pesquisa e clique em "Pesquisar" para visualizar os títulos.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Modal de Edição -->
                <div id="modal-editar-cobranca" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
                    <div style="position: relative; max-width: 800px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
                        <button type="button" id="btn-fechar-modal" style="position: absolute; top: 10px; right: 10px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 18px; line-height: 1; display: flex; align-items: center; justify-content: center;" title="Fechar (ESC)">×</button>
                        <h3 style="margin-top: 0;">✏️ Editar Título</h3>
                        <form id="form-editar-cobranca">
                            <input type="hidden" id="edit-id" name="id">
                            
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="edit-valor-mensal">Valor</label>
                                    <input type="text" id="edit-valor-mensal" name="valor_mensal" class="input-monetario" placeholder="0,00" style="width: 100%;">
                                </div>
                                <div class="form-group-inline">
                                    <label for="edit-dia-vencimento">Vencimento</label>
                                    <input type="date" id="edit-dia-vencimento" name="dia_vencimento" style="width: 100%;">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group-inline" style="flex: 1;">
                                    <label for="edit-observacao">Observação</label>
                                    <textarea id="edit-observacao" name="observacao" rows="3" style="width: 100%;"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-actions" style="margin-top: 20px;">
                                <button type="button" id="btn-salvar-edicao" class="btn-primary">💾 Salvar</button>
                                <button type="button" id="btn-cancelar-edicao" class="btn-secondary">❌ Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            setTimeout(inicializarManutencaoIptu, 0);
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
            
        case 'cobranca-consulta':
            titulo = 'Cobrança - Consulta';
            conteudo = `
                <div class="page-content" id="cobranca-consulta-page">
                    <h3>🔍 Consulta de Cobranças</h3>
                    
                    <div class="form-section" style="margin-bottom: 15px;">
                        <form id="form-consulta-cobranca">
                            <div class="form-row">
                                <div class="form-group-inline" style="flex: 0 0 200px;">
                                    <label for="cc-empreendimento">Empreendimentos</label>
                                    <select id="cc-empreendimento" name="empreendimento_id">
                                        <option value="">Selecione</option>
                                    </select>
                                </div>
                                <div class="form-group-inline" style="flex: 0 0 200px;">
                                    <label for="cc-modulo">Módulo</label>
                                    <select id="cc-modulo" name="modulo_id">
                                        <option value="">Selecione</option>
                                    </select>
                                </div>
                                <div class="form-group-inline" style="flex: 0 0 150px;">
                                    <label for="cc-contrato">Contrato</label>
                                    <input type="text" id="cc-contrato" name="contrato" placeholder="Digite o contrato" maxlength="200">
                                </div>
                                <div class="form-group-inline" style="flex: 0 0 200px;">
                                    <label for="cc-cliente">Cliente</label>
                                    <input type="text" id="cc-cliente" name="cliente" placeholder="Cliente será preenchido automaticamente" maxlength="200" disabled>
                                </div>
                                <div class="form-group-inline" style="flex: 0 0 150px;">
                                    <label for="cc-data-calculo">Dt p\\ Calculo</label>
                                    <input type="date" id="cc-data-calculo" name="data_calculo">
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div style="display: flex; gap: 20px; margin-bottom: 15px; align-items: flex-start;">
                        <div style="flex: 0 0 150px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Ordem:</label>
                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="cc-ordem" value="vencimento" checked>
                                    <span>Vencimento</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="cc-ordem" value="parcela">
                                    <span>Parcela</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="cc-ordem" value="pagamento">
                                    <span>Pagamento</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                    <input type="radio" name="cc-ordem" value="titulo">
                                    <span>Titulo</span>
                                </label>
                            </div>
                        </div>
                        
                        <div style="flex: 1;">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <button type="button" id="btn-titulos-pagos" class="btn-filter" data-filtro="pagos">Titulos Pagos</button>
                                <button type="button" id="btn-titulos-vencidos" class="btn-filter" data-filtro="vencidos">Titulos Vencidos</button>
                                <button type="button" id="btn-titulos-a-vencer" class="btn-filter" data-filtro="a-vencer">Titulos a Vencer</button>
                                <button type="button" id="btn-todos-titulos" class="btn-filter" data-filtro="todos">Todos Titulos</button>
                                <button type="button" id="btn-pesquisar-consulta" class="btn-primary">🔍 Pesquisar</button>
                                <button type="button" id="btn-imprimir-extrato" class="btn-primary">🖨️ Imprimir</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-section" style="margin-bottom: 15px;">
                        <div class="table-wrapper">
                            <table class="table" id="tabela-consulta-cobranca">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Parcela</th>
                                        <th>Vencimento</th>
                                        <th>Baixa</th>
                                        <th>Pagamento</th>
                                        <th>Pago</th>
                                        <th>Valor</th>
                                        <th>Juros</th>
                                        <th>Multa</th>
                                        <th>Valor Pago</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-consulta-cobranca-body">
                                    <tr>
                                        <td colspan="11" style="text-align: center; padding: 20px; color: #666;">
                                            Informe Empreendimento, Módulo e Contrato para pesquisar.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h4 style="margin-bottom: 10px;">Posição Financeira</h4>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">NUM.DE TITULOS</label>
                                <input type="text" id="cc-num-titulos" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Vr.TOTAL PARC.</label>
                                <input type="text" id="cc-vr-total-parc" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">JUROS\\ MULTAS PAGAS</label>
                                <input type="text" id="cc-juros-multas-pagas" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">PAGOS</label>
                                <input type="text" id="cc-pagos" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">VR.TIT.PAGOS</label>
                                <input type="text" id="cc-vr-tit-pagos" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">JUROS\\ MULTAS A PAGAR</label>
                                <input type="text" id="cc-juros-multas-a-pagar" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">VENCIDAS</label>
                                <input type="text" id="cc-vencidas" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">VR.TIT.VENCIDOS</label>
                                <input type="text" id="cc-vr-tit-vencidos" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">OUTRAS TAXAS</label>
                                <input type="text" id="cc-outras-taxas" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Á VENCER</label>
                                <input type="text" id="cc-a-vencer" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">VR.TIT.A VENCER</label>
                                <input type="text" id="cc-vr-tit-a-vencer" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: bold;">VR.IPTU</label>
                                <input type="text" id="cc-vr-iptu" readonly style="width: 100%; padding: 5px; background: #f5f5f5;">
                            </div>
                        </div>
                    </div>
                    
                    <div id="cc-mensagem" class="mensagem" style="margin-top: 10px; display: none;"></div>
                </div>
            `;
            setTimeout(inicializarConsultaCobranca, 0);
            break;
            
        case 'cobranca-baixa-manual':
            titulo = 'Cobrança - Baixa Manual';
            conteudo = `
                <div class="page-content" id="cobranca-baixa-manual-page" style="background: #f5f5f5; padding: 20px;">
                    <div style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 20px; color: #2d8659;">✏️ Baixa Manual de Cobranças</h3>
                        
                        <!-- Top Header: Empreendimentos, Módulo, Contrato -->
                        <div style="display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                            <div style="flex: 1; min-width: 200px;">
                                <label for="bm-empreendimento" style="display: block; margin-bottom: 5px; font-weight: 500;">Empreendimentos</label>
                                <select id="bm-empreendimento" name="empreendimento_id" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <label for="bm-modulo" style="display: block; margin-bottom: 5px; font-weight: 500;">Módulo</label>
                                <select id="bm-modulo" name="modulo_id" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                            <div style="flex: 1; min-width: 200px;">
                                <label for="bm-contrato" style="display: block; margin-bottom: 5px; font-weight: 500;">Contrato</label>
                                <div style="display: flex; gap: 5px;">
                                    <input type="text" id="bm-contrato" name="contrato" placeholder="Digite o número do contrato" style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                                    <button type="button" id="btn-pesquisar-contrato-bm" class="btn btn-primary" title="Pesquisar contrato" style="padding: 8px 15px; white-space: nowrap;">🔍</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Radio buttons: Baixar Parcela / Estorno -->
                        <div style="background: #e0e0e0; padding: 10px; margin-bottom: 20px; border-top: 2px solid #999; border-bottom: 2px solid #999;">
                            <div style="display: flex; gap: 20px;">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="radio" id="bm-tipo-baixar" name="bm-tipo-operacao-radio" value="baixar" checked style="margin-right: 8px;">
                                    <span>Baixar Parcela</span>
                                </label>
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="radio" id="bm-tipo-estornar" name="bm-tipo-operacao-radio" value="estornar" style="margin-right: 8px;">
                                    <span>Estorno</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Main Form Area -->
                        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                            <!-- Left Column -->
                            <div style="flex: 1; background: #f9f9f9; padding: 15px; border-radius: 4px;">
                                <div style="margin-bottom: 10px;">
                                    <label for="bm-num-titulo" style="display: block; margin-bottom: 5px; font-weight: 500;">Nº do Titulo</label>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <input type="text" id="bm-num-titulo" name="num_titulo" style="flex: 1; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                        <span id="bm-titulo-msg-erro" style="color: #dc3545; font-size: 12px; font-weight: 500; display: none;"></span>
                                    </div>
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <label for="bm-dt-pagto" style="display: block; margin-bottom: 5px; font-weight: 500;">Dt.Pagto</label>
                                    <input type="date" id="bm-dt-pagto" name="dt_pagto" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <label for="bm-data-baixa" style="display: block; margin-bottom: 5px; font-weight: 500;">Data Baixa</label>
                                    <input type="date" id="bm-data-baixa" name="data_baixa" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <label for="bm-valor-parcela" style="display: block; margin-bottom: 5px; font-weight: 500;">Valor Parc.</label>
                                    <input type="text" id="bm-valor-parcela" name="valor_parcela" placeholder="0,00" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <label for="bm-multa" style="display: block; margin-bottom: 5px; font-weight: 500;">Multa</label>
                                    <input type="text" id="bm-multa" name="multa" placeholder="0,00" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <label for="bm-juros" style="display: block; margin-bottom: 5px; font-weight: 500;">Juros</label>
                                    <input type="text" id="bm-juros" name="juros" placeholder="0,00" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <label for="bm-tarifa-bancaria" style="display: block; margin-bottom: 5px; font-weight: 500;">Tarifa.Bancaria</label>
                                    <input type="text" id="bm-tarifa-bancaria" name="tarifa_bancaria" placeholder="0,00" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <label for="bm-desconto" style="display: block; margin-bottom: 5px; font-weight: 500;">Desconto</label>
                                    <input type="text" id="bm-desconto" name="desconto" placeholder="0,00" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                </div>
                                <hr style="border: 1px solid #ccc; margin: 15px 0;">
                                <div>
                                    <label for="bm-valor-a-pagar" style="display: block; margin-bottom: 5px; font-weight: 500;">Valor a Pagar</label>
                                    <input type="text" id="bm-valor-a-pagar" name="valor_a_pagar" placeholder="0,00" readonly style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px; background: #e9e9e9; font-weight: bold;">
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div style="flex: 1; background: #f9f9f9; padding: 15px; border-radius: 4px;">
                                <div style="margin-bottom: 10px;">
                                    <label for="bm-forma-pagto" style="display: block; margin-bottom: 5px; font-weight: 500;">Forma Pagto</label>
                                    <select id="bm-forma-pagto" name="forma_pagto" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                        <option value="">Selecione...</option>
                                        <option value="Dinheiro">Dinheiro</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Transferência">Transferência</option>
                                        <option value="Boleto">Boleto</option>
                                        <option value="Cartão">Cartão</option>
                                        <option value="PIX">PIX</option>
                                    </select>
                                </div>
                                <div style="margin-bottom: 10px;">
                                    <label for="bm-local-pg" style="display: block; margin-bottom: 5px; font-weight: 500;">Local Pg.</label>
                                    <select id="bm-local-pg" name="local_pg" style="width: 100%; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                                        <option value="">Selecione...</option>
                                        <option value="Balcão">Balcão</option>
                                        <option value="Online">Online</option>
                                        <option value="Bancário">Bancário</option>
                                    </select>
                                </div>
                                <hr style="border: 1px solid #ccc; margin: 15px 0;">
                                <div style="margin-top: 15px;">
                                    <button type="button" id="btn-salvar-baixa-manual" class="btn btn-success" style="width: 100%; padding: 10px; font-weight: 500;">💾 Salvar</button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Observation Field -->
                        <div style="margin-bottom: 20px;">
                            <label for="bm-observacao" style="display: block; margin-bottom: 5px; font-weight: 500;">Observação</label>
                            <textarea id="bm-observacao" name="observacao" rows="3" placeholder="Observações sobre a operação" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: vertical;"></textarea>
                        </div>
                        
                        <!-- Grid/Table -->
                        <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                            <div id="bm-mensagem" class="mensagem" style="margin-bottom: 10px; display: none;"></div>
                            <div class="table-wrapper" style="max-height: 400px; overflow-y: auto;">
                                <table id="table-baixa-manual" class="data-table" style="width: 100%; border-collapse: collapse;">
                                    <thead style="background: #2d8659; color: white; position: sticky; top: 0;">
                                        <tr>
                                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Título</th>
                                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Cliente</th>
                                            <th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Valor Mensal</th>
                                            <th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Multas</th>
                                            <th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Juros</th>
                                            <th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Valor Total</th>
                                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Data Vencimento</th>
                                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Data Pagamento</th>
                                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Data da Baixa</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-baixa-manual">
                                        <tr>
                                            <td colspan="9" style="text-align: center; padding: 20px; border: 1px solid #ddd;">Pesquise um contrato para exibir as parcelas.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            setTimeout(inicializarBaixaManual, 0);
            break;
            
        case 'cobranca-automatica':
            titulo = 'Cobrança - Cobrança Automática';
            conteudo = `
                <div class="page-content" id="cobranca-automatica-page" style="background: #f5f5f5; padding: 20px;">
                    <div style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 20px; color: #2d8659;">⚙️ Cobrança Automática</h3>
                        
                        <!-- Seção Empreendimento -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Empreendimento</label>
                            <div style="display: flex; gap: 10px; align-items: flex-end;">
                                <div style="flex: 1;">
                                    <select id="ca-empreendimento" name="empreendimento_id" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                                        <option value="">Selecione o empreendimento...</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Informações do Banco -->
                        <div id="ca-info-banco" style="margin-bottom: 20px; padding: 15px; background: #f0f7f4; border-left: 4px solid #2d8659; border-radius: 4px; display: none;">
                            <h4 style="margin: 0 0 10px 0; color: #2d8659; font-size: 16px;">🏦 Informações do Banco</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Nome do Banco:</label>
                                    <div id="ca-banco-nome" style="padding: 8px; background: white; border-radius: 4px; color: #555; font-size: 14px;">-</div>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Agência:</label>
                                    <div id="ca-banco-agencia" style="padding: 8px; background: white; border-radius: 4px; color: #555; font-size: 14px;">-</div>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Conta Corrente:</label>
                                    <div id="ca-banco-conta" style="padding: 8px; background: white; border-radius: 4px; color: #555; font-size: 14px;">-</div>
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Caminho da Remessa:</label>
                                    <div id="ca-caminho-remessa" style="padding: 8px; background: white; border-radius: 4px; color: #555; font-size: 14px; word-break: break-all;">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção Periodo de Referencia -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Periodo de Referencia</label>
                            <div style="display: flex; gap: 10px;">
                                <div style="flex: 1;">
                                    <label style="display: block; margin-bottom: 5px; font-size: 12px; color: #666;">Data Início</label>
                                    <input type="date" id="ca-periodo-inicio" name="periodo_inicio" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                                </div>
                                <div style="flex: 1;">
                                    <label style="display: block; margin-bottom: 5px; font-size: 12px; color: #666;">Data Fim</label>
                                    <input type="date" id="ca-periodo-fim" name="periodo_fim" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção Por Título e Por Contrato -->
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; gap: 10px;">
                                <div style="flex: 1;">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Por Título</label>
                                    <input type="text" id="ca-titulo" name="titulo" placeholder="Digite o título" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                                </div>
                                <div style="flex: 1;">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Por Contrato</label>
                                    <input type="text" id="ca-contrato" name="contrato" placeholder="Digite o contrato" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Checkbox Remissão dos Boletos -->
                        <div style="margin-top: 20px;">
                            <label style="display: flex; align-items: center; cursor: pointer; font-size: 14px;">
                                <input type="checkbox" id="ca-remissao-boletos" name="remissao_boletos" value="1" style="margin-right: 8px; width: 18px; height: 18px; cursor: pointer;">
                                <span>Remissão dos Boletos</span>
                            </label>
                        </div>
                        
                        <!-- Botão Pesquisar -->
                        <div style="margin-top: 20px;">
                            <button type="button" id="btn-pesquisar-titulos" class="btn-primary" style="padding: 10px 20px;">
                                🔍 Pesquisar Títulos
                            </button>
                        </div>
                        
                        <!-- Grid de Títulos -->
                        <div style="margin-top: 30px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h4 style="margin: 0; color: #2d8659;">Títulos que serão enviados</h4>
                                <div style="display: flex; gap: 10px;">
                                    <button type="button" id="btn-selecionar-todos" class="btn-secondary" style="padding: 8px 16px; font-size: 12px; background-color: #d32f2f; color: white; border-color: #d32f2f; cursor: pointer;" onmouseover="this.style.backgroundColor='#b71c1c'" onmouseout="this.style.backgroundColor='#d32f2f'">
                                        Selecionar Todos
                                    </button>
                                    <button type="button" id="btn-deselecionar-todos" class="btn-secondary" style="padding: 8px 16px; font-size: 12px; background-color: #d32f2f; color: white; border-color: #d32f2f; cursor: pointer;" onmouseover="this.style.backgroundColor='#b71c1c'" onmouseout="this.style.backgroundColor='#d32f2f'">
                                        Deselecionar Todos
                                    </button>
                                </div>
                            </div>
                            <div class="table-wrapper" style="max-height: 500px; overflow-y: auto;">
                                <table class="table" id="tabela-cobranca-automatica">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" id="check-todos-titulos" style="cursor: pointer;">
                                            </th>
                                            <th>Título</th>
                                            <th>Cliente</th>
                                            <th>Parcela</th>
                                            <th>Vencimento</th>
                                            <th style="text-align: right;">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabela-cobranca-automatica-body">
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
                                                Selecione o empreendimento e o período de referência, depois clique em "Pesquisar Títulos".
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div style="margin-top: 15px; display: flex; justify-content: flex-end;">
                                <button type="button" id="btn-processar-cobranca" class="btn-primary" style="padding: 10px 30px; font-size: 16px;" disabled>
                                    ⚙️ Processar
                                </button>
                            </div>
                        </div>
                        
                        <!-- Mensagens -->
                        <div id="ca-mensagem" class="mensagem" style="margin-top: 15px; display: none;"></div>
                    </div>
                </div>
            `;
            setTimeout(inicializarCobrancaAutomatica, 0);
            break;
            
        case 'cobranca-retorno-bancario':
            titulo = 'Cobrança - Retorno Bancário';
            conteudo = `
                <div class="page-content" id="cobranca-retorno-bancario-page" style="background: #f5f5f5; padding: 20px;">
                    <div style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <h3 style="margin-bottom: 20px; color: #2d8659;">🏦 Retorno Bancário</h3>
                        
                        <!-- Seção Upload de Arquivo -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Arquivo de Retorno</label>
                            <div style="display: flex; gap: 10px; align-items: flex-end;">
                                <div style="flex: 1;">
                                    <input type="file" id="rb-arquivo" name="arquivo" accept=".ret,.txt,.RET,.TXT" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                                    <small style="color: #666; margin-top: 5px; display: block;">Formatos aceitos: .ret, .txt</small>
                                </div>
                                <button type="button" id="btn-processar-retorno" class="btn-primary" style="padding: 10px 20px; white-space: nowrap;">
                                    📤 Processar Arquivo
                                </button>
                            </div>
                        </div>
                        
                        <!-- Seção Banco -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Banco</label>
                            <select id="rb-banco" name="banco_id" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                                <option value="">Selecione o banco...</option>
                            </select>
                        </div>
                        
                        <!-- Seção Data de Movimento -->
                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">Data de Movimento</label>
                            <input type="date" id="rb-data-movimento" name="data_movimento" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                        </div>
                        
                        <!-- Tabela de Resultados -->
                        <div style="margin-top: 30px;">
                            <h4 style="margin-bottom: 15px; color: #2d8659;">Resultado do Processamento</h4>
                            <div class="table-wrapper" style="max-height: 500px; overflow-y: auto;">
                                <table class="table" id="tabela-retorno-bancario">
                                    <thead>
                                        <tr>
                                            <th>Linha</th>
                                            <th>Tipo</th>
                                            <th>Nosso Número</th>
                                            <th>Valor</th>
                                            <th>Data Pagamento</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabela-retorno-bancario-body">
                                        <tr>
                                            <td colspan="6" style="text-align: center; padding: 20px; color: #666;">
                                                Nenhum arquivo processado ainda.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Mensagens -->
                        <div id="rb-mensagem" class="mensagem" style="margin-top: 15px; display: none;"></div>
                    </div>
                </div>
            `;
            setTimeout(inicializarRetornoBancario, 0);
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
            
        case 'cadastro-contratos':
            titulo = 'Cadastro - Contratos';
            conteudo = `
                <div class="page-content" id="contratos-page">
                    <h3>📋 Cadastro de Contratos</h3>
                    
                    <form id="form-contrato" class="crud-form">
                        <!-- Dados Loteamento -->
                        <div class="form-section">
                            <h4>Dados Loteamento</h4>
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="ct-empreendimento"><strong>Empreendimento</strong></label>
                                    <select id="ct-empreendimento" name="empreendimento_id" class="required" title="Selecione o empreendimento">
                                        <option value="" style="color: #000;">Selecione o empreendimento</option>
                                    </select>
                                </div>
                                <div class="form-group-inline">
                                    <label for="ct-modulo"><strong>Módulo</strong></label>
                                    <select id="ct-modulo" name="modulo_id" class="required" title="Selecione o módulo">
                                        <option value="" style="color: #000;">Selecione o módulo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="ct-cpf-cnpj"><strong>CPF/CNPJ</strong></label>
                                    <input type="text" id="ct-cpf-cnpj" name="cpf_cnpj" placeholder="CPF/CNPJ" maxlength="18" style="color: #000;">
                                </div>
                                <div class="form-group-inline">
                                    <label for="ct-cliente">Cliente</label>
                                    <input type="text" id="ct-cliente" name="cliente_nome" placeholder="Cliente" readonly style="background-color: #f5f5f5; color: #000;">
                                    <input type="hidden" id="ct-cliente-id" name="cliente_id">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group-inline" style="flex: 0 0 150px;">
                                    <label for="ct-contrato"><strong>Numero de contratos</strong></label>
                                    <input type="text" id="ct-contrato" name="contrato" placeholder="Contrato" class="required" maxlength="7" required style="color: #000;">
                                    <small id="ct-total-contratos" style="display: block; margin-top: 5px; color: #666; font-size: 12px;">Total: <span id="ct-contador-contratos">0</span></small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dados Lote -->
                        <div class="form-section">
                            <h4>Dados Lote</h4>
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="ct-area"><strong>Lote\\Qda ou Area</strong></label>
                                    <input type="text" id="ct-area" name="area" placeholder="Lote/Qda ou Area" class="required" maxlength="200" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="ct-metragem"><strong>Metragem</strong></label>
                                    <input type="text" id="ct-metragem" name="metragem" placeholder="Metragem" class="numeric-input required" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="ct-vrm2"><strong>Vr m²</strong></label>
                                    <input type="text" id="ct-vrm2" name="vrm2" placeholder="Vr m²" class="currency-input required" required>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Dados do IPTU -->
                        <div class="form-section">
                            <h4>Dados do IPTU</h4>
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="ct-inscricao"><strong>Inscrição</strong></label>
                                    <input type="text" id="ct-inscricao" name="inscricao" placeholder="Inscrição" class="required" maxlength="100" required style="color: #000;">
                                </div>
                                <div class="form-group-inline">
                                    <label for="ct-valor-venal"><strong>Valor Venal</strong></label>
                                    <input type="text" id="ct-valor-venal" name="valor_venal" placeholder="Valor Venal" class="currency-input required" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="ct-aliquota"><strong>Alíquota (%)</strong></label>
                                    <input type="text" id="ct-aliquota" name="aliquota" placeholder="Alíquota (%)" class="numeric-input required" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="ct-tx-coleta-lixo"><strong>Tx Coleta Lixo</strong></label>
                                    <input type="text" id="ct-tx-coleta-lixo" name="tx_coleta_lixo" placeholder="Tx Coleta Lixo" class="currency-input required" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="ct-desconto-vista"><strong>Valor c\\Desc.</strong></label>
                                    <input type="text" id="ct-desconto-vista" name="desconto_a_vista" placeholder="Valor c\\Desc." class="currency-input required" required>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="ct-valor-anual"><strong>Valor Anual</strong></label>
                                    <input type="text" id="ct-valor-anual" name="valor_anual" placeholder="Valor Anual" class="currency-input required" required>
                                </div>
                                <div class="form-group-inline">
                                    <label for="ct-parcelamento"><strong>Parcelamento</strong></label>
                                    <input type="number" id="ct-parcelamento" name="parcelamento" placeholder="Parcelamento" class="required" min="1" required style="color: #000;">
                                </div>
                                <div class="form-group-inline">
                                    <label for="ct-valor-mensal">Valor Mensal</label>
                                    <input type="text" id="ct-valor-mensal" name="valor_mensal" placeholder="Valor Mensal" class="currency-input" readonly style="background-color: #f5f5f5;">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group-inline">
                                    <label for="ct-obs"><strong>Observação</strong></label>
                                    <textarea id="ct-obs" name="obs" class="required" rows="4" placeholder="Observação" required style="resize: vertical; color: #000;"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" id="btn-novo-contrato" class="btn btn-primary">Novo</button>
                            <button type="submit" id="btn-salvar-contrato" class="btn btn-success">Salvar</button>
                        </div>
                    </form>
                    
                    <div class="table-section">
                        <h4>Pesquisa de Contratos</h4>
                        <div class="form-row" style="margin-bottom: 10px; align-items: flex-end;">
                            <div class="form-group-inline" style="flex: 1;">
                                <label for="ct-busca">Pesquisar por Contrato, Empreendimento ou Módulo</label>
                                <input type="text" id="ct-busca" placeholder="Digite parte do contrato, empreendimento ou módulo">
                            </div>
                            <div class="form-actions" style="margin-top: 0; margin-bottom: 0;">
                                <button type="button" class="btn-primary" id="btn-buscar-contrato">Pesquisar</button>
                                <button type="button" class="btn-secondary" id="btn-limpar-busca-contrato">Limpar</button>
                            </div>
                        </div>
                        
                        <div class="table-wrapper">
                            <table id="table-contratos" class="data-table">
                            <thead>
                                <tr>
                                    <th>Contrato</th>
                                    <th>Empreendimento</th>
                                    <th>Módulo</th>
                                    <th>Cliente</th>
                                    <th>Inscrição</th>
                                    <th>Valor Venal</th>
                                    <th>Valor Mensal</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-contratos">
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 20px;">Nenhum contrato encontrado.</td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            `;
            setTimeout(inicializarCadastroContratos, 0);
            break;
            
        default:
            titulo = 'Página não encontrada';
            conteudo = '<div class="page-content"><p>Página não encontrada.</p></div>';
    }
    
    pageTitle.textContent = titulo;
    contentBody.innerHTML = conteudo;
}

// ========== Importar Clientes ==========
function inicializarImportarClientes() {
    const btnBuscarDiretorio = document.getElementById('btn-buscar-diretorio');
    const selectArquivo = document.getElementById('ic-arquivo-selecionado');
    const inputDiretorio = document.getElementById('ic-diretorio-arquivo');
    const btnPreview = document.getElementById('btn-preview-arquivo');
    const btnImportar = document.getElementById('btn-importar-arquivo');
    const btnHelp = document.getElementById('btn-help-importar');
    const helpSection = document.getElementById('ic-help-section');
    const btnFecharHelp = document.getElementById('btn-fechar-help');
    const listaDiretorios = document.getElementById('ic-lista-diretorios');
    
    // Campo de diretório inicia vazio
    
    // Carregar lista de diretórios ao inicializar
    carregarListaDiretorios();
    
    // Event listener para mostrar/ocultar lista ao focar no input
    if (inputDiretorio && listaDiretorios) {
        inputDiretorio.addEventListener('focus', function() {
            if (listaDiretorios.innerHTML.trim() !== '') {
                listaDiretorios.style.display = 'block';
            }
        });
        
        inputDiretorio.addEventListener('input', function() {
            carregarListaDiretorios();
        });
        
        // Carregar arquivos quando o usuário pressionar Enter ou sair do campo
        inputDiretorio.addEventListener('blur', function() {
            const diretorio = inputDiretorio.value.trim();
            if (diretorio) {
                // Pequeno delay para permitir clique na lista de diretórios
                setTimeout(() => {
                    carregarArquivosServidor(diretorio);
                }, 200);
            }
        });
        
        inputDiretorio.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const diretorio = inputDiretorio.value.trim();
                if (diretorio) {
                    carregarArquivosServidor(diretorio);
                    listaDiretorios.style.display = 'none';
                }
            }
        });
        
        // Fechar lista ao clicar fora
        document.addEventListener('click', function(e) {
            if (inputDiretorio && listaDiretorios && 
                !inputDiretorio.contains(e.target) && 
                !listaDiretorios.contains(e.target) &&
                btnBuscarDiretorio && !btnBuscarDiretorio.contains(e.target)) {
                listaDiretorios.style.display = 'none';
            }
        });
    }
    
    function carregarListaDiretorios() {
        if (!listaDiretorios) return;
        
        fetch('/SISIPTU/php/importar_clientes_api.php?action=listar-diretorios')
            .then(r => r.json())
            .then(data => {
                if (!data.sucesso) {
                    listaDiretorios.innerHTML = '';
                    listaDiretorios.style.display = 'none';
                    return;
                }
                
                const diretorios = data.diretorios || [];
                if (diretorios.length === 0) {
                    listaDiretorios.innerHTML = '';
                    listaDiretorios.style.display = 'none';
                    return;
                }
                
                // Filtrar diretórios baseado no que o usuário digitou
                const filtro = inputDiretorio ? inputDiretorio.value.trim().toLowerCase() : '';
                const diretoriosFiltrados = diretorios.filter(dir => {
                    if (!filtro) return true;
                    return dir.toLowerCase().includes(filtro);
                });
                
                if (diretoriosFiltrados.length === 0) {
                    listaDiretorios.innerHTML = '';
                    listaDiretorios.style.display = 'none';
                    return;
                }
                
                listaDiretorios.innerHTML = diretoriosFiltrados.map(dir => {
                    return `
                        <div class="ic-item-diretorio" style="padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 8px; transition: background-color 0.2s;" 
                             onmouseover="this.style.backgroundColor='#e3f2fd'; this.style.color='#1976d2';" 
                             onmouseout="this.style.backgroundColor=''; this.style.color='';"
                             onclick="selecionarDiretorio('${dir.replace(/'/g, "\\'")}')">
                            <span style="font-size: 16px;">📁</span>
                            <span style="flex: 1;">${dir}/</span>
                        </div>
                    `;
                }).join('');
                
                // Mostrar lista se o input estiver focado
                if (inputDiretorio && document.activeElement === inputDiretorio) {
                    listaDiretorios.style.display = 'block';
                }
            })
            .catch(err => {
                console.error('Erro ao carregar diretórios:', err);
                listaDiretorios.innerHTML = '';
                listaDiretorios.style.display = 'none';
            });
    }
    
    // Função global para selecionar diretório
    window.selecionarDiretorio = function(diretorio) {
        if (inputDiretorio) {
            inputDiretorio.value = diretorio;
        }
        if (listaDiretorios) {
            listaDiretorios.style.display = 'none';
        }
        // Carregar arquivos do diretório selecionado
        carregarArquivosServidor(diretorio);
    };
    
    // Botão Ajuda
    if (btnHelp && helpSection) {
        btnHelp.addEventListener('click', function() {
            if (helpSection.style.display === 'none') {
                helpSection.style.display = 'block';
                btnHelp.textContent = '❌ Ocultar Ajuda';
            } else {
                helpSection.style.display = 'none';
                btnHelp.textContent = '❓ Ajuda - Formato do Arquivo';
            }
        });
    }
    
    // Botão Fechar Ajuda
    if (btnFecharHelp && helpSection && btnHelp) {
        btnFecharHelp.addEventListener('click', function() {
            helpSection.style.display = 'none';
            btnHelp.textContent = '❓ Ajuda - Formato do Arquivo';
        });
    }
    
    // Botão Buscar Diretório
    if (btnBuscarDiretorio) {
        btnBuscarDiretorio.addEventListener('click', function() {
            let diretorio = inputDiretorio ? inputDiretorio.value.trim() : '';
            
            if (!diretorio) {
                alert('Por favor, informe o diretório ou selecione um da lista.');
                return;
            }
            
            carregarArquivosServidor(diretorio);
        });
    }
    
    // Botão Visualizar Arquivo
    if (btnPreview) {
        btnPreview.addEventListener('click', function() {
            const arquivo = selectArquivo ? selectArquivo.value : '';
            const diretorio = inputDiretorio ? inputDiretorio.value.trim() : '';
            const delimitador = document.getElementById('ic-delimitador') ? document.getElementById('ic-delimitador').value : ',';
            const primeiraLinhaCabecalho = document.getElementById('ic-primeira-linha-cabecalho') ? document.getElementById('ic-primeira-linha-cabecalho').checked : true;
            
            if (!arquivo || !diretorio) {
                alert('Por favor, selecione um arquivo e informe o diretório.');
                return;
            }
            
            visualizarArquivo(diretorio, arquivo, delimitador, primeiraLinhaCabecalho);
        });
    }
    
    // Botão Importar
    if (btnImportar) {
        btnImportar.addEventListener('click', function() {
            const arquivo = selectArquivo ? selectArquivo.value : '';
            const diretorio = inputDiretorio ? inputDiretorio.value.trim() : '';
            const delimitador = document.getElementById('ic-delimitador') ? document.getElementById('ic-delimitador').value : ',';
            const primeiraLinhaCabecalho = document.getElementById('ic-primeira-linha-cabecalho') ? document.getElementById('ic-primeira-linha-cabecalho').checked : true;
            
            if (!arquivo || !diretorio) {
                alert('Por favor, selecione um arquivo e informe o diretório.');
                return;
            }
            
            importarClientes(diretorio, arquivo, delimitador, primeiraLinhaCabecalho);
        });
    }
}

// ========== Pesquisar Importados ==========
function inicializarPesquisarImportados() {
    const form = document.getElementById('form-pesquisar-importados');
    const btnPesquisar = document.getElementById('btn-pesquisar-importados');
    const btnMostrarTodos = document.getElementById('btn-mostrar-todos-importados');
    const btnLimpar = document.getElementById('btn-limpar-pesquisa-importados');
    
    if (!form || !btnPesquisar || !btnLimpar) return;
    
    // Botão Pesquisar
    btnPesquisar.addEventListener('click', function() {
        pesquisarClientesImportados();
    });
    
    // Botão Mostrar Todos
    if (btnMostrarTodos) {
        btnMostrarTodos.addEventListener('click', function() {
            mostrarTodosClientesImportados();
        });
    }
    
    // Botão Limpar
    btnLimpar.addEventListener('click', function() {
        form.reset();
        const tabelaBody = document.getElementById('tabela-pesquisar-importados-body');
        if (tabelaBody) {
            tabelaBody.innerHTML = '<tr><td colspan="13" style="text-align: center; padding: 20px; color: #666;">Informe os critérios de pesquisa e clique em "Pesquisar" para visualizar os resultados.</td></tr>';
        }
        const mensagem = document.getElementById('pi-mensagem');
        if (mensagem) {
            mensagem.style.display = 'none';
            mensagem.textContent = '';
        }
    });
    
    // Permitir pesquisa ao pressionar Enter
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        pesquisarClientesImportados();
    });
    
    // Formatação de CPF/CNPJ
    const cpfCnpjInput = document.getElementById('pi-cpf-cnpj');
    if (cpfCnpjInput) {
        cpfCnpjInput.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, '');
            if (valor.length <= 11) {
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                valor = valor.replace(/(\d{2})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d)/, '$1/$2');
                valor = valor.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
            }
            e.target.value = valor;
        });
    }
    
    // Formatação de UF (maiúsculas)
    const ufInput = document.getElementById('pi-uf');
    if (ufInput) {
        ufInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
    }
}

function pesquisarClientesImportados() {
    const form = document.getElementById('form-pesquisar-importados');
    const tabelaBody = document.getElementById('tabela-pesquisar-importados-body');
    const mensagem = document.getElementById('pi-mensagem');
    
    if (!form || !tabelaBody) return;
    
    // Coletar filtros
    const filtros = {
        cpf_cnpj: document.getElementById('pi-cpf-cnpj') ? document.getElementById('pi-cpf-cnpj').value.trim() : '',
        nome: document.getElementById('pi-nome') ? document.getElementById('pi-nome').value.trim() : '',
        cidade: document.getElementById('pi-cidade') ? document.getElementById('pi-cidade').value.trim() : '',
        uf: document.getElementById('pi-uf') ? document.getElementById('pi-uf').value.trim().toUpperCase() : '',
        tipo_cadastro: document.getElementById('pi-tipo-cadastro') ? document.getElementById('pi-tipo-cadastro').value : '',
        ativo: document.getElementById('pi-ativo') ? document.getElementById('pi-ativo').value : ''
    };
    
    // Verificar se pelo menos um filtro foi preenchido
    const temFiltro = Object.values(filtros).some(v => v !== '');
    
    if (!temFiltro) {
        mostrarMensagemPesquisa('Por favor, informe pelo menos um critério de pesquisa.', 'erro');
        return;
    }
    
    tabelaBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px;">Carregando...</td></tr>';
    
    // Construir URL com filtros
    const params = new URLSearchParams({ action: 'pesquisar' });
    Object.keys(filtros).forEach(key => {
        if (filtros[key] !== '') {
            params.append(key, filtros[key]);
        }
    });
    
    buscarClientes(params);
}

function mostrarTodosClientesImportados() {
    const tabelaBody = document.getElementById('tabela-pesquisar-importados-body');
    if (!tabelaBody) return;
    
    tabelaBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px;">Carregando...</td></tr>';
    
    // Construir URL sem filtros
    const params = new URLSearchParams({ action: 'listar-todos' });
    
    buscarClientes(params);
}

function buscarClientes(params) {
    const tabelaBody = document.getElementById('tabela-pesquisar-importados-body');
    const mensagem = document.getElementById('pi-mensagem');
    
    fetch(`/SISIPTU/php/pesquisar_importados_api.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                tabelaBody.innerHTML = `<tr><td colspan="13" style="text-align: center; padding: 20px; color: #d32f2f;">${data.mensagem || 'Erro ao buscar clientes.'}</td></tr>`;
                mostrarMensagemPesquisa(data.mensagem || 'Erro ao buscar clientes.', 'erro');
                return;
            }
            
            const clientes = data.clientes || [];
            if (clientes.length === 0) {
                tabelaBody.innerHTML = '<tr><td colspan="13" style="text-align: center; padding: 20px; color: #666;">Nenhum cliente encontrado.</td></tr>';
                mostrarMensagemPesquisa('Nenhum cliente encontrado.', 'info');
                return;
            }
            
            // Renderizar resultados
            tabelaBody.innerHTML = clientes.map(c => {
                const dataCriacao = formatarData(c.data_criacao);
                return `
                    <tr>
                        <td>${c.id || ''}</td>
                        <td>${c.cpf_cnpj || ''}</td>
                        <td>${c.nome || ''}</td>
                        <td>${c.tipo_cadastro || ''}</td>
                        <td>${c.cep || ''}</td>
                        <td>${c.endereco || ''}</td>
                        <td>${c.bairro || ''}</td>
                        <td>${c.cidade || ''}</td>
                        <td>${c.uf || ''}</td>
                        <td>${c.email || ''}</td>
                        <td>${c.tel_celular1 || c.tel_comercial || c.tel_residencial || ''}</td>
                        <td>${c.ativo ? 'Sim' : 'Não'}</td>
                        <td>${dataCriacao}</td>
                    </tr>
                `;
            }).join('');
            
            mostrarMensagemPesquisa(`${clientes.length} cliente(s) encontrado(s).`, 'sucesso');
        })
        .catch(err => {
            console.error('Erro ao buscar clientes:', err);
            tabelaBody.innerHTML = '<tr><td colspan="13" style="text-align: center; padding: 20px; color: #d32f2f;">Erro ao buscar clientes. Tente novamente.</td></tr>';
            mostrarMensagemPesquisa('Erro ao buscar clientes. Tente novamente.', 'erro');
        });
}

function mostrarMensagemPesquisa(texto, tipo) {
    const mensagem = document.getElementById('pi-mensagem');
    if (!mensagem) return;
    
    mensagem.textContent = texto;
    mensagem.className = 'mensagem';
    
    if (tipo === 'sucesso') {
        mensagem.style.backgroundColor = '#d4edda';
        mensagem.style.color = '#155724';
        mensagem.style.borderColor = '#c3e6cb';
    } else if (tipo === 'erro') {
        mensagem.style.backgroundColor = '#f8d7da';
        mensagem.style.color = '#721c24';
        mensagem.style.borderColor = '#f5c6cb';
    } else {
        mensagem.style.backgroundColor = '#d1ecf1';
        mensagem.style.color = '#0c5460';
        mensagem.style.borderColor = '#bee5eb';
    }
    
    mensagem.style.display = 'block';
    mensagem.style.padding = '10px';
    mensagem.style.borderRadius = '4px';
    mensagem.style.border = '1px solid';
    mensagem.style.marginTop = '10px';
}

// ========== Pesquisar Contratos ==========
function inicializarPesquisarContratos() {
    const form = document.getElementById('form-pesquisar-contratos');
    const btnPesquisar = document.getElementById('btn-pesquisar-contratos');
    const btnMostrarTodos = document.getElementById('btn-mostrar-todos-contratos');
    const btnLimpar = document.getElementById('btn-limpar-pesquisa-contratos');
    const selectEmpreendimento = document.getElementById('pc-empreendimento');
    const selectModulo = document.getElementById('pc-modulo');
    
    if (!form || !btnPesquisar || !btnLimpar) return;
    
    // Carregar empreendimentos
    if (selectEmpreendimento) {
        fetch('/SISIPTU/php/empreendimentos_api.php?action=list')
            .then(r => r.json())
            .then(data => {
                if (data.sucesso && data.empreendimentos) {
                    data.empreendimentos.forEach(emp => {
                        const opt = document.createElement('option');
                        opt.value = emp.id;
                        opt.textContent = emp.nome;
                        selectEmpreendimento.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Erro ao carregar empreendimentos:', err));
    }
    
    // Carregar módulos quando empreendimento for selecionado
    if (selectEmpreendimento && selectModulo) {
        selectEmpreendimento.addEventListener('change', function() {
            const empId = this.value;
            selectModulo.innerHTML = '<option value="">Todos</option>';
            
            if (empId) {
                fetch(`/SISIPTU/php/modulos_api.php?action=list&empreendimento_id=${empId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.sucesso && data.modulos) {
                            data.modulos.forEach(mod => {
                                const opt = document.createElement('option');
                                opt.value = mod.id;
                                opt.textContent = mod.nome;
                                selectModulo.appendChild(opt);
                            });
                        }
                    })
                    .catch(err => console.error('Erro ao carregar módulos:', err));
            }
        });
    }
    
    // Botão Pesquisar
    btnPesquisar.addEventListener('click', function() {
        pesquisarContratos();
    });
    
    // Botão Mostrar Todos
    if (btnMostrarTodos) {
        btnMostrarTodos.addEventListener('click', function() {
            mostrarTodosContratos();
        });
    }
    
    // Botão Limpar
    btnLimpar.addEventListener('click', function() {
        form.reset();
        if (selectModulo) {
            selectModulo.innerHTML = '<option value="">Todos</option>';
        }
        const tabelaBody = document.getElementById('tabela-pesquisar-contratos-body');
        if (tabelaBody) {
            tabelaBody.innerHTML = '<tr><td colspan="17" style="text-align: center; padding: 20px; color: #666;">Informe os critérios de pesquisa e clique em "Pesquisar" para visualizar os resultados.</td></tr>';
        }
        const mensagem = document.getElementById('pc-mensagem');
        if (mensagem) {
            mensagem.style.display = 'none';
            mensagem.textContent = '';
        }
    });
    
    // Permitir pesquisa ao pressionar Enter
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        pesquisarContratos();
    });
    
    // Formatação de CPF/CNPJ
    const cpfCnpjInput = document.getElementById('pc-cpf-cnpj');
    if (cpfCnpjInput) {
        cpfCnpjInput.addEventListener('input', function(e) {
            let valor = e.target.value.replace(/\D/g, '');
            if (valor.length <= 11) {
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                valor = valor.replace(/(\d{2})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
                valor = valor.replace(/(\d{3})(\d)/, '$1/$2');
                valor = valor.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
            }
            e.target.value = valor;
        });
    }
}

function pesquisarContratos() {
    const form = document.getElementById('form-pesquisar-contratos');
    const tabelaBody = document.getElementById('tabela-pesquisar-contratos-body');
    const mensagem = document.getElementById('pc-mensagem');
    
    if (!form || !tabelaBody) return;
    
    // Coletar filtros
    const filtros = {
        contrato: document.getElementById('pc-contrato') ? document.getElementById('pc-contrato').value.trim() : '',
        cpf_cnpj: document.getElementById('pc-cpf-cnpj') ? document.getElementById('pc-cpf-cnpj').value.trim() : '',
        inscricao: document.getElementById('pc-inscricao') ? document.getElementById('pc-inscricao').value.trim() : '',
        empreendimento_id: document.getElementById('pc-empreendimento') ? document.getElementById('pc-empreendimento').value : '',
        modulo_id: document.getElementById('pc-modulo') ? document.getElementById('pc-modulo').value : '',
        situacao: document.getElementById('pc-situacao') ? document.getElementById('pc-situacao').value : ''
    };
    
    // Verificar se pelo menos um filtro foi preenchido
    const temFiltro = Object.values(filtros).some(v => v !== '');
    
    if (!temFiltro) {
        mostrarMensagemPesquisaContratos('Por favor, informe pelo menos um critério de pesquisa.', 'erro');
        return;
    }
    
    tabelaBody.innerHTML = '<tr><td colspan="17" style="text-align: center; padding: 20px;">Carregando...</td></tr>';

    // Construir URL com filtros
    const params = new URLSearchParams({ action: 'pesquisar' });
    Object.keys(filtros).forEach(key => {
        if (filtros[key] !== '') {
            params.append(key, filtros[key]);
        }
    });
    
    buscarContratos(params);
}

function mostrarTodosContratos() {
    const tabelaBody = document.getElementById('tabela-pesquisar-contratos-body');
    if (!tabelaBody) return;
    
    tabelaBody.innerHTML = '<tr><td colspan="17" style="text-align: center; padding: 20px;">Carregando...</td></tr>';
    
    // Construir URL sem filtros
    const params = new URLSearchParams({ action: 'listar-todos' });
    
    buscarContratos(params);
}

function buscarContratos(params) {
    const tabelaBody = document.getElementById('tabela-pesquisar-contratos-body');
    const mensagem = document.getElementById('pc-mensagem');
    
    fetch(`/SISIPTU/php/pesquisar_contratos_api.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                tabelaBody.innerHTML = `<tr><td colspan="17" style="text-align: center; padding: 20px; color: #d32f2f;">${data.mensagem || 'Erro ao buscar contratos.'}</td></tr>`;
                mostrarMensagemPesquisaContratos(data.mensagem || 'Erro ao buscar contratos.', 'erro');
                return;
            }
            
            const contratos = data.contratos || [];
            if (contratos.length === 0) {
                tabelaBody.innerHTML = '<tr><td colspan="17" style="text-align: center; padding: 20px; color: #666;">Nenhum contrato encontrado.</td></tr>';
                mostrarMensagemPesquisaContratos('Nenhum contrato encontrado.', 'info');
                return;
            }
            
            // Renderizar resultados - todos os campos
            tabelaBody.innerHTML = contratos.map(c => {
                const dataCriacao = formatarData(c.data_criacao);
                const dataAtualizacao = formatarData(c.data_atualizacao);
                const valorVenal = c.valor_venal ? parseFloat(c.valor_venal).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                const valorAnual = c.valor_anual ? parseFloat(c.valor_anual).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                const metragem = c.metragem ? parseFloat(c.metragem).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                const vrm2 = c.vrm2 ? parseFloat(c.vrm2).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                const aliquota = c.aliquota ? parseFloat(c.aliquota).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                const txColetaLixo = c.tx_coleta_lixo ? parseFloat(c.tx_coleta_lixo).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                const descontoAVista = c.desconto_a_vista ? parseFloat(c.desconto_a_vista).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
                
                return `
                    <tr>
                        <td>${c.empreendimento_nome || ''}</td>
                        <td>${c.modulo_nome || c.modulo || ''}</td>
                        <td>${c.area || ''}</td>
                        <td>${c.contrato || ''}</td>
                        <td>${c.inscricao || ''}</td>
                        <td>${metragem}</td>
                        <td>${vrm2}</td>
                        <td>${valorVenal}</td>
                        <td>${aliquota}%</td>
                        <td>${txColetaLixo}</td>
                        <td>${descontoAVista}</td>
                        <td>${c.parcelamento || ''}</td>
                        <td>${valorAnual}</td>
                        <td>${c.cliente_nome || ''}</td>
                        <td>${c.situacao || ''}</td>
                        <td>${dataCriacao}</td>
                        <td>${dataAtualizacao}</td>
                    </tr>
                `;
            }).join('');
            
            mostrarMensagemPesquisaContratos(`${contratos.length} contrato(s) encontrado(s).`, 'sucesso');
        })
        .catch(err => {
            console.error('Erro ao buscar contratos:', err);
            tabelaBody.innerHTML = '<tr><td colspan="17" style="text-align: center; padding: 20px; color: #d32f2f;">Erro ao buscar contratos. Tente novamente.</td></tr>';
            mostrarMensagemPesquisaContratos('Erro ao buscar contratos. Tente novamente.', 'erro');
        });
}

function mostrarMensagemPesquisaContratos(texto, tipo) {
    const mensagem = document.getElementById('pc-mensagem');
    if (!mensagem) return;
    
    mensagem.textContent = texto;
    mensagem.className = 'mensagem';
    
    if (tipo === 'sucesso') {
        mensagem.style.backgroundColor = '#d4edda';
        mensagem.style.color = '#155724';
        mensagem.style.borderColor = '#c3e6cb';
    } else if (tipo === 'erro') {
        mensagem.style.backgroundColor = '#f8d7da';
        mensagem.style.color = '#721c24';
        mensagem.style.borderColor = '#f5c6cb';
    } else {
        mensagem.style.backgroundColor = '#d1ecf1';
        mensagem.style.color = '#0c5460';
        mensagem.style.borderColor = '#bee5eb';
    }
    
    mensagem.style.display = 'block';
    mensagem.style.padding = '10px';
    mensagem.style.borderRadius = '4px';
    mensagem.style.border = '1px solid';
    mensagem.style.marginTop = '10px';
}

// ========== Manutenção IPTU ==========
function inicializarManutencaoIptu() {
    const form = document.getElementById('form-manutencao-iptu');
    const btnPesquisar = document.getElementById('btn-pesquisar-manutencao');
    const btnLimpar = document.getElementById('btn-limpar-manutencao');
    const selectEmpreendimento = document.getElementById('mi-empreendimento');
    const selectModulo = document.getElementById('mi-modulo');
    const modal = document.getElementById('modal-editar-cobranca');
    const btnSalvarEdicao = document.getElementById('btn-salvar-edicao');
    const btnCancelarEdicao = document.getElementById('btn-cancelar-edicao');
    
    if (!form || !btnPesquisar || !btnLimpar) return;
    
    // Carregar empreendimentos
    if (selectEmpreendimento) {
        fetch('/SISIPTU/php/empreendimentos_api.php?action=list')
            .then(r => r.json())
            .then(data => {
                if (data.sucesso && data.empreendimentos) {
                    data.empreendimentos.forEach(emp => {
                        const opt = document.createElement('option');
                        opt.value = emp.id;
                        opt.textContent = emp.nome;
                        selectEmpreendimento.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Erro ao carregar empreendimentos:', err));
    }
    
    // Carregar módulos quando empreendimento for selecionado
    if (selectEmpreendimento && selectModulo) {
        selectEmpreendimento.addEventListener('change', function() {
            const empId = this.value;
            selectModulo.innerHTML = '<option value="">Selecione</option>';
            
            if (empId) {
                fetch(`/SISIPTU/php/modulos_api.php?action=list&empreendimento_id=${empId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.sucesso && data.modulos) {
                            data.modulos.forEach(mod => {
                                const opt = document.createElement('option');
                                opt.value = mod.id;
                                opt.textContent = mod.nome;
                                selectModulo.appendChild(opt);
                            });
                        }
                    })
                    .catch(err => console.error('Erro ao carregar módulos:', err));
            }
        });
    }
    
    // Botão Pesquisar
    btnPesquisar.addEventListener('click', function() {
        pesquisarCobrancasManutencao();
    });
    
    // Botão Limpar
    btnLimpar.addEventListener('click', function() {
        form.reset();
        if (selectModulo) {
            selectModulo.innerHTML = '<option value="">Selecione</option>';
        }
        const campoCliente = document.getElementById('mi-cliente-nome');
        if (campoCliente) {
            campoCliente.value = '';
        }
        const tabelaBody = document.getElementById('tabela-manutencao-iptu-body');
        if (tabelaBody) {
            tabelaBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px; color: #666;">Informe os critérios de pesquisa e clique em "Pesquisar" para visualizar os títulos.</td></tr>';
        }
        const mensagem = document.getElementById('mi-mensagem');
        if (mensagem) {
            mensagem.style.display = 'none';
            mensagem.textContent = '';
        }
    });
    
    // Permitir pesquisa ao pressionar Enter
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        pesquisarCobrancasManutencao();
    });
    
    // Buscar nome do cliente quando contrato for preenchido
    const campoContrato = document.getElementById('mi-contrato');
    const campoCliente = document.getElementById('mi-cliente-nome');
    const campoEmpreendimento = document.getElementById('mi-empreendimento');
    const campoModulo = document.getElementById('mi-modulo');
    
    if (campoContrato && campoCliente && campoEmpreendimento && campoModulo) {
        campoContrato.addEventListener('blur', function() {
            const contrato = this.value.trim();
            const empreendimentoId = campoEmpreendimento.value;
            const moduloId = campoModulo.value;
            
            if (contrato && empreendimentoId && moduloId) {
                buscarNomeClientePorContrato(empreendimentoId, moduloId, contrato);
            } else {
                campoCliente.value = '';
            }
        });
    }
    
    // Botão Salvar Edição
    if (btnSalvarEdicao) {
        btnSalvarEdicao.addEventListener('click', function() {
            salvarEdicaoCobranca();
        });
    }
    
    // Botão Cancelar Edição
    if (btnCancelarEdicao) {
        btnCancelarEdicao.addEventListener('click', function() {
            if (modal) modal.style.display = 'none';
        });
    }
    
    // Botão Fechar (X)
    const btnFecharModal = document.getElementById('btn-fechar-modal');
    if (btnFecharModal) {
        btnFecharModal.addEventListener('click', function() {
            if (modal) modal.style.display = 'none';
        });
    }
    
    // Fechar modal ao clicar fora
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Fechar modal ao pressionar ESC
        const fecharModalEsc = function(e) {
            if (e.key === 'Escape' && modal.style.display === 'block') {
                modal.style.display = 'none';
                document.removeEventListener('keydown', fecharModalEsc);
            }
        };
        document.addEventListener('keydown', fecharModalEsc);
    }
    
    // Formatação monetária para o campo valor mensal no modal
    const campoValorModal = document.getElementById('edit-valor-mensal');
    if (campoValorModal) {
        let timeoutFormat = null;
        
        // Permitir digitação livre durante o input
        campoValorModal.addEventListener('input', function() {
            // Remover tudo exceto números e vírgula
            let v = this.value.replace(/[^0-9,]/g, '');
            
            // Garantir apenas uma vírgula
            const partes = v.split(',');
            if (partes.length > 2) {
                v = partes[0] + ',' + partes.slice(1).join('');
            }
            
            // Limitar a 2 casas decimais após a vírgula
            if (partes.length === 2 && partes[1].length > 2) {
                v = partes[0] + ',' + partes[1].substring(0, 2);
            }
            
            this.value = v;
            
            // Limpar timeout anterior
            if (timeoutFormat) {
                clearTimeout(timeoutFormat);
            }
            
            // Formatar após 500ms de inatividade
            timeoutFormat = setTimeout(() => {
                formatarValorMonetario(this);
            }, 500);
        });
        
        // Formatar ao sair do campo
        campoValorModal.addEventListener('blur', function() {
            if (timeoutFormat) {
                clearTimeout(timeoutFormat);
            }
            formatarValorMonetario(this);
        });
        
        function formatarValorMonetario(campo) {
            // Preservar o valor exatamente como foi digitado, sem forçar 2 casas decimais
            let v = campo.value.replace(/[^0-9,]/g, '');
            if (v === '' || v === ',') {
                campo.value = '';
                return;
            }
            // Manter o valor como está, apenas garantindo que tenha vírgula se necessário
            // Não forçar 2 casas decimais
            campo.value = v;
        }
    }
    
}

function pesquisarCobrancasManutencao() {
    const form = document.getElementById('form-manutencao-iptu');
    const tabelaBody = document.getElementById('tabela-manutencao-iptu-body');
    const mensagem = document.getElementById('mi-mensagem');
    
    if (!form || !tabelaBody) return;
    
    // Coletar filtros
    const filtros = {
        ano_referencia: document.getElementById('mi-ano-referencia') ? document.getElementById('mi-ano-referencia').value.trim() : '',
        empreendimento_id: document.getElementById('mi-empreendimento') ? document.getElementById('mi-empreendimento').value : '',
        modulo_id: document.getElementById('mi-modulo') ? document.getElementById('mi-modulo').value : '',
        contrato: document.getElementById('mi-contrato') ? document.getElementById('mi-contrato').value.trim() : ''
    };
    
    // Verificar se todos os filtros foram preenchidos
    if (!filtros.ano_referencia || !filtros.empreendimento_id || !filtros.modulo_id || !filtros.contrato) {
        mostrarMensagemManutencao('Por favor, informe Ano Referência, Empreendimento, Módulo e Contrato.', 'erro');
        return;
    }
    
    tabelaBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px;">Carregando...</td></tr>';
    
    // Construir URL com filtros
    const params = new URLSearchParams({ action: 'pesquisar' });
    Object.keys(filtros).forEach(key => {
        if (filtros[key] !== '') {
            params.append(key, filtros[key]);
        }
    });
    
    fetch(`/SISIPTU/php/manutencao_iptu_api.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                tabelaBody.innerHTML = `<tr><td colspan="10" style="text-align: center; padding: 20px; color: #d32f2f;">${data.mensagem || 'Erro ao buscar títulos.'}</td></tr>`;
                mostrarMensagemManutencao(data.mensagem || 'Erro ao buscar títulos.', 'erro');
                return;
            }
            
            const cobrancas = data.cobrancas || [];
            if (cobrancas.length === 0) {
                tabelaBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px; color: #666;">Nenhum título encontrado com os critérios informados.</td></tr>';
                mostrarMensagemManutencao('Nenhum título encontrado.', 'info');
                return;
            }
            
            // Preencher nome do cliente no campo desabilitado (usar o primeiro registro se houver)
            if (cobrancas.length > 0 && cobrancas[0].cliente_nome) {
                const campoCliente = document.getElementById('mi-cliente-nome');
                if (campoCliente) {
                    campoCliente.value = cobrancas[0].cliente_nome;
                }
            }
            
            // Renderizar resultados
            tabelaBody.innerHTML = cobrancas.map(c => {
                // Formatar vencimento - usar datavencimento ou data_vencimento
                // Usar a função formatarData que evita problemas de timezone
                const dataVenc = c.data_vencimento || c.datavencimento;
                const vencimento = formatarData(dataVenc) || '';
                
                // Formatar valor SEM separador de milhar (apenas vírgula decimal)
                const valor = c.valor_mensal ? 
                    'R$ ' + parseFloat(c.valor_mensal).toFixed(2).replace('.', ',') : 
                    '-';
                
                return `
                    <tr data-id="${c.id}">
                        <td>${c.titulo || c.id || ''}</td>
                        <td>${c.ano_referencia || ''}</td>
                        <td>${c.empreendimento_nome || ''}</td>
                        <td>${c.modulo_nome || ''}</td>
                        <td>${c.contrato || ''}</td>
                        <td>${c.parcelamento || ''}</td>
                        <td>${vencimento}</td>
                        <td>${valor}</td>
                        <td>${(c.observacao || '').substring(0, 30)}${(c.observacao || '').length > 30 ? '...' : ''}</td>
                        <td>
                            <button type="button" class="btn-small btn-edit" onclick="editarCobranca(${c.id})" style="margin-right: 5px;">✏️ Editar</button>
                            <button type="button" class="btn-small btn-delete" onclick="excluirCobranca(${c.id})">🗑️ Excluir</button>
                        </td>
                    </tr>
                `;
            }).join('');
            
            mostrarMensagemManutencao(`${cobrancas.length} título(s) encontrado(s).`, 'sucesso');
        })
        .catch(err => {
            console.error('Erro ao buscar títulos:', err);
            tabelaBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px; color: #d32f2f;">Erro ao buscar títulos. Tente novamente.</td></tr>';
            mostrarMensagemManutencao('Erro ao buscar títulos. Tente novamente.', 'erro');
        });
}

function editarCobranca(id) {
    const modal = document.getElementById('modal-editar-cobranca');
    if (!modal) return;
    
    fetch(`/SISIPTU/php/manutencao_iptu_api.php?action=get&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                alert('Erro ao carregar dados do título: ' + (data.mensagem || 'Erro desconhecido'));
                return;
            }
            
            const c = data.cobranca;
            
            // Preencher formulário
            document.getElementById('edit-id').value = c.id || '';
            document.getElementById('edit-observacao').value = c.observacao || '';
            
            // Preencher valor mensal (mesma formatação do campo Valor Anual)
            const campoValor = document.getElementById('edit-valor-mensal');
            if (campoValor && c.valor_mensal) {
                campoValor.value = c.valor_mensal ? parseFloat(c.valor_mensal).toFixed(2).replace('.', ',') : '';
            } else if (campoValor) {
                campoValor.value = '';
            }
            
            // Preencher vencimento - usar data_vencimento ou datavencimento do registro
            const campoVencimento = document.getElementById('edit-dia-vencimento');
            if (campoVencimento) {
                let dataVencimento = null;
                
                // Priorizar data_vencimento se existir, senão usar datavencimento
                const dataVenc = c.data_vencimento || c.datavencimento;
                
                if (dataVenc && dataVenc !== null && dataVenc !== '' && dataVenc !== 'null') {
                    // Data existe - usar diretamente como string
                    // Garantir que está no formato YYYY-MM-DD (sem hora/timezone)
                    let dataStr = String(dataVenc);
                    
                    // Se contém espaço ou T, pegar apenas a parte da data (YYYY-MM-DD)
                    if (dataStr.includes(' ') || dataStr.includes('T')) {
                        dataStr = dataStr.substring(0, 10);
                    }
                    
                    // Validar formato YYYY-MM-DD
                    if (dataStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        dataVencimento = dataStr;
                    }
                }
                
                if (dataVencimento) {
                    // Usar diretamente no formato YYYY-MM-DD (formato esperado pelo input type="date")
                    campoVencimento.value = dataVencimento;
                } else {
                    // Se não há data válida, deixar campo vazio
                    campoVencimento.value = '';
                }
            }
            
            modal.style.display = 'block';
        })
        .catch(err => {
            console.error('Erro ao carregar título:', err);
            alert('Erro ao carregar dados do título.');
        });
}

function salvarEdicaoCobranca() {
    const form = document.getElementById('form-editar-cobranca');
    const id = document.getElementById('edit-id').value;
    
    if (!id) {
        alert('ID do título não encontrado.');
        return;
    }
    
    // Coletar dados do formulário
    const campoValor = document.getElementById('edit-valor-mensal');
    const campoVencimento = document.getElementById('edit-dia-vencimento');
    
    // Processar valor (remover formatação e converter para número)
    // Salvar exatamente como foi informado, preservando casas decimais
    let valorMensal = '';
    if (campoValor && campoValor.value) {
        let valorLimpo = campoValor.value.trim();
        
        // Se o valor contém vírgula, tratar como separador decimal
        if (valorLimpo.includes(',')) {
            // Remover TODOS os pontos (separadores de milhar) ANTES da vírgula
            const partes = valorLimpo.split(',');
            const parteInteira = partes[0].replace(/\./g, ''); // Remove pontos da parte inteira
            const parteDecimal = partes[1] || ''; // Parte decimal após a vírgula
            
            // Reconstruir: parte inteira + ponto + parte decimal
            valorLimpo = parteInteira + '.' + parteDecimal;
        } else {
            // Se não tem vírgula, apenas remover pontos (separadores de milhar)
            valorLimpo = valorLimpo.replace(/\./g, '');
        }
        
        // Validar se é um número válido
        const num = parseFloat(valorLimpo);
        if (!isNaN(num) && num >= 0) {
            // Preservar as casas decimais do valor original
            // Se tinha vírgula, preservar as casas decimais
            if (campoValor.value.includes(',')) {
                const partesOriginais = campoValor.value.split(',');
                const casasDecimais = partesOriginais[1] ? partesOriginais[1].length : 0;
                // Usar toFixed com o número de casas decimais original, mas no máximo 10
                valorMensal = num.toFixed(Math.min(casasDecimais, 10));
            } else {
                // Se não tinha vírgula, usar o número como está (sem casas decimais forçadas)
                valorMensal = num.toString();
            }
        } else {
            valorMensal = '';
        }
    }
    
    const dados = {
        action: 'update',
        id: id,
        valor_mensal: valorMensal || '',
        dia_vencimento: campoVencimento ? campoVencimento.value : '',
        observacao: document.getElementById('edit-observacao').value
    };
    
    fetch('/SISIPTU/php/manutencao_iptu_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(dados)
    })
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                alert('Erro ao salvar: ' + (data.mensagem || 'Erro desconhecido'));
                return;
            }
            
            alert('Título atualizado com sucesso!');
            document.getElementById('modal-editar-cobranca').style.display = 'none';
            pesquisarCobrancasManutencao(); // Recarregar lista
        })
        .catch(err => {
            console.error('Erro ao salvar:', err);
            alert('Erro ao salvar título.');
        });
}

function excluirCobranca(id) {
    if (!confirm('Tem certeza que deseja excluir este título?')) {
        return;
    }
    
    fetch('/SISIPTU/php/manutencao_iptu_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({ action: 'delete', id: id })
    })
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                alert('Erro ao excluir: ' + (data.mensagem || 'Erro desconhecido'));
                return;
            }
            
            alert('Título excluído com sucesso!');
            pesquisarCobrancasManutencao(); // Recarregar lista
        })
        .catch(err => {
            console.error('Erro ao excluir:', err);
            alert('Erro ao excluir título.');
        });
}

function buscarNomeClientePorContrato(empreendimentoId, moduloId, contrato) {
    const campoCliente = document.getElementById('mi-cliente-nome');
    if (!campoCliente) return;
    
    // Buscar na tabela de contratos usando a API de pesquisa de contratos
    // Primeiro tentar buscar na tabela de cobranca (mais rápido, já tem cliente_nome)
    fetch(`/SISIPTU/php/manutencao_iptu_api.php?action=pesquisar&empreendimento_id=${empreendimentoId}&modulo_id=${moduloId}&contrato=${encodeURIComponent(contrato)}`)
        .then(r => r.json())
        .then(data => {
            if (data.sucesso && data.cobrancas && data.cobrancas.length > 0) {
                // Usar o cliente_nome da primeira cobranca encontrada
                if (data.cobrancas[0].cliente_nome) {
                    campoCliente.value = data.cobrancas[0].cliente_nome;
                    return;
                }
            }
            
            // Se não encontrou na cobranca, buscar na tabela de contratos
            fetch(`/SISIPTU/php/contratos_api.php?action=list&q=${encodeURIComponent(contrato)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.sucesso && data.contratos && data.contratos.length > 0) {
                        // Procurar contrato exato com empreendimento e módulo correspondentes
                        const contratoEncontrado = data.contratos.find(c => 
                            c.contrato === contrato && 
                            c.empreendimento_id == empreendimentoId && 
                            c.modulo_id == moduloId
                        );
                        if (contratoEncontrado && contratoEncontrado.cliente_nome) {
                            campoCliente.value = contratoEncontrado.cliente_nome;
                        } else {
                            campoCliente.value = '';
                        }
                    } else {
                        campoCliente.value = '';
                    }
                })
                .catch(err => {
                    console.error('Erro ao buscar nome do cliente:', err);
                    campoCliente.value = '';
                });
        })
        .catch(err => {
            console.error('Erro ao buscar nome do cliente:', err);
            campoCliente.value = '';
        });
}

// ========== Consulta de Cobranças ==========
function inicializarConsultaCobranca() {
    const selectEmpreendimento = document.getElementById('cc-empreendimento');
    const selectModulo = document.getElementById('cc-modulo');
    const btnTitulosPagos = document.getElementById('btn-titulos-pagos');
    const btnTitulosVencidos = document.getElementById('btn-titulos-vencidos');
    const btnTitulosAVencer = document.getElementById('btn-titulos-a-vencer');
    const btnTodosTitulos = document.getElementById('btn-todos-titulos');
    const radioOrdem = document.querySelectorAll('input[name="cc-ordem"]');
    
    // Preencher campo de data de cálculo com a data atual
    const campoDataCalculoInicial = document.getElementById('cc-data-calculo');
    if (campoDataCalculoInicial) {
        const hoje = new Date();
        const dataFormatada = hoje.toISOString().split('T')[0]; // Formato YYYY-MM-DD
        campoDataCalculoInicial.value = dataFormatada;
    }
    
    if (!selectEmpreendimento) return;
    
    // Carregar empreendimentos
    fetch('/SISIPTU/php/empreendimentos_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (data.sucesso && data.empreendimentos) {
                data.empreendimentos.forEach(emp => {
                    const opt = document.createElement('option');
                    opt.value = emp.id;
                    opt.textContent = emp.nome;
                    selectEmpreendimento.appendChild(opt);
                });
            }
        })
        .catch(err => console.error('Erro ao carregar empreendimentos:', err));
    
    // Carregar módulos quando empreendimento for selecionado
    if (selectEmpreendimento && selectModulo) {
        selectEmpreendimento.addEventListener('change', function() {
            const empId = this.value;
            selectModulo.innerHTML = '<option value="">Selecione</option>';
            
            if (empId) {
                fetch(`/SISIPTU/php/modulos_api.php?action=list&empreendimento_id=${encodeURIComponent(empId)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.sucesso && data.modulos) {
                            data.modulos.forEach(mod => {
                                if (mod.empreendimento_id == empId) {
                                    const opt = document.createElement('option');
                                    opt.value = mod.id;
                                    opt.textContent = mod.nome;
                                    selectModulo.appendChild(opt);
                                }
                            });
                        }
                    })
                    .catch(err => console.error('Erro ao carregar módulos:', err));
            }
        });
    }
    
    // Event listeners para botões de filtro
    const filtroButtons = [btnTitulosPagos, btnTitulosVencidos, btnTitulosAVencer, btnTodosTitulos];
    filtroButtons.forEach(btn => {
        if (btn) {
            btn.addEventListener('click', function() {
                // Remover active de todos
                filtroButtons.forEach(b => {
                    if (b) b.classList.remove('active');
                });
                // Adicionar active ao clicado
                this.classList.add('active');
            });
        }
    });
    
    // Event listener para botão Pesquisar
    const btnPesquisar = document.getElementById('btn-pesquisar-consulta');
    if (btnPesquisar) {
        btnPesquisar.addEventListener('click', function() {
            pesquisarCobrancasConsulta();
        });
    }
    
    // Event listener para botão Imprimir
    const btnImprimir = document.getElementById('btn-imprimir-extrato');
    if (btnImprimir) {
        btnImprimir.addEventListener('click', function() {
            gerarExtratoPDF();
        });
    }
    
    // Event listeners para ordenação (não pesquisa automaticamente)
    radioOrdem.forEach(radio => {
        radio.addEventListener('change', function() {
            // Apenas atualiza a seleção, não pesquisa
        });
    });
    
    // Event listener para verificar contrato quando os três campos estiverem preenchidos
    const campoEmpreendimento = document.getElementById('cc-empreendimento');
    const campoModulo = document.getElementById('cc-modulo');
    const campoContrato = document.getElementById('cc-contrato');
    const campoCliente = document.getElementById('cc-cliente');
    
    function verificarContratoConsulta() {
        const empreendimentoId = campoEmpreendimento?.value || '';
        const moduloId = campoModulo?.value || '';
        const contrato = campoContrato?.value.trim() || '';
        
        // Se os três campos estiverem preenchidos, verificar contrato
        if (empreendimentoId && moduloId && contrato) {
            verificarECarregarCliente(empreendimentoId, moduloId, contrato);
        } else {
            // Limpar campo cliente se algum campo estiver vazio
            if (campoCliente) {
                campoCliente.value = '';
            }
            // Limpar grid
            const tabelaBody = document.getElementById('tabela-consulta-cobranca-body');
            if (tabelaBody) {
                tabelaBody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 20px; color: #666;">Informe Empreendimento, Módulo e Contrato para pesquisar.</td></tr>';
            }
        }
    }
    
    if (campoEmpreendimento) {
        campoEmpreendimento.addEventListener('change', verificarContratoConsulta);
    }
    
    if (campoModulo) {
        campoModulo.addEventListener('change', verificarContratoConsulta);
    }
    
    if (campoContrato) {
        campoContrato.addEventListener('input', function() {
            clearTimeout(this.verificarTimeout);
            this.verificarTimeout = setTimeout(verificarContratoConsulta, 500);
        });
        campoContrato.addEventListener('blur', verificarContratoConsulta);
    }
    
    // Event listener para data de cálculo
    const campoDataCalculo = document.getElementById('cc-data-calculo');
    if (campoDataCalculo) {
        campoDataCalculo.addEventListener('change', pesquisarCobrancasConsulta);
    }
}

function verificarECarregarCliente(empreendimentoId, moduloId, contrato) {
    const campoCliente = document.getElementById('cc-cliente');
    const tabelaBody = document.getElementById('tabela-consulta-cobranca-body');
    
    // Buscar contrato na tabela contratos usando verificar-contrato
    fetch(`/SISIPTU/php/contratos_api.php?action=verificar-contrato&empreendimento_id=${encodeURIComponent(empreendimentoId)}&modulo_id=${encodeURIComponent(moduloId)}&contrato=${encodeURIComponent(contrato)}`)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso || !data.existe || !data.contrato) {
                // Contrato não existe
                mostrarMensagemConsulta('Contrato não encontrado. Verifique os dados informados.', 'erro');
                if (campoCliente) {
                    campoCliente.value = '';
                }
                if (tabelaBody) {
                    tabelaBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px; color: #d32f2f;">Contrato não encontrado.</td></tr>';
                }
                // Limpar campos
                document.getElementById('cc-empreendimento').value = '';
                document.getElementById('cc-modulo').value = '';
                document.getElementById('cc-modulo').innerHTML = '<option value="">Selecione</option>';
                document.getElementById('cc-contrato').value = '';
                return;
            }
            
            // Contrato existe - preencher nome do cliente e armazenar dados de contato
            const contratoData = data.contrato;
            const nomeCliente = contratoData.cliente_nome || '';
            
            if (campoCliente) {
                campoCliente.value = nomeCliente;
            }
            
            // Armazenar dados de contato do cliente no campo (usando data-attributes)
            if (campoCliente) {
                campoCliente.setAttribute('data-cliente-id', contratoData.cliente_id || '');
                campoCliente.setAttribute('data-cliente-email', contratoData.cliente_email || '');
                campoCliente.setAttribute('data-cliente-tel-celular1', contratoData.cliente_tel_celular1 || '');
                campoCliente.setAttribute('data-cliente-tel-celular2', contratoData.cliente_tel_celular2 || '');
                campoCliente.setAttribute('data-cliente-tel-comercial', contratoData.cliente_tel_comercial || '');
                campoCliente.setAttribute('data-cliente-tel-residencial', contratoData.cliente_tel_residencial || '');
            }
            
            // Pesquisar cobranças
            pesquisarCobrancasConsulta();
        })
        .catch(err => {
            console.error('Erro ao verificar contrato:', err);
            mostrarMensagemConsulta('Erro ao verificar contrato. Tente novamente.', 'erro');
            if (campoCliente) {
                campoCliente.value = '';
            }
            if (tabelaBody) {
                tabelaBody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 20px; color: #d32f2f;">Erro ao verificar contrato.</td></tr>';
            }
        });
}

function mostrarMensagemConsulta(texto, tipo) {
    const mensagem = document.getElementById('cc-mensagem');
    if (!mensagem) return;
    
    mensagem.textContent = texto;
    mensagem.style.display = 'block';
    mensagem.style.padding = '10px';
    mensagem.style.borderRadius = '4px';
    mensagem.style.marginTop = '10px';
    
    if (tipo === 'sucesso') {
        mensagem.style.backgroundColor = '#d4edda';
        mensagem.style.color = '#155724';
        mensagem.style.border = '1px solid #c3e6cb';
    } else if (tipo === 'erro') {
        mensagem.style.backgroundColor = '#f8d7da';
        mensagem.style.color = '#721c24';
        mensagem.style.border = '1px solid #f5c6cb';
    } else {
        mensagem.style.backgroundColor = '#d1ecf1';
        mensagem.style.color = '#0c5460';
        mensagem.style.border = '1px solid #bee5eb';
    }
    
    // Ocultar mensagem após 5 segundos
    setTimeout(() => {
        mensagem.style.display = 'none';
    }, 5000);
}

function gerarExtratoPDF() {
    const empreendimentoId = document.getElementById('cc-empreendimento')?.value || '';
    const moduloId = document.getElementById('cc-modulo')?.value || '';
    const contrato = document.getElementById('cc-contrato')?.value.trim() || '';
    const cliente = document.getElementById('cc-cliente')?.value.trim() || '';
    const dataCalculo = document.getElementById('cc-data-calculo')?.value || '';
    
    // Verificar se os campos obrigatórios estão preenchidos
    if (!empreendimentoId || !moduloId || !contrato) {
        mostrarMensagemConsulta('Informe Empreendimento, Módulo e Contrato para gerar o extrato.', 'erro');
        return;
    }
    
    // Obter filtro de títulos ativo
    const btnAtivo = document.querySelector('.btn-filter.active');
    const filtroTitulo = btnAtivo ? btnAtivo.getAttribute('data-filtro') : 'todos';
    
    // Obter ordem selecionada
    const ordemRadio = document.querySelector('input[name="cc-ordem"]:checked');
    const ordem = ordemRadio ? ordemRadio.value : 'vencimento';
    
    // Construir parâmetros
    const params = new URLSearchParams();
    params.append('empreendimento_id', empreendimentoId);
    params.append('modulo_id', moduloId);
    params.append('contrato', contrato);
    if (dataCalculo) params.append('data_calculo', dataCalculo);
    params.append('filtro_titulo', filtroTitulo);
    params.append('ordem', ordem);
    params.append('formato', 'pdf');
    
    // Abrir modal de opções
    mostrarModalExtrato(empreendimentoId, moduloId, contrato, cliente, params);
}

function mostrarModalExtrato(empreendimentoId, moduloId, contrato, cliente, params) {
    // Criar modal
    const modal = document.createElement('div');
    modal.id = 'modal-extrato';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
    
    modal.innerHTML = `
        <div style="background: white; padding: 30px; border-radius: 10px; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <h3 style="margin-bottom: 20px; color: #2d8659;">📄 Gerar Extrato</h3>
            <p style="margin-bottom: 20px; color: #666;">
                <strong>Cliente:</strong> ${cliente || 'Não informado'}<br>
                <strong>Contrato:</strong> ${contrato}
            </p>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <button type="button" id="btn-extrato-imprimir" class="btn-primary" style="width: 100%; padding: 12px;">
                    🖨️ Imprimir
                </button>
                <button type="button" id="btn-extrato-email" class="btn-primary" style="width: 100%; padding: 12px;">
                    📧 Enviar por Email
                </button>
                <button type="button" id="btn-extrato-whatsapp" class="btn-primary" style="width: 100%; padding: 12px; background: #25D366;">
                    💬 Enviar por WhatsApp
                </button>
                <button type="button" id="btn-extrato-cancelar" class="btn-secondary" style="width: 100%; padding: 12px;">
                    Cancelar
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Event listeners
    document.getElementById('btn-extrato-imprimir').addEventListener('click', function() {
        imprimirExtrato(params);
        document.body.removeChild(modal);
    });
    
    document.getElementById('btn-extrato-email').addEventListener('click', function() {
        enviarExtratoEmail(empreendimentoId, moduloId, contrato, cliente, params);
        document.body.removeChild(modal);
    });
    
    document.getElementById('btn-extrato-whatsapp').addEventListener('click', function() {
        enviarExtratoWhatsApp(empreendimentoId, moduloId, contrato, cliente, params);
        document.body.removeChild(modal);
    });
    
    document.getElementById('btn-extrato-cancelar').addEventListener('click', function() {
        document.body.removeChild(modal);
    });
    
    // Fechar ao clicar fora
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    });
}

function imprimirExtrato(params) {
    // Buscar dados e gerar HTML para impressão
    fetch(`/SISIPTU/php/manutencao_iptu_api.php?action=pesquisar&${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                mostrarMensagemConsulta('Erro ao buscar dados para impressão.', 'erro');
                return;
            }
            
            const cobrancas = data.cobrancas || [];
            const empreendimento = document.getElementById('cc-empreendimento');
            const empreendimentoNome = empreendimento.options[empreendimento.selectedIndex]?.text || '';
            const cliente = document.getElementById('cc-cliente')?.value || '';
            const contrato = document.getElementById('cc-contrato')?.value || '';
            const dataCalculo = document.getElementById('cc-data-calculo')?.value || '';
            
            // Calcular juros e multas se houver data de cálculo
            if (dataCalculo) {
                calcularJurosMultas(cobrancas, dataCalculo);
            }
            
            // Criar HTML para impressão
            const htmlExtrato = gerarHTMLExtrato(cobrancas, empreendimentoNome, cliente, contrato, dataCalculo);
            
            // Abrir janela de impressão
            const janelaImpressao = window.open('', '_blank');
            janelaImpressao.document.write(htmlExtrato);
            janelaImpressao.document.close();
            janelaImpressao.focus();
            setTimeout(() => {
                janelaImpressao.print();
            }, 250);
        })
        .catch(err => {
            console.error('Erro ao gerar extrato:', err);
            mostrarMensagemConsulta('Erro ao gerar extrato para impressão.', 'erro');
        });
}

function gerarPDFExtrato(params) {
    // Buscar dados e gerar PDF via API
    const url = `/SISIPTU/php/extrato_api.php?action=gerar-pdf&${params.toString()}`;
    window.open(url, '_blank');
}

function enviarExtratoEmail(empreendimentoId, moduloId, contrato, cliente, params) {
    // Buscar email do cliente no cadastro
    const campoCliente = document.getElementById('cc-cliente');
    let email = '';
    let emailEncontrado = false;
    
    if (campoCliente) {
        email = campoCliente.getAttribute('data-cliente-email') || '';
        emailEncontrado = (email && email.includes('@'));
    }
    
    // Se encontrou email no cadastro, mostrar confirmação
    if (emailEncontrado) {
        const emailConfirmado = prompt(`Email encontrado no cadastro do cliente:\n\n${email}\n\nDeseja usar este email? (Deixe em branco para usar ou digite outro email):`, email);
        if (emailConfirmado === null) {
            // Usuário cancelou
            return;
        }
        email = emailConfirmado.trim() || email;
    } else {
        // Se não encontrou email no cadastro, solicitar ao usuário
        email = prompt('Email do cliente não encontrado no cadastro.\n\nDigite o email do cliente para envio do extrato:');
        if (!email || !email.includes('@')) {
            mostrarMensagemConsulta('Email inválido.', 'erro');
            return;
        }
    }
    
    // Validar email antes de enviar
    if (!email || !email.includes('@')) {
        mostrarMensagemConsulta('Email inválido.', 'erro');
        return;
    }
    
    // Enviar requisição para API
    fetch('/SISIPTU/php/extrato_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'enviar-email',
            empreendimento_id: empreendimentoId,
            modulo_id: moduloId,
            contrato: contrato,
            cliente: cliente,
            email: email,
            ...Object.fromEntries(params)
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            mostrarMensagemConsulta(`Extrato enviado por email com sucesso para: ${email}`, 'sucesso');
        } else {
            mostrarMensagemConsulta(data.mensagem || 'Erro ao enviar email.', 'erro');
        }
    })
    .catch(err => {
        console.error('Erro ao enviar email:', err);
        mostrarMensagemConsulta('Erro ao enviar email.', 'erro');
    });
}

function enviarExtratoWhatsApp(empreendimentoId, moduloId, contrato, cliente, params) {
    // Buscar telefone do cliente no cadastro (prioridade: celular1 > celular2 > comercial > residencial)
    const campoCliente = document.getElementById('cc-cliente');
    let telefone = '';
    
    if (campoCliente) {
        telefone = campoCliente.getAttribute('data-cliente-tel-celular1') || 
                   campoCliente.getAttribute('data-cliente-tel-celular2') || 
                   campoCliente.getAttribute('data-cliente-tel-comercial') || 
                   campoCliente.getAttribute('data-cliente-tel-residencial') || '';
    }
    
    // Limpar formatação do telefone se encontrado
    if (telefone) {
        telefone = telefone.replace(/\D/g, '');
    }
    
    // Se não encontrou telefone no cadastro, solicitar ao usuário
    if (!telefone || telefone.length < 10) {
        telefone = prompt('Telefone do cliente não encontrado no cadastro.\n\nDigite o telefone do cliente (com DDD, apenas números):');
        if (!telefone) {
            return;
        }
        telefone = telefone.replace(/\D/g, '');
        if (telefone.length < 10) {
            mostrarMensagemConsulta('Telefone inválido.', 'erro');
            return;
        }
    }
    
    // Gerar link do WhatsApp
    const mensagem = encodeURIComponent(`Olá! Segue o extrato de IPTU do contrato ${contrato}.\n\nCliente: ${cliente}`);
    const urlWhatsApp = `https://wa.me/55${telefone}?text=${mensagem}`;
    
    // Abrir WhatsApp Web
    window.open(urlWhatsApp, '_blank');
    
    // Também gerar PDF para anexar
    setTimeout(() => {
        gerarPDFExtrato(params);
    }, 500);
}

function gerarHTMLExtrato(cobrancas, empreendimentoNome, cliente, contrato, dataCalculo) {
    const hoje = new Date();
    const dataExtrato = hoje.toLocaleDateString('pt-BR');
    const dataCalculoFormatada = dataCalculo ? formatarData(dataCalculo) : '';
    
    // Usar data de cálculo se informada, senão usar data atual
    const dataReferencia = dataCalculo ? new Date(dataCalculo) : new Date();
    dataReferencia.setHours(0, 0, 0, 0);
    
    // Calcular totais
    let totalValor = 0;
    let totalJuros = 0;
    let totalMulta = 0;
    let totalPago = 0;
    
    cobrancas.forEach(c => {
        totalValor += parseFloat(c.valor_mensal || 0);
        const jurosValor = c.juros_calculado !== undefined ? c.juros_calculado : (parseFloat(c.juros || 0));
        const multaValor = c.multa_calculada !== undefined ? c.multa_calculada : (parseFloat(c.multas || 0));
        totalJuros += jurosValor;
        totalMulta += multaValor;
        if (c.pago === 'S' || c.pago === 's') {
            totalPago += parseFloat(c.valor_pago || c.valor_mensal || 0);
        }
    });
    
    const totalGeral = totalValor + totalJuros + totalMulta;
    
    let tabelaHTML = '';
    cobrancas.forEach(c => {
        const vencimento = formatarData(c.datavencimento || c.data_vencimento) || '-';
        const valor = parseFloat(c.valor_mensal || 0);
        const jurosValor = c.juros_calculado !== undefined ? c.juros_calculado : (parseFloat(c.juros || 0));
        const multaValor = c.multa_calculada !== undefined ? c.multa_calculada : (parseFloat(c.multas || 0));
        const valorTotal = valor + jurosValor + multaValor;
        
        // Verificar se está em atraso
        let statusAtraso = '';
        const pagoStatus = c.pago === 'S' || c.pago === 's';
        if (!pagoStatus) {
            const dataVenc = c.datavencimento || c.data_vencimento;
            if (dataVenc) {
                const dataVencimento = new Date(dataVenc);
                dataVencimento.setHours(0, 0, 0, 0);
                if (dataVencimento < dataReferencia) {
                    statusAtraso = 'Em atraso';
                }
            }
        }
        
        tabelaHTML += `
            <tr>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${c.titulo || c.id || '-'}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${c.parcelamento || '-'}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">${vencimento}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">R$ ${valor.toFixed(2).replace('.', ',')}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">R$ ${jurosValor.toFixed(2).replace('.', ',')}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">R$ ${multaValor.toFixed(2).replace('.', ',')}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">R$ ${valorTotal.toFixed(2).replace('.', ',')}</td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center; ${statusAtraso ? 'color: #d32f2f; font-weight: bold;' : ''}">${statusAtraso || '-'}</td>
            </tr>
        `;
    });
    
    return `
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Extrato de IPTU - ${contrato}</title>
            <style>
                @media print {
                    @page {
                        margin: 1cm;
                    }
                    body {
                        margin: 0;
                        padding: 0;
                    }
                }
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    color: #333;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #2d8659;
                    padding-bottom: 20px;
                    margin-bottom: 20px;
                }
                .header h1 {
                    color: #2d8659;
                    margin: 0;
                }
                .info-section {
                    margin-bottom: 20px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 10px;
                }
                .info-label {
                    font-weight: bold;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                th {
                    background-color: #2d8659;
                    color: white;
                    padding: 10px;
                    text-align: center;
                    border: 1px solid #ddd;
                }
                td {
                    border: 1px solid #ddd;
                    padding: 8px;
                }
                .total-section {
                    margin-top: 20px;
                    padding: 15px;
                    background-color: #f5f5f5;
                    border: 2px solid #2d8659;
                }
                .total-row {
                    display: flex;
                    justify-content: space-between;
                    margin: 5px 0;
                    font-size: 14px;
                }
                .total-final {
                    font-size: 18px;
                    font-weight: bold;
                    color: #2d8659;
                    margin-top: 10px;
                    padding-top: 10px;
                    border-top: 2px solid #2d8659;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>EXTRATO DE IPTU</h1>
                <p>Data de Emissão: ${dataExtrato}</p>
            </div>
            
            <div class="info-section">
                <div class="info-row">
                    <span class="info-label">Cliente:</span>
                    <span>${cliente || 'Não informado'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Contrato:</span>
                    <span>${contrato}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Empreendimento:</span>
                    <span>${empreendimentoNome}</span>
                </div>
                ${dataCalculoFormatada ? `
                <div class="info-row">
                    <span class="info-label">Data para Cálculo:</span>
                    <span>${dataCalculoFormatada}</span>
                </div>
                ` : ''}
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Parcela</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Juros</th>
                        <th>Multa</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${tabelaHTML}
                </tbody>
            </table>
            
            <div class="total-section">
                <div class="total-row">
                    <span>Total de Parcelas:</span>
                    <span>R$ ${totalValor.toFixed(2).replace('.', ',')}</span>
                </div>
                <div class="total-row">
                    <span>Total de Juros:</span>
                    <span>R$ ${totalJuros.toFixed(2).replace('.', ',')}</span>
                </div>
                <div class="total-row">
                    <span>Total de Multas:</span>
                    <span>R$ ${totalMulta.toFixed(2).replace('.', ',')}</span>
                </div>
                <div class="total-row total-final">
                    <span>TOTAL GERAL:</span>
                    <span>R$ ${totalGeral.toFixed(2).replace('.', ',')}</span>
                </div>
                <div class="total-row">
                    <span>Total Pago:</span>
                    <span>R$ ${totalPago.toFixed(2).replace('.', ',')}</span>
                </div>
                <div class="total-row total-final">
                    <span>SALDO DEVEDOR:</span>
                    <span>R$ ${(totalGeral - totalPago).toFixed(2).replace('.', ',')}</span>
                </div>
            </div>
        </body>
        </html>
    `;
}

function pesquisarCobrancasConsulta() {
    const empreendimentoId = document.getElementById('cc-empreendimento')?.value || '';
    const moduloId = document.getElementById('cc-modulo')?.value || '';
    const contrato = document.getElementById('cc-contrato')?.value.trim() || '';
    const dataCalculo = document.getElementById('cc-data-calculo')?.value || '';
    
    // Verificar se os campos obrigatórios estão preenchidos
    if (!empreendimentoId || !moduloId || !contrato) {
        const tabelaBody = document.getElementById('tabela-consulta-cobranca-body');
        if (tabelaBody) {
                tabelaBody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 20px; color: #666;">Informe Empreendimento, Módulo e Contrato para pesquisar.</td></tr>';
        }
        return;
    }
    
    // Obter filtro de títulos ativo
    const btnAtivo = document.querySelector('.btn-filter.active');
    const filtroTitulo = btnAtivo ? btnAtivo.getAttribute('data-filtro') : 'todos';
    
    // Obter ordem selecionada
    const ordemRadio = document.querySelector('input[name="cc-ordem"]:checked');
    const ordem = ordemRadio ? ordemRadio.value : 'vencimento';
    
    const tabelaBody = document.getElementById('tabela-consulta-cobranca-body');
    
    if (!tabelaBody) return;
    
                tabelaBody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 20px;">Carregando...</td></tr>';
    
    // Construir parâmetros (não incluir cliente, pois ele é apenas informativo)
    const params = new URLSearchParams();
    if (empreendimentoId) params.append('empreendimento_id', empreendimentoId);
    if (moduloId) params.append('modulo_id', moduloId);
    if (contrato) params.append('contrato', contrato);
    if (dataCalculo) params.append('data_calculo', dataCalculo);
    params.append('filtro_titulo', filtroTitulo);
    params.append('ordem', ordem);
    
    fetch(`/SISIPTU/php/manutencao_iptu_api.php?action=pesquisar&${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                tabelaBody.innerHTML = `<tr><td colspan="11" style="text-align: center; padding: 20px; color: #d32f2f;">${data.mensagem || 'Erro ao buscar cobranças.'}</td></tr>`;
                return;
            }
            
            const cobrancas = data.cobrancas || [];
            
            if (cobrancas.length === 0) {
                tabelaBody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 20px; color: #666;">Nenhuma cobrança encontrada.</td></tr>';
                atualizarPosicaoFinanceira([]);
                return;
            }
            
            // Ordenar cobranças
            let cobrancasOrdenadas = [...cobrancas];
            switch(ordem) {
                case 'vencimento':
                    cobrancasOrdenadas.sort((a, b) => {
                        const dataA = a.datavencimento || a.data_vencimento || '';
                        const dataB = b.datavencimento || b.data_vencimento || '';
                        return dataA.localeCompare(dataB);
                    });
                    break;
                case 'parcela':
                    cobrancasOrdenadas.sort((a, b) => (a.parcelamento || 0) - (b.parcelamento || 0));
                    break;
                case 'pagamento':
                    cobrancasOrdenadas.sort((a, b) => {
                        const dataA = a.data_pagamento || '';
                        const dataB = b.data_pagamento || '';
                        return dataB.localeCompare(dataA); // Mais recente primeiro
                    });
                    break;
                case 'titulo':
                    cobrancasOrdenadas.sort((a, b) => {
                        const tituloA = (a.titulo || a.id || '').toString();
                        const tituloB = (b.titulo || b.id || '').toString();
                        return tituloA.localeCompare(tituloB);
                    });
                    break;
            }
            
            // Usar data de cálculo se informada, senão usar data atual
            const dataCalculoInput = document.getElementById('cc-data-calculo')?.value;
            const dataReferencia = dataCalculoInput ? new Date(dataCalculoInput) : new Date();
            dataReferencia.setHours(0, 0, 0, 0);
            
            // Calcular juros e multas automaticamente para parcelas em atraso
            // Usa a data de cálculo se informada, senão usa data atual
            calcularJurosMultas(cobrancasOrdenadas, dataCalculoInput || dataReferencia.toISOString().split('T')[0]);
            
            // Renderizar resultados
            
            tabelaBody.innerHTML = cobrancasOrdenadas.map(c => {
                const vencimento = formatarData(c.datavencimento || c.data_vencimento) || '-';
                const valor = c.valor_mensal ? 
                    'R$ ' + parseFloat(c.valor_mensal).toFixed(2).replace('.', ',') : 
                    '-';
                const valorPago = c.valor_pago ? 
                    'R$ ' + parseFloat(c.valor_pago).toFixed(2).replace('.', ',') : 
                    '-';
                const dataPagamento = formatarData(c.data_pagamento) || '-';
                const pago = c.pago === 'S' || c.pago === 's' ? 'Sim' : 'Não';
                // Baixa pode ser a data de pagamento ou data_baixa se existir
                const baixa = formatarData(c.data_baixa || c.data_pagamento) || '-';
                // Usar valores calculados se existirem, senão usar valores do banco
                const jurosCalculado = c.juros_calculado !== undefined ? c.juros_calculado : (c.juros || 0);
                const multaCalculada = c.multa_calculada !== undefined ? c.multa_calculada : (c.multas || 0);
                const juros = jurosCalculado > 0 ? 
                    'R$ ' + parseFloat(jurosCalculado).toFixed(2).replace('.', ',') : 
                    'R$ 0,00';
                const multa = multaCalculada > 0 ? 
                    'R$ ' + parseFloat(multaCalculada).toFixed(2).replace('.', ',') : 
                    'R$ 0,00';
                
                // Verificar se está em atraso usando a data de cálculo (ou data atual se não informada)
                let statusAtraso = '';
                const pagoStatus = c.pago === 'S' || c.pago === 's';
                if (!pagoStatus) {
                    const dataVenc = c.datavencimento || c.data_vencimento;
                    if (dataVenc) {
                        const dataVencimento = new Date(dataVenc);
                        dataVencimento.setHours(0, 0, 0, 0);
                        if (dataVencimento < dataReferencia) {
                            statusAtraso = '<span style="color: #d32f2f; font-weight: bold;">Em atraso</span>';
                        }
                    }
                }
                
                return `
                    <tr data-cobranca-id="${c.id}">
                        <td>${c.titulo || c.id || '-'}</td>
                        <td>${c.parcelamento || '-'}</td>
                        <td>${vencimento}</td>
                        <td>${baixa}</td>
                        <td>${dataPagamento}</td>
                        <td>${pago}</td>
                        <td>${valor}</td>
                        <td>${juros}</td>
                        <td>${multa}</td>
                        <td>${valorPago}</td>
                        <td>${statusAtraso}</td>
                    </tr>
                `;
            }).join('');
            
            atualizarPosicaoFinanceira(cobrancas);
        })
        .catch(err => {
            console.error('Erro ao buscar cobranças:', err);
            tabelaBody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 20px; color: #d32f2f;">Erro ao buscar cobranças. Tente novamente.</td></tr>';
        });
}

function calcularJurosMultas(cobrancas, dataCalculo) {
    if (!cobrancas || cobrancas.length === 0) return;
    
    // Se não houver data de cálculo, usar data atual
    const dataCalculoObj = dataCalculo ? new Date(dataCalculo) : new Date();
    dataCalculoObj.setHours(0, 0, 0, 0);
    
    cobrancas.forEach(c => {
        // Só calcular se não estiver pago
        if (c.pago === 'S' || c.pago === 's') {
            c.juros_calculado = 0;
            c.multa_calculada = 0;
            return;
        }
        
        const dataVenc = c.datavencimento || c.data_vencimento;
        if (!dataVenc) {
            c.juros_calculado = 0;
            c.multa_calculada = 0;
            return;
        }
        
        const dataVencimento = new Date(dataVenc);
        dataVencimento.setHours(0, 0, 0, 0);
        
        // Só calcular se houver atraso (data de vencimento menor que data de referência)
        if (dataVencimento >= dataCalculoObj) {
            c.juros_calculado = 0;
            c.multa_calculada = 0;
            return;
        }
        
        // Calcular dias de atraso
        const diffTime = dataCalculoObj - dataVencimento;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays <= 0) {
            c.juros_calculado = 0;
            c.multa_calculada = 0;
            return;
        }
        
        const valorMensal = parseFloat(c.valor_mensal || 0);
        
        // Buscar valores de multa_mes e juros_mes do banco vinculado ao empreendimento
        // Estes valores já vêm da API através do JOIN: cobranca -> empreendimentos -> bancos
        // O fluxo é: c.empreendimento_id -> e.banco_id -> b.multa_mes e b.juros_mes
        const multaMes = parseFloat(c.multa_mes || 0);
        const jurosMes = parseFloat(c.juros_mes || 0);
        
        // Calcular multa (percentual sobre o valor)
        // Multa = valor_mensal * (multa_mes / 100)
        let multa = 0;
        if (multaMes > 0 && valorMensal > 0) {
            multa = valorMensal * (multaMes / 100);
        }
        
        // Calcular juros (percentual mensal proporcional aos dias)
        // Juros = valor_mensal * (juros_mes / 100) * (dias_atraso / 30)
        let juros = 0;
        if (jurosMes > 0 && valorMensal > 0) {
            // Juros mensal proporcional aos dias de atraso
            const mesesAtraso = diffDays / 30;
            juros = valorMensal * (jurosMes / 100) * mesesAtraso;
        }
        
        // Armazenar valores calculados no objeto
        c.juros_calculado = juros;
        c.multa_calculada = multa;
    });
}

function atualizarPosicaoFinanceira(cobrancas) {
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    
    let numTitulos = cobrancas.length;
    let pagos = 0;
    let vencidas = 0;
    let aVencer = 0;
    let vrTotalParc = 0;
    let vrTitPagos = 0;
    let vrTitVencidos = 0;
    let vrTitAVencer = 0;
    let jurosMultasPagas = 0;
    let jurosMultasAPagar = 0;
    let outrasTaxas = 0;
    let vrIptu = 0;
    
    cobrancas.forEach(c => {
        const valor = parseFloat(c.valor_mensal || 0);
        const valorPago = parseFloat(c.valor_pago || 0);
        const pago = c.pago === 'S' || c.pago === 's';
        const dataVenc = c.datavencimento || c.data_vencimento;
        
        // Usar valores calculados de juros e multas se existirem
        const jurosValor = c.juros_calculado !== undefined ? c.juros_calculado : (parseFloat(c.juros || 0));
        const multaValor = c.multa_calculada !== undefined ? c.multa_calculada : (parseFloat(c.multas || 0));
        
        vrTotalParc += valor;
        
        if (pago) {
            pagos++;
            vrTitPagos += valorPago || valor;
            // Juros e multas pagas
            jurosMultasPagas += jurosValor + multaValor;
        } else if (dataVenc) {
            const dataVencimento = new Date(dataVenc);
            dataVencimento.setHours(0, 0, 0, 0);
            
            if (dataVencimento < hoje) {
                vencidas++;
                vrTitVencidos += valor;
                // Juros e multas a pagar
                jurosMultasAPagar += jurosValor + multaValor;
            } else {
                aVencer++;
                vrTitAVencer += valor;
            }
        }
        
        vrIptu += valor;
    });
    
    // Atualizar campos
    document.getElementById('cc-num-titulos').value = numTitulos;
    document.getElementById('cc-pagos').value = pagos;
    document.getElementById('cc-vencidas').value = vencidas;
    document.getElementById('cc-a-vencer').value = aVencer;
    document.getElementById('cc-vr-total-parc').value = 'R$ ' + vrTotalParc.toFixed(2).replace('.', ',');
    document.getElementById('cc-vr-tit-pagos').value = 'R$ ' + vrTitPagos.toFixed(2).replace('.', ',');
    document.getElementById('cc-vr-tit-vencidos').value = 'R$ ' + vrTitVencidos.toFixed(2).replace('.', ',');
    document.getElementById('cc-vr-tit-a-vencer').value = 'R$ ' + vrTitAVencer.toFixed(2).replace('.', ',');
    document.getElementById('cc-juros-multas-pagas').value = 'R$ ' + jurosMultasPagas.toFixed(2).replace('.', ',');
    document.getElementById('cc-juros-multas-a-pagar').value = 'R$ ' + jurosMultasAPagar.toFixed(2).replace('.', ',');
    document.getElementById('cc-outras-taxas').value = 'R$ ' + outrasTaxas.toFixed(2).replace('.', ',');
    document.getElementById('cc-vr-iptu').value = 'R$ ' + vrIptu.toFixed(2).replace('.', ',');
}


function mostrarMensagemManutencao(texto, tipo) {
    const mensagem = document.getElementById('mi-mensagem');
    if (!mensagem) return;
    
    mensagem.textContent = texto;
    mensagem.className = 'mensagem';
    
    if (tipo === 'sucesso') {
        mensagem.style.backgroundColor = '#d4edda';
        mensagem.style.color = '#155724';
        mensagem.style.borderColor = '#c3e6cb';
    } else if (tipo === 'erro') {
        mensagem.style.backgroundColor = '#f8d7da';
        mensagem.style.color = '#721c24';
        mensagem.style.borderColor = '#f5c6cb';
    } else {
        mensagem.style.backgroundColor = '#d1ecf1';
        mensagem.style.color = '#0c5460';
        mensagem.style.borderColor = '#bee5eb';
    }
    
    mensagem.style.display = 'block';
    mensagem.style.padding = '10px';
    mensagem.style.borderRadius = '4px';
    mensagem.style.border = '1px solid';
    mensagem.style.marginTop = '10px';
}

function carregarArquivosServidor(diretorio) {
    const selectArquivo = document.getElementById('ic-arquivo-selecionado');
    if (!selectArquivo) return;
    
    selectArquivo.innerHTML = '<option value="">Carregando arquivos...</option>';
    
    fetch(`/SISIPTU/php/importar_clientes_api.php?action=listar-arquivos&diretorio=${encodeURIComponent(diretorio)}`)
        .then(r => r.json())
        .then(data => {
            selectArquivo.innerHTML = '<option value="">Selecione um arquivo...</option>';
            
            if (!data.sucesso) {
                alert('Erro ao carregar arquivos: ' + (data.mensagem || 'Erro desconhecido'));
                return;
            }
            
            const arquivos = data.arquivos || [];
            if (arquivos.length === 0) {
                selectArquivo.innerHTML = '<option value="">Nenhum arquivo encontrado</option>';
                return;
            }
            
            arquivos.forEach(arquivo => {
                const opt = document.createElement('option');
                opt.value = arquivo;
                opt.textContent = arquivo;
                selectArquivo.appendChild(opt);
            });
        })
        .catch(err => {
            console.error('Erro ao carregar arquivos:', err);
            alert('Erro ao carregar arquivos do servidor.');
            selectArquivo.innerHTML = '<option value="">Erro ao carregar arquivos</option>';
        });
}

function visualizarArquivo(diretorio, arquivo, delimitador, primeiraLinhaCabecalho) {
    const previewSection = document.getElementById('ic-preview-section');
    const tabelaHead = document.getElementById('tabela-preview-importacao-head');
    const tabelaBody = document.getElementById('tabela-preview-importacao-body');
    
    if (!previewSection || !tabelaHead || !tabelaBody) return;
    
    previewSection.style.display = 'block';
    tabelaHead.innerHTML = '<tr><td colspan="9">Carregando...</td></tr>';
    tabelaBody.innerHTML = '';
    
    const params = new URLSearchParams({
        action: 'preview',
        diretorio: diretorio,
        arquivo: arquivo,
        delimitador: delimitador,
        primeira_linha_cabecalho: primeiraLinhaCabecalho ? '1' : '0'
    });
    
    fetch(`/SISIPTU/php/importar_clientes_api.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) {
                tabelaHead.innerHTML = '<tr><td colspan="9">Erro: ' + (data.mensagem || 'Erro desconhecido') + '</td></tr>';
                return;
            }
            
            const linhas = data.linhas || [];
            if (linhas.length === 0) {
                tabelaHead.innerHTML = '<tr><td colspan="9">Nenhum dado encontrado no arquivo</td></tr>';
                return;
            }
            
            // Primeira linha como cabeçalho - mostrar apenas colunas 1 a 9 (índices 0 a 8)
            const cabecalho = linhas[0];
            const cabecalhoLimitado = cabecalho.slice(0, 9);
            tabelaHead.innerHTML = '<tr>' + cabecalhoLimitado.map(col => `<th>${col || ''}</th>`).join('') + '</tr>';
            
            // Demais linhas como dados (máximo 20 linhas para preview) - mostrar apenas colunas 1 a 9
            const dadosPreview = linhas.slice(1, 21);
            tabelaBody.innerHTML = dadosPreview.map(linha => {
                const linhaLimitada = linha.slice(0, 9);
                return '<tr>' + linhaLimitada.map(celula => `<td>${celula || ''}</td>`).join('') + '</tr>';
            }).join('');
            
            if (linhas.length > 21) {
                tabelaBody.innerHTML += `<tr><td colspan="9" style="text-align: center; font-style: italic; color: #666;">... e mais ${linhas.length - 21} linha(s)</td></tr>`;
            }
        })
        .catch(err => {
            console.error('Erro ao visualizar arquivo:', err);
            tabelaHead.innerHTML = '<tr><td colspan="9">Erro ao carregar preview do arquivo</td></tr>';
        });
}

function importarClientes(diretorio, arquivo, delimitador, primeiraLinhaCabecalho) {
    const btnImportar = document.getElementById('btn-importar-arquivo');
    const mensagemDiv = document.getElementById('ic-mensagem');
    const resultadoSection = document.getElementById('ic-resultado-section');
    const resultadoConteudo = document.getElementById('ic-resultado-conteudo');
    
    if (!btnImportar) return;
    
    if (!confirm('⚠️ ATENÇÃO!\n\nTem certeza que deseja importar os clientes deste arquivo?\n\nEsta ação irá inserir os dados na tabela de clientes.')) {
        return;
    }
    
    btnImportar.disabled = true;
    btnImportar.textContent = 'Importando...';
    
    if (mensagemDiv) {
        mensagemDiv.style.display = 'block';
        mensagemDiv.className = 'mensagem';
        mensagemDiv.textContent = 'Importando clientes...';
    }
    
    const formData = new FormData();
    formData.append('action', 'importar');
    formData.append('diretorio', diretorio);
    formData.append('arquivo', arquivo);
    formData.append('delimitador', delimitador);
    formData.append('primeira_linha_cabecalho', primeiraLinhaCabecalho ? '1' : '0');
    
    fetch('/SISIPTU/php/importar_clientes_api.php', {
        method: 'POST',
        body: formData
    })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                if (mensagemDiv) {
                    mensagemDiv.className = 'mensagem sucesso';
                    mensagemDiv.textContent = data.mensagem || 'Importação concluída com sucesso!';
                }
                
                if (resultadoSection && resultadoConteudo) {
                    resultadoSection.style.display = 'block';
                    resultadoConteudo.innerHTML = `
                        <div style="padding: 15px; background: #f0f0f0; border-radius: 5px;">
                            <p><strong>Total de linhas processadas:</strong> ${data.total_linhas || 0}</p>
                            <p><strong>Clientes importados com sucesso:</strong> <span style="color: #2d8659; font-weight: bold;">${data.importados || 0}</span></p>
                            ${data.ignorados && data.ignorados > 0 ? `
                                <p><strong>Registros ignorados (já existentes):</strong> <span style="color: #ff9800; font-weight: bold;">${data.ignorados}</span></p>
                            ` : ''}
                            ${data.erros && data.erros > 0 ? `
                                <p><strong>Erros:</strong> <span style="color: #dc3545; font-weight: bold;">${data.erros}</span></p>
                            ` : ''}
                            ${data.erros_detalhes && data.erros_detalhes.length > 0 ? `
                                <div style="margin-top: 10px;">
                                    <strong>Detalhes (ignorados e erros):</strong>
                                    <ul style="margin-top: 5px; margin-left: 20px; max-height: 300px; overflow-y: auto;">
                                        ${data.erros_detalhes.map(erro => {
                                            const cor = erro.includes('ignorado') ? '#ff9800' : '#dc3545';
                                            return `<li style="color: ${cor};">${erro}</li>`;
                                        }).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                        </div>
                    `;
                }
            } else {
                if (mensagemDiv) {
                    mensagemDiv.className = 'mensagem erro';
                    mensagemDiv.textContent = data.mensagem || 'Erro ao importar clientes.';
                }
            }
        })
        .catch(err => {
            console.error('Erro ao importar clientes:', err);
            if (mensagemDiv) {
                mensagemDiv.className = 'mensagem erro';
                mensagemDiv.textContent = 'Erro ao processar a importação: ' + err.message;
            }
        })
        .finally(() => {
            btnImportar.disabled = false;
            btnImportar.textContent = '📥 Importar Clientes';
        });
}

// ========== Baixa Manual (trabalha diretamente com tabela cobranca) ==========
function inicializarBaixaManual() {
    const tabelaBody = document.getElementById('tbody-baixa-manual');
    if (!tabelaBody) return;

    let cobrancaSelecionada = null;

    // Carregar empreendimentos
    carregarEmpreendimentosSelectBaixaManual();

    // Quando selecionar empreendimento, filtrar módulos
    const selectEmp = document.getElementById('bm-empreendimento');
    if (selectEmp) {
        selectEmp.addEventListener('change', function() {
            const empId = this.value;
            carregarModulosSelectBaixaManual(empId);
            const campoContrato = document.getElementById('bm-contrato');
            if (campoContrato) {
                campoContrato.value = '';
                campoContrato.style.borderColor = '#ccc';
            }
        });
    }

    // Quando selecionar módulo, limpar validação do contrato
    const selectMod = document.getElementById('bm-modulo');
    if (selectMod) {
        selectMod.addEventListener('change', function() {
            const campoContrato = document.getElementById('bm-contrato');
            if (campoContrato) {
                campoContrato.style.borderColor = '#ccc';
            }
        });
    }

    // Validação do campo contrato ao perder o foco ou pressionar Enter
    const campoContrato = document.getElementById('bm-contrato');
    if (campoContrato) {
        campoContrato.addEventListener('blur', function() {
            if (this.value.trim()) {
                validarContratoBaixaManual();
            } else {
                this.style.borderColor = '#ccc';
            }
        });
        
        campoContrato.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim()) {
                    validarContratoBaixaManual();
                }
            }
        });

        campoContrato.addEventListener('input', function() {
            // Resetar cor da borda enquanto digita
            if (this.style.borderColor === '#dc3545' || this.style.borderColor === '#28a745') {
                this.style.borderColor = '#ccc';
            }
        });
    }

    // Preencher data de pagamento e baixa com hoje
    const hoje = new Date();
    const dataFormatada = hoje.toISOString().split('T')[0];
    const campoDtPagtoInit = document.getElementById('bm-dt-pagto');
    const campoDataBaixa = document.getElementById('bm-data-baixa');
    if (campoDtPagtoInit) campoDtPagtoInit.value = dataFormatada;
    if (campoDataBaixa) campoDataBaixa.value = dataFormatada;

    // Botão Pesquisar Contrato
    const btnPesquisarContrato = document.getElementById('btn-pesquisar-contrato-bm');
    if (btnPesquisarContrato) {
        btnPesquisarContrato.addEventListener('click', function() {
            pesquisarContratoBaixaManual();
        });
    }

    // Radio buttons para tipo de operação
    const radioBaixar = document.getElementById('bm-tipo-baixar');
    const radioEstornar = document.getElementById('bm-tipo-estornar');
    
    // Função para revalidar título quando o tipo de operação mudar
    function revalidarTituloSePreenchido() {
        const numTitulo = document.getElementById('bm-num-titulo').value.trim();
        if (numTitulo) {
            validarTituloBaixaManual();
        }
    }
    
    if (radioBaixar) {
        radioBaixar.addEventListener('change', function() {
            if (this.checked) {
                revalidarTituloSePreenchido();
            }
        });
    }
    if (radioEstornar) {
        radioEstornar.addEventListener('change', function() {
            if (this.checked) {
                revalidarTituloSePreenchido();
            }
        });
    }

    // Validação do campo Nº do Título
    const campoNumTitulo = document.getElementById('bm-num-titulo');
    if (campoNumTitulo) {
        campoNumTitulo.addEventListener('blur', function() {
            if (this.value.trim()) {
                validarTituloBaixaManual();
            }
        });
        
        campoNumTitulo.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim()) {
                    validarTituloBaixaManual();
                }
            }
        });

        // Ocultar mensagem de erro quando começar a digitar
        campoNumTitulo.addEventListener('input', function() {
            const msgErroTitulo = document.getElementById('bm-titulo-msg-erro');
            if (msgErroTitulo) {
                msgErroTitulo.style.display = 'none';
                msgErroTitulo.textContent = '';
            }
            // Resetar cor da borda enquanto digita
            if (this.style.borderColor === '#dc3545' || this.style.borderColor === '#28a745') {
                this.style.borderColor = '#ccc';
            }
        });
    }

    // Recalcular juros e multas quando a data de pagamento mudar
    const campoDtPagtoRecalc = document.getElementById('bm-dt-pagto');
    if (campoDtPagtoRecalc) {
        campoDtPagtoRecalc.addEventListener('change', function() {
            const numTitulo = document.getElementById('bm-num-titulo').value.trim();
            if (numTitulo) {
                // Revalidar título para recalcular juros e multas com nova data
                validarTituloBaixaManual();
            }
        });
    }

    // Calcular valor a pagar automaticamente
    const camposValor = ['bm-valor-parcela', 'bm-multa', 'bm-juros', 'bm-tarifa-bancaria', 'bm-desconto'];
    camposValor.forEach(id => {
        const campo = document.getElementById(id);
        if (campo) {
            campo.addEventListener('input', calcularValorAPagar);
        }
    });

    // Botão Salvar
    const btnSalvarBaixa = document.getElementById('btn-salvar-baixa-manual');
    if (btnSalvarBaixa) {
        btnSalvarBaixa.addEventListener('click', function() {
            salvarBaixaManual();
        });
    }

    function limparFormularioBaixa() {
        document.getElementById('bm-num-titulo').value = '';
        document.getElementById('bm-dt-pagto').value = dataFormatada;
        document.getElementById('bm-data-baixa').value = dataFormatada;
        document.getElementById('bm-valor-parcela').value = '';
        document.getElementById('bm-multa').value = '';
        document.getElementById('bm-juros').value = '';
        document.getElementById('bm-tarifa-bancaria').value = '';
        document.getElementById('bm-desconto').value = '';
        document.getElementById('bm-valor-a-pagar').value = '0,00';
        document.getElementById('bm-forma-pagto').value = '';
        document.getElementById('bm-local-pg').value = '';
        document.getElementById('bm-observacao').value = '';
        
        // Limpar mensagem de erro do título
        const msgErroTitulo = document.getElementById('bm-titulo-msg-erro');
        if (msgErroTitulo) {
            msgErroTitulo.style.display = 'none';
            msgErroTitulo.textContent = '';
        }
        
        // Resetar estilo dos campos
        if (campoContrato) {
            campoContrato.style.borderColor = '#ccc';
        }
        if (campoNumTitulo) {
            campoNumTitulo.style.borderColor = '#ccc';
        }
    }

    function calcularValorAPagar() {
        const valorParcela = parseFloat(document.getElementById('bm-valor-parcela').value.replace(',', '.')) || 0;
        const multa = parseFloat(document.getElementById('bm-multa').value.replace(',', '.')) || 0;
        const juros = parseFloat(document.getElementById('bm-juros').value.replace(',', '.')) || 0;
        const tarifaBancaria = parseFloat(document.getElementById('bm-tarifa-bancaria').value.replace(',', '.')) || 0;
        const desconto = parseFloat(document.getElementById('bm-desconto').value.replace(',', '.')) || 0;

        const total = valorParcela + multa + juros + tarifaBancaria - desconto;
        document.getElementById('bm-valor-a-pagar').value = total.toFixed(2).replace('.', ',');
    }

    function validarTituloBaixaManual() {
        const empreendimentoId = document.getElementById('bm-empreendimento').value;
        const moduloId = document.getElementById('bm-modulo').value;
        const contrato = document.getElementById('bm-contrato').value.trim();
        const numTitulo = document.getElementById('bm-num-titulo').value.trim();
        const msgErroTitulo = document.getElementById('bm-titulo-msg-erro');

        // Limpar mensagem de erro anterior
        if (msgErroTitulo) {
            msgErroTitulo.style.display = 'none';
            msgErroTitulo.textContent = '';
        }

        if (!empreendimentoId || !moduloId || !contrato) {
            mostrarMensagemBaixaManual('Selecione Empreendimento, Módulo e Contrato antes de validar o título.', 'erro');
            return;
        }

        if (!numTitulo) {
            return;
        }

        const params = new URLSearchParams();
        params.append('action', 'buscar-por-titulo');
        params.append('empreendimento_id', empreendimentoId);
        params.append('modulo_id', moduloId);
        params.append('contrato', contrato);
        params.append('titulo', numTitulo);

        fetch(`/SISIPTU/php/baixa_manual_api.php?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                if (!data.sucesso || !data.cobranca) {
                    // Mostrar mensagem de erro na frente do campo título
                    if (msgErroTitulo) {
                        msgErroTitulo.textContent = 'Título não encontrado';
                        msgErroTitulo.style.display = 'block';
                    }
                    limparFormularioBaixa();
                    campoNumTitulo.style.borderColor = '#dc3545';
                    campoNumTitulo.focus();
                    return;
                }

                const cobranca = data.cobranca;
                
                // Verificar se está em modo estorno
                const tipoOperacao = document.querySelector('input[name="bm-tipo-operacao-radio"]:checked')?.value;
                
                if (tipoOperacao === 'estornar') {
                    // Modo estorno: preencher todos os campos com os dados da cobrança
                    document.getElementById('bm-valor-parcela').value = cobranca.valor_mensal ? parseFloat(cobranca.valor_mensal).toFixed(2).replace('.', ',') : '0,00';
                    document.getElementById('bm-dt-pagto').value = cobranca.datapagamento ? cobranca.datapagamento.split('T')[0] : '';
                    document.getElementById('bm-data-baixa').value = cobranca.databaixa ? cobranca.databaixa.split('T')[0] : '';
                    document.getElementById('bm-multa').value = cobranca.multas ? parseFloat(cobranca.multas).toFixed(2).replace('.', ',') : '0,00';
                    document.getElementById('bm-juros').value = cobranca.juros ? parseFloat(cobranca.juros).toFixed(2).replace('.', ',') : '0,00';
                    document.getElementById('bm-tarifa-bancaria').value = cobranca.tarifa_bancaria ? parseFloat(cobranca.tarifa_bancaria).toFixed(2).replace('.', ',') : '0,00';
                    document.getElementById('bm-desconto').value = cobranca.desconto ? parseFloat(cobranca.desconto).toFixed(2).replace('.', ',') : '0,00';
                    document.getElementById('bm-forma-pagto').value = cobranca.forma_pagamento || '';
                    document.getElementById('bm-local-pg').value = cobranca.local_pagamento || '';
                    document.getElementById('bm-observacao').value = cobranca.observacao || '';
                    calcularValorAPagar();
                } else {
                    // Modo baixar: preencher apenas valor mensal e calcular juros/multas se necessário
                    document.getElementById('bm-valor-parcela').value = cobranca.valor_mensal ? parseFloat(cobranca.valor_mensal).toFixed(2).replace('.', ',') : '0,00';
                    
                    // Verificar se está em atraso
                    const dtPagto = document.getElementById('bm-dt-pagto').value;
                    if (cobranca.datavencimento && dtPagto) {
                        const dataVencimento = new Date(cobranca.datavencimento);
                        const dataPagamento = new Date(dtPagto);
                        
                        if (dataVencimento < dataPagamento) {
                            mostrarMensagemBaixaManual('⚠️ Parcela em atraso! Calculando juros e multas...', 'erro');
                            
                            // Calcular juros e multas
                            calcularJurosMultasBaixaManual(cobranca, dtPagto);
                        } else {
                            // Limpar juros e multas se não estiver em atraso
                            document.getElementById('bm-multa').value = '0,00';
                            document.getElementById('bm-juros').value = '0,00';
                            calcularValorAPagar();
                        }
                    } else {
                        calcularValorAPagar();
                    }
                }
                
                // Limpar mensagem de erro e marcar como válido
                if (msgErroTitulo) {
                    msgErroTitulo.style.display = 'none';
                    msgErroTitulo.textContent = '';
                }
                campoNumTitulo.style.borderColor = '#28a745';
            })
            .catch(err => {
                console.error(err);
                if (msgErroTitulo) {
                    msgErroTitulo.textContent = 'Erro ao validar título';
                    msgErroTitulo.style.display = 'block';
                }
                mostrarMensagemBaixaManual('Erro ao validar título.', 'erro');
            });
    }

    function calcularJurosMultasBaixaManual(cobranca, dataPagamento) {
        const params = new URLSearchParams();
        params.append('action', 'calcular-juros-multas');
        params.append('cobranca_id', cobranca.id);
        params.append('data_pagamento', dataPagamento);

        fetch(`/SISIPTU/php/baixa_manual_api.php?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    document.getElementById('bm-multa').value = data.multa ? parseFloat(data.multa).toFixed(2).replace('.', ',') : '0,00';
                    document.getElementById('bm-juros').value = data.juros ? parseFloat(data.juros).toFixed(2).replace('.', ',') : '0,00';
                    calcularValorAPagar();
                } else {
                    // Calcular localmente em caso de erro
                    const valorMensal = parseFloat(cobranca.valor_mensal || 0);
                    const multa = valorMensal * 0.02; // 2% de multa
                    const diasAtraso = Math.ceil((new Date(dataPagamento) - new Date(cobranca.datavencimento)) / (1000 * 60 * 60 * 24));
                    const juros = valorMensal * (0.033 / 100) * diasAtraso; // 0,033% ao dia
                    
                    document.getElementById('bm-multa').value = multa.toFixed(2).replace('.', ',');
                    document.getElementById('bm-juros').value = juros.toFixed(2).replace('.', ',');
                    calcularValorAPagar();
                }
            })
            .catch(err => {
                console.error(err);
                // Calcular localmente em caso de erro
                const valorMensal = parseFloat(cobranca.valor_mensal || 0);
                const multa = valorMensal * 0.02; // 2% de multa
                const diasAtraso = Math.ceil((new Date(dataPagamento) - new Date(cobranca.datavencimento)) / (1000 * 60 * 60 * 24));
                const juros = valorMensal * (0.033 / 100) * diasAtraso; // 0,033% ao dia
                
                document.getElementById('bm-multa').value = multa.toFixed(2).replace('.', ',');
                document.getElementById('bm-juros').value = juros.toFixed(2).replace('.', ',');
                calcularValorAPagar();
            });
    }

    function validarContratoBaixaManual() {
        const empreendimentoId = document.getElementById('bm-empreendimento').value;
        const moduloId = document.getElementById('bm-modulo').value;
        const contrato = document.getElementById('bm-contrato').value.trim();

        if (!empreendimentoId || !moduloId) {
            return; // Não validar se não tiver empreendimento e módulo selecionados
        }

        if (!contrato) {
            return; // Não validar se o campo estiver vazio
        }

        const params = new URLSearchParams();
        params.append('action', 'validar-contrato');
        params.append('empreendimento_id', empreendimentoId);
        params.append('modulo_id', moduloId);
        params.append('contrato', contrato);

        fetch(`/SISIPTU/php/baixa_manual_api.php?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                if (!data.sucesso) {
                    mostrarMensagemBaixaManual(data.mensagem || 'Contrato não encontrado. Verifique se o número está correto.', 'erro');
                    campoContrato.style.borderColor = '#dc3545';
                    campoContrato.focus();
                } else {
                    campoContrato.style.borderColor = '#28a745';
                    mostrarMensagemBaixaManual('Contrato válido.', 'sucesso');
                }
            })
            .catch(err => {
                console.error(err);
                mostrarMensagemBaixaManual('Erro ao validar contrato.', 'erro');
            });
    }

    function pesquisarContratoBaixaManual() {
        const empreendimentoId = document.getElementById('bm-empreendimento').value;
        const moduloId = document.getElementById('bm-modulo').value;
        const contrato = document.getElementById('bm-contrato').value.trim();

        if (!empreendimentoId || !moduloId || !contrato) {
            mostrarMensagemBaixaManual('Selecione Empreendimento, Módulo e digite o Contrato.', 'erro');
            return;
        }

        const params = new URLSearchParams();
        params.append('action', 'pesquisar-contrato-completo');
        params.append('empreendimento_id', empreendimentoId);
        params.append('modulo_id', moduloId);
        params.append('contrato', contrato);

        mostrarMensagemBaixaManual('Pesquisando contrato...', 'info');

        fetch(`/SISIPTU/php/baixa_manual_api.php?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                if (!data.sucesso) {
                    mostrarMensagemBaixaManual(data.mensagem || 'Contrato não encontrado.', 'erro');
                    tabelaBody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px; border: 1px solid #ddd;">Nenhuma parcela encontrada.</td></tr>';
                    return;
                }

                const cobrancas = data.cobrancas || [];
                if (cobrancas.length === 0) {
                    mostrarMensagemBaixaManual('Nenhuma parcela encontrada para este contrato.', 'erro');
                    tabelaBody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 20px; border: 1px solid #ddd;">Nenhuma parcela encontrada.</td></tr>';
                    return;
                }

                exibirCobrancasNoGrid(cobrancas);
                mostrarMensagemBaixaManual(`${cobrancas.length} parcela(s) encontrada(s) para o contrato ${contrato}.`, 'sucesso');
            })
            .catch(err => {
                console.error(err);
                mostrarMensagemBaixaManual('Erro ao pesquisar contrato.', 'erro');
            });
    }

    function salvarBaixaManual() {
        const empreendimentoId = document.getElementById('bm-empreendimento').value;
        const moduloId = document.getElementById('bm-modulo').value;
        const contrato = document.getElementById('bm-contrato').value.trim();
        const numTitulo = document.getElementById('bm-num-titulo').value.trim();
        const tipoOperacao = document.querySelector('input[name="bm-tipo-operacao-radio"]:checked')?.value;

        // Validar campos obrigatórios
        if (!empreendimentoId || !moduloId || !contrato || !numTitulo) {
            mostrarMensagemBaixaManual('Preencha todos os campos obrigatórios (Empreendimento, Módulo, Contrato e Nº do Título).', 'erro');
            return;
        }

        // Verificar se uma opção está marcada
        if (!tipoOperacao || !['baixar', 'estornar'].includes(tipoOperacao)) {
            mostrarMensagemBaixaManual('Selecione a opção "Baixar Parcela" ou "Estornar Parcela" para salvar.', 'erro');
            return;
        }

        // Buscar cobrança pelo título
        const params = new URLSearchParams();
        params.append('action', 'buscar-por-titulo');
        params.append('empreendimento_id', empreendimentoId);
        params.append('modulo_id', moduloId);
        params.append('contrato', contrato);
        params.append('titulo', numTitulo);

        fetch(`/SISIPTU/php/baixa_manual_api.php?${params.toString()}`)
            .then(r => r.json())
            .then(data => {
                if (!data.sucesso || !data.cobranca) {
                    mostrarMensagemBaixaManual('Título não encontrado. Verifique se o número está correto.', 'erro');
                    const msgErroTitulo = document.getElementById('bm-titulo-msg-erro');
                    if (msgErroTitulo) {
                        msgErroTitulo.textContent = 'Título não encontrado';
                        msgErroTitulo.style.display = 'block';
                    }
                    return;
                }

                const cobrancaId = data.cobranca.id;
                
                // Coletar todos os valores do formulário
                const dtPagto = document.getElementById('bm-dt-pagto').value;
                const dataBaixa = document.getElementById('bm-data-baixa').value;
                const valorParcela = document.getElementById('bm-valor-parcela').value.replace(',', '.') || '0';
                const multa = document.getElementById('bm-multa').value.replace(',', '.') || '0';
                const juros = document.getElementById('bm-juros').value.replace(',', '.') || '0';
                const tarifaBancaria = document.getElementById('bm-tarifa-bancaria').value.replace(',', '.') || '0';
                const desconto = document.getElementById('bm-desconto').value.replace(',', '.') || '0';
                const formaPagto = document.getElementById('bm-forma-pagto').value;
                const localPg = document.getElementById('bm-local-pg').value;
                const observacao = document.getElementById('bm-observacao').value.trim();

                const formData = new FormData();
                formData.append('action', 'salvar-baixa-completa');
                formData.append('cobranca_id', cobrancaId);
                formData.append('tipo_operacao', tipoOperacao);
                formData.append('data_pagamento', dtPagto);
                formData.append('data_baixa', dataBaixa);
                formData.append('valor_parcela', valorParcela);
                formData.append('multa', multa);
                formData.append('juros', juros);
                formData.append('tarifa_bancaria', tarifaBancaria);
                formData.append('desconto', desconto);
                formData.append('forma_pagamento', formaPagto);
                formData.append('local_pagamento', localPg);
                formData.append('observacao', observacao);

                return fetch('/SISIPTU/php/baixa_manual_api.php', {
                    method: 'POST',
                    body: formData
                });
            })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) {
                    const mensagem = tipoOperacao === 'estornar' 
                        ? (data.mensagem || 'Parcela estornada com sucesso!')
                        : (data.mensagem || 'Parcela baixada com sucesso!');
                    mostrarMensagemBaixaManual(mensagem, 'sucesso');
                    limparFormularioBaixa();
                    pesquisarContratoBaixaManual();
                } else {
                    mostrarMensagemBaixaManual(data.mensagem || 'Erro ao salvar baixa.', 'erro');
                }
            })
            .catch(err => {
                console.error(err);
                mostrarMensagemBaixaManual('Erro ao salvar baixa.', 'erro');
            });
    }

    function excluirBaixaManual(cobrancaId) {
        const formData = new FormData();
        formData.append('action', 'baixar-estornar');
        formData.append('cobranca_id', cobrancaId);
        formData.append('tipo_operacao', 'estornar');
        formData.append('data_pagamento', '');
        formData.append('data_baixa', '');
        formData.append('observacao', 'Exclusão de baixa manual');

        fetch('/SISIPTU/php/baixa_manual_api.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                mostrarMensagemBaixaManual('Baixa excluída (estornada) com sucesso!', 'sucesso');
                limparFormularioBaixa();
                cobrancaSelecionada = null;
                pesquisarContratoBaixaManual();
            } else {
                mostrarMensagemBaixaManual(data.mensagem || 'Erro ao excluir baixa.', 'erro');
            }
        })
        .catch(err => {
            console.error(err);
            mostrarMensagemBaixaManual('Erro ao excluir baixa.', 'erro');
        });
    }

    function exibirCobrancasNoGrid(cobrancas) {
        const tabelaBody = document.getElementById('tbody-baixa-manual');
        if (!tabelaBody) return;

        tabelaBody.innerHTML = '';
        cobrancas.forEach(c => {
            const tr = document.createElement('tr');
            const valorTotal = calcularValorTotalCobrancaBaixa(c);
            const statusPago = c.pago === 'S' ? '<span style="color: green; font-weight: bold;">Pago</span>' : '<span style="color: red; font-weight: bold;">Não Pago</span>';
            
            tr.style.cursor = 'pointer';
            tr.onclick = function() {
                // Remover seleção anterior
                tabelaBody.querySelectorAll('tr').forEach(row => {
                    row.style.backgroundColor = '';
                });
                // Selecionar linha atual
                tr.style.backgroundColor = '#e3f2fd';
                
                // Preencher formulário
                preencherFormularioBaixa(c);
                cobrancaSelecionada = c;
            };
            
            tr.innerHTML = `
                <td style="padding: 8px; border: 1px solid #ddd;">${c.titulo || '-'}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${c.cliente_nome || '-'}</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">${c.valor_mensal ? parseFloat(c.valor_mensal).toFixed(2).replace('.', ',') : '0,00'}</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">${c.multas ? parseFloat(c.multas).toFixed(2).replace('.', ',') : '0,00'}</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">${c.juros ? parseFloat(c.juros).toFixed(2).replace('.', ',') : '0,00'}</td>
                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">${valorTotal}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${c.datavencimento ? formatarData(c.datavencimento) : '-'}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${c.datapagamento ? formatarData(c.datapagamento) : '-'}</td>
                <td style="padding: 8px; border: 1px solid #ddd;">${c.databaixa ? formatarData(c.databaixa) : '-'}</td>
            `;
            tabelaBody.appendChild(tr);
        });
    }

    function preencherFormularioBaixa(c) {
        document.getElementById('bm-num-titulo').value = c.titulo || '';
        document.getElementById('bm-dt-pagto').value = c.datapagamento ? c.datapagamento.split('T')[0] : new Date().toISOString().split('T')[0];
        document.getElementById('bm-data-baixa').value = c.databaixa ? c.databaixa.split('T')[0] : new Date().toISOString().split('T')[0];
        document.getElementById('bm-valor-parcela').value = c.valor_mensal ? parseFloat(c.valor_mensal).toFixed(2).replace('.', ',') : '';
        document.getElementById('bm-multa').value = c.multas ? parseFloat(c.multas).toFixed(2).replace('.', ',') : '';
        document.getElementById('bm-juros').value = c.juros ? parseFloat(c.juros).toFixed(2).replace('.', ',') : '';
        document.getElementById('bm-tarifa-bancaria').value = '';
        document.getElementById('bm-desconto').value = '';
        document.getElementById('bm-observacao').value = c.observacao || '';
        
        // Selecionar radio button baseado no status
        if (c.pago === 'S') {
            document.getElementById('bm-tipo-estornar').checked = true;
        } else {
            document.getElementById('bm-tipo-baixar').checked = true;
        }
        
        calcularValorAPagar();
    }

    function calcularValorTotalCobrancaBaixa(c) {
        const valorMensal = parseFloat(c.valor_mensal || 0);
        const multas = parseFloat(c.multas || 0);
        const juros = parseFloat(c.juros || 0);
        const total = valorMensal + multas + juros;
        return total.toFixed(2).replace('.', ',');
    }
}

// ========== COBRANÇA AUTOMÁTICA ==========
// Variável global para armazenar os títulos carregados
let titulosCobrancaAutomatica = [];

function inicializarCobrancaAutomatica() {
    carregarEmpreendimentosSelectCobrancaAutomatica();
    
    // Event listener para botão pesquisar
    const btnPesquisar = document.getElementById('btn-pesquisar-titulos');
    if (btnPesquisar) {
        btnPesquisar.addEventListener('click', function() {
            pesquisarTitulosCobrancaAutomatica();
        });
    }
    
    // Event listener para botão processar
    const btnProcessar = document.getElementById('btn-processar-cobranca');
    if (btnProcessar) {
        btnProcessar.addEventListener('click', function() {
            processarCobrancaAutomatica();
        });
    }
    
    // Event listeners para seleção
    const checkTodos = document.getElementById('check-todos-titulos');
    if (checkTodos) {
        checkTodos.addEventListener('change', function() {
            const checked = this.checked;
            document.querySelectorAll('#tabela-cobranca-automatica-body input[type="checkbox"][data-titulo-id]').forEach(cb => {
                cb.checked = checked;
            });
            atualizarBotaoProcessar();
        });
    }
    
    const btnSelecionarTodos = document.getElementById('btn-selecionar-todos');
    if (btnSelecionarTodos) {
        btnSelecionarTodos.addEventListener('click', function() {
            document.querySelectorAll('#tabela-cobranca-automatica-body input[type="checkbox"][data-titulo-id]').forEach(cb => {
                cb.checked = true;
            });
            if (checkTodos) checkTodos.checked = true;
            atualizarBotaoProcessar();
        });
    }
    
    const btnDeselecionarTodos = document.getElementById('btn-deselecionar-todos');
    if (btnDeselecionarTodos) {
        btnDeselecionarTodos.addEventListener('click', function() {
            document.querySelectorAll('#tabela-cobranca-automatica-body input[type="checkbox"][data-titulo-id]').forEach(cb => {
                cb.checked = false;
            });
            if (checkTodos) checkTodos.checked = false;
            atualizarBotaoProcessar();
        });
    }
    
    // Event listeners para atualizar grid quando filtros mudarem
    const selectEmpreendimento = document.getElementById('ca-empreendimento');
    const inputPeriodoInicio = document.getElementById('ca-periodo-inicio');
    const inputPeriodoFim = document.getElementById('ca-periodo-fim');
    
    if (selectEmpreendimento) {
        selectEmpreendimento.addEventListener('change', function() {
            const empreendimentoId = this.value;
            if (empreendimentoId) {
                carregarInfoBancoEmpreendimento(empreendimentoId);
            } else {
                ocultarInfoBanco();
            }
            // Limpar grid quando mudar empreendimento
            limparGridCobrancaAutomatica();
        });
    }
    
    if (inputPeriodoInicio) {
        inputPeriodoInicio.addEventListener('change', function() {
            limparGridCobrancaAutomatica();
        });
    }
    
    if (inputPeriodoFim) {
        inputPeriodoFim.addEventListener('change', function() {
            limparGridCobrancaAutomatica();
        });
    }
}

function atualizarBotaoProcessar() {
    const btnProcessar = document.getElementById('btn-processar-cobranca');
    if (!btnProcessar) return;
    
    const selecionados = document.querySelectorAll('#tabela-cobranca-automatica-body input[type="checkbox"][data-titulo-id]:checked');
    btnProcessar.disabled = selecionados.length === 0;
}

function carregarInfoBancoEmpreendimento(empreendimentoId) {
    if (!empreendimentoId) {
        ocultarInfoBanco();
        return;
    }
    
    // Buscar informações do empreendimento
    fetch(`/SISIPTU/php/empreendimentos_api.php?action=get&id=${empreendimentoId}`)
        .then(r => r.json())
        .then(data => {
            if (data.sucesso && data.empreendimento) {
                const bancoId = data.empreendimento.banco_id;
                
                if (bancoId) {
                    // Buscar informações do banco
                    fetch(`/SISIPTU/php/bancos_api.php?action=get&id=${bancoId}`)
                        .then(r => r.json())
                        .then(dataBanco => {
                            if (dataBanco.sucesso && dataBanco.banco) {
                                exibirInfoBanco(dataBanco.banco);
                            } else {
                                ocultarInfoBanco();
                            }
                        })
                        .catch(err => {
                            console.error('Erro ao buscar informações do banco:', err);
                            ocultarInfoBanco();
                        });
                } else {
                    ocultarInfoBanco();
                }
            } else {
                ocultarInfoBanco();
            }
        })
        .catch(err => {
            console.error('Erro ao buscar informações do empreendimento:', err);
            ocultarInfoBanco();
        });
}

function exibirInfoBanco(banco) {
    const infoBancoDiv = document.getElementById('ca-info-banco');
    const bancoNomeDiv = document.getElementById('ca-banco-nome');
    const bancoAgenciaDiv = document.getElementById('ca-banco-agencia');
    const bancoContaDiv = document.getElementById('ca-banco-conta');
    const caminhoRemessaDiv = document.getElementById('ca-caminho-remessa');
    
    if (infoBancoDiv && bancoNomeDiv && bancoAgenciaDiv && bancoContaDiv && caminhoRemessaDiv) {
        // Exibir nome do banco (prioridade: banco > apelido > id)
        const nomeBanco = banco.banco || banco.apelido || `Banco ID ${banco.id}`;
        bancoNomeDiv.textContent = nomeBanco;
        
        // Exibir agência
        const agencia = banco.agencia || 'Não informado';
        bancoAgenciaDiv.textContent = agencia;
        
        // Exibir conta corrente
        const conta = banco.conta || 'Não informado';
        bancoContaDiv.textContent = conta;
        
        // Exibir caminho da remessa
        const caminhoRemessa = banco.caminho_remessa || 'Não informado';
        caminhoRemessaDiv.textContent = caminhoRemessa;
        
        // Mostrar a seção
        infoBancoDiv.style.display = 'block';
    }
}

function ocultarInfoBanco() {
    const infoBancoDiv = document.getElementById('ca-info-banco');
    if (infoBancoDiv) {
        infoBancoDiv.style.display = 'none';
    }
    
    const bancoNomeDiv = document.getElementById('ca-banco-nome');
    const bancoAgenciaDiv = document.getElementById('ca-banco-agencia');
    const bancoContaDiv = document.getElementById('ca-banco-conta');
    const caminhoRemessaDiv = document.getElementById('ca-caminho-remessa');
    if (bancoNomeDiv) bancoNomeDiv.textContent = '-';
    if (bancoAgenciaDiv) bancoAgenciaDiv.textContent = '-';
    if (bancoContaDiv) bancoContaDiv.textContent = '-';
    if (caminhoRemessaDiv) caminhoRemessaDiv.textContent = '-';
}

function limparGridCobrancaAutomatica() {
    const tabelaBody = document.getElementById('tabela-cobranca-automatica-body');
    if (tabelaBody) {
        tabelaBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #666;">Selecione o empreendimento e o período de referência, depois clique em "Pesquisar Títulos".</td></tr>';
    }
    titulosCobrancaAutomatica = [];
    atualizarBotaoProcessar();
}

function pesquisarTitulosCobrancaAutomatica() {
    const empreendimentoId = document.getElementById('ca-empreendimento')?.value || '';
    const periodoInicio = document.getElementById('ca-periodo-inicio')?.value || '';
    const periodoFim = document.getElementById('ca-periodo-fim')?.value || '';
    const titulo = document.getElementById('ca-titulo')?.value.trim() || '';
    const contrato = document.getElementById('ca-contrato')?.value.trim() || '';
    const tabelaBody = document.getElementById('tabela-cobranca-automatica-body');
    const mensagemDiv = document.getElementById('ca-mensagem');
    
    // Validações
    if (!empreendimentoId) {
        mostrarMensagemCobrancaAutomatica('Selecione o empreendimento.', 'erro');
        return;
    }
    
    if (!periodoInicio || !periodoFim) {
        mostrarMensagemCobrancaAutomatica('Informe o período de referência (data início e data fim).', 'erro');
        return;
    }
    
    // Mostrar loading
    if (tabelaBody) {
        tabelaBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">Carregando...</td></tr>';
    }
    
    // Construir parâmetros
    const params = new URLSearchParams();
    params.append('action', 'pesquisar-titulos');
    params.append('empreendimento_id', empreendimentoId);
    params.append('periodo_inicio', periodoInicio);
    params.append('periodo_fim', periodoFim);
    if (titulo) params.append('titulo', titulo);
    if (contrato) params.append('contrato', contrato);
    
    fetch(`/SISIPTU/php/cobranca_automatica_api.php?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) {
                // Não sobrescrever mensagem de sucesso do processamento
                // Só mostrar mensagem de pesquisa se não houver mensagem de processamento
                const mensagemAtual = document.getElementById('ca-mensagem');
                if (!mensagemAtual || mensagemAtual.style.display === 'none' || !mensagemAtual.textContent.includes('Processados')) {
                    mostrarMensagemCobrancaAutomatica(`Encontrados ${data.total || 0} título(s).`, 'sucesso');
                }
                
                // Armazenar títulos globalmente
                titulosCobrancaAutomatica = data.titulos || [];
                
                // Atualizar grid
                if (tabelaBody) {
                    if (!data.titulos || data.titulos.length === 0) {
                        tabelaBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #666;">Nenhum título encontrado para os filtros selecionados.</td></tr>';
                        atualizarBotaoProcessar();
                    } else {
                        let html = '';
                        data.titulos.forEach((t, index) => {
                            const vencimento = t.datavencimento ? formatarData(t.datavencimento) : '-';
                            const valor = parseFloat(t.valor_mensal || 0).toFixed(2).replace('.', ',');
                            const tituloId = t.id;
                            
                            html += `
                                <tr>
                                    <td style="text-align: center;">
                                        <input type="checkbox" data-titulo-id="${tituloId}" data-titulo-index="${index}" style="cursor: pointer;">
                                    </td>
                                    <td>${t.titulo || t.id || '-'}</td>
                                    <td>${t.cliente_nome || '-'}</td>
                                    <td>${t.parcelamento || '-'}</td>
                                    <td>${vencimento}</td>
                                    <td style="text-align: right;">R$ ${valor}</td>
                                </tr>
                            `;
                        });
                        tabelaBody.innerHTML = html;
                        
                        // Adicionar event listeners aos checkboxes
                        document.querySelectorAll('#tabela-cobranca-automatica-body input[type="checkbox"][data-titulo-id]').forEach(cb => {
                            cb.addEventListener('change', function() {
                                atualizarCheckTodos();
                                atualizarBotaoProcessar();
                            });
                        });
                        
                        atualizarBotaoProcessar();
                    }
                }
            } else {
                mostrarMensagemCobrancaAutomatica(data.mensagem || 'Erro ao pesquisar títulos.', 'erro');
                if (tabelaBody) {
                    tabelaBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #d32f2f;">Erro ao pesquisar títulos.</td></tr>';
                }
                atualizarBotaoProcessar();
            }
        })
        .catch(err => {
            console.error('Erro ao pesquisar títulos:', err);
            mostrarMensagemCobrancaAutomatica('Erro ao pesquisar títulos. Verifique o console para mais detalhes.', 'erro');
            if (tabelaBody) {
                tabelaBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #d32f2f;">Erro ao pesquisar títulos.</td></tr>';
            }
        });
}

function atualizarCheckTodos() {
    const checkTodos = document.getElementById('check-todos-titulos');
    if (!checkTodos) return;
    
    const checkboxes = document.querySelectorAll('#tabela-cobranca-automatica-body input[type="checkbox"][data-titulo-id]');
    const todosSelecionados = checkboxes.length > 0 && Array.from(checkboxes).every(cb => cb.checked);
    checkTodos.checked = todosSelecionados;
}

function processarCobrancaAutomatica() {
    const selecionados = document.querySelectorAll('#tabela-cobranca-automatica-body input[type="checkbox"][data-titulo-id]:checked');
    
    if (selecionados.length === 0) {
        mostrarMensagemCobrancaAutomatica('Selecione pelo menos um título para processar.', 'erro');
        return;
    }
    
    // Coletar IDs dos títulos selecionados
    const titulosIds = Array.from(selecionados).map(cb => {
        const index = parseInt(cb.getAttribute('data-titulo-index'));
        return titulosCobrancaAutomatica[index];
    });
    
    // Coletar dados do formulário
    const empreendimentoId = document.getElementById('ca-empreendimento')?.value || '';
    const periodoInicio = document.getElementById('ca-periodo-inicio')?.value || '';
    const periodoFim = document.getElementById('ca-periodo-fim')?.value || '';
    const remissaoBoletos = document.getElementById('ca-remissao-boletos')?.checked || false;
    
    if (!empreendimentoId || !periodoInicio || !periodoFim) {
        mostrarMensagemCobrancaAutomatica('Preencha todos os campos obrigatórios.', 'erro');
        return;
    }
    
    // Confirmar processamento
    const confirmar = confirm(`Deseja processar ${titulosIds.length} título(s) selecionado(s)?`);
    if (!confirmar) {
        return;
    }
    
    // Mostrar loading
    mostrarMensagemCobrancaAutomatica('Processando títulos...', 'info');
    const btnProcessar = document.getElementById('btn-processar-cobranca');
    if (btnProcessar) {
        btnProcessar.disabled = true;
    }
    
    // Preparar dados para envio
    const dados = {
        action: 'processar',
        empreendimento_id: empreendimentoId,
        periodo_inicio: periodoInicio,
        periodo_fim: periodoFim,
        remissao_boletos: remissaoBoletos ? 1 : 0,
        titulos: titulosIds.map(t => ({
            id: t.id,
            titulo: t.titulo || t.id,
            contrato: t.contrato,
            cliente_nome: t.cliente_nome,
            modulo_id: t.modulo_id,
            valor_mensal: t.valor_mensal,
            datavencimento: t.datavencimento
        }))
    };
    
    // Mostrar mensagem de processamento
    mostrarMensagemCobrancaAutomatica('Processando títulos e gerando arquivo de remessa CNAB...', 'info');
    
    fetch('/SISIPTU/php/cobranca_automatica_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dados)
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            let mensagem = data.mensagem || `Processados ${titulosIds.length} título(s) com sucesso!`;
            
            // Adicionar informação sobre arquivo CNAB se foi gerado
            if (data.remessa_gerada && data.arquivo_cnab) {
                mensagem += `\n\n📄 Arquivo CNAB de remessa gerado: ${data.arquivo_cnab}`;
                if (data.caminho_cnab) {
                    mensagem += `\n📁 Salvo em: ${data.caminho_cnab}`;
                }
            }
            
            mostrarMensagemCobrancaAutomatica(mensagem, 'sucesso');
            
            // Atualizar grid removendo os processados, mas manter a mensagem
            setTimeout(() => {
                // Salvar a mensagem atual antes de pesquisar
                const mensagemAtual = document.getElementById('ca-mensagem');
                const mensagemTexto = mensagemAtual ? (mensagemAtual.innerHTML || mensagemAtual.textContent) : '';
                const mensagemTipo = mensagemAtual ? mensagemAtual.className.replace('mensagem ', '') : '';
                
                // Pesquisar títulos (isso pode limpar a mensagem temporariamente)
                pesquisarTitulosCobrancaAutomatica();
                
                // Restaurar a mensagem após a pesquisa
                setTimeout(() => {
                    if (mensagemTexto && mensagemAtual) {
                        mensagemAtual.innerHTML = mensagemTexto;
                        mensagemAtual.className = `mensagem ${mensagemTipo}`;
                        mensagemAtual.style.display = 'block';
                    }
                }, 500);
            }, 2000);
        } else {
            mostrarMensagemCobrancaAutomatica(data.mensagem || 'Erro ao processar títulos.', 'erro');
        }
    })
    .catch(err => {
        console.error('Erro ao processar títulos:', err);
        mostrarMensagemCobrancaAutomatica('Erro ao processar títulos. Verifique o console para mais detalhes.', 'erro');
    })
    .finally(() => {
        if (btnProcessar) {
            atualizarBotaoProcessar();
        }
    });
}

function mostrarMensagemCobrancaAutomatica(mensagem, tipo) {
    const mensagemDiv = document.getElementById('ca-mensagem');
    if (!mensagemDiv) return;
    
    // Se a mensagem contém quebras de linha, usar innerHTML para preservar
    if (mensagem.includes('\n')) {
        mensagemDiv.innerHTML = mensagem.replace(/\n/g, '<br>');
    } else {
        mensagemDiv.textContent = mensagem;
    }
    
    mensagemDiv.className = `mensagem ${tipo}`;
    mensagemDiv.style.display = 'block';
    
    // Scroll para a mensagem
    mensagemDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Não ocultar automaticamente - manter a mensagem visível
    // A mensagem só será substituída por uma nova mensagem ou removida manualmente
}

function inicializarRetornoBancario() {
    // Carregar bancos no select
    carregarBancosSelectRetornoBancario();
    
    // Event listener para botão processar
    const btnProcessar = document.getElementById('btn-processar-retorno');
    if (btnProcessar) {
        btnProcessar.addEventListener('click', function() {
            processarArquivoRetorno();
        });
    }
}

function carregarBancosSelectRetornoBancario() {
    const selectBanco = document.getElementById('rb-banco');
    if (!selectBanco) return;
    
    fetch('/SISIPTU/php/bancos_api.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (data.sucesso && data.bancos) {
                selectBanco.innerHTML = '<option value="">Selecione o banco...</option>';
                data.bancos.forEach(banco => {
                    const option = document.createElement('option');
                    option.value = banco.id;
                    option.textContent = banco.banco || banco.apelido || `Banco ${banco.id}`;
                    selectBanco.appendChild(option);
                });
            }
        })
        .catch(err => {
            console.error('Erro ao carregar bancos:', err);
        });
}

function processarArquivoRetorno() {
    const inputArquivo = document.getElementById('rb-arquivo');
    const selectBanco = document.getElementById('rb-banco');
    const inputDataMovimento = document.getElementById('rb-data-movimento');
    const tabelaBody = document.getElementById('tabela-retorno-bancario-body');
    const mensagemDiv = document.getElementById('rb-mensagem');
    
    if (!inputArquivo || !inputArquivo.files || inputArquivo.files.length === 0) {
        mostrarMensagemRetornoBancario('Selecione um arquivo para processar.', 'erro');
        return;
    }
    
    if (!selectBanco || !selectBanco.value) {
        mostrarMensagemRetornoBancario('Selecione o banco.', 'erro');
        return;
    }
    
    const arquivo = inputArquivo.files[0];
    const bancoId = selectBanco.value;
    const dataMovimento = inputDataMovimento ? inputDataMovimento.value : '';
    
    // Criar FormData para enviar arquivo
    const formData = new FormData();
    formData.append('arquivo', arquivo);
    formData.append('banco_id', bancoId);
    formData.append('data_movimento', dataMovimento);
    formData.append('action', 'processar');
    
    // Mostrar loading
    if (tabelaBody) {
        tabelaBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">Processando arquivo...</td></tr>';
    }
    
    fetch('/SISIPTU/php/retorno_bancario_api.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            mostrarMensagemRetornoBancario(data.mensagem || 'Arquivo processado com sucesso!', 'sucesso');
            
            // Atualizar tabela com resultados
            if (tabelaBody && data.registros) {
                if (data.registros.length === 0) {
                    tabelaBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #666;">Nenhum registro encontrado no arquivo.</td></tr>';
                } else {
                    let html = '';
                    data.registros.forEach(reg => {
                        html += `
                            <tr>
                                <td>${reg.linha || '-'}</td>
                                <td>${reg.tipo || '-'}</td>
                                <td>${reg.nosso_numero || '-'}</td>
                                <td>R$ ${parseFloat(reg.valor || 0).toFixed(2).replace('.', ',')}</td>
                                <td>${reg.data_pagamento || '-'}</td>
                                <td>${reg.status || '-'}</td>
                            </tr>
                        `;
                    });
                    tabelaBody.innerHTML = html;
                }
            }
        } else {
            mostrarMensagemRetornoBancario(data.mensagem || 'Erro ao processar arquivo.', 'erro');
            if (tabelaBody) {
                tabelaBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #d32f2f;">Erro ao processar arquivo.</td></tr>';
            }
        }
    })
    .catch(err => {
        console.error('Erro ao processar arquivo:', err);
        mostrarMensagemRetornoBancario('Erro ao processar arquivo. Verifique o console para mais detalhes.', 'erro');
        if (tabelaBody) {
            tabelaBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #d32f2f;">Erro ao processar arquivo.</td></tr>';
        }
    });
}

function mostrarMensagemRetornoBancario(mensagem, tipo) {
    const mensagemDiv = document.getElementById('rb-mensagem');
    if (!mensagemDiv) return;
    
    mensagemDiv.textContent = mensagem;
    mensagemDiv.className = `mensagem ${tipo}`;
    mensagemDiv.style.display = 'block';
    
    // Scroll para a mensagem
    mensagemDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Ocultar após 5 segundos se for sucesso
    if (tipo === 'sucesso') {
        setTimeout(() => {
            mensagemDiv.style.display = 'none';
        }, 5000);
    }
}

function carregarEmpreendimentosSelectCobrancaAutomatica() {
    const select = document.getElementById('ca-empreendimento');
    if (!select) return;

    select.innerHTML = '<option value="">Selecione o empreendimento...</option>';

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
            console.error('Erro ao carregar empreendimentos:', err);
        });
}

// Função removida - os campos de período agora são do tipo date (input type="date")

// Função removida - o campo de contrato agora é um input de texto digitável


function carregarEmpreendimentosSelectBaixaManual() {
    const select = document.getElementById('bm-empreendimento');
    if (!select) return;

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

function carregarModulosSelectBaixaManual(empreendimentoId = null) {
    const select = document.getElementById('bm-modulo');
    if (!select) return;

    select.innerHTML = '<option value="">Selecione...</option>';

    let url = '/SISIPTU/php/modulos_api.php?action=list';
    if (empreendimentoId) {
        url += '&empreendimento_id=' + encodeURIComponent(empreendimentoId);
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.sucesso) return;

            const mods = data.modulos || [];
            if (empreendimentoId) {
                mods.forEach(m => {
                    if (m.empreendimento_id == empreendimentoId) {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.nome;
                        select.appendChild(opt);
                    }
                });
            } else {
                mods.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.nome;
                    select.appendChild(opt);
                });
            }
        })
        .catch(err => {
            console.error(err);
        });
}

function mostrarMensagemBaixaManual(texto, tipo) {
    const msg = document.getElementById('bm-mensagem');
    if (!msg) return;

    if (!texto || !tipo) {
        msg.style.display = 'none';
        return;
    }

    msg.textContent = texto;
    msg.className = 'mensagem ' + tipo;
    msg.style.display = 'block';

    if (tipo === 'sucesso') {
        setTimeout(() => {
            msg.style.display = 'none';
        }, 5000);
    }
}


