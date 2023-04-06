@extends('admin.layout.master')

@section('title', 'Edit Exam Paper')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <div class="card-body">
        <form role="form" action="{{ URL::to('/admin/update-exam-information/'.$examInformation->id) }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-group row">
                <label for="name" class="col-sm-2 col-form-label">Exam Information Title :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('name')) is-invalid @endif" name="name" placeholder="Enter exam-paper name" value="{{ $examInformation->name }}" >
                </div>
            </div>

            <div class="form-group row">
                <label for="nepaliName" class="col-sm-2 col-form-label">Nepali Title :</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control @if($errors->has('nepaliName')) is-invalid @endif" name="nepaliName" placeholder="Enter nepali name" value="{{ $examInformation->nepaliName }}" >
                </div>
            </div>

            <div class="form-group row">
                <label for="description" class="col-sm-2 col-form-label">Descriptioin :</label>
                <div class="col-sm-10">
                    <textarea type="text" class="form-control  @if($errors->has('description')) is-invalid @endif" rows="4" name="description" placeholder="Enter exam-paper description">
                        {{ $examInformation->description }}
                    </textarea></textarea>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-10 input-group">
                    <label for="englishFile" class="col-sm-2 col-form-label">English File :</label>
                    <embed src="{{ $examInformation->getFirstMediaUrl() }}" height="200" width="200">
                    <div class="col-sm-10">
                        <input type="file" name="englishFile">
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-sm-10 input-group">
                    <label for="nepaliFile" class="col-sm-2 col-form-label">Nepali File :</label>
                    <embed src="{{ $examInformation->getMedia()[1]->getUrl()  }}" height="200" width="200">
                    <div class="col-sm-10">
                        <input type="file" name="nepaliFile">
                    </div>
                </div>
            </div>

            {{-- <div class="form-check">
                <input type="checkbox" class="form-check-input" id="exampleCheck1">
                <label class="form-check-label" for="exampleCheck1">Check me out</label>
            </div> --}}
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
