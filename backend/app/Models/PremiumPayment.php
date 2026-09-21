<?php
namespace App\Models;
class PremiumPayment extends \Illuminate\Database\Eloquent\Model {
    public $incrementing=false;protected $keyType='string';protected $guarded=[];
    protected $casts=['live'=>'boolean','paid_at'=>'datetime','access_until'=>'datetime'];
}
