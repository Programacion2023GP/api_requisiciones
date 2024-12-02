<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuUser extends Model
{
    use HasFactory;
    protected $primaryKey = 'iD';
    protected $table = 'relmenuusuario';
    public $timestamps = false; 
}
