@php
    $formKey=$kind.':'.($record?->id ?? 'new');
    $value=fn($field,$default='')=>old('form_key')===$formKey?old($field,$record?->$field ?? $default):($record?->$field ?? $default);
@endphp
<form method="post" action="{{ route('learn.school.students.save',[$school->id,$student->id,$kind]) }}" class="stack">
@csrf
<input type="hidden" name="form_key" value="{{ $formKey }}">
@if($record)<input type="hidden" name="id" value="{{ $record->id }}"><input type="hidden" name="version" value="{{ $record->version }}">@endif
<label>{{ __('Licence category') }}<input name="category" value="{{ $value('category') }}" maxlength="30" required></label>
@if($kind==='exam')
<label>{{ __('Exam type') }}<select name="exam_type">@foreach($types as $type)<option value="{{ $type }}" @selected($value('exam_type','Written')===$type)>{{ __($type) }}</option>@endforeach</select></label>
<label>{{ __('Exam date') }}<input type="date" name="exam_date" value="{{ $value('exam_date') }}" required></label>
<label>{{ __('Exam centre') }}<input name="centre" value="{{ $value('centre') }}" maxlength="160"></label>
<label>{{ __('Result') }}<select name="result">@foreach($results as $result)<option value="{{ $result }}" @selected($value('result','Scheduled')===$result)>{{ __($result) }}</option>@endforeach</select></label>
<label>{{ __('Result date') }}<input type="date" name="result_date" value="{{ $value('result_date') }}" max="{{ now('Asia/Kathmandu')->toDateString() }}"></label>
@else
<label>{{ __('Issued on') }}<input type="date" name="issued_on" value="{{ $value('issued_on') }}" max="{{ now('Asia/Kathmandu')->toDateString() }}"></label>
<label>{{ __('Expires on') }}<input type="date" name="expires_on" value="{{ $value('expires_on') }}"></label>
@endif
<label>{{ __('Notes visible to student') }}<textarea name="notes" maxlength="1000">{{ $value('notes') }}</textarea></label>
<button class="button">{{ __('Save') }}</button>
</form>
