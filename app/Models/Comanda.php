<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comanda extends Model
{
    use HasFactory;

    // Campos que se pueden asignar masivamente
    protected $fillable = ['user_id', 'fecha', 'estado', 'mesa_id', 'iva','total_con_iva'];

    // Relación con el modelo User
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id'); // user_id es la clave foránea
    }

    // Relación con el modelo ComandaDetalle
    public function detalles()
    {
        return $this->hasMany(ComandaDetalle::class, 'comanda_id'); // comanda_id es la clave foránea en ComandaDetalle
    }
     public function mesa()
    {
        return $this->belongsTo(Mesa::class);
    }
}