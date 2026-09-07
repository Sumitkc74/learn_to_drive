@extends('admin.layout.master')

@section('title', 'Users')

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
                <a href="{{ URL::to('/admin/add-user/') }}" class="btn btn-sm btn-success">
                    <i class="nav-icon fas fa-plus"></i> Add User
                </a>
            </div>
        </div>

        @include('admin.crud.partials.table-controls', [
            'items' => $users,
            'sortOptions' => ['created_at' => 'Date added', 'last_login_at' => 'Last login', 'name' => 'Name', 'email' => 'Email', 'role' => 'Role', 'is_active' => 'Account status', 'id' => 'ID'],
            'filters' => [
                'role' => ['label' => 'Roles', 'options' => ['User' => 'User', 'PremiumUser' => 'Premium User', 'Admin' => 'Admin']],
                'verification' => ['label' => 'Verification', 'options' => ['verified' => 'Email and phone verified', 'email_unverified' => 'Email not verified', 'phone_unverified' => 'Phone not verified']],
                'account_status' => ['label' => 'Account status', 'options' => ['active' => 'Active', 'suspended' => 'Suspended']],
            ],
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Role</th>
                        <th>Status</th>
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
                        <td><span class="badge badge-{{ $row->is_active ? 'success' : 'danger' }}">{{ $row->is_active ? 'Active' : 'Suspended' }}</span></td>
                        <td><img src="{{ $row->avatar_url }}" alt="{{ $row->name }} profile photo" width="70" height="70" style="object-fit:cover;border-radius:8px"></td>
                        <td>
                            @if($row->canBeManagedBy(auth()->user()))
                                <a href="{{ route('showUser', $row->id) }}" class="btn btn-sm btn-outline-primary"><i class="nav-icon fas fa-eye"></i> View</a>
                                <a href="{{ URL::to('/admin/edit-user/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                <form action="{{ route('deleteUser', $row->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                                </form>
                            @else
                                @if($row->is_seed_admin)
                                    <span class="text-muted small">Managed through Profile Settings</span>
                                @else
                                    <span class="text-muted" aria-label="No actions available">&mdash;</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $users])
    </div>
@endsection
