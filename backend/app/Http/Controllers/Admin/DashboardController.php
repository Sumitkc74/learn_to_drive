<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\PdfExtractionRun;
use App\Models\PdfQuestionImport;
use App\Models\Question;
use App\Models\User;
use App\Models\UserHistory;
use App\Services\AdminSystemHealth;
use App\Services\ContentReadiness;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', $this->dashboardData() + [
            'contentReadiness' => app(ContentReadiness::class)->summary(),
            'systemHealth' => app(AdminSystemHealth::class)->snapshot(),
        ]);
    }

    public function analytics()
    {
        return view('admin.analytics', $this->dashboardData());
    }

    private function dashboardData(): array
    {
        $users = ['total' => User::count(), 'premium' => User::where('role', 'PremiumUser')->count(), 'verified' => User::whereNotNull('email_verified_at')->whereNotNull('phone_verified_at')->count(), 'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count()];
        $questions = ['total' => Question::count(), 'published' => Question::where('status', 'Published')->count(), 'draft' => Question::where('status', 'Draft')->count(), 'archived' => Question::where('status', 'Archived')->count()];
        $passingScore = AppSetting::read('exam_passing_score');
        $attemptCount = 0;
        $scoreTotal = 0;
        $passed = 0;
        $learners = [];
        $misses = [];
        foreach (UserHistory::select(['id', 'user_id', 'attempted_questions', 'correct_options', 'selected_options'])->cursor() as $attempt) {
            $questionIds = $this->decode($attempt->attempted_questions);
            $correct = $this->decode($attempt->correct_options);
            $selected = $this->decode($attempt->selected_options);
            $score = $attempt->score_percentage;
            $attemptCount++;
            $scoreTotal += $score;
            $passed += (int) ($score >= $passingScore);
            $learners[$attempt->user_id] = true;
            foreach ($questionIds as $index => $id) {
                if (isset($correct[$index],$selected[$index]) && $correct[$index] !== $selected[$index] && is_numeric($id)) {
                    $misses[(int) $id] = ($misses[(int) $id] ?? 0) + 1;
                }
            }
        }
        $performance = ['attempts' => $attemptCount, 'average' => $attemptCount ? (int) round($scoreTotal / $attemptCount) : 0, 'pass_rate' => $attemptCount ? (int) round($passed / $attemptCount * 100) : 0, 'active_learners' => count($learners)];
        $months = collect(range(5, 0))->map(fn ($offset) => Carbon::now()->startOfMonth()->subMonths($offset));
        $signupLabels = $months->map(fn ($month) => $month->format('M Y'));
        $signupCounts = $months->map(fn ($month) => User::where('created_at', '>=', $month)->where('created_at', '<', $month->copy()->addMonth())->count());
        $recentActivity = AuditLog::with('actor')->latest()->take(8)->get();
        $mostMissed = $this->mostMissedQuestions($misses);
        $reviewWorkload = PdfQuestionImport::where('status', 'Pending')->selectRaw('COUNT(*) AS pending, COALESCE(SUM(needs_answer),0) AS answers, COALESCE(SUM(needs_diagram),0) AS diagrams, COALESCE(SUM(is_ocr),0) AS ocr')->first();
        $reviewBanks = PdfQuestionImport::where('status', 'Pending')->select('learning_content_import_id')->selectRaw('COUNT(*) AS pending')->groupBy('learning_content_import_id')->orderByDesc('pending')->limit(6)->with('resource:id,title')->get();
        $extractionIssues = PdfExtractionRun::whereIn('status', ['Queued', 'Running', 'Failed'])->with('resource:id,title')->latest('id')->limit(5)->get();

        return compact('users', 'questions', 'performance', 'signupLabels', 'signupCounts', 'recentActivity', 'mostMissed', 'reviewWorkload', 'reviewBanks', 'extractionIssues');
    }

    private function mostMissedQuestions(array $misses)
    {
        arsort($misses);
        $top = array_slice($misses, 0, 5, true);
        $questions = Question::whereIn('id', array_keys($top))->get()->keyBy('id');

        return collect($top)->map(fn ($count, $id) => ['question' => $questions->get($id), 'misses' => $count])->filter(fn ($item) => $item['question'] !== null)->values();
    }

    private function decode(?string $value): array
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }
}
