<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends BaseController
{
    public function register(Request $request){
        //validate
        $rules = [
            'name' => 'required|string',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phoneNumber' => 'required|digits:10',
            'password' => ['required', 'confirmed', Password::min(8)],
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        //create new user in table
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phoneNumber' => $request->phoneNumber,
            'password' =>Hash::make($request->password),
            'role' => 'User',
            'profileImage' => 'dist/img/avatar.png',
        ]);


        // $user->addMedia('/public/dist/image.jpg')->toMediaCollection('avatar');

        $expiresAt = Carbon::now()->addMonths(3);
        $token = $user->createToken('Personal Access Token', ['*'], $expiresAt);
        $text= $token->plainTextToken;

        $user = $user->only([
            'id',
            'name',
            'email',
            'phoneNumber',
            'role',
            'profileImage',
        ]);
        $token = [
            'access_token' => $text,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $expiresAt
            )->toDateTimeString()
        ];
        return response()->json([
            'status' => true,
            'message' => 'User Registered Successfully.',
            'data' => ['user' => $user, 'token' => $token,]
        ]);
    }

    public function login(Request $request) {
        //validate
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string'
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $response['message'] = $validator->messages()->first();
            $response['status'] = false;
            return $response;
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'The provided credentials are incorrect.'], 401);
        }

        $expiresAt = Carbon::now()->addMonths(3);
        $token = $user->createToken('Personal Access Token', ['*'], $expiresAt);
        $text= $token->plainTextToken;

        // $response = ['user' => $user, 'token' => $token, 'message' => 'User Login Successful'];
        // return response()->json($response, 200);

        // $user = $request->user();

        // $tokenResult = $user->createToken('Personal Access Token');
        // $token = $tokenResult->token->plainTextToken;
        // if ($request->remember_me)
        // $token->save();
        $user = $user->only([
            'id',
            'name',
            'email',
            'phoneNumber',
            'role',
            'profileImage',
        ]);
        $token = [
            'access_token' => $text,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $expiresAt
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
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'The current password is incorrect.',
            ], 422);
        }

        $user->password = Hash::make($request->new_password);

        if ($user->save()) {
            $user->updated_at = Carbon::now()->format('Y-m-d H:i:s');
            return response()->json([
                'status' => true,
                'message' => 'Password updated successfully',
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
            'phoneNumber' => 'required|numeric',
            'profileImage' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
        try {
            $user->Update($request->only('name', 'email', 'phoneNumber'));

            if ($request->hasFile('profileImage') && $request->profileImage != '') {
                $user->clearMediaCollection();
                $user->addMedia($request->profileImage)->toMediaCollection();
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
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out'
        ], 200);
    }
}
