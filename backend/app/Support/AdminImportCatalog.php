<?php

namespace App\Support;

use App\Models\{User, Tutorial, TrafficSign, VisionTest, ExamPaper, ExamInformation, DrivingSchool, SchoolMembership, AppSetting};
use App\Rules\DocumentLanguage;

class AdminImportCatalog
{
    public const TYPES = [
        'users' => ['Users', User::class, ['name', 'email', 'phoneNumber', 'role'], 'email'],
        'tutorials' => ['Tutorials', Tutorial::class, ['title', 'description', 'videoLink', 'attachment'], 'title'],
        'traffic-signs' => ['Traffic signs', TrafficSign::class, ['name', 'nepaliSignName', 'description', 'attachment'], 'name'],
        'vision-tests' => ['Vision tests', VisionTest::class, ['testNumber', 'attachment'], 'testNumber'],
        'question-banks' => ['Question banks', ExamPaper::class, ['name', 'description', 'language', 'attachment'], 'name'],
        'exam-information' => ['Exam information', ExamInformation::class, ['name', 'description', 'language', 'attachment'], 'name'],
        'schools' => ['Driving schools', DrivingSchool::class, ['name', 'address', 'phone', 'email', 'owner_email', 'latitude', 'longitude'], 'name'],
        'school-memberships' => ['School memberships', SchoolMembership::class, ['school_id', 'email', 'role'], null],
    ];

    public static function type(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        return self::TYPES[$type];
    }

    public static function rules(string $type, array $row): array
    {
        $name = ['required', 'string', 'max:255'];
        $description = ['required', 'string', 'max:1000'];
        return match ($type) {
            'users' => ['name' => $name, 'email' => ['required', 'email', 'max:255'], 'phoneNumber' => ['required', 'digits:10'], 'role' => ['required', 'in:User,PremiumUser']],
            'tutorials' => ['title' => $name, 'description' => $description, 'videoLink' => ['required', 'url:http,https', 'max:2048']],
            'traffic-signs' => ['name' => $name, 'nepaliSignName' => $name, 'description' => $description],
            'vision-tests' => ['testNumber' => ['required', 'integer', 'min:1']],
            'question-banks', 'exam-information' => ['name' => [...$name, new DocumentLanguage($row['language'] ?? '')],
                'description' => [...$description, new DocumentLanguage($row['language'] ?? '')], 'language' => ['required', 'in:English,Nepali']],
            'schools' => ['name' => ['required', 'string', 'max:160'], 'address' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'], 'owner_email' => ['required', 'email', 'max:255'],
                'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180']],
            'school-memberships' => ['school_id' => ['required', 'integer', 'min:1'], 'email' => ['required', 'email', 'max:255'], 'role' => ['required', 'in:Manager,Instructor,Student']],
        };
    }

    public static function mediaRules(string $type): array
    {
        return in_array($type, ['question-banks', 'exam-information'], true)
            ? ['required', 'file', 'mimes:pdf', 'max:'.AppSetting::documentLimitKb()]
            : ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.AppSetting::imageLimitKb()];
    }
}
