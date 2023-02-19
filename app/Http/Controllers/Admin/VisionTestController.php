<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $visionTests = DB::table('vision_tests') -> get();

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
        try{
            $data = array();
            $data['name'] = $request->name;
            // $data['description'] = $request->description;
            $data['role'] = $request->role;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $insert = DB::table('vision_tests') -> insert($data);
            if($insert){
                $notification=array(
                    'messege'=>'User added successfully',
                    'alert-type' => 'success'
                );
                return redirect()->route('allVisionTest')->with($notification);
            }
            else{
                // echo "Something went wrong. Try Again!";
                $notification=array(
                    'messege'=>'Something went wrong. Try Again!',
                    'alert-type' => 'error'
                );
                return redirect()->route('allVisionTest')->with($notification);
            }
        }
        catch(e){
            return redirect()->route('allVisionTest');
        }
    }


    //show form to edit user to database
    public function editVisionTest($id)
    {
        $edit = DB::table('vision_tests')->where('id', $id)->first();
        return view('admin.visionTests.editVisionTest', compact('edit'));
    }


    //update user to database
    public function updateVisionTest(Request $request, $id)
    {
        try{
            $data = array();
            $data['name'] = $request->name;
            $data['description'] = $request->description;
            $data['role'] = $request->role;
            // $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $update = DB::table('vision_tests') ->where('id', $id)-> update($data);
            if($update){
                echo "User updated";
                redirect()->route('allVisionTest');
            }
            else{
                echo "Something went wrong. Try Again!";
                redirect()->route('allVisionTest');
            }
        }
        catch(e){
            return redirect()->route('allVisionTest');
        }
    }

    //delete user from database
    public function deleteVisionTest($id)
    {
        $delete = DB::table('vision_tests')->where('id', $id)->delete();
        if($delete){
            echo "User deleted successfully.";
            redirect()->route('allVisionTest');
        }
        else{
            echo "Something went wrong.";
            redirect()->route('allVisionTest');
        }
    }
}
