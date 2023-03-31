<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\ExamInformation;

class ExamInformationController extends BaseController
{
    //
    public function index(){
        try {
            $examInformation = ExamInformation::select('id','name','description')->with('media')->get();;
            return response()->json([
                'status' => true,
                'data' => ['examInformations' => $examInformation]
                ], 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
