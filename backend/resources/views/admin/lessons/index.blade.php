@extends('admin.layout.master')

@section('title', __('Lessons'))

@section('content')

    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>{{ __('Lessons') }}</h1>
            <p class="text-muted mb-0">{{ __('Every driving lesson published by a school, with its booking state.') }}</p>
        </div>
        <ol class="ltd-breadcrumb">
            <li><a href="/admin">Home</a></li>
            <li>{{ __('Lessons') }}</li>
        </ol>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="ltd-panel__title mb-0">{{ __('Lessons') }}</h3>
            <div><a href="{{ route('admin.lesson-bookings.index') }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-calendar-check"></i> {{ __('Lesson bookings') }}</a></div>
        </div>

        @include('admin.crud.partials.table-controls', [
            'items' => $lessons,
            'sortOptions' => ['created_at' => 'Date added', 'starts_at' => 'Lesson date', 'ends_at' => 'End time', 'price_npr' => 'Price', 'id' => 'ID'],
            'filters' => [
                'school' => ['label' => __('Schools'), 'options' => $schools->mapWithKeys(fn ($name, $id) => [(string) $id => $name])->all()],
                'status' => ['label' => __('Status'), 'options' => ['scheduled' => __('Scheduled'), 'cancelled' => __('Cancelled')]],
            ],
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{{ __('School') }}</th>
                        <th>{{ __('Instructor') }}</th>
                        <th>{{ __('Vehicle') }}</th>
                        <th>{{ __('Meeting point') }}</th>
                        <th>{{ __('Date and time') }}</th>
                        <th>{{ __('Duration') }}</th>
                        <th>{{ __('Price') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Booked by') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lessons as $lesson)
                    <tr>
                        <td>{{ $lesson->id }}</td>
                        <td>{{ $lesson->vehicle->school->name ?? '—' }}</td>
                        <td>{{ $lesson->vehicle->instructor->name ?? '—' }}</td>
                        <td>{{ $lesson->vehicle->name ?? '—' }}@if($lesson->vehicle?->registration) · {{ $lesson->vehicle->registration }}@endif</td>
                        <td>{{ $lesson->meeting_point }}</td>
                        <td>{{ $lesson->starts_at->format('M j, Y g:i A') }}</td>
                        <td>{{ $lesson->starts_at->diffInMinutes($lesson->ends_at) }} {{ __('min') }}</td>
                        <td>Rs {{ number_format($lesson->price_npr) }}</td>
                        <td><span class="badge badge-{{ $lesson->cancelled ? 'danger' : 'success' }}">{{ $lesson->cancelled ? __('Cancelled') : __('Scheduled') }}</span></td>
                        <td>@if($lesson->bookings->first()?->learner){{ $lesson->bookings->first()->learner->name }}<div class="small text-muted">{{ $lesson->bookings->first()->learner->email }}</div>@else<span class="text-muted">{{ __('Available') }}</span>@endif</td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-muted">{{ __('No lessons yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.crud.partials.table-pagination', ['items' => $lessons])
    </div>

@endsection
