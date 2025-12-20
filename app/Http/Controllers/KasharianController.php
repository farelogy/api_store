<?php

namespace App\Http\Controllers;

use App\Models\Kasharian;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KasharianController extends Controller
{
    public function tambah_operasional_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'kategori' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi',
            ], 200);
        }

        $kasharian = new Kasharian;
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
        ], 200);

    }

    public function get_operasional_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Hubungi Admin Anda',
            ], 200);
        }
        $kasharian = Kasharian::leftjoin('pembelis', 'kasharians.id_pembeli', '=', 'pembelis.id')
            ->leftjoin('karyawans', 'kasharians.id_karyawan', '=', 'karyawans.id')
            ->select('kasharians.*', 'pembelis.nama_pembeli', 'karyawans.nama_karyawan')
            ->where('kasharians.id_cabang', $request->id_cabang)
            ->whereDate('kasharians.created_at', Carbon::parse($request->date))
            ->orderBy('kasharians.created_at', 'desc')->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Operasional diterima',
            'data' => $kasharian,
        ], 200);
    }

    public function edit_operasional_cabang(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_kasharian' => 'required',
            'kategori' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi',
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
        ], 200);
    }
}
