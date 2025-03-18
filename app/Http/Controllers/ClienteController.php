<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    public function index()
    {
        try {
            $clientes = Cliente::latest()->paginate(10);
            
            Log::info('Clientes encontrados:', ['clientes' => $clientes->toArray()]);
            
            return response()->json([
                'data' => $clientes->items(),
                'current_page' => $clientes->currentPage(),
                'last_page' => $clientes->lastPage(),
                'total' => $clientes->total(),
                'per_page' => $clientes->perPage()
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar clientes: ' . $e->getMessage());
            return response()->json(['message' => 'Erro ao carregar clientes', 'data' => []], 500);
        }
        
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'nome' => 'required|string|max:255',
                'email' => 'nullable|email|unique:clientes',
                'telefone' => 'nullable|string|max:20',
                'cpf_cnpj' => 'nullable|string|unique:clientes|max:20',
                'endereco' => 'nullable|string|max:255',
            ], [
                'nome.required' => 'O nome é obrigatório',
                'email.email' => 'Digite um email válido',
                'email.unique' => 'Este email já está cadastrado',
                'cpf_cnpj.unique' => 'Este CPF/CNPJ já está cadastrado',
            ]);
    
            $cliente = Cliente::create($validated);
    
            return response()->json([
                'message' => 'Cliente cadastrado com sucesso',
                'cliente' => $cliente
            ], 201);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erro de validação',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function show(Cliente $cliente)
    {
        return response()->json($cliente);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'email' => ['nullable', 'email', Rule::unique('clientes')->ignore($cliente->id)],
            'telefone' => 'nullable|string|max:20',
            'cpf_cnpj' => ['nullable', 'string', 'max:20', Rule::unique('clientes')->ignore($cliente->id)],
            'endereco' => 'nullable|string|max:255',
        ]);

        $cliente->update($validated);

        return response()->json([
            'message' => 'Cliente atualizado com sucesso',
            'cliente' => $cliente
        ]);
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return response()->json([
            'message' => 'Cliente excluído com sucesso'
        ]);
    }

    public function getClientesList()
    {
        try {
            $clientes = Cliente::select('id', 'nome')->orderBy('nome')->get();
            return response()->json($clientes);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar lista de clientes: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao carregar clientes'
            ], 500);
        }
    }

    public function getList()
    {
        try {
            $clientes = Cliente::select('id', 'nome')->orderBy('nome')->get();
            return response()->json($clientes);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar lista de clientes: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao carregar clientes'
            ], 500);
        }
    }

    public function total()
    {
        $total = Cliente::count();
        return response()->json(['total' => $total]);
    }

    public function clientesComNotasAltas()
    {
        try {
            $clientes = DB::table('clientes')
                ->select(
                    'clientes.*',
                    DB::raw('SUM(CASE 
                            WHEN notas.status = "ABERTA" THEN notas.valor_total 
                            WHEN notas.status = "PAGA PARCIALMENTE" THEN notas.valor_pendente
                            ELSE 0 
                            END) as total_aberto'),
                    DB::raw('COUNT(notas.id) as quantidade_notas')
                )
                ->join('notas', 'clientes.id', '=', 'notas.cliente_id')
                ->where(function($query) {
                    $query->where('notas.status', '=', 'ABERTA')
                        ->orWhere('notas.status', '=', 'PAGA PARCIALMENTE');
                })
                ->groupBy(
                    'clientes.id',
                    'clientes.nome',
                    'clientes.email',
                    'clientes.telefone',
                    'clientes.cpf_cnpj',
                    'clientes.endereco',
                    'clientes.created_at',
                    'clientes.updated_at'
                )
                ->havingRaw('SUM(CASE 
                            WHEN notas.status = "ABERTA" THEN notas.valor_total 
                            WHEN notas.status = "PAGA PARCIALMENTE" THEN notas.valor_pendente
                            ELSE 0 
                            END) > 800')
                ->orderByRaw('SUM(CASE 
                            WHEN notas.status = "ABERTA" THEN notas.valor_total 
                            WHEN notas.status = "PAGA PARCIALMENTE" THEN notas.valor_pendente
                            ELSE 0 
                            END) DESC')
                ->get();

            return response()->json($clientes);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar clientes com notas altas: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}