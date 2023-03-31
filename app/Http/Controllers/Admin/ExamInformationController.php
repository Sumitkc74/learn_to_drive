<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamInformation;
use Illuminate\Http\Request;

class ExamInformationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show users from database
    public function allExamInformation()
    {
        $examInformation = ExamInformation::all();
        return view('admin.crud.examInformation.showExamInformation', compact('examInformation'));
    }

    //show form to add user to database
    public function addExamInformation()
    {
        return view('admin.crud.examInformation.addExamInformation');
    }

    //add user to database
    public function insertExamInformation(Request $request)
    {
        $sanitized = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'file' => 'required',
        ]);
        $sanitized['file'] = "demo";

        $examInformation = ExamInformation::create($sanitized);
        $examInformation->addMedia($request->file)->toMediaCollection();

        return redirect()->to('/admin/exam-information')->with('success','Exam Information Added Successfully');
    }

    //show form to edit user to database
    public function editExamInformation($id)
    {
        $examInformation = ExamInformation::find($id);
        return view('admin.crud.examInformation.editExamInformation', compact('examInformation'));
    }

    //update user to database
    public function updateExamInformation(Request $request, $id)
    {
        $sanitized = $request->validate([
            'name' => 'required',
            'description' => 'required',
            // 'file' => 'required',
        ]);
        // $sanitized['file'] = "demo";

        $examInformation = ExamInformation::find($id);

        if ($request -> hasFile('file') && $request->image != ''){
            $examInformation->clearMediaCollection();
            $examInformation->addMedia($request->image)->toMediaCollection();
        }

        $examInformation->update($sanitized);
        return redirect()->to('/admin/exam-information')->with('success','Exam Information Updated Successfully');
    }

    //delete user from database
    public function deleteExamInformation($id)
    {
        ExamInformation::find($id)->delete();
        return redirect()->back()->with('success','Exam Information Deleted Successfully');
    }
}

