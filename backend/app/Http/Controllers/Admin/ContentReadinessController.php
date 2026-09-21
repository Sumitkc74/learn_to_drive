<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Services\ContentReadiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentReadinessController extends Controller
{
    public function index(Request $request, ContentReadiness $readiness)
    {
        $issue = $request->validate(['issue' => ['nullable', Rule::in(array_keys(ContentReadiness::LABELS))]])['issue'] ?? null;
        $questions = $readiness->query($issue)->with('media')->latest('id')->paginate(20)->withQueryString();
        $counts = $readiness->summary();

        return view('admin.readiness.index', compact('questions', 'counts', 'issue'));
    }

    public function verify(Request $request, Question $question, ContentReadiness $readiness)
    {
        $data = $request->validate(['confirmed' => ['accepted'], 'review_hash' => ['required', 'string', 'size:64']]);
        DB::transaction(function () use ($question, $readiness, $data) {
            $question = Question::lockForUpdate()->findOrFail($question->id);
            abort_unless($question->status === 'Draft', 409, 'Only draft questions can be verified here.');
            abort_unless(hash_equals(ContentReadiness::answerHash($question), $data['review_hash']), 409, 'This question changed after the page loaded. Reload and check the updated answer.');
            if ($readiness->query('answer')->whereKey($question->id)->exists()) {
                throw ValidationException::withMessages(['answer' => 'Complete the question and all four distinct options before verifying.']);
            }
            $question->forceFill(['answer_verified_at' => now(), 'answer_verified_by' => auth()->id(), 'answer_review_hash' => ContentReadiness::answerHash($question)])->save();
        });

        return back()->with('success','Answer marked as verified. The question remains a draft.');
    }
}
