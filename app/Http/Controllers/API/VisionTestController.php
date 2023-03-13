<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\VisionTest;

class VisionTestController extends BaseController
{
    //
    public function index(){
        try {
            $visionTests = VisionTest::all();
            return response()->json([
                'status' => true,
                'data' => ['visionTests' => $visionTests]
                ], 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
