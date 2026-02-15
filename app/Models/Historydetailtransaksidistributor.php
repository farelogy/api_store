<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Historydetailtransaksidistributor extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'id_distributor',
        'id_barang',
        'nama_barang',
        'qty',
        'harga_satuan',
        'tanggal',
        'created_at',
        'updated_at',
    ];
}
