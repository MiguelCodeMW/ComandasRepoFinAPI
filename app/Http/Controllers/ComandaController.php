<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comanda;
use App\Models\Producto;
use App\Models\Setting;
use App\Models\Mesa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // Importar la fachada DB para transacciones
use Illuminate\Validation\ValidationException; // Importar para manejar errores de validación

class ComandaController extends Controller
{
    /**
     * Muestra una lista de todas las comandas.
     * Incluye la relación con usuario y mesa para una vista completa.
     */
    public function index()
    {
        // Carga todas las comandas, incluyendo la relación con usuario y mesa para una vista completa
        $comandas = Comanda::with('usuario', 'mesa')->get();
        return response()->json([
            'message'  => 'Bienvenido al Dashboard de Comandas',
            'comandas' => $comandas,
        ]);
    }

    /**
     * Muestra la comanda especificada.
     */
    public function show($id)
    {
        // Carga la comanda con sus detalles, productos asociados y la mesa
        $comanda = Comanda::with('detalles.producto', 'mesa')->findOrFail($id);

        $subtotal = $comanda->detalles->sum('total');
        $ivaAplicado = null;

        // Lógica para determinar el IVA a aplicar/mostrar:
        // Prioridad: IVA fijado en comanda (si está cerrada) -> IVA ya guardado en comanda (si está abierta) -> IVA global -> 0.21 por defecto
        if ($comanda->estado === 'cerrada' && $comanda->iva !== null) {
            $ivaAplicado = $comanda->iva;
        } else {
            if ($comanda->iva !== null) {
                $ivaAplicado = $comanda->iva;
            } else {
                $globalIvaSetting = Setting::where('key', 'global_iva')->first();
                if ($globalIvaSetting) {
                    $ivaAplicado = (float) $globalIvaSetting->value;
                } else {
                    $ivaAplicado = 0.21; // Valor por defecto si no hay IVA global
                }
            }
        }

        $totalConIva = $subtotal + ($subtotal * $ivaAplicado);

        return response()->json([
            'comanda' => $comanda,
            'subtotal' => $subtotal,
            'iva' => $ivaAplicado,
            'total_con_iva' => $totalConIva,
        ]);
    }

