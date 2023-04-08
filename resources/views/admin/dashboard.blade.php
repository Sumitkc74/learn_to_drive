@extends('admin.layout.master')

@section('title', 'Admin Dashboard')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
          <div class="row mb-2">
            <div class="col-sm-6">
              <h1 class="m-0">Dashboard</h1>
            </div><!-- /.col -->
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="/admin">Home</a></li>
                <li class="breadcrumb-item">Dashboard</li>
              </ol>
            </div><!-- /.col -->
          </div><!-- /.row -->
        </div><!-- /.container-fluid -->
      </div>
      <!-- /.content-header -->

      <!-- Main content -->
      <section class="content">
        <div class="container-fluid">

          <!-- Small boxes (Stat box) -->
          @include('admin.dashboard-components.stat-boxes')
          <!-- Main row -->
          <div class="row">
            <!-- Left col -->
            {{-- <section class="col-lg-7 connectedSortable">

                <!-- TO DO List -->
                @include('admin.dashboard-components.to-do-list')

                <!-- Total downloads chart -->
                @include('admin.dashboard-components.downloads-chart')

            </section>
            <!-- /.Left col -->

            <!-- right col (We are only adding the ID to make the widgets sortable)-->
            <section class="col-lg-5 connectedSortable">

                @include('admin.dashboard-components.calendar')

                @include('admin.dashboard-components.sales-graph')

                @include('admin.dashboard-components.map')

            </section> --}}
            <!-- right col -->
            </div>
          <!-- /.row (main row) -->
        </div><!-- /.container-fluid -->
      </section>
      <!-- /.content -->
@endsection

@section('page-script')
    <script type='text/javacript'>

    </script>
@endsection
