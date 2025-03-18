<template>
    <div class="container">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
            <div class="loading-spinner"></div>
    </div>    
            <!-- Cards informativos -->
            <div class="dashboard-grid">

                <div class="info-card">
                    <h3>Contas a Pagar</h3>
                    <div class="card-value">R$ {{ formatarValor(totalDespesasAberto) }}</div>
                    <div class="card-subtitle">{{ quantidadeDespesasAberto }} despesas em aberto</div>
                </div>

                <div class="info-card">
                    <h3>Clientes Cadastrados</h3>
                    <div class="card-value">{{ totalClientes }}</div>
                    <div class="card-subtitle">Total de clientes</div>
                </div>

                <div class="info-card">
                    <h3>Notas a Receber</h3>
                    <div class="card-value">R$ {{ formatarValor(totalNotasAberto) }}</div>
                    <div class="card-subtitle">{{ quantidadeNotasAberto }} notas em aberto</div>
                </div>
            </div>

            <div class="tables-grid">
                <!-- Despesas a Vencer -->
                <div class="table-container">
                    <div class="card-header">Despesas a vencer</div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Vencimento</th>
                                    <th>Fornecedor</th>
                                    <th>Valor</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="despesa in despesasAVencer" :key="despesa.id">
                                    <td>{{ despesa.id }}</td>
                                    <td class="vencimento">{{ formatarData(despesa.data_vencimento) }}</td>
                                    <td>{{ despesa.fornecedor?.nome }}</td>
                                    <td class="valor">R$ {{ formatarValor(despesa.valor) }}</td>
                                    <td>
                                        <router-link 
                                            :to="{
                                                name: 'buscar-despesas',
                                                query: {
                                                    fornecedor_id: despesa.fornecedor_id,
                                                    fornecedor_nome: despesa.fornecedor?.nome,
                                                    despesa_id: despesa.id,
                                                    action: 'pagar'
                                                }
                                            }"
                                            class="btn-pagar"
                                        >
                                            Pagar
                                        </router-link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Clientes com Notas Altas -->
                <div class="table-container">
                    <div class="card-header">Contas a Receber</div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Total em Aberto</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cliente in clientesNotasAltas" :key="cliente.id">
                                    <td>{{ cliente.nome }}</td>
                                    <td class="valor">R$ {{ formatarValor(cliente.total_aberto) }}</td>
                                    <td>
                                        <router-link 
                                            :to="{ 
                                                name: 'buscar-notas',
                                                query: { 
                                                    cliente_id: cliente.id,
                                                    cliente_nome: cliente.nome,
                                                    status: 'ABERTA'
                                                }
                                            }"
                                            class="btn-ver-notas"
                                        >
                                            Ver Notas
                                        </router-link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
</template>

