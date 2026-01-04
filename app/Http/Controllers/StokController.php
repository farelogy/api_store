<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Historystok;
use App\Models\StokBarang;
use DB;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StokController extends Controller
{
    public function data_stok_barang()
    {
        $get_cabang = Cabang::leftjoin('stok_barang', 'cabang.id', '=', 'stok_barang.id_cabang')->leftjoin('barangs', 'barangs.id', '=', 'stok_barang.id_barang')
            ->select('cabang.id', 'cabang.nama_cabang', DB::raw('SUM(stok_barang.stok) as stok'), DB::raw('SUM(stok_barang.stok*barangs.harga) as rupiah_asset'))
            ->groupby('cabang.id', 'cabang.nama_cabang')->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Cabang diterima',
            'data' => $get_cabang,
        ], 200);
    }

    public function data_stok_barang_detail(Request $request)
    {
        $get_stok = DB::table('stok_barang')->where('id_cabang', $request->id_cabang);
        $get_cabang_barang = DB::table('barangs')->leftJoinSub($get_stok, 'filtered_stok', function (JoinClause $join) {
            $join->on('barangs.id', '=', 'filtered_stok.id_barang');
        })->select('barangs.id', 'barangs.nama_barang', 'barangs.satuan', 'barangs.harga', 'filtered_stok.stok', 'filtered_stok.updated_at')
            ->orderby('barangs.nama_barang', 'asc')
            ->orderByRaw('filtered_stok.stok = 0 asc')
            ->orderby('filtered_stok.stok', 'desc')->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Barang Cabang diterima',
            'data' => $get_cabang_barang,
        ], 200);
    }

    public function add_stok_barang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_barang' => 'required',
            'id_cabang' => 'required',
            'stok' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        $jumlah_stok_update = 0;
        //get stok eksisting
        $count_stok_eksisting = StokBarang::where('id_barang', $request->id_barang)->where('id_cabang', $request->id_cabang)->count();
        if ($count_stok_eksisting != 0) {
            //get id stok eksisting
            $stok_eksisting = StokBarang::where('id_barang', $request->id_barang)->where('id_cabang', $request->id_cabang)->first();
            $update_stok = StokBarang::find($stok_eksisting->id);
            $update_stok->stok = $request->stok;
            $update_stok->save();

            $jumlah_stok_update = $request->stok - $stok_eksisting->stok;
        } else {
            $new_stok = new StokBarang;
            $new_stok->id_barang = $request->id_barang;
            $new_stok->id_cabang = $request->id_cabang;
            $new_stok->stok = $request->stok;
            $new_stok->save();

            $jumlah_stok_update = $request->stok;
        }
        //tambah history stok
        $history_stok = new Historystok;
        $history_stok->id_barang = $request->id_barang;
        $history_stok->id_cabang = $request->id_cabang;
        $history_stok->jumlah = $jumlah_stok_update;
        if ($jumlah_stok_update < 0) {
            $history_stok->status = 'Kurang';
        } else {
            $history_stok->status = 'Tambah';
        }
        $history_stok->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Tambah Stok Barang Berhasil',
        ], 200);
    }

    public function history_stok_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        $get_history = Historystok::leftjoin('barangs', 'historystoks.id_barang', '=', 'barangs.id')->where('historystoks.id_cabang', $request->id_cabang)
            ->select('historystoks.id', 'barangs.nama_barang', 'historystoks.status', 'historystoks.jumlah', 'historystoks.updated_at')->orderby('historystoks.updated_at', 'desc')->limit(50)->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data History Stok Cabang diterima',
            'data' => $get_history,
        ], 200);

    }
}
