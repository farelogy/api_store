<?php

namespace App\Http\Controllers;
use App\Models\Barang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function data_barang(){
        $get_barang = Barang::all();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Barang diterima',
            'data' => $get_barang
        ],200);
    }
}
