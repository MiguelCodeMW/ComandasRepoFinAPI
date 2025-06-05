<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    // Listar todas las categorías
    public function index()
    {
        $categorias = Categoria::all();
        return response()->json($categorias);
    }

    // Crear una nueva categoría
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $categoria = Categoria::create($validated);
        return response()->json(['message' => 'Categoría creada con éxito', 'categoria' => $categoria], 201);
    }

    // Mostrar una categoría específica
    public function show($id)
    {
        $categoria = Categoria::findOrFail($id);
        return response()->json($categoria);
    }

    // Actualizar una categoría
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $categoria = Categoria::findOrFail($id);
        $categoria->update($validated);
        return response()->json(['message' => 'Categoría actualizada con éxito', 'categoria' => $categoria]);
    }

    // Eliminar una categoría
    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->delete();
        return response()->json(['message' => 'Categoría eliminada con éxito']);
    }
}