<template>
    <div class="container">
        <!-- Loading Overlay -->
        <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner"></div>
        </div>
        <!-- Barra de ações -->
        <div class="actions-bar">
            <button @click="openModal" class="btn-novo">
                Novo
            </button>
        </div>
        <!-- Filtro de despesas por busca -->
        <div class="filter-section">
            <div class="filter-grid">
                <!-- Filtro por ID -->
                <div class="filter-item">
                    <label>ID da Despesa</label>
                    <input 
                        type="number" 
                        v-model="filtros.id" 
                        placeholder="Buscar por ID"
                        class="filter-input"
                    >
                </div>
                
                <!-- Filtro por Fornecedor -->
                <div class="filter-item">
                    <label>Fornecedor</label>
                    <div class="autocomplete-wrapper" ref="filterAutocomplete">
                        <input 
                            type="text"
                            v-model="filtros.searchFornecedor"
                            @input="filterFornecedoresSearch"
                            placeholder="Buscar por fornecedor"
                            class="filter-input"
                        >
                        <div v-if="showFilterResults" class="autocomplete-results">
                            <div 
                                v-for="fornecedor in filteredFornecedoresSearch"
                                :key="fornecedor.id"
                                @click="selectFornecedorFilter(fornecedor)"
                                class="autocomplete-item"
                            >
                                {{ fornecedor.nome }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtro por Data -->
                <div class="filter-item">
                    <label>Data Início</label>
                    <input 
                        type="date" 
                        v-model="filtros.data_inicio"
                        class="filter-input"
                    >
                </div>
                
                <div class="filter-item">
                    <label>Data Fim</label>
                    <input 
                        type="date" 
                        v-model="filtros.data_fim"
                        class="filter-input"
                    >
                </div>
            </div>
            
            <div class="filter-actions">
                <button @click="aplicarFiltros" class="btn-filter">
                    Filtrar
                </button>
                <button @click="limparFiltros" class="btn-filter clear">
                    Limpar Filtros
                </button>
            </div>
        </div>


        <!-- Lista de Despesas -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Fornecedor</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="despesa in despesas" :key="despesa.id">
                        <td>{{ despesa.id }}</td>
                        <td>{{ formatarData(despesa.data) }}</td>
                        <td>{{ despesa.produto }}</td>
                        <td>{{ formatarNumero(despesa.quantidade) }}</td>
                        <td>{{ despesa.fornecedor?.nome }}</td>
                        <td class="valor-total">{{ formatarMoeda(despesa.valor) }}</td>
                        <td>{{ formatarData(despesa.data_vencimento) }}</td>
                        <td>
                            <span :class="getStatusClass(despesa.status)">
                                {{ despesa.status }}
                            </span>
                        </td>
                        <td class="actions">
                            <button @click="editarDespesa(despesa)" class="btn-edit">
                                Editar
                            </button>
                            <button @click="excluirDespesa(despesa.id)" class="btn-delete">
                                Excluir
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!despesas.length">
                        <td colspan="8" class="text-center">Nenhuma despesa encontrada.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div class="pagination-container" v-if="paginacao">
            <div class="pagination-info">
                Visualizando {{ paginationInfo }}
            </div>
            <div class="pagination-controls">
                <button 
                    class="btn-pagination"
                    :disabled="paginacao.current_page === 1"
                    @click="carregarDespesas(paginacao.current_page - 1)"
                >
                    Anterior
                </button>
                <span class="page-info">
                    Página {{ paginacao.current_page }} de {{ paginacao.last_page }}
                </span>
                <button 
                    class="btn-pagination"
                    :disabled="paginacao.current_page === paginacao.last_page"
                    @click="carregarDespesas(paginacao.current_page + 1)"
                >
                    Próxima
                </button>
            </div>
        </div>

        <!-- Modal do Formulário -->
        <transition name="modal">
            <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
                <div class="modal-container">
                    <div class="modal-header">
                        <h2>{{ modoEdicao ? 'Editar Despesa' : 'Nova Despesa' }}</h2>
                        <button @click="closeModal" class="close-button">&times;</button>
                    </div>
                    
                    <div class="modal-body">
                        <form @submit.prevent="modoEdicao ? atualizarDespesa() : criarDespesa()" class="form-despesa">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Data *</label>
                                    <input 
                                        type="date" 
                                        v-model="despesa.data" 
                                        class="form-control"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label>Data de Vencimento *</label>
                                    <input 
                                        type="date" 
                                        v-model="despesa.data_vencimento" 
                                        class="form-control"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Fornecedor *</label>
                                <div class="autocomplete-wrapper" ref="autocomplete">
                                    <input 
                                        type="text"
                                        v-model="searchFornecedor"
                                        @input="filterFornecedores"
                                        class="form-control"
                                        placeholder="Digite para buscar fornecedor"
                                        required
                                    >
                                    <div v-if="showResults" class="autocomplete-results">
                                        <div v-if="filteredFornecedores.length === 0" class="autocomplete-item no-results">
                                            Nenhum fornecedor encontrado
                                        </div>
                                        <div
                                            v-else
                                            v-for="fornecedor in filteredFornecedores"
                                            :key="fornecedor.id"
                                            @click="selectFornecedor(fornecedor)"
                                            class="autocomplete-item"
                                        >
                                            {{ fornecedor.nome }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Produto *</label>
                                    <input 
                                        type="text" 
                                        v-model="despesa.produto" 
                                        class="form-control"
                                        placeholder="Digite o nome do produto"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label>Quantidade *</label>
                                    <input 
                                        type="number" 
                                        v-model="despesa.quantidade" 
                                        class="form-control"
                                        step="0.01"
                                        min="0"
                                        required
                                    >
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Valor (R$) *</label>
                                    <input 
                                        type="number" 
                                        v-model="despesa.valor" 
                                        class="form-control"
                                        step="0.01"
                                        min="0"
                                        required
                                    >
                                </div>

                                <div class="form-group checkbox-group">
                                    <label class="checkbox-label">
                                        <input 
                                            type="checkbox" 
                                            v-model="despesa.status_pago"
                                        >
                                        Despesa Paga?
                                    </label>
                                </div>
                            </div>

                            <div class="modal-actions">
                                <button 
                                    type="submit" 
                                    class="btn btn-primary"
                                >
                                    {{ modoEdicao ? 'Atualizar' : 'Criar' }}
                                </button>
                                <button 
                                    type="button"
                                    @click="closeModal" 
                                    class="btn btn-secondary"
                                >
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
    name: 'CriarDespesas',
    data() {
        return {
            showModal: false,
            modoEdicao: false,
            despesa: {
                id: null,
                data: new Date().toISOString().split('T')[0],
                produto: '',
                quantidade: null,
                fornecedor_id: null,
                valor: null,
                data_vencimento: '',
                status_pago: false,
            },

            paginacao: {
                current_page: 1,
                last_page: 1,
                total: 0
            },

            despesas: [],
            loading: false,
            fornecedores: [],
            searchFornecedor: '',
            filteredFornecedores: [],        
            showResults: false,
            selectedFornecedor: null,
            errors: {},
            filtros: {
            id: '',
                fornecedor_id: '',
                searchFornecedor: '',
                data_inicio: '',
                data_fim: ''
            },
            showFilterResults: false,
            filteredFornecedoresSearch: [],
        }
    },

    computed: {
                paginationInfo() {
                if (!this.paginacao) return '';
                const inicio = ((this.paginacao.current_page - 1) * 15) + 1;
                const fim = Math.min(this.paginacao.current_page * 15, this.paginacao.total);
                
                if (inicio > this.paginacao.total) {
                    return `${this.paginacao.total} de ${this.paginacao.total} despesas`;
                }
                
                return `${inicio}-${fim} de ${this.paginacao.total} despesas`;
            }
        },

    created() {
        Promise.all([
            this.carregarFornecedores(),
            this.carregarDespesas()
        ]).catch(error => {
            console.error('Erro ao carregar dados iniciais:', error);
        });
    },

     mounted() {
        document.addEventListener('click', this.handleClickOutside);
    },

    beforeDestroy() {
        document.removeEventListener('click', this.handleClickOutside);
    },

    async created() {
        await Promise.all([
            this.carregarFornecedores(),
            this.carregarDespesas()
        ]);
    },

    methods: {

        openModal() {
            this.showModal = true;
            this.modoEdicao = false;
            this.limpar();
        },

        closeModal() {
            this.showModal = false;
            this.limpar();
        },

        getStatusClass(status) {
            return {
                'status-tag': true,
                'status-aberto': status === 'ABERTO',
                'status-paga': status === 'PAGA'
            };
        },

        async carregarFornecedores() {
            this.loading = true;
            try {
                const response = await fetch('/api/fornecedores', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    this.fornecedores = data;
                    this.filteredFornecedores = data;
                }
            } catch (error) {
                console.error('Erro ao carregar fornecedores:', error);
                alert('Erro ao carregar lista de fornecedores');
            } finally {
                this.loading = false;
            }
        },
        async criarDespesa() {
            this.loading = true;
            try {
                
                if (!this.despesa.data || !this.despesa.produto || !this.despesa.quantidade || 
                    !this.despesa.fornecedor_id || !this.despesa.valor || !this.despesa.data_vencimento) {
                    alert('Por favor, preencha todos os campos obrigatórios');
                    return;
                }

                const formatarDataParaEnvio = (dataString) => {
                    const data = new Date(dataString);
                    return data.toISOString().split('T')[0];
                };

                const dadosDespesa = {
                    data: formatarDataParaEnvio(this.despesa.data),
                    produto: this.despesa.produto,
                    quantidade: parseFloat(this.despesa.quantidade),
                    fornecedor_id: parseInt(this.despesa.fornecedor_id),
                    valor: parseFloat(this.despesa.valor),
                    data_vencimento: formatarDataParaEnvio(this.despesa.data_vencimento),
                    status: this.despesa.status_pago ? 'PAGA' : 'ABERTO'
                };

                const response = await fetch('/api/despesas', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    },
                    body: JSON.stringify(dadosDespesa)
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `Erro ${response.status}: ${response.statusText}`);
                }

                const resultado = await response.json();
                
                alert('Despesa criada com sucesso!');
                this.showModal = false;
                this.limpar();
                await this.carregarDespesas();

            } catch (error) {
                console.error('Erro detalhado:', error);
                alert(`Erro ao criar despesa: ${error.message}`);
            } finally {
                this.loading = false;
            }
        },
        limpar() {
            this.despesa = {
                id: null,
                data: new Date().toISOString().split('T')[0],
                produto: '',
                quantidade: '',
                fornecedor_id: '',
                valor: '',
                data_vencimento: '',
                status_pago: false
            };
            this.modoEdicao = false;
            this.searchFornecedor = '';
            this.selectedFornecedor = null;
            this.showResults = false;
        },

        filterFornecedores() {
            this.showResults = true;

            if (!Array.isArray(this.fornecedores)) {
                console.error('this.fornecedores não é um array');
                return;
            }

            if (!this.searchFornecedor) {
                this.filteredFornecedores = [...this.fornecedores];
                return;
            }

            const search = this.searchFornecedor.toLowerCase().trim();
            this.filteredFornecedores = this.fornecedores.filter(fornecedor => 
                fornecedor.nome.toLowerCase().includes(search)
            );

        },

        selectFornecedor(fornecedor) {
            if (!fornecedor) return;


            this.selectedFornecedor = fornecedor;
            this.searchFornecedor = fornecedor.nome;
            this.despesa.fornecedor_id = fornecedor.id;
            this.showResults = false;
        },

        editarDespesa(despesa) {
            const ajustarDataLocal = (dataString) => {
                const data = new Date(dataString);
                data.setMinutes(data.getMinutes() + data.getTimezoneOffset());
                return data.toISOString().split('T')[0];
            };
            
            this.searchFornecedor = despesa.fornecedor?.nome || '';
            this.selectedFornecedor = despesa.fornecedor;

            this.despesa = {
                id: despesa.id,
                data: ajustarDataLocal(despesa.data),
                produto: despesa.produto,
                quantidade: parseFloat(despesa.quantidade),
                fornecedor_id: despesa.fornecedor_id,
                valor: parseFloat(despesa.valor),
                data_vencimento: ajustarDataLocal(despesa.data_vencimento),
                status_pago: despesa.status === 'PAGA'
            };
            
            this.modoEdicao = true;
            this.showModal = true;
        },


        async atualizarDespesa() {
            this.loading = true;
            try {
                if (!this.despesa.data || !this.despesa.produto || !this.despesa.quantidade || 
                    !this.despesa.fornecedor_id || !this.despesa.valor || !this.despesa.data_vencimento) {
                    alert('Por favor, preencha todos os campos obrigatórios');
                    return;
                }

                const formatarDataParaEnvio = (dataString) => {
                    const data = new Date(dataString);
                    return data.toISOString().split('T')[0];
                };

                const dadosDespesa = {
                    data: formatarDataParaEnvio(this.despesa.data),
                    produto: this.despesa.produto,
                    quantidade: parseFloat(this.despesa.quantidade),
                    fornecedor_id: parseInt(this.despesa.fornecedor_id),
                    valor: parseFloat(this.despesa.valor),
                    data_vencimento: formatarDataParaEnvio(this.despesa.data_vencimento),
                    status: this.despesa.status_pago ? 'PAGA' : 'ABERTO'
                };

                const response = await fetch(`/api/despesas/${this.despesa.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    },
                    body: JSON.stringify(dadosDespesa)
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `Erro ${response.status}: ${response.statusText}`);
                }

                alert('Despesa atualizada com sucesso!');
                this.limpar();
                await this.carregarDespesas();

            } catch (error) {
                console.error('Erro ao atualizar despesa:', error);
                alert(`Erro ao atualizar despesa: ${error.message}`);
            } finally {
                this.loading = false;
            }
        },


        async carregarDespesas(page = 1, customParams = {}) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                params.append('page', page);

                // Adiciona os filtros se existirem
                if (this.filtros.id) {
                    params.append('id', this.filtros.id);
                }
                if (this.filtros.fornecedor_id) {
                    params.append('fornecedor_id', this.filtros.fornecedor_id);
                }
                if (this.filtros.data_inicio) {
                    params.append('data_inicio', this.filtros.data_inicio);
                }
                if (this.filtros.data_fim) {
                    params.append('data_fim', this.filtros.data_fim);
                }

                // Adiciona parâmetros customizados se houver
                Object.entries(customParams).forEach(([key, value]) => {
                    if (value) params.append(key, value);
                });

                const response = await fetch(`/api/despesas?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.despesas = data.data;
                    this.paginacao = {
                        current_page: data.current_page,
                        last_page: data.last_page,
                        total: data.total
                    };
                }
            } catch (error) {
                console.error('Erro ao carregar despesas:', error);
                alert('Erro ao carregar lista de despesas');
            } finally {
                this.loading = false;
            }
        },

        formatarData(data) {
            try {
                const dataPura = data.split('T')[0];
                const [ano, mes, dia] = dataPura.split('-');
                return `${dia}/${mes}/${ano}`;
            } catch (error) {
                console.error('Erro ao formatar data:', error);
                return data;
            }
        },

        formatarMoeda(valor) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(valor);
        },

        formatarNumero(numero) {
            return new Intl.NumberFormat('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(numero);
        },

        async excluirDespesa(id) {
            if (!confirm('Tem certeza que deseja excluir esta despesa?')) {
                return;
            }

            this.loading = true;
            try {
                const response = await fetch(`/api/despesas/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });

                if (response.ok) {
                    alert('Despesa excluída com sucesso!');
                    this.carregarDespesas(this.despesas.current_page);
                }
            } catch (error) {
                console.error('Erro ao excluir despesa:', error);
                alert('Erro ao excluir despesa');
            } finally {
                this.loading = false;
            }
        },

        handleClickOutside(event) {
            const wrapper = this.$refs.autocomplete;
            if (wrapper && !wrapper.contains(event.target)) {
                this.showResults = false;
            }
        },

        async aplicarFiltros() {
            this.loading = true;
            try {
                this.currentPage = 1;
                await this.carregarDespesas(1, {
                    id: this.filtros.id,
                    fornecedor_id: this.filtros.fornecedor_id,
                    data_inicio: this.filtros.data_inicio,
                    data_fim: this.filtros.data_fim
                });
                } finally {
                    this.loading = false;
                }
            },

        limparFiltros() {
            this.filtros = {
                id: '',
                fornecedor_id: '',
                searchFornecedor: '',
                data_inicio: '',
                data_fim: ''
            };
            this.showFilterResults = false;
            this.carregarDespesas(1);
        },

        filterFornecedoresSearch() {
            this.showFilterResults = true;
            
            if (!this.filtros.searchFornecedor) {
                this.filteredFornecedoresSearch = this.fornecedores;
                return;
            }

            const search = this.filtros.searchFornecedor.toLowerCase().trim();
            this.filteredFornecedoresSearch = this.fornecedores.filter(fornecedor => 
                fornecedor.nome.toLowerCase().includes(search)
            );
        },

        selectFornecedorFilter(fornecedor) {
            this.filtros.searchFornecedor = fornecedor.nome;
            this.filtros.fornecedor_id = fornecedor.id;
            this.showFilterResults = false;
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

.container {
    padding: 20px;
    max-width: 1400px; 
    margin: 0 auto;
    background-color: #f8f9fa;
}

.actions {
    display: flex;
    gap: 8px;
    justify-content: flex-start;
    min-width: 140px;
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
    border-bottom: 1px solid  #4338ca;
}

.modal-header h2 {
    font-size: 18px;
    font-weight: 600;
    color:  #4338ca;
    margin: 0;
}

.modal-body {
    padding: 24px;
}

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

.close-button {
    background: none;
    border: none;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
}
.form-container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 16px;
}

label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.checkbox-group {
    margin-top: 15px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
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

.btn-primary {
    background-color: #7bff00;
    color: white;
}

.btn-secondary {
    background-color: #ef4444;
    color: white;
}

.btn:hover {
    opacity: 0.9;
}

select.form-control {
    background-color: white;
}


.status-tag {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 80px;
}

.status-aberto {
    background-color: #fef3c7;
    color: #92400e;
}

.status-paga {
    background-color: #d1fae5;
    color: #065f46;
}

.despesas-list {
    margin-top: 30px;
}

table {
    width: 100%;
    min-width: 800px;
    border-collapse: separate;
    border-spacing: 0;
}

th {
    text-align: center;
    padding: 12px 16px;
    background: white;
    font-weight: 500;
    color: #666;
}

td {
    padding: 12px 16px;
    text-align: center; 
    border-bottom: 1px solid #e5e7eb;
}


td:nth-child(3), 
td:nth-child(4) { 
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    font-weight: 500;
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

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.status-badge.paga {
    background-color: #28a745;
    color: white;
}

.status-badge.aberto {
    background-color: #ffc107;
    color: black;
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

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.ms-2 {
    margin-left: 8px;
}

.loading {
    opacity: 0.6;
    pointer-events: none;
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

.table-container {
    width: 100%;
    overflow-x: auto; 
    margin-bottom: 20px;
    background: white;
    border-radius: 8px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.filter-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-top: 16px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.filter-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
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

.btn-filter:hover {
    background-color: #3730a3;
}

.btn-filter.clear {
    background-color: #6b7280;
}

.btn-filter.clear:hover {
    background-color: #4b5563;
}

.filter-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.filter-input:focus {
    outline: none;
    border-color: #4338ca;
    box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);
}

@media (max-width: 768px) {
    .table-container {
        margin: 0 -16px; /* Remove margens laterais em telas pequenas */
        border-radius: 0;
        padding: 8px;
    }
    
    td, th {
        padding: 12px 8px; /* Reduz padding em telas menores */
    }

    .actions {
        min-width: 120px;
    }
}
</style>