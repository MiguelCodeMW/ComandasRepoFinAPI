<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta solicitud.
     * En este caso, siempre retorna true, lo que significa que cualquier usuario
     * puede intentar iniciar sesión a través de esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     * Estas reglas definen qué datos son obligatorios y qué formato deben tener.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // El campo 'email' es obligatorio y debe tener un formato de email válido.
            'email' => 'required|email',
            // El campo 'password' es obligatorio.
            'password' => 'required',  
        ];
    }
}