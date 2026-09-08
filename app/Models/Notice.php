<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notice extends Model
{
    use HasFactory, SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'nepaliTitle',
        'nepaliDescription',
        'link',
        'status',
        'publish_at',
        'expires_at',
    ];

    protected $casts = [
        'publish_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopeVisibleToLearners($query)
    {
        return $query->where('status', 'Published')
            ->where(fn ($builder) => $builder->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where(fn ($builder) => $builder->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function getPublicationStateAttribute(): string
    {
        if ($this->status !== 'Published') return $this->status;
        if ($this->publish_at?->isFuture()) return 'Scheduled';
        if ($this->expires_at?->isPast()) return 'Expired';
        return 'Active';
    }
}
