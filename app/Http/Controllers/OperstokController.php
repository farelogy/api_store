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
            ->select('operstoks.*', 'from_cabang.nama_cabang as from_cabang_nama', 'to_cabang.nama_cabang as to_cabang_nama')
            ->where('operstoks.from_cabang', $request->id_cabang)->orderBy('operstoks.created_at', 'DESC')->get();

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
            ->select('operstoks.*', 'from_cabang.nama_cabang as from_cabang_nama', 'to_cabang.nama_cabang as to_cabang_nama')->where('operstoks.from_cabang', $request->id_cabang)->whereMonth('created_at', Carbon::parse($request->date)->month)
            ->whereYear('created_at', Carbon::parse($request->date)->year)->orderBy('created_at', 'DESC')->get();

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

        $operstok = Operstok::create([
            'from_cabang' => $request->id_cabang,
            'to_cabang' => $request->to_cabang,
            'stok_transfer' => $request->jumlah,
            'id_barang' => $request->id_barang,
            'approved' => 'Pending',
        ]);

        return response()->json([
            'status' => 'Success',
            'message' => 'Oper Stok berhasil ditambahkan',
        ], 200);
    }
}
