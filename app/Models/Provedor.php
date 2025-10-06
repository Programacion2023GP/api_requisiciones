<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provedor extends Model
{
    use HasFactory;
    protected $primaryKey = 'IDProveedor';
    protected $table = 'cat_proveedores';
    public $timestamps = false;
    protected $fillable = [
        'Nombre_RazonSocial',
        'ApPaterno',
        'ApMaterno',
        'RFC',
        'Telefono1',
        'Telefono2',
        'EMail',
        'FechaAlta',
        'Usuario',
        'FUM',
        'UsuarioFUM',
        'DeRelleno',
        'Activo'
    ];
}