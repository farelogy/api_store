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
            ->where('operstoks.id_cabang', $request->id_cabang)->orderBy('operstoks.created_at', 'DESC')->get();

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
            ->select('operstoks.*', 'from_cabang.nama_cabang as from_cabang_nama', 'to_cabang.nama_cabang as to_cabang_nama')->where('id_cabang', $request->id_cabang)->whereMonth('created_at', Carbon::parse($request->date)->month)
            ->whereYear('created_at', Carbon::parse($request->date)->year)->orderBy('created_at', 'DESC')->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Oper Stok diterima',
            'data' => $data_operstok,
        ], 200);
    }
}
