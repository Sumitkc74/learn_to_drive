<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\DrivingSchool;
use App\Models\GovernmentNoticeImport;
use App\Models\LearningContentImport;
use App\Models\LessonBooking;
use App\Models\Notice;
use App\Models\PdfExtractionRun;
use App\Models\PdfQuestionImport;
use App\Models\PremiumPayment;
use App\Models\Question;
use App\Models\SchoolLessonPayment;
use App\Models\User;
use App\Models\UserHistory;
use App\Services\AdminSystemHealth;
use App\Services\ContentReadiness;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', $this->dashboardData() + [
            'contentReadiness' => $this->safe(fn () => app(ContentReadiness::class)->summary()),
            'systemHealth' => $this->safe(fn () => app(AdminSystemHealth::class)->snapshot()),
            'importAlerts' => $this->importAlerts(),
        ]);
    }

    public function analytics()
    {
        return view('admin.analytics', $this->dashboardData());
    }

    /**
     * One failing panel must not take down the whole dashboard: every
     * group below degrades to null-filled values (rendered as "—") when
     * its query fails, and the failure is reported for investigation.
     * Live, the dashboard 500'd on every load after the 719d7fbc deploy;
     * a missing rates_declined_at column is the leading suspect (it is
     * the only dashboard-only query added by that deploy), but the exact
     * live cause is unproven — no live logs or DB access were available.
     */
    private function dashboardData(): array
    {
        $users = $this->safe(fn () => ['total' => User::count(), 'premium' => User::where('role', 'PremiumUser')->count(), 'verified' => User::whereNotNull('email_verified_at')->whereNotNull('phone_verified_at')->count(), 'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count()], ['total' => null, 'premium' => null, 'verified' => null, 'new_this_month' => null]);
        $questions = $this->safe(fn () => ['total' => Question::count(), 'published' => Question::where('status', 'Published')->count(), 'draft' => Question::where('status', 'Draft')->count(), 'archived' => Question::where('status', 'Archived')->count()], ['total' => null, 'published' => null, 'draft' => null, 'archived' => null]);
        $operations = $this->safe(fn () => ['schools' => DrivingSchool::count(), 'schools_pending' => DrivingSchool::where('status', 'Pending')->count(), 'lesson_bookings' => LessonBooking::count(), 'school_payments' => SchoolLessonPayment::count(), 'school_paid_paisa' => SchoolLessonPayment::where('status', 'Paid')->sum('amount_paisa'), 'premium_payments' => PremiumPayment::count()], ['schools' => null, 'schools_pending' => null, 'lesson_bookings' => null, 'school_payments' => null, 'school_paid_paisa' => null, 'premium_payments' => null]);
        $passingScore = AppSetting::read('exam_passing_score');
        // The attempt scan decodes every learner attempt in PHP, so cache
        // the aggregates for 5 minutes instead of recomputing them on
        // every dashboard and analytics load. Only the miss counts are
        // cached; the most-missed questions themselves are re-queried
        // fresh below, so an edited or deleted question never lingers.
        $cached = $this->safe(function () use ($passingScore) {
            return Cache::remember('admin_dashboard_performance', 300, function () use ($passingScore) {
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
                return [
                    'performance' => ['attempts' => $attemptCount, 'average' => $attemptCount ? (int) round($scoreTotal / $attemptCount) : 0, 'pass_rate' => $attemptCount ? (int) round($passed / $attemptCount * 100) : 0, 'active_learners' => count($learners)],
                    'misses' => $misses,
                ];
            });
        }, ['performance' => ['attempts' => null, 'average' => null, 'pass_rate' => null, 'active_learners' => null], 'misses' => []]);
        $performance = $cached['performance'];
        $months = collect(range(5, 0))->map(fn ($offset) => Carbon::now()->startOfMonth()->subMonths($offset));
        $signupLabels = $months->map(fn ($month) => $month->format('M Y'));
        $signupCounts = $this->safe(fn () => $months->map(fn ($month) => User::where('created_at', '>=', $month)->where('created_at', '<', $month->copy()->addMonth())->count()), collect([null, null, null, null, null, null]));
        $recentActivity = $this->safe(fn () => AuditLog::with('actor')->latest()->take(8)->get(), collect());
        $mostMissed = $this->safe(fn () => $this->mostMissedQuestions($cached['misses']), collect());
        $reviewWorkload = $this->safe(fn () => PdfQuestionImport::where('status', 'Pending')->selectRaw('COUNT(*) AS pending, COALESCE(SUM(CASE WHEN needs_answer THEN 1 ELSE 0 END),0) AS answers, COALESCE(SUM(CASE WHEN needs_diagram THEN 1 ELSE 0 END),0) AS diagrams, COALESCE(SUM(CASE WHEN is_ocr THEN 1 ELSE 0 END),0) AS ocr')->first(), (object) ['pending' => null, 'answers' => null, 'diagrams' => null, 'ocr' => null]);
        $reviewBanks = $this->safe(fn () => PdfQuestionImport::where('status', 'Pending')->select('learning_content_import_id')->selectRaw('COUNT(*) AS pending')->groupBy('learning_content_import_id')->orderByDesc('pending')->limit(6)->with('resource:id,title')->get(), collect());
        $extractionIssues = $this->safe(fn () => PdfExtractionRun::whereIn('status', ['Queued', 'Running', 'Failed'])->with('resource:id,title')->latest('id')->limit(5)->get(), collect());

        return compact('users', 'questions', 'operations', 'performance', 'signupLabels', 'signupCounts', 'recentActivity', 'mostMissed', 'reviewWorkload', 'reviewBanks', 'extractionIssues');
    }

    /**
     * The import-alert counts used to live as inline queries in the
     * blade, so one failing query (e.g. a missing column) 500'd the
     * whole dashboard. Each count is guarded on its own, so a failure
     * degrades just that count to null (rendered as "unavailable").
     */
    private function importAlerts(): array
    {
        return [
            'failed_sources' => $this->safe(fn () => DB::table('content_source_runs')->whereIn('id', DB::table('content_source_runs')->selectRaw('MAX(id)')->groupBy('source'))->where('status', 'Failed')->count()),
            'needs_review' => $this->safe(fn () => LearningContentImport::where('source_check_status', 'Needs review')->count()),
            'failed_ai' => $this->safe(fn () => LearningContentImport::where('ai_status', 'Failed')->count() + GovernmentNoticeImport::where('ai_status', 'Failed')->count()),
            'expiring' => $this->safe(fn () => Notice::visibleToLearners()->whereBetween('expires_at', [now(), now()->addDays(7)])->count()),
            'declined_rates' => $this->safe(fn () => DrivingSchool::whereNotNull('rates_declined_at')->count()),
        ];
    }

    /** Run a dashboard query, reporting the failure and returning the fallback when it throws. */
    private function safe(callable $callback, mixed $fallback = null): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            report($e);

            return $fallback;
        }
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
