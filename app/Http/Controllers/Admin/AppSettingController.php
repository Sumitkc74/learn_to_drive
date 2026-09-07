<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    public function index()
    {
        $settings = AppSetting::values();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = $request->validate([
            'exam_duration_minutes' => ['required', 'integer', 'between:5,180'],
            'exam_passing_score' => ['required', 'integer', 'between:1,100'],
            'exam_question_count' => ['required', 'integer', 'between:5,100'],
            'otp_expiry_minutes' => ['required', 'integer', 'between:2,30'],
            'image_upload_limit_mb' => ['required', 'integer', 'between:1,10'],
            'document_upload_limit_mb' => ['required', 'integer', 'between:1,50'],
            'maintenance_notice' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($settings as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => (string) ($value ?? '')]);
        }

        return back()->with('success', 'Application settings updated successfully.');
    }
}