    /**
     * Guarda una nueva comanda en el almacenamiento.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction(); // Iniciar transacción

            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'estado' => 'required|string|in:abierta,cerrada',
                'fecha' => 'required|date',
                'productos' => 'required|array',
                'productos.*.producto_id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'iva' => 'nullable|numeric|min:0|max:1',
                'mesa_id' => 'nullable|exists:mesas,id',
            ]);

            $subtotalCalculado = 0;
            // Primero, calculamos el subtotal para poder usarlo en la comanda
            foreach ($validated['productos'] as $productoData) {
                $productoModel = Producto::find($productoData['producto_id']);
                $precioUnitario = $productoModel->precio;
                $cantidad = $productoData['cantidad'];
                $totalDetalle = $precioUnitario * $cantidad;
                $subtotalCalculado += $totalDetalle;
            }

            // Calcular el IVA a aplicar
            $ivaParaGuardar = $validated['iva'] ?? null;
            if ($ivaParaGuardar === null) {
                $globalIvaSetting = Setting::where('key', 'global_iva')->first();
                $ivaParaGuardar = $globalIvaSetting ? (float) $globalIvaSetting->value : 0.21;
            }

            // Calcular el total con IVA
            $totalConIvaCalculado = $subtotalCalculado + ($subtotalCalculado * $ivaParaGuardar);

            // Crear la comanda con todos los campos validados y calculados
            $comanda = Comanda::create([
                'user_id' => $validated['user_id'],
                'estado' => $validated['estado'],
                'fecha' => $validated['fecha'],
                'iva' => $ivaParaGuardar, // Ahora sí se guardará gracias a $fillable
                'total_con_iva' => $totalConIvaCalculado, // Ahora sí se guardará gracias a $fillable
                'mesa_id' => $validated['mesa_id'] ?? null,
            ]);

            // Si se asignó una mesa, se actualiza su estado a 'ocupada'
            if ($comanda->mesa_id !== null) {
                $mesa = Mesa::find($comanda->mesa_id);
                if ($mesa && $mesa->estado === 'libre') {
                    $mesa->estado = 'ocupada';
                    $mesa->save();
                }
            }

            // Asociar los productos a la comanda (detalles)
            foreach ($validated['productos'] as $productoData) {
                $productoModel = Producto::find($productoData['producto_id']);
                $precioUnitario = $productoModel->precio; // Re-obtener para asegurar consistencia
                $cantidad = $productoData['cantidad'];
                $totalDetalle = $precioUnitario * $cantidad;

                $comanda->detalles()->create([
                    'producto_id' => $productoData['producto_id'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'total' => $totalDetalle,
                ]);
            }

            DB::commit(); // Confirmar la transacción

            // Cargar las relaciones para la respuesta
            return response()->json([
                'message' => 'Comanda creada con éxito',
                'comanda' => $comanda->load('detalles.producto', 'mesa', 'usuario'),
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack(); // Revertir si hay error de validación
            return response()->json(['message' => 'Error de validación', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir si hay cualquier otro error
            Log::error('Error al crear la comanda: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error interno del servidor al crear la comanda.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualiza la comanda especificada en el almacenamiento.
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction(); // Iniciar transacción

            $comanda = Comanda::findOrFail($id);
            $oldMesaId = $comanda->mesa_id; // Guarda el ID de la mesa actual para compararla después

            $validatedData = $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'estado'  => 'sometimes|required|in:abierta,cerrada',
                'productos' => 'sometimes|required|array',
                'productos.*.producto_id' => 'required|exists:productos,id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'iva' => 'nullable|numeric|min:0|max:1',
                'mesa_id' => 'nullable|exists:mesas,id',
            ]);

            // Procesa y actualiza los detalles de la comanda si se proporcionaron productos
            $subtotalActual = 0;
            if (isset($validatedData['productos'])) {
                $comanda->detalles()->delete(); // Elimina los detalles existentes
                foreach ($validatedData['productos'] as $productoData) {
                    $productoModel = Producto::find($productoData['producto_id']);
                    $totalDetalle = $productoModel->precio * $productoData['cantidad'];
                    $subtotalActual += $totalDetalle;

                    $comanda->detalles()->create([
                        'producto_id' => $productoData['producto_id'],
                        'cantidad' => $productoData['cantidad'],
                        'precio_unitario' => $productoModel->precio,
                        'total' => $totalDetalle,
                    ]);
                }
            } else {
                // Si no se actualizaron productos, usar el subtotal existente para el cálculo
                $subtotalActual = $comanda->detalles->sum('total');
            }

            // Calcular y asignar IVA y Total con IVA
            $ivaParaGuardar = $comanda->iva; // Usar el IVA actual de la comanda por defecto
            if (isset($validatedData['iva']) && $validatedData['iva'] !== $comanda->iva) {
                $ivaParaGuardar = $validatedData['iva']; // Si se envió un nuevo IVA
            } elseif ($ivaParaGuardar === null) {
                // Si la comanda no tenía IVA y no se envió uno nuevo, usar el global
                $globalIvaSetting = Setting::where('key', 'global_iva')->first();
                $ivaParaGuardar = $globalIvaSetting ? (float) $globalIvaSetting->value : 0.21;
            }

            $totalConIvaCalculado = $subtotalActual + ($subtotalActual * $ivaParaGuardar);

            // Actualizar campos de la comanda. 'fill' se encargará de los que están en $fillable
            $comanda->fill([
                'user_id'       => $validatedData['user_id'] ?? $comanda->user_id,
                'estado'        => $validatedData['estado'] ?? $comanda->estado,
                'mesa_id'       => $validatedData['mesa_id'] ?? null,
                'iva'           => $ivaParaGuardar, // Se asigna el IVA final
                'total_con_iva' => $totalConIvaCalculado, // Se asigna el total con IVA final
            ]);

            // Lógica para gestionar el estado de las mesas si la mesa asociada a la comanda cambia
            if ($oldMesaId !== ($validatedData['mesa_id'] ?? null)) {
                // Si la comanda tenía una mesa asignada previamente, y ya no es la misma, la libera
                if ($oldMesaId !== null) {
                    $oldMesa = Mesa::find($oldMesaId);
                    if ($oldMesa) {
                        // Solo libera la mesa antigua si ninguna otra comanda abierta la está usando
                        if (!$oldMesa->comandas()->where('estado', 'abierta')->where('id', '!=', $comanda->id)->exists()) {
                            $oldMesa->estado = 'libre';
                            $oldMesa->save();
                        }
                    }
                }
                // Si la comanda ahora tiene una nueva mesa asignada, la ocupa
                if (($validatedData['mesa_id'] ?? null) !== null) {
                    $newMesa = Mesa::find($validatedData['mesa_id']);
                    if ($newMesa && $newMesa->estado === 'libre') {
                        $newMesa->estado = 'ocupada';
                        $newMesa->save();
                    }
                }
            }

            // Si el estado cambia a 'cerrada' y tenía una mesa, liberar la mesa
            if (($validatedData['estado'] ?? $comanda->estado) === 'cerrada' && $comanda->mesa_id) {
                $mesa = Mesa::find($comanda->mesa_id);
                if ($mesa) {
                    if (!$mesa->comandas()->where('estado', 'abierta')->where('id', '!=', $comanda->id)->exists()) {
                         $mesa->estado = 'libre';
                         $mesa->save();
                    }
                }
            }

            $comanda->save(); // Guarda los cambios finales en la comanda

            DB::commit(); // Confirmar la transacción

            return response()->json([
                'message' => 'Comanda actualizada con éxito.',
                'comanda' => $comanda->load('detalles.producto', 'mesa', 'usuario'),
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack(); // Revertir si hay error de validación
            return response()->json(['message' => 'Error de validación', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir si hay cualquier otro error
            Log::error('Error al actualizar la comanda: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error interno del servidor al actualizar la comanda.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marca la comanda especificada como pagada y actualiza su estado.
     */
    public function pagar(Request $request, $id)
    {
        try {
            DB::beginTransaction(); // Iniciar transacción

            $request->validate([
                'iva' => 'required|numeric|min:0|max:1', // El IVA es requerido al momento de pagar
            ]);

            $comanda = Comanda::with('detalles')->findOrFail($id);

            // Si la comanda ya está cerrada, no permitir volver a pagarla
            if ($comanda->estado === 'cerrada') {
                DB::rollBack();
                return response()->json(['message' => 'La comanda ya ha sido cerrada.'], 400);
            }

            $subtotal = $comanda->detalles->sum('total');
            $iva = $request->iva;
            $ivaAmount = $subtotal * $iva;
            $totalConIva = $subtotal + $ivaAmount;

            $comanda->estado = 'cerrada';
            $comanda->iva = $iva;
            $comanda->total_con_iva = $totalConIva;
            $comanda->save();

            // Si la comanda tenía una mesa asignada, la libera
            if ($comanda->mesa_id !== null) {
                $mesa = Mesa::find($comanda->mesa_id);
                if ($mesa) {
                    // Solo libera la mesa si ya no tiene más comandas "abiertas" asociadas
                    if (!$mesa->comandas()->where('estado', 'abierta')->exists()) {
                        $mesa->estado = 'libre';
                        $mesa->save();
                    }
                }
            }

            DB::commit(); // Confirmar la transacción

            return response()->json([
                'message' => 'Comanda pagada con éxito',
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total_con_iva' => $totalConIva,
                'comanda' => $comanda->load('detalles.producto', 'mesa', 'usuario'),
            ]);

        } catch (ValidationException $e) {
            DB::rollBack(); // Revertir si hay error de validación
            return response()->json(['message' => 'Error de validación', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir si hay cualquier otro error
            Log::error('Error al pagar la comanda: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error interno del servidor al pagar la comanda.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Elimina la comanda especificada del almacenamiento.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction(); // Iniciar transacción

            $comanda = Comanda::findOrFail($id);
            $mesaId = $comanda->mesa_id; // Guarda el ID de la mesa antes de eliminar la comanda

            $comanda->delete();

            // Si la comanda eliminada tenía una mesa asignada, intenta liberar la mesa
            if ($mesaId !== null) {
                $mesa = Mesa::find($mesaId);
                if ($mesa) {
                    // Solo libera la mesa si ya no tiene más comandas "abiertas" asociadas
                    if (!$mesa->comandas()->where('estado', 'abierta')->exists()) {
                        $mesa->estado = 'libre';
                        $mesa->save();
                    }
                }
            }

            DB::commit(); // Confirmar la transacción

            return response()->json(['message' => 'Comanda eliminada con éxito.']);

        } catch (\Exception | ValidationException $e) { // Captura ambos tipos de excepción para un manejo más general
            DB::rollBack(); // Revertir si hay error
            Log::error('Error al eliminar la comanda: ' . $e->getMessage());
            $statusCode = ($e instanceof ValidationException) ? 422 : 500;
            return response()->json([
                'message' => 'Error al eliminar la comanda.',
                'error'   => $e->getMessage(),
            ], $statusCode);
        }
    }
}