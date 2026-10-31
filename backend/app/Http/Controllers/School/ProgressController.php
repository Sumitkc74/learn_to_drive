<?php
namespace App\Http\Controllers\School;
use App\Models\{DrivingSchool,AuditLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ProgressController extends StudentRecordController {
    public const ASSESSMENTS=['Needs practice','Developing','Confident'];
    public function readiness(Request $r,int $school,int $student){
        $member=$this->member($r,$school);
        $enrollment=$this->students($r,$school,$member)->with('user:id,name')->where('user_id',$student)->firstOrFail();
        $sessions=DB::table('school_class_sessions as s')->join('school_batches as b','b.id','=','s.school_batch_id')->join('school_courses as c','c.id','=','b.school_course_id')
            ->join('school_batch_members as m','m.school_batch_id','=','b.id')->where('c.driving_school_id',$school)->where('m.user_id',$student)->where('s.cancelled',false)
            ->when($member->role==='Instructor',fn($q)=>$q->where('b.instructor_id',$r->user()->id))->select('s.*','b.name as batch_name')->orderByDesc('s.starts_at');
        $sessionIds=(clone $sessions)->reorder()->select('s.id');
        $attendance=DB::table('school_attendance')->where('user_id',$student)->whereIn('school_class_session_id',clone $sessionIds)->select('status')->selectRaw('COUNT(*) AS total')->groupBy('status')->pluck('total','status');
        $feedback=DB::table('school_lesson_feedback as f')->join('users as u','u.id','=','f.instructor_id')->where('f.user_id',$student)->whereIn('f.school_class_session_id',clone $sessionIds)
            ->select('f.*','u.name as instructor_name')->orderByDesc('f.updated_at')->orderByDesc('f.id')->paginate(20)->withQueryString();
        $practiceCount=($member->role==='Student'||$enrollment->share_progress)?DB::table('learner_practice_attempts')->where('user_id',$student)->whereNotNull('completed_at')->count():null;
        $exams=DB::table('school_student_exams')->where('driving_school_id',$school)->where('user_id',$student)->where('exam_date','>=',now('Asia/Kathmandu')->toDateString())->orderBy('exam_date')->limit(10)->get(['category','exam_type','exam_date','result']);
        $data=['school'=>DrivingSchool::findOrFail($school),'student'=>$enrollment->user,'attendance'=>$attendance,'feedback'=>$feedback,
            'practice_count'=>$practiceCount,'exams'=>$exams,'canEdit'=>$member->role!=='Student','assessments'=>self::ASSESSMENTS,
            'sessions'=>(clone $sessions)->where('s.ends_at','<=',now())->whereExists(fn($q)=>$q->selectRaw('1')->from('school_attendance as a')->whereColumn('a.school_class_session_id','s.id')->where('a.user_id',$student)->where('a.status','Present'))->limit(50)->get()];
        return $r->expectsJson()?response()->json(['data'=>$data]):view('school.students.readiness',$data);
    }
    public function feedback(Request $r,int $school,int $student){
        $d=$r->validate(['session_id'=>'required|integer','skill'=>'required|string|max:100','assessment'=>'required|in:'.implode(',',self::ASSESSMENTS),'notes'=>'required|string|max:2000']);
        DB::transaction(function()use($r,$school,$student,$d){
            DrivingSchool::whereKey($school)->lockForUpdate()->firstOrFail();$member=$this->member($r,$school);
            abort_unless(in_array($member->role,['Owner','Manager','Instructor'],true),403);
            $this->students($r,$school,$member)->where('user_id',$student)->firstOrFail();
            $session=DB::table('school_class_sessions as s')->join('school_batches as b','b.id','=','s.school_batch_id')->join('school_courses as c','c.id','=','b.school_course_id')
                ->where('s.id',$d['session_id'])->where('c.driving_school_id',$school)->where('s.cancelled',false)->where('s.ends_at','<=',now())
                ->when($member->role==='Instructor',fn($q)=>$q->where('b.instructor_id',$r->user()->id))->select('s.id')->first();
            abort_unless($session && DB::table('school_attendance')->where('school_class_session_id',$session->id)->where('user_id',$student)->where('status','Present')->exists(),422);
            DB::table('school_lesson_feedback')->updateOrInsert(['school_class_session_id'=>$session->id,'user_id'=>$student,'skill'=>trim($d['skill'])],
                ['instructor_id'=>$r->user()->id,'assessment'=>$d['assessment'],'notes'=>$d['notes'],'created_at'=>now(),'updated_at'=>now()]);
            AuditLog::create(['actor_id'=>$r->user()->id,'event'=>'school.feedback_saved','subject_type'=>DrivingSchool::class,'subject_id'=>$school,'subject_label'=>'Lesson feedback']);
        });
        return $r->expectsJson()?response()->json(['message'=>__('Lesson feedback saved.')]):back()->with('success',__('Lesson feedback saved.'));
    }
    public function report(Request $r,int $school){
        $member=$this->member($r,$school);abort_unless(in_array($member->role,['Owner','Manager'],true),403);
        $d=$r->validate(['from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d','format'=>'nullable|in:csv']);
        $from=\Carbon\Carbon::parse($d['from']??now('Asia/Kathmandu')->startOfMonth()->toDateString(),'Asia/Kathmandu')->startOfDay();
        $to=\Carbon\Carbon::parse($d['to']??now('Asia/Kathmandu')->toDateString(),'Asia/Kathmandu')->endOfDay();
        abort_unless($from<=$to && $from->diffInDays($to)<=366,422);
        $exams=DB::table('school_student_exams')->where('driving_school_id',$school)->whereBetween('exam_date',[$from->toDateString(),$to->toDateString()])->select('result')->selectRaw('COUNT(*) as total')->groupBy('result')->pluck('total','result');
        $start=$from->copy()->utc();$end=$to->copy()->utc();
        $bookings=DB::table('lesson_bookings as b')->join('driving_lessons as l','l.id','=','b.driving_lesson_id')->join('lesson_vehicles as v','v.id','=','l.lesson_vehicle_id')->where('v.driving_school_id',$school)->whereBetween('l.starts_at',[$start,$end]);
        $attendance=DB::table('school_attendance as a')->join('school_class_sessions as s','s.id','=','a.school_class_session_id')->join('school_batches as b','b.id','=','s.school_batch_id')->join('school_courses as c','c.id','=','b.school_course_id')->where('c.driving_school_id',$school)->where('s.cancelled',false)->whereBetween('s.starts_at',[$start,$end]);
        $payments=DB::table('school_lesson_payments as p')->join('lesson_bookings as b','b.id','=','p.lesson_booking_id')->join('driving_lessons as l','l.id','=','b.driving_lesson_id')->join('lesson_vehicles as v','v.id','=','l.lesson_vehicle_id')->where('v.driving_school_id',$school)->whereBetween('p.created_at',[$start,$end]);
        $passed=(int)($exams['Passed']??0);$failed=(int)($exams['Failed']??0);
        $metrics=['Passed exams'=>$passed,'Failed exams'=>$failed,'Pass rate (passed and failed only)'=>$passed+$failed?round(100*$passed/($passed+$failed),1).'%':'—',
            'Results pending'=>(int)($exams['Awaiting result']??0)+(int)($exams['Scheduled']??0),
            'Booked lessons'=>(clone $bookings)->where('b.status','Booked')->count(),'Cancelled bookings'=>(clone $bookings)->where('b.status','Cancelled')->count(),
            'Present attendance'=>(clone $attendance)->where('a.status','Present')->count(),'Absent attendance'=>(clone $attendance)->where('a.status','Absent')->count(),
            'Paid live invoices (NPR)'=>(clone $payments)->where('p.live',true)->where('p.status','Paid')->sum('p.amount_paisa')/100,
            'Paid test invoices (NPR)'=>(clone $payments)->where('p.live',false)->where('p.status','Paid')->sum('p.amount_paisa')/100,
            'Refund requests'=>(clone $payments)->where('p.status','RefundRequested')->count()];
        if(($d['format']??null)==='csv')return response()->streamDownload(function()use($metrics,$from,$to){$f=fopen('php://output','w');fputcsv($f,['Metric','Value','From','To']);foreach($metrics as $label=>$value)fputcsv($f,[$label,$value,$from->toDateString(),$to->toDateString()]);fclose($f);},'school-report.csv',['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'no-store']);
        $data=['metrics'=>$metrics,'from'=>$from->toDateString(),'to'=>$to->toDateString(),'school'=>DrivingSchool::findOrFail($school)];
        return $r->expectsJson()?response()->json(['data'=>$data]):view('school.report',$data);
    }
}
