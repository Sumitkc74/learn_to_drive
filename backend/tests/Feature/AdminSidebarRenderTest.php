<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminSidebarRenderTest extends TestCase
{
    public function test_sidebar_renders_when_no_user_is_authenticated(): void
    {
        auth()->logout();

        $html = view('admin.layout.sidebar')->render();

        $this->assertStringContainsString('Learn To Drive', $html);
        $this->assertStringContainsString('Guest', $html);
    }
}
