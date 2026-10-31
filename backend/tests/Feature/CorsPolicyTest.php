<?php
namespace Tests\Feature;
use Tests\TestCase;
class CorsPolicyTest extends TestCase {
    public function test_untrusted_origins_do_not_receive_cors_permission(): void {
        $r=$this->withHeaders(['Origin'=>'https://untrusted.example','Access-Control-Request-Method'=>'GET'])->options('/api/learner/account');
        $this->assertNotContains($r->headers->get('Access-Control-Allow-Origin'), ['*', 'https://untrusted.example']);
        $r->assertHeaderMissing('Access-Control-Allow-Credentials');
    }
    public function test_configured_origin_can_send_bearer_requests(): void {
        config(['cors.allowed_origins'=>['https://learn.example']]);
        $this->withHeaders(['Origin'=>'https://learn.example','Access-Control-Request-Method'=>'GET','Access-Control-Request-Headers'=>'authorization,x-learner-language'])->options('/api/learner/account')->assertHeader('Access-Control-Allow-Origin','https://learn.example')->assertHeaderMissing('Access-Control-Allow-Credentials');
    }
}
