<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }


    //show users from database
    public function allUser()
    {
        $users = DB::table('users') -> get();

        return view('admin.users.showUser', compact('users'));
    }


    //show form to add user to database
    public function addUser()
    {
        return view('admin.users.addUser');
    }


    //add user to database
    public function insertUser(Request $request)
    {
        try{
            $data = array();
            $data['name'] = $request->name;
            $data['email'] = $request->email;
            $data['phoneNumber'] = $request->phoneNumber;
            $data['role'] = $request->role;
            $data['password'] = Hash::make($request->password);
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $insert = DB::table('users') -> insert($data);
            if($insert){
                // return view('admin.users.showUser');
                // echo "Successful";
                $notification=array(
                    'messege'=>'User added successfully',
                    'alert-type' => 'success'
                );
                return redirect()->route('allUser')->with($notification);
            }
            else{
                // echo "Something went wrong. Try Again!";
                $notification=array(
                    'messege'=>'Something went wrong. Try Again!',
                    'alert-type' => 'error'
                );
                return redirect()->route('allUser')->with($notification);
            }
        }
        catch(e){
            return redirect()->route('allUser');
        }
    }


    //show form to edit user to database
    public function editUser($id)
    {
        $edit = DB::table('users')->where('id', $id)->first();
        return view('admin.users.editUser', compact('edit'));
    }


    //update user to database
    public function updateUser(Request $request, $id)
    {
        try{
            $data = array();
            $data['name'] = $request->name;
            $data['email'] = $request->email;
            $data['phoneNumber'] = $request->phoneNumber;
            $data['role'] = $request->role;
            $data['password'] = Hash::make($request->password);
            // $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $update = DB::table('users') ->where('id', $id)-> update($data);
            if($update){
                // return view('admin.users.showUser');
                echo "User updated";
                redirect()->route('allUser');
            }
            else{
                echo "Something went wrong. Try Again!";
                redirect()->route('allUser');
            }
        }
        catch(e){
            return redirect()->route('allUser');
        }
    }

    //delete user from database
    public function deleteUser($id)
    {
        $delete = DB::table('users')->where('id', $id)->delete();
        if($delete){
            echo "User deleted successfully.";
            redirect()->route('allUser');
        }
        else{
            echo "Something went wrong.";
            redirect()->route('allUser');
        }
    }
}
