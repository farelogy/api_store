<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\Kasharian;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;

class KasharianController extends Controller
{
    public function tambah_operasional_cabang(Request $request){
        $validated = Validator::make($request->all(), [
            'kategori' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' =>'Pastikan Field Input Terisi'
            ], 200);
        }

        $kasharian = new Kasharian();
        $kasharian->kategori = $request->kategori;
        $kasharian->keterangan = $request->keterangan;
        $kasharian->jumlah = $request->jumlah;
        $kasharian->status = $request->status;
        $kasharian->id_pembeli = $request->id_pembeli;
        $kasharian->id_karyawan = $request->id_karyawan;
        $kasharian->id_cabang = $request->id_cabang;

        $kasharian->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Operasional Berhasil Ditambahkan',
        ],200);

    }

    public function get_operasional_cabang(){
        $kasharian = Kasharian::orderBy('created_at','desc')->get();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Operasional diterima',
            'data' => $kasharian
        ],200);
    }

        public function edit_operasional_cabang(Request $request){
        $validated = Validator::make($request->all(), [
            'id_kasharian' => 'required',
            'kategori' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi'
            ], 200);
        }

        $kasharian = Kasharian::find($request->id_kasharian);
        $kasharian->kategori = $request->kategori;
        $kasharian->keterangan = $request->keterangan;
        $kasharian->jumlah = $request->jumlah;
        $kasharian->status = $request->status;
        $kasharian->id_pembeli = $request->id_pembeli;
        $kasharian->id_karyawan = $request->id_karyawan;
        $kasharian->id_cabang = $request->id_cabang;
        $karyawan->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Operasional Berhasil Diedit',
        ],200);
    }
}
