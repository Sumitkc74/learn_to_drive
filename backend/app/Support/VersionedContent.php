<?php
namespace App\Support;
class VersionedContent {
    public const TYPES = [
        'questions'=>[\App\Models\Question::class,'editQuestion'],
        'notices'=>[\App\Models\Notice::class,'editNotice'],
        'banks'=>[\App\Models\ExamPaper::class,'editExamPaper'],
        'information'=>[\App\Models\ExamInformation::class,'editExamInformation'],
        'signs'=>[\App\Models\TrafficSign::class,'editTrafficSign'],
        'vision'=>[\App\Models\VisionTest::class,'editVisionTest'],
        'tutorials'=>[\App\Models\Tutorial::class,'editTutorial'],
    ];
    public static function type($record): ?string {
        foreach(self::TYPES as $key=>[$class]) if($record instanceof $class) return $key;
        return null;
    }
    public static function fields($record): array {
        return array_values(array_diff($record->getFillable(),['image','englishFile','nepaliFile','government_notice_import_id']));
    }
    public static function hash($record): string { return hash('sha256',json_encode($record->getAttributes())); }
}
