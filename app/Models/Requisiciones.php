<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisiciones extends Model
{
    use HasFactory;
    protected $primaryKey = 'iD';
    protected $table = 'requisiciones';
    public $timestamps = false; }
