@extends('admin.layout.master')

@section('title', 'Questions')

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
            <div><a href="{{ route('questionExport', ['format' => 'csv']) }}" class="btn btn-sm btn-outline-primary mr-1"><i class="fas fa-file-csv"></i> Download CSV</a><a href="{{ route('questionExport', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-primary mr-2"><i class="fas fa-file-excel"></i> Download Excel</a><a href="{{ route('questionImport') }}" class="btn btn-sm btn-primary mr-2"><i class="fas fa-file-import"></i> Import from CSV / Excel</a><a href="{{ route('questionTrash') }}" class="btn btn-sm btn-outline-secondary mr-2"><i class="fas fa-trash-restore"></i> Trash</a><a href="{{ URL::to('/admin/add-question/') }}" class="btn btn-sm btn-success">
                <i class="nav-icon fas fa-plus"></i> Add Question
            </a></div>
        </div>

        @include('admin.crud.partials.table-controls', [
            'items' => $questions,
            'sortOptions' => ['created_at' => 'Date added', 'question' => 'Question', 'category' => 'Category', 'difficulty' => 'Difficulty', 'status' => 'Status', 'correctOption' => 'Correct option', 'id' => 'ID'],
            'filters' => [
                'category' => ['label' => 'Categories', 'options' => array_combine(['General', 'Road Signs', 'Traffic Rules', 'Road Safety', 'Vehicle Knowledge'], ['General', 'Road Signs', 'Traffic Rules', 'Road Safety', 'Vehicle Knowledge'])],
                'difficulty' => ['label' => 'Difficulties', 'options' => array_combine(['Easy', 'Medium', 'Hard'], ['Easy', 'Medium', 'Hard'])],
                'status' => ['label' => 'Statuses', 'options' => array_combine(['Draft', 'Published', 'Archived'], ['Draft', 'Published', 'Archived'])],
            ],
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Question</th>
                        <th>Category</th>
                        <th>Difficulty</th>
                        <th>Correct Option</th>
                        <th>Status</th>
                        <th>Added by</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($questions as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td><div class="d-flex align-items-center">@if($row->image_url)<img src="{{ $row->image_url }}" alt="" width="52" height="52" class="rounded mr-2" style="object-fit:cover">@endif<span>{{ $row->question }}</span></div></td>
                        <td>{{ $row->category }}</td>
                        <td><span class="badge badge-{{ $row->difficulty === 'Hard' ? 'danger' : ($row->difficulty === 'Easy' ? 'success' : 'warning') }}">{{ $row->difficulty }}</span></td>
                        <td>{{ $row->correctOption }}</td>
                        <td><span class="badge badge-{{ $row->status === 'Published' ? 'success' : ($row->status === 'Draft' ? 'secondary' : 'dark') }}">{{ $row->status }}</span></td>
                        <td>@include('admin.crud.partials.creator', ['record' => $row])</td>
                        <td>
                            <a href="{{ route('previewQuestion', $row->id) }}" class="btn btn-sm btn-outline-primary"><i class="nav-icon fas fa-eye"></i> Preview</a>
                            <a href="{{ URL::to('/admin/edit-question/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                            <form action="{{ route('deleteQuestion', $row->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Move this question to Trash?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $questions])
    </div>
@endsection
