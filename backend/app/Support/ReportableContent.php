<?php
namespace App\Support;
class ReportableContent {
    public const TYPES=[
        'question'=>[\App\Models\Question::class,'editQuestion','question'],
        'traffic-sign'=>[\App\Models\TrafficSign::class,'editTrafficSign','name'],
        'vision-test'=>[\App\Models\VisionTest::class,'editVisionTest','testNumber'],
        'question-bank'=>[\App\Models\ExamPaper::class,'editExamPaper','name'],
        'exam-information'=>[\App\Models\ExamInformation::class,'editExamInformation','name'],
        'notice'=>[\App\Models\Notice::class,'editNotice','title'],
        'tutorial'=>[\App\Models\Tutorial::class,'editTutorial','title'],
    ];
    public static function visible(string $type,int $id) {
        if(!isset(self::TYPES[$type])) return null;
        $query=self::TYPES[$type][0]::query();
        if($type==='question') $query->where('status','Published');
        if($type==='notice') $query->visibleToLearners();
        return $query->find($id);
    }
}
