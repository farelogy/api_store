<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\Models\Karyawan;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;

class KaryawanController extends Controller
{
    public function tambah_karyawan(Request $request){
        $validated = Validator::make($request->all(), [
            'nama_karyawan' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' =>'Pastikan Field Input Terisi'
            ], 200);
        }

        $karyawan = new Karyawan();
        $karyawan->nama_karyawan = $request->nama_karyawan;
        $karyawan->status = $request->status;
        $karyawan->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Karyawan '.$request->nama_karyawan.' Berhasil Ditambahkan',
        ],200);

    }

    public function get_karyawan(){
        $karyawan = Karyawan::all();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Karyawan diterima',
            'data' => $karyawan
        ],200);
    }

        public function edit_karyawan(Request $request){
        $validated = Validator::make($request->all(), [
            'id_karyawan' => 'required',
            'nama_karyawan' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi'
            ], 200);
        }

        $karyawan = Karyawan::find($request->id_karyawan);
        $karyawan->nama_karyawan = $request->nama_karyawan;
        $karyawan->status = $request->status;
        $karyawan->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Karyawan '.$request->nama_karyawan.' Berhasil Diedit',
        ],200);
    }
}
