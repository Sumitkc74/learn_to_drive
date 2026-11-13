<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class PremiumPayment extends \Illuminate\Database\Eloquent\Model {
    public $incrementing=false;protected $keyType='string';
    // Explicit allow-list (not $guarded = []): every field the app
    // mass-assigns on a payment, and nothing else. Creation sets the
    // server-side fields; verify() and refund() set the rest. No
    // user-controlled field may ever be mass-assigned onto a payment.
    protected $fillable=['id','user_id','gateway','live','merchant','amount_paisa','access_days','status','provider_id','transaction_id','paid_at','access_until','refund_status','refund_reason','refunded_at'];
    protected $casts=['live'=>'boolean','paid_at'=>'datetime','access_until'=>'datetime','refunded_at'=>'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function refund(?string $reason = null): void
    {
        DB::transaction(function () use ($reason) {
            $payment = static::lockForUpdate()->findOrFail($this->id);
            if ($payment->status === 'Refunded') {
                return;
            }

            if (! in_array($payment->status, ['Paid'], true)) {
                return;
            }

            $user = User::lockForUpdate()->findOrFail($payment->user_id);
            $user->forceFill([
                'premium_until' => now()->subMinute(),
            ])->save();

            $payment->update([
                'status' => 'Refunded',
                'refund_status' => 'refunded',
                'refund_reason' => $reason,
                'refunded_at' => now(),
            ]);
        });
    }
}
