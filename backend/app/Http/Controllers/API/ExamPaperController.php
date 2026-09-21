<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\ExamPaper;

class ExamPaperController extends BaseController
{
    //
    public function index(){
        try {
            $examPapers = ExamPaper::select('id','name','description','language')->with('media')->get();
            $examPapers->each(function ($paper) {
                $paper->setAttribute('pdf_url', $paper->pdfMedia()?->getUrl());
            });
            return response()->json([
                'status' => true,
                'data' => ['examPapers' => $examPapers]
                ], 200);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
