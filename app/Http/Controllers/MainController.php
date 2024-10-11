<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

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
            ], 200);
        }

        $encryptedText = $request->password;
        $decoded = base64_decode($encryptedText);
        $utf8Decoded = mb_convert_encoding($decoded, 'UTF-8');
        $decryptedText = Crypt::decryptString($utf8Decoded);

        if($validated)
        {
            $new_user = new User();
            $new_user->name = $request->name;
            $new_user->email = $request->email;
            $new_user->password = Hash::make($decryptedText);
            $new_user->save();

            return response()->json([
                'status' => 'Success',
                'message' => 'Tunggu Approve oleh Admin'
            ],200);
        }
        else
        {
            return response([
                'status' => 'Error',
                'message' => "Mohon maaf, bisa mencoba register kembali."
            ], 500);
        }
    }

    public function login(Request $request){
        $validated = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ],[
            'email.required' => 'Username diperlukan'
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login success',
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);


    }

}
