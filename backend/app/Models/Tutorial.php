<?php

namespace App\Models;

use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tutorial extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, TracksCreator;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'videoLink',
    ];

    /**
     * URL of the thumbnail shown for this tutorial: the uploaded image
     * when one exists, otherwise the thumbnail YouTube serves for the
     * linked video. Null when there is neither.
     */
    public function thumbnailUrl(): ?string
    {
        $media = $this->getFirstMedia();

        if ($media) {
            return $media->getUrl();
        }

        $id = self::youTubeVideoId($this->videoLink);

        return $id ? 'https://img.youtube.com/vi/'.$id.'/hqdefault.jpg' : null;
    }

    /**
     * Extract the 11-character video ID from a YouTube URL, or null when
     * the URL is not a recognised YouTube video link. Accepts watch,
     * youtu.be, shorts, embed, live and legacy /v/ forms.
     */
    public static function youTubeVideoId(?string $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $parts = parse_url(trim($url));

        if (! is_array($parts) || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower(preg_replace('/^www\./', '', $parts['host']));
        $path = trim($parts['path'] ?? '', '/');
        $segments = $path === '' ? [] : explode('/', $path);
        $candidate = null;

        if ($host === 'youtu.be') {
            $candidate = $segments[0] ?? null;
        } elseif (in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com'], true)) {
            if (in_array($segments[0] ?? null, ['shorts', 'embed', 'live', 'v'], true)) {
                $candidate = $segments[1] ?? null;
            } elseif (($segments[0] ?? null) === 'watch' && isset($parts['query'])) {
                parse_str($parts['query'], $query);
                $candidate = $query['v'] ?? null;
            }
        }

        if (! is_string($candidate) || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate)) {
            return null;
        }

        return $candidate;
    }
}
