@php
    $value = fn ($field, $default = '') => old($field, $questionItem?->{$field} ?? $default);
    $categories = ['General', 'Road Signs', 'Traffic Rules', 'Road Safety', 'Vehicle Knowledge'];
@endphp
<div class="ltd-form-card__body">
    <section class="ltd-form-section">
        <h3 class="ltd-form-section__title">Question details</h3>
        <div class="form-group">
            <label class="ltd-field-label" for="question">Question text <span class="ltd-required">*</span></label>
            <textarea id="question" name="question" rows="3" maxlength="500" class="form-control @error('question') is-invalid @enderror" required>{{ $value('question') }}</textarea>
            @error('question')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="row">
            <div class="col-md-4 form-group"><label class="ltd-field-label" for="category">Category <span class="ltd-required">*</span></label><select id="category" name="category" class="form-control @error('category') is-invalid @enderror" required>@foreach($categories as $category)<option value="{{ $category }}" {{ $value('category', 'General') === $category ? 'selected' : '' }}>{{ $category }}</option>@endforeach</select>@error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4 form-group"><label class="ltd-field-label" for="difficulty">Difficulty <span class="ltd-required">*</span></label><select id="difficulty" name="difficulty" class="form-control @error('difficulty') is-invalid @enderror" required>@foreach(['Easy', 'Medium', 'Hard'] as $difficulty)<option value="{{ $difficulty }}" {{ $value('difficulty', 'Medium') === $difficulty ? 'selected' : '' }}>{{ $difficulty }}</option>@endforeach</select>@error('difficulty')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-md-4 form-group"><label class="ltd-field-label" for="status">Publication status <span class="ltd-required">*</span></label><select id="status" name="status" class="form-control @error('status') is-invalid @enderror" required>@foreach(['Draft', 'Published', 'Archived'] as $status)<option value="{{ $status }}" {{ $value('status', 'Draft') === $status ? 'selected' : '' }}>{{ $status }}</option>@endforeach</select>@error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
    </section>
    <section class="ltd-form-section">
        <h3 class="ltd-form-section__title">Answer options</h3>
        <div class="row">@foreach(['option1' => 'A', 'option2' => 'B', 'option3' => 'C', 'option4' => 'D'] as $field => $letter)<div class="col-md-6 form-group"><label class="ltd-field-label" for="{{ $field }}">Option {{ $letter }} <span class="ltd-required">*</span></label><input id="{{ $field }}" name="{{ $field }}" value="{{ $value($field) }}" maxlength="255" class="form-control @error($field) is-invalid @enderror" required>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>@endforeach</div>
        <label class="ltd-field-label" for="correctOption">Correct answer <span class="ltd-required">*</span></label>
        <select id="correctOption" name="correctOption" class="form-control @error('correctOption') is-invalid @enderror" required>@foreach(['A', 'B', 'C', 'D'] as $letter)<option value="{{ $letter }}" {{ $value('correctOption', 'A') === $letter ? 'selected' : '' }}>Option {{ $letter }}</option>@endforeach</select>
        @error('correctOption')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </section>
    <section class="ltd-form-section">
        <h3 class="ltd-form-section__title">Learning support</h3>
        <div class="form-group"><label class="ltd-field-label" for="explanation">Answer explanation</label><textarea id="explanation" name="explanation" rows="4" maxlength="2000" class="form-control @error('explanation') is-invalid @enderror" placeholder="Explain why the selected answer is correct.">{{ $value('explanation') }}</textarea>@error('explanation')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="form-group mb-0"><label class="ltd-field-label" for="image">Question image <span class="text-muted">(optional)</span></label>@if($questionItem?->image_url)<img src="{{ $questionItem->image_url }}" alt="Current question" class="d-block mb-3 rounded" style="max-width:240px;max-height:160px;object-fit:cover">@endif<input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="form-control-file @error('image') is-invalid @enderror"><small class="form-text text-muted">JPEG, PNG, or WebP; maximum 2 MB. Uploading a new image replaces the current one.</small>@error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
    </section>
</div>
