<template>
    <div class="container">
         <!-- Loading Overlay -->
        <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner"></div>
        </div>

        <!-- Seleção de Fornecedor -->
        <div class="search-container">
            <div class="form-group">
                <label>Fornecedor</label>
                <div class="autocomplete-wrapper" ref="autocomplete">
                    <input 
                        type="text"
                        v-model="search.fornecedor"
                        @input="filterFornecedores"
                        class="form-control"
                        placeholder="Digite para buscar fornecedor"
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
                    <label>Data Inicial</label>
                    <input 
                        type="date" 
                        v-model="search.dataInicial"
                        class="input-date"
                        @change="buscarDespesas"
                    >
                </div>

                <div class="form-group">
                    <label>Data Final</label>
                    <input 
                        type="date" 
                        v-model="search.dataFinal"
                        class="input-date"
                        @change="buscarDespesas"
                        :min="search.dataInicial"
                    >
                </div>
            </div>
        </div>

        <!-- Resumo do Fornecedor -->
        <div v-if="search.fornecedor_id" class="resumo-container">
            <div class="card">
                <h3>Resumo de Despesas</h3>
                <div class="resumo-info">
                    <div class="info-item">
                        <span class="label">Total em Aberto:</span>
                        <span class="value text-danger">R$ {{ formatarValor(totalEmAberto) }}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Total Pago:</span>
                        <span class="value text-success">R$ {{ formatarValor(totalPago) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Despesas -->
        <div v-if="search.fornecedor_id" class="despesas-list">
            <h2>Despesas do Fornecedor</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="despesa in despesas" :key="despesa.id">
                            <td>{{ despesa.id }}</td>
                            <td>{{ formatarData(despesa.data) }}</td>
                            <td>{{ despesa.produto }}</td>
                            <td>{{ formatarNumero(despesa.quantidade) }}</td>
                            <td>R$ {{ formatarValor(despesa.valor) }}</td>
                            <td>{{ formatarData(despesa.data_vencimento) }}</td>
                            <td>
                                <span :class="getStatusClass(despesa.status)">
                                    {{ despesa.status }}
                                </span>
                            </td>
                            <td>
                                <button 
                                    v-if="despesa.status === 'ABERTO'"
                                    @click="abrirModalPagamento(despesa)"
                                    class="btn btn-success"
                                >
                                    Marcar como Paga
                                </button>
                                <button 
                                    v-if="despesa.status === 'PAGA'"
                                    @click="verDetalhes(despesa)"
                                    class="btn btn-info"
                                >
                                    Detalhes
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!despesas.length">
                            <td colspan="7" class="text-center">Nenhuma despesa encontrada.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal de Pagamento -->
        <div v-if="showPaymentModal" class="modal-overlay">
            <div class="modal-content">
                <h3>Confirmar Pagamento</h3>
                
                <div class="form-group">
                    <label for="data_pagamento">Data do Pagamento *</label>
                    <input 
                        type="date" 
                        id="data_pagamento"
                        v-model="paymentForm.data_pagamento"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="observacao">Observação</label>
                    <textarea 
                        id="observacao"
                        v-model="paymentForm.observacao"
                        rows="3"
                        placeholder="Observação (opcional)"
                    ></textarea>
                </div>

                <div class="modal-actions">
                    <button @click="confirmarPagamento" class="btn btn-success">
                        Confirmar
                    </button>
                    <button @click="showPaymentModal = false" class="btn btn-secondary">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Detalhes -->
        <div v-if="showDetalhesModal && despesaSelecionada" class="modal-overlay">
            <div class="modal-content">
                <h3>Detalhes do Pagamento</h3>
                
                <div class="detalhes-info">
                    <div class="info-group">
                        <label>Data do Pagamento:</label>
                        <span> {{ despesaSelecionada.data_pagamento ? formatarData(despesaSelecionada.data_pagamento) : 'Não disponível' }}</span>
                    </div>
                    
                    <div class="info-group">
                        <label>Data da Despesa:</label>
                        <span> {{ formatarData(despesaSelecionada.data) }}</span>
                    </div>
                    
                    <div class="info-group">
                        <label>Valor:</label>
                        <span> R$ {{ formatarValor(despesaSelecionada.valor) }}</span>
                    </div>
                    
                    <div class="info-group">
                        <label>Observação:</label>
                        <p>{{ despesaSelecionada.observacao || 'Nenhuma observação' }}</p>
                    </div>
                </div>

                <div class="modal-actions">
                    <button @click="cancelarPagamento" class="btn btn-danger">
                        Cancelar Pagamento
                    </button>

                    <button @click="showDetalhesModal = false" class="btn btn-secondary">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { emitter } from '../eventBus' 

export default {
    name: 'BuscarDespesas',
    data() {
        return {
            loading: false,
            search: {
                fornecedor: '',
                fornecedor_id: null,
                dataInicial: '',
                dataFinal: ''
            },
            fornecedores: [],
            filteredFornecedores: [],
            showResults: false,
            selectedFornecedor: null,
            despesas: [],
            totalEmAberto: 0,
            totalPago: 0,
            showPaymentModal: false,
            despesaSelecionada: null,
            paymentForm: {
                data_pagamento: '',
                observacao: ''
            },
            showDetalhesModal: false
        }
    },

    watch: {
        '$route.query': {
        immediate: true,
            handler(query) {
                if (query.fornecedor_id && query.fornecedor_nome) {
                    this.search.fornecedor_id = query.fornecedor_id;
                    this.search.fornecedor = query.fornecedor_nome;
                    this.selectedFornecedor = {
                        id: query.fornecedor_id,
                        nome: query.fornecedor_nome
                    };
                    this.buscarDespesas().then(() => {
                        // Se houver uma despesa específica para pagar
                        if (query.despesa_id && query.action === 'pagar') {
                            const despesa = this.despesas.find(d => d.id === parseInt(query.despesa_id));
                            if (despesa) {
                                this.abrirModalPagamento(despesa);
                            }
                        }
                    });
                }
            }
        }
    },


    methods: {
        formatarValor(valor) {
            return Number(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatarNumero(numero) {
            return Number(numero).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatarData(data) {
            const dataPura = data.split('T')[0];
            const [ano, mes, dia] = dataPura.split('-');
            return `${dia}/${mes}/${ano}`;
        },

        getStatusClass(status) {
            return {
                'status-tag': true,
                'status-aberto': status === 'ABERTO',
                'status-paga': status === 'PAGA'
            };
        },

        async loadFornecedores() {
            this.loading = true;
                    try {
                const response = await fetch('/api/fornecedores', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Accept': 'application/json'
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

        async buscarDespesas() {
            this.loading = true;
            try {
                if (!this.search.fornecedor_id) {
                    this.despesas = [];
                    this.calcularTotais();
                    return;
                }

                let url = '/api/despesas/buscar';
                const params = new URLSearchParams();
                
                params.append('fornecedor_id', this.search.fornecedor_id);
                
                if (this.search.dataInicial) {
                    params.append('data_inicial', this.search.dataInicial);
                }
                if (this.search.dataFinal) {
                    params.append('data_final', this.search.dataFinal);
                }

                const response = await fetch(url + '?' + params.toString(), {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Accept': 'application/json'
                    }
                });


                if (response.ok) {
                    const data = await response.json();
                    this.despesas = Array.isArray(data.despesas) ? data.despesas : [];
                    this.calcularTotais();
                } else {
                    throw new Error('Erro ao buscar despesas');
                }
            } catch (error) {
                console.error('Erro ao buscar despesas:', error);
                this.despesas = [];
                this.calcularTotais();
                alert('Erro ao buscar despesas');
            } finally {
                this.loading = false;
            }
        },

        calcularTotais() {
            if (!Array.isArray(this.despesas)) {
                this.despesas = [];
            }

            this.totalEmAberto = this.despesas
                .filter(despesa => despesa && despesa.status === 'ABERTO')
                .reduce((total, despesa) => total + (parseFloat(despesa.valor) || 0), 0);

            this.totalPago = this.despesas
                .filter(despesa => despesa && despesa.status === 'PAGA')
                .reduce((total, despesa) => total + (parseFloat(despesa.valor) || 0), 0);
        },

        abrirModalPagamento(despesa) {
            this.despesaSelecionada = despesa;
            this.paymentForm.data_pagamento = new Date().toISOString().split('T')[0];
            this.paymentForm.observacao = '';
            this.showPaymentModal = true;
        },

        async confirmarPagamento() {
            this.loading = true;
            try {
                if (!this.paymentForm.data_pagamento) {
                    alert('A data de pagamento é obrigatória');
                    return;
                }

                const response = await fetch(`/api/despesas/${this.despesaSelecionada.id}/pagar`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        data_pagamento: this.paymentForm.data_pagamento,
                        observacao: this.paymentForm.observacao
                    })
                });

                if (!response.ok) {
                    throw new Error('Erro ao processar pagamento');
                }

                const data = await response.json();
                this.showPaymentModal = false;
                await this.buscarDespesas(); // Recarrega as despesas
                alert('Despesa marcada como paga com sucesso!');
            } catch (error) {
                console.error('Erro ao pagar despesa:', error);
                alert('Erro ao processar o pagamento da despesa');
            } finally {
                this.loading = false;
            }
        },
        
                verDetalhes(despesa) {
            this.despesaSelecionada = { ...despesa }; 
            this.showDetalhesModal = true;
        },

        filterFornecedores() {
            this.showResults = true;
            if (!this.search.fornecedor) {
                this.filteredFornecedores = [...this.fornecedores];
                return;
            }

            const search = this.search.fornecedor.toLowerCase().trim();
            this.filteredFornecedores = this.fornecedores.filter(fornecedor => 
                fornecedor.nome.toLowerCase().includes(search)
            );
        },
        
        selectFornecedor(fornecedor) {
            if (!fornecedor) return;

            this.selectedFornecedor = fornecedor;
            this.search.fornecedor = fornecedor.nome;
            this.search.fornecedor_id = fornecedor.id;
            this.showResults = false;
            
            this.buscarDespesas();
        },

        handleClickOutside(event) {
            const wrapper = this.$refs.autocomplete;
            if (wrapper && !wrapper.contains(event.target)) {
                this.showResults = false;
            }
        },

        async cancelarPagamento() {
            if (confirm('Tem certeza que deseja cancelar este pagamento? A despesa voltará para o status ABERTO.')) {
                this.loading = true;
                try {
                    await axios.put(`/api/despesas/${this.despesaSelecionada.id}/cancelar-pagamento`);
                
                    this.showDetalhesModal = false;
                    
                    await this.buscarDespesas();
                    
                    emitter.emit('despesa-atualizada');
                    
                    alert('Pagamento cancelado com sucesso!');

                } catch (error) {
                    console.error('Erro ao cancelar pagamento:', error);
                    alert('Erro ao cancelar o pagamento');
                } finally {
                    this.loading = false;
                }
            }
        }

    },

    mounted() {
        document.addEventListener('click', this.handleClickOutside);
    },

    beforeDestroy() {
        document.removeEventListener('click', this.handleClickOutside);
    },

    async created() {
        await this.loadFornecedores().catch(error => {
        console.error('Erro ao carregar dados iniciais:', error);
    });
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

.search-container {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    width: 95%;
    margin: 0 auto 24px;
}

.select-fornecedor {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.resumo-container {
    width: 95%;
    margin: 0 auto;
}

.card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.resumo-info {
    display: flex;
    justify-content: space-around;
    margin-top: 15px;
}

.info-item {
    text-align: center;
}

.label {
    font-weight: bold;
    color: #666;
    display: block;
    margin-bottom: 5px;
}

.value {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 24px;
    font-weight: 600;
    color: #1a1a1a;
    letter-spacing: -0.2px;
}

.text-danger {
    color: #dc2626;
}

.text-success {
    color: #059669;
}

.status-tag {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.status-aberto {
    background-color: #fef3c7;
    color: #92400e;
}

.status-paga {
    background-color: #d4edda;
    color: #155724;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.btn:hover {
    opacity: 0.9;
}

.btn-success {
    background-color: #34d399 !important;
    color: white;
    border: none;
}

.btn-info {
    background-color: #3b82f6 !important;
    color: white;
    border: none;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.input-date {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.input-date:focus {
    outline: none;
    border-color: #4338ca;
    box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 24px;
    border-radius: 8px;
    width: 100%;
    max-width: 500px;
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

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #4338ca;
    box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 24px;
}

.detalhes-info {
    margin-top: 20px;
}

.info-group {
    margin-bottom: 15px;
}

.info-group label {
    font-weight: bold;
    color: #666;
}

.btn-danger {
    background-color: #ef4444;
    color: white;
    margin-right: 8px;
}

.btn-danger:hover {
    background-color: #dc2626;
}

@media (max-width: 768px) {
    .container {
        padding: 10px;
    }

    .search-container,
    .table-container,
    .resumo-container {
        width: 100%;
        padding: 16px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .resumo-info {
        flex-direction: column;
        gap: 15px;
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        min-width: 700px;
    }
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
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    margin-top: 4px;
}

.autocomplete-item {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
}

.autocomplete-item {
    padding: 12px;
    cursor: pointer;
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s ease;
}

.autocomplete-item:hover {
    background-color: #f3f4f6;
}

.autocomplete-item:last-child {
    border-bottom: none;
}

.autocomplete-item.no-results {
    color: #6b7280;
    font-style: italic;
}

</style>