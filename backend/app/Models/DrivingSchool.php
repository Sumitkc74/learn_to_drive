<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class DrivingSchool extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    // Logo and gallery follow the configured media disk (R2 in production,
    // via MEDIA_DISK): both render for signed-out learners on the Find
    // Schools listing through the disk's public URL. They must NOT pin the
    // local 'public' disk — Render's free disk is wiped on every deploy.
    // The logo is a single file; the gallery holds up to
    // SchoolMedia::GALLERY_MAX_ITEMS images/videos.
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(\App\Support\SchoolMedia::LOGO_COLLECTION)->singleFile();
        $this->addMediaCollection(\App\Support\SchoolMedia::GALLERY_COLLECTION);
    }

    public function logoUrl(): ?string
    {
        return $this->getFirstMediaUrl(\App\Support\SchoolMedia::LOGO_COLLECTION) ?: null;
    }

    protected $fillable = ['name', 'address', 'phone', 'email', 'status', 'latitude', 'longitude',
        'commission_percent', 'enrollment_fee_paisa', 'proposed_commission_percent', 'proposed_enrollment_fee_paisa',
        'rates_declined_at', 'rates_declined_reason', 'rates_declined_by'];

    protected $casts = ['latitude' => 'float', 'longitude' => 'float',
        'commission_percent' => 'integer', 'enrollment_fee_paisa' => 'integer',
        'proposed_commission_percent' => 'integer', 'proposed_enrollment_fee_paisa' => 'integer',
        'rates_declined_at' => 'datetime', 'rates_declined_by' => 'integer'];

    // Agreed rates are per school. A null agreed value means the school
    // uses the platform default from config — the platform cannot change
    // a school's agreed rate unilaterally; a new rate only applies once
    // the school accepts the platform's proposal.
    public function effectiveCommissionPercent(): int
    {
        return $this->commission_percent ?? (int)config('schools.commission_percent');
    }

    public function effectiveEnrollmentFeePaisa(): int
    {
        return $this->enrollment_fee_paisa ?? (int)config('schools.enrollment_fee_paisa');
    }

    public function ratesDeclinedBy()
    {
        return $this->belongsTo(User::class, 'rates_declined_by');
    }

    public function memberships()
    {
        return $this->hasMany(SchoolMembership::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'school_memberships')
            ->withPivot('role')
            ->withTimestamps();
    }
}
