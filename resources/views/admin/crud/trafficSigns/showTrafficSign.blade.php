@extends('admin.layout.master')

@section('title', 'Traffic Signs')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection


@section('content_header')

@endsection

@section('content')
    <!-- Content Header (Page header) -->
    @include('admin.layout.flash')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Traffic-Signs</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin">Home</a></li>
                        <li class="breadcrumb-item">Add Traffic-Signs</li>
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
                        <h3 class="card-title">Traffic-Signs</h3>

                        <div class="card-tools">
                            <div class="input-group input-group-sm" style="width: 300px;">
                            <input type="text" id="search" name="table_search" class="form-control float-right" placeholder="Search">

                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                                </button>
                            </div>

                            <a href="{{ URL::to('/admin/add-traffic-sign/') }}" class="btn btn-sm btn-success"><i class="nav-icon fas fa-plus"></i> Add Traffic Sign</a>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-header -->

                    <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Traffic-Signs</th>
                            <th>Nepali Sign-Name</th>
                            <th>Description</th>
                            <th>Image</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                            @foreach ($trafficSigns as $key=>$row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->name }}</td>
                                <td>{{ $row->nepaliSignName }}</td>
                                <td>{{ $row->description }}</td>
                                <td><img src="{{ $row->getFirstMediaUrl() }}" width="100px"></td>
                                <td>
                                    <a href="{{ URL::to('/admin/edit-traffic-sign/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                    <a href="{{ URL::to('/admin/delete-traffic-sign/'.$row->id) }}" class="btn btn-sm btn-danger"><i class="nav-icon fas fa-trash"></i> Delete</a>
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
        $(document).ready(function(){
            $('#search').keyup(function(){
                searchTable($(this).val());
            });
        });

        function searchTable(inputVal){
            var table = $('.table');
            table.find('tr').each(function(index, row){
                var allCells = $(row).find('td');
                if(allCells.length > 0){
                    var found = false;
                    allCells.each(function(index, td){
                        var regExp = new RegExp(inputVal, 'i');
                        if(regExp.test($(td).text())){
                            found = true;
                            return false;
                        }
                    });
                    if(found == true)$(row).show();else $(row).hide();
                }
            });
        }
    </script>
@endsection
