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
        $edit = VisionTest::find($id);
        return view('admin.visionTests.editVisionTest', compact('edit'));
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

        $visionTest = VisionTest::find($id)->update($sanitized);

        $visionTest->addMedia($request->image)->toMediaCollection();

        return redirect()->to('/admin/vision-tests')->with('success', 'Vision Test updated successfully');

        // try{
        //     $data = array();
        //     $data['name'] = $request->name;
        //     $data['description'] = $request->description;
        //     $data['role'] = $request->role;
        //     // $data['created_at'] = date('Y-m-d H:i:s');
        //     $data['updated_at'] = date('Y-m-d H:i:s');

        //     $update = DB::table('vision_tests') ->where('id', $id)-> update($data);
        //     if($update){
        //         echo "User updated";
        //         redirect()->route('allVisionTest');
        //     }
        //     else{
        //         echo "Something went wrong. Try Again!";
        //         redirect()->route('allVisionTest');
        //     }
        // }
        // catch(e){
        //     return redirect()->route('allVisionTest');
        // }
    }

    //delete user from database
    public function deleteVisionTest($id)
    {
        VisionTest::find($id)->delete();
        return redirect()->back();
    }
}
