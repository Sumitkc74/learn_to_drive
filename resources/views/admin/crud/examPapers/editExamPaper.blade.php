@extends('admin.layout.master')

@section('title', 'Edit Exam Paper')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
<div class="ltd-panel mb-3"><span class="text-muted">Added by</span> @include('admin.crud.partials.creator', ['record' => $examPaper])</div>

    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/update-exam-paper/'.$examPaper->id) }}" method="post" enctype="multipart/form-data">
            @csrf
<div class="form-group"><label for="nepaliName">Nepali name</label><input id="nepaliName" class="form-control" name="nepaliName" value="{{ old('nepaliName', $examPaper->nepaliName) }}" required></div>
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Exam Paper Name :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('name')) is-invalid @endif" name="name" placeholder="Enter exam-paper name" value="{{ $examPaper->name }}" >
                </div>
            </div>

            <div class="form-group row">
                <label for="description" class="col-sm-2 col-form-label">Descriptioin :</label>
                <div class="col-sm-10">
                    <textarea type="text" class="form-control  @if($errors->has('description')) is-invalid @endif" rows="4" name="description" placeholder="Enter exam-paper description">
                        {{ $examPaper->description }}
                    </textarea>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-10 input-group">
                    <label for="englishFile" class="col-sm-2 col-form-label">English Exam File :</label>
                    <embed src="{{ $examPaper->getFirstMediaUrl() }}" height="200" width="200">
                    <div class="col-sm-10">
                        <input type="file" name="englishFile">
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-10 input-group">
                    <label for="nepaliFile" class="col-sm-2 col-form-label">Nepali Exam File :</label>
                    <embed src="{{ $examPaper->getMedia()->get(1)?->getUrl()  }}" height="200" width="200">
                    <div class="col-sm-10">
                        <input type="file" name="nepaliFile">
                    </div>
                </div>
            </div>
            <!-- /.card-body -->

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
