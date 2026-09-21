<?php
namespace App\Services;
use App\Models\PdfTranslation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
class ReviewedTranslationPdf {
    public function generate(PdfTranslation $run): array {
        $disk = Storage::disk('learning-content');
        $folder = 'translation-builds/'.Str::uuid();
        $disk->makeDirectory($folder);
        $html = '<!doctype html><meta charset="utf-8"><style>@page{size:A4;margin:16mm}body{font-family:"Nirmala UI","Noto Sans Devanagari",sans-serif;font-size:12pt}section{break-before:page}pre{white-space:pre-wrap;overflow-wrap:anywhere;font:inherit}img{max-width:100%;max-height:245mm}</style><h1>Reviewed translation</h1><p>'.e($run->resource->title).'</p><p>This is an admin-reviewed translation, not an official translated edition. Original pages are included for comparison.</p>';
        foreach ($run->pages as $page) {
            $preview = app(LocalPdfTools::class)->preview($run->resource, $page->page);
            $html .= '<section><h2>Original page '.$page->page.'</h2><img src="data:image/png;base64,'.base64_encode($disk->get($preview)).'"></section>';
            $html .= '<section><h2>Reviewed translation — source page '.$page->page.'</h2><pre>'.e($page->translated_text).'</pre></section>';
        }
        $disk->put($folder.'/document.html', $html);
        $output = $disk->path($folder.'/document.pdf');
        $process = new Process([config('pdf-translation.browser'), '--headless', '--disable-gpu', '--no-pdf-header-footer',
            '--user-data-dir='.$disk->path($folder.'/browser'), '--print-to-pdf='.$output,
            'file:///'.str_replace(['\\', ' '], ['/', '%20'], $disk->path($folder.'/document.html'))]);
        $process->setTimeout(120);
        try {
            $process->mustRun();
            if (!is_file($output) || (new \finfo(FILEINFO_MIME_TYPE))->file($output) !== 'application/pdf') throw new \RuntimeException('PDF generation failed.');
            if (filesize($output) > \App\Models\AppSetting::documentLimitKb() * 1024) throw new \RuntimeException('Generated PDF exceeds the document upload limit.');
            $path = 'translated-'.Str::uuid().'.pdf';
            $disk->put($path, file_get_contents($output));
            return ['file_path' => $path, 'file_hash' => hash_file('sha256', $output), 'file_size' => filesize($output), 'mime_type' => 'application/pdf', 'downloaded_at' => now()];
        } finally {
            // Chromium may briefly retain profile locks on Windows. Do not mask
            // generation errors or discard a successful PDF because of cleanup.
            try { $disk->deleteDirectory($folder); } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Translation browser temporary directory needs cleanup.', ['directory' => $folder]);
            }
        }
    }
}
