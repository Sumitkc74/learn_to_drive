<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tutorial;
use App\Models\AppSetting;
use App\Support\AdminTable;
use Illuminate\Http\Request;

class TutorialController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show tutorials from database
    public function allTutorial(Request $request)
    {
        $tutorials = AdminTable::paginate(Tutorial::query(), $request,
            ['title', 'description', 'videoLink'],
            ['id', 'title', 'created_at']
        );
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'videoLink' => ['required', 'url:http,https', 'max:2048'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.AppSetting::imageLimitKb()],
        ]);

        $image = $sanitized['image'];
        unset($sanitized['image']);

        $tutorial = Tutorial::create($sanitized);
        $tutorial->addMedia($image)->toMediaCollection();

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
