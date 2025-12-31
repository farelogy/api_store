<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Historysaldocabang;
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

    public function get_data_uang_makan(Request $request)
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
        $kasharian = Kasharian::leftjoin('karyawans', 'kasharians.id_karyawan', '=', 'karyawans.id')
            ->select('kasharians.*', 'karyawans.nama_karyawan')
            ->where('kasharians.id_cabang', $request->id_cabang)
            ->where('kasharians.id_karyawan', $request->id_karyawan)
            ->where('kategori', 'Uang Makan')
            ->whereMonth('kasharians.created_at', Carbon::parse($request->date)->month)
            ->whereYear('kasharians.created_at', Carbon::parse($request->date)->year)
            ->orderBy('kasharians.created_at', 'desc')->get();

        return response()->json([
            'status' => 'Success',
            'message' => 'Data Uang Makan diterima',
            'data' => $kasharian,
        ], 200);
    }

    public function add_data_uang_makan(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
            'jumlah' => 'required',
            'id_karyawan' => 'required',

        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi',
            ], 200);
        }

        $kasharian = new Kasharian;
        $kasharian->kategori = 'Uang Makan';
        $kasharian->keterangan = $request->keterangan;
        $kasharian->jumlah = $request->jumlah;
        $kasharian->status = 'Keluar';
        $kasharian->id_karyawan = $request->id_karyawan;
        $kasharian->id_cabang = $request->id_cabang;
        $kasharian->save();

        //update kas cabang
        $update_kas_cabang = Cabang::find($request->id_cabang);
        $update_kas_cabang->saldo = $update_kas_cabang->saldo - $request->jumlah;
        $update_kas_cabang->save();

        //update posisi saldo cabang terakhir hari itu
        $history_saldo_cabang = Historysaldocabang::where('id_cabang', $request->id_cabang)->whereDate('created_at', Carbon::today())->count();
        if ($history_saldo_cabang == 0) {
            $update_history = new Historysaldocabang;
        } else {
            $id_history_saldo_cabang = Historysaldocabang::where('id_cabang', $request->id_cabang)->whereDate('created_at', Carbon::today())->first();

            $update_history = Historysaldocabang::find($id_history_saldo_cabang->id);
        }
        $update_history->id_cabang = $request->id_cabang;
        $update_history->saldo = $update_kas_cabang->saldo;
        $update_history->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Uang Makan Berhasil Ditambahkan',
        ], 200);

    }

    public function edit_data_uang_makan(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
            'id_kas_harian' => 'required',
            'jumlah' => 'required',
            'id_karyawan' => 'required',

        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi',
            ], 200);
        }

        $kasharian = Kasharian::find($request->id_kas_harian);
        $kasharian->kategori = 'Uang Makan';
        $kasharian->keterangan = $request->keterangan;
        $kasharian->jumlah = $request->jumlah;
        $kasharian->status = 'Keluar';
        $kasharian->id_karyawan = $request->id_karyawan;
        $kasharian->id_cabang = $request->id_cabang;
        $kasharian->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Uang Makan Berhasil Diedit',
        ], 200);

    }

    public function delete_data_uang_makan(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id_kas_harian' => 'required',

        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi',
            ], 200);
        }

        $kasharian = Kasharian::find($request->id_kas_harian);

        //update kas cabang
        $update_kas_cabang = Cabang::find($request->id_cabang);
        $update_kas_cabang->saldo = $update_kas_cabang->saldo + $kasharian->jumlah;
        $update_kas_cabang->save();

        //delete kas harian
        $kasharian->delete();

        return response()->json([
            'status' => 'Success',
            'message' => 'Uang Makan Berhasil Ditambahkan',
        ], 200);

    }
}
