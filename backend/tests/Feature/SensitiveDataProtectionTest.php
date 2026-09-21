<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PublicWebsiteRequest;
use Database\Seeders\UserTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SensitiveDataProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_scraper_rejects_credential_urls_before_network_access(): void
    {
        Http::preventStrayRequests();
        foreach (['token=example', 'api_key=example', 'auth[password]=example', 'X-Amz-Signature=example'] as $query) {
            try {
                app(PublicWebsiteRequest::class)->get('https://example.org/file.pdf?'.$query);
                $this->fail('Credential-bearing URL was accepted.');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString('without access tokens', $e->getMessage());
            }
        }
        Http::assertNothingSent();
        $this->assertSame('example.org', app(PublicWebsiteRequest::class)->host('https://example.org/?page=2'));
    }

    public function test_reseeding_preserves_existing_administrator_password(): void
    {
        $admin = User::where('email', 'admin@admin.com')->firstOrFail();
        $original = $admin->password;
        config(['seed-admin.password' => 'Another-test-password-123!']);
        $this->seed(UserTableSeeder::class);
        $this->assertSame($original, $admin->fresh()->password);
        $this->assertFalse(Hash::check('password', $original));
    }
}