<script>
import { emitter } from '../eventBus' 

    export default {
    name: 'Home',
    data() {
        return {
            loading: false,
            totalNotasAberto: 0,
            quantidadeNotasAberto: 0,
            totalClientes: 0,
            totalDespesasAberto: 0,
            quantidadeDespesasAberto: 0,
            despesasAVencer: [],
            clientesNotasAltas: [],
            showPaymentModal: false,
            selectedClient: null,
            paymentForm: {
                data_pagamento: '',
                observacao: ''
            }
        }
    },
    
    async created() {
        this.loading = true;
        try {
            await Promise.all([
                this.carregarTotaisNotas(),
                this.carregarTotalClientes(),
                this.carregarTotaisDespesas(),
                this.carregarDespesasAVencer(),
                this.carregarClientesNotasAltas()
            ]);
        } finally {
            this.loading = false; 
        }
    },

    mounted() {
            emitter.on('despesa-atualizada', () => {
            console.log('Evento recebido, recarregando dados...');
            this.recarregarDados().then(() => {
                console.log('Dados recarregados');
                console.log('Despesas a vencer:', this.despesasAVencer);
            });
        });
    },

    beforeUnmount() {
        emitter.off('despesa-atualizada');
    },

    
    methods: {

        async recarregarDados() {
            await Promise.all([
                this.carregarTotaisNotas(),
                this.carregarTotalClientes(),
                this.carregarTotaisDespesas(),
                this.carregarDespesasAVencer()
            ]);
        },

        async carregarTotaisNotas() {
            try {
                const response = await axios.get('/api/notas/totais');
                this.totalNotasAberto = response.data.total_valor;
                this.quantidadeNotasAberto = response.data.quantidade;
            } catch (error) {
                console.error('Erro ao carregar totais de notas:', error);
            }
        },

        async carregarTotalClientes() {
            try {
                const response = await axios.get('/api/clientes/total');
                this.totalClientes = response.data.total;
            } catch (error) {
                console.error('Erro ao carregar total de clientes:', error);
            }
        },

        async carregarTotaisDespesas() {
            try {
                const response = await axios.get('/api/despesas/totais');
                this.totalDespesasAberto = response.data.total_valor;
                this.quantidadeDespesasAberto = response.data.quantidade;
            } catch (error) {
                console.error('Erro ao carregar totais de despesas:', error);
            }
        },

        async carregarDespesasAVencer() {
            try {
                const response = await axios.get('/api/despesas/a-vencer');
                this.despesasAVencer = response.data;
            } catch (error) {
                console.error('Erro ao carregar despesas a vencer:', error);
            }
        },

        async carregarClientesNotasAltas() {
            try {
                const response = await axios.get('/api/clientes/notas-altas');
                this.clientesNotasAltas = response.data;
            } catch (error) {
                console.error('Erro ao carregar clientes com notas altas:', error);
            }
        },


        formatarValor(valor) {
            return Number(valor).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        },

        formatarData(data) {
            const [ano, mes, dia] = data.split('-');
            return `${dia}/${mes}/${ano}`;
        },
        formatarData(data) {
            if (!data) return '';
            const dataPura = data.split('T')[0];
            const [ano, mes, dia] = dataPura.split('-');
            return `${dia.padStart(2, '0')}/${mes.padStart(2, '0')}/${ano}`;
        },

        abrirModalPagamento(cliente) {
            this.selectedClient = cliente;
            this.paymentForm.data_pagamento = new Date().toISOString().split('T')[0];
            this.paymentForm.observacao = '';
            this.showPaymentModal = true;
        },

        async pagarTodasNotas() {
            if (!this.paymentForm.data_pagamento) {
                alert('A data de pagamento é obrigatória');
                return;
            }

            try {
                await axios.post(`/api/notas/pagar-todas`, {
                    cliente_id: this.selectedClient.id,
                    data_pagamento: this.paymentForm.data_pagamento,
                    observacao: this.paymentForm.observacao
                });

                this.showPaymentModal = false;
                alert('Notas pagas com sucesso!');
                await this.recarregarDados();
            } catch (error) {
                console.error('Erro ao pagar notas:', error);
                alert('Erro ao processar o pagamento das notas');
            }
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

.table-container {
    background: white;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
}

.table-container h2 {
   color: #374151;
   font-size: 18px;
   font-weight: 600;
   margin-bottom: 16px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    text-align: left;
    padding: 12px 20px;
    font-weight: 500;
    color: #666;
    font-size: 14px;
    background: white;
    border-bottom: 1px solid #eee;
}

td {
    padding: 12px 20px;
    font-size: 14px;
    border-bottom: 1px solid #eee;
    line-height: 1.4; 
}

tr {
    height: 52px; 
}

.valor {
   font-weight: 500;
   color: #333333;
   font-weight: bold; 
}

.vencimento {
   color: #dc2626;
   font-weight: bold; 
}


tr:hover {
   background-color: #f9fafb;
}

thead {
    position: sticky;
    top: 0;
    background: white;
    z-index: 1;
}

.btn-link, .btn-pagar {
    display: none; 
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.info-card {
    background: white;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-value {
    font-size: 32px;
    font-weight: 600;
    color: #4338ca;
    margin: 16px 0;
}

.card-subtitle {
    color: #6b7280;
    font-size: 14px;
}

.btn-pagar {
   background: #dc2626;
}

.btn-ver-notas {
   background: #22c55e;
}

.btn-pagar, .btn-ver-notas {
   display: inline-block; 
   padding: 6px 12px; 
   color: white;
   border-radius: 4px;
   text-decoration: none;
   font-size: 13px; 
}



.btn-link {
    background-color: #3b82f6;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}




.tables-grid {
   display: grid;
   grid-template-columns: 1fr 1fr;
   gap: 20px;
   margin: 20px auto;
   width: 95%;
}


.tables-grid .table-container {
    margin: 0;
    width: 100%;
    height: 100%;
}


.table-container {
   background: white;
   border-radius: 4px;
   box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
   min-height: 500px; 
}

.card-header {
    padding: 15px 20px;
    font-size: 25px;
    font-weight: 500;
    border-bottom: 1px solid #eee;
    color: #444;
    font-weight: bold; 
}


.table-container h2 {
    color: #666;
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}


.table-wrapper {
    overflow-y: auto;
    max-height: 300px; 
}

.table-wrapper::-webkit-scrollbar {
    width: 8px;  
}

.table-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1; 
    border-radius: 10px;
}

.table-wrapper::-webkit-scrollbar-thumb {
    background: #888; 
    border-radius: 10px;
    border: 2px solid #f1f1f1;
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
    background: #666;
}

.table-wrapper {
    scrollbar-width: thin;
    scrollbar-color: #888 #f1f1f1;
}


@media (max-width: 1024px) {
   .tables-grid {
       grid-template-columns: 1fr;
   }
}
</style>