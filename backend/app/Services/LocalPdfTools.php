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
    public function sourcePath(LearningContentImport $resource): string
    {
        $disk = Storage::disk('learning-content');
        if ($resource->kind !== 'question-bank-document' || ! $resource->file_path || ! $disk->exists($resource->file_path)) {
            throw new RuntimeException('Save a private PDF copy first.');
        }
        $path = $disk->path($resource->file_path);
        if (filesize($path) > 20 * 1024 * 1024 || ! hash_equals((string) $resource->file_hash, hash_file('sha256', $path))) {
            throw new RuntimeException('The source PDF has changed or exceeds the 20 MB limit.');
        }

        return $path;
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
                $disk->makeDirectory(dirname($path));
                $temp = dirname($path).'/'.Str::uuid().'.png';
                try {
                    $this->run('render', ['--pdf', $source, '--page', (string) $page, '--output', $disk->path($temp)]);
                    $disk->move($temp, $path);
                } finally {
                    $disk->delete($temp);
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
