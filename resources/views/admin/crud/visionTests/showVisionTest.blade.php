@extends('admin.layout.master')

@section('title', 'Vision-Tests')

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
                    <h1 class="m-0">Vision-Tests</h1>
                </div><!-- /.col -->

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin">Home</a></li>
                        <li class="breadcrumb-item">Add Vision-Test</li>
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
                        <h3 class="card-title">Vision-Tests</h3>

                        <div class="card-tools">
                            <div class="input-group input-group-sm" style="width: 300px;">
                                <input type="text" name="table_search" class="form-control float-right" placeholder="Search">

                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-default">
                                    <i class="fas fa-search"></i>
                                    </button>
                                </div>
                                <a href="{{ URL::to('/admin/add-vision-test/') }}" class="btn btn-sm btn-success">
                                    <i class="nav-icon fas fa-plus"></i>
                                    Add Test
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-header -->

                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Test-Number</th>
                                <th>Image</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach ($visionTests as $key=>$row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $row->testNumber }}</td>
                                    {{-- <td>{{ $row->description }}</td> --}}
                                    <td><img src="{{ $row->getFirstMediaUrl() }}" width="100px"></td>
                                    <td>
                                        <a href="{{ URL::to('/admin/edit-vision-test/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                        <a href="{{ URL::to('/admin/delete-vision-test/'.$row->id) }}" class="btn btn-sm btn-danger"><i class="nav-icon fas fa-trash"></i> Delete</a>
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
