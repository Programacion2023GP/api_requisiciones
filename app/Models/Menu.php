<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;
    protected $primaryKey = 'Id';
    protected $table = 'cat_menus';
    public $timestamps = false; // Desactiva el manejo automático de created_at y updated_at
}
