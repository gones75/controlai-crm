<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\PagamentoNota;

class NotaController extends Controller
{
    public function index(Request $request)
    {
        
        try {

            $query = Nota::with(['cliente', 'items']);

            Log::info('Parâmetros de filtro recebidos:', $request->all());

            if ($request->filled('nome_cliente')) {
                $query->whereHas('cliente', function ($q) use ($request) {
                    $q->where('nome', 'LIKE', '%' . $request->nome_cliente . '%');
                });
            }

            if ($request->filled('numero_nota')) {
                $query->where('numero_nota', 'LIKE', '%' . $request->numero_nota . '%');
            }

            if ($request->filled('data_inicio')) {
                $query->whereDate('data', '>=', $request->data_inicio);
            }
            
            if ($request->filled('data_fim')) {
                $query->whereDate('data', '<=', $request->data_fim);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            Log::info('Query SQL:', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);

            $notas = $query->orderBy('data', 'desc')
                      ->paginate($request->input('per_page', 10))
                      ->withQueryString();

            $notas->load('cliente', 'items');

            Log::info('Notas encontradas:', [
                'total' => $notas->total(),
                'filtros_aplicados' => $request->all()
            ]);
            
            return response()->json([
                'data' => $notas->items(),
                'current_page' => $notas->currentPage(),
                'last_page' => $notas->lastPage(),
                'total' => $notas->total(),
                'per_page' => $notas->perPage()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar notas: ' . $e->getMessage(), [
                'exception' => $e,
                'filtros' => $request->all()
            ]);
            return response()->json([
                'message' => 'Erro ao carregar notas',
                'data' => []
            ], 500);
        }
    }

    public function getClientes()
    {
        try {
            $clientes = Cliente::select('id', 'nome')->get();
            return response()->json($clientes);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar clientes: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao carregar clientes'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            Log::info('Dados recebidos:', $request->all());


            $validated = $request->validate([
                'cliente_id' => 'required|exists:clientes,id',
                'numero_nota' => 'required|string|unique:notas',
                'data' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.kg' => 'required|numeric|min:0',
                'items.*.valor_kg' => 'required|numeric|min:0',
                'items.*.valor_total' => 'required|numeric|min:0',
                'valor_total' => 'required|numeric|min:0'
            ]);
            
            Log::info('Dados validados:', $validated);

            $nota = Nota::create([
                'cliente_id' => $validated['cliente_id'],
                'numero_nota' => $validated['numero_nota'],
                'data' => $validated['data'],
                'valor_total' => $validated['valor_total'],
                'status' => 'ABERTA'
            ]);

            foreach ($validated['items'] as $item) {
                $nota->items()->create([
                    'kg' => $item['kg'],
                    'valor_kg' => $item['valor_kg'],
                    'valor_total' => $item['valor_total']
                ]);
            }
            
            DB::commit();

            return response()->json([
                'message' => 'Nota criada com sucesso',
                'nota' => $nota->load('items', 'cliente')
            ], 201);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Erro de validação:', ['errors' => $e->errors()]);
            
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar nota: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao criar nota: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $nota = Nota::with(['cliente', 'items', 'pagamentos'])->findOrFail($id);
            
            return response()->json($nota);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar nota: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao buscar nota'], 500);
        }
    }

    public function update(Request $request, Nota $nota)
        {
            DB::beginTransaction();
            try {
                $validated = $request->validate([
                    'cliente_id' => 'required|exists:clientes,id',
                    'numero_nota' => 'required|string|unique:notas,numero_nota,' . $nota->id,
                    'data' => 'required|date',
                    'items' => 'required|array|min:1',
                    'items.*.kg' => 'required|numeric|min:0',
                    'items.*.valor_kg' => 'required|numeric|min:0',
                    'items.*.valor_total' => 'required|numeric|min:0',
                    'valor_total' => 'required|numeric|min:0'
                ]);
        
                
                $nota->update([
                    'cliente_id' => $validated['cliente_id'],
                    'numero_nota' => $validated['numero_nota'],
                    'data' => $validated['data'],
                    'valor_total' => $validated['valor_total']
                ]);
        
                
                $nota->items()->delete();
        
                
                foreach ($validated['items'] as $item) {
                    $nota->items()->create([
                        'kg' => $item['kg'],
                        'valor_kg' => $item['valor_kg'],
                        'valor_total' => $item['valor_total']
                    ]);
                }
                
                DB::commit();
        
                return response()->json([
                    'message' => 'Nota atualizada com sucesso',
                    'nota' => $nota->load('items', 'cliente')
                ]);
        
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Erro ao atualizar nota: ' . $e->getMessage());
                return response()->json([
                    'message' => 'Erro ao atualizar nota: ' . $e->getMessage()
                ], 500);
            }
        }

    public function destroy(Nota $nota)
    {
        try {
            $nota->delete();
            return response()->json([
                'message' => 'Nota excluída com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir nota: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao excluir nota'
            ], 500);
        }
    }

    public function getNotasByCliente($clienteId, Request $request)
    {
        try {
            $query = Nota::with(['items', 'cliente', 'pagamentos'])
                ->where('cliente_id', $clienteId);

            if ($request->get('all') == 1) {
                $todasNotas = $query->get();
                
                $totalEmAberto = 0;
                $totalPago = 0;

                foreach ($todasNotas as $nota) {
                    if ($nota->status === 'ABERTA') {
                        $totalEmAberto += floatval($nota->valor_total);
                    } elseif ($nota->status === 'PAGA PARCIALMENTE') {
                        $totalEmAberto += floatval($nota->valor_pendente);
                        $totalPago += floatval($nota->valor_pago);
                    } elseif ($nota->status === 'PAGA') {
                        $totalPago += floatval($nota->valor_total);
                    }
                }

                return response()->json([
                    'totais' => [
                        'total_em_aberto' => round($totalEmAberto, 2),
                        'total_pago' => round($totalPago, 2)
                    ]
                ]);
            }

            $perPage = $request->input('per_page', 15);
            $notas = $query->orderBy('data', 'desc')->paginate($perPage);

            return response()->json([
                'notas' => $notas->items(),
                'current_page' => $notas->currentPage(),
                'last_page' => $notas->lastPage(),
                'total' => $notas->total(),
                'per_page' => $notas->perPage()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar notas'
            ], 500);
        }
    }

    public function pagarNota(Request $request, Nota $nota)
    {
        try {
            $validated = $request->validate([
                'data_pagamento' => 'required|date',
                'observacao' => 'nullable|string',
                'valor_pago' => 'required|numeric|min:0.01',
                'ja_pago' => 'nullable|numeric'
            ]);

            
            $novoValorPago = (float)$validated['valor_pago'];
            $valorJaPago = isset($validated['ja_pago']) ? (float)$validated['ja_pago'] : 0;
            $valorTotalPago = $valorJaPago + $novoValorPago;
            $valorTotal = (float)$nota->valor_total;
            
            
            if ($valorTotalPago > $valorTotal) {
                $valorTotalPago = $valorTotal;
                $novoValorPago = $valorTotal - $valorJaPago;
            }
            
            
            $valorPendente = $valorTotal - $valorTotalPago;
            if ($valorPendente < 0.01) {
                $valorPendente = 0;
            }
            
            
            $novoStatus = $valorPendente > 0 ? 'PAGA PARCIALMENTE' : 'PAGA';
            
            DB::beginTransaction();

            PagamentoNota::create([
                'nota_id' => $nota->id,
                'valor' => $novoValorPago,
                'data_pagamento' => $validated['data_pagamento'],
                'observacao' => $validated['observacao']
            ]);
            
            $nota->update([
                'status' => $novoStatus,
                'data_pagamento' => $validated['data_pagamento'],
                'observacao_pagamento' => $validated['observacao'],
                'valor_pago' => $valorTotalPago,
                'valor_pendente' => $valorPendente
            ]);

            DB::commit();

            return response()->json([
                'message' => $novoStatus === 'PAGA PARCIALMENTE' ? 'Pagamento parcial registrado com sucesso' : 'Nota quitada com sucesso',
                'nota' => $nota->fresh(['pagamentos'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao pagar nota: ' . $e->getMessage(), [
                'nota_id' => $nota->id,
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => 'Erro ao processar pagamento da nota'
            ], 500);
        }
    }

    public function cancelarPagamento(Nota $nota)
    {
        try {
            DB::beginTransaction();

            PagamentoNota::where('nota_id', $nota->id)->delete();

            $nota->update([
                'status' => 'ABERTA',
                'data_pagamento' => null,
                'observacao' => null,
                'valor_pago' => 0,
                'valor_pendente' => $nota->valor_total
            ]);

            DB::commit();

            return response()->json(['message' => 'Pagamento cancelado com sucesso']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erro ao cancelar pagamento'], 500);
        }
    }

    public function totais()
    {
        $totais = Nota::where('status', 'ABERTA')
            ->selectRaw('COUNT(*) as quantidade, SUM(valor_total) as total_valor')
            ->first();

        return response()->json([
            'quantidade' => $totais->quantidade,
            'total_valor' => $totais->total_valor ?? 0
        ]);
    }

    public function pagamentoLote(Request $request)
    {
        $validated = $request->validate([
            'notas_ids' => 'required|array',
            'notas_ids.*' => 'required|exists:notas,id',
            'data_pagamento' => 'required|date',
            'observacao' => 'nullable|string'
        ]);

        $sucessos = 0;
        $falhas = 0;
        $notasProcessadas = [];

        DB::beginTransaction();
        try {
            foreach ($validated['notas_ids'] as $notaId) {
                $nota = Nota::findOrFail($notaId);
                
                if ($nota->status !== 'ABERTA') {
                    $falhas++;
                    continue;
                }
    
                PagamentoNota::where('nota_id', $nota->id)->delete();
                
                PagamentoNota::create([
                    'nota_id' => $nota->id,
                    'valor' => $nota->valor_total,
                    'data_pagamento' => $validated['data_pagamento'],
                    'observacao' => $validated['observacao']
                ]);
                
                $nota->update([
                    'status' => 'PAGA',
                    'data_pagamento' => $validated['data_pagamento'],
                    'observacao_pagamento' => $validated['observacao'],
                    'valor_pago' => $nota->valor_total,
                    'valor_pendente' => 0
                ]);
                
                $sucessos++;
                $notasProcessadas[] = $nota->numero_nota;
            }
            
            DB::commit();
            
            return response()->json([
                'message' => "Pagamento em lote processado com sucesso",
                'sucessos' => $sucessos,
                'falhas' => $falhas,
                'notas_processadas' => $notasProcessadas
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao processar pagamento em lote: " . $e->getMessage());
            
            return response()->json([
                'message' => "Erro ao processar pagamento em lote: " . $e->getMessage()
            ], 500);
        }
    }
}
