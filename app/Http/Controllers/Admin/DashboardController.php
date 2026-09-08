<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\GovernmentNoticeImport;
use App\Models\Question;
use App\Models\User;
use App\Models\UserHistory;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $users = ['total' => User::count(), 'premium' => User::where('role', 'PremiumUser')->count(), 'verified' => User::whereNotNull('email_verified_at')->whereNotNull('phone_verified_at')->count(), 'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count()];
        $questions = ['total' => Question::count(), 'published' => Question::where('status', 'Published')->count(), 'draft' => Question::where('status', 'Draft')->count(), 'archived' => Question::where('status', 'Archived')->count()];
        $attempts = UserHistory::latest()->get();
        $performance = ['attempts' => $attempts->count(), 'average' => $attempts->count() ? (int) round($attempts->avg('score_percentage')) : 0, 'pass_rate' => $attempts->count() ? (int) round(($attempts->filter(fn ($attempt) => $attempt->score_percentage >= 60)->count() / $attempts->count()) * 100) : 0, 'active_learners' => $attempts->pluck('user_id')->unique()->count()];
        $months = collect(range(5, 0))->map(fn ($offset) => Carbon::now()->subMonths($offset));
        $signupLabels = $months->map(fn ($month) => $month->format('M Y'));
        $signupCounts = $months->map(fn ($month) => User::whereYear('created_at', $month->year)->whereMonth('created_at', $month->month)->count());
        $recentActivity = AuditLog::with('actor')->latest()->take(8)->get();
        $pendingGovernmentNotices = GovernmentNoticeImport::where('status', 'Pending')->count();
        $mostMissed = $this->mostMissedQuestions($attempts);

        return view('admin.dashboard', compact('users', 'questions', 'performance', 'signupLabels', 'signupCounts', 'recentActivity', 'pendingGovernmentNotices', 'mostMissed'));
    }

    private function mostMissedQuestions($attempts)
    {
        $misses = [];
        foreach ($attempts as $attempt) {
            $questionIds = $this->decode($attempt->attempted_questions);
            $correct = $this->decode($attempt->correct_options);
            $selected = $this->decode($attempt->selected_options);
            foreach ($questionIds as $index => $questionId) {
                if (isset($correct[$index], $selected[$index]) && $correct[$index] !== $selected[$index] && is_numeric($questionId)) {
                    $misses[(int) $questionId] = ($misses[(int) $questionId] ?? 0) + 1;
                }
            }
        }
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
