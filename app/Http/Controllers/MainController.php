<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MainController extends Controller
{
    public function register(Request $request)
    {
        
        $validated = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'name' => 'required',
        ],[
            'email.unique' => 'Username sudah digunakan'
        ]);
        
        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
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
