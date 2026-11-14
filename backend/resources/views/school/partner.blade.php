@extends('learner.layout')
@section('title',$school->name)
@section('content')
<h1>{{ $school->name }}</h1><a href="{{ route('learn.school.centre') }}">{{ __('School centre') }}</a>
<p><a class="button" href="{{ route('learn.school.students',$school->id) }}">{{ __('Student exams and licences') }}</a></p>
@php($manager=in_array($member->role,['Owner','Manager']))
@if($manager)
<p><a class="button" href="{{ route('learn.school.report',$school->id) }}">{{ __('School reports') }}</a></p>
@include('school.action-form',['title'=>'Invite member','action'=>'invite','fields'=>['email'=>['Account email','email'],'role'=>['School role',$member->role==='Owner'?['Student'=>'Student','Instructor'=>'Instructor','Manager'=>'Manager']:['Student'=>'Student','Instructor'=>'Instructor']]]])
<p>{{ __('If the student does not have an account yet, create one for them instead — they sign in with the email and initial password below, then change the password in Account settings. If the email is already registered, use Invite member above.') }}</p>
@include('school.action-form',['title'=>'Create student account','action'=>'create-student','fields'=>['name'=>['Student name','text'],'email'=>['Student email','email'],'phoneNumber'=>['Phone number (10 digits)','text'],'password'=>['Initial password (at least 8 characters)','password'],'password_confirmation'=>['Confirm initial password','password']]])
@if($joinRequests->isNotEmpty())<h2>{{ __('Join requests') }}</h2>@foreach($joinRequests as $joinRequest)<section class="card"><h3>{{ $joinRequest->name }}</h3><p>{{ __('Requested') }}: {{ $joinRequest->created_at }}</p><form method="post" action="{{ route('learn.school.action',[$school->id,'join-approve']) }}">@csrf<input type="hidden" name="request_id" value="{{ $joinRequest->id }}"><button>{{ __('Approve and enrol') }}</button></form><form method="post" action="{{ route('learn.school.action',[$school->id,'join-decline']) }}">@csrf<input type="hidden" name="request_id" value="{{ $joinRequest->id }}"><button>{{ __('Decline') }}</button></form></section>@endforeach @endif
@include('school.action-form',['title'=>'Create course','action'=>'course','fields'=>['title'=>['Course title','text'],'description'=>['Description','text']]])
@if($courses->isNotEmpty() && $staff->isNotEmpty())
@include('school.action-form',['title'=>'Create batch','action'=>'batch','fields'=>['school_course_id'=>['Course',$courses->pluck('title','id')->all()],'instructor_id'=>['Instructor',$staff->mapWithKeys(fn($m)=>[$m->user_id=>$m->user->name])->all()],'name'=>['Batch name','text'],'starts_on'=>['Start date','date'],'ends_on'=>['End date','date'],'capacity'=>['Capacity','number']]])
@endif
@if($member->role==='Owner')
@include('school.action-form',['title'=>'Change member role','action'=>'role','fields'=>['user_id'=>['Member',$members->mapWithKeys(fn($m)=>[$m->user_id=>$m->user->name])->all()],'role'=>['School role',['Student'=>'Student','Instructor'=>'Instructor','Manager'=>'Manager']]]])
@include('school.action-form',['title'=>'Transfer ownership to an existing manager','action'=>'transfer','fields'=>['user_id'=>['Manager',$members->where('role','Manager')->mapWithKeys(fn($m)=>[$m->user_id=>$m->user->name])->all()]]])
@endif
@endif
@if($member->role==='Student')
<section class="card"><h2>{{ __('Learning progress privacy') }}</h2><p>{{ __('Share only your completed practice count with school staff. Your answers and chats stay private.') }}</p><form method="post" action="{{ route('learn.school.action',[$school->id,'consent']) }}">@csrf<select name="share_progress"><option value="0" @selected(!$member->share_progress)>{{ __('Private') }}</option><option value="1" @selected($member->share_progress)>{{ __('Share progress') }}</option></select><button>{{ __('Save') }}</button></form></section>
@endif
<h2>{{ __('Courses and batches') }}</h2>
@foreach($courses as $course)<section class="card"><h3>{{ $course->title }}</h3><p>{{ $course->description }}</p></section>@endforeach
@foreach($batches as $batch)<section class="card"><h3>{{ $batch->name }} · {{ $batch->course_title }}</h3><p>{{ $batch->instructor_name }} · {{ $batch->starts_on }}–{{ $batch->ends_on }} · {{ __('Capacity') }}: {{ $batch->capacity }}</p><p>{{ $batch->closed?__('Closed'):__('Open') }}</p>
@if(!$batch->closed && ($manager || $member->role==='Student'))<form method="post" action="{{ route('learn.school.action',[$school->id,$manager?'close-batch':'join']) }}">@csrf<input type="hidden" name="batch_id" value="{{ $batch->id }}"><button>{{ $manager?__('Close enrollment'):__('Join batch') }}</button></form>@endif
</section>@endforeach
@php($teaching=$member->role==='Instructor'?$batches->where('instructor_id',auth()->id()):$batches)
@if($member->role!=='Student' && $teaching->isNotEmpty())
@include('school.action-form',['title'=>'Schedule class','action'=>'session','fields'=>['batch_id'=>['Batch',$teaching->pluck('name','id')->all()],'starts_at'=>['Start (Nepal time)','datetime-local'],'duration'=>['Duration in minutes','number'],'weeks'=>['Weekly occurrences (1–12)','number'],'location'=>['Meeting point','text']]])
@endif
<h2>{{ __('Class schedule and attendance') }}</h2>
@foreach($sessions as $session)<section class="card"><h3>{{ $session->batch_name }}</h3><p>{{ \Carbon\Carbon::parse($session->starts_at)->timezone('Asia/Kathmandu')->format('d M Y H:i') }} {{ __('Nepal time') }} · {{ $session->location }}</p>
@if($session->cancelled)<p>{{ __('Cancelled') }}</p>@else
@if($member->role!=='Student' && ($manager || $session->instructor_id===auth()->id()))
@if(\Carbon\Carbon::parse($session->starts_at)->isFuture())<form method="post" action="{{ route('learn.school.action',[$school->id,'cancel-session']) }}">@csrf<input type="hidden" name="session_id" value="{{ $session->id }}"><button>{{ __('Cancel class') }}</button></form>
@else
@foreach($roster->where('school_batch_id',$session->school_batch_id) as $student)<form method="post" action="{{ route('learn.school.action',[$school->id,'attendance']) }}">@csrf<input type="hidden" name="session_id" value="{{ $session->id }}"><input type="hidden" name="user_id" value="{{ $student->user_id }}"><label>{{ $student->name }}<select name="status">@foreach(['Present','Absent','Excused'] as $status)<option @selected($attendance->where('school_class_session_id',$session->id)->firstWhere('user_id',$student->user_id)?->status===$status)>{{ $status }}</option>@endforeach</select></label><button>{{ __('Record attendance') }}</button></form>@endforeach
@endif
@endif
@foreach($attendance->where('school_class_session_id',$session->id) as $entry)<p>{{ $roster->firstWhere('user_id',$entry->user_id)?->name }}: {{ __($entry->status) }}</p>@endforeach
@endif</section>@endforeach
@if($member->role!=='Student')<h2>{{ __('Shared learning progress') }}</h2>@forelse($progress as $row)<p>{{ $row->name }}: {{ $row->completed_attempts }} {{ __('completed practices') }}</p>@empty<p>{{ __('No shared progress yet.') }}</p>@endforelse @endif
{{ $sessionPages->links('pagination::simple-default') }}
@endsection
