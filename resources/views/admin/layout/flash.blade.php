@if($errors->any())
<div class="alert alert-danger" role="alert" id="myAlert">
    <strong>{{ $errors->first() }}</strong>
</div>
@endif

@if ($message = Session::get('success'))
<div class="alert alert-success alert-block"  id="myAlert">
    <strong>{{ $message }}</strong>
</div>
@endif

