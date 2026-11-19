<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'exam_duration_minutes' => 30,
        'exam_passing_score' => 60,
        'exam_question_count' => 20,
        'otp_expiry_minutes' => 10,
        'otp_resend_seconds' => 60,
        'access_token_expiry_days' => 90,
        'image_upload_limit_mb' => 2,
        'document_upload_limit_mb' => 10,
        'video_upload_limit_mb' => 20,
        'maintenance_notice' => '',
    ];

    public static function read(string $key)
    {
        $default = self::DEFAULTS[$key] ?? null;
        $value = static::query()->where('key', $key)->value('value');

        if ($value === null) {
            return $default;
        }

        return is_int($default) ? (int) $value : $value;
    }

    public static function values(): array
    {
        return collect(self::DEFAULTS)->mapWithKeys(fn ($default, $key) => [$key => self::read($key)])->all();
    }

    public static function imageLimitKb(): int
    {
        return self::read('image_upload_limit_mb') * 1024;
    }

    public static function documentLimitKb(): int
    {
        return self::read('document_upload_limit_mb') * 1024;
    }

    // No video upload existed before school galleries; 20 MB covers short
    // phone clips of a school while keeping free-tier storage bounded.
    public static function videoLimitKb(): int
    {
        return self::read('video_upload_limit_mb') * 1024;
    }
}
