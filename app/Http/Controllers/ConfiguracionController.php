<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfiguracionController extends Controller
{
    /**
     * Obtener la configuración actual del IVA.
     */
   public function getIva()
{
    $setting = Setting::where('key', 'global_iva')->first();

    if (!$setting) {
        // Si no existe la configuración, devolvemos 0.21 como valor por defecto.
        return response()->json(['iva' => 0.21]);
    }

    return response()->json(['iva' => (float) $setting->value]);
}

    /**
     * Establecer o actualizar la configuración del IVA.
     */
    public function setIva(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'iva' => 'required|numeric|min:0|max:1', // ej: 0.21 para 21%
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ivaValue = $request->input('iva');

        // Usamos updateOrCreate para crear la configuración si no existe, o actualizarla si ya existe.
        $setting = Setting::updateOrCreate(
            ['key' => 'global_iva'], // Condiciones para buscar
            ['value' => (string) $ivaValue] // Valores para actualizar o crear
        );

        return response()->json([
            'message' => 'IVA configurado con éxito.',
            'iva' => (float) $setting->value // Devolver el valor guardado
        ], 200);
    }

    /**
     * Obtener la configuración actual de la Moneda.
     */
    public function getMoneda()
    {
        $setting = Setting::where('key', 'global_currency')->first();

        if (!$setting) {
            // Si no existe la configuración, devolvemos null y un mensaje.
            return response()->json(['currency' => null, 'message' => 'Moneda no configurada.']);
        }

        return response()->json(['currency' => $setting->value]);
    }

    /**
     * Establecer o actualizar la configuración de la Moneda.
     */
    public function setMoneda(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string|size:3', // ej: EUR, USD, MXN
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $currencyValue = strtoupper($request->input('currency')); // Guardar en mayúsculas por consistencia

        $setting = Setting::updateOrCreate(
            ['key' => 'global_currency'], // Condiciones para buscar
            ['value' => $currencyValue]    // Valores para actualizar o crear
        );

        return response()->json([
            'message' => 'Moneda configurada con éxito.',
            'currency' => $setting->value
        ], 200);
    }
}