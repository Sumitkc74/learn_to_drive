<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamInformation;
use App\Models\ExamPaper;
use App\Models\Notice;
use App\Models\Question;
use App\Models\TrafficSign;
use App\Models\Tutorial;
use App\Models\User;
use App\Models\VisionTest;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim($validated['q'] ?? '');
        $sections = [];
        if ($search !== '') {
            foreach ([
                ['Users', User::class, 'allUser', 'name', ['name', 'email', 'phoneNumber']],
                ['Questions', Question::class, 'allQuestion', 'question', ['question', 'option1', 'option2', 'option3', 'option4', 'category', 'explanation']],
                ['Notices', Notice::class, 'allNotice', 'title', ['title', 'nepaliTitle', 'description', 'nepaliDescription']],
                ['Traffic Signs', TrafficSign::class, 'allTrafficSign', 'name', ['name', 'nepaliSignName', 'description']],
                ['Vision Tests', VisionTest::class, 'allVisionTest', 'testNumber', ['testNumber']],
                ['Question Banks', ExamPaper::class, 'allExamPaper', 'name', ['name', 'description']],
                ['Exam Information', ExamInformation::class, 'allExamInformation', 'name', ['name', 'description']],
                ['Tutorials', Tutorial::class, 'allTutorial', 'title', ['title', 'description', 'videoLink']],
            ] as [$title, $model, $route, $label, $columns]) {
                $query = $model::query()->where(function ($builder) use ($columns, $search) {
                    foreach ($columns as $column) {
                        $builder->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
                $count = $query->count();
                $sectionMatches = str_contains(mb_strtolower($title), mb_strtolower($search));
                if ($count || $sectionMatches) {
                    $sections[] = [
                        'title' => $title,
                        'url' => route($route, $sectionMatches ? [] : ['search' => $search]),
                        'count' => $count,
                        'items' => $query->orderByDesc('id')->limit(5)->get(['id', $label])->map(fn ($record) => [
                            'label' => (string) $record->{$label},
                            'url' => route($route, ['search' => $search]),
                        ]),
                    ];
                }
            }
        }

        return view('admin.search', compact('search', 'sections'));
    }
}
