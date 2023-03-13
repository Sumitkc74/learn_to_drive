<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\ExamPaper;

class ExamPaperController extends BaseController
{
    //
    public function index(){
        try {
            $examPapers = ExamPaper::all();
            return response()->json([
                'status' => true,
                'data' => ['examPapers' => $examPapers]
                ], 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
