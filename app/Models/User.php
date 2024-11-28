<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $primaryKey = 'IDUsuario';
    protected $table = 'cat_usuarios';
    protected $fillable = [
        'Usuario',
        'Nombre',
        'Paterno',
        'Materno',
        'NombreCompleto',
        'IDDepartamento',
        'Rol',
        'Password',
        'Activo',
        
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'Password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    public $timestamps = false; // Desactiva el manejo automático de created_at y updated_at
    protected $casts = [
        'email_verified_at' => 'datetime',
        'Password' => 'hashed',
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // Si el campo 'Activo' no está establecido, se establece a 1 (activo) por defecto
            if (is_null($user->Activo)) {
                $user->Activo = 1;
            }
        });
    }
}
