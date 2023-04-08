<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Payment;

class PaymentController extends Controller
{
    //
    public function payment(Request $request){
        //validate
        $rules = [
            'user_id' => 'required',
            'token' => 'required',
            'amount' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        $user = User::find($request->user_id);
        if ($user->role != 'User') {
            return response()->json(['status' => false, 'message' => 'User is already using premium'], 500);
        }

        //create new payment in table
        $payment = Payment::create([
            'user_id' => $request->user_id,
            'transaction_id' => $request->token,
            'amount' => $request->amount,
        ]);

        // $user = User::where('id', $request->user_id)->update(['role' => 'PremiumUser']);

        $user->update(['role' => 'PremiumUser']);

        $user = $user->only([
            'id',
            'name',
            'email',
            'phoneNumber',
            'role',
        ]);

        if ($payment->save()) {
            return response()->json([
                'status' => true,
                'data' => ['user' => $user],
                'message' => 'Payment saved successfully',
            ], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Error occured! Please try again'], 500);
        }
    }




    // public function verifyPayment(Request $request)
    // {
    //     $token = $request->token;
    //     $amount = $request->amount;

    //     $args = http_build_query(array(
    //         'token' => $token,
    //         'amount'  => $amount
    //     ));

    //     $url = "https://khalti.com/api/v2/payment/verify/";

    //     # Make the call using API.
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_POST, 1);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //     $headers = ['Authorization: Key test_secret_key_37e47644e0be4e2590ca7134949f7305'];
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    //     // Response
    //     $response = curl_exec($ch);
    //     $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //     curl_close($ch);

    //     return $response;
    // }
}
