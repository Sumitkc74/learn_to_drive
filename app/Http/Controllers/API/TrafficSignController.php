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
            $trafficSigns = TrafficSign::select('id','nepaliSignName','name','description')->with('media')->get();
            return response()->json(
                [ 'status' => true,
                    'data'=>   ['trafficSigns' => $trafficSigns]],200);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
