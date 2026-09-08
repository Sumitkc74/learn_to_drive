<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TracksCreator
{
    public static function bootTracksCreator(): void
    {
        static::creating(function ($model): void {
            $actor = auth()->user();

            if ($actor instanceof User && $actor->role === 'Admin' && empty($model->created_by)) {
                $model->created_by = $actor->id;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
