<?php
namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\{DrivingSchool,SchoolMembership,User,DrivingLesson};
use App\Services\SchoolNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class PartnerController extends Controller
{
    private function respond(Request $r, string $message) {
        return $r->expectsJson() ? response()->json(['message'=>__($message)]) : back()->with('success',__($message));
    }
    public function index(Request $r) {
        $data = [
            'schools'=>$r->user()->schoolMemberships()->with('school')->get(),
            'invitations'=>DB::table('school_invitations as i')->join('driving_schools as s','s.id','=','i.driving_school_id')
                ->where('i.user_id',$r->user()->id)->where('i.status','Pending')->where('i.expires_at','>',now())->where('s.status','Approved')
                ->select('i.id','i.role','s.name','i.expires_at')->get(),
            'notifications'=>DB::table('school_notifications')->where('user_id',$r->user()->id)->latest('id')->paginate(20),
        ];
        return $r->expectsJson() ? response()->json(['data'=>$data]) : view('school.centre',$data);
    }
    public function apply(Request $r) {
        abort_if($r->user()->role === 'Admin',403);
        $data=$r->validate(['name'=>'required|string|max:160','address'=>'required|string|max:255','phone'=>'required|string|max:30','email'=>'required|email|max:255']);
        DB::transaction(function()use($r,$data){
            User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
            if($r->user()->schoolMemberships()->where('role','Owner')->whereHas('school',fn($q)=>$q->where('status','Pending'))->exists())
                throw ValidationException::withMessages(['name'=>__('You already have a pending school application.')]);
            $school=DrivingSchool::create($data+['status'=>'Pending']);
            $school->memberships()->create(['user_id'=>$r->user()->id,'role'=>'Owner']);
        });
        return $this->respond($r,'School application submitted for administrator review.');
    }
    public function invitation(Request $r,int $invitation) {
        $data=$r->validate(['decision'=>'required|in:accept,decline']);
        DB::transaction(function()use($r,$invitation,$data){
            $invite=DB::table('school_invitations')->where('user_id',$r->user()->id)->where('id',$invitation)->lockForUpdate()->first();
            abort_unless($invite && $invite->status==='Pending' && Carbon::parse($invite->expires_at)->isFuture(),404);
            abort_unless(DrivingSchool::whereKey($invite->driving_school_id)->where('status','Approved')->exists(),403);
            if($data['decision']==='accept') SchoolMembership::firstOrCreate(['driving_school_id'=>$invite->driving_school_id,'user_id'=>$r->user()->id],['role'=>$invite->role]);
            DB::table('school_invitations')->where('id',$invite->id)->update(['status'=>$data['decision']==='accept'?'Accepted':'Declined','updated_at'=>now()]);
        });
        return $this->respond($r,'Invitation updated.');
    }
    public function read(Request $r) {
        DB::table('school_notifications')->where('user_id',$r->user()->id)->whereNull('read_at')->update(['read_at'=>now()]);
        return $this->respond($r,'Notifications marked as read.');
    }
    private function membership(Request $r,int $school): SchoolMembership {
        return $r->user()->schoolMemberships()->where('driving_school_id',$school)
            ->whereHas('school',fn($q)=>$q->where('status','Approved'))->firstOrFail();
    }
    public function show(Request $r,int $school) {
        $member=$this->membership($r,$school); $isStaff=$member->role!=='Student';
        $courses=DB::table('school_courses')->where('driving_school_id',$school)->orderBy('title')->get();
        $batches=DB::table('school_batches as b')->join('school_courses as c','c.id','=','b.school_course_id')
            ->join('users as u','u.id','=','b.instructor_id')->where('c.driving_school_id',$school)
            ->select('b.*','c.title as course_title','u.name as instructor_name')->orderByDesc('b.id')->get();
        $sessions=DB::table('school_class_sessions as s')->join('school_batches as b','b.id','=','s.school_batch_id')
            ->whereIn('b.id',$batches->pluck('id'))
            ->when(!$isStaff,fn($q)=>$q->whereExists(fn($x)=>$x->selectRaw('1')->from('school_batch_members as m')->whereColumn('m.school_batch_id','b.id')->where('m.user_id',$r->user()->id)))
            ->select('s.*','b.name as batch_name','b.instructor_id')->orderByDesc('s.starts_at')->paginate(30,['s.*','b.name as batch_name','b.instructor_id'],'session_page')->withQueryString();
        $sessionPages=$sessions; $sessions=$sessions->getCollection();
        $roster=DB::table('school_batch_members as m')->join('users as u','u.id','=','m.user_id')->whereIn('m.school_batch_id',$batches->pluck('id'))
            ->when(!$isStaff,fn($q)=>$q->where('m.user_id',$r->user()->id))->select('m.school_batch_id','m.user_id','u.name')->get();
        $attendance=DB::table('school_attendance')->whereIn('school_class_session_id',$sessions->pluck('id'))
            ->when(!$isStaff,fn($q)=>$q->where('user_id',$r->user()->id))->get();
        $staff=$isStaff ? DrivingSchool::findOrFail($school)->memberships()->where('role','Instructor')->whereHas('user',fn($q)=>$q->where('is_active',true))->with('user:id,name')->get() : collect();
        $progress=collect();
        if($isStaff) $progress=DB::table('learner_practice_attempts as a')->join('school_memberships as m','m.user_id','=','a.user_id')->join('users as u','u.id','=','a.user_id')
            ->where('m.driving_school_id',$school)->where('m.role','Student')->where('m.share_progress',true)->whereNotNull('a.completed_at')
            ->select('u.id','u.name')->selectRaw('COUNT(*) as completed_attempts')->groupBy('u.id','u.name')->get();
        $data=compact('member','courses','batches','sessions','roster','attendance','staff','progress');
        $data['session_pages']=['current_page'=>$sessionPages->currentPage(),'last_page'=>$sessionPages->lastPage()];
        $members=$member->role==='Owner'?DrivingSchool::findOrFail($school)->memberships()->where('role','!=','Owner')->with('user:id,name')->get():collect();
        if(!$r->expectsJson()) $data+=compact('sessionPages','members');
        $data['school']=DrivingSchool::findOrFail($school);
        return $r->expectsJson() ? response()->json(['data'=>$data]) : view('school.partner',$data);
    }
    public function action(Request $r,int $school,string $action) {
        $member=$this->membership($r,$school);
        DB::transaction(function()use($r,$school,$action,$member){
            DrivingSchool::whereKey($school)->lockForUpdate()->firstOrFail();
            // Recheck membership after acquiring the school lock.
            $member=$this->membership($r,$school);
            if($action==='consent') {
                $data=$r->validate(['share_progress'=>'required|boolean']);
                abort_unless($member->role==='Student',403);
                $member->forceFill($data)->save(); return;
            }
            if($action==='join') {
                $d=$r->validate(['batch_id'=>'required|integer']); abort_unless($member->role==='Student',403);
                User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();
                $batch=$this->batch($school,$d['batch_id']);
                abort_if($batch->closed || $batch->ends_on < now('Asia/Kathmandu')->toDateString(),422);
                if(DB::table('school_batch_members')->where('school_batch_id',$batch->id)->where('user_id',$r->user()->id)->exists()) return;
                foreach(DB::table('school_class_sessions')->where('school_batch_id',$batch->id)->where('cancelled',false)->where('ends_at','>',now())->get() as $session) {
                    if(\App\Models\LessonBooking::where('learner_id',$r->user()->id)->where('status','Booked')->whereHas('lesson',fn($q)=>$q->overlapping($session->starts_at,$session->ends_at))->exists())
                        throw ValidationException::withMessages(['batch_id'=>__('You already have a booking at this time.')]);
                    if(DB::table('school_class_sessions as s')->join('school_batch_members as m','m.school_batch_id','=','s.school_batch_id')->where('m.user_id',$r->user()->id)->where('s.cancelled',false)->where('s.starts_at','<',$session->ends_at)->where('s.ends_at','>',$session->starts_at)->exists())
                        throw ValidationException::withMessages(['batch_id'=>__('You already have a booking at this time.')]);
                }
                abort_if(DB::table('school_batch_members')->where('school_batch_id',$batch->id)->count()>=$batch->capacity,422,__('This batch is full.'));
                DB::table('school_batch_members')->insert(['school_batch_id'=>$batch->id,'user_id'=>$r->user()->id,'created_at'=>now(),'updated_at'=>now()]); return;
            }
            abort_unless(in_array($member->role,['Owner','Manager','Instructor']),403);
            if(in_array($action,['invite','course','batch','close-batch','role','transfer'])) abort_unless(in_array($member->role,['Owner','Manager']),403);
            switch($action) {
                case 'invite':
                    $d=$r->validate(['email'=>'required|email|max:255','role'=>'required|in:Student,Instructor,Manager']);
                    if($d['role']==='Manager' && $member->role!=='Owner') throw ValidationException::withMessages(['role'=>__('Only the owner can invite managers.')]);
                    $user=User::where('email',$d['email'])->where('is_active',true)->where('role','!=','Admin')->first();
                    if(!$user || SchoolMembership::where('driving_school_id',$school)->where('user_id',$user->id)->exists()) throw ValidationException::withMessages(['email'=>__('Choose an active account that is not already a member.')]);
                    DB::table('school_invitations')->updateOrInsert(['driving_school_id'=>$school,'user_id'=>$user->id],['role'=>$d['role'],'status'=>'Pending','expires_at'=>now()->addDays(7),'created_at'=>now(),'updated_at'=>now()]);
                    SchoolNotifications::send($user->id,'A school invitation is waiting in your school centre.'); break;
                case 'role':
                case 'transfer':
                    abort_unless($member->role==='Owner',403);
                    $d=$r->validate(['user_id'=>'required|integer','role'=>$action==='role'?'required|in:Student,Instructor,Manager':'nullable']);
                    $target=SchoolMembership::where('driving_school_id',$school)->where('user_id',$d['user_id'])->whereHas('user',fn($q)=>$q->where('is_active',true)->where('role','!=','Admin'))->firstOrFail();
                    abort_if($target->role==='Owner',422);
                    if($action==='transfer') { abort_unless($target->role==='Manager',422); $target->update(['role'=>'Owner']); $member->update(['role'=>'Manager']); }
                    else $target->update(['role'=>$d['role']]);
                    SchoolNotifications::send($target->user_id,'Your school role has changed. Check your school centre.'); break;
                case 'course':
                    $d=$r->validate(['title'=>'required|string|max:160','description'=>'required|string|max:2000']);
                    DB::table('school_courses')->insert($d+['driving_school_id'=>$school,'created_at'=>now(),'updated_at'=>now()]); break;
                case 'batch':
                    $d=$r->validate(['school_course_id'=>'required|integer','instructor_id'=>'required|integer','name'=>'required|string|max:100','starts_on'=>'required|date_format:Y-m-d','ends_on'=>'required|date_format:Y-m-d|after_or_equal:starts_on','capacity'=>'required|integer|min:1|max:100']);
                    abort_unless(DB::table('school_courses')->where('id',$d['school_course_id'])->where('driving_school_id',$school)->where('archived',false)->exists(),404);
                    abort_unless(SchoolMembership::where('driving_school_id',$school)->where('user_id',$d['instructor_id'])->where('role','Instructor')->whereHas('user',fn($q)=>$q->where('is_active',true))->exists(),422);
                    DB::table('school_batches')->insert($d+['created_at'=>now(),'updated_at'=>now()]); break;
                case 'close-batch':
                    $d=$r->validate(['batch_id'=>'required|integer']); $batch=$this->batch($school,$d['batch_id']);
                    DB::table('school_batches')->where('id',$batch->id)->update(['closed'=>true,'updated_at'=>now()]); break;
                case 'session':
                    $d=$r->validate(['batch_id'=>'required|integer','starts_at'=>'required|date_format:Y-m-d\TH:i','duration'=>'required|integer|min:15|max:240','weeks'=>'nullable|integer|min:1|max:12','location'=>'required|string|max:255']);
                    $batch=$this->batch($school,$d['batch_id']);
                    abort_unless($member->role!=='Instructor' || $batch->instructor_id===$r->user()->id,403);
                    $learners=DB::table('school_batch_members')->where('school_batch_id',$batch->id)->pluck('user_id');
                    User::whereIn('id',$learners->push($batch->instructor_id)->unique())->orderBy('id')->lockForUpdate()->get(['id']);
                    $learners=$learners->reject(fn($id)=>$id===$batch->instructor_id);
                    abort_unless(SchoolMembership::where('driving_school_id',$school)->where('user_id',$batch->instructor_id)->where('role','Instructor')->whereHas('user',fn($q)=>$q->where('is_active',true))->exists(),422);
                    $first=Carbon::createFromFormat('Y-m-d\TH:i',$d['starts_at'],'Asia/Kathmandu')->second(0);
                    for($week=0;$week<($d['weeks']??1);$week++){
                    $start=$first->copy()->addWeeks($week);
                    abort_unless($start->isFuture() && $start->toDateString()>=$batch->starts_on && $start->toDateString()<=$batch->ends_on,422);
                    $start->utc(); $end=$start->copy()->addMinutes($d['duration']);
                    abort_if($end->copy()->timezone('Asia/Kathmandu')->toDateString()>$batch->ends_on,422);
                    $learnerBusy=\App\Models\LessonBooking::whereIn('learner_id',$learners)->where('status','Booked')->whereHas('lesson',fn($q)=>$q->overlapping($start,$end))->exists()
                        || DB::table('school_class_sessions as s')->join('school_batch_members as m','m.school_batch_id','=','s.school_batch_id')->whereIn('m.user_id',$learners)->where('s.cancelled',false)->where('s.starts_at','<',$end)->where('s.ends_at','>',$start)->exists();
                    if($learnerBusy) throw ValidationException::withMessages(['starts_at'=>__('An enrolled learner already has a class or booking at this time.')]);
                    $overlap=DB::table('school_class_sessions as s')->join('school_batches as b','b.id','=','s.school_batch_id')->where('b.instructor_id',$batch->instructor_id)->where('s.cancelled',false)->where('s.starts_at','<',$end)->where('s.ends_at','>',$start)->exists();
                    if($overlap || DrivingLesson::where('cancelled',false)->whereHas('vehicle',fn($q)=>$q->where('instructor_id',$batch->instructor_id))->overlapping($start,$end)->exists()) throw ValidationException::withMessages(['starts_at'=>__('The instructor already has a lesson at this time.')]);
                    DB::table('school_class_sessions')->insert(['school_batch_id'=>$batch->id,'starts_at'=>$start,'ends_at'=>$end,'location'=>$d['location'],'created_at'=>now(),'updated_at'=>now()]);
                    }
                    foreach(DB::table('school_batch_members')->where('school_batch_id',$batch->id)->pluck('user_id') as $id) SchoolNotifications::send($id,'New classes were added to your school schedule.');
                    break;
                case 'attendance':
                case 'cancel-session':
                    $d=$r->validate(['session_id'=>'required|integer','user_id'=>$action==='attendance'?'required|integer':'nullable','status'=>$action==='attendance'?'required|in:Present,Absent,Excused':'nullable']);
                    $session=DB::table('school_class_sessions')->where('id',$d['session_id'])->first(); abort_unless($session,404);
                    $batch=$this->batch($school,$session->school_batch_id);
                    abort_unless($member->role!=='Instructor' || $batch->instructor_id===$r->user()->id,403);
                    if($action==='cancel-session') {
                        if($session->cancelled) return;
                        abort_unless(Carbon::parse($session->starts_at)->isFuture(),422);
                        DB::table('school_class_sessions')->where('id',$session->id)->update(['cancelled'=>true,'updated_at'=>now()]);
                        foreach(DB::table('school_batch_members')->where('school_batch_id',$batch->id)->pluck('user_id') as $id) SchoolNotifications::send($id,'A school class was cancelled. Check your class schedule.');
                    } else {
                        abort_if($session->cancelled || Carbon::parse($session->starts_at)->isFuture(),422);
                        abort_unless(DB::table('school_batch_members')->where('school_batch_id',$batch->id)->where('user_id',$d['user_id'])->exists(),404);
                        DB::table('school_attendance')->updateOrInsert(['school_class_session_id'=>$session->id,'user_id'=>$d['user_id']],['status'=>$d['status'],'marked_by'=>$r->user()->id,'created_at'=>now(),'updated_at'=>now()]);
                    } break;
                default: abort(404);
            }
            \App\Models\AuditLog::create(['actor_id'=>$r->user()->id,'event'=>'school.'.$action,'subject_type'=>DrivingSchool::class,'subject_id'=>$school,'subject_label'=>'School workflow']);
        });
        return $this->respond($r,'School updated.');
    }
    private function batch(int $school,int $id) {
        $batch=DB::table('school_batches as b')->join('school_courses as c','c.id','=','b.school_course_id')->where('b.id',$id)->where('c.driving_school_id',$school)->select('b.*')->first();
        abort_unless($batch,404); return $batch;
    }
}
