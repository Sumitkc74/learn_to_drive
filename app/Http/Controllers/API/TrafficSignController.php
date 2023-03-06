<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TrafficSign;
use Illuminate\Http\Request;

class TrafficSignController extends Controller
{
    //
    public function index(){
        return TrafficSign::all();
    }
}
