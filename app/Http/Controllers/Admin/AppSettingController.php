<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    private const SECTION_RULES = [
        'question-bank' => [
            'exam_duration_minutes' => ['required', 'integer', 'between:5,180'],
            'exam_passing_score' => ['required', 'integer', 'between:1,100'],
            'exam_question_count' => ['required', 'integer', 'between:5,100'],
        ],
        'verification' => ['otp_expiry_minutes' => ['required', 'integer', 'between:2,30']],
        'users' => ['access_token_expiry_days' => ['required', 'integer', 'between:1,365']],
        'uploads' => [
            'image_upload_limit_mb' => ['required', 'integer', 'between:1,10'],
            'document_upload_limit_mb' => ['required', 'integer', 'between:1,50'],
        ],
        'general' => ['maintenance_notice' => ['nullable', 'string', 'max:500']],
    ];

    public function index()
    {
        $settings = AppSetting::values();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request, string $section)
    {
        abort_unless(isset(self::SECTION_RULES[$section]), 404);
        $settings = $request->validate(self::SECTION_RULES[$section]);

        foreach ($settings as $key => $value) {
            AppSetting::updateOrCreate(['key' => $key], ['value' => (string) ($value ?? '')]);
        }

        return back()->with('success', 'The '.str_replace('-', ' ', $section).' settings were updated successfully.');
    }
}
