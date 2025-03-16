<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
class TransaksiController extends Controller
{
    public function cek_keranjang(Request $request){
        $get_keranjang = DB::table('keranjangs')->where('id_cabang',$request->id_cabang)->count();
        return response()->json([
        'status' => 'Success',
        'message' => 'Data Keranjang diterima',
        'data' => $get_keranjang
        ],200);
    }
}
