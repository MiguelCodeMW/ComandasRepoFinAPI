<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Comanda;
use App\Models\Producto;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ComandaDetalle>
 */
class ComandaDetalleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 5);
    $precio = $this->faker->randomFloat(2, 1, 20);

    return [
        'comanda_id' => Comanda::factory(),
        'producto_id' => Producto::factory(),
        'cantidad' => $cantidad,
        'precio_unitario' => $precio,
        'total' => $cantidad * $precio,
    ];
    }
}
