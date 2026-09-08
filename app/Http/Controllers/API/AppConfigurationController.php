<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;

class AppConfigurationController extends Controller
{
    public function index()
    {
        $settings = AppSetting::values();

        return response()->json(['status' => true, 'data' => [
            'exam_duration_minutes' => $settings['exam_duration_minutes'],
            'exam_passing_score' => $settings['exam_passing_score'],
            'exam_question_count' => $settings['exam_question_count'],
            'maintenance_notice' => $settings['maintenance_notice'],
        ]]);
    }
}
