@foreach(['name' => 'School name', 'address' => 'Address', 'phone' => 'Phone', 'email' => 'School email'] as $field => $label)
<div class="mb-3">
    <label for="school-{{ $field }}" class="form-label">{{ __($label) }}</label>
    <input id="school-{{ $field }}" class="form-control" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}"
        maxlength="{{ $field === 'name' ? 160 : ($field === 'phone' ? 30 : 255) }}"
        value="{{ old($field, $school?->$field) }}" @required($field === 'name')>
</div>
@endforeach
<fieldset class="mb-3">
    <legend>{{ __('Map location') }}</legend>
    <p>{{ __('Enter both coordinates to show an approved school on the public map. Leave both blank if its location is not confirmed.') }}</p>
    @foreach(['latitude' => 'Latitude', 'longitude' => 'Longitude'] as $field => $label)
        <label for="school-{{ $field }}" class="form-label">{{ __($label) }}</label>
        <input id="school-{{ $field }}" class="form-control mb-2" type="number" step="0.0000001" min="{{ $field === 'latitude' ? -90 : -180 }}" max="{{ $field === 'latitude' ? 90 : 180 }}" name="{{ $field }}" value="{{ old($field, $school?->$field) }}">
    @endforeach
    <p>{{ __('Approved school names, addresses, phone numbers and map locations are public. Owner and member details remain private.') }}</p>
</fieldset>
