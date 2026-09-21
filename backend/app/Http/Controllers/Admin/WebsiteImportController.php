<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningContentImport;
use App\Services\WebsiteContentScanner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebsiteImportController extends Controller
{
    public const CATEGORIES = ['question-bank-document' => 'Question Banks', 'traffic-sign-sheet' => 'Traffic Signs', 'vision-test-image' => 'Vision Tests'];

    public function index(Request $request)
    {
        $scan = $request->session()->get('website_import_scan');
        if ($scan && $scan['expires'] < time()) $scan = null;
        return view('admin.learning-content.website-import', ['scan' => $scan]);
    }

    public function scan(Request $request, WebsiteContentScanner $scanner)
    {
        $data = $request->validate(['url' => ['required', 'string', 'url:https', 'max:2048'], 'kind' => ['required', Rule::in(array_keys(self::CATEGORIES))]]);
        $request->session()->forget('website_import_scan');
        try { $assets = $scanner->scan($data['url'], $data['kind']); }
        catch (\Throwable $e) { return back()->withInput()->withErrors(['website' => 'This website could not be scanned. Use a public HTTPS page with direct PDF links or images. Redirects, blocked websites, and private networks are not supported.']); }
        $request->session()->put('website_import_scan', $data + ['assets' => $assets, 'token' => (string) Str::uuid(), 'expires' => time() + 1800]);
        return redirect()->route('websiteImport');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string'], 'selected' => ['required', 'array', 'min:1', 'max:50'], 'selected.*' => ['required', 'integer', 'min:0', 'distinct']]);
        $scan = $request->session()->get('website_import_scan');
        abort_unless($scan && $scan['expires'] >= time() && hash_equals($scan['token'], $data['token']), 419, 'Scan expired. Scan the website again.');
        foreach ($data['selected'] as $index) abort_unless(isset($scan['assets'][$index]), 422);
        $added = 0;
        foreach ($data['selected'] as $index) {
            $asset = $scan['assets'][$index];
            $resource = LearningContentImport::firstOrCreate(['url_hash' => hash('sha256', $asset['url'])], [
                'source_key' => 'custom-website', 'source_name' => 'Website: '.parse_url($scan['url'], PHP_URL_HOST),
                'source_url' => $scan['url'], 'asset_url' => $asset['url'], 'kind' => $scan['kind'], 'title' => $asset['title'], 'fetched_at' => now(),
            ]);
            $added += (int) $resource->wasRecentlyCreated;
        }
        return redirect()->route('learningContent', ['kind' => $scan['kind']])->with('success', $added.' new resources added for review; '.(count($data['selected']) - $added).' already known.');
    }
}
