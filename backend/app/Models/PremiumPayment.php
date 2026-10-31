<?php
namespace App\Models;

use Illuminate\Support\Facades\DB;

class PremiumPayment extends \Illuminate\Database\Eloquent\Model {
    public $incrementing=false;protected $keyType='string';protected $guarded=[];
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
