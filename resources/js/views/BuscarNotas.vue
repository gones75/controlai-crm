<template>
    <div class="search-container">
        <!-- Loading Overlay -->
        <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner"></div>
        </div>
        <div class="form-group">
            <label>Cliente</label>
            <div class="autocomplete-wrapper" ref="autocomplete">
                <input 
                    type="text"
                    v-model="searchCliente"
                    @input="filterClientes"
                    class="form-control"
                    placeholder="Digite para buscar cliente"
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
        
            <div class="form-row">
                <div class="form-group">
                    <label for="dataInicial">Data Inicial</label>
                    <input 
                        type="date" 
                        id="dataInicial"
                        v-model="dataInicial"
                        @change="buscarNotas"
                        class="input-date"
                    >
                </div>

                <div class="form-group">
                    <label for="dataFinal">Data Final</label>
                    <input 
                        type="date" 
                        id="dataFinal"
                        v-model="dataFinal"
                        @change="buscarNotas"
                        class="input-date"
                        :min="dataInicial"
                    >
                </div>
            </div>
        </div>

        <!-- Resumo do Cliente -->
        <div v-if="selectedClienteId" class="resumo-container">
            <div class="card">
                <h3>Resumo do Cliente</h3>
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

        <!-- Barra de Ações em Lote -->
        <div v-if="selectedClienteId && notasSelecionadas.length > 0" class="batch-action-bar">
            <div class="batch-info">
                <span class="info-badge">{{ notasSelecionadas.length }}</span> nota(s) selecionada(s) 
                <span class="valor-destaque">R$ {{ formatarValor(totalNotasSelecionadas) }}</span>
            </div>
            <button 
                @click="abrirModalPagamentoLote" 
                class="btn-batch-pay"
            >
                <span class="icon">💰</span> Pagar Notas Selecionadas
            </button>
            <button 
                @click="limparSelecao" 
                class="btn-clear-selection"
            >
                Limpar Seleção
            </button>
        </div>

        <!-- Lista de Notas -->
        <div v-if="selectedClienteId" class="notas-list">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-column">
                                <input 
                                    type="checkbox" 
                                    @change="selecionarTodas($event.target.checked)"
                                    :checked="todasSelecionadas"
                                    :disabled="!notasSelecionaveis.length"
                                    class="checkbox-input"
                                >
                            </th>
                            <th>N° da Nota</th>
                            <th>Data</th>
                            <th>KG</th>
                            <th>Valor KG</th>
                            <th>Valor Total</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="nota in notas" :key="nota.id">
                            <td class="checkbox-column">
                                <input 
                                    type="checkbox" 
                                    v-model="notasSelecionadas" 
                                    :value="nota.id"
                                    :disabled="nota.status !== 'ABERTA'"
                                    class="checkbox-input"
                                >
                            </td>
                            <td>{{ nota.numero_nota }}</td>
                            <td>{{ formatarData(nota.data) }}</td>
                            <td>
                                <div v-for="item in nota.items" :key="item.id">
                                    {{ item.kg }} 
                                </div>
                            </td>
                            <td>
                                <div v-for="item in nota.items" :key="item.id">
                                    R$ {{ formatarValor(item.valor_kg) }}
                                </div>
                            </td>
                            <td>R$ {{ formatarValor(nota.valor_total) }}</td>
                            <td>
                                <span :class="getStatusClass(nota.status)">
                                    {{ nota.status }}
                                </span>
                            </td>
                            <td>
                                <button 
                                    v-if="nota.status === 'ABERTA'"
                                    @click="abrirModalPagamento(nota)"
                                    class="btn btn-success"
                                >
                                    Marcar como Paga
                                </button>
                                
                                <button 
                                    v-if="nota.status === 'PAGA PARCIALMENTE'"
                                    @click="abrirModalPagamentoRestante(nota)"
                                    class="btn btn-warning"
                                >
                                    Pagar Restante: R$ {{ formatarValor(nota.valor_pendente) }}
                                </button>
                                
                                <button 
                                    v-if="nota.status === 'PAGA' || nota.status === 'PAGA PARCIALMENTE'"
                                    @click="verHistorico(nota)"
                                    class="btn btn-info"
                                >
                                    Ver Histórico
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!notas.length">
                            <td colspan="8" class="text-center">Nenhuma nota encontrada.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal de Pagamento -->
        <div v-if="showPaymentModal" class="modal-overlay">
            <div class="modal-content">
                <h3>{{ isRestantePagamento ? 'Pagar Valor Restante' : 'Registrar Pagamento' }}</h3>
                
                <div class="nota-info" v-if="notaSelecionada">
                    <div class="info-row">
                        <div class="info-label">Valor Total da Nota:</div>
                        <div class="info-value">R$ {{ formatarValor(notaSelecionada.valor_total) }}</div>
                    </div>
                    
                    <div class="info-row" v-if="notaSelecionada.status === 'PAGA PARCIALMENTE'">
                        <div class="info-label">Valor Já Pago:</div>
                        <div class="info-value">R$ {{ formatarValor(notaSelecionada.valor_pago) }}</div>
                    </div>
                    
                    <div class="info-row" v-if="notaSelecionada.status === 'PAGA PARCIALMENTE'">
                        <div class="info-label">Valor Pendente:</div>
                        <div class="info-value text-warning">R$ {{ formatarValor(notaSelecionada.valor_pendente) }}</div>
                    </div>
                </div>
                
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
                    <label for="valor_pago">{{ isRestantePagamento ? 'Valor a Pagar' : 'Valor do Pagamento (R$)' }} *</label>
                    <input 
                        type="number" 
                        id="valor_pago"
                        v-model="paymentForm.valor_pago"
                        step="0.01"
                        min="0.01"
                        :max="getValorMaximo()"
                        @input="validarValorPagamento"
                        required
                    >
                    <div class="payment-info" v-if="isPagamentoParcial">
                        <span class="text-warning">Pagamento Parcial</span>
                        <p>Após este pagamento, o valor pendente será: R$ {{ formatarValor(valorPendente) }}</p>
                    </div>
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
                    <button @click="confirmarPagamento" class="btn btn-success" :disabled="!isValidPayment">
                        {{ isRestantePagamento ? 'Confirmar Pagamento' : 'Registrar Pagamento' }}
                    </button>
                    <button @click="showPaymentModal = false" class="btn btn-secondary">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Pagamento em Lote -->
        <div v-if="showBatchPaymentModal" class="modal-overlay">
            <div class="modal-content modal-lg fixed-footer-modal">
                <div class="modal-header sticky-header">
                    <h3>Pagamento em Lote</h3>
                    <button @click="showBatchPaymentModal = false" class="close-button">&times;</button>
                </div>
                
                <div class="scrollable-container">
                    <div class="batch-summary">
                        <div class="summary-item">
                            <span class="summary-label">Notas Selecionadas:</span>
                            <span class="summary-value">{{ notasSelecionadas.length }}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Valor Total:</span>
                            <span class="summary-value">R$ {{ formatarValor(totalNotasSelecionadas) }}</span>
                        </div>
                    </div>
                    
                    <div class="notas-lote-lista">
                        <h4>Notas Incluídas</h4>
                        <table class="compact-table">
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Data</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="nota in notasSelecionadasDetalhes" :key="nota.id">
                                    <td>{{ nota.numero_nota }}</td>
                                    <td>{{ formatarData(nota.data) }}</td>
                                    <td class="text-right">R$ {{ formatarValor(nota.valor_total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-group">
                        <label for="batch_data_pagamento">Data do Pagamento *</label>
                        <input 
                            type="date" 
                            id="batch_data_pagamento"
                            v-model="batchPaymentForm.data_pagamento"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="batch_observacao">Observação (será aplicada a todas as notas)</label>
                        <textarea 
                            id="batch_observacao"
                            v-model="batchPaymentForm.observacao"
                            rows="3"
                            placeholder="Observação (opcional)"
                        ></textarea>
                    </div>
                    
                    <!-- Espaço extra para garantir que o conteúdo não seja ocultado pelo rodapé fixo -->
                    <div class="footer-spacer"></div>
                </div>
                
                <div class="modal-footer sticky-footer">
                    <button 
                        @click="confirmarPagamentoLote" 
                        class="btn btn-success"
                        :disabled="!batchPaymentForm.data_pagamento || processandoPagamentoLote"
                    >
                        {{ processandoPagamentoLote ? 'Processando...' : 'Confirmar Pagamento' }}
                    </button>
                    <button 
                        @click="showBatchPaymentModal = false" 
                        class="btn btn-secondary"
                        :disabled="processandoPagamentoLote"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de Histórico -->
        <div v-if="showHistoricoModal" class="modal-overlay">
            <div class="modal-content">
                <h3>Histórico de Pagamentos</h3>
                
                <div class="nota-info" v-if="notaSelecionada">
                    <div class="info-row">
                        <div class="info-label">Nota:</div>
                        <div class="info-value">{{ notaSelecionada.numero_nota }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Valor Total:</div>
                        <div class="info-value">R$ {{ formatarValor(notaSelecionada.valor_total) }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value">
                            <span :class="getStatusClass(notaSelecionada.status)">
                                {{ notaSelecionada.status }}
                            </span>
                        </div>
                    </div>
                    <div class="info-row" v-if="notaSelecionada.data_pagamento">
                        <div class="info-label">Última data de pagamento:</div>
                        <div class="info-value">{{ formatarData(notaSelecionada.data_pagamento) }}</div>
                    </div>
                    <div class="info-row" v-if="notaSelecionada.observacao_pagamento">
                        <div class="info-label">Observação:</div>
                        <div class="info-value">{{ notaSelecionada.observacao_pagamento }}</div>
                    </div>
                </div>
                
                <div class="historico-container">
                    <table class="historico-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Valor</th>
                                <th>Observação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pagamento in notaSelecionada.pagamentos" :key="pagamento.id">
                                <td>{{ formatarData(pagamento.data_pagamento) }}</td>
                                <td class="valor-pagamento">R$ {{ formatarValor(pagamento.valor) }}</td>
                                <td>{{ pagamento.observacao || '-' }}</td>
                            </tr>
                            <tr v-if="!notaSelecionada.pagamentos || notaSelecionada.pagamentos.length === 0">
                                <td colspan="3" class="text-center">Nenhum pagamento registrado</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="notaSelecionada.pagamentos && notaSelecionada.pagamentos.length > 0">
                            <tr>
                                <td><strong>Total Pago:</strong></td>
                                <td class="valor-pagamento total-pago">
                                    R$ {{ formatarValor(getTotalPagamentos()) }}
                                </td>
                                <td></td>
                            </tr>
                            <tr v-if="notaSelecionada.valor_pendente > 0">
                                <td><strong>Pendente:</strong></td>
                                <td class="valor-pagamento valor-pendente">
                                    R$ {{ formatarValor(notaSelecionada.valor_pendente) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="modal-actions">
                    <button 
                        v-if="notaSelecionada && (notaSelecionada.status === 'PAGA' || notaSelecionada.status === 'PAGA PARCIALMENTE')"
                        @click="cancelarPagamento" 
                        class="btn btn-danger"
                    >
                        Cancelar Pagamento
                    </button>
                    <button @click="showHistoricoModal = false" class="btn btn-secondary">
                        Fechar
                    </button>
                </div>
            </div>
        </div>

        <!-- Paginação -->
        <div class="pagination-container" v-if="paginacao.total > 0">
            <div class="pagination-info">
                {{ paginationInfo }}
            </div>
            <div class="pagination-controls">
                <button 
                    @click="mudarPagina(paginacao.current_page - 1)" 
                    :disabled="paginacao.current_page === 1"
                    class="btn-pagination"
                >
                    Anterior
                </button>
                <span class="page-info">
                    Página {{ paginacao.current_page }} de {{ paginacao.last_page }}
                </span>
                <button 
                    @click="mudarPagina(paginacao.current_page + 1)" 
                    :disabled="paginacao.current_page === paginacao.last_page"
                    class="btn-pagination"
                >
                    Próxima
                </button>
            </div>
        </div> 
    </div>
</template>

<script>
export default {
    name: 'BuscarNotas',
    data() {
        return {
            loading: false,
            clientes: [],
            notas: [],
            searchCliente: '',
            filteredClientes: [],
            showResults: false,
            selectedCliente: null,
            dataInicial: '',
            dataFinal: '',
            totalEmAberto: 0,
            totalPago: 0,
            showPaymentModal: false,
            notaSelecionada: null,
            paymentForm: {
                data_pagamento: '',
                observacao: '',
                valor_pago: 0
            },
            isPagamentoParcial: false,
            valorPendente: 0,
            isRestantePagamento: false,
            showHistoricoModal: false,
            paginacao: {
                current_page: 1,
                last_page: 1,
                total: 0,
                per_page: 15
            },
            notasSelecionadas: [],
            showBatchPaymentModal: false,
            batchPaymentForm: {
                data_pagamento: new Date().toISOString().split('T')[0],
                observacao: ''
            },
            processandoPagamentoLote: false,
        };
    },

    computed: {
        isValidPayment() {
            return this.paymentForm.data_pagamento && 
                this.paymentForm.valor_pago > 0 && 
                this.paymentForm.valor_pago <= this.getValorMaximo();
        },

        paginationInfo() {
            const inicio = ((this.paginacao.current_page - 1) * this.paginacao.per_page) + 1;
            const fim = Math.min(this.paginacao.current_page * this.paginacao.per_page, this.paginacao.total);
            return `${inicio}-${fim} de ${this.paginacao.total} notas`;
        },

        notasSelecionaveis() {
            return this.notas.filter(nota => nota.status === 'ABERTA');
        },
        
        todasSelecionadas() {
            return this.notasSelecionaveis.length > 0 && 
                this.notasSelecionaveis.every(nota => 
                    this.notasSelecionadas.includes(nota.id)
                );
        },
        
        totalNotasSelecionadas() {
            return this.notas
                .filter(nota => this.notasSelecionadas.includes(nota.id))
                .reduce((total, nota) => total + parseFloat(nota.valor_total), 0);
        },
        
        notasSelecionadasDetalhes() {
            return this.notas.filter(nota => this.notasSelecionadas.includes(nota.id));
        }
    },

    methods: {

        filterClientes() {
            this.showResults = true;
            
            if (!this.searchCliente) {
                this.filteredClientes = [...this.clientes];
                return;
            }

            const search = this.searchCliente.toLowerCase().trim();
            this.filteredClientes = this.clientes.filter(cliente => 
                cliente.nome.toLowerCase().includes(search)
            );
        },

        async selectCliente(cliente) {
            if (!cliente) return;
            
            this.selectedCliente = cliente;
            this.searchCliente = cliente.nome;
            this.selectedClienteId = cliente.id;
            this.showResults = false;
            
            this.buscarNotas();
        },

        handleClickOutside(event) {
            const wrapper = this.$refs.autocomplete;
            if (wrapper && !wrapper.contains(event.target)) {
                this.showResults = false;
            }
        },


        async cancelarPagamento() {
            if (confirm('Tem certeza que deseja cancelar este pagamento? A nota voltará para o status ABERTA e o histórico de pagamentos será removido.')) {
                try {
                    await axios.put(`/api/notas/${this.notaSelecionada.id}/cancelar-pagamento`);
                    
                    alert('Pagamento cancelado com sucesso!');
                    this.showHistoricoModal = false;
                    await this.buscarNotas();
                } catch (error) {
                    console.error('Erro ao cancelar pagamento:', error);
                    alert('Erro ao cancelar o pagamento');
                }
            }
        },

        formatarValor(valor) {
            return Number(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatarData(data) {
            if (!data) return '-'; 
            
            try {
                const dataPura = typeof data === 'string' ? data.split('T')[0] : '';
                if (!dataPura) return '-';
                
                const [ano, mes, dia] = dataPura.split('-');
                if (!ano || !mes || !dia) return '-';
                
                return `${dia}/${mes}/${ano}`;
            } catch (error) {
                console.error('Erro ao formatar data:', error, data);
                return '-';
            }
        },

        getStatusClass(status) {
            return {
                'status-tag': true,
                'status-aberta': status === 'ABERTA',
                'status-paga': status === 'PAGA',
                'status-parcial': status === 'PAGA PARCIALMENTE'
            };
        },

        async loadClientes() {
            this.loading = true;
            try {
                const response = await axios.get('/api/clientes/list');
                this.clientes = response.data;
                
            } catch (error) {
                console.error('Erro ao carregar clientes:', error);
                alert('Erro ao carregar a lista de clientes');
            } finally {
                this.loading = false; // Desativa loading
            }
        },

        async buscarNotas(page = 1) {
            this.loading = true;
            this.notas = [];
            this.totalEmAberto = 0;
            this.totalPago = 0;
            this.notasSelecionadas = [];

            if (!this.selectedClienteId) {
                return;
            }

            try {
                let url = `/api/notas/cliente/${this.selectedClienteId}`;
                const params = new URLSearchParams();
                
                params.append('page', page);
                params.append('per_page', 15);
                
                if (this.dataInicial) {
                    params.append('data_inicial', this.dataInicial);
                }
                if (this.dataFinal) {
                    params.append('data_final', this.dataFinal);
                }

                const queryString = params.toString();
                url += `?${queryString}`;
                
                const response = await axios.get(url);

                if (response.data.notas) {
                    this.notas = response.data.notas;
                } else if (Array.isArray(response.data)) {
                    this.notas = response.data;
                } else {
                    this.notas = [];
                    console.error('Formato de resposta inesperado:', response.data);
                }
                
                this.paginacao = {
                    current_page: response.data.current_page || 1,
                    last_page: response.data.last_page || 1,
                    total: response.data.total || 0,
                    per_page: response.data.per_page || 15
                };
                
                if (this.notas.length > 0) {
                    this.calcularTotais();
                }

                await this.calcularTotais();
                
            } catch (error) {
                console.error('Erro ao buscar notas:', error);
                this.notas = [];
            } finally {
                this.loading = false;
            }
        },

        async mudarPagina(page) {
            if (page >= 1 && page <= this.paginacao.last_page) {
                await this.buscarNotas(page);
            }
        },

        verHistorico(nota) {
            this.notaSelecionada = nota;
            this.showHistoricoModal = true;
        },

        getTotalPagamentos() {
            if (!this.notaSelecionada || !this.notaSelecionada.pagamentos) return 0;
            
            return this.notaSelecionada.pagamentos.reduce((total, pagamento) => {
                return total + parseFloat(pagamento.valor);
            }, 0);
        },

        async calcularTotais() {
            if (!this.selectedClienteId) {
                this.totalEmAberto = 0;
                this.totalPago = 0;
                return;
            }
            
            try {
                const response = await axios.get(`/api/notas/cliente/${this.selectedClienteId}`, {
                    params: {
                        all: 1
                    }
                });
                
                if (response.data.totais) {
                    this.totalEmAberto = response.data.totais.total_em_aberto;
                    this.totalPago = response.data.totais.total_pago;
                } else {
                    this.totalEmAberto = 0;
                    this.totalPago = 0;
                }
                
            } catch (error) {
                console.error('Erro ao calcular totais:', error);
                this.totalEmAberto = 0;
                this.totalPago = 0;
            }
        },

        abrirModalPagamento(nota) {
            this.notaSelecionada = nota;
            this.paymentForm.data_pagamento = new Date().toISOString().split('T')[0];
            this.paymentForm.observacao = '';
            this.paymentForm.valor_pago = nota.valor_total;
            this.isPagamentoParcial = false;
            this.valorPendente = 0;
            this.isRestantePagamento = false;
            this.showPaymentModal = true;
        },

        abrirModalPagamentoRestante(nota) {
            this.notaSelecionada = nota;
            this.paymentForm.data_pagamento = new Date().toISOString().split('T')[0];
            this.paymentForm.observacao = 'Pagamento de valor restante';
            this.paymentForm.valor_pago = nota.valor_pendente;  // Pré-preencher com o valor pendente
            this.isPagamentoParcial = false;
            this.valorPendente = 0;
            this.isRestantePagamento = true;
            this.showPaymentModal = true;
        },

        getValorMaximo() {
            if (!this.notaSelecionada) return 0;
            
            return this.notaSelecionada.status === 'PAGA PARCIALMENTE'
                ? this.notaSelecionada.valor_pendente
                : this.notaSelecionada.valor_total;
        },

        validarValorPagamento() {
            if (!this.notaSelecionada) return;
            
            const valorMax = this.getValorMaximo();
            
        
            if (this.paymentForm.valor_pago <= 0) {
                this.paymentForm.valor_pago = ''; 
            } else if (this.paymentForm.valor_pago > valorMax) {
                this.paymentForm.valor_pago = valorMax;
            }
            
            const valorAtual = parseFloat(this.paymentForm.valor_pago) || 0;
            const valorTotalPago = (this.notaSelecionada.status === 'PAGA PARCIALMENTE' 
                ? parseFloat(this.notaSelecionada.valor_pago) || 0 
                : 0) + valorAtual;
            
            this.isPagamentoParcial = valorTotalPago < parseFloat(this.notaSelecionada.valor_total);
            this.valorPendente = parseFloat(this.notaSelecionada.valor_total) - valorTotalPago;
            
            if (this.valorPendente < 0.01) this.valorPendente = 0;
        },

        async confirmarPagamento() {
            if (!this.paymentForm.data_pagamento || this.paymentForm.valor_pago <= 0) {
                alert('Preencha a data de pagamento e um valor válido');
                return;
            }

            const valorPendente = this.notaSelecionada.status === 'PAGA PARCIALMENTE' 
                ? parseFloat(this.notaSelecionada.valor_pendente)
                : parseFloat(this.notaSelecionada.valor_total);

            if (parseFloat(this.paymentForm.valor_pago) > valorPendente) {
                alert(`O valor máximo para pagamento é R$ ${this.formatarValor(valorPendente)}`);
                this.paymentForm.valor_pago = valorPendente;
                return;
            }

            try {
                const novoValorPago = parseFloat(this.paymentForm.valor_pago);
                const valorJaPago = this.notaSelecionada.status === 'PAGA PARCIALMENTE' 
                    ? parseFloat(this.notaSelecionada.valor_pago) || 0
                    : 0;
                    
                let mensagem = '';
                const valorTotalNota = parseFloat(this.notaSelecionada.valor_total);
                const novoTotalPago = valorJaPago + novoValorPago;
                
                if (novoTotalPago >= valorTotalNota) {
                    mensagem = 'Nota quitada com sucesso!';
                } else {
                    const novoValorPendente = valorTotalNota - novoTotalPago;
                    mensagem = `Pagamento parcial registrado! Valor pendente: R$ ${this.formatarValor(novoValorPendente)}`;
                }

                const response = await axios.put(`/api/notas/${this.notaSelecionada.id}/pagar`, {
                    data_pagamento: this.paymentForm.data_pagamento,
                    observacao: this.paymentForm.observacao,
                    valor_pago: novoValorPago,
                    ja_pago: valorJaPago
                });

                this.showPaymentModal = false;
                await this.buscarNotas(this.paginacao.current_page);
                
                alert(mensagem);
            } catch (error) {
                console.error('Erro ao pagar nota:', error);
                alert('Erro ao processar o pagamento da nota');
            }
        },

        selecionarTodas(checked) {
            if (checked) {
                this.notasSelecionadas = this.notasSelecionaveis.map(nota => nota.id);
            } else {
                this.notasSelecionadas = [];
            }
        },

        limparSelecao() {
            this.notasSelecionadas = [];
        },

        abrirModalPagamentoLote() {
            if (this.notasSelecionadas.length === 0) {
                alert('Selecione pelo menos uma nota para pagamento');
                return;
            }
            
            this.batchPaymentForm.data_pagamento = new Date().toISOString().split('T')[0];
            this.batchPaymentForm.observacao = '';
            this.showBatchPaymentModal = true;
        },

        async confirmarPagamentoLote() {
            if (!this.batchPaymentForm.data_pagamento) {
                alert('A data de pagamento é obrigatória');
                return;
            }
            
            if (this.notasSelecionadas.length === 0) {
                alert('Nenhuma nota selecionada');
                return;
            }
            
            this.processandoPagamentoLote = true;
            
            try {
                const response = await axios.post('/api/notas/pagamento-lote', {
                    notas_ids: this.notasSelecionadas,
                    data_pagamento: this.batchPaymentForm.data_pagamento,
                    observacao: this.batchPaymentForm.observacao
                });
                
                this.showBatchPaymentModal = false;
                this.notasSelecionadas = [];
                await this.buscarNotas(this.paginacao.current_page);
                
                alert(`Pagamento em lote concluído! ${response.data.sucessos} nota(s) processada(s) com sucesso.`);
                
            } catch (error) {
                console.error('Erro ao processar pagamento em lote:', error);
                alert('Erro ao processar pagamento em lote. Verifique o console para mais detalhes.');
            } finally {
                this.processandoPagamentoLote = false;
            }
        },
        
    },
    mounted() {
        document.addEventListener('click', this.handleClickOutside);
    },

    beforeDestroy() {
        document.removeEventListener('click', this.handleClickOutside);
    },
        async created() {
            await this.loadClientes();

        if (this.$route.query.cliente_id) {
            this.selectedClienteId = this.$route.query.cliente_id;
            this.searchCliente = this.$route.query.cliente_nome;
            this.selectedCliente = {
                id: this.$route.query.cliente_id,
                nome: this.$route.query.cliente_nome
            };
            await this.buscarNotas();
        }

    },
}
</script>

<style scoped>
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
    margin-bottom: 20px;
    width: 95%;
    margin: 0 auto 24px;
}

.table-container {
    background: white;
    border-radius: 8px;
    padding: 24px;
    margin: 20px auto;
    width: 95%;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.select-cliente {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.select-cliente:focus {
    outline: none;
    border-color: #4338ca;
    box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);
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
    font-size: 1.5em;
    font-weight: bold;
}

.text-danger {
    color: #dc3545;
}

.text-success {
    color: #28a745;
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

.status-aberta {
    background-color: #fef3c7;
    color: #92400e;
}

.status-paga {
    background-color: #d1fae5;
    color: #065f46;
}

.nota-paga {
    background-color: #f8f9fa;
}

.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 500;
}

.btn-success {
    background-color: #28a745;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-success:hover {
    background-color: #218838;
}

.nota-info {
    background-color: #f8fafc;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-label {
    font-weight: 500;
    color: #4b5563;
}

.info-value {
    font-weight: 600;
    color: #1f2937;
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

.btn-danger {
    background-color: #ef4444;
    color: white;
    margin-right: 8px;
}

.btn-danger:hover {
    background-color: #dc2626;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }

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

    td, th {
        white-space: nowrap;
        padding: 12px;
    }

    .table-container {
        overflow-x: auto;
    }
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

.valor-total {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    font-size: 15px;
    font-weight: 600;
    color: #1a1a1a;
    letter-spacing: -0.2px;
    text-align: right;
    padding-right: 24px;
}

.btn-edit {
    background-color: #34d399 !important;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    border: none;
    font-size: 13px;
    font-weight: 500;
    min-width: 80px;
    text-align: center;
    cursor: pointer;
}

.btn-info {
    background-color: #3b82f6 !important;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    border: none;
    font-size: 13px;
    font-weight: 500;
    min-width: 80px;
    text-align: center;
    cursor: pointer;
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

.status-parcial {
    background-color: #feef89;
    color: #854d0e;
}

.payment-info {
    margin-top: 8px;
    padding: 8px;
    background-color: #fff9db;
    border-radius: 4px;
    border: 1px solid #ffd980;
}

.text-warning {
    color: #f59e0b;
    font-weight: 500;
}

.btn-warning {
    background-color: #f59e0b;
    color: white;
}

.btn-warning:hover {
    background-color: #d97706;
}

.historico-container {
    max-height: 300px;
    overflow-y: auto;
    margin: 15px 0;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
}

.historico-table {
    width: 100%;
    border-collapse: collapse;
}

.historico-table th,
.historico-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.historico-table th {
    background-color: #f7fafc;
    font-weight: 500;
    color: #4b5563;
}

.historico-table tbody tr:last-child td {
    border-bottom: none;
}

.historico-table tfoot {
    border-top: 2px solid #e2e8f0;
    background-color: #f7fafc;
}

.valor-pagamento {
    text-align: right;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    font-weight: 500;
}

.total-pago {
    color: #059669;
}

.valor-pendente {
    color: #f59e0b;
}

.pagination-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    background: white;
    border-radius: 0 0 8px 8px;
    margin-top: 20px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
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

.checkbox-column {
    width: 40px;
    text-align: center;
}

.checkbox-input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

batch-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #eef2ff;
    border: 1px solid #c7d2fe;
    padding: 12px 16px;
    border-radius: 6px;
    margin-top: 20px;
}

.selected-info {
    font-weight: 500;
    color: #4f46e5;
}

.modal-lg {
    max-width: 700px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 20px;
}

.close-button {
    background: none;
    border: none;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
}

.batch-summary {
    display: flex;
    justify-content: space-around;
    background-color: #f8fafc;
    padding: 16px;
    border-radius: 6px;
    margin-bottom: 24px;
    border: 1px solid #e2e8f0;
}

.summary-item {
    text-align: center;
}

.summary-label {
    display: block;
    font-size: 14px;
    color: #64748b;
    margin-bottom: 4px;
}

.summary-value {
    font-size: 18px;
    font-weight: 600;
    color: #0f172a;
}

.notas-lote-lista {
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 20px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 16px;
}

.notas-lote-lista h4 {
    margin-top: 0;
    font-size: 16px;
    color: #334155;
    margin-bottom: 12px;
}

.compact-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.compact-table th,
.compact-table td {
    padding: 8px;
    border-bottom: 1px solid #e2e8f0;
}

.compact-table th {
    background-color: #f1f5f9;
    font-weight: 500;
    text-align: left;
}

.text-right {
    text-align: right;
}

.batch-action-bar {
    display: flex;
    align-items: center;
    background-color: white;
    padding: 14px 20px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin: 16px 0;
    gap: 16px;
}

.batch-info {
    flex: 1;
    display: flex;
    align-items: center;
    color: #4b5563;
    font-size: 15px;
}

.info-badge {
    background-color: #4338ca;
    color: white;
    border-radius: 20px;
    padding: 2px 8px;
    font-size: 14px;
    font-weight: 600;
    margin-right: 6px;
}

.valor-destaque {
    font-weight: 600;
    color: #1f2937;
    margin-left: 4px;
}

.btn-batch-pay {
    display: flex;
    align-items: center;
    gap: 8px;
    background-color: #4338ca;
    color: white;
    padding: 10px 18px;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.2s;
    cursor: pointer;
}

.btn-batch-pay:hover:not(:disabled) {
    background-color: #3730a3;
}

.btn-batch-pay:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-clear-selection {
    background-color: transparent;
    color: #6b7280;
    border: 1px solid #d1d5db;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
    cursor: pointer;
}

.btn-clear-selection:hover {
    background-color: #f3f4f6;
    color: #4b5563;
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

.fixed-footer-modal {
    display: flex !important;
    flex-direction: column !important;
    max-height: 85vh !important;
    position: relative !important;
    padding-bottom: 0 !important;
}

.sticky-header {
    position: sticky !important;
    top: 0 !important;
    background: white !important;
    z-index: 10 !important;
    border-bottom: 1px solid #e5e7eb !important;
    padding: 20px 20px 15px !important;
    margin: 0 !important;
}

.scrollable-container {
    flex: 1 !important;
    overflow-y: auto !important;
    padding: 0 20px !important;
    max-height: calc(85vh - 130px) !important;
}

.sticky-footer {
    position: sticky !important;
    bottom: 0 !important;
    background: white !important;
    z-index: 10 !important;
    border-top: 1px solid #e5e7eb !important;
    padding: 15px 20px !important;
    margin: 0 !important;
    display: flex !important;
    justify-content: flex-end !important;
    gap: 10px !important;
}

.footer-spacer {
    height: 20px !important;
}

.notas-lote-lista {
    overflow-x: auto !important;
}

.compact-table {
    width: 100% !important;
    table-layout: fixed !important;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

</style>