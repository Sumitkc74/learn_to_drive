<?php
namespace App\Models;
use App\Models\Concerns\TracksCreator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
class ExamInformation extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, TracksCreator;
    protected $fillable = ['name', 'description', 'language'];
    public function pdfMedia() { return $this->getFirstMedia(); }
    public function replacePdf(\Illuminate\Http\UploadedFile $file): void
    {
        $previous = $this->getMedia()->all();
        $this->addMedia($file)->withCustomProperties(['language' => $this->language])->toMediaCollection();
        foreach ($previous as $media) $media->delete();
        $this->unsetRelation('media');
    }
}
