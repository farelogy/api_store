<?php

namespace App\Http\Controllers;

use App\Models\Pembeli;
use Illuminate\Http\Request;
use DB;
use App\Models\Keranjang;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Historystok;
use Illuminate\Database\Query\JoinClause;
class PembeliController extends Controller
{
        public function get_pembeli(Request $request) {

        //get list keranjang
        $get_pembeli = Pembeli::all();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Pembeli diterima',
            'data' => $get_pembeli
            ],200);
    }

        public function ceknamapembeli(Request $request){
        $ceknama = Pembeli::where("nama_pembeli",$request->nama_pembeli)->count();
        return response()->json([
            'status' => 'Success',
            'data' => $ceknama
        ]);
    }
}
