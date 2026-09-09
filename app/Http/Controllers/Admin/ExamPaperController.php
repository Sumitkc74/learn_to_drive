<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamPaper;
use App\Models\AppSetting;
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
        $examPapers = AdminTable::paginate(ExamPaper::with('creator'), $request,
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
            'englishFile' => ['required', 'file', 'mimes:pdf', 'max:'.AppSetting::documentLimitKb()],
            'nepaliFile' => ['required', 'file', 'mimes:pdf', 'max:'.AppSetting::documentLimitKb()],
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
        $examPaper = ExamPaper::findOrFail($id);
        return view('admin.crud.examPapers.editExamPaper', compact('examPaper'));
    }

    //update user to database
    public function updateExamPaper(Request $request, $id)
    {
        $examPaper = ExamPaper::findOrFail($id);
        $sanitized = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nepaliName' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'englishFile' => ['nullable', 'file', 'mimes:pdf', 'max:'.AppSetting::documentLimitKb()],
            'nepaliFile' => ['nullable', 'file', 'mimes:pdf', 'max:'.AppSetting::documentLimitKb()],
        ]);
        unset($sanitized['englishFile']);
        if ($request->hasFile('englishFile')) {
            \App\Support\ReplaceMedia::at($examPaper, $request->file('englishFile'), 0);
            $sanitized['englishFile'] = $request->file('englishFile')->getClientOriginalName();
        }
        unset($sanitized['nepaliFile']);
        if ($request->hasFile('nepaliFile')) {
            \App\Support\ReplaceMedia::at($examPaper, $request->file('nepaliFile'), 1);
            $sanitized['nepaliFile'] = $request->file('nepaliFile')->getClientOriginalName();
        }
        $examPaper->update($sanitized);
        return redirect()->route('allExamPaper')->with('success', 'Record updated successfully.');
    }

    //delete user from database
    public function deleteExamPaper($id)
    {
        ExamPaper::findOrFail($id)->delete();
        return redirect()->back()->with('success','Exam Paper Deleted Successfully');
    }
}
