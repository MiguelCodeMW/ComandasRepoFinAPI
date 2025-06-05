<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Producto extends Model
{
    use HasFactory;
    
    protected $fillable = ['nombre', 'precio', 'categoria_id'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function detalles()
    {
        return $this->hasMany(ComandaDetalle::class);
    }
}
