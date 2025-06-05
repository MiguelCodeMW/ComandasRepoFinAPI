<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    // Listar todos los productos
    public function index()
    {
        $productos = Producto::with('categoria')->get(); // Incluye la categoría asociada
        return response()->json($productos);
    }

    // Crear un nuevo producto
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'categoria_id' => 'required|exists:categorias,id', // Verifica que la categoría exista
        ]);

        $producto = Producto::create($validated);
        return response()->json(['message' => 'Producto creado con éxito', 'producto' => $producto], 201);
    }

    // Mostrar un producto específico
    public function show($id)
    {
        $producto = Producto::with('categoria')->findOrFail($id); // Incluye la categoría asociada
        return response()->json($producto);
    }

    // Actualizar un producto
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'categoria_id' => 'required|exists:categorias,id', // Verifica que la categoría exista
        ]);

        $producto = Producto::findOrFail($id);
        $producto->update($validated);
        return response()->json(['message' => 'Producto actualizado con éxito', 'producto' => $producto]);
    }

    // Eliminar un producto
    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->delete();
        return response()->json(['message' => 'Producto eliminado con éxito']);
    }
}