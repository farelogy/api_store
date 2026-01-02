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

        $get_hutang = Transaksi::leftjoin('detailtransaksis', 'detailtransaksis.id_transaksi', '=', 'transaksis.id')->
        select(DB::raw('SUM(transaksis.jumlah_bayar) as terbayar'), DB::raw('SUM(detailtransaksis.jumlah * detailtransaksis.harga_satuan) as total_harga'))->
        where('transaksis.id_cabang', $request->id_cabang)->
        where('transaksis.status', 'Belum Lunas')->
        whereDate('transaksis.created_at', Carbon::parse($request->date))
            ->first();

        if ($get_hutang->total_harga == null) {
            $hutang_total_harga = 0;
        } else {
            $hutang_total_harga = $get_hutang->total_harga;
        }

        if ($get_hutang->terbayar == null) {
            $hutang_terbayar = 0;
        } else {
            $hutang_terbayar = $get_hutang->terbayar;
        }

        $total_hutang = $hutang_terbayar - $hutang_total_harga;

        $get_pembayaran_utang = Kasharian::where('kategori', 'Pembayaran Utang')->whereDate('created_at', Carbon::parse($request->date))->sum('jumlah');
        $get_uang_makan = Kasharian::where('kategori', 'Uang Makan')->whereDate('created_at', Carbon::parse($request->date))->sum('jumlah');
        $operasional_lain_masuk = Kasharian::where('status', 'Masuk')->whereNotIn('kategori', ['Uang Makan', 'Pembelian Barang', 'Pembayaran Utang', 'Setoran Kas'])->whereDate('created_at', Carbon::parse($request->date))->sum('jumlah');
        $operasional_lain_keluar = Kasharian::where('status', 'Keluar')->whereNotIn('kategori', ['Uang Makan', 'Pembelian Barang', 'Pembayaran Utang', 'Setoran Kas'])->whereDate('created_at', Carbon::parse($request->date))->sum('jumlah');
        $total_operasional_lain = $operasional_lain_masuk - $operasional_lain_keluar;
        $get_setoran = Kasharian::where('kategori', 'Setoran Kas')->whereDate('created_at', Carbon::parse($request->date))->sum('jumlah');

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Piutang diterima',
            'total_penjualan' => $total_penjualan,
            'total_utang' => $total_hutang,
            'total_pembayaran_utang' => $get_pembayaran_utang,
            'total_uang_makan' => $get_uang_makan,
            'total_operasional_lain' => $total_operasional_lain,
            'total_setoran' => $get_setoran,

        ], 200);

    }

    public function add_operasional_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'kategori' => 'required',
            'jumlah' => 'required',
            'status' => 'required',

        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi',
            ], 200);
        }

        $kasharian = new Kasharian;
        $kasharian->kategori = $request->kategori;
        $kasharian->keterangan = $request->keterangan;
        $kasharian->jumlah = $request->jumlah;
        $kasharian->status = $request->status;
        $kasharian->id_cabang = $request->id_cabang;

        $kasharian->save();

        //update kas cabang
        $update_kas_cabang = Cabang::find($request->id_cabang);
        if ($request->status == 'Masuk') {
            $update_kas_cabang->saldo = $update_kas_cabang->saldo + $request->jumlah;

        } else {
            $update_kas_cabang->saldo = $update_kas_cabang->saldo - $request->jumlah;
        }
        $update_kas_cabang->save();

        //update posisi saldo cabang terakhir hari itu
        $history_saldo_cabang = Historysaldocabang::where('id_cabang', $request->id_cabang)->whereDate('created_at', Carbon::today())->count();
        if ($history_saldo_cabang == 0) {
            $update_history = new Historysaldocabang;
        } else {
            $id_history_saldo_cabang = Historysaldocabang::where('id_cabang', $request->id_cabang)->whereDate('created_at', Carbon::today())->first();

            $update_history = Historysaldocabang::find($id_history_saldo_cabang->id);
        }
        $update_history->id_cabang = $request->id_cabang;
        $update_history->saldo = $update_kas_cabang->saldo;
        $update_history->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Operasional Berhasil Ditambahkan',
        ], 200);

    }

    public function edit_operasional_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_kasharian' => 'required',
            'kategori' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi',
            ], 200);
        }

        $kasharian = Kasharian::find($request->id_kasharian);
        $kasharian->kategori = $request->kategori;
        $kasharian->keterangan = $request->keterangan;
        $kasharian->jumlah = $request->jumlah;
        $kasharian->status = $request->status;
        $kasharian->id_pembeli = $request->id_pembeli;
        $kasharian->id_karyawan = $request->id_karyawan;
        $kasharian->id_cabang = $request->id_cabang;
        $karyawan->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Operasional Berhasil Diedit',
        ], 200);
    }
}
