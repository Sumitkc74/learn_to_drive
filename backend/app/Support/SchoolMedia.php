<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\DrivingSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

// Logo + gallery uploads for driving schools. One shared helper serves the
// three upload paths (admin, partner application, Owner/Manager dashboard)
// so the type/size rules and the gallery cap cannot drift apart. Videos
// have no precedent elsewhere in the app (tutorials link out to external
// videos), so the video cap is new: 20 MB per file, MP4/WebM only.
class SchoolMedia
{
    public const LOGO_COLLECTION = 'school-logo';
    public const GALLERY_COLLECTION = 'school-gallery';
    public const GALLERY_MAX_ITEMS = 20;
    public const GALLERY_MAX_IMAGES_PER_REQUEST = 10;
    public const GALLERY_MAX_VIDEOS_PER_REQUEST = 5;

    public static function logoRules(): array
    {
        return ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:'.AppSetting::imageLimitKb()];
    }

    public static function galleryRules(): array
    {
        return [
            'gallery_images' => ['nullable', 'array', 'max:'.self::GALLERY_MAX_IMAGES_PER_REQUEST],
            'gallery_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:'.AppSetting::imageLimitKb()],
            'gallery_videos' => ['nullable', 'array', 'max:'.self::GALLERY_MAX_VIDEOS_PER_REQUEST],
            'gallery_videos.*' => ['file', 'mimetypes:video/mp4,video/webm', 'max:'.AppSetting::videoLimitKb()],
        ];
    }

    // The stored mime type comes from content guessing, which can misread
    // a container; the extension is what the upload rules validated.
    public static function isVideo(Media $media): bool
    {
        return str_starts_with($media->mime_type ?? '', 'video/')
            || in_array(strtolower($media->extension ?? ''), ['mp4', 'webm'], true);
    }

    // Replaces the logo when a file is given, removes it when $remove is
    // true, leaves it alone otherwise.
    public static function attachLogo(DrivingSchool $school, ?UploadedFile $file, bool $remove = false): void
    {
        if ($remove || $file) {
            $school->clearMediaCollection(self::LOGO_COLLECTION);
        }
        if ($file) {
            $school->addMedia($file)->toMediaCollection(self::LOGO_COLLECTION);
        }
        $school->unsetRelation('media');
    }

    public static function attachGallery(DrivingSchool $school, array $images, array $videos): void
    {
        $new = count($images) + count($videos);
        if ($new === 0) {
            return;
        }
        if ($school->getMedia(self::GALLERY_COLLECTION)->count() + $new > self::GALLERY_MAX_ITEMS) {
            throw ValidationException::withMessages(['gallery_images' => __('A school can have at most :count gallery items.', ['count' => self::GALLERY_MAX_ITEMS])]);
        }
        foreach (array_merge($images, $videos) as $file) {
            $school->addMedia($file)->toMediaCollection(self::GALLERY_COLLECTION);
        }
        $school->unsetRelation('media');
    }

    // Only gallery items belonging to this school can be removed.
    public static function removeGalleryItems(DrivingSchool $school, array $ids): void
    {
        if ($ids === []) {
            return;
        }
        $school->media()->where('collection_name', self::GALLERY_COLLECTION)->whereIn('id', $ids)->delete();
        $school->unsetRelation('media');
    }
}
