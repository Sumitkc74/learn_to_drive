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
            'name' => 'required',
            'nepaliSignName' => 'required',
            'description' => 'required',
            'image' => 'required|image',
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
        return view('admin.crud.trafficSigns.editTrafficSign', compact('trafficSign'));
    }

    //update traffic sign in database
    public function updateTrafficSign(Request $request, $id)
    {
        $sanitized = $request->validate([
            'name' => 'required',
            'nepaliSignName' => 'required',
            'description' => 'required',
        ]);

        $traffic = TrafficSign::find($id);

        if ($request -> hasFile('image') && $request->image != ''){
            $traffic->clearMediaCollection();
            $traffic->addMedia($request->image)->toMediaCollection();
        }

        $traffic->update($sanitized);
        return redirect()->to('/admin/traffic-signs')->with('success','Traffic Sign Updated Successfully');
    }

    //delete user from databasec
    public function deleteTrafficSign($id)
    {
        TrafficSign::find($id)->delete();
        return redirect()->back()->with('success','Traffic Sign Deleted Successfully');
    }
}
