<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use Illuminate\Http\Request;

class TutorialController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show tutorials from database
    public function allTutorial()
    {
        $tutorials = Tutorial::all();
        return view('admin.crud.tutorials.showTutorial', compact('tutorials'));
    }

    //show form to add tutorial to database
    public function addTutorial()
    {
        return view('admin.crud.tutorials.addTutorial');
    }

    //add tutorial to database
    public function insertTutorial(Request $request)
    {
        $sanitized = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'videoLink' => 'required',
        ]);

        $tutorial = Tutorial::create($sanitized);
        $tutorial->addMedia($request->image)->toMediaCollection();

        return redirect()->to('/admin/tutorials')->with('success','Tutorial Added Successfully');
    }

    //show tutorial to edit user to database
    public function editTutorial($id)
    {
        $tutorial = Tutorial::find($id);
        return view('admin.crud.tutorials.editTutorial', compact('tutorial'));
    }

    //update tutorial to database
    public function updateTutorial(Request $request, $id)
    {
        $sanitized = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'videoLink' => 'required',
        ]);

        Tutorial::find($id)->update($sanitized);
        return redirect()->to('/admin/tutorials')->with('success','Tutorial Updated successfully');
    }

    //delete tutorial from database
    public function deleteTutorial($id)
    {
        Tutorial::find($id)->delete();
        return redirect()->back()->with('success','Tutorial Deleted Successfully');
    }
}
