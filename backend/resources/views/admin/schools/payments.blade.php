@extends('admin.layout.master')
@section('title',__('School payments and refunds'))
@section('content')
<h1>{{ __('School payments and refunds') }}</h1>
<p>{{ __('Refunds must first be completed in the merchant dashboard. Recording a reference here does not transfer money.') }}</p>
@foreach($payments as $payment)<section class="ltd-panel mb-3"><h2>{{ $payment->booking->lesson->vehicle->school->name }}</h2><p>{{ $payment->id }} · {{ $payment->booking->learner->name }} · NPR {{ number_format($payment->amount_paisa/100,2) }} · {{ $payment->gateway }} · {{ __($payment->status) }}</p>
@if($payment->status==='RefundRequested')<form method="post" action="{{ route('admin.school-payments.refund',$payment->id) }}">@csrf<label>{{ __('Refund reference') }}<input name="refund_reference" required maxlength="255" class="form-control"></label><label><input type="checkbox" name="confirmed" value="1" required>{{ __('I have completed this refund in the merchant dashboard.') }}</label><button class="btn btn-primary">{{ __('Record completed refund') }}</button></form>@endif</section>@endforeach
{{ $payments->links() }}
@endsection
