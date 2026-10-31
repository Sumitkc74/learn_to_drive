<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class DemoPreviewState {
    public const TABLES=['driving_schools','school_memberships','lesson_vehicles','driving_lessons','lesson_bookings','school_courses','school_batches','school_batch_members','school_class_sessions','school_attendance','school_student_exams','school_student_licences'];
    public function __construct(private ?string $baselinePath=null){}
    private function path():string{return $this->baselinePath??storage_path('app/private/demo-preview-baseline.json');}
    public function capture(array $accounts):void {
        if(!app()->environment('local','testing'))throw new \RuntimeException('Demo reset is restricted to local/testing.');
        if(is_file($this->path()))return;
        $users=array_values(array_map(fn($u)=>$u->id,$accounts));
        $schools=DB::table('driving_schools')->whereIn('name',['[DEMO] Kathmandu Driving Academy','[DEMO] Pokhara Learning School'])->pluck('id');
        $cars=DB::table('lesson_vehicles')->whereIn('driving_school_id',$schools)->whereIn('instructor_id',$users)->whereIn('registration',['DEMO-CAR-1','DEMO-CAR-2'])->pluck('id');
        $lessons=DB::table('driving_lessons')->whereIn('lesson_vehicle_id',$cars)->where('meeting_point','like','[DEMO]%')->pluck('id');
        $courses=DB::table('school_courses')->whereIn('driving_school_id',$schools)->where('title','like','[DEMO]%')->pluck('id');
        $batches=DB::table('school_batches')->whereIn('school_course_id',$courses)->where('name','like','[DEMO]%')->pluck('id');
        $sessions=DB::table('school_class_sessions')->whereIn('school_batch_id',$batches)->where('location','like','[DEMO]%')->pluck('id');
        $queries=[
            'driving_schools'=>DB::table('driving_schools')->whereIn('id',$schools),
            'school_memberships'=>DB::table('school_memberships')->whereIn('driving_school_id',$schools)->whereIn('user_id',$users),
            'lesson_vehicles'=>DB::table('lesson_vehicles')->whereIn('id',$cars),
            'driving_lessons'=>DB::table('driving_lessons')->whereIn('id',$lessons),
            'lesson_bookings'=>DB::table('lesson_bookings')->whereIn('driving_lesson_id',$lessons)->whereIn('learner_id',$users),
            'school_courses'=>DB::table('school_courses')->whereIn('id',$courses),
            'school_batches'=>DB::table('school_batches')->whereIn('id',$batches),
            'school_batch_members'=>DB::table('school_batch_members')->whereIn('school_batch_id',$batches)->whereIn('user_id',$users),
            'school_class_sessions'=>DB::table('school_class_sessions')->whereIn('id',$sessions),
            'school_attendance'=>DB::table('school_attendance')->whereIn('school_class_session_id',$sessions)->whereIn('user_id',$users),
            'school_student_exams'=>DB::table('school_student_exams')->whereIn('driving_school_id',$schools)->whereIn('user_id',$users)->where('centre','[DEMO] Exam centre'),
            'school_student_licences'=>DB::table('school_student_licences')->whereIn('driving_school_id',$schools)->whereIn('user_id',$users)->where('notes','like','[DEMO]%'),
        ];
        $state=['users'=>$users,'schools'=>$schools->all(),'rows'=>[]];
        foreach($queries as $table=>$query)$state['rows'][$table]=$query->get()->map(fn($r)=>(array)$r)->all();
        file_put_contents($this->path(),json_encode($state,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
    }
    public function restore(bool $apply):int {
        if(!app()->environment('local','testing'))throw new \RuntimeException('Demo reset is restricted to local/testing.');
        if(!is_file($this->path()))throw new \RuntimeException('No demo baseline. Run DemoPreviewSeeder first.');
        $state=json_decode(file_get_contents($this->path()),true,512,JSON_THROW_ON_ERROR);
        return DB::transaction(function()use($state,$apply){
            DB::table('driving_schools')->whereIn('id',$state['schools'])->orderBy('id')->lockForUpdate()->get();
            if(DB::table('users')->whereIn('id',$state['users'])->where('email','not like','demo.%@example.test')->exists()
                || DB::table('school_memberships')->whereIn('driving_school_id',$state['schools'])->whereNotIn('user_id',$state['users'])->exists())
                throw new \RuntimeException('A demo account or school now contains non-demo data; reset refused.');
            $bookings=DB::table('lesson_bookings as b')->join('driving_lessons as l','l.id','=','b.driving_lesson_id')->join('lesson_vehicles as v','v.id','=','l.lesson_vehicle_id')->whereIn('v.driving_school_id',$state['schools']);
            if((clone $bookings)->whereNotIn('b.learner_id',$state['users'])->exists()
                || DB::table('school_lesson_payments')->whereIn('lesson_booking_id',(clone $bookings)->select('b.id'))->where(fn($q)=>$q->where('live',true)->orWhere('gateway','!=','demo'))->exists())
                throw new \RuntimeException('Demo school has a real learner booking or provider payment; reset refused.');
            $count=0;
            foreach($state['rows'] as $table=>$rows){
                if(!in_array($table,self::TABLES,true))throw new \RuntimeException('Invalid demo baseline.');
                foreach($rows as $row){
                    $current=DB::table($table)->where('id',$row['id'])->lockForUpdate()->first();
                    if(!$current)continue; // Never recreate deleted rows or reuse identities.
                    foreach(['driving_school_id','user_id','learner_id','instructor_id','school_course_id','school_batch_id','school_class_session_id'] as $key)
                        if(isset($row[$key]) && $row[$key]!=$current->$key)throw new \RuntimeException('A demo record changed ownership; reset refused.');
                    $count++;
                    if($apply){unset($row['id'],$row['created_at']);$row['updated_at']=now();DB::table($table)->where('id',$current->id)->update($row);}
                }
            }
            return $count;
        });
    }
}
