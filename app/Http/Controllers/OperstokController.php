<?php

namespace App\Http\Controllers;

use App\Models\Operstok;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OperstokController extends Controller
{
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

    public function approve_oper_stok_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_operstok' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors(),
            ], 200);
        }

        $operstok = Operstok::find($request->id_operstok);
        if (! $operstok) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Oper Stok tidak ditemukan',
            ], 200);
        }

        $operstok->approved = 'Approved';
        $operstok->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Oper Stok berhasil disetujui',
        ], 200);
    }
}
