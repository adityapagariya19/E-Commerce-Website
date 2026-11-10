<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class NewsletterAndContactTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    public function test_native_subscription_works_without_omnisend(): void
    {
        Http::fake();
        $this->postJson(route('newsletter.subscribe'), ['email' => 'Fan@Test.test', 'consent' => 1])->assertOk()->assertJsonStructure(['ok', 'message']);
        $this->postJson(route('newsletter.subscribe'), ['email' => 'fan@test.test', 'consent' => 1])->assertOk(); // duplicate is harmless
        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertSame('fan@test.test', NewsletterSubscriber::first()->email);
        Http::assertNothingSent();
    }

    public function test_invalid_email_and_honeypot_are_rejected(): void
    {
        $this->postJson(route('newsletter.subscribe'), ['email' => 'nope', 'consent' => 1])->assertStatus(422);
        $this->postJson(route('newsletter.subscribe'), ['email' => 'bot@test.test', 'website' => 'spam', 'consent' => 1])->assertStatus(422);
        $this->assertSame(0, NewsletterSubscriber::count());
    }

    public function test_omnisend_failure_never_breaks_signup(): void
    {
        Setting::put('omnisend_api_key', 'key', true);
        Http::fake(['api.omnisend.com/*' => Http::sequence()->push('boom', 500)->push(['ok' => true], 200)]);
        $this->postJson(route('newsletter.subscribe'), ['email' => 'a@test.test', 'consent' => 1])->assertOk();
        $this->assertFalse(NewsletterSubscriber::first()->synced_omnisend);
        $this->postJson(route('newsletter.subscribe'), ['email' => 'b@test.test', 'consent' => 1])->assertOk();
        $this->assertTrue(NewsletterSubscriber::where('email', 'b@test.test')->first()->synced_omnisend);
        Http::assertSent(fn ($r) => $r->hasHeader('X-API-KEY', 'key'));
    }

    public function test_csv_export_works_and_neutralises_formulas(): void
    {
        NewsletterSubscriber::create(['email' => 'ok@test.test', 'token' => 't1', 'status' => 'subscribed', 'subscribed_at' => now()]);
        NewsletterSubscriber::create(['email' => '=cmd@test.test', 'token' => 't2', 'status' => 'unsubscribed']);
        $this->actingAs($this->admin('store_manager'));
        $csv = $this->get(route('admin.newsletter.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('email,phone,status', $csv);
        $this->assertStringContainsString('ok@test.test', $csv);
        $this->assertStringContainsString("'=cmd@test.test", $csv);
        $this->get(route('admin.newsletter.export', ['status' => 'subscribed']))->assertOk();
        $this->actingAs($this->admin('order_support'))->get(route('admin.newsletter.export'))->assertForbidden();
    }

    public function test_unsubscribe_link(): void
    {
        $s = NewsletterSubscriber::create(['email' => 'u@test.test', 'token' => 'tok123', 'status' => 'subscribed']);
        $this->get(route('newsletter.unsubscribe', 'tok123'))->assertRedirect();
        $this->assertSame('unsubscribed', $s->fresh()->status);
    }

    public function test_contact_form_stores_message_and_emails_recipient(): void
    {
        Mail::fake();
        Setting::put('contact_recipient', 'owner@test.test');
        $this->post(route('contact.store'), ['name' => 'Ana', 'email' => 'ana@test.test', 'message' => 'Do you stock kids frames?'])->assertRedirect(route('contact'));
        $this->assertDatabaseHas('contact_messages', ['email' => 'ana@test.test', 'status' => 'new']);
        Mail::assertSent(\App\Mail\ContactReceived::class, fn ($m) => $m->hasTo('owner@test.test'));
    }

    public function test_contact_validation_honeypot_and_html_escaping(): void
    {
        $this->post(route('contact.store'), ['name' => '', 'email' => 'bad', 'message' => ''])->assertSessionHasErrors(['name', 'email', 'message']);
        $this->post(route('contact.store'), ['name' => 'Bot', 'email' => 'b@test.test', 'message' => 'hello there', 'website' => 'x'])->assertSessionHasErrors('website');
        $this->post(route('contact.store'), ['name' => '<script>alert(1)</script>', 'email' => 'x@test.test', 'message' => 'valid message'])->assertRedirect();
        $this->actingAs($this->admin('store_manager'))->get(route('admin.messages.index'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_help_centre_shows_topics_and_never_invents_missing_answers(): void
    {
        $t = \App\Models\FaqTopic::create(['title' => 'Returns & Refunds', 'is_active' => true]);
        \App\Models\Faq::create(['faq_topic_id' => $t->id, 'question' => 'What is your return policy?', 'answer' => null, 'is_active' => true]);
        $this->get(route('help'))->assertOk()->assertSee('What is your return policy?')->assertSee('Please contact us')->assertSee('Feedback');
    }

    public function test_chat_endpoint_answers_from_faqs_and_products(): void
    {
        $this->product(['name' => 'Wayfarer Deluxe']);
        $t = \App\Models\FaqTopic::create(['title' => 'Account', 'is_active' => true]);
        \App\Models\Faq::create(['faq_topic_id' => $t->id, 'question' => 'How do I reset my password?', 'answer' => 'Use Lost your password.', 'is_active' => true]);
        $this->postJson(route('chat'), ['message' => 'reset password'])->assertOk()->assertJsonFragment(['reply' => 'Use Lost your password.']);
        $this->postJson(route('chat'), ['message' => 'wayfarer'])->assertOk()->assertJsonPath('links.0.label', 'Wayfarer Deluxe');
    }
}
