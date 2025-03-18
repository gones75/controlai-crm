<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotaItem extends Model
{
    protected $fillable = [
        'nota_id',
        'kg',
        'valor_kg',
        'valor_total'
    ];

    public function nota()
    {
        return $this->belongsTo(Nota::class);
    }
}