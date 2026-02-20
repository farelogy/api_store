<?php

namespace App\Http\Controllers;

use App\Models\Operstok;
use App\Models\StokBarang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OperstokController extends Controller
{
    public function get_list_oper_stok()
    {
        $data_operstok = Operstok::leftJoin('cabang as from_cabang', 'operstoks.from_cabang', '=', 'from_cabang.id')->leftJoin('cabang as to_cabang', 'operstoks.to_cabang', '=', 'to_cabang.id')
            ->leftJoin('barangs', 'operstoks.id_barang', '=', 'barangs.id')
            ->select('operstoks.*', 'from_cabang.nama_cabang as from_cabang_nama', 'to_cabang.nama_cabang as to_cabang_nama', 'barangs.nama_barang as nama_barang')
            ->where('operstoks.approved', 'Pending')->orderBy('operstoks.created_at', 'DESC')->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Oper Stok diterima',
            'data' => $data_operstok,
        ], 200);
    }

    public function get_history_oper_stok()
    {

        $data_operstok = Operstok::leftJoin('cabang as from_cabang', 'operstoks.from_cabang', '=', 'from_cabang.id')->leftJoin('cabang as to_cabang', 'operstoks.to_cabang', '=', 'to_cabang.id')
            ->leftJoin('barangs', 'operstoks.id_barang', '=', 'barangs.id')
            ->select('operstoks.*', 'from_cabang.nama_cabang as from_cabang_nama', 'to_cabang.nama_cabang as to_cabang_nama', 'barangs.nama_barang as nama_barang')
            ->whereMonth('operstoks.created_at', Carbon::parse($request->date)->month)
            ->whereYear('operstoks.created_at', Carbon::parse($request->date)->year)->orderBy('operstoks.created_at', 'DESC')->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Oper Stok diterima',
            'data' => $data_operstok,
        ], 200);
    }

    public function get_list_oper_stok_cabang(Request $request)
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
        $data_operstok = Operstok::leftJoin('cabang as from_cabang', 'operstoks.from_cabang', '=', 'from_cabang.id')->leftJoin('cabang as to_cabang', 'operstoks.to_cabang', '=', 'to_cabang.id')
            ->leftJoin('barangs', 'operstoks.id_barang', '=', 'barangs.id')
            ->select('operstoks.*', 'from_cabang.nama_cabang as from_cabang_nama', 'to_cabang.nama_cabang as to_cabang_nama', 'barangs.nama_barang as nama_barang')
            ->where('operstoks.from_cabang', $request->id_cabang)->where('operstoks.approved', 'Pending')->orderBy('operstoks.created_at', 'DESC')->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Oper Stok diterima',
            'data' => $data_operstok,
        ], 200);
    }

    public function get_history_oper_stok_cabang(Request $request)
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
        $data_operstok = Operstok::leftJoin('cabang as from_cabang', 'operstoks.from_cabang', '=', 'from_cabang.id')->leftJoin('cabang as to_cabang', 'operstoks.to_cabang', '=', 'to_cabang.id')
            ->leftJoin('barangs', 'operstoks.id_barang', '=', 'barangs.id')
            ->select('operstoks.*', 'from_cabang.nama_cabang as from_cabang_nama', 'to_cabang.nama_cabang as to_cabang_nama', 'barangs.nama_barang as nama_barang')
            ->where('operstoks.from_cabang', $request->id_cabang)->whereMonth('operstoks.created_at', Carbon::parse($request->date)->month)
            ->whereYear('operstoks.created_at', Carbon::parse($request->date)->year)->orderBy('operstoks.created_at', 'DESC')->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Oper Stok diterima',
            'data' => $data_operstok,
        ], 200);
    }

    public function add_oper_stok_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
            'to_cabang' => 'required',
            'jumlah' => 'required',
            'id_barang' => 'nullable',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        $operstok = new Operstok;
        $operstok->from_cabang = $request->id_cabang;
        $operstok->to_cabang = $request->to_cabang;
        $operstok->stok_transfer = $request->jumlah;
        $operstok->id_barang = $request->id_barang ?? null;
        $operstok->approved = 'Pending';
        $operstok->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Oper Stok berhasil ditambahkan',
        ], 200);
    }

    public function approve_oper_stok(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_oper_stok' => 'required',
            'approval' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        $operstok = Operstok::find($request->id_oper_stok);
        if (! $operstok) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Oper Stok tidak ditemukan',
            ], 200);
        }

        if ($request->approval == 'Approve') {
            //pindah stok di table stok barang
            $stok_barang_from = StokBarang::where('id_cabang', $operstok->from_cabang)->where('id_barang', $operstok->id_barang)->first();
            if ($stok_barang_from) {
                $stok_barang_from->stok = $stok_barang_from->stok - $operstok->stok_transfer;
                $stok_barang_from->save();
            } else {
                return response()->json([
                    'status' => 'Error',
                    'message' => 'Stok Barang di cabang asal tidak ditemukan',
                ], 200);
            }

            $stok_barang_to = StokBarang::where('id_cabang', $operstok->to_cabang)->where('id_barang', $operstok->id_barang)->first();
            if ($stok_barang_to) {
                $stok_barang_to->stok = $stok_barang_to->stok + $operstok->stok_transfer;
                $stok_barang_to->save();
            } else {
                $new_stok_barang_to = new StokBarang;
                $new_stok_barang_to->id_cabang = $operstok->to_cabang;
                $new_stok_barang_to->id_barang = $operstok->id_barang;
                $new_stok_barang_to->stok = $operstok->stok_transfer;
                $new_stok_barang_to->save();
            }
        }

        $operstok->approved = $request->approval;
        $operstok->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Oper Stok berhasil disetujui',
        ], 200);
    }
}
