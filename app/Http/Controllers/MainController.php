<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Cabang;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class MainController extends Controller
{

    public function check_token(Request $request)
    {
        $token = $request->input('access_token');
        
        if ($token) {
            $tokenData = PersonalAccessToken::findToken($token);

            if (!$tokenData || $tokenData->expires_at->lt(Carbon::now())) {
                return response()->json(['message' => 'Token is expired'], 401);
            }
        } else {
            return response()->json(['message' => 'Token not provided'], 401);
        }
        return response()->json(['message' => 'Token is valid'], 200);
    }
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

        if($validated)
        {
            $new_user = new User();
            $new_user->name = $request->name;
            $new_user->email = $request->email;
            $new_user->password = Hash::make($request->password);
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
        $token = "";
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
                'status' => 'Error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        if($user->role)
        {
            $tokenResult = $user->createToken('auth_token');
            $token = $tokenResult->plainTextToken;
    
            // Set expiration time to 30 days from now
            // $tokenResult->accessToken->expires_at = Carbon::now()->addDays(30);
            $tokenResult->accessToken->save();
        }
       

        return response()->json([
            'name' => $user->name,
            'role' => $user->role == null ? "kosong" : $user->role,
            'status' => 'Success',
            'message' => 'Login success',
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    public function tambah_cabang(Request $request){
        $validated = Validator::make($request->all(), [
            'nama_cabang' => 'required',
            'saldo' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }

        $cabang = new Cabang();
        $cabang->nama_cabang = $request->nama_cabang;
        $cabang->saldo = $request->saldo;
        $cabang->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Cabang '.$request->nama_cabang.' Berhasil Ditambahkan',
        ],200);

    }
}
