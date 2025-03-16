<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\Keranjang;
use Illuminate\Support\Facades\Validator;
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

    public function tambah_keranjang(Request $request){
        $validated = Validator::make($request->all(), [
            'id_barang' => 'required',
            'id_cabang' => 'required',
            'jumlah' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }
        //cek barang di keranjang
        $cek_keranjang_barang = Keranjang::where('id_cabang',$request->id_cabang)->where('id_barang',$request->id_barang)->count();
        if($cek_keranjang_barang != 0)
        {
            $barang_sudah_di_keranjang = Keranjang::where('id_cabang',$request->id_cabang)->where('id_barang',$request->id_barang)->first();
            $keranjang = Keranjang::find($barang_sudah_di_keranjang->id);
            $keranjang->jumlah = $barang_sudah_di_keranjang->jumlah + $request->jumlah;
            $keranjang->save();
        }
        else {
            $keranjang = new Keranjang();
            $keranjang->id_cabang = $request->id_cabang;
            $keranjang->id_barang = $request->id_barang;
            $keranjang->jumlah = $request->jumlah;
            $keranjang->save();
        }
        
        return response()->json([
            'status' => 'Success',
            'message' => 'Tambah Barang Berhasil',
        ],200);


    }
}
