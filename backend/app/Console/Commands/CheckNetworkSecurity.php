<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckNetworkSecurity extends Command
{
    protected $signature = 'security:check-network';
    protected $description = 'Check deployment network configuration without printing secrets';

    public function handle(): int
    {
        $checks = [
            'Production environment' => app()->environment('production'),
            'Debug output disabled' => !config('app.debug'),
            'HTTPS application URL' => parse_url(config('app.url'), PHP_URL_SCHEME) === 'https',
            'Secure session cookies' => (bool) config('session.secure'),
            'Explicit proxy trust (no wildcard)' => !in_array('*', config('network.trusted_proxies', []), true),
        ];
        foreach ($checks as $label => $passed) $this->line(($passed ? 'PASS: ' : 'FAIL: ').$label);
        $this->comment('This checks configuration only. Verify TLS, firewall, proxy IPs and forwarded-header sanitization on the hosting server.');
        return in_array(false, $checks, true) ? self::FAILURE : self::SUCCESS;
    }
}
