<?php

namespace App\Http\Controllers;

use App\Models\Detailtransaksi;
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

        //convert item string json
        $item = json_decode($request->data_refund);

        //update detail transaksi
        foreach ($item as $x) {
            //find detail transaksi
            $get_detail_transaksi = Detailtransaksi::find($x->id_detail_transaksi);

            //compare jumlah lama dengan jumlah refund
            if ($x->jumlah == $x->jumlah_refund) {
                $get_detail_transaksi->delete();
            } else {
                $get_detail_transaksi->jumlah = $get_detail_transaksi->jumlah - $x->jumlah_refund;
                $get_detail_transaksi->save();
            }
        }

        return response()->json([
            'status' => 'Success',
            'message' => 'Refund Berhasil',
        ], 200);
    }
}
