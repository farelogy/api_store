<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\Cabang;
use App\Models\Barang;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Validator;
use App\Models\StokBarang;
use App\Models\Historystok;

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
        $get_stok = DB::table('stok_barang')->where('id_cabang',$request->id_cabang);
        $get_cabang_barang = DB::table('barangs')->leftJoinSub($get_stok, 'filtered_stok', function (JoinClause $join){
            $join->on('barangs.id','=','filtered_stok.id_barang');
        })->select('barangs.id','barangs.nama_barang','barangs.satuan','filtered_stok.stok','filtered_stok.updated_at')->get();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Barang Cabang diterima',
            'data' => $get_cabang_barang
        ],200);
    }

    public function add_stok_barang(Request $request){
        $validated = Validator::make($request->all(), [
            'id_barang' => 'required',
            'id_cabang' => 'required',
            'stok' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }

        $jumlah_stok_update = 0;
        //get stok eksisting
        $count_stok_eksisting = StokBarang::where('id_barang',$request->id_barang)->where('id_cabang',$request->id_cabang)->count();
        if($count_stok_eksisting != 0)
        {
            //get id stok eksisting
            $stok_eksisting = StokBarang::where('id_barang',$request->id_barang)->where('id_cabang',$request->id_cabang)->first();
            $update_stok = StokBarang::find($stok_eksisting->id);
            $update_stok->stok = $request->stok;
            $update_stok->save();

            $jumlah_stok_update = $request->stok - $stok_eksisting->stok;
        }
        else
        {
            $new_stok  = new StokBarang();
            $new_stok->id_barang = $request->id_barang;
            $new_stok->id_cabang = $request->id_cabang;
            $new_stok->stok = $request->stok;
            $new_stok->save();

            $jumlah_stok_update = $request->stok;
        }
        //tambah history stok
        $history_stok = new Historystok();
        $history_stok->id_barang = $request->id_barang;
        $history_stok->id_cabang = $request->id_cabang;
        $history_stok->jumlah = $jumlah_stok_update;
        if($jumlah_stok_update < 0)
        {
            $history_stok->status = 'Kurang';
        }
        else
        {
            $history_stok->status = 'Tambah';
        }
        $history_stok->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Tambah Stok Barang Berhasil',
        ],200);
    }
}
