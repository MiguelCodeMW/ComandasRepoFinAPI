<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class MesaController extends Controller
{
    // Listar todas las mesas
    public function index()
    {
        $mesas = Mesa::all();
        return response()->json($mesas);
    }

    // Crear una nueva mesa
    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero' => 'required|integer|min:1|unique:mesas,numero',
            'estado' => 'in:libre,ocupada', 
        ]);

        $mesa = Mesa::create([
            'numero' => $validated['numero'],
            'estado' => $validated['estado'] ?? 'libre',
        ]);

        return response()->json(['message' => 'Mesa creada con éxito', 'mesa' => $mesa], 201);
    }

    // Mostrar una mesa específica
    public function show($id)
    {
        $mesa = Mesa::findOrFail($id);
        return response()->json($mesa);
    }

    // Actualizar una mesa
    public function update(Request $request, $id)
    {
        $mesa = Mesa::findOrFail($id);

        $validated = $request->validate([
            'numero' => 'sometimes|required|integer|min:1|unique:mesas,numero,' . $mesa->id,
            'estado' => 'sometimes|required|in:libre,ocupada',
        ]);

        $mesa->update($validated);

        return response()->json(['message' => 'Mesa actualizada con éxito', 'mesa' => $mesa]);
    }

    // Eliminar una mesa
    public function destroy($id)
    {
        $mesa = Mesa::findOrFail($id);

        if ($mesa->estado === 'ocupada') {
            return response()->json(['message' => 'No se puede eliminar una mesa que está ocupada.'], 409);
        }

        $mesa->delete();
        return response()->json(['message' => 'Mesa eliminada correctamente.']);
    }

    // Configurar el número total de mesas (opcional, para gestión masiva)
    public function setTotalMesas(Request $request)
    {
        $request->validate([
            'total_mesas' => 'required|integer|min:0',
        ]);

        $newTotal = $request->input('total_mesas');
        $currentMesas = Mesa::orderBy('numero')->get();
        $currentCount = $currentMesas->count();

        DB::beginTransaction();

        try {
            if ($newTotal > $currentCount) {
                // Crear nuevas mesas
                for ($i = $currentCount + 1; $i <= $newTotal; $i++) {
                    Mesa::create([
                        'numero' => $i,
                        'estado' => 'libre',
                    ]);
                }
                DB::commit();
                return response()->json(['message' => 'Número de mesas actualizado. Nuevas mesas añadidas.']);
            } elseif ($newTotal < $currentCount) {
                // Eliminar mesas si es necesario
                $mesasToDelete = Mesa::orderByDesc('numero')
                    ->take($currentCount - $newTotal)
                    ->get();

                $occupiedMesas = $mesasToDelete->where('estado', 'ocupada');
                if ($occupiedMesas->isNotEmpty()) {
                    $occupiedNumbers = $occupiedMesas->pluck('numero')->implode(', ');
                    DB::rollBack();
                    return response()->json([
                        'message' => "No se pueden eliminar las mesas {$occupiedNumbers} porque están ocupadas. Libéralas primero."
                    ], 422);
                }

                Mesa::whereIn('id', $mesasToDelete->pluck('id'))->delete();
                DB::commit();
                return response()->json(['message' => 'Número de mesas actualizado. Mesas eliminadas.']);
            } else {
                DB::commit();
                return response()->json(['message' => 'El número total de mesas ya es el deseado. No se realizaron cambios.']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error al configurar el número de mesas: ' . $e->getMessage()], 500);
        }
    }
}
