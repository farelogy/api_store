<?php

namespace App\Http\Controllers;

use App\Models\Cabang;
use App\Models\Kasharian;
use App\Models\Pembayaran;
use App\Models\Pembeli;
use App\Models\Transaksi;
use Carbon\Carbon;
use DB;

class DireksiController extends Controller
{
    public function data_piutang_direksi()
    {
        // total piutang secara keseluruhan cabang
        $total_penjualan_piutang = Transaksi::leftjoin('detailtransaksis', 'transaksis.id', '=', 'detailtransaksis.id_transaksi')
            ->where('transaksis.status', 'Belum Lunas')
            ->selectRaw('SUM(detailtransaksis.harga_satuan * detailtransaksis.jumlah) as total')
            ->value('total');
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
        COALESCE(SUM(detailtransaksis.harga_satuan * detailtransaksis.jumlah), 0)
            - COALESCE(SUM(kasharians.jumlah), 0) AS debt_amount
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
                'total_debt' => $total_piutang,
                'branches' => $branches,
            ],
        ];

        return response()->json($response, 200);
    }

    public function data_keuntungan_direksi()
    {

        $today = Carbon::today();

        $totalRevenue = DB::table('detailtransaksis')
            ->join('transaksis', 'transaksis.id', '=', 'detailtransaksis.id_transaksi')
            ->join('barangs', 'barangs.id', '=', 'detailtransaksis.id_barang')
            ->whereDate('transaksis.created_at', $today)
            ->selectRaw('SUM((detailtransaksis.jumlah * detailtransaksis.harga_satuan) - (detailtransaksis.jumlah * COALESCE(barangs.modal, 0))) as total')
            ->value('total');

        $branches = DB::table('cabang')
            ->select('id as branch_id', 'nama_cabang as branch_name')
            ->get()
            ->map(function ($branch) use ($today) {

                $revenue = DB::table('detailtransaksis')
                    ->join('transaksis', 'transaksis.id', '=', 'detailtransaksis.id_transaksi')
                    ->join('barangs', 'barangs.id', '=', 'detailtransaksis.id_barang')
                    ->where('transaksis.id_cabang', $branch->branch_id)
                    ->whereDate('transaksis.created_at', $today)
                    ->selectRaw('SUM((detailtransaksis.jumlah * detailtransaksis.harga_satuan) - (detailtransaksis.jumlah * COALESCE(barangs.modal, 0))) as total')
                    ->value('total');

                $branch->revenue = $revenue ?? 0;

                return $branch;
            });

        $response = [
            'total_revenue' => $totalRevenue ?? 0,
            'branches' => $branches,
        ];

        return response()->json([
            'success' => true,
            'data' => $response,
        ], 200);

    }

    public function data_product_direksi()
    {
        $totalProducts = DB::table('stok_barang')
            ->distinct('id_barang')
            ->count('id_barang');
        $branches = DB::table('cabang')
            ->select('id as branch_id', 'nama_cabang as branch_name')
            ->get()
            ->map(function ($branch) {

                $productCount = DB::table('stok_barang')
                    ->where('id_cabang', $branch->branch_id)
                    ->count('id_barang');

                $branch->product_count = $productCount;

                return $branch;
            });
        $response = [
            'total_products' => $totalProducts,
            'branches' => $branches,
        ];

        return response()->json([
            'success' => true,
            'data' => $response,
        ], 200);
    }

    public function data_stock_direksi()
    {
        $totalStock = DB::table('stok_barang')
            ->selectRaw('SUM(stok) as total_stock')
            ->value('total_stock');

        $branches = DB::table('cabang')
            ->select('id as branch_id', 'nama_cabang as branch_name')
            ->get()
            ->map(function ($branch) {

                $stockCount = DB::table('stok_barang')
                    ->where('id_cabang', $branch->branch_id)
                    ->selectRaw('SUM(stok) as total_stock')
                    ->value('total_stock');

                $branch->stock_count = $stockCount;

                return $branch;
            });

        $response = [
            'total_stocks' => $totalStock,
            'branches' => $branches,
        ];

        return response()->json([
            'success' => true,
            'data' => $response,
        ], 200);
    }

    public function data_cash_flow_direksi()
    {
        $pusatSaldo = DB::table('kaspusats')
            ->where('id', 1)
            ->value('saldo');
        $branches = DB::table('cabang')
            ->select('id as branch_id', 'nama_cabang as branch_name', 'saldo')
            ->get()
            ->map(function ($branch) {
                $branch->cash_flow = $branch->saldo ?? 0;
                unset($branch->saldo); // hapus kolom saldo biar sesuai format

                return $branch;
            });
        $pusatBranch = (object) [
            'branch_id' => 0, // bisa pakai 0 atau null untuk pusat
            'branch_name' => 'Pusat',
            'cash_flow' => $pusatSaldo ?? 0,
        ];

        $allBranches = collect([$pusatBranch])->merge($branches);
        $netCashFlow = ($pusatSaldo ?? 0) + $branches->sum('cash_flow');
        $response = [
            'net_cash_flow' => $netCashFlow,
            'branches' => $allBranches,
        ];

        return response()->json([
            'success' => true,
            'data' => $response,
        ], 200);

    }

    public function data_asset_value_direksi()
    {
        $totalAssetValue = DB::table('stok_barang')
            ->join('barangs', 'barangs.id', '=', 'stok_barang.id_barang')
            ->selectRaw('SUM(stok_barang.stok * COALESCE(barangs.harga, 0)) as total')
            ->value('total');
        $branches = DB::table('cabang')
            ->select('id as branch_id', 'nama_cabang as branch_name')
            ->get()
            ->map(function ($branch) {

                $assetValue = DB::table('stok_barang')
                    ->join('barangs', 'barangs.id', '=', 'stok_barang.id_barang')
                    ->where('stok_barang.id_cabang', $branch->branch_id)
                    ->selectRaw('SUM(stok_barang.stok * COALESCE(barangs.harga, 0)) as total')
                    ->value('total');

                $branch->asset_value = $assetValue ?? 0;

                return $branch;
            });
        $response = [
            'total_asset_value' => $totalAssetValue ?? 0,
            'branches' => $branches,
        ];

        return response()->json([
            'success' => true,
            'data' => $response,
        ], 200);

    }

    public function data_investment_direksi()
    {
        $totalAssetValue = DB::table('stok_barang')
            ->join('barangs', 'barangs.id', '=', 'stok_barang.id_barang')
            ->selectRaw('SUM(stok_barang.stok * COALESCE(barangs.modal, 0)) as total')
            ->value('total');
        $branches = DB::table('cabang')
            ->select('id as branch_id', 'nama_cabang as branch_name')
            ->get()
            ->map(function ($branch) {

                $assetValue = DB::table('stok_barang')
                    ->join('barangs', 'barangs.id', '=', 'stok_barang.id_barang')
                    ->where('stok_barang.id_cabang', $branch->branch_id)
                    ->selectRaw('SUM(stok_barang.stok * COALESCE(barangs.modal, 0)) as total')
                    ->value('total');

                $branch->investment_value = $assetValue ?? 0;

                return $branch;
            });
        $response = [
            'total_investment' => $totalAssetValue ?? 0,
            'branches' => $branches,
        ];

        return response()->json([
            'success' => true,
            'data' => $response,
        ], 200);

    }

    public function data_sales_today_direksi()
    {

        $today = Carbon::today();

        $totalSales = DB::table('detailtransaksis')
            ->join('transaksis', 'transaksis.id', '=', 'detailtransaksis.id_transaksi')
            ->whereDate('transaksis.created_at', $today)
            ->selectRaw('SUM(detailtransaksis.jumlah * detailtransaksis.harga_satuan) as total')
            ->value('total');

        $totalTransactions = DB::table('transaksis')
            ->whereDate('created_at', $today)
            ->count('id');

        $branches = DB::table('cabang')
            ->select('id as branch_id', 'nama_cabang as branch_name')
            ->get()
            ->map(function ($branch) use ($today) {

                $salesAmount = DB::table('detailtransaksis')
                    ->join('transaksis', 'transaksis.id', '=', 'detailtransaksis.id_transaksi')
                    ->where('transaksis.id_cabang', $branch->branch_id)
                    ->whereDate('transaksis.created_at', $today)
                    ->selectRaw('SUM(detailtransaksis.jumlah * detailtransaksis.harga_satuan) as total')
                    ->value('total');

                $transactionCount = DB::table('transaksis')
                    ->where('id_cabang', $branch->branch_id)
                    ->whereDate('created_at', $today)
                    ->count('id');

                $branch->sales_amount = $salesAmount ?? 0;
                $branch->transaction_count = $transactionCount;

                return $branch;
            });
        $response = [
            'total_sales' => $totalSales ?? 0,
            'total_transactions' => $totalTransactions,
            'branches' => $branches,
        ];

        return response()->json([
            'success' => true,
            'data' => $response,
        ], 200);
    }
}
