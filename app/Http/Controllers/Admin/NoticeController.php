<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show notices from database
    public function allNotice()
    {
        $notices = Notice::all();
        return view('admin.crud.notices.showNotices', compact('notices'));
    }

    //show form to add notice to database
    public function addNotice()
    {
        return view('admin.crud.notices.addNotice');
    }

    //add notice to database
    public function insertNotice(Request $request)
    {
        $sanitized = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'nepaliTitle' => 'required',
            'nepaliDescription' => 'required',
            'link' => 'nullable',
        ]);
        Notice::create($sanitized);
        return redirect()->to('/admin/notices')->with('success','Notice Added Successfully');
    }

    //show notice to edit user to database
    public function editNotice($id)
    {
        $notice = Notice::find($id);
        return view('admin.crud.notices.editNotice', compact('notice'));
    }

    //update notice to database
    public function updateNotice(Request $request, $id)
    {
        $sanitized = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'nepaliTitle' => 'required',
            'nepaliDescription' => 'required',
            'link' => 'nullable',
        ]);
        // $sanitized['link'] = $sanitized['link'] ?? '';
        Notice::find($id)->update($sanitized);
        return redirect()->to('/admin/notices')->with('success','Notice Updated successfully');
    }

    //delete notice from database
    public function deleteNotice($id)
    {
        Notice::find($id)->delete();
        return redirect()->back()->with('success','Notice Deleted Successfully');
    }
}
