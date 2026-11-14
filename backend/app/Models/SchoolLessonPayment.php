<?php
namespace App\Models;
class SchoolLessonPayment extends \Illuminate\Database\Eloquent\Model {
    public $incrementing=false; protected $keyType='string'; protected $guarded=['id'];
    protected $casts=['live'=>'boolean','amount_paisa'=>'integer','commission_paisa'=>'integer','school_payout_paisa'=>'integer','paid_at'=>'datetime','refunded_at'=>'datetime'];
    public function booking(){ return $this->belongsTo(LessonBooking::class,'lesson_booking_id'); }
}
