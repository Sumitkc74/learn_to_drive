<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisionTest;
use App\Models\AppSetting;
use App\Support\AdminTable;
use Illuminate\Http\Request;

class VisionTestController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show users from database
    public function allVisionTest(Request $request)
    {
        $visionTests = AdminTable::paginate(VisionTest::with('creator'), $request,
            ['testNumber'],
            ['id', 'testNumber', 'created_at']
        );
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
            'testNumber' => ['required', 'integer', 'min:1', 'unique:vision_tests,testNumber'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.AppSetting::imageLimitKb()],
        ]);
        $image = $sanitized['image'];
        $sanitized['image'] = $image->getClientOriginalName();

        $visionTest = VisionTest::create($sanitized);
        $visionTest->addMedia($image)->toMediaCollection();

        return redirect()->to('/admin/vision-tests')->with('success', 'Vision Test added successfully');
    }

    //show form to edit user to database
    public function editVisionTest($id)
    {
        $visionTest = VisionTest::findOrFail($id);
        return view('admin.crud.visionTests.editVisionTest', compact('visionTest'));
    }

    //update user to database
    public function updateVisionTest(Request $request, $id)
    {
        $visionTest = VisionTest::findOrFail($id);
        $sanitized = $request->validate([
            'testNumber' => ['required', 'integer', 'min:1', \Illuminate\Validation\Rule::unique('vision_tests', 'testNumber')->ignore($visionTest->id)],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.AppSetting::imageLimitKb()],
        ]);
        unset($sanitized['image']);
        if ($request->hasFile('image')) {
            \App\Support\ReplaceMedia::at($visionTest, $request->file('image'), 0);
            $sanitized['image'] = $request->file('image')->getClientOriginalName();
        }
        $visionTest->update($sanitized);
        return redirect()->route('allVisionTest')->with('success', 'Record updated successfully.');
    }

    //delete user from database
    public function deleteVisionTest($id)
    {
        VisionTest::findOrFail($id)->delete();
        return redirect()->back();
    }

    // Delete the selected vision tests (bulk delete from the list). Reuses
    // the same delete as the per-row Delete button.
    public function bulkDeleteVisionTests(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:50', 'distinct'],
            'ids.*' => ['integer', 'exists:vision_tests,id'],
        ]);

        foreach ($data['ids'] as $id) {
            VisionTest::findOrFail($id)->delete();
        }

        return redirect()->back()->with('success', trans_choice(':count vision test deleted.|:count vision tests deleted.', count($data['ids']), ['count' => count($data['ids'])]));
    }
}
