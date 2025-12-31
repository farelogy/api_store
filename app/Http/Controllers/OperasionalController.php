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
        select(DB::raw('SUM(detailtransaksis.jumlah * detailtransaksis.harga_satuan) as total_harga'))->
        where('transaksis.id_cabang', $request->id_cabang)->
        whereDate('transaksis.created_at', Carbon::parse($request->date))
            ->first();
        if ($get_penjualan->total_harga == null) {
            $total_penjualan = 0;
        } else {
            $total_penjualan = $get_penjualan->total_harga;
        }

        $get_pembayaran_utang = Kasharian::where('kategori', 'Pembayaran Utang')->whereDate('created_at', Carbon::parse($request->date))->sum('jumlah');
        $get_uang_makan = Kasharian::where('kategori', 'Uang Makan')->whereDate('created_at', Carbon::parse($request->date))->sum('jumlah');
        $operasional_lain = Kasharian::whereNotIn('kategori', ['Uang Makan', 'Pembelian Barang', 'Setoran'])->whereDate('created_at', Carbon::parse($request->date))->sum('jumlah');

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Piutang diterima',
            'total_penjualan' => $total_penjualan,
            'total_pembayaran_utang' => $get_pembayaran_utang,
            'total_uang_makan' => $get_uang_makan,

        ], 200);

    }
}
