<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Despesas extends Model
{
    protected $fillable = [
        'data',
        'produto',
        'quantidade',
        'fornecedor_id',
        'valor',
        'data_vencimento',
        'status',
        'data_pagamento',
        'observacao'
    ];

    protected $casts = [
        'data' => 'date',
        'data_vencimento' => 'date',
        'quantidade' => 'decimal:2',
        'valor' => 'decimal:2'
    ];

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }
}