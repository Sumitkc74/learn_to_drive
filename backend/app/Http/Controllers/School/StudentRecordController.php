<?php
namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\{DrivingSchool, SchoolMembership, AuditLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentRecordController extends Controller
{
    public const TYPES = ['Written', 'Practical', 'Medical', 'Other'];
    public const RESULTS = ['Scheduled', 'Awaiting result', 'Passed', 'Failed', 'Absent', 'Cancelled'];

    protected function member(Request $request, int $school): SchoolMembership {
        return $request->user()->schoolMemberships()->where('driving_school_id', $school)
            ->whereHas('school', fn($q) => $q->where('status', 'Approved'))->firstOrFail();
    }

    protected function students(Request $request, int $school, SchoolMembership $member) {
        return SchoolMembership::where('driving_school_id', $school)->where('role', 'Student')
            ->when($member->role === 'Student', fn($q) => $q->where('user_id', $request->user()->id))
            ->when($member->role === 'Instructor', fn($q) => $q->whereExists(function($sub) use ($request, $school) {
                $sub->selectRaw('1')->from('school_batch_members as bm')->join('school_batches as b','b.id','=','bm.school_batch_id')
                    ->join('school_courses as c','c.id','=','b.school_course_id')->where('c.driving_school_id',$school)
                    ->where('b.instructor_id',$request->user()->id)->whereColumn('bm.user_id','school_memberships.user_id');
            }));
    }

    public function index(Request $request, int $school) {
        $member=$this->member($request,$school);
        $filters=$request->validate(['q'=>'nullable|string|max:100','result'=>'nullable|in:'.implode(',',self::RESULTS),
            'date_from'=>'nullable|date_format:Y-m-d','date_to'=>'nullable|date_format:Y-m-d'.($request->filled('date_from')?'|after_or_equal:date_from':'')]);
        $studentQuery=$this->students($request,$school,$member)
            ->when($filters['q']??null,fn($q,$term)=>$q->whereHas('user',fn($u)=>$u->where(fn($x)=>$x->where('name','like','%'.$term.'%')->orWhere('email','like','%'.$term.'%'))))
            ->orderBy('id');
        $students=(clone $studentQuery)->with('user:id,name,email')->paginate(20)->withQueryString();
        $schedule=DB::table('school_student_exams as e')->join('users as u','u.id','=','e.user_id')
            ->where('e.driving_school_id',$school)->whereIn('e.user_id',(clone $studentQuery)->select('user_id'))
            ->when($filters['result']??null,fn($q,$result)=>$q->where('e.result',$result))
            ->when($filters['date_from']??null,fn($q,$date)=>$q->where('e.exam_date','>=',$date))
            ->when($filters['date_to']??null,fn($q,$date)=>$q->where('e.exam_date','<=',$date))
            ->select('e.id','e.user_id','u.name','e.category','e.exam_type','e.exam_date','e.result')
            ->orderByDesc('e.exam_date')->orderByDesc('e.id')->paginate(20,['*'],'exam_page')->withQueryString();
        $school=DrivingSchool::findOrFail($school);
        $results=self::RESULTS;
        return $request->expectsJson()?response()->json(['data'=>$students,'exam_schedule'=>$schedule]):view('school.students.index',compact('students','school','schedule','results'));
    }

    public function show(Request $request,int $school,int $student) {
        $member=$this->member($request,$school);
        $student=$this->students($request,$school,$member)->with('user:id,name,email')->where('user_id',$student)->firstOrFail()->user;
        $exams=DB::table('school_student_exams')->where('driving_school_id',$school)->where('user_id',$student->id)->orderByDesc('exam_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $licences=DB::table('school_student_licences')->where('driving_school_id',$school)->where('user_id',$student->id)->orderBy('category')->get();
        $canEdit=in_array($member->role,['Owner','Manager'],true);
        $school=DrivingSchool::findOrFail($school);
        $types=self::TYPES; $results=self::RESULTS;
        $data=compact('student','school','exams','licences','canEdit','types','results');
        return $request->expectsJson()?response()->json(['data'=>$data]):view('school.students.show',$data);
    }

    public function save(Request $request,int $school,int $student,string $kind) {
        abort_unless(in_array($kind,['exam','licence'],true),404);
        $member=$this->member($request,$school);
        abort_unless(in_array($member->role,['Owner','Manager'],true),403);
        if(is_string($request->input('category'))) $request->merge(['category'=>mb_strtoupper(trim($request->input('category')))]);
        $rules=['id'=>'nullable|integer|min:1','version'=>'required_with:id|integer|min:1',
            'category'=>'required|string|max:30|regex:/^[A-Z0-9][A-Z0-9 .\/-]*$/','notes'=>'nullable|string|max:1000'];
        $today=now('Asia/Kathmandu')->toDateString();
        $rules += $kind==='exam' ? [
            'exam_type'=>'required|in:'.implode(',',self::TYPES),'exam_date'=>'required|date_format:Y-m-d',
            'centre'=>'nullable|string|max:160','result'=>'required|in:'.implode(',',self::RESULTS),
            'result_date'=>'nullable|date_format:Y-m-d|after_or_equal:exam_date|before_or_equal:'.$today,
        ] : ['issued_on'=>'nullable|date_format:Y-m-d|before_or_equal:'.$today,
            'expires_on'=>'nullable|date_format:Y-m-d'.($request->filled('issued_on')?'|after_or_equal:issued_on':'')];
        $data=$request->validate($rules);
        if($kind==='exam') {
            if(in_array($data['result'],['Passed','Failed','Absent','Awaiting result'],true) && $data['exam_date']>$today)
                throw ValidationException::withMessages(['result'=>__('A future exam cannot have an outcome yet.')]);
            if(in_array($data['result'],['Passed','Failed','Absent'],true) && empty($data['result_date']))
                throw ValidationException::withMessages(['result_date'=>__('Enter the date the result was recorded.')]);
            if(in_array($data['result'],['Scheduled','Awaiting result'],true) && !empty($data['result_date']))
                throw ValidationException::withMessages(['result_date'=>__('Leave the result date empty until an outcome is available.')]);
        }
        DB::transaction(function() use($request,$school,$student,$kind,$data) {
            DrivingSchool::whereKey($school)->lockForUpdate()->firstOrFail();
            $member=$this->member($request,$school);
            abort_unless(in_array($member->role,['Owner','Manager'],true),403);
            $this->students($request,$school,$member)->where('user_id',$student)->firstOrFail();
            $table=$kind==='exam'?'school_student_exams':'school_student_licences';
            $query=DB::table($table)->where('driving_school_id',$school)->where('user_id',$student);
            $id=$data['id']??null;
            $version=$data['version']??null;
            unset($data['id'],$data['version']);
            if($id) {
                $existing=(clone $query)->where('id',$id)->lockForUpdate()->first();
                abort_unless($existing,404);
                if($existing->version!=$version) throw ValidationException::withMessages(['version'=>__('This record changed. Reload the page before saving.')]);
            }
            $duplicate=(clone $query)->where('category',$data['category'])->when($id,fn($q)=>$q->where('id','!=',$id));
            if($kind==='exam') $duplicate->where('exam_type',$data['exam_type'])->where('exam_date',$data['exam_date']);
            if($duplicate->exists()) throw ValidationException::withMessages(['category'=>__('This record already exists. Edit the existing record instead.')]);
            $data+=['recorded_by'=>$request->user()->id,'updated_at'=>now()];
            if($id) (clone $query)->where('id',$id)->update($data+['version'=>$version+1]);
            else $id=DB::table($table)->insertGetId($data+['driving_school_id'=>$school,'user_id'=>$student,'created_at'=>now()]);
            AuditLog::create(['actor_id'=>$request->user()->id,'event'=>'school.student_'.$kind.'_saved',
                'subject_type'=>DrivingSchool::class,'subject_id'=>$school,'subject_label'=>'Student record',
                'new_values'=>['student_id'=>$student,'record_id'=>$id]]);
        });
        return $request->expectsJson()?response()->json(['message'=>__('Student record saved.')]):redirect()->route('learn.school.students.show',[$school,$student])->with('success',__('Student record saved.'));
    }
}
