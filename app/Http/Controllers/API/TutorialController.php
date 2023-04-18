<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use Illuminate\Http\Request;

class TutorialController extends Controller
{
    //
    public function index(){
        try {
            $tutorials = Tutorial::select('id','title','description','videoLink')->with('media')->get();
            return response()->json([
                'status' => true,
                'data' => ['tutorials' => $tutorials]
                ], 200);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
