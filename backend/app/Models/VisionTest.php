<?php

namespace App\Models;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VisionTest extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, TracksCreator;

    protected $fillable = [
        'testNumber',
        'image',
    ];
}
