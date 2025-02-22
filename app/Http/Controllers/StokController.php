<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\Cabang;
use App\Models\Barang;

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

    public function data_stok_barang_detail(Request $request){
        $get_stok = DB::table('stok_barang')->where('id_cabang',$request->id_cabang)->get();
        $get_cabang_barang = Barang::leftJoinSub($get_stok, 'filtered_stok', function ($join){
            $join->on('barangs.id','=','filtered_stok.id_barang');
        })->select('barangs.id','barangs.nama_barang','barangs.satuan','filtered_stok.stok')->get();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Barang Cabang diterima',
            'data' => $get_cabang_barang
        ],200);
    }
}
