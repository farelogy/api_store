<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\Cabang;
class StokController extends Controller
{
    public function data_stok_barang(){
        $get_cabang = Cabang::leftjoin('stok_barang','cabang.id','=','stok_barang.id_cabang')->select('cabang.id','cabang.nama_cabang',DB::raw('SUM(stok_barang.stok) as stok'))
                      ->groupby('cabang.id','cabang.nama_cabang')->get();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Cabang diterima',
            'data' => $get_cabang
        ],200);
    }
}
