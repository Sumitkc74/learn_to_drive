<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrafficSign;
use App\Models\AppSetting;
use App\Support\AdminTable;
use Illuminate\Http\Request;

class TrafficSignController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show traffic signs from database
    public function allTrafficSign(Request $request)
    {
        $trafficSigns = AdminTable::paginate(TrafficSign::with('creator'), $request,
            ['name', 'nepaliSignName', 'description'],
            ['id', 'name', 'nepaliSignName', 'created_at']
        );
        return view('admin.crud.trafficSigns.showTrafficSign', compact('trafficSigns'));
    }

    //show form to add traffic sign to database
    public function addTrafficSign()
    {
        return view('admin.crud.trafficSigns.addTrafficSign');
    }

    //add traffic sign to database
    public function insertTrafficSign(Request $request)
    {
        $sanitized = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nepaliSignName' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.AppSetting::imageLimitKb()],
        ]);
        $sanitized['image'] = "demo";

        $traffic = TrafficSign::create($sanitized);
        $traffic->addMedia($request->image)->toMediaCollection();

        return redirect()->to('/admin/traffic-signs')->with('success','Traffic Sign Added Successfully');
    }


    //show form to edit traffic sign to database
    public function editTrafficSign($id)
    {
        $trafficSign = TrafficSign::findOrFail($id);
        return view('admin.crud.trafficSigns.editTrafficSign', compact('trafficSign'));
    }

    //update traffic sign in database
    public function updateTrafficSign(Request $request, $id)
    {
        $traffic = TrafficSign::findOrFail($id);
        $sanitized = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nepaliSignName' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.AppSetting::imageLimitKb()],
        ]);
        unset($sanitized['image']);
        if ($request->hasFile('image')) {
            \App\Support\ReplaceMedia::at($traffic, $request->file('image'), 0);
            $sanitized['image'] = $request->file('image')->getClientOriginalName();
        }
        $traffic->update($sanitized);
        return redirect()->route('allTrafficSign')->with('success', 'Record updated successfully.');
    }

    //delete user from databasec
    public function deleteTrafficSign($id)
    {
        TrafficSign::findOrFail($id)->delete();
        return redirect()->back()->with('success','Traffic Sign Deleted Successfully');
    }
}
