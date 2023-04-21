<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Dashboard')</title>

        @include('admin.layout.css')
        @yield('page-style')
    </head>

    <body class="hold-transition sidebar-mini layout-fixed">
        <div class="wrapper">

            @include('admin.layout.header')

            @include('admin.layout.sidebar')

            <!-- Content Wrapper. Contains page content -->
            <div class="content-wrapper">
                @yield('content')
            </div>

            <!-- Control Sidebar -->
            <aside class="control-sidebar control-sidebar-dark">
                <!-- Control sidebar content goes here -->
            </aside>
            <!-- /.control-sidebar -->
        </div>
        <!-- ./wrapper -->

        @include('admin.layout.footer')

        @include('admin.layout.script')

        @yield('page-script')
    </body>
</html>
