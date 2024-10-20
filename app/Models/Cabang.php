<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    use HasFactory;
    protected $table = 'cabang';

    protected $fillable = [
        'nama_cabang',
        'saldo',
        // Add more columns here
    ];
}
