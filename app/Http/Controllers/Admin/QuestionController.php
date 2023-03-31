<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show questions from database
    public function allQuestion()
    {
        $questions = Question::all();
        return view('admin.crud.questions.showQuestion', compact('questions'));
    }

    //show form to add question to database
    public function addQuestion()
    {
        return view('admin.crud.questions.addQuestion');
    }

    //function to add question to database
    public function insertQuestion(Request $request)
    {
        $sanitized = $request->validate([
            'question' => 'required',
            'option1' => 'required',
            'option2' => 'required',
            'option3' => 'required',
            'option4' => 'required',
            'correctOption' => 'required',
        ]);
        Question::create($sanitized);
        return redirect()->to('/admin/questions')->with('success','Question Added Successfully');
    }

    //show form to edit question to database
    public function editQuestion($id)
    {
        $edit = Question::find($id);
        return view('admin.crud.questions.editQuestion', compact('edit'));
    }

    //update question to database
    public function updateQuestion(Request $request, $id)
    {
        $sanitized = $request->validate([
            'question' => 'required',
            'option1' => 'required',
            'option2' => 'required',
            'option3' => 'required',
            'option4' => 'required',
            'correctOption' => 'required',
        ]);
        Question::find($id)->update($sanitized);
        return redirect()->to('/admin/questions')->with('success','Question Updated Successfully');
    }

    //delete question from database
    public function deleteQuestion($id)
    {
        Question::find($id)->delete();
        return redirect()->to('/admin/questions')->with('success','Question Deleted Successfully');
    }
}
