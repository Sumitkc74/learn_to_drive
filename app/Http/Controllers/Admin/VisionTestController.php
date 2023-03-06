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

        return view('admin.visionTests.showVisionTest', compact('visionTests'));
    }

    //show form to add user to database
    public function addVisionTest()
    {
        return view('admin.visionTests.addVisionTest');
    }

    //add user to database
    public function insertVisionTest(Request $request)
    {
        $sanitized = $request->validate([
            'number' => 'required',
            // 'description' => 'required',
            'image' => 'required',
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
        return view('admin.visionTests.editVisionTest', compact('visionTest'));
    }

    //update user to database
    public function updateVisionTest(Request $request, $id)
    {
        $sanitized = $request->validate([
            'name' => 'required',
            // 'description' => 'required',
            'image' => 'required',
        ]);

        // $sanitized['image'] = "demo";

        $visionTest = VisionTest::find($id);

        $visionTest->update($sanitized);

        $visionTest->clearMediaCollection();

        $visionTest->addMedia($request->image)->toMediaCollection();

        return redirect()->to('/admin/vision-tests')->with('success', 'Vision Test updated successfully');
    }

    //delete user from database
    public function deleteVisionTest($id)
    {
        VisionTest::find($id)->delete();
        return redirect()->back();
    }
}
