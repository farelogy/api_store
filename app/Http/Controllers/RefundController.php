<?php

namespace App\Http\Controllers;

use App\Models\Detailtransaksi;
use App\Models\Historystok;
use App\Models\Pembeli;
use App\Models\StokBarang;
use App\Models\Transaksi;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    public function get_transaksi_cabang_refund(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
            'id_pembeli' => 'required',

        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        //get Piutang Transaksi
        $get_transaksi_cabang_refund = Transaksi::leftjoin('pembelis', 'transaksis.id_pembeli', '=', 'pembelis.id')->
        leftjoin('detailtransaksis', 'detailtransaksis.id_transaksi', '=', 'transaksis.id')->
        select('transaksis.*', 'pembelis.nama_pembeli', DB::raw('SUM(detailtransaksis.jumlah * detailtransaksis.harga_satuan) as total_harga'))->
        where('transaksis.id_cabang', $request->id_cabang)->
        where('transaksis.id_pembeli', $request->id_pembeli)
            ->whereMonth('transaksis.created_at', Carbon::parse($request->date)->month)
            ->whereYear('transaksis.created_at', Carbon::parse($request->date)->year)->
        groupby('transaksis.id', 'transaksis.id_cabang',
            'transaksis.id_pembeli', 'transaksis.nama_transaksi',
            'transaksis.keterangan', 'transaksis.status',
            'transaksis.jumlah_bayar', 'transaksis.created_at',
            'transaksis.updated_at', 'pembelis.nama_pembeli')->
        orderby('transaksis.created_at', 'DESC')
            ->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Transaksi diterima',
            'data' => $get_transaksi_cabang_refund,
        ], 200);
    }

    public function get_detail_transaksi_cabang_refund(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
            'id_transaksi' => 'required',

        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        //get list detail transaksi
        $get_detail_transaksi = Detailtransaksi::where('id_cabang', $request->id_cabang)->where('id_transaksi', $request->id_transaksi)->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Detail Transaksi diterima',
            'data' => $get_detail_transaksi,
        ], 200);

    }

    public function selesai_refund_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
            'id_transaksi' => 'required',
            'data_refund' => 'required',

        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        //tes
        $get_stok_barang = StokBarang::where('id_barang', 392)->where('id_cabang', $request->id_cabang)->first();
        $stok_barang = StokBarang::find($get_stok_barang->id);

        return response()->json([
            'status' => 'Success',
            'message' => 'Refund Berhasil',
            'data' => $stok_barang,
        ], 200);

        //ambil data pembeli
        $pembeli = Pembeli::find($request->id_pembeli);
        $get_transaksi = Transaksi::find($request->id_transaksi);

        $status_transaksi = 'Belum Lunas';

        //convert data masuk ke angka dulu, karena saat dikirim berupa string
        $total_refund = floatval($request->total_refund);
        $total_transaksi_sebelum_refund = floatval($request->total_transaksi_sebelum_refund);
        $terbayar = floatval($request->terbayar);
        $total_harga_setelah_refund = $total_transaksi_sebelum_refund - $total_refund;

        //fokus ke table Pembeli dulu buat update saldonya
        if ($request->status == 'Lunas') {
            //maka total refund akan masuk saldo pembeli
            $pembeli->saldo = $pembeli->saldo + $total_refund;
            $pembeli->save();
            $status_transaksi = $request->status;
            $get_transaksi->status = $status_transaksi;
            $get_transaksi->jumlah_bayar = $total_harga_setelah_refund;

        } else {
            //compare antara total harga dikurangi total refund dengan yang sudah terbayar

            if ($terbayar >= $total_harga_setelah_refund) {
                $pembeli->saldo = $pembeli->saldo + ($terbayar - $total_harga_setelah_refund);
                $pembeli->save();
                $status_transaksi = 'Lunas';
                $get_transaksi->status = $status_transaksi;
                $get_transaksi->jumlah_bayar = $total_harga_setelah_refund;

            }
        }

        //fokus ke table transaksi jikalau statusnya bisa berganti dari Belum Lunas menjadi Lunas
        $get_transaksi->save();

        //convert item string json
        $item = json_decode($request->data_refund);

        //update detail transaksi
        foreach ($item as $x) {
            //find detail transaksi
            $get_detail_transaksi = Detailtransaksi::find($x->id_detail_transaksi);

            //update stok setelah refund
            $get_stok_barang = StokBarang::where('id_barang', $x->id_barang)->where('id_cabang', $request->id_cabang)->first();
            $stok_barang = StokBarang::find($get_stok_barang->id);
            $stok_barang->stok = $stok_barang->stok + $x->jumlah_refund;

            //update history stok
            $history_stok = new Historystok;
            $history_stok->id_barang = $x->id_barang;
            $history_stok->id_cabang = $request->id_cabang;
            $history_stok->jumlah = $x->jumlah_refund;
            $history_stok->status = 'Tambah';
            $history_stok->keterangan = 'Refund Barang';
            $history_stok->save();

            //compare jumlah lama dengan jumlah refund
            if ($x->jumlah == $x->jumlah_refund) {
                $get_detail_transaksi->delete();
            } else {
                $get_detail_transaksi->jumlah = $get_detail_transaksi->jumlah - $x->jumlah_refund;
                $get_detail_transaksi->save();
            }
        }

        //jika refund ini menghapus detail transaksi keseluruhan
        $cek_jumlah_detail_transaksi = Detailtransaksi::where('id_transaksi', $request->id_transaksi)->count();
        if ($cek_jumlah_detail_transaksi == 0) {
            //hapus transaksi
            $hapus_transaksi = Transaksi::find($request->id_transaksi);
            $hapus_transaksi->delete();
        }

        return response()->json([
            'status' => 'Success',
            'message' => 'Refund Berhasil',
        ], 200);
    }

    public function ganti_barang_refund_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
            'id_transaksi' => 'required',
            'data_refund' => 'required',

        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        //ambil data pembeli
        $pembeli = Pembeli::find($request->id_pembeli);
        $get_transaksi = Transaksi::find($request->id_transaksi);

        $status_transaksi = 'Belum Lunas';

        //convert data masuk ke angka dulu, karena saat dikirim berupa string
        $total_refund = floatval($request->total_refund);
        $total_transaksi_sebelum_refund = floatval($request->total_transaksi_sebelum_refund);
        $terbayar = floatval($request->terbayar);
        $total_harga_ganti_barang = floatval($request->total_harga_ganti_barang);

        //fokus ke table Pembeli dulu buat update saldonya
        if ($request->status == 'Lunas') {
            //lihat total harga baru yang sudah dikurangi refund dan ditambah ganti barang
            $total_harga_baru = ($total_transaksi_sebelum_refund - $total_refund) + $total_harga_ganti_barang;

            //cek kondisi total harga lama dengan yang baru
            if ($total_harga_baru <= $total_transaksi_sebelum_refund) {
                $pembeli->saldo = $pembeli->saldo + ($total_transaksi_sebelum_refund - $total_harga_baru);
                $pembeli->save();
                $status_transaksi = 'Lunas';
                $get_transaksi->status = $status_transaksi;
                $get_transaksi->jumlah_bayar = $total_harga_baru;

            } else {
                $status_transaksi = 'Belum Lunas';
                $get_transaksi->status = $status_transaksi;

            }

        } else {
            $total_harga_baru = ($total_transaksi_sebelum_refund - $total_refund) + $total_harga_ganti_barang;

            //cek kondisi total harga lama dengan yang baru
            if ($terbayar >= $total_harga_baru) {
                $pembeli->saldo = $pembeli->saldo + ($terbayar - $total_harga_baru);
                $pembeli->save();
                $status_transaksi = 'Lunas';
                $get_transaksi->status = $status_transaksi;
                $get_transaksi->jumlah_bayar = $total_harga_baru;

            } else {
                $status_transaksi = 'Belum Lunas';
                $get_transaksi->status = $status_transaksi;

            }
        }

        //fokus ke table transaksi jikalau statusnya bisa berganti dari Belum Lunas menjadi Lunas
        $get_transaksi->save();

        //convert item string json
        $item = json_decode($request->data_refund);
        $item_ganti_barang = json_decode($request->data_ganti_barang);

        //update detail transaksi
        foreach ($item as $x) {
            //find detail transaksi
            $get_detail_transaksi = Detailtransaksi::find($x->id_detail_transaksi);

            //update stok setelah refund
            $get_stok_barang = StokBarang::where('id_barang', $x->id_barang)->where('id_cabang', $request->id_cabang)->first();
            $stok_barang = StokBarang::find($get_stok_barang->id);
            $stok_barang->stok = $stok_barang->stok + $x->jumlah_refund;

            //update history stok
            $history_stok = new Historystok;
            $history_stok->id_barang = $x->id_barang;
            $history_stok->id_cabang = $request->id_cabang;
            $history_stok->jumlah = $x->jumlah_refund;
            $history_stok->status = 'Tambah';
            $history_stok->keterangan = 'Refund Barang';
            $history_stok->save();

            //compare jumlah lama dengan jumlah refund
            if ($x->jumlah == $x->jumlah_refund) {
                $get_detail_transaksi->delete();
            } else {
                $get_detail_transaksi->jumlah = $get_detail_transaksi->jumlah - $x->jumlah_refund;
                $get_detail_transaksi->save();
            }
        }

        //tambahkan ganti barangnya ke detail transaksi
        //buat detail transaksi
        foreach ($item_ganti_barang as $x) {
            $detail_trans = new Detailtransaksi;
            $detail_trans->id_transaksi = $request->id_transaksi;
            $detail_trans->id_cabang = $request->id_cabang;
            $detail_trans->id_barang = $x->id;
            $detail_trans->nama_barang = $x->nama_barang;
            $detail_trans->jumlah = $x->stok_dibeli;
            $detail_trans->harga_satuan = $x->harga;
            $detail_trans->save();
        }

        //pengurangan stok barang
        foreach ($item_ganti_barang as $y) {
            $get_stok = DB::table('stok_barang')->updateOrInsert(
                ['id_barang' => $y->id, 'id_cabang' => $request->id_cabang], // Condition to find the record
                ['stok' => $y->stok - $y->stok_dibeli] // Values to update or insert
            );

            //put history stok
            $history_stok = new Historystok;
            $history_stok->id_barang = $y->id;
            $history_stok->id_cabang = $request->id_cabang;
            $history_stok->jumlah = $y->stok_dibeli;
            $history_stok->status = 'Kurang';
            $history_stok->save();

        }

        return response()->json([
            'status' => 'Success',
            'message' => 'Refund Berhasil',
        ], 200);
    }
}
