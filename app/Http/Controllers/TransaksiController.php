<?php

namespace App\Http\Controllers;

use App\Models\Detailtransaksi;
use Illuminate\Http\Request;
use DB;
use App\Models\Keranjang;
use App\Models\Pembeli;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Historystok;
use App\Models\Kasharian;
use Illuminate\Database\Query\JoinClause;
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
            $keranjang->harga_satuan = $request->harga_satuan;
            $keranjang->save();
        }
        
        return response()->json([
            'status' => 'Success',
            'message' => 'Tambah Barang Berhasil',
        ],200);


    }

    public function delete_keranjang(Request $request){
        $validated = Validator::make($request->all(), [
            'id_barang' => 'required',
            'id_cabang' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }
        //delete barang di keranjang
        Keranjang::where('id_cabang',$request->id_cabang)->where('id_barang',$request->id_barang)->delete();
        
        return response()->json([
            'status' => 'Success',
            'message' => 'Delete Barang Berhasil',
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
        $get_barang = Keranjang::select('keranjangs.id_barang','keranjangs.jumlah','keranjangs.harga_satuan','barangs.nama_barang','stok_barang.stok')
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
        
        //tambahkan pembelinya dulu
        $ceknama = Pembeli::where("nama_pembeli",$request->nama_pembeli)->count();
        if($ceknama != 0)
        {
            $id_pembeli = Pembeli::where("nama_pembeli",$request->nama_pembeli)->first();
            $id_pembeli = $id_pembeli->id;
        }
        else {
            $new_pembeli = new Pembeli();
            $new_pembeli->nama_pembeli = $request->nama_pembeli;
            $new_pembeli->save();
            $id_pembeli = $new_pembeli->id;
        }
        // cek saldo, ini akan pengaruh ke kas harian


        //convert item string json
        $item = json_decode($request->item);

        //hapus keranjang
        Keranjang::where('id_cabang',$request->id_cabang)->delete();

        //buat judul transaksi
        $judul_transaksi = 'TR-'.strtotime("now");
        $transaksi = new Transaksi();
        $transaksi->id_cabang = $request->id_cabang;
        $transaksi->id_pembeli = $id_pembeli;
        $transaksi->nama_transaksi = $judul_transaksi;
        $transaksi->keterangan = $request->keterangan;
        $transaksi->status = $request->status;
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
            $detail_trans->harga_satuan = $x->harga_satuan;
            $detail_trans->save();
        }

        //pengurangan stok barang
        foreach($item as $y)
        {
            $get_stok = DB::table('stok_barang')->updateOrInsert(
                ['id_barang' => $y->id_barang, 'id_cabang'=>$request->id_cabang], // Condition to find the record
                ['stok' => $y->stok - $y->jumlah] // Values to update or insert
            );

            //put history stok
            $history_stok = new Historystok();
            $history_stok->id_barang = $y->id_barang;
            $history_stok->id_cabang = $request->id_cabang;
            $history_stok->jumlah = $y->jumlah;
            $history_stok->status = 'Check Out';
            $history_stok->save();

        }
       //record to kas harian
       if($request->saldo != 0){
        $kasharian = new Kasharian();
       $kasharian->kategori = "Pembelian Barang";
       $kasharian->keterangan = "Transaksi ".$id_trans;
       $kasharian->id_pembeli = $request->id_pembeli;
       $kasharian->jumlah = $request->saldo;
       $kasharian->id_cabang = $request->id_cabang;
       $kasharian->status = "Masuk";
       $kasharian->save();
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
        $get_transaksi =Transaksi::where('id_cabang',$request->id_cabang)->whereDate('created_at', Carbon::parse($request->date))->orderby('created_at','DESC')->get();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Transaksi diterima',
            'data' => $get_transaksi
            ],200);
    }

    public function get_transaksi(Request $request) {
        //get list harga total per transaksi
        $harga_total_transaksi = Detailtransaksi::leftjoin('barangs','barangs.id','=','detailtransaksis.id_barang')->select('detailtransaksis.id_transaksi',DB::raw('SUM(detailtransaksis.jumlah*barangs.harga) as total_rupiah_transaksi'))->groupby('detailtransaksis.id_transaksi');
        //get list Transaksi
        $get_transaksi =Transaksi::leftjoin('cabang','cabang.id','=','transaksis.id_cabang')->leftJoinSub($harga_total_transaksi, 'harga_total_transaksi', function (JoinClause $join){
            $join->on('transaksis.id','=','harga_total_transaksi.id_transaksi');
        })->select('transaksis.*','harga_total_transaksi.total_rupiah_transaksi','cabang.nama_cabang')->whereDate('transaksis.created_at', Carbon::parse($request->date))->orderby('transaksis.created_at','DESC')->get();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Transaksi diterima',
            'data' => $get_transaksi
            ],200);
    }

    public function get_transaksi_cabang_detail(Request $request) {
        $validated = Validator::make($request->all(), [
            'id_transaksi' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }

        //get list keranjang
        $get_barang = Detailtransaksi::select('detailtransaksis.id_barang','detailtransaksis.jumlah','detailtransaksis.nama_barang','detailtransaksis.status','detailtransaksis.keterangan','stok_barang.stok')
        ->leftjoin('stok_barang',function ($join) {
            $join->on('detailtransaksis.id_barang', '=', 'stok_barang.id_barang')
                 ->on('detailtransaksis.id_cabang', '=', 'stok_barang.id_cabang');
        })->where('detailtransaksis.id_transaksi',$request->id_transaksi)->get();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Detail Transaksi diterima',
            'data' => $get_barang
            ],200);
    }


    public function edit_transaksi_cabang(Request $request){
        $validated = Validator::make($request->all(), [
            'id_transaksi' => 'required',
            'id_barang' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }

        //get id detail transaksi
        $get_detail_transaksi = Detailtransaksi::where('id_transaksi',$request->id_transaksi)->where('id_barang',$request->id_barang)->first();
        $edit_detail = Detailtransaksi::find($get_detail_transaksi->id);
        $edit_detail->status = $request->status;
        $edit_detail->keterangan = $request->keterangan;
        $edit_detail->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Edit Detail Transaksi Berhasil',
        ],200);
    }

}
