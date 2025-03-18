<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FornecedorController extends Controller
{
    public function index()
    {
        return Fornecedor::orderBy('nome')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255'
        ]);

        $fornecedor = Fornecedor::create($request->all());
        return response()->json($fornecedor, 201);
    }

    public function destroy($id)  // Mudamos para receber o ID diretamente
    {
        try {
            Log::info('Procurando fornecedor', ['id' => $id]);
            
            $fornecedor = Fornecedor::find($id);
            
            if (!$fornecedor) {
                return response()->json(['message' => 'Fornecedor não encontrado'], 404);
            }
            
            Log::info('Fornecedor encontrado', [
                'id' => $fornecedor->id,
                'nome' => $fornecedor->nome
            ]);

            $deleted = $fornecedor->delete();
            
            Log::info('Resultado da exclusão', ['deleted' => $deleted]);

            if ($deleted) {
                return response()->json(['message' => 'Fornecedor excluído com sucesso'], 200);
            }

            return response()->json(['message' => 'Não foi possível excluir o fornecedor'], 500);

        } catch (\Exception $e) {
            Log::error('Erro ao excluir fornecedor:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'message' => 'Erro ao excluir fornecedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
