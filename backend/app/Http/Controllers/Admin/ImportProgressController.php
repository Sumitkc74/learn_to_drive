<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ImportProgressController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['type' => ['nullable', 'in:extraction,translation'],
            'status' => ['nullable', 'in:Queued,Running,Review,Generating,Completed,Failed,Cancelled']]);
        $extraction = DB::table('pdf_extraction_runs')->selectRaw("id, learning_content_import_id, 'extraction' as type, status, from_page, to_page as total, next_page, error, updated_at");
        $translation = DB::table('pdf_translations')->selectRaw("id, learning_content_import_id, 'translation' as type, status, 1 as from_page, total_pages as total, next_page, error, updated_at");
        $query = DB::query()->fromSub($extraction->unionAll($translation), 'runs');
        $counts = (clone $query)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');
        foreach (['type', 'status'] as $key) if (!empty($filters[$key])) $query->where($key, $filters[$key]);
        $runs = $query->orderByDesc('updated_at')->orderBy('type')->orderByDesc('id')->paginate(20)->withQueryString();
        $resources = \App\Models\LearningContentImport::whereIn('id', $runs->pluck('learning_content_import_id'))->pluck('title','id');
        return view('admin.learning-content.progress', compact('runs', 'counts', 'resources'));
    }
}
