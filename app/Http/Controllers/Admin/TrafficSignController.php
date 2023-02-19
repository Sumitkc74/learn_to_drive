<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrafficSignController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }


    //show users from database
    public function allTrafficSign()
    {
        $trafficSigns = DB::table('traffic_signs') -> get();

        return view('admin.trafficSigns.showTrafficSign', compact('trafficSigns'));
    }


    //show form to add user to database
    public function addTrafficSign()
    {
        return view('admin.trafficSigns.addTrafficSign');
    }


    //add user to database
    public function insertTrafficSign(Request $request)
    {
        try{
            $data = array();
            $data['name'] = $request->name;
            $data['description'] = $request->description;
            $data['role'] = $request->role;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $insert = DB::table('traffic_signs') -> insert($data);
            if($insert){
                // return view('admin.examPapers.showExamPaper');
                // echo "Successful";
                $notification=array(
                    'messege'=>'User added successfully',
                    'alert-type' => 'success'
                );
                return redirect()->route('allExamPaper')->with($notification);
            }
            else{
                // echo "Something went wrong. Try Again!";
                $notification=array(
                    'messege'=>'Something went wrong. Try Again!',
                    'alert-type' => 'error'
                );
                return redirect()->route('allTrafficSign')->with($notification);
            }
        }
        catch(e){
            return redirect()->route('allTrafficSign');
        }
    }


    //show form to edit user to database
    public function editTrafficSign($id)
    {
        $edit = DB::table('traffic_signs')->where('id', $id)->first();
        return view('admin.trafficSigns.editTrafficSign', compact('edit'));
    }


    //update user to database
    public function updateTrafficSign(Request $request, $id)
    {
        try{
            $data = array();
            $data['name'] = $request->name;
            $data['description'] = $request->description;
            $data['role'] = $request->role;
            // $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $update = DB::table('traffic_signs') ->where('id', $id)-> update($data);
            if($update){
                echo "User updated";
                redirect()->route('allTrafficSign');
            }
            else{
                echo "Something went wrong. Try Again!";
                redirect()->route('allTrafficSign');
            }
        }
        catch(e){
            return redirect()->route('allTrafficSign');
        }
    }

    //delete user from database
    public function deleteTrafficSign($id)
    {
        $delete = DB::table('traffic_signs')->where('id', $id)->delete();
        if($delete){
            echo "User deleted successfully.";
            redirect()->route('allTrafficSign');
        }
        else{
            echo "Something went wrong.";
            redirect()->route('allTrafficSign');
        }
    }
}
