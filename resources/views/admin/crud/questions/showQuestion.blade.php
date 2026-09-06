@extends('admin.layout.master')

@section('title', 'Questions')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    @include('admin.layout.flash')

    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Questions</h1>
        </div>
        <ol class="ltd-breadcrumb">
            <li><a href="/admin">Home</a></li>
            <li>Questions</li>
        </ol>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="ltd-panel__title mb-0">Exam Questions</h3>
            <a href="{{ URL::to('/admin/add-question/') }}" class="btn btn-sm btn-success">
                <i class="nav-icon fas fa-plus"></i> Add Question
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Question</th>
                        <th>Option A</th>
                        <th>Option B</th>
                        <th>Option C</th>
                        <th>Option D</th>
                        <th>Correct Option</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($questions as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->question }}</td>
                        <td>{{ $row->option1 }}</td>
                        <td>{{ $row->option2 }}</td>
                        <td>{{ $row->option3 }}</td>
                        <td>{{ $row->option4 }}</td>
                        <td>{{ $row->correctOption }}</td>
                        <td>
                            <a href="{{ URL::to('/admin/edit-question/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                            <form action="{{ route('deleteQuestion', $row->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this question?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
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
