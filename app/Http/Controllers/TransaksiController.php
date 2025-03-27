<?php

namespace App\Http\Controllers;

use App\Models\Detailtransaksi;
use Illuminate\Http\Request;
use DB;
use App\Models\Keranjang;
use App\Models\Transaksi;
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

    public function get_barang_keranjang(Request $request) {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }


        //get list keranjang
        $get_barang = Keranjang::select('keranjangs.id_barang','keranjangs.jumlah','barangs.nama_barang','stok_barang.stok')
        ->leftjoin('barangs','keranjangs.id_barang','=','barangs.id')
        ->leftjoin('stok_barang','keranjangs.id_barang','=','stok_barang.id_barang')
        ->where('keranjangs.id_cabang',$request->id_cabang)
        ->where('stok_barang.id_cabang',$request->id_cabang)->get();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Keranjang diterima',
            'data' => $get_barang
            ],200);
    }

    public function check_out(Request $request){
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
        ]);
        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }
        
        //convert item string json
        $item = json_decode($request->item);

        //hapus keranjang
        Keranjang::where('id_cabang',$request->id_cabang)->delete();

        //buat judul transaksi
        $judul_transaksi = 'TR-'.strtotime("now");
        $transaksi = new Transaksi();
        $transaksi->id_cabang = $request->id_cabang;
        $transaksi->nama_transaksi = $judul_transaksi;
        $transaksi->save();

        //get id transaksi
        $id_trans = Transaksi::where('nama_transaksi',$judul_transaksi)->first()->id;


        //buat detail transaksi
        foreach($item as $x)
        {
            $detail_trans = new Detailtransaksi();
            $detail_trans->id_transaksi = $id_trans;
            $detail_trans->id_cabang = $request->id_cabang;
            $detail_trans->id_barang = $x->id_barang;
            $detail_trans->nama_barang = $x->nama_barang;
            $detail_trans->jumlah = $x->jumlah;
            $detail_trans->status = $x->status;
            $detail_trans->keterangan = $x->keterangan;
            $detail_trans->save();
        }

        //pengurangan stok barang
        foreach($item as $y)
        {
            $get_stok = DB::table('stok_barang')->updateOrInsert(
                ['id_barang' => $y->id_barang, 'id_cabang'=>$request->id_cabang], // Condition to find the record
                ['stok' => $y->stok - $y->jumlah] // Values to update or insert
            );
        }
       
        return response()->json([
            'status' => 'Success',
            'message' => 'Check Out Berhasil',
        ],200);

    }
    public function get_transaksi_cabang(Request $request) {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }

        //get list Transaksi
        $get_transaksi =Transaksi::where('id_cabang',$request->id_cabang)->get();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Transaksi diterima',
            'data' => $get_transaksi
            ],200);
    }
}
