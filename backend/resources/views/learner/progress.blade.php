<section class="engagement-section" aria-label="{{ __('Your daily learning') }}">
    <div class="section-heading"><div><p class="eyebrow">{{ __('A SMALL WIN EVERY DAY') }}</p><h2>{{ __($progress['todayDone'] ? 'Today’s goal? Done.' : 'Make today a learning day.') }}</h2></div><span class="badge">{{ __('Daily goal · Nepal time') }}</span></div>
    <div class="engagement-grid">
        <article class="card daily-goal">
            <span class="goal-icon" aria-hidden="true">@include('learner.icon',['icon'=>$progress['todayDone']?'check':'arrow'])</span>
            <p class="eyebrow">{{ __('YOUR DAILY CHALLENGE') }}</p>
            <h3>{{ __($progress['todayDone'] ? 'You showed up. Keep it going.' : 'One session. A little more confidence.') }}</h3>
            <p>{{ __('Complete one practice session today to reach your goal. Start with up to five questions, then review your answers.') }}</p>
            @if($progress['todayDone'])
                <a class="button" href="#practice-history">{{ __('Review your sessions →') }}</a>
            @elseif(($counts['questions'] ?? \App\Models\Question::where('status','Published')->count()) > 0)
                @auth
                <form action="{{ route('learn.practice.start') }}" method="post">@csrf<input type="hidden" name="count" value="5"><input type="hidden" name="language" value="{{ session('practice_language',app()->getLocale()) }}"><button class="button">{{ __('Take today’s challenge →') }}</button></form>
                @else
                <a class="button" href="{{ route('learn.login') }}">{{ __('Sign in to start →') }}</a>
                @endauth
            @else
                <p class="muted">{{ __('New practice questions are being prepared. Try sign flashcards while you wait.') }}</p>
                <a class="button" href="{{ route('learn.flashcards') }}">{{ __('Try sign flashcards →') }}</a>
            @endif
        </article>
        <article class="card activity-card">
            <p class="eyebrow">{{ __('YOUR MOMENTUM') }}</p>
            @auth
            <h3>{{ __(':count days in a row',['count'=>$progress['streak']]) }}</h3>
            <p>{{ __('Complete a session each day to build your streak. Missing a day starts a fresh streak.') }}</p>
            <div class="activity-week" aria-label="{{ __('Practice activity for the past seven days') }}">
                @foreach($progress['week'] as $day)<div><span @class(['activity-dot','completed'=>$day['done']]) aria-label="{{ $day['date'] }}: {{ __($day['done'] ? 'practice completed' : 'no completed practice') }}">@include('learner.icon',['icon'=>$day['done']?'check':'dot'])</span><small>{{ __($day['label']) }}</small></div>@endforeach
            </div>
            <div class="progress-totals"><span><strong>{{ $progress['sessions'] }}</strong> {{ __('sessions') }}</span><span><strong>{{ $progress['answered'] }}</strong> {{ __('answered') }}</span><span><strong>{{ $progress['accuracy'] !== null ? $progress['accuracy'].'%' : '—' }}</strong> {{ __('accuracy') }}</span></div>
            @else
            <h3>{{ __('Watch your progress grow.') }}</h3><p>{{ __('Create an account to track your practice streak, see your accuracy, and earn milestones as you learn.') }}</p><a class="text-link" href="{{ route('learn.register') }}">{{ __('Start your learning journey →') }}</a>
            @endauth
        </article>
    </div>
    @auth
    <div class="milestones" aria-label="{{ __('Learning milestones') }}">
        @foreach([1=>['First step','Complete your first session'],5=>['Finding your rhythm','Complete 5 sessions'],20=>['Building confidence','Complete 20 sessions']] as $target=>$milestone)
        <div @class(['milestone','earned'=>$progress['sessions'] >= $target])><span class="milestone-symbol" aria-hidden="true">{{ $progress['sessions'] >= $target ? '★' : '☆' }}</span><div><strong>{{ __($milestone[0]) }}</strong><small>{{ $progress['sessions'] >= $target ? __('Earned') : __($milestone[1]).' · '.min($progress['sessions'],$target).'/'.$target }}</small></div></div>
        @endforeach
    </div>
    @endauth
</section>
