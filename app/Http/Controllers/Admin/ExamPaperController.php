<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamPaper;
use Illuminate\Http\Request;

class ExamPaperController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show users from database
    public function allExamPaper()
    {
        $examPapers = ExamPaper::all();
        // $examPapers = DB::table('exam_papers') -> get();

        return view('admin.examPapers.showExamPaper', compact('examPapers'));
    }

    //show form to add user to database
    public function addExamPaper()
    {
        return view('admin.examPapers.addExamPaper');
    }

    //add user to database
    public function insertExamPaper(Request $request)
    {
        $sanitized = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'file' => 'required',
        ]);
        $sanitized['file'] = "demo";

        $examPaper = ExamPaper::create($sanitized);

        $examPaper->addMedia($request->file)->toMediaCollection();

        return redirect()->to('/admin/exam-papers')->with('success','Exam Paper Added Successfully');
    }

    //show form to edit user to database
    public function editExamPaper($id)
    {
        $examPaper = ExamPaper::find($id);
        return view('admin.examPapers.editExamPaper', compact('examPaper'));
    }

    //update user to database
    public function updateExamPaper(Request $request, $id)
    {
        $sanitized = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'file' => 'required',
        ]);
        $sanitized['file'] = "demo";

        $examPaper = ExamPaper::find($id);

        $examPaper->update($sanitized);

        $examPaper->clearMediaCollection();

        $examPaper->addMedia($request->file)->toMediaCollection();

        return redirect()->to('/admin/exam-papers')->with('success','Exam Paper Added Successfully');
    }

    //delete user from database
    public function deleteExamPaper($id)
    {
        ExamPaper::find($id)->delete();
        return redirect()->back();
    }
}
