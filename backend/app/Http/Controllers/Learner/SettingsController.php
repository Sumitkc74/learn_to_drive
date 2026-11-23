<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function edit(Request $request)
    {
        return view('learner.settings', ['user' => $request->user()]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phoneNumber' => ['required', 'digits:10'],
            'current_password' => [Rule::requiredIf($request->input('email') !== $user->email), 'nullable', 'current_password:web'],
        ]);
        $user->fill(collect($data)->only(['name', 'email', 'phoneNumber'])->all());
        if ($user->isDirty('email')) {
            $user->forceFill(['email_verified_at' => null]);
        }
        if ($user->isDirty('phoneNumber')) {
            $user->forceFill(['phone_verified_at' => null, 'phone_verification_code' => null, 'phone_verification_expires_at' => null]);
        }
        $user->save();

        return redirect()->route('learn.settings')->with('success', __('Your profile has been updated.'));
    }

    public function detail(Request $request, string $field)
    {
        abort_unless(in_array($field,['name','email','phoneNumber']),404);
        $user=$request->user();
        $rules=match($field){
            'name'=>['name'=>['required','string','max:100']],
            'phoneNumber'=>['phoneNumber'=>['required','digits:10']],
            'email'=>['email'=>['required','email','max:255',Rule::unique('users')->ignore($user->id)],'current_password'=>['required','current_password:web']],
        };
        $data=$request->validate($rules);
        $user->fill([$field=>$data[$field]]);
        if($user->isDirty('email')) $user->forceFill(['email_verified_at'=>null]);
        if($user->isDirty('phoneNumber')) $user->forceFill(['phone_verified_at'=>null,'phone_verification_code'=>null,'phone_verification_expires_at'=>null]);
        $user->save();
        return redirect()->route('learn.settings')->with('success',$field==='email'?__('Email updated. Verify your new address below.'):__('Your details have been updated.'));
    }

    public function avatar(Request $request)
    {
        $user = $request->user();
        $presets = ['avatar-1', 'avatar-2', 'avatar-3', 'avatar-4', 'avatar-5', 'avatar-6'];
        $data = $request->validate([
            'preset' => ['nullable', 'string', Rule::in($presets)],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.AppSetting::imageLimitKb()],
        ]);
        $hasPreset = ! empty($data['preset']);
        $hasPhoto = $request->hasFile('photo');
        if ($hasPreset === $hasPhoto) {
            return back()->withErrors(['avatar' => __('Choose a preset avatar or upload your own photo, but not both.')]);
        }
        $user->clearMediaCollection();
        if ($hasPreset) {
            // preservingOriginal: the preset file is shared by every user, so
            // it must be copied into the media library, not moved away.
            $user->addMedia(public_path('dist/img/avatars/'.$data['preset'].'.svg'))->preservingOriginal()->toMediaCollection();
        } else {
            $user->addMedia($data['photo'])->toMediaCollection();
        }

        return redirect()->route('learn.settings')->with('success', __('Your profile photo has been updated.'));
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)],
        ]);
        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();
        $request->user()->tokens()->delete();
        $request->session()->regenerate();

        return redirect()->route('learn.settings')->with('success', __('Your password has been changed. Use it the next time you sign in.'));
    }
}
