@extends('admin.layout.master')
@section('title', 'Extract Individual Traffic Signs')
@section('content')
<div class="ltd-page-header"><div><h1>Extract Individual Traffic Signs</h1><p>Drag a rectangle around one sign. Check the preview and its meaning before saving.</p></div><a href="{{ route('learningContent.show', $resource) }}" class="btn btn-outline-secondary">Back to Resource</a></div>
<div class="ltd-panel mb-4">
    <p>{{ $resource->title }} — <a href="{{ $resource->source_url }}" target="_blank" rel="noopener noreferrer">Official source</a></p>
    <canvas id="sign-sheet" style="max-width:100%;height:auto;touch-action:none;cursor:crosshair;border:1px solid #aaa" aria-label="Select one sign by dragging on this sheet"></canvas>
    <p id="image-status" role="status">Loading saved sheet…</p>
</div>
<form class="ltd-panel" method="POST" action="{{ route('learningContent.storeSign', $resource) }}">
    @csrf
    <h2>Review Individual Sign</h2>
    <div class="row">
        @foreach(['x' => 'Left', 'y' => 'Top', 'width' => 'Width', 'height' => 'Height'] as $key => $label)
        <div class="col-6 col-md-3 form-group"><label for="crop-{{ $key }}">{{ $label }} (pixels)</label><input id="crop-{{ $key }}" name="{{ $key }}" type="number" min="{{ in_array($key, ['width', 'height']) ? 16 : 0 }}" value="{{ old($key, 0) }}" class="form-control" required></div>
        @endforeach
    </div>
    <canvas id="sign-preview" style="max-width:100%;max-height:300px;border:1px solid #ddd" aria-label="Selected individual sign preview"></canvas>
    <div class="form-group"><label for="sign-name">English name</label><input id="sign-name" name="name" maxlength="255" value="{{ old('name') }}" class="form-control" required></div>
    <div class="form-group"><label for="sign-nepali">Nepali name</label><input id="sign-nepali" name="nepaliSignName" maxlength="255" value="{{ old('nepaliSignName') }}" class="form-control" lang="ne" required></div>
    <div class="form-group"><label for="sign-meaning">Meaning / driver instruction</label><textarea id="sign-meaning" name="description" maxlength="1000" class="form-control" rows="3" required>{{ old('description') }}</textarea></div>
    <label><input type="checkbox" name="confirmed" value="1" required> I checked that this image contains one sign and that both names and the meaning are correct.</label>
    <p class="text-muted">Saving adds this sign to Traffic Signs, where it becomes available to learners. Source details are retained with the image.</p>
    <button id="save-sign" class="btn btn-primary" disabled>Save Individual Traffic Sign</button>
</form>
@endsection
@section('page-script')
<script>
(() => {
    const sheet = document.getElementById('sign-sheet'), preview = document.getElementById('sign-preview');
    const context = sheet.getContext('2d'), fields = ['x', 'y', 'width', 'height'].map(key => document.getElementById('crop-' + key));
    const status = document.getElementById('image-status'), save = document.getElementById('save-sign');
    const source = new Image();
    let start = null, ready = false;
    function render() {
        if (!ready) return;
        const [x, y, width, height] = fields.map(field => Number(field.value));
        context.clearRect(0, 0, sheet.width, sheet.height);
        context.drawImage(source, 0, 0);
        const valid = [x, y, width, height].every(Number.isInteger) && x >= 0 && y >= 0 && width >= 16 && height >= 16 && x + width <= sheet.width && y + height <= sheet.height;
        save.disabled = !valid;
        preview.width = valid ? width : 1; preview.height = valid ? height : 1;
        if (valid) {
            preview.getContext('2d').drawImage(source, x, y, width, height, 0, 0, width, height);
            context.strokeStyle = '#0069d9'; context.lineWidth = Math.max(2, sheet.width / 350);
            context.strokeRect(x, y, width, height);
        }
        status.textContent = valid ? `Selected ${width} × ${height} pixels. Check the preview below.` : 'Select one sign, or enter its rectangle coordinates below.';
        document.querySelector('[name="confirmed"]').checked = false;
    }
    function point(event) {
        const rect = sheet.getBoundingClientRect();
        return [Math.max(0, Math.min(sheet.width, Math.round((event.clientX - rect.left) * sheet.width / rect.width))), Math.max(0, Math.min(sheet.height, Math.round((event.clientY - rect.top) * sheet.height / rect.height)))];
    }
    sheet.addEventListener('pointerdown', event => { if (!ready) return; start = point(event); sheet.setPointerCapture(event.pointerId); });
    sheet.addEventListener('pointermove', event => {
        if (!start) return;
        const end = point(event), values = [Math.min(start[0], end[0]), Math.min(start[1], end[1]), Math.abs(start[0] - end[0]), Math.abs(start[1] - end[1])];
        fields.forEach((field, index) => field.value = values[index]); render();
    });
    ['pointerup', 'pointercancel'].forEach(type => sheet.addEventListener(type, () => start = null));
    fields.forEach(field => field.addEventListener('input', render));
    source.onload = () => { sheet.width = source.naturalWidth; sheet.height = source.naturalHeight; ready = true; render(); };
    source.onerror = () => status.textContent = 'The saved image could not be loaded. Return to the resource and check its private copy.';
    source.src = @json(route('learningContent.file', ['resource' => $resource, 'preview' => 1]));
})();
</script>
@endsection
