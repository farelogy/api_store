<?php

namespace App\Http\Controllers;

use App\Models\Kasharian;
use App\Models\Transaksi;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OperasionalController extends Controller
{
    public function get_operasional_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi',
            ], 200);
        }

        $get_penjualan = Transaksi::leftjoin('detailtransaksis', 'detailtransaksis.id_transaksi', '=', 'transaksis.id')->
        select('transaksis.id', 'transaksis.nama_transaksi', DB::raw('SUM(detailtransaksis.jumlah * detailtransaksis.harga_satuan) as total_harga'))->
        where('transaksis.id_cabang', $request->id_cabang)->
        whereDate('transaksis.created_at', Carbon::parse($request->date))->
        groupby('transaksis.id', 'transaksis.nama_transaksi')
            ->get();

        $get_pembayaran = Kasharian::where('kategori', 'Pembelian Barang')->whereDate('kasharians.created_at', Carbon::parse($request->date))->get();
        $get_uang_makan = Kasharian::where('kategori', 'Uang Makan')->whereDate('kasharians.created_at', Carbon::parse($request->date))->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Piutang diterima',
            'data' => [$get_penjualan, $get_pembayaran, $get_uang_makan],
        ], 200);

    }
}
