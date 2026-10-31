@extends('admin.layout.master')

@section('title', 'Premium Payments')

@section('content')
<div class="ltd-page-header">
    <div>
        <span class="ltd-page-header__eyebrow">Admin Panel</span>
        <h1>Premium Payments</h1>
    </div>
    <ol class="ltd-breadcrumb">
        <li><a href="{{ route('adminDashboard') }}">Home</a></li>
        <li>Premium Payments</li>
    </ol>
</div>

<div class="ltd-panel">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="ltd-panel__title mb-0">Payment history</h3>
    </div>

    @include('admin.crud.partials.table-controls', [
        'items' => $payments,
        'sortOptions' => ['created_at' => 'Date', 'amount_paisa' => 'Amount', 'status' => 'Status', 'gateway' => 'Gateway'],
        'filters' => [
            'status' => ['label' => 'Status', 'options' => ['Pending' => 'Pending', 'Paid' => 'Paid', 'Refunded' => 'Refunded']],
            'gateway' => ['label' => 'Gateway', 'options' => ['khalti' => 'Khalti', 'esewa' => 'eSewa']],
        ],
    ])

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Gateway</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Paid</th>
                    <th>Access</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->user?->name ?? 'Unknown user' }}</td>
                        <td>{{ $payment->gateway === 'esewa' ? 'eSewa' : 'Khalti' }} {{ $payment->live ? '' : '(sandbox)' }}</td>
                        <td>NPR {{ number_format($payment->amount_paisa / 100, 2) }}</td>
                        <td>
                            <span class="badge badge-{{ $payment->status === 'Paid' ? 'success' : ($payment->status === 'Refunded' ? 'secondary' : 'warning') }}">
                                {{ $payment->status }}
                            </span>
                        </td>
                        <td>{{ $payment->paid_at?->format('M j, Y H:i') ?? '—' }}</td>
                        <td>{{ $payment->access_until?->format('M j, Y H:i') ?? '—' }}</td>
                        <td>
                            @if($payment->status === 'Paid')
                                <form action="{{ route('admin.payments.refund', $payment) }}" method="POST" class="d-flex align-items-center gap-2" onsubmit="return confirm('Refund this payment and revoke access?');">
                                    @csrf
                                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="Refund reason" aria-label="Refund reason" maxlength="1000" style="min-width: 180px;">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Refund</button>
                                </form>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-muted text-center">No premium payments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.crud.partials.table-pagination', ['items' => $payments])
</div>
@endsection
