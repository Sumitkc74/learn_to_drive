<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class UserHistoryController extends Controller
{
    //
    public function recordHistory(Request $request){
        //validate
        $rules = [
            'user_id' => 'required',
            'attempted_questions' => 'required',
            'optionA' => 'required',
            'optionB'  => 'required',
            'optionC' => 'required',
            'optionD' => 'required',
            'correct_options' => 'required',
            'selected_options' => 'required',

        ];

        $validator = Validator::make($request->all(), $rules);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        //create new user in table
        UserHistory::create([
            'user_id' => $request->user_id,
            'attempted_questions' => $request->attempted_questions,
            'optionA' => $request->optionA,
            'optionB'  => $request->optionB,
            'optionC' => $request->optionC,
            'optionD' => $request->optionD,
            'correct_options' => $request->correct_options,
            'selected_options' => $request->selected_options,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User History Recorded Successfully',
        ]);
    }

    public function displayHistory(){
        try {
            $userHistories = UserHistory::all()->map(function($userHistory) {
                return [
                    'id' => $userHistory->id,
                    'user_id' => $userHistory->user_id,
                    'attempted_questions' => $userHistory->attempted_questions,
                    'optionA' => $userHistory->optionA,
                    'optionB' => $userHistory->optionB,
                    'optionC' => $userHistory->optionC,
                    'optionD' => $userHistory->optionD,
                    'correct_options' => $userHistory->correct_options,
                    'selected_options' => $userHistory->selected_options,
                    'updated_at' => Carbon::parse(
                        $userHistory->updated_at
                    )->toDateTimeString()
                ];
            });

            return response()->json([
                'status' => true,
                'data' => ['userHistories' => $userHistories]
            ], 200);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
