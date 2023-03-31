<div class="row">

    <!-- Users Box -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-warning">
            <div class="inner">
                <h3> {{ \App\Models\User::all()->count() }} </h3>
                <p>User Registrations</p>
            </div>
            <div class="icon">
                <i class="ion ion-person-add"></i>
            </div>
            <a href="/admin/users" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <!-- Users Box -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-info">
            <div class="inner">
                <h3> {{ \App\Models\TrafficSign::all()->count() }} </h3>
                <p>Traffic Signs</p>
            </div>
            <div class="icon">
                <i class="fas fa-map-signs"></i>
            </div>
            <a href="/admin/traffic-signs" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <!-- Users Box -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-success">
            <div class="inner">
                <h3> {{ \App\Models\ExamPaper::all()->count() }} </h3>
                <p>Exam Papers</p>
            </div>
            <div class="icon">
                <i class="fas fa-file"></i>
            </div>
            <a href="/admin/exam-papers" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <!-- Users Box -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-danger">
            <div class="inner">
                <h3> {{ \App\Models\VisionTest::all()->count() }} </h3>
                <p>Vision Tests</p>
            </div>
            <div class="icon">
                <i class="fas fa-low-vision"></i>
            </div>
            <a href="/admin/users" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <!-- Users Box -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-primary">
            <div class="inner">
                <h3> {{ \App\Models\ExamInformation::all()->count() }} </h3>
                <p>Exam Information</p>
            </div>
            <div class="icon">
                <i class="fas fa-info"></i>
            </div>
            <a href="/admin/users" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

        <!-- Users Box -->
        <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3> {{ \App\Models\Question::all()->count() }} </h3>
                    <p>Exam Questions</p>
                </div>
                <div class="icon">
                    <i class="fas fa-question"></i>
                </div>
                <a href="/admin/users" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

    {{-- <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-info">
            <div class="inner">
                <h3>150</h3>
                <p>File Downloads</p>
            </div>
            <div class="icon">
                <i class="fas fa-download"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-success">
            <div class="inner">
                <h3>53<sup style="font-size: 20px">%</sup></h3>
                <p>Bounce Rate</p>
            </div>
            <div class="icon">
                <i class="ion ion-stats-bars"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <!-- ./col -->
    <div class="col-lg-3 col-6">
        <!-- small box -->
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>65</h3>
                <p>Unique Visitors</p>
            </div>
            <div class="icon">
                <i class="ion ion-pie-graph"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
        </div>
    <!-- ./col -->
  </div> --}}
  <!-- /.row -->
