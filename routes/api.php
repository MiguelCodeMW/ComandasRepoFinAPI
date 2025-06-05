<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComandaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\MesaController;

// --- Autenticación ---

// Ruta para crear un nuevo usuario.
Route::post('/create', [AuthController::class, 'store'])->name('create');
// Ruta para iniciar sesión y obtener un token de API.
Route::post('/login', [AuthController::class, 'loginUser'])->name('login');

// --- Rutas protegidas por autenticación (Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {
    // Ruta para obtener los datos del usuario autenticado.
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // --- Rutas de Categorías (solo lectura para todos los autenticados) ---
    // Obtener todas las categorías.
    Route::get('categorias', [CategoriaController::class, 'index']);
    // Obtener una categoría específica por ID.
    Route::get('categorias/{categoria}', [CategoriaController::class, 'show']);

    // --- Rutas de Productos (solo lectura para todos los autenticados) ---
    // Obtener todos los productos.
    Route::get('productos', [ProductoController::class, 'index']);
    // Obtener un producto específico por ID.
    Route::get('productos/{producto}', [ProductoController::class, 'show']);

    // --- Rutas de Mesas (API Resource para operaciones CRUD, accesible a todos los autenticados) ---
    // Define rutas RESTful para el recurso 'mesas'.
    Route::apiResource('mesas', MesaController::class);

    // --- Rutas de Configuración (solo lectura para todos los autenticados) ---
    // Obtener el valor del IVA.
    Route::get('/configuracion/iva', [ConfiguracionController::class, 'getIva'])->name('config.getIva');
    // Obtener el símbolo de la moneda.
    Route::get('/configuracion/moneda', [ConfiguracionController::class, 'getMoneda'])->name('config.getMoneda');

    // --- Rutas exclusivas para administradores ---
    Route::middleware([CheckRole::class . ':admin'])->group(function () {
        // --- CRUD de Categorías (solo para administradores) ---
        // Crear una nueva categoría.
        Route::post('categorias', [CategoriaController::class, 'store']);
        // Actualizar una categoría existente.
        Route::put('categorias/{categoria}', [CategoriaController::class, 'update']);
        // Eliminar una categoría.
        Route::delete('categorias/{categoria}', [CategoriaController::class, 'destroy']);

        // --- CRUD de Productos (solo para administradores) ---
        // Crear un nuevo producto.
        Route::post('productos', [ProductoController::class, 'store']);
        // Actualizar un producto existente.
        Route::put('productos/{producto}', [ProductoController::class, 'update']);
        // Eliminar un producto.
        Route::delete('productos/{producto}', [ProductoController::class, 'destroy']);

        // --- Eliminación de Comandas (solo para administradores) ---
        // Eliminar una comanda.
        Route::delete('comandas/{comanda}', [ComandaController::class, 'destroy']);

        // --- Configuración (solo para administradores) ---
        // Establecer el valor del IVA.
        Route::post('/configuracion/iva', [ConfiguracionController::class, 'setIva'])->name('config.setIva');
        // Establecer el símbolo de la moneda.
        Route::post('/configuracion/moneda', [ConfiguracionController::class, 'setMoneda'])->name('config.setMoneda');

        // Establecer el número total de mesas.
        Route::post('/configuracion/total-mesas', [MesaController::class, 'setTotalMesas']);
    });

    // --- Rutas de Comandas (accesibles a todos los autenticados) ---
    // Obtener todas las comandas (probablemente para el dashboard).
    Route::get('/dashboard', [ComandaController::class, 'index']);
    // Rutas RESTful para comandas (excepto eliminación).
    Route::apiResource('comandas', ComandaController::class)->except(['destroy']);
    // Marcar una comanda como pagada.
    Route::put('/comandas/{id}/pagar', [ComandaController::class, 'pagar']);
});