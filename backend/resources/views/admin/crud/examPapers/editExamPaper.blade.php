@extends('admin.layout.master')

@section('title', 'Edit Question Bank')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
<div class="ltd-panel mb-3"><span class="text-muted">Added by</span> @include('admin.crud.partials.creator', ['record' => $examPaper])</div>

    <div class="card-body">
        <p class="text-muted">Write the title and description in the selected PDF language.</p>
        <form role="form" action="{{ URL::to('/admin/update-exam-paper/'.$examPaper->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Title:</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('name')) is-invalid @endif" name="name" placeholder="Enter exam-paper name" value="{{ old('name', $examPaper->name) }}" >
                </div>
            </div>

            <div class="form-group row">
                <label for="description" class="col-sm-2 col-form-label">Description :</label>
                <div class="col-sm-10">
                    <textarea type="text" class="form-control  @if($errors->has('description')) is-invalid @endif" rows="4" name="description" placeholder="Enter exam-paper description">
                        {{ old('description', $examPaper->description) }}
                    </textarea>
                </div>
            </div>

            @if($pdf = $examPaper->pdfMedia())<p><a href="{{ $pdf->getUrl() }}" target="_blank" rel="noopener noreferrer">Open current PDF</a></p>@endif
            @include('admin.crud.examPapers.file-upload', ['editing' => true])

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
@endsection

@section('page-script')
    <script type='text/javacript'>

    </script>
@endsection
