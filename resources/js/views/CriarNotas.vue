<template>    
    <div class="container">
            <!-- Loading Overlay -->
            <div v-if="loading" class="loading-overlay">
                <div class="loading-spinner"></div>
            </div>
                    <div class="actions-bar">
                        <!-- Barra de ações -->
                        <div class="actions-top">
                            <button @click="showModal = true" class="btn-novo">
                                Novo
                            </button>
                        </div>
                        <!-- Filtros de buscas de notas -->
                        <div class="filter-section">
                            <div class="filter-grid">
                                <div class="filter-item">
                                    <label>Nome do Cliente</label>
                                    <input 
                                        type="text" 
                                        v-model="filters.nome_cliente" 
                                        placeholder="Buscar por cliente"
                                        class="filter-input"
                                    >
                                </div>
                                
                                <div class="filter-item">
                                    <label>Número da Nota</label>
                                    <input 
                                        type="text" 
                                        v-model="filters.numero_nota" 
                                        placeholder="Buscar por número"
                                        class="filter-input"
                                    >
                                </div>
                                
                                <div class="filter-item">
                                    <label>Data Início</label>
                                    <input 
                                        type="date" 
                                        v-model="filters.data_inicio"
                                        class="filter-input"
                                    >
                                </div>
                                
                                <div class="filter-item">
                                    <label>Data Fim</label>
                                    <input 
                                        type="date" 
                                        v-model="filters.data_fim"
                                        class="filter-input"
                                    >
                                </div>

                                <div class="filter-item">
                                    <label for="status">Status</label>
                                    <select 
                                        id="status"
                                        v-model="filters.status"
                                        class="filter-input"
                                    >
                                        <option value="">Todos</option>
                                        <option value="ABERTA">Aberta</option>
                                        <option value="PAGA">Paga</option>
                                        <option value="PAGA PARCIALMENTE">Paga Parcialmente</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="filter-actions">
                                <button 
                                @click="aplicarFiltros" 
                                class="btn-filter"
                                :disabled="isLoading"
                                >
                                    {{ isLoading ? 'Filtrando...' : 'Filtrar' }}
                                </button>
                                <button 
                                @click="limparFiltros" 
                                class="btn-filter clear"
                                :disabled="isLoading"
                                >
                                    Limpar Filtros
                                </button>
                            </div>
                        </div>
                    </div>

                <!-- Lista de Notas -->
                <div class="notas-list">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>N° da Nota</th>
                                    <th>Cliente</th>
                                    <th>Data</th>
                                    <th>Valor Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                    <tr v-for="nota in notas" :key="nota.id">
                                        <td>{{ nota.numero_nota }}</td>
                                        <td>
                                            <router-link 
                                                :to="{ name: 'clientes', params: { clienteId: nota.cliente_id }}"
                                                class="client-link"
                                            >
                                                {{ nota.cliente?.nome }}
                                            </router-link>
                                        </td>
                                        <td>{{ formatarData(nota.data) }}</td>
                                        <td>R$ {{ formatarValor(nota.valor_total) }}</td>
                                        <td class="actions">
                                            <button @click="editarNota(nota)" class="btn-edit">
                                                Editar
                                            </button>
                                            <button @click="confirmarExclusao(nota)" class="btn btn-delete">
                                                Excluir
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!notas.length">
                                        <td colspan="6" class="text-center">Nenhuma nota cadastrada.</td>
                                    </tr>
                            </tbody>
                        </table>
                </div>
                
                <!-- Paginação -->
                <div class="pagination-container" v-show="notas.length > 0">
                    <div class="pagination-info">
                        Visualizando {{ paginationInfo }}
                    </div>
                    <div class="pagination-controls">
                        <button 
                            @click="mudarPagina(currentPage - 1)" 
                            :disabled="currentPage === 1"
                            class="btn-pagination"
                        >
                            Anterior
                        </button>
                        <span class="page-info">Página {{ currentPage }} de {{ lastPage }}</span>
                        <button 
                            @click="mudarPagina(currentPage + 1)" 
                            :disabled="currentPage === lastPage"
                            class="btn-pagination"
                        >
                            Próxima
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal do Formulário -->
        <transition name="modal">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <h2>{{ editMode ? 'Editar Nota' : 'Nova Nota' }}</h2>
                        <button @click="closeModal" class="close-button">&times;</button>
                    </div>
                    
                    <div class="modal-body">
                        <form @submit.prevent="submitForm" class="nota-form">
                            <!-- Campo de Cliente -->
                            <div class="form-group">
                                <label for="cliente">Cliente *</label>
                                <div class="autocomplete-wrapper" ref="autocomplete">
                                    <input 
                                        type="text"
                                        v-model="searchCliente"
                                        @input="filterClientes"
                                        class="form-control"
                                        placeholder="Digite para buscar cliente"
                                        :class="{ 'error': errors.cliente_id }"
                                    >
                                    <div v-if="showResults" class="autocomplete-results">
                                        <div v-if="filteredClientes.length === 0" class="autocomplete-item no-results">
                                            Nenhum cliente encontrado
                                        </div>
                                        <div
                                            v-else
                                            v-for="cliente in filteredClientes"
                                            :key="cliente.id"
                                            @click="selectCliente(cliente)"
                                            class="autocomplete-item"
                                        >
                                            {{ cliente.nome }}
                                        </div>
                                    </div>
                                </div>
                                <span class="error-message" v-if="errors.cliente_id">
                                    {{ errors.cliente_id }}
                                </span>
                            </div>

                            <div class="form-row">
                                <!-- Campo de Data -->
                                <div class="form-group">
                                    <label for="data">Data *</label>
                                    <input 
                                        type="date" 
                                        id="data" 
                                        v-model="form.data"
                                        :class="{ 'error': errors.data }"
                                        :max="getCurrentDate()"
                                    >
                                    <span class="error-message" v-if="errors.data">{{ errors.data }}</span>
                                </div>

                                <!-- Número da Nota -->
                                <div class="form-group">
                                    <label for="numero_nota">N° da Nota *</label>
                                    <input 
                                        type="text" 
                                        id="numero_nota" 
                                        v-model="form.numero_nota"
                                        :class="{ 'error': errors.numero_nota }"
                                        placeholder="Digite o número da nota"
                                    >
                                    <span class="error-message" v-if="errors.numero_nota">
                                        {{ errors.numero_nota }}
                                    </span>
                                </div>
                            </div>

                            <!-- Seção de Itens -->
                            <div class="form-group items-section">
                                <label>Itens da Nota *</label>
                                <div v-for="(item, index) in form.items" :key="index" class="item-row">
                                    <div class="item-fields">
                                        <div class="field">
                                            <label>Quantidade (KG)</label>
                                            <input 
                                                type="number" 
                                                v-model="item.kg"
                                                step="0.01"
                                                min="0"
                                                @input="calcularTotal(index)"
                                                placeholder="0.00"
                                            >
                                        </div>
                                        <div class="field">
                                            <label>Valor por KG</label>
                                            <input 
                                                type="number" 
                                                v-model="item.valor_kg"
                                                step="0.01"
                                                min="0"
                                                @input="calcularTotal(index)"
                                                placeholder="0.00"
                                            >
                                        </div>
                                        <div class="field">
                                            <label>Valor Total</label>
                                            <input 
                                                type="number" 
                                                v-model="item.valor_total"
                                                step="0.01"
                                                readonly
                                            >
                                        </div>
                                        <button 
                                            type="button" 
                                            @click="removerItem(index)" 
                                            class="btn btn-danger"
                                            v-if="form.items.length > 1"
                                        >
                                            Remover
                                        </button>
                                    </div>
                                </div>
                                <button type="button" @click="adicionarItem" class="btn btn-secondary mt-2">
                                    Adicionar Item
                                </button>
                            </div>

                            <!-- Valor Total da Nota -->
                            <div class="form-group total-section">
                                <label for="valor_total">Valor Total da Nota (R$)</label>
                                <input 
                                    type="number" 
                                    id="valor_total" 
                                    v-model="form.valor_total"
                                    class="readonly-input"
                                    step="0.01"
                                    readonly
                                >
                            </div>

                            <!-- Botões do formulário -->
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    {{ editMode ? 'Atualizar' : 'Criar Nota' }}
                                </button>
                                <button type="button" @click="resetForm" class="btn btn-secondary">
                                    Limpar
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
    name: 'CriarNotas',
    data() {
        return {
            loading: false,
            clientes: [],
            notas: [],
            form: {
                cliente_id: '',
                numero_nota: '',
                data: '',
                items: [
                    {
                        kg: '',
                        valor_kg: '',
                        valor_total: ''
                    }
                ]
            },
            showModal: false, 
            errors: {},
            editMode: false,
            editingId: null,
            currentPage: 1,
            lastPage: 1,
            total: 0,
            perPage: 10,
            searchCliente: '',
            filteredClientes: [],
            showResults: false,
            selectedCliente: null,
            filters: {
                nome_cliente: '',
                numero_nota: '',
                data_inicio: '',
                data_fim: '',
                status: ''
            },
            isLoading: false      
        }
    },
    computed: {
        paginationInfo() {
            const start = ((this.currentPage - 1) * this.perPage) + 1;
            const end = Math.min(this.currentPage * this.perPage, this.total);
            return `${start}-${end} de ${this.total} notas`;
        }
    },

    mounted() {
        this.$nextTick(() => {
            document.addEventListener('click', this.handleClickOutside);
        });
    },

    beforeDestroy() {
        document.removeEventListener('click', this.handleClickOutside);
    },

    created() {
        this.form.data = this.getCurrentDate();
        this.loadClientes();
        this.loadNotas();
    },

    methods: {

        getCurrentDate() {
            return new Date().toISOString().split('T')[0];
        },

        closeModal() {
            this.showModal = false;
            this.resetForm();
            this.editMode = false;
            this.editingId = null;
        },
        
        formatarData(data) {
            try {
                // Remove a parte do timestamp e mantém só a data
                const dataPura = data.split('T')[0];
                const [ano, mes, dia] = dataPura.split('-');
                return `${dia}/${mes}/${ano}`;
            } catch (error) {
                console.error('Erro ao formatar data:', error);
                return data; // Retorna a data original em caso de erro
            }
        },

        calcularValorTotal() {
            if (this.form.kg && this.form.valor_kg) {
                const kg = parseFloat(this.form.kg);
                const valorKg = parseFloat(this.form.valor_kg);
                this.form.valor_total = (kg * valorKg).toFixed(2);
            } else {
                this.form.valor_total = '';
            }
        },
        formatarValor(valor) {
            return Number(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        async loadClientes() {
            this.loading = true;
            try {
                const response = await axios.get('/api/clientes/list');
                this.clientes = response.data;
                this.filteredClientes = response.data;
            } catch (error) {
                console.error('Erro ao carregar clientes:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadNotas() {
            this.isLoading = true;
            try {

                const params = {
                    page: this.currentPage
                };

                if (this.filters.nome_cliente?.trim()) {
                params.nome_cliente = this.filters.nome_cliente.trim();
                }
                if (this.filters.numero_nota?.trim()) {
                    params.numero_nota = this.filters.numero_nota.trim();
                }
                if (this.filters.data_inicio) {
                    params.data_inicio = this.filters.data_inicio;
                }
                if (this.filters.data_fim) {
                    params.data_fim = this.filters.data_fim;
                }
                if (this.filters.status) {
                    params.status = this.filters.status;
            }

                const response = await axios.get('/api/notas', { params });
                
                this.notas = response.data.data;
                this.currentPage = response.data.current_page;
                this.lastPage = response.data.last_page;
                this.total = response.data.total;
                this.perPage = response.data.per_page;

            } catch (error) {
                console.error('Erro ao carregar notas:', error);
                alert('Erro ao carregar notas');
            } finally {
                this.isLoading = false;
            }
        },

        async aplicarFiltros() {
            this.currentPage = 1;
            await this.loadNotas();
        },

        limparFiltros() {
            this.filters = {
                nome_cliente: '',
                numero_nota: '',
                data_inicio: '',
                data_fim: '',
                status: ''
            };
            this.aplicarFiltros();
        },

        validateForm() {
            this.errors = {};
            let isValid = true;

            if (!this.form.cliente_id) {
                this.errors.cliente_id = 'Selecione um cliente';
                isValid = false;
            }
            if (!this.form.data) {
            this.errors.data = 'A data é obrigatória';
            isValid = false;
            }

            if (!this.form.numero_nota) {
                this.errors.numero_nota = 'Digite o número da nota';
                isValid = false;
            }
            if (!this.form.items.length) {
                this.errors.items = 'Adicione pelo menos um item';
                isValid = false;
            }
            
            this.form.items.forEach((item, index) => {
                if (!item.kg) {
                    this.errors[`item_${index}_kg`] = 'Quantidade em KG é obrigatória';
                    isValid = false;
                }
                if (!item.valor_kg) {
                    this.errors[`item_${index}_valor_kg`] = 'Valor por KG é obrigatório';
                    isValid = false;
                }
            });

            if (!isValid) {
                console.error('Erros de validação:', this.errors);
            }

            return isValid;
        },

        async submitForm() {
            if (this.validateForm()) {
                this.loading = true;
                try {
       
                    const valorTotal = this.form.items.reduce((total, item) => {
                        return total + (parseFloat(item.valor_total) || 0);
                    }, 0);

                    const dadosNota = {
                        cliente_id: this.form.cliente_id,
                        numero_nota: this.form.numero_nota,
                        data: this.form.data,
                        items: this.form.items.map(item => ({
                            kg: parseFloat(item.kg),
                            valor_kg: parseFloat(item.valor_kg),
                            valor_total: parseFloat(item.valor_total)
                        })),
                        valor_total: valorTotal
                    };


                    let response;

                    if (this.editMode && this.editingId) {
                        await axios.put(`/api/notas/${this.editingId}`, dadosNota);
                        alert('Nota atualizada com sucesso!');
                    } else {
                        await axios.post('/api/notas', dadosNota);
                        alert('Nota criada com sucesso!');
                    }

                    this.closeModal();
                    await this.loadNotas();

                } catch (error) {
                    console.error('Erro completo:', error);
                    const message = error.response?.data?.message || 'Erro ao processar a nota';
                    alert(message);
                } finally {
                    this.loading = false;
                }
            }
        },

        async editarNota(nota) {
            try {
                this.showModal = true;

                const response = await axios.get(`/api/notas/${nota.id}`);
                const notaCompleta = response.data;

                const data = new Date(notaCompleta.data);
                data.setMinutes(data.getMinutes() + data.getTimezoneOffset());
                
                this.form = {
                    cliente_id: notaCompleta.cliente_id,
                    numero_nota: notaCompleta.numero_nota,
                    data: data.toISOString().split('T')[0],
                    items: notaCompleta.items && notaCompleta.items.length > 0 
                        ? notaCompleta.items.map(item => ({
                            kg: item.kg,
                            valor_kg: item.valor_kg,
                            valor_total: item.valor_total
                        })) 
                        : [{
                            kg: '',
                            valor_kg: '',
                            valor_total: ''
                        }],
                    valor_total: notaCompleta.valor_total
                };

                
                this.searchCliente = notaCompleta.cliente?.nome || '';
                this.selectedCliente = notaCompleta.cliente;

                this.editMode = true;
                this.editingId = notaCompleta.id;
                
                this.calcularValorTotalNota();
                
            } catch (error) {
                console.error('Erro ao carregar detalhes da nota:', error);
                alert('Erro ao carregar os detalhes da nota');
            }
        },

    
        async confirmarExclusao(nota) {
            if (confirm(`Deseja realmente excluir a nota ${nota.numero_nota}?`)) {
                try {
                    await axios.delete(`/api/notas/${nota.id}`);
                    alert('Nota excluída com sucesso!');
                    await this.loadNotas();
                } catch (error) {
                    console.error('Erro ao excluir nota:', error);
                    alert('Erro ao excluir a nota');
                }
            }
        },

        resetForm() {
                this.form = {
                cliente_id: '',
                numero_nota: '',
                data: this.getCurrentDate(),
                items: [{
                    kg: '',
                    valor_kg: '',
                    valor_total: ''
                }],
                valor_total: ''
            };
        this.errors = {};
        this.editMode = false;
        this.editingId = null;
        this.searchCliente = '';
        this.selectedCliente = null;
        this.showResults = false;
        },

        async mudarPagina(page) {
            if (page >= 1 && page <= this.lastPage) {
                this.currentPage = page;
                await this.loadNotas();
            }
        },

        adicionarItem() {
            this.form.items.push({
                kg: '',
                valor_kg: '',
                valor_total: ''
            });
        },

        removerItem(index) {
            if (this.form.items.length > 1) {
                this.form.items.splice(index, 1);
            }
        },
        calcularTotal(index) {
            const item = this.form.items[index];
            if (item.kg && item.valor_kg) {
                item.valor_total = (parseFloat(item.kg) * parseFloat(item.valor_kg)).toFixed(2);
            } else {
                item.valor_total = '';
            }
            this.calcularValorTotalNota();
        },
        calcularValorTotalNota() {
            this.form.valor_total = this.form.items.reduce((total, item) => {
                return total + (parseFloat(item.valor_total) || 0);
            }, 0).toFixed(2);
        },
        
        filterClientes() {
            
            this.showResults = true;
            
            if (!Array.isArray(this.clientes)) {
                console.error('this.clientes não é um array');
                return;
            }

            if (!this.searchCliente) {
                this.filteredClientes = [...this.clientes];
                return;
            }

            const search = this.searchCliente.toLowerCase().trim();
            this.filteredClientes = this.clientes.filter(cliente => 
                cliente.nome.toLowerCase().includes(search)
            );
            
            
        },

        selectCliente(cliente) {
            if (!cliente) return;
            
            this.selectedCliente = cliente;
            this.searchCliente = cliente.nome;
            this.form.cliente_id = cliente.id;
            this.showResults = false;
        },

        handleClickOutside(event) {
            const wrapper = this.$refs.autocomplete;
            if (wrapper && !wrapper.contains(event.target)) {
                this.showResults = false;
            }
        },


    },
    async created() {
        await this.loadClientes();
        await this.loadNotas();
    }
}
</script>

<style scoped>

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-content-enter-active {
    transition: all 0.3s ease-out;
}

.modal-content-leave-active {
    transition: all 0.2s ease-in;
}

.modal-content-enter-from,
.modal-content-leave-to {
    transform: translateY(-20px);
    opacity: 0;
}

.modal-content-enter-to,
.modal-content-leave-from {
    transform: translateY(0);
    opacity: 1;
}

.actions-bar {
    background: white;
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 16px 24px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.actions-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.filter-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-input {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.btn-filter {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    background-color: #4338ca;
    color: white;
    border: none;
}

.btn-filter.clear {
    background-color: #6b7280;
}

.filter-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.filter-input:disabled {
    background-color: #f3f4f6;
    cursor: not-allowed;
}

.btn-filter:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.loading {
    opacity: 0.7;
    pointer-events: none;
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
    max-width: 1000px;
    margin-top: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    will-change: transform;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
    font-size: 18px;
    font-weight: 600;
    color: #374151;
    margin: 0;
}

.close-button {
    background: none;
    border: none;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
}

.modal-body {
    padding: 24px;
}

.container {
    padding: 20px;
    max-width: 1400px; 
    margin: 0 auto;
    background-color: #f8f9fa;
}

.notas-list h2 {
    text-align: center;
    margin-bottom: 24px;
    font-size: 24px;
    color: #374151;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.form-container {
    background: white;
    border-radius: 8px;
    padding: 24px;
    margin: 0 auto 24px;
    width: 95%;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.nota-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
}

input, select, textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

input:focus, select:focus, textarea:focus {
    outline: none;
    border-color: #4338ca;
    box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);
}

label {
    font-weight: bold;
    color: #333;
}

input, select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.error {
    border-color: #dc3545;
}

.error-message {
    color: #dc3545;
    font-size: 12px;
}

.client-link {
    color: #4338ca;
    text-decoration: none;
    font-weight: 500;
}

.client-link:hover {
    text-decoration: underline;
}

.actions {
    display: flex;
    gap: 8px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-primary {
    background: #0a0079;
    color: white;
}

.btn-secondary {
    background-color: #4338ca !important; 
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    border: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(67, 56, 202, 0.1);
}

.btn-secondary:hover {
    background-color: #3730a3 !important; 
    box-shadow: 0 2px 4px rgba(67, 56, 202, 0.2);
}

.btn-secondary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-edit {
    background-color: #7bff00 !important;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    border: none;
    font-size: 13px;
    font-weight: 500;
    min-width: 80px; 
    text-align: center;
}

.btn-delete {
    background-color: #ef4444 !important;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    border: none;
    font-size: 13px;
    font-weight: 500;
    min-width: 80px; 
    text-align: center;
}

table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.table-container {
    background: white;
    border-radius: 8px;
    padding: 24px;
    margin: 20px auto;
    width: 95%; 
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}


table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

th {
    padding: 16px;
    text-align: left;
    font-weight: 500;
    color: #666;
    border-bottom: 1px solid #e5e7eb;
    background-color: white;
    font-size: 14px;
}

td {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    color: #333;
    font-size: 14px;
}

tr:hover {
    background-color: #f9fafb;
}

.text-center {
    text-align: center;
}

@media (max-width: 768px) {

    .modal-container {
        width: 95%;
        max-height: 95vh;
    }
    
    .modal-body {
        padding: 16px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
    
    .actions {
        flex-direction: column;
    }
    
    th, td {
        padding: 8px;
    }
}
.readonly-input {
    background-color: #f8f9fa;
    cursor: not-allowed;
}
input[type="date"] {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    width: 100%;
}

input[type="date"].error {
    border-color: #dc3545;
}
.item-row {
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 4px;
}

.item-fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    align-items: end;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.mt-2 {
    margin-top: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.mt-2::before {
    content: "+";
    font-size: 16px;
    font-weight: bold;
}

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: white;
    border-top: 1px solid #e5e7eb;
    margin-top: 20px;
    border-radius: 0 0 8px 8px;
}


.pagination-info {
    color: #6b7280;
    font-size: 14px;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 16px;
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
    transition: all 0.2s ease;
}

.btn-pagination:hover:not(:disabled) {
    background-color: #f3f4f6;
    border-color: #d1d5db;
}

.btn-pagination:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: #f3f4f6;
}


.page-info {
    color: #4b5563;
    font-size: 14px;
    min-width: 150px;
    text-align: center;
}

td:nth-child(4) { 
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;;
    font-weight: 500;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.autocomplete-wrapper {
    position: relative;
}

.autocomplete-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    max-height: 200px;
    overflow-y: auto;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.autocomplete-item {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.autocomplete-item:hover {
    background-color: #f5f5f5;
}

.autocomplete-item:last-child {
    border-bottom: none;
}

.autocomplete-item.no-results {
    color: #666;
    font-style: italic;
}

select.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
    background-color: white;
}

select.form-control:focus {
    outline: none;
    border-color: #4338ca;
    box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);
}

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

@media (max-width: 768px) {
    .container {
        padding: 10px;
    }

    .table-container {
        overflow-x: auto;
    }

    td, th {
        white-space: nowrap;
        padding: 12px;
    }

    .actions {
        flex-direction: row;
    }
    
    .pagination-container {
        flex-direction: column;
        gap: 12px;
    }

    .pagination-controls {
        width: 100%;
        justify-content: center;
    }
}

</style>