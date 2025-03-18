<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DespesasController;
use App\Http\Controllers\FornecedorController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

    // Rotas protegidas
    Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

    // ROTAS PARA O HOME
    Route::get('notas/totais', [NotaController::class, 'totais']);
    Route::get('clientes/total', [ClienteController::class, 'total']);
    Route::get('despesas/totais', [DespesasController::class, 'totais']);
    Route::get('despesas/a-vencer', [DespesasController::class, 'aVencer']);
    
    // ROTAS PARA CLIENTES
    Route::get('/clientes/list', [ClienteController::class, 'getList']);
    Route::get('/clientes/notas-altas', [ClienteController::class, 'clientesComNotasAltas']);
    Route::resource('clientes', ClienteController::class);
    Route::resource('produtos', ProdutoController::class);
    
    // ROTAS PARA NOTAS
    Route::get('notas/cliente/{clienteId}', [NotaController::class, 'getNotasByCliente']);
    Route::put('notas/{nota}/pagar', [NotaController::class, 'pagarNota']);
    Route::put('notas/{nota}/cancelar-pagamento', [NotaController::class, 'cancelarPagamento']);
    Route::post('/notas/pagamento-lote', [NotaController::class, 'pagamentoLote']);
    Route::resource('notas', NotaController::class);
    Route::get('notas/{id}', 'NotaController@show');

    // ROTAS PARA DESPESAS
    Route::get('despesas/buscar', [DespesasController::class, 'buscarDespesas']);
    Route::get('despesas/fornecedor/{fornecedor_id}', [DespesasController::class, 'buscarPorFornecedor']);
    Route::put('despesas/{despesa}/pagar', [DespesasController::class, 'pagarDespesa']);
    Route::put('despesas/{despesa}/cancelar-pagamento', [DespesasController::class, 'cancelarPagamento']);
    Route::resource('despesas', DespesasController::class);
    
    // ROTAS PARA FORNECEDORES
    Route::get('/fornecedores/list', [FornecedorController::class, 'getList']);
    Route::resource('fornecedores', FornecedorController::class);
    Route::delete('fornecedores/{id}', [FornecedorController::class, 'destroy']);
});