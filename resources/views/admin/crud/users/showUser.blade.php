@extends('admin.layout.master')

@section('title', 'Users')

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
                    <h1 class="m-0">Users</h1>
                </div><!-- /.col -->

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin">Home</a></li>
                        <li class="breadcrumb-item">Add-User</li>
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
                        <h3 class="card-title">App Users</h3>

                        <div class="card-tools">
                            <div class="input-group input-group-sm" style="width: 300px;">
                                <input type="text" id="search" name="table_search" class="form-control float-right" placeholder="Search">
                                <div class="input-group-append"  style="padding-left: 10">
                                    <button type="submit" class="btn btn-default">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>

                                <a href="{{ URL::to('/admin/add-user/') }}" class="btn btn-sm btn-success" style="padding-left: 10">
                                    <i class="nav-icon fas fa-plus"></i>
                                    Add User
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
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone Number</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($users as $key=>$row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $row->name }}</td>
                                    <td>{{ $row->email }}</td>
                                    <td>{{ $row->phoneNumber }}</td>
                                    <td>{{ $row->role }}</td>
                                    <td>
                                        <a href="{{ URL::to('/admin/edit-user/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                        <a href="{{ URL::to('/admin/delete-user/'.$row->id) }}" class="btn btn-sm btn-danger"><i class="nav-icon fas fa-trash"></i> Delete</a>
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
