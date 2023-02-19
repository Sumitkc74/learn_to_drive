<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamPaperController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }


    //show users from database
    public function allExamPaper()
    {
        $examPapers = DB::table('exam_papers') -> get();

        return view('admin.examPapers.showExamPaper', compact('examPapers'));
    }


    //show form to add user to database
    public function addExamPaper()
    {
        return view('admin.examPapers.addExamPaper');
    }


    //add user to database
    public function insertExamPaper(Request $request)
    {
        try{
            $data = array();
            $data['name'] = $request->name;
            $data['description'] = $request->description;
            $data['role'] = $request->role;
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $insert = DB::table('exam_papers') -> insert($data);
            if($insert){
                // return view('admin.examPapers.showExamPaper');
                // echo "Successful";
                $notification=array(
                    'messege'=>'User added successfully',
                    'alert-type' => 'success'
                );
                return redirect()->route('allExamPaper')->with($notification);
            }
            else{
                // echo "Something went wrong. Try Again!";
                $notification=array(
                    'messege'=>'Something went wrong. Try Again!',
                    'alert-type' => 'error'
                );
                return redirect()->route('allExamPaper')->with($notification);
            }
        }
        catch(e){
            return redirect()->route('allExamPaper');
        }
    }


    //show form to edit user to database
    public function editExamPaper($id)
    {
        $edit = DB::table('exam_papers')->where('id', $id)->first();
        return view('admin.examPapers.editExamPaper', compact('edit'));
    }


    //update user to database
    public function updateExamPaper(Request $request, $id)
    {
        try{
            $data = array();
            $data['name'] = $request->name;
            $data['description'] = $request->description;
            $data['role'] = $request->role;
            // $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $update = DB::table('exam_papers') ->where('id', $id)-> update($data);
            if($update){
                echo "User updated";
                redirect()->route('allExamPaper');
            }
            else{
                echo "Something went wrong. Try Again!";
                redirect()->route('allExamPaper');
            }
        }
        catch(e){
            return redirect()->route('allExamPaper');
        }
    }

    //delete user from database
    public function deleteExamPaper($id)
    {
        $delete = DB::table('exam_papers')->where('id', $id)->delete();
        if($delete){
            echo "User deleted successfully.";
            redirect()->route('allExamPaper');
        }
        else{
            echo "Something went wrong.";
            redirect()->route('allExamPaper');
        }
    }
}
