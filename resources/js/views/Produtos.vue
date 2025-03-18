<template>
    <div class="container">
        <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner"></div>
        </div>
        <div class="form-container">
            <form @submit.prevent="submitForm" class="produto-form">
                <div class="form-group">
                    <label for="nome">Nome do Produto *</label>
                    <input 
                        type="text" 
                        id="nome" 
                        v-model="form.nome"
                        :class="{ 'error': errors.nome }"
                        placeholder="Digite o nome do produto"
                    >
                    <span class="error-message" v-if="errors.nome">{{ errors.nome }}</span>
                </div>

                <div class="form-group">
                    <label for="valor">Valor (R$) *</label>
                    <input 
                        type="number" 
                        id="valor" 
                        v-model="form.valor"
                        :class="{ 'error': errors.valor }"
                        step="0.01"
                        min="0"
                        placeholder="0,00"
                    >
                    <span class="error-message" v-if="errors.valor">{{ errors.valor }}</span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        {{ editMode ? 'Atualizar' : 'Cadastrar' }}
                    </button>
                    <button type="button" @click="resetForm" class="btn btn-secondary">
                        Limpar
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Produtos -->
        <div class="produtos-list">
            <h2>Produtos Cadastrados</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Valor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="produto in produtos" :key="produto.id">
                            <td>{{ produto.id }}</td>
                            <td>{{ produto.nome }}</td>
                            <td>R$ {{ formatarValor(produto.valor) }}</td>
                            <td class="actions">
                                <button @click="editarProduto(produto)" class="btn btn-edit">
                                    Editar
                                </button>
                                <button @click="confirmarExclusao(produto)" class="btn btn-delete">
                                    Excluir
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!produtos.length">
                            <td colspan="4" class="text-center">Nenhum produto cadastrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div class="pagination-container">
                <div class="pagination-info">
                    Visualizando {{ paginationInfo }} produtos
                </div>
                <div class="pagination-controls">
                    <button 
                        @click="mudarPagina(currentPage - 1)" 
                        :disabled="currentPage === 1"
                        class="btn btn-secondary"
                    >
                        Anterior
                    </button>
                    <span class="page-info">Página {{ currentPage }} de {{ lastPage }}</span>
                    <button 
                        @click="mudarPagina(currentPage + 1)" 
                        :disabled="currentPage === lastPage"
                        class="btn btn-secondary"
                    >
                        Próxima
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Produtos',
    data() {
        return {
            loading: false,
            produtos: [],
            form: {
                nome: '',
                valor: ''
            },
            errors: {},
            editMode: false,
            editingId: null,
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: 10
        }
    },
    computed: {
        paginationInfo() {
            const start = ((this.currentPage - 1) * this.perPage) + 1;
            const end = Math.min(this.currentPage * this.perPage, this.total);
            return `${start}-${end} de ${this.total}`;
        }
    },
    methods: {
        formatarValor(valor) {
            return Number(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        validateForm() {
            this.errors = {};
            let isValid = true;

            if (!this.form.nome) {
                this.errors.nome = 'O nome é obrigatório';
                isValid = false;
            }

            if (!this.form.valor) {
                this.errors.valor = 'O valor é obrigatório';
                isValid = false;
            } else if (this.form.valor < 0) {
                this.errors.valor = 'O valor não pode ser negativo';
                isValid = false;
            }

            return isValid;
        },

        async loadProdutos() {
            this.loading = true;
            try {
                const response = await axios.get(`/api/produtos?page=${this.currentPage}`);
                this.produtos = response.data.data;
                this.currentPage = response.data.current_page;
                this.lastPage = response.data.last_page;
                this.total = response.data.total;
                this.perPage = response.data.per_page;
            } catch (error) {
                console.error('Erro ao carregar produtos:', error);
                this.produtos = [];
            }  finally {
                this.loading = false;
            }
        },

        async submitForm() {
                if (this.validateForm()) {
                    this.loading = true;
                    try {
                        // Converte o valor para número antes de enviar
                        const produtoData = {
                            nome: this.form.nome,
                            valor: parseFloat(this.form.valor)
                        };


                if (this.editMode) {
                    await axios.put(`/api/produtos/${this.editingId}`, produtoData);
                    alert('Produto atualizado com sucesso!');
                } else {
                    await axios.post('/api/produtos', produtoData);
                    alert('Produto cadastrado com sucesso!');
                }
                    this.resetForm();
                    await this.loadProdutos();
                } catch (error) {
                    console.error('Erro completo:', error.response || error);
                    const message = error.response?.data?.message || 'Erro ao processar a requisição';
                    alert('Erro: ' + message);
                }  finally {
                    this.loading = false;
                }
            }   
        },

        editarProduto(produto) {
            this.form = {
                nome: produto.nome,
                valor: produto.valor
            };
            this.editMode = true;
            this.editingId = produto.id;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        async confirmarExclusao(produto) {
            if (confirm(`Deseja realmente excluir o produto ${produto.nome}?`)) {
                try {
                    await axios.delete(`/api/produtos/${produto.id}`);
                    alert('Produto excluído com sucesso!');
                    await this.loadProdutos();
                } catch (error) {
                    console.error('Erro ao excluir produto:', error);
                    alert('Erro ao excluir o produto');
                }
            }
        },

        resetForm() {
            this.form = {
                nome: '',
                valor: ''
            };
            this.errors = {};
            this.editMode = false;
            this.editingId = null;
        },

        async mudarPagina(page) {
            if (page >= 1 && page <= this.lastPage) {
                this.currentPage = page;
                await this.loadProdutos();
                this.$el.querySelector('.produtos-list').scrollIntoView({ behavior: 'smooth' });
            }
        }
    },
    created() {
        this.loadProdutos();
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

.container {
    padding: 20px;
    max-width: 800px;
    margin: 0 auto;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.form-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.cliente-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

label {
    font-weight: bold;
    color: #333;
}

input {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

input.error {
    border-color: #dc3545;
}

.error-message {
    color: #dc3545;
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
.clientes-list {
    margin-top: 40px;
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.actions {
    display: flex;
    gap: 8px;
}

.btn-edit {
    background: #28a745;
    color: white;
}

.btn-delete {
    background: #dc3545;
    color: white;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 20px;
    padding: 20px;
}

@media (max-width: 768px) {
    table {
        font-size: 14px;
    }
    
    th, td {
        padding: 8px;
    }

    .actions {
        flex-direction: column;
    }
}
.text-center {
    text-align: center;
}
.pagination-container {
    margin-top: 20px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.pagination-info {
    text-align: center;
    margin-bottom: 15px;
    color: #666;
}

.pagination-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: background-color 0.2s;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover:not(:disabled) {
    background: #5a6268;
}

.page-info {
    color: #495057;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .pagination-controls {
        flex-direction: column;
        gap: 10px;
    }
}
</style>