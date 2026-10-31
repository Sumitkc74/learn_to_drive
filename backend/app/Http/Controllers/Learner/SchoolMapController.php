<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\DrivingSchool;
use Illuminate\Http\Request;

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

        return response()->view('learner.schools', compact('schools', 'search'))
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
