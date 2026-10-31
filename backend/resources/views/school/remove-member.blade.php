@if(in_array($member->role, $membershipRole === 'Owner' ? ['Student','Instructor','Manager'] : ($membershipRole === 'Manager' ? ['Student','Instructor'] : []), true))
<details class="mb-3"><summary>{{ __('Remove membership') }}</summary>
    <p>{{ __('Only school access is removed. The account and learning history are kept.') }}</p>
    <form method="post" action="{{ route('learn.school.members.remove', $member->id) }}">@csrf @method('DELETE')
        <input type="hidden" name="school_id" value="{{ $school->id }}">
        <button class="button" type="submit">{{ __('Confirm removal') }}</button>
    </form>
</details>
@endif
