<?php

namespace App\Services;

use App\Models\{User, Notice, Question, TrafficSign, Tutorial, ExamPaper, ExamInformation, VisionTest, LearningContentImport, GovernmentNoticeImport, SupportTicket, DrivingSchool, LessonBooking};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Fixed, read-only queries. The model never supplies SQL or a network target. */
class AdminDataContext
{
    public function resolve(Request $request): array
    {
        $message = $request->string('message')->toString();
        $operational = preg_match('/\b(users?|premium|registered|registration|signups?|content|reading|files?|pdf|links?|urls?|notices?|sources?|updated|schools?|bookings?|payments?|health|database|statistics|dashboard)\b|प्रयोगकर्ता|सामग्री|सूचना|विद्यालय/iu', $message);
        $support = preg_match('/\b(complaints?|tickets?|repl(?:y|ies)|support)\b|उजुरी/iu', $message);
        // Explicit ticket requests and the ticket page still use support context, but
        // switching to operational questions must not silently reuse an old ticket.
        if (!$operational || $support) {
            $context = app(AdminSupportContext::class)->resolve($request);
            if (isset($context['context']) || isset($context['reply'])) return $context;
        } else {
            session()->forget('admin_ai_ticket');
        }

        $start = now('Asia/Kathmandu')->startOfDay()->utc();
        $end = $start->copy()->addDay();
        $yesterday = $start->copy()->subDay();
        $users = User::query();
        $premium = User::where('is_active', true)->where('role', '!=', 'Admin')
            ->where(fn($q) => $q->where('role', 'PremiumUser')->orWhere('premium_until', '>', now())
                ->orWhereHas('schoolMemberships', fn($m) => $m->where('role', 'Student')->whereHas('school', fn($s) => $s->where('status', 'Approved'))));
        $stats = [
            'read_at' => now()->toIso8601String(), 'reporting_timezone' => 'Asia/Kathmandu',
            'today' => now('Asia/Kathmandu')->toDateString(),
            'scope' => 'Current database, including demo accounts and records. Counts are not a claim about production-only data.',
            'users' => ['total' => (clone $users)->count(), 'active' => (clone $users)->where('is_active', true)->count(),
                'added_today' => (clone $users)->where('created_at', '>=', $start)->where('created_at', '<', $end)->count(),
                'added_yesterday' => (clone $users)->where('created_at', '>=', $yesterday)->where('created_at', '<', $start)->count(),
                'by_role' => $this->counts(User::query(), 'role'),
                'premium_learners_with_access' => $premium->count(),
                'premium_definition' => 'Active non-admin users with PremiumUser role OR premium_until in the future OR an active Student membership at an Approved school; role-granted access does not prove payment. Admins also have access but are excluded here.'],
            'content_totals' => [],
            'support_by_status' => $this->counts(SupportTicket::query(), 'status'),
            'schools_by_status' => $this->counts(DrivingSchool::query(), 'status'),
            'school_student_ranking' => $this->schoolStudentRanking(),
            'lesson_bookings_by_status' => $this->counts(LessonBooking::query(), 'status'),
        ];
        foreach (['questions'=>Question::class,'traffic_signs'=>TrafficSign::class,'tutorials'=>Tutorial::class,'question_banks'=>ExamPaper::class,'exam_information'=>ExamInformation::class,'vision_tests'=>VisionTest::class] as $key=>$class) {
            $stats['content_totals'][$key] = $class::count();
        }
        $stats['question_statuses'] = $this->counts(Question::query(), 'status');
        $stats['draft_question_issues'] = app(ContentReadiness::class)->summary();
        $stats['question_issue_scope'] = 'Draft questions only; categories overlap. This does not certify published answer correctness.';
        foreach (['premium_payments','school_lesson_payments'] as $table) {
            $stats[$table] = DB::table($table)->select('status','live')->selectRaw('COUNT(*) as total')->groupBy('status','live')->get()->toArray();
        }
        $stats['health'] = app(AdminSystemHealth::class)->snapshot();
        $stats['imports_by_status'] = $this->counts(LearningContentImport::query(), 'status');
        $stats['imports_ai_screening'] = $this->counts(LearningContentImport::query(), 'ai_status');
        $stats['revision_states'] = $this->counts(LearningContentImport::query(), 'source_check_status');
        $stats['recent_revision_checks'] = LearningContentImport::whereNotNull('source_checked_at')->orderByDesc('source_checked_at')->limit(10)->get(['id','title','source_check_status','source_checked_at'])->toArray();
        $stats['recent_source_runs'] = DB::table('content_source_runs')->orderByDesc('id')->limit(10)->get(['source','status','added','known','started_at','finished_at'])->toArray();
        $stats['notices'] = [
            'by_status' => $this->counts(Notice::query(), 'status'), 'visible_to_learners' => Notice::visibleToLearners()->count(),
            'expired_but_published' => Notice::where('status','Published')->where('expires_at','<=',now())->count(),
            'last_import_fetched_at' => GovernmentNoticeImport::max('fetched_at'),
            'import_review_states' => $this->counts(GovernmentNoticeImport::query(), 'status'),
            'recent' => Notice::latest('updated_at')->limit(10)->get(['id','title','status','publish_at','expires_at','updated_at','government_notice_import_id'])->toArray(),
        ];
        $report = storage_path('app/private/storage-check.json');
        $audit = is_file($report) ? json_decode(file_get_contents($report), true) : null;
        $stats['last_storage_audit'] = ['checked_at' => $audit['checked_at'] ?? null,
            'issues_by_type' => collect($audit['issues'] ?? [])->countBy('type')->all(),
            'record_examples' => collect($audit['issues'] ?? [])->take(10)->map(fn($i)=>['type'=>$i['type']??'Unknown','record'=>$i['record']??null])->all(),
            'scope' => 'Saved audit, not a new scan. No saved timestamp means unknown, not healthy.'];
        // Selected notices can be discussed precisely without exposing arbitrary tables.
        if (preg_match('/notice\s*#?\s*(\d+)/i', $message, $match)) {
            $notice = Notice::find((int)$match[1]);
            $stats['selected_notice'] = $notice?->only(['id','title','description','status','publish_at','expires_at','source_name','updated_at','government_notice_import_id']);
        }
        $stats['verification_limits'] = 'No HTTP request or new storage scan was performed. Source success records only describe a past check. Database updates, AI screening and human approval do not prove factual correctness or current official validity. For a URL or record not present in context, request its record ID and use the relevant check tool. Never claim all links work or notices are correct. No arbitrary SQL or record changes are available.';
        if (preg_match('~https?://[^\s<>"\x27]+~i', $message, $match)) {
            $url = rtrim($match[0], '.,)');
            $stats['requested_link'] = [
                'live_check_performed' => false,
                'matching_learning_resources' => LearningContentImport::where(fn($q)=>$q->where('asset_url',$url)->orWhere('source_url',$url))
                    ->limit(10)->get(['id','title','status','source_checked_at','source_check_status'])->toArray(),
                'matching_notices' => Notice::where(fn($q)=>$q->where('link',$url)->orWhere('source_url',$url))
                    ->limit(10)->get(['id','title','status','updated_at','publish_at','expires_at'])->toArray(),
                'meaning' => 'Exact stored URL matches only. No matches does not mean the URL is broken. Even a previous Unchanged result is not a live availability guarantee.',
            ];
        }

        return ['context' => $stats, 'sources' => [
            ['label'=>'Users', 'url'=>route('allUser')],
            ['label'=>'Content readiness', 'url'=>route('contentReadiness')],
            ['label'=>'Storage checks', 'url'=>route('storageCheck')],
            ['label'=>'Source check history', 'url'=>route('importHistory')],
            ['label'=>'Government notices review', 'url'=>route('governmentNotices')],
            ['label'=>'Driving schools', 'url'=>route('admin.schools.index')],
        ]];
    }

