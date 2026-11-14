<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\{DrivingSchool, SchoolJoinRequest};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SchoolMapController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['q'] ?? '');
        $schools = DrivingSchool::where('status', 'Approved')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$search.'%')->orWhere('address', 'like', '%'.$search.'%')))
            ->orderBy('name')->orderBy('id')->paginate(24, ['id', 'name', 'address', 'phone', 'latitude', 'longitude'])
            ->withQueryString();
        $memberSchoolIds = $request->user()?->schoolMemberships()->pluck('driving_school_id')->all() ?? [];

        return response()->view('learner.schools', compact('schools', 'search', 'memberSchoolIds'))
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function joinRequest(Request $request, int $school)
    {
        $school = DrivingSchool::whereKey($school)->where('status', 'Approved')->firstOrFail();
        $user = $request->user();
        if ($user->schoolMemberships()->where('driving_school_id', $school->id)->exists())
            throw ValidationException::withMessages(['school' => __('You are already a member of this school.')]);
        if (SchoolJoinRequest::where('driving_school_id', $school->id)->where('user_id', $user->id)->where('status', 'Pending')->exists())
            throw ValidationException::withMessages(['school' => __('You already have a pending request to join this school.')]);
        SchoolJoinRequest::create(['driving_school_id' => $school->id, 'user_id' => $user->id, 'status' => 'Pending']);
        return $request->expectsJson()
            ? response()->json(['message' => __('Join request sent. The school will review it.')])
            : back()->with('success', __('Join request sent. The school will review it.'));
    }
}
