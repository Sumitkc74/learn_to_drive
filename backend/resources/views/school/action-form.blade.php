<details class="card"><summary>{{ __($title) }}</summary>
<form method="post" action="{{ route('learn.school.action',[$school->id,$action]) }}" class="stack">@csrf
@foreach($fields as $field=>$spec)
<label>{{ __($spec[0]) }}
@if(is_array($spec[1]))<select name="{{ $field }}" required>@foreach($spec[1] as $value=>$label)<option value="{{ $value }}">{{ __($label) }}</option>@endforeach</select>
@else<input name="{{ $field }}" type="{{ $spec[1] }}" value="{{ old($field,$field==='weeks'?1:'') }}" required>@endif
</label>@endforeach
<button class="button">{{ __('Save') }}</button></form></details>
