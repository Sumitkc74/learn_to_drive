@extends('admin.layout.master')

@section('title', 'Traffic Signs')


@section('content_header')

@endsection

@section('content')
    @include('admin.layout.flash')

    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Traffic Signs</h1>
        </div>
        <ol class="ltd-breadcrumb">
            <li><a href="/admin">Home</a></li>
            <li>Traffic Signs</li>
        </ol>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="ltd-panel__title mb-0">Traffic Signs</h3>
            <a href="{{ URL::to('/admin/add-traffic-sign/') }}" class="btn btn-sm btn-success">
                <i class="nav-icon fas fa-plus"></i> Add Traffic Sign
            </a>
        </div>

        @include('admin.crud.partials.table-controls', [
            'items' => $trafficSigns,
            'sortOptions' => ['created_at' => 'Date added', 'name' => 'English name', 'nepaliSignName' => 'Nepali name', 'id' => 'ID'],
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Traffic Sign</th>
                    <th>Nepali Sign Name</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($trafficSigns as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->nepaliSignName }}</td>
                        <td>{{ $row->description }}</td>
                        <td><img src="{{ $row->getFirstMediaUrl() }}" width="70" height="70" style="object-fit:cover;border-radius:8px"></td>
                        <td>
                            <a href="{{ URL::to('/admin/edit-traffic-sign/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                            <form action="{{ route('deleteTrafficSign', $row->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this traffic sign?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $trafficSigns])
    </div>
@endsection
