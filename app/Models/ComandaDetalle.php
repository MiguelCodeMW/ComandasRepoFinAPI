<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ComandaDetalle extends Model
{
    use HasFactory;
    

    protected $fillable = ['comanda_id', 'producto_id', 'cantidad', 'precio_unitario', 'total'];

    public function comanda()
    {
        return $this->belongsTo(Comanda::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
