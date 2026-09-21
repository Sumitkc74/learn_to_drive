<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    public const TYPES = ['Complaint', 'Feature request', 'Content report', 'Other'];
    public const STATUSES = ['New', 'In Progress', 'Resolved'];
    protected $guarded = ['id'];
    public function user() { return $this->belongsTo(User::class); }
    public function replies() { return $this->hasMany(SupportReply::class)->orderBy('id'); }
}
