<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/** Office / VPN IP allowlist, the proxy IP header, and the public test-site safeguards */
class NetworkAccessTest extends TestCase
{
    use RefreshDatabase;

    private const OFFICE = '203.0.113.10';
    private const VPN_RANGE = '10.8.0.0/24';
    private const OUTSIDE = '198.51.100.77';

    private function allowlist(string $scope = 'all', ?string $header = null): void
    {
        config([
            'network.allowlist'        => [self::OFFICE, self::VPN_RANGE, '2001:db8:abcd::/48'],
            'network.allowlist_scope'  => $scope,
            'network.client_ip_header' => $header,
        ]);
    }

    private function fromIp(string $ip): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }

    public function test_without_an_allowlist_the_site_is_public(): void
    {
        $this->fromIp(self::OUTSIDE)->get('/login')->assertOk();
    }

    public function test_office_and_vpn_addresses_are_allowed_and_others_blocked(): void
    {
        $this->allowlist();

        $this->fromIp(self::OFFICE)->get('/login')->assertOk();
        $this->fromIp('10.8.0.42')->get('/login')->assertOk();                    // VPN pool
        $this->fromIp('2001:db8:abcd:12::5')->get('/login')->assertOk();         // IPv6 range

        $this->fromIp(self::OUTSIDE)->get('/login')
            ->assertForbidden()
            ->assertSee(self::OUTSIDE)                                          // tells the user which IP to report
            ->assertSee('company VPN');
    }

    public function test_health_check_is_never_blocked(): void
    {
        $this->allowlist();

        $this->fromIp(self::OUTSIDE)->get('/up')->assertOk();
    }

    public function test_behind_the_proxy_the_visitor_ip_comes_from_the_configured_header(): void
    {
        $this->allowlist(header: 'X-Real-IP');
        $proxy = '100.64.1.2';

        $this->fromIp($proxy)->withHeader('X-Real-IP', self::OFFICE)->get('/login')->assertOk();
        $this->fromIp($proxy)->withHeader('X-Real-IP', self::OUTSIDE)->get('/login')->assertForbidden();
    }

    public function test_a_missing_or_garbage_header_fails_closed(): void
    {
        $this->allowlist(header: 'X-Real-IP');

        $this->fromIp('100.64.1.2')->get('/login')->assertForbidden();
        $this->fromIp('100.64.1.2')->withHeader('X-Real-IP', 'not-an-ip')->get('/login')->assertForbidden();
    }

    public function test_the_header_is_ignored_when_not_configured_so_it_cannot_be_spoofed(): void
    {
        $this->allowlist(header: null);

        $this->fromIp(self::OUTSIDE)->withHeader('X-Real-IP', self::OFFICE)->get('/login')->assertForbidden();
        $this->fromIp(self::OUTSIDE)->withHeader('X-Forwarded-For', self::OFFICE)->get('/login')->assertForbidden();
    }

    public function test_hr_scope_keeps_the_applicant_portal_public(): void
    {
        $this->allowlist('hr');
        [$applicant] = $this->applicant();
        $hr = $this->hrUser();

        // Applicants: public
        $this->fromIp(self::OUTSIDE)->get('/register')->assertOk();
        $this->fromIp(self::OUTSIDE)->actingAs($applicant)->get('/applicant/dashboard')->assertOk();

        // HR area and HR accounts: office / VPN only
        $this->fromIp(self::OUTSIDE)->actingAs($hr)->get('/hr/dashboard')->assertForbidden();
        $this->fromIp(self::OUTSIDE)->actingAs($hr)->get('/notifications/feed')->assertForbidden();
        $this->fromIp(self::OFFICE)->actingAs($hr)->get('/hr/dashboard')->assertOk();
    }

    public function test_login_rate_limit_is_per_visitor_not_per_proxy(): void
    {
        config(['network.client_ip_header' => 'X-Real-IP']);
        [$user] = $this->applicant();

        // One visitor locks themselves out...
        for ($i = 0; $i < 6; $i++) {
            $this->fromIp('100.64.1.2')->withHeader('X-Real-IP', self::OUTSIDE)
                ->post('/login', ['login' => $user->email, 'password' => 'wrong']);
        }

        // ...but someone else behind the same proxy can still sign in
        $this->fromIp('100.64.1.2')->withHeader('X-Real-IP', self::OFFICE)
            ->post('/login', ['login' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('applicant.dashboard'));
    }

    public function test_https_from_the_proxy_produces_https_links_but_forwarded_ips_are_ignored(): void
    {
        config(['network.client_ip_header' => 'X-Real-IP']);

        $this->fromIp('100.64.1.2')
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Real-IP' => self::OFFICE, 'X-Forwarded-For' => '1.2.3.4'])
            ->get('/login')
            ->assertOk();

        $request = request();
        $this->assertTrue($request->secure());
        $this->assertStringStartsWith('https://', url('/login'));
        $this->assertSame(self::OFFICE, $request->ip());   // X-Forwarded-For was not trusted
    }

    public function test_test_site_is_hidden_from_search_engines_and_shows_a_banner(): void
    {
        $this->app['env'] = 'staging';
        $this->get('/login')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Test environment');

        $this->app['env'] = 'production';
        $response = $this->get('/login');
        $this->assertFalse($response->headers->has('X-Robots-Tag'));
        $response->assertDontSee('Test environment');
    }

    public function test_demo_reset_refuses_to_run_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->assertSame(1, Artisan::call('ihr:reset-demo'));
        $this->assertStringContainsString('only runs in staging', Artisan::output());
    }
}
