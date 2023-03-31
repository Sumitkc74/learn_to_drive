<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisionTest;
use Illuminate\Http\Request;

class VisionTestController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show users from database
    public function allVisionTest()
    {
        $visionTests = VisionTest::all();
        return view('admin.crud.visionTests.showVisionTest', compact('visionTests'));
    }

    //show form to add user to database
    public function addVisionTest()
    {
        return view('admin.crud.visionTests.addVisionTest');
    }

    //add user to database
    public function insertVisionTest(Request $request)
    {
        $sanitized = $request->validate([
            'testNumber' => 'required',
            'image' => 'required|image',
        ]);
        // $sanitized['image'] = "demo";

        $visionTest = VisionTest::create($sanitized);
        $visionTest->addMedia($request->image)->toMediaCollection();

        return redirect()->to('/admin/vision-tests')->with('success', 'Vision Test added successfully');
    }

    //show form to edit user to database
    public function editVisionTest($id)
    {
        $visionTest = VisionTest::find($id);
        return view('admin.crud.visionTests.editVisionTest', compact('visionTest'));
    }

    //update user to database
    public function updateVisionTest(Request $request, $id)
    {
        $sanitized = $request->validate([
            'testNumber' => 'required',
            // 'image' => 'required|image',
        ]);

        // $sanitized['image'] = "demo";

        $visionTest = VisionTest::find($id);

        if ($request -> hasFile('image') && $request->image != ''){
            $visionTest->clearMediaCollection();
            $visionTest->addMedia($request->image)->toMediaCollection();
        }

        $visionTest->update($sanitized);
        return redirect()->to('/admin/vision-tests')->with('success', 'Vision Test updated successfully');
    }

    //delete user from database
    public function deleteVisionTest($id)
    {
        VisionTest::find($id)->delete();
        return redirect()->back();
    }
}
