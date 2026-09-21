<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{ExamInformation, AppSetting};
use App\Support\AdminTable;
use Illuminate\Http\Request;
class ExamInformationController extends Controller
{
    public function allExamInformation(Request $request)
    {
        $examInformation = AdminTable::paginate(ExamInformation::with(['creator', 'media']), $request,
            ['name','description','language'], ['id','name','language','created_at'],
            ['language'=>['allowed'=>['English','Nepali']]]);
        return view('admin.crud.examInformation.showExamInformation', compact('examInformation'));
    }
    public function addExamInformation() { return view('admin.crud.examInformation.addExamInformation'); }
    public function editExamInformation($id)
    {
        $examInformation = ExamInformation::findOrFail($id);
        return view('admin.crud.examInformation.editExamInformation', compact('examInformation'));
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
    public function insertExamInformation(Request $request)
    {
        $data = $this->validated($request, true);
        $file = $data['pdf']; unset($data['pdf']);
        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $file) {
            $paper = ExamInformation::create($data);
            $paper->replacePdf($file);
        });
        return redirect()->route('allExamInformation')->with('success','Exam information added successfully.');
    }
    public function updateExamInformation(Request $request, $id)
    {
        $paper = ExamInformation::findOrFail($id);
        $data = $this->validated($request, false);
        $file = $data['pdf'] ?? null; unset($data['pdf']);
        $paper->fill($data);
        if ($file) $paper->replacePdf($file);
        $paper->save();
        if ($media = $paper->pdfMedia()) {
            $media->setCustomProperty('language', $paper->language)->save();
        }
        return redirect()->route('allExamInformation')->with('success','Exam information updated successfully.');
    }
    public function deleteExamInformation($id)
    {
        ExamInformation::findOrFail($id)->delete();
        return redirect()->back()->with('success','Exam information deleted successfully.');
    }
}
