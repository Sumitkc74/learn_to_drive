<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'attempted_questions',
        'optionA',
        'optionB',
        'optionC',
        'optionD',
        'correct_options',
        'selected_options',
    ];
}
