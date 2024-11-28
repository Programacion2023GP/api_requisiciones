<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Director extends Model
{
    use HasFactory;
    protected $primaryKey = 'IdDetDirectores';
    protected $table = 'det_directores';
    public $timestamps = false; // Desactiva el manejo automático de created_at y updated_at

}
