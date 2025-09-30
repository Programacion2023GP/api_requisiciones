<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailRequisition extends Model
{
    use HasFactory;
    protected $primaryKey = 'IDDetalle';
    protected $table = 'det_requisicion';
    public $timestamps = false; //
}
