<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Detailpembayaran;
use App\Models\Detailtransaksi;
use App\Models\Historysaldocabang;
use App\Models\Historystok;
use App\Models\Kasharian;
use App\Models\Keranjang;
use App\Models\Pembayaran;
use App\Models\Pembeli;
use App\Models\Transaksi;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransaksiController extends Controller
{
    public function cek_keranjang(Request $request)
    {
        $get_keranjang = DB::table('keranjangs')->where('id_cabang', $request->id_cabang)->count();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Keranjang diterima',
            'data' => $get_keranjang,
        ], 200);
    }

    public function tambah_keranjang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_barang' => 'required',
            'id_cabang' => 'required',
            'jumlah' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }
        //cek barang di keranjang
        $cek_keranjang_barang = Keranjang::where('id_cabang', $request->id_cabang)->where('id_barang', $request->id_barang)->count();
        if ($cek_keranjang_barang != 0) {
            $barang_sudah_di_keranjang = Keranjang::where('id_cabang', $request->id_cabang)->where('id_barang', $request->id_barang)->first();
            $keranjang = Keranjang::find($barang_sudah_di_keranjang->id);
            $keranjang->jumlah = $barang_sudah_di_keranjang->jumlah + $request->jumlah;
            $keranjang->save();
        } else {
            $keranjang = new Keranjang;
            $keranjang->id_cabang = $request->id_cabang;
            $keranjang->id_barang = $request->id_barang;
            $keranjang->jumlah = $request->jumlah;
            $keranjang->harga_satuan = $request->harga_satuan;
            $keranjang->save();
        }

        return response()->json([
            'status' => 'Success',
            'message' => 'Tambah Barang Berhasil',
        ], 200);

    }

    public function delete_keranjang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_barang' => 'required',
            'id_cabang' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }
        //delete barang di keranjang
        Keranjang::where('id_cabang', $request->id_cabang)->where('id_barang', $request->id_barang)->delete();

        return response()->json([
            'status' => 'Success',
            'message' => 'Delete Barang Berhasil',
        ], 200);

    }

    public function get_barang_keranjang(Request $request)
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

        //get list keranjang
        $get_barang = Keranjang::select('keranjangs.id_barang', 'keranjangs.jumlah', 'keranjangs.harga_satuan', 'barangs.nama_barang', 'stok_barang.stok')
            ->leftjoin('barangs', 'keranjangs.id_barang', '=', 'barangs.id')
            ->leftjoin('stok_barang', 'keranjangs.id_barang', '=', 'stok_barang.id_barang')
            ->where('keranjangs.id_cabang', $request->id_cabang)
            ->where('stok_barang.id_cabang', $request->id_cabang)->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Keranjang diterima',
            'data' => $get_barang,
        ], 200);
    }

    public function check_out(Request $request)
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

        //buat judul transaksi
        $judul_transaksi = 'TR-'.strtotime('now');

        // update saldo pembeli jika pembayaran transaksi berlebih
        $saldopembeli = floatval($request->saldo);
        $jumlahbayar = floatval($request->jumlah_bayar);
        $total_harga = floatval($request->total_harga);

        //hapus keranjang
        Keranjang::where('id_cabang', $request->id_cabang)->delete();

        $transaksi = new Transaksi;
        $transaksi->id_cabang = $request->id_cabang;
        $transaksi->id_pembeli = $id_pembeli;
        $transaksi->nama_transaksi = $judul_transaksi;
        $transaksi->keterangan = $request->keterangan;
        $transaksi->status = $request->status;
        if ($request->status == 'Lunas') {
            $transaksi->jumlah_bayar = $total_harga;
        } else {
            $transaksi->jumlah_bayar = $jumlahbayar + $saldopembeli;
        }
        $transaksi->save();

        //get id transaksi
        $id_trans = Transaksi::where('nama_transaksi', $judul_transaksi)->first()->id;

        //tambahkan pembelinya dulu
        $ceknama = Pembeli::where('nama_pembeli', $request->nama_pembeli)->count();
        if ($ceknama != 0) {
            $id_pembeli = Pembeli::where('nama_pembeli', $request->nama_pembeli)->first();
            $id_pembeli = $id_pembeli->id;
        } else {
            $new_pembeli = new Pembeli;
            $new_pembeli->nama_pembeli = $request->nama_pembeli;
            $new_pembeli->saldo = 0;
            $new_pembeli->save();
            $id_pembeli = $new_pembeli->id;
        }

        $update_pembeli = Pembeli::find($id_pembeli);

        if (($saldopembeli + $jumlahbayar) - $total_harga >= 0) {
            $update_pembeli->saldo = ($saldopembeli + $jumlahbayar) - $total_harga;
            $update_pembeli->save();

            if ($update_pembeli->saldo > 0) {
                $kasharian = new Kasharian;
                $kasharian->kategori = 'Saldo Pembeli';
                $kasharian->keterangan = 'Saldo lebih dari transaksi '.$judul_transaksi;
                $kasharian->id_pembeli = $id_pembeli;
                $kasharian->jumlah = $update_pembeli->saldo;
                $kasharian->id_cabang = $request->id_cabang;
                $kasharian->id_transaksi = $id_trans;
                $kasharian->status = 'Masuk';
                $kasharian->save();
            }
        } else {
            $update_pembeli->saldo = 0;
            $update_pembeli->save();
        }

        //convert item string json
        $item = json_decode($request->item);

        //buat detail transaksi
        foreach ($item as $x) {
            $detail_trans = new Detailtransaksi;
            $detail_trans->id_transaksi = $id_trans;
            $detail_trans->id_cabang = $request->id_cabang;
            $detail_trans->id_barang = $x->id_barang;
            $detail_trans->nama_barang = $x->nama_barang;
            $detail_trans->jumlah = $x->jumlah;
            $detail_trans->harga_satuan = $x->harga_satuan;
            $detail_trans->save();
        }

        //pengurangan stok barang
        foreach ($item as $y) {
            $get_stok = DB::table('stok_barang')->updateOrInsert(
                ['id_barang' => $y->id_barang, 'id_cabang' => $request->id_cabang], // Condition to find the record
                ['stok' => $y->stok - $y->jumlah] // Values to update or insert
            );

            //put history stok
            $history_stok = new Historystok;
            $history_stok->id_barang = $y->id_barang;
            $history_stok->id_cabang = $request->id_cabang;
            $history_stok->jumlah = $y->jumlah;
            $history_stok->status = 'Check Out';
            $history_stok->save();

        }
        //record to kas harian dan pembayarans
        if ($jumlahbayar != 0) {
            $pembayaran = new Pembayaran;
            $pembayaran->nama_pembayaran = 'PB-'.strtotime('now');
            $pembayaran->id_pembeli = $id_pembeli;
            $pembayaran->status = $request->status;
            $pembayaran->jumlah_bayar = $jumlahbayar;
            $pembayaran->save();

            $detail_pembayaran = new DetailPembayaran;
            $detail_pembayaran->id_pembayaran = $pembayaran->id;
            $detail_pembayaran->id_transaksi = $id_trans;
            $detail_pembayaran->save();

            $kasharian = new Kasharian;
            $kasharian->kategori = 'Pembelian Barang';
            $kasharian->keterangan = 'Transaksi '.$judul_transaksi;
            $kasharian->id_pembeli = $id_pembeli;
            $kasharian->jumlah = $request->jumlah_bayar;
            $kasharian->id_cabang = $request->id_cabang;
            $kasharian->id_transaksi = $id_trans;
            $kasharian->status = 'Masuk';
            $kasharian->save();

            //karena ada pembayaran maka perlu masuk juga ke saldo cabang
            $update_cabang = Cabang::find($request->id_cabang);
            $update_cabang->saldo = $update_cabang->saldo + $jumlahbayar;
            $update_cabang->save();

            //update posisi saldo cabang terakhir hari itu
            $history_saldo_cabang = Historysaldocabang::where('id_cabang', $request->id_cabang)->whereDate('created_at', Carbon::today())->count();
            if ($history_saldo_cabang == 0) {
                $update_history = new Historysaldocabang;
            } else {
                $id_history_saldo_cabang = Historysaldocabang::where('id_cabang', $request->id_cabang)->whereDate('created_at', Carbon::today())->first();

                $update_history = Historysaldocabang::find($id_history_saldo_cabang->id);
            }
            $update_history->id_cabang = $request->id_cabang;
            $update_history->saldo = $update_cabang->saldo;
            $update_history->save();

        }

        return response()->json([
            'status' => 'Success',
            'message' => 'Check Out Berhasil',
        ], 200);

    }

    public function get_transaksi_cabang(Request $request)
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

        //get list Transaksi
        $get_transaksi = Transaksi::leftjoin('pembelis', 'transaksis.id_pembeli', '=', 'pembelis.id')->select('transaksis.*', 'pembelis.nama_pembeli')->
        where('transaksis.id_cabang', $request->id_cabang)->whereDate('transaksis.created_at', Carbon::parse($request->date))->orderby('transaksis.created_at', 'DESC')
            ->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Transaksi diterima',
            'data' => $get_transaksi,
        ], 200);
    }

    public function get_transaksi(Request $request)
    {
        //get list harga total per transaksi
        $harga_total_transaksi = Detailtransaksi::select('detailtransaksis.id_transaksi', DB::raw('SUM(detailtransaksis.jumlah*detailtransaksis.harga_satuan) as total_rupiah_transaksi'))->groupby('detailtransaksis.id_transaksi');
        //get list Transaksi
        $get_transaksi = Transaksi::leftjoin('cabang', 'cabang.id', '=', 'transaksis.id_cabang')->leftJoinSub($harga_total_transaksi, 'harga_total_transaksi', function (JoinClause $join) {
            $join->on('transaksis.id', '=', 'harga_total_transaksi.id_transaksi');
        })->select('transaksis.*', 'harga_total_transaksi.total_rupiah_transaksi', 'cabang.nama_cabang')->whereDate('transaksis.created_at', Carbon::parse($request->date))->orderby('transaksis.created_at', 'DESC')->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Transaksi diterima',
            'data' => $get_transaksi,
        ], 200);
    }

    public function get_transaksi_cabang_detail(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_transaksi' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        //get list keranjang
        $get_barang = Detailtransaksi::select('detailtransaksis.id_barang', 'detailtransaksis.jumlah', 'detailtransaksis.nama_barang', 'detailtransaksis.status', 'detailtransaksis.keterangan', 'detailtransaksis.harga_satuan', 'stok_barang.stok')
            ->leftjoin('stok_barang', function ($join) {
                $join->on('detailtransaksis.id_barang', '=', 'stok_barang.id_barang')
                    ->on('detailtransaksis.id_cabang', '=', 'stok_barang.id_cabang');
            })->where('detailtransaksis.id_transaksi', $request->id_transaksi)->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Detail Transaksi diterima',
            'data' => $get_barang,
        ], 200);
    }

    public function edit_transaksi_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_transaksi' => 'required',
            'id_barang' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        //get id detail transaksi
        $get_detail_transaksi = Detailtransaksi::where('id_transaksi', $request->id_transaksi)->where('id_barang', $request->id_barang)->first();
        $edit_detail = Detailtransaksi::find($get_detail_transaksi->id);
        $edit_detail->status = $request->status;
        $edit_detail->keterangan = $request->keterangan;
        $edit_detail->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Edit Detail Transaksi Berhasil',
        ], 200);
    }

    public function get_piutang_cabang(Request $request)
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
        $get_piutang = Transaksi::leftjoin('pembelis', 'transaksis.id_pembeli', '=', 'pembelis.id')->
        leftjoin('detailtransaksis', 'detailtransaksis.id_transaksi', '=', 'transaksis.id')->
        select('transaksis.*', 'pembelis.nama_pembeli', DB::raw('SUM(detailtransaksis.jumlah * detailtransaksis.harga_satuan) as total_harga'))->
        where('transaksis.id_cabang', $request->id_cabang)->
        where('transaksis.id_pembeli', $request->id_pembeli)->
        where('transaksis.status', 'Belum Lunas')->
        groupby('transaksis.id', 'transaksis.id_cabang',
            'transaksis.id_pembeli', 'transaksis.nama_transaksi',
            'transaksis.keterangan', 'transaksis.status',
            'transaksis.jumlah_bayar', 'transaksis.created_at',
            'transaksis.updated_at', 'pembelis.nama_pembeli')->
        orderby('transaksis.created_at', 'DESC')
            ->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Piutang diterima',
            'data' => $get_piutang,
        ], 200);
    }

    public function bayar_piutang_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
            'id_pembeli' => 'required',
            'id_transaksi' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        //tabel Transaksi
        $jumlahbayar = floatval($request->jumlah_bayar);
        $total_harga = floatval($request->total_harga);
        $sisa_bayar = floatval($request->sisa_bayar);

        $transaksi = Transaksi::find($request->id_transaksi);
        if ($request->status == 'Lunas') {
            $transaksi->jumlah_bayar = $total_harga;

        } else {
            $transaksi->jumlah_bayar = $transaksi->jumlah_bayar + $jumlahbayar + $request->saldo_pembeli;
        }
        $transaksi->status = $request->status;
        $transaksi->save();

        //jika bayar nya lebih
        $get_pembeli = Pembeli::find($request->id_pembeli);
        if ($jumlahbayar > $sisa_bayar) {
            $get_pembeli->saldo = ($jumlahbayar - $sisa_bayar);
            $get_pembeli->save();
        } else {
            $get_pembeli->saldo = 0;
            $get_pembeli->save();

        }

        //record to kas harian dan pembayarans
        if ($jumlahbayar != 0) {
            $pembayaran = new Pembayaran;
            $pembayaran->nama_pembayaran = 'PB-'.strtotime('now');
            $pembayaran->id_pembeli = $request->id_pembeli;
            $pembayaran->status = $request->status;
            $pembayaran->jumlah_bayar = $jumlahbayar;
            $pembayaran->save();

            $detail_pembayaran = new DetailPembayaran;
            $detail_pembayaran->id_pembayaran = $pembayaran->id;
            $detail_pembayaran->id_transaksi = $request->id_transaksi;
            $detail_pembayaran->save();

            $kasharian = new Kasharian;
            $kasharian->kategori = 'Pembayaran Utang';
            $kasharian->keterangan = 'Transaksi '.$request->nama_transaksi;
            $kasharian->id_pembeli = $request->id_pembeli;
            $kasharian->jumlah = $jumlahbayar;
            $kasharian->id_cabang = $request->id_cabang;
            $kasharian->id_transaksi = $request->id_transaksi;
            $kasharian->status = 'Masuk';
            $kasharian->save();

            //karena ada pembayaran maka perlu masuk juga ke saldo cabang
            $update_cabang = Cabang::find($request->id_cabang);
            $update_cabang->saldo = $update_cabang->saldo + $jumlahbayar;
            $update_cabang->save();

            //update posisi saldo cabang terakhir hari itu
            $history_saldo_cabang = Historysaldocabang::where('id_cabang', $request->id_cabang)->whereDate('created_at', Carbon::today())->count();
            if ($history_saldo_cabang == 0) {
                $update_history = new Historysaldocabang;
            } else {
                $id_history_saldo_cabang = Historysaldocabang::where('id_cabang', $request->id_cabang)->whereDate('created_at', Carbon::today())->first();

                $update_history = Historysaldocabang::find($id_history_saldo_cabang->id);
            }
            $update_history->id_cabang = $request->id_cabang;
            $update_history->saldo = $update_cabang->saldo;
            $update_history->save();

        }

        return response()->json([
            'status' => 'Success',
            'message' => 'Pembayaran Piutang Berhasil',
        ], 200);

    }
}
