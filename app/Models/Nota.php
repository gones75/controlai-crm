<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'numero_nota',
        'data',
        'valor_total',
        'status',
        'data_pagamento',
        'observacao_pagamento',
        'valor_pago',
        'valor_pendente',
    ];

    protected $dates = [
        'data',
        'data_pagamento'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function items()
    {
        return $this->hasMany(NotaItem::class);
    }

    public function pagamentos()
    {
        return $this->hasMany(PagamentoNota::class);
    }
}
