<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class ProdutoController extends Controller
{
    public function index()
    {
        try {
            $produtos = Produto::latest()->paginate(10);
            
            return response()->json([
                'data' => $produtos->items(),
                'current_page' => $produtos->currentPage(),
                'last_page' => $produtos->lastPage(),
                'total' => $produtos->total(),
                'per_page' => $produtos->perPage()
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar produtos: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao carregar produtos',
                'data' => []
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Log dos dados recebidos
            Log::info('Dados recebidos:', $request->all());

            // Validação
            $validated = $request->validate([
                'nome' => 'required|string|max:255',
                'valor' => 'required|numeric|min:0'
            ]);

            // Log dos dados validados
            Log::info('Dados validados:', $validated);

            // Criação do produto
            $produto = Produto::create([
                'nome' => $validated['nome'],
                'valor' => $validated['valor']
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Produto cadastrado com sucesso',
                'produto' => $produto
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
            Log::error('Erro ao criar produto:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao cadastrar produto',
                'debug_message' => $e->getMessage() // apenas em ambiente de desenvolvimento
            ], 500);
        }
    
    }

    public function update(Request $request, Produto $produto)
    {
        try {
            $validated = $request->validate([
                'nome' => 'required|string|max:255',
                'valor' => 'required|numeric|min:0'
            ]);

            $produto->update($validated);

            return response()->json([
                'message' => 'Produto atualizado com sucesso',
                'produto' => $produto
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar produto: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao atualizar produto'
            ], 500);
        }
    }

    public function destroy(Produto $produto)
    {
        try {
            $produto->delete();
            return response()->json([
                'message' => 'Produto excluído com sucesso'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao excluir produto: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao excluir produto'
            ], 500);
        }
    }
}