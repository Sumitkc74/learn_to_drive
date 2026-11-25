<?php

namespace Tests\Feature;

use App\Models\DrivingSchool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): void
    {
        $admin = User::where('is_seed_admin', true)->firstOrFail();
        $this->actingAs($admin);
    }

    private function seedSchools(int $count = 21): void
    {
        // 21 schools = 2 pages at the schools list's 20 rows per page.
        for ($i = 1; $i <= $count; $i++) {
            DrivingSchool::create([
                'name' => "Trusted proxies school {$i}",
                'address' => 'Kathmandu',
                'phone' => '+97714000000',
                'status' => 'Pending',
            ]);
        }
    }

    /**
     * Evaluate config/network.php with the given environment variables,
     * restoring them afterwards so no state leaks into other tests.
     */
    private function networkConfigWithEnv(array $vars): mixed
    {
        $saved = [];
        foreach ($vars as $key => $value) {
            $saved[$key] = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
            if ($value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        try {
            return require base_path('config/network.php');
        } finally {
            foreach ($saved as $key => [$env, $envVar, $serverVar]) {
                if ($env === false) {
                    putenv($key);
                } else {
                    putenv("{$key}={$env}");
                }
                if ($envVar === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $envVar;
                }
                if ($serverVar === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $serverVar;
                }
            }
        }
    }

    public function test_production_defaults_to_trusting_all_proxies_when_env_is_unset(): void
    {
        $config = $this->networkConfigWithEnv(['APP_ENV' => 'production', 'TRUSTED_PROXIES' => null]);

        $this->assertSame('*', $config['trusted_proxies']);
    }

    public function test_explicit_trusted_proxies_win_over_the_production_default(): void
    {
        $config = $this->networkConfigWithEnv([
            'APP_ENV' => 'production',
            'TRUSTED_PROXIES' => '203.0.113.10, 198.51.100.0/24',
        ]);

        $this->assertSame(['203.0.113.10', '198.51.100.0/24'], $config['trusted_proxies']);
    }

    public function test_unset_env_trusts_all_proxies_in_any_environment(): void
    {
        $config = $this->networkConfigWithEnv(['APP_ENV' => 'testing', 'TRUSTED_PROXIES' => null]);

        $this->assertSame('*', $config['trusted_proxies']);
    }

    public function test_star_env_value_returns_the_string_wildcard_not_an_array(): void
    {
        // Regression: TRUSTED_PROXIES='*' used to come back as ['*'], which
        // Laravel treats as a list of literal IPs — '*' matches no real IP,
        // so no proxy was trusted and paginator links stayed http live.
        $config = $this->networkConfigWithEnv(['APP_ENV' => 'testing', 'TRUSTED_PROXIES' => '*']);

        $this->assertSame('*', $config['trusted_proxies']);
    }

    public function test_star_among_explicit_ips_returns_the_string_wildcard(): void
    {
        $config = $this->networkConfigWithEnv(['APP_ENV' => 'testing', 'TRUSTED_PROXIES' => '*, 203.0.113.10']);

        $this->assertSame('*', $config['trusted_proxies']);
    }

    public function test_paginator_uses_https_when_env_star_is_set(): void
    {
        // End-to-end proof of the regression: the env value '*' (as the
        // dashboard holds it) must produce https paginator links, not http.
        $this->admin();
        $this->seedSchools();
        config(['network.trusted_proxies' => $this->networkConfigWithEnv(['TRUSTED_PROXIES' => '*'])['trusted_proxies']]);

        $response = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->get('/admin/schools?page=2');

        $response->assertOk();
        $response->assertSee('https://localhost/admin/schools?page=1', false);
        $response->assertDontSee('http://localhost/admin/schools?page=1', false);
    }

    public function test_paginator_uses_https_when_forwarded_proto_is_https_and_proxies_are_trusted(): void
    {
        $this->admin();
        $this->seedSchools();
        config(['network.trusted_proxies' => '*']);

        $response = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->get('/admin/schools?page=2');

        $response->assertOk();
        $response->assertSee('https://localhost/admin/schools?page=1', false);
        $response->assertDontSee('http://localhost/admin/schools?page=1', false);
    }

    public function test_paginator_ignores_forwarded_proto_when_proxies_are_not_trusted(): void
    {
        $this->admin();
        $this->seedSchools();
        config(['network.trusted_proxies' => []]);

        $response = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->get('/admin/schools?page=2');

        $response->assertOk();
        $response->assertSee('http://localhost/admin/schools?page=1', false);
        $response->assertDontSee('https://localhost/admin/schools?page=1', false);
    }
}
