<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Support\AdminTable;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show notices from database
    public function allNotice(Request $request)
    {
        $notices = AdminTable::paginate(Notice::with('creator'), $request,
            ['title', 'description', 'nepaliTitle', 'nepaliDescription'],
            ['id', 'title', 'nepaliTitle', 'status', 'publish_at', 'expires_at', 'created_at'],
            ['status' => ['allowed' => ['Draft', 'Published', 'Archived']]]
        );
        return view('admin.crud.notices.showNotices', compact('notices'));
    }

    //show form to add notice to database
    public function addNotice()
    {
        return view('admin.crud.notices.addNotice');
    }

    public function trashedNotices(Request $request)
    {
        if (!$request->query->has('sort')) {
            $request->query->set('sort', 'deleted_at');
        }

        $notices = AdminTable::paginate(
            Notice::onlyTrashed(),
            $request,
            ['title', 'description', 'nepaliTitle', 'nepaliDescription'],
            ['id', 'title', 'nepaliTitle', 'status', 'deleted_at']
        );

        return view('admin.crud.notices.trash', compact('notices'));
    }

    //add notice to database
    public function insertNotice(Request $request)
    {
        $sanitized = $request->validate($this->rules());
        Notice::create($sanitized);
        return redirect()->to('/admin/notices')->with('success','Notice Added Successfully');
    }

    //show notice to edit user to database
    public function editNotice($id)
    {
        $notice = Notice::findOrFail($id);
        return view('admin.crud.notices.editNotice', compact('notice'));
    }

    //update notice to database
    public function updateNotice(Request $request, $id)
    {
        $sanitized = $request->validate($this->rules());
        Notice::findOrFail($id)->update($sanitized);
        return redirect()->to('/admin/notices')->with('success','Notice Updated successfully');
    }

    //delete notice from database
    public function deleteNotice($id)
    {
        Notice::findOrFail($id)->delete();
        return redirect()->back()->with('success','Notice Deleted Successfully');
    }

    public function restoreNotice($id)
    {
        Notice::onlyTrashed()->findOrFail($id)->restore();
        return redirect()->route('noticeTrash')->with('success', 'Notice restored successfully.');
    }

    public function forceDeleteNotice($id)
    {
        Notice::onlyTrashed()->findOrFail($id)->forceDelete();
        return redirect()->route('noticeTrash')->with('success', 'Notice permanently deleted.');
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'nepaliTitle' => ['required', 'string', 'max:255'],
            'nepaliDescription' => ['required', 'string', 'max:1000'],
            'link' => ['nullable', 'url:http,https', 'max:2048'],
            'status' => ['required', 'in:Draft,Published,Archived'],
            'publish_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
