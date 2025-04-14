<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Cabang;
use App\Models\Keranjang;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;
use DB;
class MainController extends Controller
{

    public function check_token(Request $request)
    {
        $token = $request->input('access_token');
        $tokenData = PersonalAccessToken::findToken($token);
        if ($tokenData) {
            return response()->json(['message' => 'Token is valid'], 200);

        } else {
            return response()->json(['message' => 'Token not provided'], 401);
        }
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
                'message' => 'Pastikan Field Input Terisi'
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
                'message' =>'Pastikan Field Input Terisi'
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

    public function data_cabang(){
        $cabang = Cabang::all();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Cabang diterima',
            'data' => $cabang
        ],200);
    }

    public function data_cabang_home(){
        $cabang = DB::select("
        SELECT a.*,b.tanggal,b.jumlah_transaksi,b.total_harga FROM cabang a LEFT JOIN (SELECT d.id_cabang,DATE_FORMAT(d.created_at,'%Y-%m-%d') as tanggal,COUNT(DISTINCT(d.nama_transaksi)) as jumlah_transaksi, SUM(d.total_harga) as total_harga FROM (SELECT a.*,b.id_barang,b.jumlah,c.harga,b.jumlah*c.harga as total_harga FROM transaksis a
LEFT JOIN detailtransaksis b ON a.id = b.id_transaksi
LEFT JOIN barangs c ON b.id_barang = c.id where DATE(a.created_at) = DATE(NOW()) ) d GROUP BY d.id_cabang,DATE_FORMAT(d.created_at,'%Y-%m-%d')) b ON a.id = b.id_cabang;
        ");
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Cabang diterima',
            'data' => $cabang
        ],200);
    }
    public function data_cabang_home2(Request $request){
        $validated = Validator::make($request->all(), [
            'date' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => 'Something Went Wrong'
            ], 200);
        }
        $cabang = DB::select("
        SELECT a.*,b.tanggal,b.jumlah_transaksi,b.total_harga FROM cabang a LEFT JOIN (SELECT d.id_cabang,DATE_FORMAT(d.created_at,'%Y-%m-%d') as tanggal,COUNT(DISTINCT(d.nama_transaksi)) as jumlah_transaksi, SUM(d.total_harga) as total_harga FROM (SELECT a.*,b.id_barang,b.jumlah,c.harga,b.jumlah*c.harga as total_harga FROM transaksis a
LEFT JOIN detailtransaksis b ON a.id = b.id_transaksi
LEFT JOIN barangs c ON b.id_barang = c.id where DATE(a.created_at) = DATE(STR_TO_DATE('".Carbon::parse($request->date)."', '%Y-%m-%dT%H:%i:%s.%fZ')) ) d GROUP BY d.id_cabang,DATE_FORMAT(d.created_at,'%Y-%m-%d')) b ON a.id = b.id_cabang;
        ");
        return response()->json([
            'status' => 'Success',
            'message' => 'Data Cabang diterima',
            'data' => $cabang
        ],200);
    }

    public function edit_cabang(Request $request){
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
            'nama_cabang' => 'required',
            'saldo' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => 'Pastikan Field Input Terisi'
            ], 200);
        }

        $cabang = Cabang::find($request->id_cabang);
        $cabang->nama_cabang = $request->nama_cabang;
        $cabang->saldo = $request->saldo;
        $cabang->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Cabang '.$request->nama_cabang.' Berhasil Diedit',
        ],200);
    }

    public function delete_cabang(Request $request){
        $validated = Validator::make($request->all(), [
            'id_cabang' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => 'Terdapat Error'
            ], 200);
        }
        //delete stok cabang
        DB::table('stok_barang')->where('id_cabang',$request->id_cabang)->delete();
        //delete transaksi cabang
        Transaksi::where('id_cabang',$request->id_cabang)->delete();
        //remove role cabang user
        $get_user_role = DB::table('user_to_cabang')->where('id_cabang',$request->id_cabang)->get();
        foreach($get_user_role as $x)
        {
            User::where('id', $x->id_user)->update(['role' => null]);
        }
        //remove keranjang
        Keranjang::where('id_cabang',$request->id_cabang)->delete();
        //remove user to cabang
        DB::table('user_to_cabang')->where('id_cabang',$request->id_cabang)->delete();
        //remove cabang
        $cabang = Cabang::find($request->id_cabang);
        $cabang->delete();
        return response()->json([
            'status' => 'Success',
            'message' => 'Cabang '.$request->nama_cabang.' Berhasil Dihapus',
        ],200);
    }

    public function data_user(){
        $user = User::leftJoin('user_to_cabang','users.id','=','user_to_cabang.id_user')
        ->leftJoin('cabang','user_to_cabang.id_cabang','=','cabang.id')
        ->select('users.*','user_to_cabang.id_cabang','cabang.nama_cabang')->get();
        return response()->json([
            'status' => 'Success',
            'message' => 'Data User diterima',
            'data' => $user
        ],200);
    }

    public function edit_user(Request $request){
        $validated = Validator::make($request->all(), [
            'id' => 'required',
            'role' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }
        $user = User::find($request->id);
        $user->role = $request->role;
        if($request->cabang != null)
        {
            //cek apabila user sudah termap ke cabang
            $cek_user = DB::table('user_to_cabang')->where('id_user',$request->id)->count();
            if($cek_user == 0)
            {
                DB::table('user_to_cabang')->insert([
                    'id_user'=>$request->id,
                    'id_cabang'=>$request->cabang
                ]);
            }
            else{
                $delete = DB::table('user_to_cabang')->where('id_user',$request->id)->delete();
                if($delete)
                {
                    DB::table('user_to_cabang')->insert([
                        'id_user'=>$request->id,
                        'id_cabang'=>$request->cabang
                    ]);
                }
                
            }
        }
        $user->save();
        return response()->json([
            'status' => 'Success',
            'message' => 'Informasi User '.$user->name.' Berhasil Disimpan',
        ],200);
    }

    public function delete_user(Request $request){
        $validated = Validator::make($request->all(), [
            'id_user' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }
        $user = User::find($request->id_user);
        //remove token user
        $user->tokens()->delete();

        //remove user to cabang if ada
        $cek_user_cabang = DB::table('user_to_cabang')->where('id_user',$request->id_user)->count();
        if($cek_user_cabang != 0)
        {
            DB::table('user_to_cabang')->where('id_user',$request->id_user)->delete();
        }

        $user->delete();
        return response()->json([
            'status' => 'Success',
            'message' => 'User '.$user->name.' Berhasil Dihapus',
        ],200);
    }
    public function reset_password(Request $request){
        $validated = Validator::make($request->all(), [
            'id' => 'required',
            'password' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => $validated->errors()
            ], 200);
        }
        $user = User::find($request->id);
        //reset password user
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'User '.$user->name.' Berhasil Dilakukan Reset Password',
        ],200);
    }

    public function reset_password_profile(Request $request){
        $validated = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => 'Terdapat Error'
            ], 200);
        }
        $user_cek = User::where('email',$request->username.'@berkah.com')->first();
        $user = User::find($user_cek->id);

        //reset password user
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => 'Success',
            'message' => 'Password Anda Berhasil Direset',
        ],200);
    }

    public function get_user_cabang(Request $request){
        $validated = Validator::make($request->all(), [
            'username' => 'required',
        ]);

        if($validated->fails())
        {
            return response()->json([
                'status' => 'Error',
                'message' => 'Terdapat Error'
            ], 200);
        }
        $user_cek = User::where('email',$request->username.'@berkah.com')->first();
        $cek_hub_cabang = DB::table('user_to_cabang')->where('id_user',$user_cek->id)->first();
        $cek_cabang = Cabang::where('id',$cek_hub_cabang->id_cabang)->first();
        //reset password user
        return response()->json([
            'status' => 'Success',
            'message' => 'Data User Cabang Diterima',
            'data' => [$cek_cabang->nama_cabang,$cek_hub_cabang->id_cabang]

        ],200);
    }
}
