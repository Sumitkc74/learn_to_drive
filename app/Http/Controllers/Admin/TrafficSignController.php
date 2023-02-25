<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrafficSign;
use Illuminate\Http\Request;

class TrafficSignController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }


    //show traffic signs from database
    public function allTrafficSign()
    {
        $trafficSigns = TrafficSign::all();

        return view('admin.trafficSigns.showTrafficSign', compact('trafficSigns'));
    }


    //show form to add traffic sign to database
    public function addTrafficSign()
    {
        return view('admin.trafficSigns.addTrafficSign');
    }


    //add traffic sign to database
    public function insertTrafficSign(Request $request)
    {
        $sanitized = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'image' => 'required',
        ]);
        $sanitized['image'] = "demo";

        $traffic = TrafficSign::create($sanitized);

        $traffic->addMedia($request->image)->toMediaCollection();

        return redirect()->to('/admin/traffic-signs')->with('success','Traffic Sign Added Successfully');
    }


    //show form to edit traffic sign to database
    public function editTrafficSign($id)
    {
        $trafficSign = TrafficSign::find($id);

        return view('admin.trafficSigns.editTrafficSign', compact('trafficSign'));
    }


    //update traffic sign in database
    public function updateTrafficSign(Request $request, $id)
    {
        $sanitized = $request->validate([
            'name' => 'required',
            'description' => 'required',
            'image' => 'required',
        ]);

        $traffic = TrafficSign::find($id)->update($sanitized);

        $traffic->addMedia($request->image)->toMediaCollection();

        return redirect()->to('/admin/traffic-signs')->with('success','Traffic Sign Updated Successfully');
    }

    //delete user from database
    public function deleteTrafficSign($id)
    {
        TrafficSign::find($id)->delete();
        return redirect()->back();
    }
}
