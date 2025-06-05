<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /**
     * Utiliza el trait HasApiTokens para la gestión de tokens de API de Laravel Sanctum.
     * Utiliza el trait HasFactory para generar fábricas de modelos para pruebas y seeding.
     * Utiliza el trait Notifiable para enviar notificaciones al usuario.
     *
     * @use HasFactory<\Database\Factories\UserFactory>
     */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Los atributos que son asignables masivamente.
     * Esto significa que estos campos se pueden llenar utilizando la asignación masiva
     * (por ejemplo, `User::create($data)`).
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Se añade 'role' para permitir su asignación masiva
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     * Cuando este modelo se convierte a un array o JSON, estos atributos no se incluirán.
     * Esto es útil para la seguridad, por ejemplo, para no exponer la contraseña.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Obtiene los atributos que deben ser "casteados" a tipos de datos específicos.
     * Por ejemplo, `email_verified_at` se convertirá a un objeto `datetime` y `password` se hasheará automáticamente.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Laravel hasheará automáticamente la contraseña al guardarla.
        ];
    }

    /**
     * Define la relación uno a muchos con el modelo Comanda.
     * Un usuario puede tener muchas comandas.
     * La clave foránea en la tabla `comandas` es `id_usuario`.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comandas()
    {
        return $this->hasMany(Comanda::class, 'id_usuario');
    }
}