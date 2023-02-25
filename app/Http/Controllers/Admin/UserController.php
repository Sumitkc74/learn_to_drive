<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $sanitized = $request->validate([
            'name' => 'required',
            'email' => 'required',
            'phoneNumber' => 'required|digits:10',
            'role' => 'required',
            'password' => 'required',
            // 'image' => 'required',
        ]);
        // $sanitized['image'] = "demo";

        $sanitized['password'] = Hash::make($sanitized['password']);

        $user = User::create($sanitized);

        // $user->addMedia($request->image)->toMediaCollection();

        return redirect()->to('/admin/users')->with('success','User Added Successfully');
    }


    //show form to edit user to database
    public function editUser($id)
    {
        $edit = User::find($id);
        return view('admin.users.editUser', compact('edit'));
    }


    //update user to database
    public function updateUser(Request $request, $id)
    {
        $sanitized = $request->validate([
            'name' => 'required',
            'email' => 'required',
            'phoneNumber' => 'required|digits:10',
            'role' => 'required',
            // 'password' => 'required',
            // 'image' => 'required',
        ]);


        $user = User::find($id)->update($sanitized);


        return redirect()->to('/admin/users')->with('success','User Updated Successfully');

        // try{
        //     $data = array();
        //     $data['name'] = $request->name;
        //     $data['email'] = $request->email;
        //     $data['phoneNumber'] = $request->phoneNumber;
        //     $data['role'] = $request->role;
        //     // $data['password'] = Hash::make($request->password);
        //     // $data['created_at'] = date('Y-m-d H:i:s');
        //     $data['updated_at'] = date('Y-m-d H:i:s');



        //     $update = DB::table('users') ->where('id', $id)-> update($data);
        //     if($update){
        //         return redirect()->to('/admin/users')->with('success','User Updated Successfully');
        //     }
        //     else{
        //         echo "Something went wrong. Try Again!";
        //         redirect()->route('allUser');
        //     }
        // }
        // catch(e){
        //     return redirect()->route('allUser');
        // }
    }

    //delete user from database
    public function deleteUser($id)
    {
        User::find($id)->delete();
        return redirect()->to('/admin/users')->with('success','User Deleted Successfully');

        // $delete = DB::table('users')->where('id', $id)->delete();
        // if($delete){
        //     echo "User deleted successfully.";
        //     redirect()->route('allUser');
        // }
        // else{
        //     echo "Something went wrong.";
        //     redirect()->route('allUser');
        // }
    }
}
