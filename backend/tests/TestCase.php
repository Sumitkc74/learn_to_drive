<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    // actingAs bypasses the login response that normally establishes this marker.
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        if($guard===null || $guard==='web'){
            $this->withSession(['browser_password.'.$user->getAuthIdentifier()=>$user->getAuthPassword()]);
        }
        return parent::actingAs($user,$guard);
    }

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('protected-media');
    }

    /**
     * Seed the database after RefreshDatabase runs its migrations.
     */
    protected $seed = true;
}
