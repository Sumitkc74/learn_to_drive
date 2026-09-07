<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show users from database
    public function allUser(Request $request)
    {
        $users = AdminTable::paginate(
            User::query(),
            $request,
            ['name', 'email', 'phoneNumber', 'role'],
            ['id', 'name', 'email', 'role', 'created_at'],
            [
                'role' => ['allowed' => ['User', 'PremiumUser', 'Admin']],
                'verification' => [
                    'allowed' => ['verified', 'email_unverified', 'phone_unverified'],
                    'apply' => function ($query, $value) {
                        if ($value === 'verified') {
                            $query->whereNotNull('email_verified_at')->whereNotNull('phone_verified_at');
                        } elseif ($value === 'email_unverified') {
                            $query->whereNull('email_verified_at');
                        } else {
                            $query->whereNull('phone_verified_at');
                        }
                    },
                ],
            ]
        );
        return view('admin.crud.users.showUser', compact('users'));
    }

    //show form to add user to database
    public function addUser()
    {
        return view('admin.crud.users.addUser');
    }

    public function showUser($id)
    {
        $user = User::with(['histories' => fn ($query) => $query->latest()])->findOrFail($id);

        if (!$user->canBeManagedBy(auth()->user())) {
            abort(403, 'You are not allowed to view this account.');
        }

        $attempts = $user->histories;
        $summary = [
            'attempts' => $attempts->count(),
            'average' => $attempts->count() ? (int) round($attempts->avg('score_percentage')) : 0,
            'best' => $attempts->max('score_percentage') ?? 0,
            'questions' => $attempts->sum('question_count'),
        ];

        return view('admin.crud.users.user-details', compact('user', 'attempts', 'summary'));
    }

    //add user to database
    public function insertUser(Request $request)
    {
        $allowedRoles = auth()->user()->is_seed_admin
            ? 'in:User,PremiumUser,Admin'
            : 'in:User,PremiumUser';

        $sanitized = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phoneNumber' => ['required', 'digits:10'],
            'role' => ['required', $allowedRoles],
            'password' => ['required', 'confirmed', Password::min(8)],
            'profileImage' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);
        $sanitized['profileImage'] = 'dist/img/avatar.png';
        $sanitized['password'] = Hash::make($sanitized['password']);

        $user = User::create($sanitized);

        if ($request->hasFile('profileImage') && $request->profileImage != '') {
            $user->addMedia($request->profileImage)->toMediaCollection();
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

    public function updateProfileName(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);
        $user->update($data);

        return back()->with('success', 'Name updated successfully.');
    }

    public function updateProfileEmail(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        if ($user->email !== $data['email']) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
            $user->save();
        }

        return back()->with('success', 'Email address updated successfully.');
    }

    public function updateProfilePhone(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'phoneNumber' => ['required', 'digits:10'],
        ]);
        if ($user->phoneNumber !== $data['phoneNumber']) {
            $user->phoneNumber = $data['phoneNumber'];
            $user->phone_verified_at = null;
            $user->phone_verification_code = null;
            $user->phone_verification_expires_at = null;
            $user->save();
        }

        return back()->with('success', 'Phone number updated successfully.');
    }

    public function updateProfilePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        auth()->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function updateProfileImage(Request $request)
    {
        $data = $request->validate([
            'profileImage' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $user = auth()->user();
        $user->clearMediaCollection();
        $user->addMedia($data['profileImage'])->toMediaCollection();

        return back()->with('success', 'Profile photo updated successfully.');
    }

    //show form to edit user to database
    public function editUser($id)
    {
        $edit = User::findOrFail($id);
        $currentUser = auth()->user();

        if (!$edit->canBeManagedBy($currentUser)) {
            abort(403, 'You are not allowed to edit this account.');
        }

        return view('admin.crud.users.editUser', compact('edit'));
    }

    //update user to database
    public function updateUser(Request $request, $id)
    {
        $currentUser = auth()->user();
        $user = User::findOrFail($id);

        if (!$user->canBeManagedBy($currentUser)) {
            abort(403, 'You are not allowed to edit this account.');
        }

        $allowedRoles = $currentUser->is_seed_admin
            ? 'in:User,PremiumUser,Admin'
            : 'in:User,PremiumUser';

        $sanitized = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phoneNumber' => 'required|digits:10',
            'role' => ['required', $allowedRoles],
        ]);

        if ($request->hasFile('profileImage') && $request->profileImage != '') {
            $user->clearMediaCollection();
            $user->addMedia($request->profileImage)->toMediaCollection();
        }

        $user->update($sanitized);

        return redirect()->to('/admin/users')->with('success','User Updated Successfully');
    }

    //delete user from database
    public function deleteUser($id)
    {
        $currentUser = auth()->user();
        $user = User::findOrFail($id);

        if (!$user->canBeManagedBy($currentUser)) {
            abort(403, 'You are not allowed to delete this account.');
        }

        $user->delete();
        return redirect()->to('/admin/users')->with('success','User Deleted Successfully');
    }
}
