<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'valor'];

    //  Mutator para garantir que o valor seja tratado corretamente
    protected function setValorAttribute($value)
    {
        $this->attributes['valor'] = str_replace(',', '.', $value);
    }
}