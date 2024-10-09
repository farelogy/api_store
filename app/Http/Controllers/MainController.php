<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MainController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'name' => 'required',
            'c_password' => 'required'
        ]);
        if($validated->fails())
        {
            return response()->json([
                'status' => 'error',
                'errors' => $validated->errors()
            ], 422);
        }

        if($validated)
        {
            $new_user = new User();
            $new_user->name = $request->name;
            $new_user->email = $request->email;
            $new_user->password = Hash::make($request->password);
            $new_user->save();

            return response()->json([
                'status' => 'Success',
                'message' => 'Authenticated'
            ],200);
        }
        else
        {
            return response([
                'status' => 'Error',
                'message' => "Sorry, We can't Authenticate You."
            ], 500);
        }
    }
}
