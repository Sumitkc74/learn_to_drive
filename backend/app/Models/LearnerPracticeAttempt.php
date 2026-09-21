<?php
namespace App\Models;
class LearnerPracticeAttempt extends \Illuminate\Database\Eloquent\Model {
    protected $guarded=['id'];
    protected $casts=['questions'=>'array','answers'=>'array','completed_at'=>'datetime','expires_at'=>'datetime'];
}
