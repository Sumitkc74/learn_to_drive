<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    public function register(Request $request){
        //validate
        $rules = [
            'name' => 'required|string',
            'email' => 'required|string|unique:users',
            'phoneNumber' => 'required|string|min:10|max:10',
            'password' => 'required|string|min:6',
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            // $response['message'] = $validator->messages()->first();
            // $response['status'] = false;
            // return $response;
            return response()->json($validator->errors(), 400);
        }

        //create new user in table
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phoneNumber' => $request->phoneNumber,
            'password' =>Hash::make($request->password),
            'email_verified_at' => Carbon::now(),
            'role' => 'User'
        ]);

        $token = $user->createToken('Personal Access Token');
        $text= $token->plainTextToken;
        $token->expires_at = Carbon::now()->addMonths(3);

        $user = $user->only([
            'id',
            'name',
            'email',
            'phoneNumber',
            'role',
        ]);
        $token = [
            'access_token' => $text,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $token->expires_at
            )->toDateTimeString()
        ];
        return response()->json([
            'status' => true,
            'message' => 'User Registered Successfully.',
            'data' => ['user' => $user, 'token' => $token,]
        ]);

        // $this->token = $user->createToken('Personal Access Token')->plainTextToken;
        // $response = ['user' => $user, 'token' => $this->token];
        // return response()->json($response, 200);
    }

    public function login(Request $request) {
        //validate
        $rules = [
            'email' => 'required',
            'password' => 'required|string'
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response['message'] = $validator->messages()->first();
            $response['status'] = false;
            return $response;
        }

        //find email from users table
        $user = User::where('email', $request->email)->first();

        if(!$user){
            return response()->json(['message' => 'Incorrect email'], 400);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect password'], 400);
        }

        $token = $user->createToken('Personal Access Token');
        $text= $token->plainTextToken;

        // $response = ['user' => $user, 'token' => $token, 'message' => 'User Login Successful'];
        // return response()->json($response, 200);

        // $user = $request->user();

        // $tokenResult = $user->createToken('Personal Access Token');
        // $token = $tokenResult->token->plainTextToken;
        // if ($request->remember_me)
        $token->expires_at = Carbon::now()->addMonths(3);
        // $token->save();
        $user = $user->only([
            'id',
            'name',
            'email',
            'phoneNumber',
            'role',
        ]);
        $token = [
            'access_token' => $text,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $token->expires_at
            )->toDateTimeString()
        ];

        return response()->json([
            'status' => true,
            'user' => $user,
            'token' => $token,
            'message' => 'User Login Successful',
        ], 200);
    }

    public function changePassword(Request $request) {
        //validate
        $rules = [
            'email' => 'required',
            'password' => 'required|string',
            'new_password' => 'required|string'
        ];
        $request->validate($rules);

        //find password from users table
        $user = User::where('email', $request->email)->first();

        if(!$user && Hash::check($request->password, $user->password)) {
            return response()->json(['status' => false, 'message' => 'Incorrect Current Password'], 500);
        }

        $user->password = Hash::make($request->new_password);

        if ($user->save()) {
            $user->updated_at = Carbon::now()->format('Y-m-d H:i:s');
            return response()->json([
                'status' => true,
                'message' => 'Password updated successfully',
                'data' => $user
            ], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Error occured! Please try again'], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $rules = [
            'email' => 'required',
            'name' => 'required|string',
            'phoneNumber' => 'required|numeric'
        ];
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
        try {
            $user->Update($request->only('name', 'email', 'phoneNumber'));

            if ($request->hasFile('profile_image') && $request->profile_image != '') {
                $user->clearMediaCollection();
                $user->addMedia($request->image)->toMediaCollection();
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Could not update the profile'], 500);
        }

        return response()->json(['user' => $user, 'message' => 'Profile updated successfully'], 200);
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

    public function logout(Request $request)
    {
        User::find($request->user()->id)->token()->revoke();
        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out'
        ], 200);
    }
}
