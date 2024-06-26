@extends('admin.layout.master')

@section('title', 'Tutorials')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    @include('admin.layout.flash')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Tutorials</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin">Home</a></li>
                        <li class="breadcrumb-item">Add Tutorials</li>
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
                    <div class="card-header">
                    <h3 class="card-title">Exam Papers</h3>

                    <div class="card-tools">
                        <div class="input-group input-group-sm" style="width: 300px;">
                        <input type="text" name="table_search" class="form-control float-right" placeholder="Search">

                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                            <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <a href="{{ URL::to('/admin/add-tutorial/') }}" class="btn btn-sm btn-success"><i class="nav-icon fas fa-plus"></i> Add Tutorial</a>
                        </div>
                    </div>
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Video Link</th>
                            <th>Image</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach ($tutorials as $key=>$row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->title }}</td>
                                <td>{{ $row->description }}</td>
                                <td>{{ $row->videoLink }}</td>
                                <td><img src="{{ $row->getFirstMediaUrl() }}" width="100px"></td>
                                <td>
                                    <a href="{{ URL::to('/admin/edit-tutorial/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                    <a href="{{ URL::to('/admin/delete-tutorial/'.$row->id) }}" class="btn btn-sm btn-danger"><i class="nav-icon fas fa-trash"></i> Delete</a>
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
