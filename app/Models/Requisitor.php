<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisitor extends Model
{
    use HasFactory;
    protected $primaryKey = 'Usuario';
    protected $table = 'cat_requisitores';
    public $timestamps = false; // Desactiva el manejo automático de created_at y updated_at

}
