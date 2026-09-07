<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\AppSetting;
use App\Support\AdminTable;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }

    //show questions from database
    public function allQuestion(Request $request)
    {
        $questions = AdminTable::paginate(Question::query(), $request,
            ['question', 'option1', 'option2', 'option3', 'option4', 'category', 'explanation'],
            ['id', 'question', 'category', 'difficulty', 'status', 'correctOption', 'created_at'],
            [
                'category' => ['allowed' => ['General', 'Road Signs', 'Traffic Rules', 'Road Safety', 'Vehicle Knowledge']],
                'difficulty' => ['allowed' => ['Easy', 'Medium', 'Hard']],
                'status' => ['allowed' => ['Draft', 'Published', 'Archived']],
            ]
        );
        return view('admin.crud.questions.showQuestion', compact('questions'));
    }

    //show form to add question to database
    public function addQuestion()
    {
        return view('admin.crud.questions.addQuestion');
    }

    public function trashedQuestions(Request $request)
    {
        if (!$request->query->has('sort')) {
            $request->query->set('sort', 'deleted_at');
        }

        $questions = AdminTable::paginate(
            Question::onlyTrashed(),
            $request,
            ['question', 'category', 'explanation'],
            ['id', 'question', 'category', 'difficulty', 'status', 'deleted_at']
        );

        return view('admin.crud.questions.trash', compact('questions'));
    }

    //function to add question to database
    public function insertQuestion(Request $request)
    {
        $sanitized = $request->validate($this->rules());
        unset($sanitized['image']);
        $question = Question::create($sanitized);
        if ($request->hasFile('image')) {
            $question->addMediaFromRequest('image')->toMediaCollection('question-images');
        }
        return redirect()->to('/admin/questions')->with('success','Question Added Successfully');
    }

    //show form to edit question to database
    public function editQuestion($id)
    {
        $edit = Question::findOrFail($id);
        return view('admin.crud.questions.editQuestion', compact('edit'));
    }

    //update question to database
    public function updateQuestion(Request $request, $id)
    {
        $sanitized = $request->validate($this->rules());
        unset($sanitized['image']);
        $question = Question::findOrFail($id);
        $question->update($sanitized);
        if ($request->hasFile('image')) {
            $question->clearMediaCollection('question-images');
            $question->addMediaFromRequest('image')->toMediaCollection('question-images');
        }
        return redirect()->to('/admin/questions')->with('success','Question Updated Successfully');
    }

    //delete question from database
    public function deleteQuestion($id)
    {
        Question::findOrFail($id)->delete();
        return redirect()->to('/admin/questions')->with('success','Question Deleted Successfully');
    }

    public function restoreQuestion($id)
    {
        Question::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->route('questionTrash')->with('success', 'Question restored successfully.');
    }

    public function forceDeleteQuestion($id)
    {
        Question::onlyTrashed()->findOrFail($id)->forceDelete();

        return redirect()->route('questionTrash')->with('success', 'Question permanently deleted.');
    }

    private function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:500'],
            'option1' => ['required', 'string', 'max:255'],
            'option2' => ['required', 'string', 'max:255', 'different:option1'],
            'option3' => ['required', 'string', 'max:255', 'different:option1,option2'],
            'option4' => ['required', 'string', 'max:255', 'different:option1,option2,option3'],
            'correctOption' => ['required', 'in:A,B,C,D'],
            'category' => ['required', 'in:General,Road Signs,Traffic Rules,Road Safety,Vehicle Knowledge'],
            'difficulty' => ['required', 'in:Easy,Medium,Hard'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:Draft,Published,Archived'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.AppSetting::imageLimitKb()],
        ];
    }
}
