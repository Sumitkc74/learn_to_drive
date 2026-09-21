<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PdfTranslation extends Model {
    protected $guarded = ['id'];
    protected $casts = ['next_page' => 'integer', 'total_pages' => 'integer'];
    public function resource() { return $this->belongsTo(LearningContentImport::class, 'learning_content_import_id'); }
    public function pages() { return $this->hasMany(PdfTranslationPage::class)->orderBy('page'); }
}
