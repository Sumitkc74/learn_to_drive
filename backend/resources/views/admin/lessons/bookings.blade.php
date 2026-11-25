@extends('admin.layout.master')

@section('title', __('Lesson bookings'))

@section('content')

    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>{{ __('Lesson bookings') }}</h1>
            <p class="text-muted mb-0">{{ __('Every lesson booking, with learner, lesson and payment state.') }}</p>
        </div>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h2 class="ltd-panel__title mb-0">{{ __('Lesson bookings') }}</h2>
            <div><a href="{{ route('admin.lessons.index') }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-calendar-alt"></i> {{ __('Lessons') }}</a></div>
        </div>

        @include('admin.crud.partials.table-controls', [
            'items' => $bookings,
            'sortOptions' => ['created_at' => 'Date booked', 'id' => 'ID'],
            'filters' => [
                'school' => ['label' => __('Schools'), 'options' => $schools->mapWithKeys(fn ($name, $id) => [(string) $id => $name])->all()],
                'status' => ['label' => __('Status'), 'options' => ['Booked' => __('Booked'), 'Cancelled' => __('Cancelled'), 'Completed' => __('Completed')]],
            ],
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <th scope="col"ead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">{{ __('Learner') }}</th>
                        <th scope="col">{{ __('Lesson') }}</th>
                        <th scope="col">{{ __('School') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Payment') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                    <tr>
                        <td>{{ $booking->id }}</td>
                        <td>{{ $booking->learner->name ?? '—' }}@if($booking->learner?->email)<div class="small text-muted">{{ $booking->learner->email }}</div>@endif</td>
                        <td>{{ $booking->lesson->starts_at->format('M j, Y g:i A') }}<div class="small text-muted">{{ $booking->lesson->vehicle->name ?? '—' }}@if($booking->lesson->vehicle?->instructor) · {{ $booking->lesson->vehicle->instructor->name }}@endif</div></td>
                        <td>{{ $booking->lesson->vehicle->school->name ?? '—' }}</td>
                        <td><span class="badge badge-{{ $booking->status === 'Booked' ? 'success' : 'secondary' }}">{{ __($booking->status) }}</span></td>
                        <td>@if($booking->payment)NPR {{ number_format($booking->payment->amount_paisa / 100, 2) }} · {{ __($booking->payment->status) }}@else<span class="text-muted">{{ __('No payment') }}</span>@endif</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted">{{ __('No lesson bookings yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.crud.partials.table-pagination', ['items' => $bookings])
    </div>

@endsection
