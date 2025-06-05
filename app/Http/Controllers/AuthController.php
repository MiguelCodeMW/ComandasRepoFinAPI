<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Registra un nuevo usuario en el sistema.
     *
     * @param CreateUserRequest $request La solicitud que contiene los datos para crear el usuario.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateUserRequest $request)
    {
        try {
            // Comprueba si ya existe algún usuario con el rol 'admin'.
            // Si no existe ninguno, el nuevo usuario se registrará como 'admin'.
            $isFirstAdmin = !User::where('role', 'admin')->exists();

            // Crea un nuevo usuario en la base de datos.
            // La contraseña se hashea antes de guardarse para mayor seguridad.
            // El rol se asigna como 'admin' si es el primer administrador, de lo contrario, 'camarero'.
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $isFirstAdmin ? 'admin' : 'camarero',
            ]);

            // Devuelve una respuesta JSON exitosa con el estado, un mensaje, el rol del usuario
            // y un token de API para futuras autenticaciones.
            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
                'role' => $user->role,
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 201);

        } catch (\Exception $e) {
            // Captura cualquier excepción que ocurra durante el proceso de registro
            // y devuelve una respuesta JSON con un mensaje de error y los detalles de la excepción.
            return response()->json([
                'status' => false,
                'message' => 'User registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Autentica a un usuario en el sistema.
     *
     * @param LoginRequest $request La solicitud que contiene las credenciales de inicio de sesión.
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginUser(LoginRequest $request)
    {
        try {
            // Obtiene las credenciales de email y contraseña validadas de la solicitud.
            $credentials = $request->validated();

            // Intenta autenticar al usuario utilizando las credenciales proporcionadas.
            // Si la autenticación falla, devuelve una respuesta de error.
            if (!Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & password do not match our records'
                ], 401);
            }

            // Si la autenticación es exitosa, busca al usuario por su email.
            $user = User::where('email', $credentials['email'])->firstOrFail();

            // Devuelve una respuesta JSON exitosa con el estado, un mensaje, el rol del usuario
            // y un token de API para futuras autenticaciones.
            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully',
                'role' => $user->role,
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}