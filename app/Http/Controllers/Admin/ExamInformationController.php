<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamInformation;
use App\Models\AppSetting;
use App\Support\AdminTable;
use Illuminate\Http\Request;

class ExamInformationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show users from database
    public function allExamInformation(Request $request)
    {
        $examInformation = AdminTable::paginate(ExamInformation::query(), $request,
            ['name', 'nepaliName', 'description'],
            ['id', 'name', 'nepaliName', 'created_at']
        );
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

        $examInformation = ExamInformation::create($sanitized);
        $examInformation->addMedia($englishFile)->toMediaCollection();
        $examInformation->addMedia($nepaliFile)->toMediaCollection();

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
            'nepaliName' => 'required',
            'description' => 'required',
            // 'file' => 'required',
        ]);
        // $sanitized['file'] = "demo";

        $examInformation = ExamInformation::find($id);

        if ($request -> hasFile('englishFile') && $request->image != ''){
            $examInformation->clearMediaCollection();
            $examInformation->addMedia($request->image)->toMediaCollection();
        }

        if ($request -> hasFile('nepaliFile') && $request->image != ''){
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
