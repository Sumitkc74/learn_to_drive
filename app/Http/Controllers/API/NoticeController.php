<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NoticeController extends Controller
{
    //
    // public function index(){
    //     try {
    //         $notices = Notice::all();

    //         $notices = [
    //             'id' => $notices->id,
    //             'title' => $notices->title,
    //             'updated_at' => Carbon::parse(
    //                 $notices->updated_at
    //             )->toDateTimeString()
    //         ];
    //         return response()->json([
    //             'status' => true,
    //             'data' => ['notices' => $notices]
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return $this->sendError($e->getMessage());
    //     }
    // }

    public function index(){
        try {
            $notices = Notice::all()->map(function($notice) {
                return [
                    'id' => $notice->id,
                    'title' => $notice->title,
                    'description' => $notice->description,
                    'nepaliTitle' => $notice->nepaliTitle,
                    'nepaliDescription' => $notice->nepaliDescription,
                    'link' => $notice->link,
                    'updated_at' => Carbon::parse(
                        $notice->updated_at
                    )->toDateTimeString()
                ];
            });

            return response()->json([
                'status' => true,
                'data' => ['notices' => $notices]
            ], 200);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage());
        }
    }

}
