<?php

namespace App\Http\Controllers;

use App\Models\Pembeli;
use Illuminate\Http\Request;

class PembeliController extends Controller
{
    public function get_pembeli(Request $request)
    {
        $get_pembeli = Pembeli::all();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Pembeli diterima',
            'data' => $get_pembeli,
        ], 200);
    }

    public function get_pembeli_satu(Request $request)
    {
        $get_pembeli = Pembeli::where('id', $request->id_pembeli)->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Pembeli diterima',
            'data' => $get_pembeli,
        ], 200);
    }

    public function ceknamapembeli(Request $request)
    {
        $ceknama = Pembeli::where('nama_pembeli', $request->nama_pembeli)->count();

        return response()->json([
            'status' => 'Success',
            'data' => [$ceknama],
        ]);
    }
}
