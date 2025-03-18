<template>
    <div class="container">
        <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner"></div>
        </div>
        <!-- Barra de ações -->
        <div class="actions-bar">
            <button @click="openModal" class="btn-novo">
                Novo
            </button>
        </div>

        <!-- Lista de Clientes -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>CPF/CNPJ</th>
                        <th>Endereço</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="cliente in clientes" :key="cliente.id">
                        <td>{{ cliente.nome }}</td>
                        <td>{{ cliente.email }}</td>
                        <td>{{ cliente.telefone }}</td>
                        <td>{{ cliente.cpf_cnpj }}</td>
                        <td>{{ cliente.endereco }}</td>
                        <td class="actions">
                            <button @click="editarCliente(cliente)" class="btn-edit">
                                Editar
                            </button>
                            <button @click="confirmarExclusao(cliente)" class="btn-delete">
                                Excluir
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginação -->
            <div class="pagination-container">
                <div class="pagination-info">
                    Visualizando {{ paginationInfo }}
                </div>
                <div class="pagination-controls">
                    <button 
                        class="btn-pagination"
                        :disabled="currentPage === 1"
                        @click="mudarPagina(currentPage - 1)"
                    >
                        Anterior
                    </button>
                    <span class="page-info">Página {{ currentPage }} de {{ lastPage }}</span>
                    <button 
                        class="btn-pagination"
                        :disabled="currentPage === lastPage"
                        @click="mudarPagina(currentPage + 1)"
                    >
                        Próxima
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Formulário -->
        <transition name="modal">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <h2>{{ editMode ? 'Editar Cliente' : 'Novo Cliente' }}</h2>
                        <button @click="closeModal" class="close-button">&times;</button>
                    </div>
                    
                    <div class="modal-body">
                        <form @submit.prevent="submitForm" class="cliente-form">
                            <div class="form-group">
                                <label for="nome">Nome *</label>
                                <input 
                                    type="text" 
                                    id="nome" 
                                    v-model="form.nome"
                                    :class="{ 'error': errors.nome }"
                                    placeholder="Digite o nome completo"
                                >
                                <span class="error-message" v-if="errors.nome">{{ errors.nome }}</span>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    v-model="form.email"
                                    :class="{ 'error': errors.email }"
                                    placeholder="exemplo@email.com"
                                >
                                <span class="error-message" v-if="errors.email">{{ errors.email }}</span>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="telefone">Telefone</label>
                                    <input 
                                        type="text" 
                                        id="telefone" 
                                        v-model="form.telefone"
                                        :class="{ 'error': errors.telefone }"
                                        placeholder="(00) 00000-0000"
                                        @input="formatTelefone"
                                    >
                                    <span class="error-message" v-if="errors.telefone">{{ errors.telefone }}</span>
                                </div>

                                <div class="form-group">
                                    <label for="cpf_cnpj">CPF/CNPJ</label>
                                    <input 
                                        type="text" 
                                        id="cpf_cnpj" 
                                        v-model="form.cpf_cnpj"
                                        :class="{ 'error': errors.cpf_cnpj }"
                                        placeholder="000.000.000-00"
                                        @input="formatCpfCnpj"
                                    >
                                    <span class="error-message" v-if="errors.cpf_cnpj">{{ errors.cpf_cnpj }}</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="endereco">Endereço</label>
                                <input 
                                    type="text" 
                                    id="endereco" 
                                    v-model="form.endereco"
                                    :class="{ 'error': errors.endereco }"
                                    placeholder="Digite o endereço completo"
                                >
                                <span class="error-message" v-if="errors.endereco">{{ errors.endereco }}</span>
                            </div>

                            <div class="modal-actions">
                                <button type="submit" class="btn btn-primary">
                                    {{ editMode ? 'Atualizar' : 'Cadastrar' }}
                                </button>
                                <button type="button" @click="closeModal" class="btn btn-secondary">
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
export default {
    name: 'Clientes',
    data() {
        return {
            loading: false,
            showModal: false,
            clientes: [],
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: 10,
            form: {
                nome: '',
                email: '',
                telefone: '',
                cpf_cnpj: '',
                endereco: '',
            },
            errors: {},
            editMode: false,
            editingId: null,
            currentPage: 1,
            lastPage: 1
        }
    },
    async created() {
        await this.loadClientes();
    },
    
    computed: {
        paginationInfo() {
            const start = ((this.currentPage - 1) * this.perPage) + 1;
            const end = Math.min(this.currentPage * this.perPage, this.total);
            return `${start}-${end} de ${this.total} clientes`;
        }
    },
    methods: {

        openModal() {
            this.showModal = true;
            this.editMode = false;
            this.resetForm();
        },

        closeModal() {
            this.showModal = false;
            this.resetForm();
        },


        validateForm() {
            this.errors = {};
            let isValid = true;

            if (!this.form.nome) {
                this.errors.nome = 'O nome é obrigatório';
                isValid = false;
            }

            if (this.form.email && !this.validateEmail(this.form.email)) {
                this.errors.email = 'Email inválido';
                isValid = false;
            }

            return isValid;
        },

        validateEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        formatTelefone() {
            let value = this.form.telefone.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
            }
            this.form.telefone = value;
        },

        formatCpfCnpj() {
            let value = this.form.cpf_cnpj.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
            }
            this.form.cpf_cnpj = value;
        },

        async submitForm() {
            if (this.validateForm()) {
                this.loading = true;
        try {
            if (this.editMode) {
                await axios.put(`/api/clientes/${this.editingId}`, this.form);
                alert('Cliente atualizado com sucesso!');
            } else {
                const response = await axios.post('/api/clientes', this.form);
                alert('Cliente cadastrado com sucesso!');
            }
            this.closeModal();
            await this.loadClientes();
        } catch (error) {
            if (error.response?.data.errors) {
                const errorMessages = Object.values(error.response.data.errors)
                    .flat()
                    .join('\n');
                alert('Erros de validação:\n' + errorMessages);
            } else {
                alert('Erro ao processar a requisição');
            }
            console.error('Erro completo:', error.response?.data);
            }  finally {
                this.loading = false;
            }
            }
        },
        mounted() {
            this.loadClientes();
        },

        resetForm() {
            this.form = {
                nome: '',
                email: '',
                telefone: '',
                cpf_cnpj: '',
                endereco: ''
            };
            this.errors = {};
            this.editMode = false;
        },
        async loadClientes() {
            this.loading = true;
            try {
                const response = await axios.get(`/api/clientes?page=${this.currentPage}`);
                this.clientes = response.data.data;
                this.currentPage = response.data.current_page;
                this.lastPage = response.data.last_page;
                this.total = response.data.total;
                this.perPage = response.data.per_page;
            } catch (error) {
                console.error('Erro ao carregar clientes:', error);
                this.clientes = [];
            }finally {
                this.loading = false;
            }
        },

        async mudarPagina(page) {
            if (page >= 1 && page <= this.lastPage) {
                this.currentPage = page;
                await this.loadClientes();
                
                this.$el.querySelector('.clientes-list').scrollIntoView({ behavior: 'smooth' });
            }
        },

        editarCliente(cliente) {
            this.form = { ...cliente };
            this.editMode = true;
            this.editingId = cliente.id;
            this.showModal = true;
        },

        async confirmarExclusao(cliente) {
            if (confirm(`Deseja realmente excluir o cliente ${cliente.nome}?`)) {
                try {
                    await axios.delete(`/api/clientes/${cliente.id}`);
                    alert('Cliente excluído com sucesso!');
                    await this.loadClientes();
                } catch (error) {
                    console.error('Erro ao excluir cliente:', error);
                    alert('Erro ao excluir o cliente');
                }
            }
        },
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

.actions-bar {
    background: white;
    padding: 16px 24px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-novo {
    background-color: #4338ca;
    color: white;
    border: none;
    padding: 8px 24px;
    border-radius: 4px;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-novo:hover {
    background-color: #1000be;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 20px;
    overflow-y: auto;
    z-index: 1000;
}

.modal-container {
    background: white;
    border-radius: 8px;
    width: 100%;
    max-width: 800px;
    margin-top: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-body {
    padding: 24px;
}

.close-button {
    background: none;
    border: none;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.btn-pagination {
    padding: 8px 16px;
    border: 1px solid #e5e7eb;
    background-color: white;
    color: #374151;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
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
    background: #7bff00;
    color: white;
}

.btn-secondary {
    background: #ff0000;
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
    border-radius: 4px;
    border: none;
    font-weight: 500;
    font-size: 13px;
    color: white;
}

.btn-delete {
    background: #dc3545;
    border-radius: 4px;
    border: none;
    font-weight: 500;
    font-size: 13px;
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