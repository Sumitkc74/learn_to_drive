<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    private $token;

    public function register(Request $req){
        //validate
        $rules = [
            'name' => 'required|string',
            'email' => 'required|string|unique:users',
            'phoneNumber' => 'required|string|min:10|max:10',
            'password' => 'required|string|min:6',
        ];

        $validator = Validator::make($req->all(), $rules);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        //create new user in table
        $user = User::create([
            'name' => $req->name,
            'email' => $req->email,
            'phoneNumber' => $req->phoneNumber,
            'password' =>Hash::make($req->password),
            'role' => 'User'
        ]);

        $this->token = $user->createToken('Personal Access Token')->plainTextToken;
        $response = ['user' => $user, 'token' => $this->token];
        return response()->json($response, 200);
    }

    public function login(Request $req) {
        //validate
        $rules = [
            'email' => 'required',
            'password' => 'required|string'
        ];
        $req->validate($rules);
        //find email from users table
        $user = User::where('email', $req->email)->first();

        if($user){
            if (Hash::check($req->password, $user->password)) {
                $this->token = $user->createToken('Personal Access Token')->plainTextToken;
                $response = ['user' => $user, 'token' => $this->token, 'message' => 'User Login Successful'];
                return response()->json($response, 200);
            }else{
                return response()->json(['message' => 'Incorrect password'], 400);
            }
        }
        return response()->json(['message' => 'Incorrect email'], 400);
    }

    public function changePassword(Request $req) {
        //validate
        $rules = [
            'email' => 'required',
            'password' => 'required|string',
            'new_password' => 'required|string'
        ];

        $req->validate($rules);

        //find password from users table
        $user = User::where('email', $req->email)->first();

        if($user && Hash::check($req->password, $user->password)) {
            $user->password = Hash::make($req->new_password);

            if ($user->save()) {
                $user->updated_at = Carbon::now()->format('Y-m-d H:i:s');
                return response()->json(['message' => 'Password updated successfully'], 200);
            } else {
                return response()->json(['message' => 'Error occured! Please try again'], 500);
            }
        }
        return response()->json(['message' => 'Incorrect Password'], 500);
    }

    // public function resetPassword(Request $req)
    // {
    //     $rules = [
    //         'email' => 'required|string',
    //     ];
    //     $req->validate($rules);

        //find email from users table
        //     $user = User::where('email', $req->email)->where('verification_code',
        //     $req ->verification_code)->first();

        //     if(!$user) {
        //         return response()->json(['message' => 'Invalid code'], 400);
        //     }

        //     $user->password = Hash::make($req->new_password);
        //     $user->verification_code = NULL;

        //     if($user->save()) {
        //         return response()->json(['message' => 'Password updated successfully'], 200);
        //     }
        //     else{
        //         return response()->json(['message' => 'Error occured! Please try again'], 500);
        //     }

        //     return response()->json(['message' => 'No user found'], 400);




        // $user = User::where('email', $req->email)->first();

        // if($user){
        //     $token = Str::random(40);

        // }

        // PasswordReset::updateOrCreate(
        //     ['email' => $req -> email],
        //     [
        //         'email' => $req -> email,
        //         'token' => $token,
        //     ],
        // );

    // }

    public function logout()
    {
        // $userToken = PersonalAccessToken::where('token',$this->token)->first();
        // $userToken->revoke();
        // return response()->json(['message' => 'Logged out successfully!'], 200);

        // $user = Auth::user();
        // DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->delete();

        Session::flush();
        Auth::logout();
        return response()->json(['message' => 'User logged out'], 200);

    }
}