    private function counts($query, string $column): array
    {
        return $query->select($column)->selectRaw('COUNT(*) as total')->groupBy($column)->pluck('total', $column)->all();
    }

    private function schoolStudentRanking(): array
    {
        $students = DB::table('school_memberships as m')->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('m.role', 'Student')->select('m.driving_school_id')
            ->selectRaw('COUNT(DISTINCT m.user_id) AS students')
            ->selectRaw('COUNT(DISTINCT CASE WHEN u.is_active = 1 THEN m.user_id END) AS active_students')
            ->groupBy('m.driving_school_id');
        $ranking = DB::table('driving_schools as s')->leftJoinSub($students, 'enrollment', 's.id', '=', 'enrollment.driving_school_id')
            ->where('s.status', 'Approved')->select('s.id', 's.name')
            ->selectRaw('COALESCE(enrollment.students, 0) AS student_count, COALESCE(enrollment.active_students, 0) AS active_student_count');
        $rows = (clone $ranking)->orderByDesc('student_count')->orderBy('s.id')->limit(10)->get();
        $maximum = $rows->isEmpty() ? null : (int) $rows->first()->student_count;
        return [
            'definition' => 'Approved schools ranked by distinct current Student memberships, including inactive accounts. Staff, invitations, batch enrollments and private bookings are not counted as students. Active-account counts are separate. Demo schools are included. A student enrolled at two schools counts once at each.',
            'maximum_student_count' => $maximum,
            'schools_tied_at_maximum' => $maximum === null ? 0 : DB::query()->fromSub($ranking, 'ranked')->where('student_count', $maximum)->count(),
            'top_schools' => $rows->map(fn($r) => ['id'=>$r->id, 'name'=>$r->name, 'student_count'=>(int)$r->student_count, 'active_student_count'=>(int)$r->active_student_count])->all(),
            'display_limit' => 10,
            'interpretation' => 'Use these names and counts to answer which school has most students. Mention ties; if the maximum is zero, say no enrolled students. Do not substitute booking totals. If more than 10 schools tie, state that only 10 names are shown.',
        ];
    }
}
