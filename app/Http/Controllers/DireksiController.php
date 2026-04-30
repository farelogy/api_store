<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Kasharian;
use App\Models\Pembayaran;
use App\Models\Pembeli;
use App\Models\Transaksi;
use DB;

class DireksiController extends Controller
{
    public function data_piutang_direksi()
    {
        // total piutang secara keseluruhan cabang
        $total_penjualan_piutang = Transaksi::leftjoin('detailtransaksis', 'transaksis.id', '=', 'detailtransaksis.id_transaksi')
            ->where('transaksis.status', 'Belum Lunas')
            ->sum('detailtransaksis.harga_satuan * detailtransaksis.jumlah');
        $total_terbayar_piutang = Kasharian::where('kategori', 'Pembayaran Utang')->sum('jumlah');

        $total_piutang = $total_penjualan_piutang - $total_terbayar_piutang;

        // total piutang per cabang, lalu berapa pembeli yang berutang, serta top 5 pembeli dengan piutang terbesar
        $branches = DB::table('cabang')
            ->select('id as branch_id', 'nama_cabang as branch_name')
            ->get()
            ->map(function ($branch) {

                // Total penjualan per cabang
                $penjualan = DB::table('detailtransaksis')
                    ->join('transaksis', 'transaksis.id', '=', 'detailtransaksis.id_transaksi')
                    ->where('transaksis.status', 'Belum Lunas')
                    ->where('transaksis.id_cabang', $branch->branch_id)
                    ->selectRaw('SUM(detailtransaksis.harga_satuan * detailtransaksis.jumlah) as total')
                    ->value('total');

                // Total pembayaran per cabang
                $pembayaran = DB::table('kasharians')
                    ->join('transaksis', 'transaksis.id', '=', 'kasharians.id_transaksi')
                    ->where('kasharians.kategori', 'Pembayaran Utang')
                    ->where('transaksis.id_cabang', $branch->branch_id)
                    ->sum('kasharians.jumlah');

                // Customer count
                $customerCount = DB::table('transaksis')
                    ->where('status', 'Belum Lunas')
                    ->where('id_cabang', $branch->branch_id)
                    ->distinct('id_pembeli')
                    ->count('id_pembeli');

                // Top 5 customers
                $topCustomers = DB::table('pembelis')
                    ->join('transaksis', 'transaksis.id_pembeli', '=', 'pembelis.id')
                    ->leftJoin('detailtransaksis', 'detailtransaksis.id_transaksi', '=', 'transaksis.id')
                    ->leftJoin('kasharians', function ($join) {
                        $join->on('kasharians.id_transaksi', '=', 'transaksis.id')
                            ->where('kasharians.kategori', 'Pembayaran Utang');
                    })
                    ->where('transaksis.status', 'Belum Lunas')
                    ->where('transaksis.id_cabang', $branch->branch_id)
                    ->groupBy('pembelis.id', 'pembelis.nama_pembeli')
                    ->selectRaw('
                pembelis.id as customer_id,
                pembelis.nama_pembeli as name,
                SUM(detailtransaksis.harga_satuan * detailtransaksis.jumlah) 
                    - COALESCE(SUM(kasharians.jumlah), 0) as debt_amount
            ')
                    ->orderByDesc('debt_amount')
                    ->limit(5)
                    ->get()
                    ->map(function ($c, $i) {
                        $c->rank = $i + 1;

                        return $c;
                    });

                $branch->branch_total = ($penjualan ?? 0) - ($pembayaran ?? 0);
                $branch->customer_count = $customerCount;
                $branch->top_customers = $topCustomers;

                return $branch;
            });

        $response = [
            'success' => true,
            'data' => [
                'total_piutang' => $total_piutang,
                'branches' => $branches,
            ],
        ];

        return response()->json($response);
    }
}
