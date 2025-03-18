<?php

namespace App\Http\Controllers;

use App\Models\Despesas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DespesasController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Despesas::with('fornecedor');

            // Log para debug
            Log::info('Parâmetros de filtro recebidos:', $request->all());

            // Filtro por ID
            if ($request->filled('id')) {
                Log::info('Aplicando filtro por ID:', ['id' => $request->id]);
                $query->where('id', $request->id);
            }

            // Filtro por fornecedor
            if ($request->filled('fornecedor_id')) {
                Log::info('Aplicando filtro por fornecedor:', ['fornecedor_id' => $request->fornecedor_id]);
                $query->where('fornecedor_id', $request->fornecedor_id);
            }

            // Filtro por período
            if ($request->filled('data_inicio')) {
                Log::info('Aplicando filtro data inicial:', ['data_inicio' => $request->data_inicio]);
                $query->whereDate('data', '>=', $request->data_inicio);
            }
            if ($request->filled('data_fim')) {
                Log::info('Aplicando filtro data final:', ['data_fim' => $request->data_fim]);
                $query->whereDate('data', '<=', $request->data_fim);
            }

            // Log da query final
            Log::info('Query SQL:', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
    
            $despesas = $query->orderBy('data', 'desc')
                             ->paginate(10);

            Log::info('Despesas encontradas:', [
                'total' => $despesas->total(),
                'filtros_aplicados' => $request->all()
            ]); 
    
            return response()->json([
                'data' => $despesas->items(),
                'current_page' => $despesas->currentPage(),
                'last_page' => $despesas->lastPage(),
                'total' => $despesas->total(),
                'per_page' => $despesas->perPage()
            ]);
    
        } catch (\Exception $e) {
            Log::error('Erro ao buscar despesas:', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao carregar despesas',
                'data' => []
            ], 500);
        }
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'data' => 'required|date',
            'produto' => 'required|string',
            'quantidade' => 'required|numeric|min:0',
            'fornecedor_id' => 'required|exists:fornecedores,id',
            'valor' => 'required|numeric|min:0',
            'data_vencimento' => 'required|date',
            'status' => 'required|in:PAGA,ABERTO'
        ]);

        $despesa = Despesas::create($request->all());
        return response()->json($despesa, 201);
    }

    public function show(Despesas $despesa)
    {
        return $despesa->load('fornecedor');
    }

    public function update(Request $request, Despesas $despesa)
    {
        $request->validate([
            'data' => 'required|date',
            'produto' => 'required|string',
            'quantidade' => 'required|numeric|min:0',
            'fornecedor_id' => 'required|exists:fornecedores,id',
            'valor' => 'required|numeric|min:0',
            'data_vencimento' => 'required|date',
            'status' => 'required|in:PAGA,ABERTO'
        ]);

        $despesa->update($request->all());
        return response()->json($despesa);
    }

    public function destroy(Despesas $despesa)
    {
        $despesa->delete();
        return response()->json(null, 204);
    }

    public function buscarPorFornecedor($fornecedor_id)
    {
        return Despesas::where('fornecedor_id', $fornecedor_id)
                     ->with('fornecedor')
                     ->orderBy('data', 'desc')
                     ->get();
    }

    
    public function buscarDespesas(Request $request)
    {
        try {
            Log::info('Recebendo requisição de busca:', $request->all());

            $query = Despesas::with('fornecedor');

            if ($request->has('fornecedor_id')) {
                Log::info('Filtrando por fornecedor:', ['fornecedor_id' => $request->fornecedor_id]);
                $query->where('fornecedor_id', $request->fornecedor_id);
            }

            if ($request->has('data_inicial')) {
                Log::info('Filtrando por data inicial:', ['data_inicial' => $request->data_inicial]);
                $query->whereDate('data', '>=', $request->data_inicial);
            }

            if ($request->has('data_final')) {
                Log::info('Filtrando por data final:', ['data_final' => $request->data_final]);
                $query->whereDate('data', '<=', $request->data_final);
            }

            $despesas = $query->orderBy('data', 'desc')->get();
            
            Log::info('Despesas encontradas:', ['quantidade' => $despesas->count()]);
            
            return response()->json([
                'despesas' => $despesas,
                'success' => true,
                'count' => $despesas->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar despesas:', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao buscar despesas',
                'error' => $e->getMessage(),
                'despesas' => []
            ], 500);
        }
    }



    public function pagarDespesa(Request $request, Despesas $despesa)
    {
        try {
            Log::info('Iniciando pagamento da despesa:', ['id' => $despesa->id]);
            
            $validated = $request->validate([
                'data_pagamento' => 'required|date',
                'observacao' => 'nullable|string'
            ]);

            $despesa->update([
                'status' => 'PAGA',
                'data_pagamento' => $validated['data_pagamento'],
                'observacao' => $validated['observacao']
            ]);

            Log::info('Despesa paga com sucesso');

            return response()->json([
                'message' => 'Pagamento registrado com sucesso',
                'despesa' => $despesa
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao registrar pagamento:', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Erro ao registrar pagamento'
            ], 500);
        }
    }

    public function cancelarPagamento(Despesas $despesa)
    {
        try {
            $despesa->update([
                'status' => 'ABERTO',
                'data_pagamento' => null,
                'observacao' => null
            ]);

            // Log da despesa após atualização
            Log::info('Despesa após cancelamento:', [
                'despesa_id' => $despesa->id,
                'status' => $despesa->status,
                'data_vencimento' => $despesa->data_vencimento
            ]);

            return response()->json(['message' => 'Pagamento cancelado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao cancelar pagamento'], 500);
        }
    }

    public function totais()
    {
        $totais = Despesas::where('status', 'ABERTO')
            ->selectRaw('COUNT(*) as quantidade, SUM(valor) as total_valor')
            ->first();

        return response()->json([
            'quantidade' => $totais->quantidade,
            'total_valor' => $totais->total_valor ?? 0
        ]);
    }

    public function aVencer()
    {
        $dataInicial = now()->startOfDay();
        $dataFinal = now()->addDays(7)->endOfDay();

        $despesas = Despesas::with('fornecedor')
            ->where('status', 'ABERTO')
            ->where(function ($query) use ($dataInicial, $dataFinal) {
                $query->whereBetween('data_vencimento', [$dataInicial, $dataFinal])
                    ->orWhere(function ($q) {
                        $q->where('status', 'ABERTO')
                            ->where('data_vencimento', '<', now());
                    });
            })
            ->orderBy('data_vencimento')
            ->get();

        return response()->json($despesas);
    }
}


