<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{ExamPaper, AppSetting};
use App\Support\AdminTable;
use Illuminate\Http\Request;
class ExamPaperController extends Controller
{
    public function allExamPaper(Request $request)
    {
        $examPapers = AdminTable::paginate(ExamPaper::with(['creator', 'media']), $request,
            ['name','description','language'], ['id','name','language','created_at'],
            ['language'=>['allowed'=>['English','Nepali']]]);
        return view('admin.crud.examPapers.showExamPaper', compact('examPapers'));
    }
    public function addExamPaper() { return view('admin.crud.examPapers.addExamPaper'); }
    public function editExamPaper($id)
    {
        $examPaper = ExamPaper::findOrFail($id);
        return view('admin.crud.examPapers.editExamPaper', compact('examPaper'));
    }
    private function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'name'=>['required','string','max:255', new \App\Rules\DocumentLanguage($request->input('language'))],
            'description'=>['required','string','max:1000', new \App\Rules\DocumentLanguage($request->input('language'))], 'language'=>['required','in:English,Nepali'],
            'pdf'=>[$creating ? 'required' : 'nullable','file','mimes:pdf','max:'.AppSetting::documentLimitKb()],
            'englishFile'=>['prohibited'], 'nepaliFile'=>['prohibited'],
        ]);
    }
    public function insertExamPaper(Request $request)
    {
        $data = $this->validated($request, true);
        $file = $data['pdf']; unset($data['pdf']);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $file) {
            $paper = ExamPaper::create($data);
            $paper->replacePdf($file);
        });
        return redirect()->route('allExamPaper')->with('success','Question bank added successfully.');
    }
    public function updateExamPaper(Request $request, $id)
    {
        $paper = ExamPaper::findOrFail($id);
        $data = $this->validated($request, false);
        $file = $data['pdf'] ?? null; unset($data['pdf']);
        $paper->fill($data);
        if ($file) $paper->replacePdf($file);
        $paper->save();
        if ($media = $paper->pdfMedia()) {
            $media->setCustomProperty('language', $paper->language)->save();
        }
        return redirect()->route('allExamPaper')->with('success','Question bank updated successfully.');
    }
    public function deleteExamPaper($id)
    {
        ExamPaper::findOrFail($id)->delete();
        return redirect()->back()->with('success','Question bank deleted successfully.');
    }
}
