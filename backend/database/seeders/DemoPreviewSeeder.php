<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Support\Str;
use App\Models\{User,DrivingSchool,LessonVehicle,DrivingLesson,LessonBooking,SchoolLessonPayment,Tutorial,ExamPaper,ExamInformation,Notice,Question,SupportTicket};

/** Explicit local preview only; never invoked by DatabaseSeeder. */
class DemoPreviewSeeder extends Seeder
{
    private function row(string $table,array $key,array $values): int {
        $existing=DB::table($table)->where($key)->value('id');
        return $existing ?? DB::table($table)->insertGetId($key+$values+['created_at'=>now(),'updated_at'=>now()]);
    }
    public function run(): void {
        if(!app()->environment('local','testing')) throw new \RuntimeException('Demo preview data is restricted to local/testing.');
        $credentialFile=storage_path('app/private/demo-preview-accounts.json');
        $credentials=is_file($credentialFile)?json_decode(file_get_contents($credentialFile),true,512,JSON_THROW_ON_ERROR):[];
        $password=$credentials['password']??Str::random(24);
        $accounts=[];
        DB::transaction(function()use($password,&$accounts){
            foreach(['owner','manager','instructor','student','student2','premium'] as $i=>$role){
                $email='demo.'.$role.'@example.test';
                $user=User::firstOrCreate(['email'=>$email],[
                    'name'=>'[DEMO] '.ucfirst($role),'phoneNumber'=>'98000000'.str_pad((string)$i,2,'0',STR_PAD_LEFT),
                    'password'=>Hash::make($password),'role'=>$role==='premium'?'PremiumUser':'User','is_active'=>true,'profileImage'=>'',
                ]);
                if(!str_starts_with($user->name,'[DEMO]')) throw new \RuntimeException('Demo email is already used by a non-demo account.');
                if($user->wasRecentlyCreated) $user->forceFill(['email_verified_at'=>now(),'premium_until'=>$role==='premium'?now()->addMonth():null])->save();
                $accounts[$role]=$user;
            }
            $school=DrivingSchool::firstOrCreate(['name'=>'[DEMO] Kathmandu Driving Academy'],[
                'address'=>'Demonstration location, Kathmandu (not a real school)','email'=>'demo.school@example.test',
                'status'=>'Approved','latitude'=>27.7172,'longitude'=>85.3240,
            ]);
            $pendingSchool=DrivingSchool::firstOrCreate(['name'=>'[DEMO] Pokhara Learning School'],[
                'address'=>'Demonstration location, Pokhara (not a real school)','status'=>'Pending','email'=>'demo.pokhara@example.test',
                'latitude'=>28.2096,'longitude'=>83.9856,
            ]);
            $pendingSchool->memberships()->firstOrCreate(['user_id'=>$accounts['owner']->id],['role'=>'Owner']);
            foreach($accounts as $role=>$user) $school->memberships()->firstOrCreate(['user_id'=>$user->id],[
                'role'=>match($role){'owner'=>'Owner','manager'=>'Manager','instructor'=>'Instructor',default=>'Student'},
            ]);
            $cars=[];
            foreach(['Manual','Automatic'] as $i=>$transmission) $cars[]=LessonVehicle::firstOrCreate(['registration'=>'DEMO-CAR-'.($i+1)],[
                'driving_school_id'=>$school->id,'instructor_id'=>$accounts['instructor']->id,'name'=>'[DEMO] '.($i?'City hatchback':'Training sedan'),'transmission'=>$transmission,
            ]);
            $slots=[];
            foreach(range(1,6) as $i) {
                $start=now('Asia/Kathmandu')->addDays($i)->setTime(10,0)->utc();
                $slots[]=DrivingLesson::firstOrCreate(['lesson_vehicle_id'=>$cars[$i%2]->id,'meeting_point'=>'[DEMO] Academy reception - slot '.$i],[
                    'starts_at'=>$start,'ends_at'=>$start->copy()->addHour(),'price_npr'=>$i===6?1100:1000+$i*100,
                ]);
            }
            foreach([0,1] as $i){
                $booking=LessonBooking::firstOrCreate(['driving_lesson_id'=>$slots[$i]->id,'learner_id'=>$accounts['student']->id],[
                    'status'=>$i?'Cancelled':'Booked','active_lesson_id'=>$i?null:$slots[$i]->id,
                ]);
                if($i===0 && !$booking->payment()->exists()){
                    $payment=new SchoolLessonPayment;
                    $payment->forceFill(['id'=>(string)Str::uuid(),'lesson_booking_id'=>$booking->id,'amount_paisa'=>$slots[$i]->price_npr*100,
                        'gateway'=>'demo','live'=>false,'status'=>'Paid','transaction_id'=>'DEMO-NO-MONEY-'.$booking->id,'paid_at'=>now()])->save();
                }
            }
            $course=$this->row('school_courses',['driving_school_id'=>$school->id,'title'=>'[DEMO] Beginner car course'],['description'=>'Preview course: classroom learning and supervised driving.']);
            $batch=$this->row('school_batches',['school_course_id'=>$course,'name'=>'[DEMO] Morning group'],[
                'instructor_id'=>$accounts['instructor']->id,'starts_on'=>now()->subWeek()->toDateString(),'ends_on'=>now()->addMonth()->toDateString(),'capacity'=>12,
            ]);
            foreach(['student','student2','premium'] as $role) $this->row('school_batch_members',['school_batch_id'=>$batch,'user_id'=>$accounts[$role]->id],[]);
            foreach([-2,2,4] as $day){
                $start=now('Asia/Kathmandu')->addDays($day)->setTime(14,0)->utc();
                $session=$this->row('school_class_sessions',['school_batch_id'=>$batch,'location'=>'[DEMO] Classroom '.$day],[
                    'starts_at'=>$start,'ends_at'=>$start->copy()->addHour(),
                ]);
                if($day<0) foreach(['student'=>'Present','student2'=>'Absent','premium'=>'Excused'] as $role=>$status)
                    $this->row('school_attendance',['school_class_session_id'=>$session,'user_id'=>$accounts[$role]->id],['status'=>$status,'marked_by'=>$accounts['instructor']->id]);
            }
            foreach(['student','student2','premium'] as $i=>$role){
                $student=$accounts[$role];
                foreach(['Written'=>'Failed','Practical'=>'Scheduled'] as $type=>$result) $this->row('school_student_exams',[
                    'driving_school_id'=>$school->id,'user_id'=>$student->id,'category'=>'B','exam_type'=>$type,
                ],['exam_date'=>($result==='Scheduled'?now()->addDays(8+$i):now()->subDays(7))->toDateString(),
                    'result'=>$result,'result_date'=>$result==='Scheduled'?null:now()->subDays(6)->toDateString(),
                    'centre'=>'[DEMO] Exam centre','notes'=>'Demonstration only; not an official exam booking or result.','recorded_by'=>$accounts['owner']->id]);
                $this->row('school_student_licences',['driving_school_id'=>$school->id,'user_id'=>$student->id,'category'=>'A'],[
                    'issued_on'=>now()->subYears(2)->toDateString(),'expires_on'=>now()->addYear()->toDateString(),
                    'notes'=>'[DEMO] Fictional previous category for preview.','recorded_by'=>$accounts['owner']->id,
                ]);
                $this->row('school_notifications',['user_id'=>$student->id,'message'=>'[DEMO] Welcome! Open your school to explore classes, attendance and exam records.'],[]);
                SupportTicket::firstOrCreate(['user_id'=>$student->id,'subject'=>'[DEMO] '.($i?'Feature suggestion':'Booking help')],[
                    'type'=>$i?'Feature request':'Other','message'=>'Fictional support request for previewing the help desk.','status'=>['New','In Progress','Resolved'][$i],
                ]);
            }
            $this->row('school_invitations',['driving_school_id'=>$school->id,'user_id'=>$accounts['manager']->id],[
                'role'=>'Manager','status'=>'Accepted','expires_at'=>now()->addWeek(),
            ]);
            foreach(['Published','Draft'] as $status) Notice::firstOrCreate(['title'=>'[DEMO] School orientation - '.$status],[
                'description'=>'Preview notice only. This is not a government announcement.','nepaliTitle'=>'[DEMO] नमुना सूचना',
                'nepaliDescription'=>'यो प्रदर्शनका लागि मात्र हो।','link'=>url('/learn/schools'),'status'=>$status,'source_name'=>'Local demonstration',
            ]);
            foreach(['Getting started','Preparing for lessons'] as $topic) Tutorial::firstOrCreate(['title'=>'[DEMO] '.$topic],[
                'description'=>'Preview tutorial card. Use the linked practice page to explore learning activities. No video is attached.',
                'videoLink'=>url('/learn/practice'),
            ]);
            foreach(['[DEMO] What should you adjust before moving?','[DEMO] What helps you learn from practice?'] as $i=>$question)
                Question::firstOrCreate(['question'=>$question],[
                    'option1'=>$i?'Review mistakes':'Seat and mirrors','option2'=>'Ignore instructions','option3'=>'Rush','option4'=>'Skip checks',
                    'correctOption'=>'A','category'=>'General','difficulty'=>'Easy','explanation'=>'Demonstration question for the review list.','status'=>'Draft',
                ]);
            foreach([ExamPaper::class,ExamInformation::class] as $class) foreach([1,2] as $i){
                $doc=$class::firstOrCreate(['name'=>'[DEMO] '.($class===ExamPaper::class?'Question bank':'Exam information').' '.$i],[
                    'description'=>'English demonstration document for preview only. Not an official question bank or exam notice.','language'=>'English',
                ]);
                if(!$doc->getFirstMedia()) $doc->addMediaFromString($this->pdf())->usingFileName('demo-preview.pdf')->toMediaCollection();
            }
        });
        if(!app()->environment('testing')) {
            app(\App\Services\DemoPreviewState::class)->capture($accounts);
            file_put_contents($credentialFile,json_encode(['password'=>$password,'accounts'=>array_map(fn($u)=>$u->email,$accounts)],JSON_PRETTY_PRINT));
            $this->command?->info('Demo records added. Login details: storage/app/private/demo-preview-accounts.json');
        }
    }
    private function pdf():string {
        $stream="BT /F1 18 Tf 50 760 Td (DEMO - Learn to Drive) Tj 0 -35 Td /F1 12 Tf (Preview document. Not official exam material.) Tj ET";
        $objects=['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',"<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream"];
        $pdf="%PDF-1.4\n";$offsets=[0];foreach($objects as $i=>$object){$offsets[]=strlen($pdf);$pdf.=($i+1)." 0 obj\n".$object."\nendobj\n";}
        $xref=strlen($pdf);$pdf.="xref\n0 6\n0000000000 65535 f \n";foreach(array_slice($offsets,1) as $offset)$pdf.=sprintf('%010d 00000 n ',$offset)."\n";
        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }
}
