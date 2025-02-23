<?php

namespace App\Http\Controllers;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangController extends Controller
{
    public function data_barang(){
        $get_barang = Barang::all();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Barang diterima',
            'data' => $get_barang
        ],200);
    }

    public function add_barang(Request $request){
        $validated = Validator::make($request->all(), [
            'nama_barang' => 'required',
            'harga' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi'
            ], 200);
        }

        $barang = new Barang();
        $barang->nama_barang = $request->nama_barang;
        $barang->harga = $request->harga;
        $barang->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Tambah Barang Berhasil',
        ],200);
    }
}
