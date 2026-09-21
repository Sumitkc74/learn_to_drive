<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
class MediaLibraryController extends Controller {
    public const TYPES = [
        'questions' => [\App\Models\Question::class, 'Questions', 'editQuestion', 'question'],
        'banks' => [\App\Models\ExamPaper::class, 'Question Banks', 'editExamPaper', 'name'],
        'signs' => [\App\Models\TrafficSign::class, 'Traffic Signs', 'editTrafficSign', 'name'],
        'vision' => [\App\Models\VisionTest::class, 'Vision Tests', 'editVisionTest', 'testNumber'],
        'information' => [\App\Models\ExamInformation::class, 'Exam Information', 'editExamInformation', 'name'],
        'tutorials' => [\App\Models\Tutorial::class, 'Tutorials', 'editTutorial', 'title'],
    ];
    public function index(Request $request) {
        $data = $request->validate(['type'=>['nullable',Rule::in(array_keys(self::TYPES))], 'format'=>['nullable','in:image,pdf'], 'search'=>['nullable','string','max:100']]);
        $map = [];
        foreach (self::TYPES as $key => $type) $map[(new $type[0])->getMorphClass()] = [$key, $type];
        $query = Media::whereIn('model_type',array_keys($map));
        if (!empty($data['type'])) $query->where('model_type',(new (self::TYPES[$data['type']][0]))->getMorphClass());
        if (($data['format'] ?? '') === 'image') $query->whereIn('mime_type',['image/jpeg','image/png','image/webp']);
        if (($data['format'] ?? '') === 'pdf') $query->where('mime_type','application/pdf');
        if (!empty($data['search'])) $query->where('file_name','like','%'.$data['search'].'%');
        $files = $query->with('model')->latest('id')->paginate(24)->withQueryString();
        $rows = $files->getCollection()->map(function ($media) use ($map) {
            [$key,$type] = $map[$media->model_type];
            $exists = null;
            try { $exists = Storage::disk($media->disk)->exists($media->getPathRelativeToRoot()); } catch (\Throwable $e) {}
            $owner = $media->model;
            return ['media'=>$media,'label'=>$type[1],'exists'=>$exists,
                'title'=>$owner?->{$type[3]} ?? 'Content unavailable',
                'edit'=>$owner ? route($type[2],$owner->id) : null,
                'image'=>in_array($media->mime_type,['image/jpeg','image/png','image/webp'],true)];
        });
        return view('admin.media.index', compact('files','rows'));
    }
}
