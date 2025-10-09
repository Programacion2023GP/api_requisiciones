<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelUsuarioDepartamento extends Model
{
    use HasFactory;
    protected $primaryKey = 'IdRelUsuarioDepartamento';
    protected $table = 'relusuariodepartamento';
    protected $fillable = [
        'IDUsuario',
        'IDDepartamento'

    ];
    public $timestamps = false;

    // esta tabla es una tabla relacion muchos a muchos entre la tabla cat_usuarios y cat_departametnos

    public function usuario()
    {
        return $this->belongsTo(User::class, 'IDUsuario', 'id');
        // 'IDUsuario' es la clave foránea en esta tabla,
        // 'id' (o como sea la PK de usuarios) es la clave local del modelo User
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'IDDepartamento', 'id');
        // 'IDDepartamento' es la clave en esta tabla,
        // y el tercer parámetro es la PK del modelo Departamento
    }
}