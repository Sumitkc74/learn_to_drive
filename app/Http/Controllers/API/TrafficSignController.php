<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Api\BaseController as BaseController;
use App\Models\TrafficSign;
use Illuminate\Http\Request;

class TrafficSignController extends BaseController
{
    //
    public function index(){
        try {
            $trafficSigns = TrafficSign::select('id','name','description')->with('media')->get();
            return response()->json(
                [ 'status' => true,
                    'data'=>   ['categories' => $trafficSigns]],200);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}


// public function allCategory(Request $request)
// {
//     try {
//         $categories = Category::all();
//         return response()->json([
//             'status' => true,
//             'data' => ['categories' => $categories]
//         ], 201);
//     } catch (\Exception $e) {
//         return $this->sendError($e->getMessage());
//     }
// }
