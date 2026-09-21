<?php
namespace App\Support;
class ProtectedMediaUrlGenerator extends \Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator {
    public function getUrl(): string {
        if ($this->media->disk !== 'protected-media') return parent::getUrl();
        $owner = $this->media->model;
        $published = $owner instanceof \App\Models\Question && $owner->status === 'Published' && !$owner->trashed();
        return route($published ? 'media.published' : 'media.review', $this->media->id);
    }
    public function getTemporaryUrl(\DateTimeInterface $expiration, array $options = []): string {
        return $this->media->disk === 'protected-media' ? $this->getUrl() : parent::getTemporaryUrl($expiration,$options);
    }
}
