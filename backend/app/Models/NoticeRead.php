<?php
namespace App\Models;
class NoticeRead extends \Illuminate\Database\Eloquent\Model
{
    protected $guarded = ['id'];
    protected $casts = ['read_at' => 'datetime'];
}
