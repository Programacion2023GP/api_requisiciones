<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatDetailsTipos extends Model
{
    use HasFactory;
    protected $primaryKey = 'IDDetalleTipo';
    protected $table = 'cat_detailstipos';
    public $timestamps = false;
    protected $fillable = [
        'IDTipo',
        'Nombre',
    ];
}
