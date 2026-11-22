<details class="notice-bell">
<summary aria-label="{{ __('Notifications') }}">@include('learner.icon',['icon'=>'bell'])@if(!is_null($noticeBell['unread']) && $noticeBell['unread'] > 0)<span class="notice-badge">{{ $noticeBell['unread'] > 99 ? '99+' : $noticeBell['unread'] }}</span>@endif</summary>
<div class="account-menu-panel notice-panel">
<div class="notice-panel-head"><strong>{{ __('Notifications') }}</strong>@auth<form method="post" action="{{ route('learn.notices.read-all') }}">@csrf<button type="submit">{{ __('Mark all as read') }}</button></form>@endauth</div>
@forelse($noticeBell['notices'] as $notice)<a class="notice-row @if(auth()->check() && !$noticeBell['readIds']->contains($notice->id)) unread @endif" href="{{ route('learn.detail',['notice',$notice->id]) }}"><span class="notice-row-title">{{ \App\Support\LearnerContent::text($notice,'title') }}@if(auth()->check() && !$noticeBell['readIds']->contains($notice->id))<span class="notice-new">{{ __('New') }}</span>@endif</span><span class="notice-row-date">{{ ($notice->publish_at ?? $notice->created_at)->format('d M Y') }}</span><span class="notice-row-snippet">{{ \Illuminate\Support\Str::limit(strip_tags(\App\Support\LearnerContent::text($notice,'description')),90) }}</span></a>@empty<p class="muted notice-empty">{{ __('No notices yet.') }}</p>@endforelse
<a class="notice-view-all" href="{{ route('learn.library','notice') }}">{{ __('View all notices') }} →</a>
</div>
</details>
