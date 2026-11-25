<section class="ltd-form-section"><h2 class="ltd-form-section__title">{{ __('School details') }}</h2>
<div class="row">
@foreach(['name' => 'School name', 'address' => 'Address', 'phone' => 'Phone', 'email' => 'School email'] as $field => $label)
    <div class="col-md-6 form-group">
        <label for="school-{{ $field }}" class="ltd-field-label">{{ __($label) }} @if($field === 'name')<span class="ltd-required">*</span>@endif</label>
        <input id="school-{{ $field }}" class="form-control" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}"
            maxlength="{{ $field === 'name' ? 160 : ($field === 'phone' ? 30 : 255) }}"
            value="{{ old($field, $school?->$field) }}" @required($field === 'name')>
    </div>
@endforeach
</div></section>
<section class="ltd-form-section"><h2 class="ltd-form-section__title">{{ __('Map location') }}</h2>
<span class="ltd-field-help">{{ __('Enter both coordinates to show an approved school on the public map. Leave both blank if its location is not confirmed.') }}</span>
<div class="row">
    @foreach(['latitude' => 'Latitude', 'longitude' => 'Longitude'] as $field => $label)
        <div class="col-md-6 form-group">
            <label for="school-{{ $field }}" class="ltd-field-label">{{ __($label) }}</label>
            <input id="school-{{ $field }}" class="form-control" type="number" step="0.0000001" min="{{ $field === 'latitude' ? -90 : -180 }}" max="{{ $field === 'latitude' ? 90 : 180 }}" name="{{ $field }}" value="{{ old($field, $school?->$field) }}">
        </div>
    @endforeach
</div>
<span class="ltd-field-help">{{ __('Approved school names, addresses, phone numbers and map locations are public. Owner and member details remain private.') }}</span></section>
<section class="ltd-form-section"><h2 class="ltd-form-section__title">{{ __('School logo and photos') }}</h2>
<span class="ltd-field-help">{{ __('The logo and gallery show on the public school listing once the school is approved. Images: JPEG, PNG or WebP, up to :imageLimit MB each. Videos: MP4 or WebM, up to :videoLimit MB each. At most :max gallery items per school.', ['imageLimit' => (int) (\App\Models\AppSetting::imageLimitKb() / 1024), 'videoLimit' => (int) (\App\Models\AppSetting::videoLimitKb() / 1024), 'max' => \App\Support\SchoolMedia::GALLERY_MAX_ITEMS]) }}</span>
<div class="form-group"><x-upload-field name="logo" id="school-logo" :label="__('Logo (optional)')" accept="image/jpeg,image/png,image/webp" :max-size-kb="\App\Models\AppSetting::imageLimitKb()" /></div>
<div class="form-group"><x-upload-field name="gallery_images[]" id="school-gallery-images" :label="__('Gallery photos (optional)')" accept="image/jpeg,image/png,image/webp" multiple :max-size-kb="\App\Models\AppSetting::imageLimitKb()" /></div>
<div class="form-group mb-0"><x-upload-field name="gallery_videos[]" id="school-gallery-videos" :label="__('Gallery videos (optional)')" accept="video/mp4,video/webm" multiple :max-size-kb="\App\Models\AppSetting::videoLimitKb()" /></div></section>
