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
            $questions = Question::where('status', 'Published')->get()->map(fn ($q) => $q->only(['id','question','option1','option2','option3','option4','category','difficulty','language','image_url']));
            return response()->json([
                'status' => true,
                'data' => ['questions' => $questions]
                ], 200);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
