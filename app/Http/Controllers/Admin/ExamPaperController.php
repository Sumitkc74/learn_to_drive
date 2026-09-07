<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamPaper;
use App\Support\AdminTable;
use Illuminate\Http\Request;

class ExamPaperController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show users from database
    public function allExamPaper(Request $request)
    {
        $examPapers = AdminTable::paginate(ExamPaper::query(), $request,
            ['name', 'nepaliName', 'description'],
            ['id', 'name', 'nepaliName', 'created_at']
        );
        return view('admin.crud.examPapers.showExamPaper', compact('examPapers'));
    }

    //show form to add user to database
    public function addExamPaper()
    {
        return view('admin.crud.examPapers.addExamPaper');
    }

    //add user to database
    public function insertExamPaper(Request $request)
    {
        $sanitized = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nepaliName' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'englishFile' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'nepaliFile' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);
        $englishFile = $sanitized['englishFile'];
        $nepaliFile = $sanitized['nepaliFile'];
        $sanitized['englishFile'] = $englishFile->getClientOriginalName();
        $sanitized['nepaliFile'] = $nepaliFile->getClientOriginalName();

        $examPaper = ExamPaper::create($sanitized);
        $examPaper->addMedia($englishFile)->toMediaCollection();
        $examPaper->addMedia($nepaliFile)->toMediaCollection();

        return redirect()->to('/admin/exam-papers')->with('success','Exam Paper Added Successfully');
    }

    //show form to edit user to database
    public function editExamPaper($id)
    {
        $examPaper = ExamPaper::find($id);
        return view('admin.crud.examPapers.editExamPaper', compact('examPaper'));
    }

    //update user to database
    public function updateExamPaper(Request $request, $id)
    {
        $sanitized = $request->validate([
            'name' => 'required',
            'description' => 'required',
            // 'file' => 'required',
        ]);
        // $sanitized['file'] = "demo";

        $examPaper = ExamPaper::find($id);

        if ($request -> hasFile('englishFile') && $request->image != ''){
            $examPaper->clearMediaCollection();
            $examPaper->addMedia($request->image)->toMediaCollection();
        }

        if ($request -> hasFile('nepaliFile') && $request->image != ''){
            $examPaper->clearMediaCollection();
            $examPaper->addMedia($request->image)->toMediaCollection();
        }

        $examPaper->update($sanitized);
        return redirect()->to('/admin/exam-papers')->with('success','Exam Paper Updated Successfully');
    }

    //delete user from database
    public function deleteExamPaper($id)
    {
        ExamPaper::find($id)->delete();
        return redirect()->back()->with('success','Exam Paper Deleted Successfully');
    }
}
