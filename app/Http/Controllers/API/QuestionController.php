<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    //
    public function index(){
        try {
            $questions = Question::where('status', 'Published')->get();
            return response()->json([
                'status' => true,
                'data' => ['questions' => $questions]
                ], 200);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
