<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function index(){
        try {
            $users = User::all();
            return response()->json([
                'status' => true,
                'data' => ['users' => $users]
                ], 201);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }
}
