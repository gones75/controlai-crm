<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagamentoNota extends Model
{
    use HasFactory;

    protected $table = 'pagamentos_notas';

    protected $fillable = [
        'nota_id',
        'valor',
        'data_pagamento',
        'observacao'
    ];

    protected $casts = [
        'data_pagamento' => 'date',
        'valor' => 'decimal:2'
    ];

    public function nota()
    {
        return $this->belongsTo(Nota::class);
    }
}
