<?php

namespace App\Http\Controllers;

use App\Models\Detailtransaksidistributor;
use App\Models\Distributor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DistributorController extends Controller
{
    public function data_distributor(Request $request)
    {
        $data = Distributor::orderBy('id', 'DESC')->get();

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function add_distributor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_distributor' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        $distributor = new Distributor;
        $distributor->nama_distributor = $request->nama_distributor;
        $distributor->save();

        return response()->json(['status' => 'success', 'message' => 'Distributor added successfully']);
    }

    public function edit_distributor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:distributors,id',
            'nama_distributor' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        $distributor = Distributor::find($request->id);
        $distributor->nama_distributor = $request->nama_distributor;
        $distributor->save();

        return response()->json(['status' => 'success', 'message' => 'Distributor updated successfully']);
    }

    public function delete_distributor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:distributors,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        $distributor = Distributor::find($request->id);
        $distributor->delete();

        return response()->json(['status' => 'success', 'message' => 'Distributor deleted successfully']);
    }

    public function data_detail_distributor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_distributor' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()]);
        }

        $data_detail_distributor = Detailtransaksidistributor::find($request->id_distributor);

        return response()->json(['status' => 'success', 'data' => $data_detail_distributor]);
    }
}
