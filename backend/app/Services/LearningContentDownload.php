<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\LearningContentImport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class LearningContentDownload
{
    public function fetch(LearningContentImport $resource): array
    {
        $source = config('official-content.sources')[$resource->source_key] ?? null;
        $custom = $resource->source_key === 'custom-website';
        $url = parse_url($resource->asset_url);
        if (!$custom && (! $source || ! is_array($url) || ($url['scheme'] ?? '') !== 'https'
            || ! in_array(strtolower($url['host'] ?? ''), $source['hosts'], true)
            || isset($url['user']) || isset($url['pass']) || (isset($url['port']) && $url['port'] !== 443)
            || $resource->kind !== $source['kind'])) {
            throw new RuntimeException('This resource is no longer on the official source allowlist.');
        }
        $pdf = $resource->kind === 'question-bank-document';
        $limit = ($pdf ? AppSetting::documentLimitKb() : AppSetting::imageLimitKb()) * 1024;
        $response = $custom ? app(PublicWebsiteRequest::class)->get($resource->asset_url) : Http::connectTimeout(10)->timeout(25)
            ->withHeaders(['User-Agent' => 'LearnToDrive-ContentReview/1.0'])
            ->withOptions(['allow_redirects' => false, 'stream' => true, 'verify' => config('official-content.ca_bundle') ?: true])->get($resource->asset_url);
        $stream = $response->toPsrResponse()->getBody();
        $contents = '';
        try {
            if (! $response->successful()) {
                throw new RuntimeException('The official file could not be retrieved; redirects are not followed.');
            }
            if ((int) $response->header('Content-Length') > $limit) {
                throw new RuntimeException('The official file exceeds the saved upload size limit.');
            }
            while (! $stream->eof()) {
                $contents .= $stream->read(65536);
                if (strlen($contents) > $limit) {
                    throw new RuntimeException('The official file exceeds the saved upload size limit.');
                }
            }
        } finally {
            $stream->close();
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if ($pdf) {
            if ($mime !== 'application/pdf' || ! str_starts_with($contents, '%PDF-')) {
                throw new RuntimeException('The source did not return a PDF document.');
            }
            $extension = 'pdf';
        } else {
            $dimensions = @getimagesizefromstring($contents);
            if (! isset($extensions[$mime]) || ! $dimensions || $dimensions[0] * $dimensions[1] > 40000000) {
                throw new RuntimeException('The source did not return a supported image within the 40 megapixel limit.');
            }
            $extension = $extensions[$mime];
        }
        $path = Str::uuid().'.'.$extension;
        Storage::disk('learning-content')->put($path, $contents);

        return ['file_path' => $path, 'mime_type' => $mime, 'file_size' => strlen($contents), 'file_hash' => hash('sha256', $contents), 'downloaded_at' => now()];
    }
}
