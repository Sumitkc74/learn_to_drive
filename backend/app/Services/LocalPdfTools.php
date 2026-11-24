<?php

namespace App\Services;

use App\Models\LearningContentImport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class LocalPdfTools
{
    /**
     * Local scratch directory for the PDF tools. The learning-content
     * disk is remote (private R2), but pdftotext/OCR/Chromium and the
     * Smalot parser all need a real local file path, so verified copies
     * are staged here. The files are transient working copies, never
     * the canonical store; Render wipes this directory on redeploy
     * and it can be cleared by hand at any time.
     */
    public function stagingRoot(): string
    {
        $root = storage_path('app/private/learning-content-tmp');
        if (! is_dir($root)) {
            mkdir($root, 0700, true);
        }

        return $root;
    }

    /**
     * Stage already-verified bytes as a local file and return its path.
     * The name is the content hash, so staging the same file twice
     * reuses the same path and a staged file always matches its hash.
     */
    public function stageBytes(string $contents, string $hash): string
    {
        $path = $this->stagingRoot().DIRECTORY_SEPARATOR.$hash.'.pdf';
        if (! is_file($path) || hash_file('sha256', $path) !== $hash) {
            file_put_contents($path, $contents);
        }

        return $path;
    }

    public function sourcePath(LearningContentImport $resource): string
    {
        if ($resource->kind !== 'question-bank-document' || ! $resource->file_path) {
            throw new RuntimeException('Save a private PDF copy first.');
        }
        $disk = Storage::disk('learning-content');
        if (! $disk->exists($resource->file_path)) {
            throw new RuntimeException('Save a private PDF copy first.');
        }
        // The disk is remote, so there is no local path to hand to the
        // PDF tools: download the bytes, verify them, and stage a local
        // copy instead.
        $bytes = $disk->get($resource->file_path);
        if (strlen($bytes) > 20 * 1024 * 1024 || ! hash_equals((string) $resource->file_hash, hash('sha256', $bytes))) {
            throw new RuntimeException('The source PDF has changed or exceeds the 20 MB limit.');
        }

        return $this->stageBytes($bytes, (string) $resource->file_hash);
    }

    public function preview(LearningContentImport $resource, int $page): string
    {
        $source = $this->sourcePath($resource);
        if ($page < 1 || $page > 1000) {
            throw new RuntimeException('Invalid PDF page.');
        }
        $disk = Storage::disk('learning-content');
        $path = 'pdf-previews/'.$resource->file_hash.'/'.$page.'.png';

        return Cache::lock('pdf-preview:'.$resource->file_hash.':'.$page, 60)->block(5, function () use ($disk, $path, $source, $page) {
            if (! $disk->exists($path)) {
                // Render to a local scratch file first: the renderer
                // needs a real path, and the disk is remote.
                $temp = $this->stagingRoot().DIRECTORY_SEPARATOR.Str::uuid().'.png';
                try {
                    $this->run('render', ['--pdf', $source, '--page', (string) $page, '--output', $temp]);
                    $disk->put($path, file_get_contents($temp));
                } finally {
                    if (is_file($temp)) {
                        unlink($temp);
                    }
                }
            }

            return $path;
        });
    }

    public function ocr(LearningContentImport $resource, int $page): array
    {
        if ($resource->status === 'Rejected') {
            throw new RuntimeException('Rejected resources cannot be extracted.');
        }

        return $this->run('ocr', ['--pdf', $this->sourcePath($resource), '--page', (string) $page]);
    }

    public function run(string $mode, array $arguments = []): array
    {
        $process = new Process(array_merge([
            config('pdf-tools.python'), base_path('scripts/pdf_tools.py'), $mode,
            '--modules', config('pdf-tools.modules'), '--tessdata', config('pdf-tools.tessdata'),
        ], $arguments), base_path(), ['OMP_THREAD_LIMIT' => '2']);
        $process->setTimeout($mode === 'ocr' ? 120 : 45);
        try {
            $process->run();
        } catch (\Throwable $exception) {
            report(new RuntimeException('Local PDF processing stopped before completion.'));
            throw new RuntimeException('Local PDF processing could not finish. Check the OCR setup or try one smaller page.');
        }
        $result = json_decode($process->getOutput(), true);
        if (! $process->isSuccessful() || ! is_array($result)) {
            report(new RuntimeException('Local PDF helper failed (exit code '.(string)$process->getExitCode().').'));
            throw new RuntimeException('Local PDF processing failed. Check the Python/OCR setup and that this page exists.');
        }

        return $result;
    }
}
