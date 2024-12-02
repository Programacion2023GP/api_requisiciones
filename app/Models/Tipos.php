<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tipos extends Model
{
    use HasFactory;
    protected $primaryKey = 'IDTipo';
    protected $table = 'cat_tipos';
    public $timestamps = false; // Desactiva el manejo automático de created_at y updated_at
}
