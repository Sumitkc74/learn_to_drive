@extends('admin.layout.master')

@section('title', 'Exam-Papers')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    @include('admin.layout.flash')

    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Exam Papers</h1>
        </div>
        <ol class="ltd-breadcrumb">
            <li><a href="/admin">Home</a></li>
            <li>Exam Papers</li>
        </ol>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="ltd-panel__title mb-0">Exam Papers</h3>
            <a href="{{ URL::to('/admin/add-exam-paper/') }}" class="btn btn-sm btn-success">
                <i class="nav-icon fas fa-plus"></i> Add Exam Paper
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Exam-paper name</th>
                    <th>Nepali name</th>
                    <th>Description</th>
                    <th>English File</th>
                    <th>Nepali File</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($examPapers as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->nepaliName }}</td>
                        <td>{{ $row->description }}</td>
                        <td><embed src="{{ $row->getFirstMediaUrl() }}" width="100px"></td>
                        <td><embed src="{{ $row->getMedia()[1]->getUrl() }}" width="100px"></td>
                        <td>
                            <a href="{{ URL::to('/admin/edit-exam-paper/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                            <form action="{{ route('deleteExamPaper', $row->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this exam paper?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('page-script')
    <script type='text/javacript'>

    </script>
@endsection
