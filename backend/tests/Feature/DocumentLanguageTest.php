<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentLanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_documents_reject_mismatched_text_and_accept_nepali_with_category_codes(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
        foreach (['ExamPaper', 'ExamInformation'] as $type) {
            $data = ['name' => 'English title', 'description' => 'Practice document', 'language' => 'Nepali'];
            $this->post(route('insert'.$type), $data + ['pdf' => UploadedFile::fake()->createWithContent('test.pdf', "%PDF-1.4\n%%EOF")])
                ->assertSessionHasErrors(['name', 'description']);
            $data = ['name' => 'वर्ग ख (B) प्रश्न संग्रह', 'description' => 'अभ्यासका लागि नेपाली प्रश्न संग्रह।', 'language' => 'English'];
            $this->post(route('insert'.$type), $data + ['pdf' => UploadedFile::fake()->createWithContent('test.pdf', "%PDF-1.4\n%%EOF")])
                ->assertSessionHasErrors(['name', 'description']);
            $data['language'] = 'Nepali';
            $this->post(route('insert'.$type), $data + ['pdf' => UploadedFile::fake()->createWithContent('test.pdf', "%PDF-1.4\n%%EOF")])
                ->assertSessionHasNoErrors()->assertRedirect();
        }
    }
}
