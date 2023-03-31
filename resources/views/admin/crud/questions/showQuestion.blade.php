@extends('admin.layout.master')

@section('title', 'Questions')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    @include('admin.layout.flash')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Questions</h1>
                </div><!-- /.col -->

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin">Home</a></li>
                        <li class="breadcrumb-item">Add-Question</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col card">
                    <!-- Card-header -->
                    <div class="card-header">
                        <h3 class="card-title">Exam Questions</h3>

                        <div class="card-tools">
                            <div class="input-group input-group-sm" style="width: 300px;">
                                <input type="text" name="table_search" class="form-control float-right" placeholder="Search">
                                <div class="input-group-append"  style="padding-left: 10">
                                    <button type="submit" class="btn btn-default">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>

                                <a href="{{ URL::to('/admin/add-question/') }}" class="btn btn-sm btn-success" style="padding-left: 10">
                                    <i class="nav-icon fas fa-plus"></i>
                                    Add Question
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-header -->

                    <!-- Card-body -->
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Question</th>
                                    <th>Option 1</th>
                                    <th>Option 2</th>
                                    <th>Option 3</th>
                                    <th>Option 4</th>
                                    <th>Correct Option</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($questions as $key=>$row)
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
                                        <a href="{{ URL::to('/admin/delete-question/'.$row->id) }}" class="btn btn-sm btn-danger"><i class="nav-icon fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- /.card-body -->
                </div>
            </div>
        </div>
    </section>
@endsection

@section('page-script')
    <script type='text/javacript'>


    </script>
@endsection
