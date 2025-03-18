<template>
    <div class="container">
        <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner"></div>
        </div>
        <!-- Formulário de cadastro -->
        <div class="form-container">
            <div class="form-group">
                <label>Nome do Fornecedor *</label>
                <input 
                    type="text" 
                    v-model="novoFornecedor.nome" 
                    class="form-control"
                    placeholder="Digite o nome do fornecedor"
                    required
                >
            </div>

            <div class="button-group">
                <button 
                    @click="cadastrarFornecedor" 
                    class="btn btn-primary"
                >
                    Cadastrar
                </button>
                <button 
                    @click="limparFormulario" 
                    class="btn btn-secondary"
                >
                    Limpar
                </button>
            </div>
        </div>

        <!-- Lista de fornecedores -->
        <div class="lista-container">
            <h2>Fornecedores Cadastrados</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="fornecedor in fornecedores" :key="fornecedor.id">
                        <td>{{ fornecedor.nome }}</td>
                        <td>
                            <button 
                                @click="excluirFornecedor(fornecedor.id)"
                                class="btn btn-danger btn-sm"
                            >
                                Excluir
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Fornecedores',
    data() {
        return {
            loading: false,
            novoFornecedor: {
                nome: ''
            },
            fornecedores: []
        }
    },
    created() {
        this.carregarFornecedores();
    },
    methods: {
        async carregarFornecedores() {
            this.loading = true;
            try {
                const response = await fetch('/api/fornecedores', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });
                if (response.ok) {
                    this.fornecedores = await response.json();
                }
            } catch (error) {
                console.error('Erro ao carregar fornecedores:', error);
                alert('Erro ao carregar fornecedores');
            }  finally {
                this.loading = false;
            }
        },
        async cadastrarFornecedor() {
            if (!this.novoFornecedor.nome) {
                alert('Por favor, preencha o nome do fornecedor');
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('/api/fornecedores', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    },
                    body: JSON.stringify(this.novoFornecedor)
                });

                if (response.ok) {
                    await this.carregarFornecedores();
                    this.limparFormulario();
                    alert('Fornecedor cadastrado com sucesso!');
                } else {
                    throw new Error('Erro ao cadastrar fornecedor');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao cadastrar fornecedor');
            }  finally {
                this.loading = false;
            }
        },
        async excluirFornecedor(id) {
            if (!confirm('Tem certeza que deseja excluir este fornecedor?')) {
                return;
            }

            try {
                const response = await fetch(`/api/fornecedores/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });

                if (response.ok) {
                    await this.carregarFornecedores();
                    alert('Fornecedor excluído com sucesso!');
                } else {
                    throw new Error('Erro ao excluir fornecedor');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao excluir fornecedor');
            }
        },
        limparFormulario() {
            this.novoFornecedor.nome = '';
        }
    }
}
</script>

<style scoped>

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #4338ca;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.form-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.lista-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.button-group {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary {
    background-color: #4285f4;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.table th,
.table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}
</style>