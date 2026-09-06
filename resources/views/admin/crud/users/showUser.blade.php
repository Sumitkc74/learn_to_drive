@extends('admin.layout.master')

@section('title', 'Users')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    @include('admin.layout.flash')

    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Users</h1>
        </div>
        <ol class="ltd-breadcrumb">
            <li><a href="/admin">Home</a></li>
            <li>Users</li>
        </ol>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="ltd-panel__title mb-0">App Users</h3>
            <div class="d-flex align-items-center gap-2">
                <div class="input-group input-group-sm" style="width: 260px;">
                    <input type="text" id="search" class="form-control" placeholder="Search">
                    <div class="input-group-append">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                </div>
                <a href="{{ URL::to('/admin/add-user/') }}" class="btn btn-sm btn-success">
                    <i class="nav-icon fas fa-plus"></i> Add User
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Role</th>
                        <th>Profile Image</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->email }}</td>
                        <td>{{ $row->phoneNumber }}</td>
                        <td>{{ $row->role }}</td>
                        <td><img src="{{ $row->avatar_url }}" alt="{{ $row->name }} profile photo" width="70" height="70" style="object-fit:cover;border-radius:8px"></td>
                        <td>
                            @if(auth()->user()->email === 'admin@admin.com' || $row->role !== 'Admin')
                                <a href="{{ URL::to('/admin/edit-user/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                            @endif
                            @if(auth()->user()->email === 'admin@admin.com' || $row->role !== 'Admin')
                                <form action="{{ route('deleteUser', $row->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                                </form>
                            @endif
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
