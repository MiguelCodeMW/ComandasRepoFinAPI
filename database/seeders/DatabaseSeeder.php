<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Comanda;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\ComandaDetalle;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuarios
    User::factory()->count(5)->create();

    // Crear categorías con productos
    Categoria::factory()->count(5)->create()->each(function ($categoria) {
        Producto::factory()->count(5)->create(['categoria_id' => $categoria->id]);
    });

    // Crear comandas y detalles
    Comanda::factory()->count(10)->create()->each(function ($comanda) {
        $productos = Producto::inRandomOrder()->take(3)->get();

        foreach ($productos as $producto) {
            $cantidad = rand(1, 3);
            ComandaDetalle::create([
                'comanda_id' => $comanda->id,
                'producto_id' => $producto->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $producto->precio,
                'total' => $producto->precio * $cantidad,
            ]);
        }
    });
    }
}
