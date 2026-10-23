<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeadTest extends TestCase
{
    private array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutDefer();
        Storage::fake('local');
        Http::fake(['sheet.test/*' => Http::response('ok')]);
        config([
            'services.leads.to' => 'leads@example.test',
            'services.leads.reply_to' => 'sales@example.test',
            'services.leads.sheet_url' => 'https://sheet.test/exec',
        ]);
        Event::listen(MessageSending::class, fn ($e) => $this->sent[] = $e->message);
    }

    private function audit(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/wp-json/glowth/v1/audit', $overrides + [
            'name' => 'Sara Test',
            'bizName' => 'Glow <b>Salon</b>',
            'wa' => '+971 50 123 4567',
            'url' => 'glowsalon.ae',
            'biz' => 'Beauty Salon',
            'goal' => 'More bookings',
            'budget' => 'Under AED 3,000',
            'emirate' => 'Dubai',
            'website_hp' => '',
        ], ['Referer' => 'http://localhost/get-a-free-audit/']);
    }

    public function test_audit_lead_is_saved_emailed_and_sent_to_the_sheet(): void
    {
        $this->audit()->assertOk()->assertJson(['success' => true]);

        $file = 'leads/'.now()->format('Y-m').'.jsonl';
        Storage::disk('local')->assertExists($file);
        $this->assertStringContainsString('"WhatsApp number":"+971 50 123 4567"', Storage::disk('local')->get($file));

        $this->assertCount(1, $this->sent);
        $mail = $this->sent[0];
        $this->assertSame('New Growth Audit Lead - Glow <b>Salon</b> - Sara Test', $mail->getSubject());
        $this->assertSame('sales@example.test', $mail->getReplyTo()[0]->getAddress());
        $html = $mail->getHtmlBody();
        $this->assertStringContainsString('https://wa.me/971501234567', $html);
        $this->assertStringContainsString('Glow &lt;b&gt;Salon&lt;/b&gt;', $html);

        Http::assertSent(fn (HttpRequest $r) => $r->url() === 'https://sheet.test/exec'
            && $r['name'] === 'Sara Test'
            && $r['bizName'] === 'Glow <b>Salon</b>'
            && $r['wa'] === '+971 50 123 4567'
            && $r['emirate'] === 'Dubai'
            && array_key_exists('ip', $r->data())
            && array_key_exists('user_agent', $r->data()));
    }

    public function test_honeypot_submissions_are_dropped(): void
    {
        $this->audit(['website_hp' => 'http://spam.example'])->assertOk();

        Storage::disk('local')->assertMissing('leads/'.now()->format('Y-m').'.jsonl');
        $this->assertCount(0, $this->sent);
        Http::assertNothingSent();
    }

    public function test_name_and_whatsapp_are_required(): void
    {
        $this->audit(['wa' => ''])->assertStatus(422);
        $this->audit(['name' => ''])->assertStatus(422);

        $this->assertCount(0, $this->sent);
        Http::assertNothingSent();
    }

    public function test_elementor_form_lead_is_emailed_but_not_sent_to_the_sheet(): void
    {
        $this->post('/wp-admin/admin-ajax.php', [
            'action' => 'elementor_pro_forms_send_form',
            'form_id' => 'ccee265',
            'referer_title' => 'Home',
            'form_fields' => [
                'name' => 'Ali Test',
                'field_877ea9e' => 'Test Clinic',
                'field_9c0ce6b' => '+971500000000',
                'field_b5428e6' => 'Not enough bookings',
            ],
        ], ['Referer' => 'http://localhost/'])->assertOk()->assertJson(['success' => true]);

        $this->assertCount(1, $this->sent);
        $html = $this->sent[0]->getHtmlBody();
        $this->assertStringContainsString('Salon / clinic name', $html);
        $this->assertStringContainsString('https://wa.me/971500000000', $html);
        Http::assertNothingSent();
    }
}
