<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;

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
        $users = User::all();
        return view('admin.crud.users.showUser', compact('users'));
    }

    //show form to add user to database
    public function addUser()
    {
        return view('admin.crud.users.addUser');
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
            'profileImage' => ['nullable', 'image'],
        ]);
        $sanitized['profileImage'] = 'demo';
        $sanitized['password'] = Hash::make($sanitized['password']);

        $user = User::create($sanitized);

        if ($request->hasFile('profileImage') && $request->profileImage != '') {
            $user->addMedia($request->profileImage)->toMediaCollection();
        } else {
            $seedAdmin = User::where('email', 'admin@admin.com')->first();
            if ($seedAdmin && $seedAdmin->getFirstMediaUrl()) {
                $user->addMediaFromUrl($seedAdmin->getFirstMediaUrl())->toMediaCollection();
            }
        }

        return redirect()->to('/admin/users')->with('success','User Added Successfully');
    }

    public function profileSettings()
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        return view('admin.profile-settings', compact('user'));
    }

    public function updateProfileSettings(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phoneNumber' => ['required', 'digits:10'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phoneNumber = $data['phoneNumber'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->to('/admin/profile-settings')->with('success', 'Profile Updated Successfully');
    }

    //show form to edit user to database
    public function editUser($id)
    {
        $edit = User::findOrFail($id);
        $currentUser = auth()->user();

        if ($edit->role === 'Admin' && $currentUser->email !== 'admin@admin.com') {
            abort(403, 'Only the seed admin can edit other admin accounts.');
        }

        return view('admin.crud.users.editUser', compact('edit'));
    }

    //update user to database
    public function updateUser(Request $request, $id)
    {
        $currentUser = auth()->user();
        $user = User::findOrFail($id);

        if ($user->role === 'Admin' && $currentUser->email !== 'admin@admin.com') {
            abort(403, 'Only the seed admin can edit other admin accounts.');
        }

        $sanitized = $request->validate([
            'name' => 'required',
            'email' => 'required',
            'phoneNumber' => 'required|digits:10',
            'role' => 'required',
        ]);

        if ($request->hasFile('profileImage') && $request->profileImage != '') {
            $user->clearMediaCollection();
            $user->addMedia($request->profileImage)->toMediaCollection();
        } elseif ($user->getMedia()->isEmpty()) {
            $seedAdmin = User::where('email', 'admin@admin.com')->first();
            if ($seedAdmin && $seedAdmin->getFirstMediaUrl()) {
                $user->addMediaFromUrl($seedAdmin->getFirstMediaUrl())->toMediaCollection();
            }
        }

        $user->update($sanitized);

        return redirect()->to('/admin/users')->with('success','User Updated Successfully');
    }

    //delete user from database
    public function deleteUser($id)
    {
        $currentUser = auth()->user();
        $user = User::findOrFail($id);

        if ($user->role === 'Admin' && $currentUser->email !== 'admin@admin.com') {
            abort(403, 'Only the seed admin can manage other admin accounts.');
        }

        $user->delete();
        return redirect()->to('/admin/users')->with('success','User Deleted Successfully');
    }
}
