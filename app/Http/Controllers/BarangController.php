<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Keranjang;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BarangController extends Controller
{
    public function data_barang()
    {
        $get_barang = Barang::all();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Barang diterima',
            'data' => $get_barang,
        ], 200);
    }

    public function add_barang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'nama_barang' => 'required',
            'harga' => 'required',
            'modal' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi',
            ], 200);
        }

        $barang = new Barang;
        $barang->nama_barang = $request->nama_barang;
        $barang->harga = $request->harga;
        $barang->modal = $request->modal;
        $barang->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Tambah Barang Berhasil',
        ], 200);
    }

    public function edit_barang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_barang' => 'required',
            'nama_barang' => 'required',
            'harga' => 'required',
            'modal' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi',
            ], 200);
        }

        $cabang = Barang::find($request->id_barang);
        $cabang->nama_barang = $request->nama_barang;
        $cabang->harga = $request->harga;
        $cabang->modal = $request->modal;
        $cabang->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Barang '.$request->nama_barang.' Berhasil Diedit',
        ], 200);
    }

    public function delete_barang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_barang' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Terdapat Error',
            ], 200);
        }
        //remove stok barang
        DB::table('stok_barang')->where('id_barang', $request->id_barang)->delete();
        //remove keranjang
        Keranjang::where('id_barang', $request->id_barang)->delete();
        //remove barang
        $barang = Barang::find($request->id_barang);
        $barang->delete();

        return response()->json([
            'status' => 'Success',
            'message' => 'Barang '.$request->nama_barang.' Berhasil Dihapus',
        ], 200);
    }
}
