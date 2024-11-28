<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Autorizadores extends Model
{
    use HasFactory;
    protected $primaryKey = 'Autorizador';
    protected $table = 'autorizadores';
    protected $fillable = [
        'Autorizador',
        'Permiso_Autorizar',
        'Permiso_Asignar',
        'Permiso_Cotizar',
        'Permiso_Orden_Compra',
     
        
    ];
    public $timestamps = false; // Desactiva el manejo automático de created_at y updated_at

    protected $hidden = [
        'Usuario',
        // 'remember_token',
    ];
}
