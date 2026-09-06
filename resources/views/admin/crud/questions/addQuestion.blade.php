@extends('admin.layout.master')
@section('title', 'Add Question')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Question Bank</span><h1>Add Question</h1></div></div>
<div class="ltd-form-shell">
    <form class="ltd-form-card" action="{{ route('insertQuestion') }}" method="POST" data-add-form>
        @csrf
        <div class="ltd-form-card__intro"><i class="fas fa-question"></i><div><h2>Create a practice question</h2><p>Add one clear question, four distinct answers, and identify the correct option.</p></div></div>
        <div class="ltd-form-card__body">
            <section class="ltd-form-section">
                <h3 class="ltd-form-section__title">Question</h3>
                <label class="ltd-field-label" for="question">Question text <span class="ltd-required">*</span></label>
                <textarea id="question" name="question" rows="3" maxlength="500" class="form-control @error('question') is-invalid @enderror" placeholder="What does this traffic sign indicate?" required>{{ old('question') }}</textarea>
                @error('question')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </section>
            <section class="ltd-form-section">
                <h3 class="ltd-form-section__title">Answer options</h3>
                <div class="row">
                    @foreach(['option1' => 'A', 'option2' => 'B', 'option3' => 'C', 'option4' => 'D'] as $field => $letter)
                        <div class="col-md-6 form-group">
                            <label class="ltd-field-label" for="{{ $field }}">Option {{ $letter }} <span class="ltd-required">*</span></label>
                            <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field) }}" maxlength="255" class="form-control @error($field) is-invalid @enderror" placeholder="Enter option {{ $letter }}" required>
                            @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
                <label class="ltd-field-label" for="correctOption">Correct answer <span class="ltd-required">*</span></label>
                <select id="correctOption" name="correctOption" class="form-control @error('correctOption') is-invalid @enderror" required>
                    <option value="" disabled {{ old('correctOption') ? '' : 'selected' }}>Select the correct option</option>
                    @foreach(['A', 'B', 'C', 'D'] as $letter)<option value="{{ $letter }}" {{ old('correctOption') === $letter ? 'selected' : '' }}>Option {{ $letter }}</option>@endforeach
                </select>
                @error('correctOption')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </section>
        </div>
        <div class="ltd-form-actions"><a href="{{ route('allQuestion') }}" class="btn btn-outline-secondary">Cancel</a><button class="btn btn-primary" type="submit" data-submit-button><i class="fas fa-plus mr-2"></i>Add Question</button></div>
    </form>
</div>
@endsection
@section('page-script')@include('admin.crud.partials.add-form-script')@endsection
