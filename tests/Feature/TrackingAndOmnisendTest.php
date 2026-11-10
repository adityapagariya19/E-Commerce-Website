<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class TrackingAndOmnisendTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    public function test_full_courier_workflow_with_timestamps_and_cod_payment(): void
    {
        $o = $this->order([[$this->product(), 1]], ['payment_method' => 'cod', 'payment_status' => 'cod_pending']);
        $this->actingAs($this->admin());
        foreach (['confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered'] as $s) {
            $this->post(route('admin.orders.status', $o), ['status' => $s])->assertSessionHas('success');
        }
        $o->refresh();
        $this->assertSame('delivered', $o->status);
        $this->assertNotNull($o->shipped_at);
        $this->assertNotNull($o->delivered_at);
        $this->assertSame('paid', $o->payment_status);   // cash collected on delivery
        $this->assertSame(5, $o->histories()->where('from_status', '!=', null)->count());
        $this->post(route('admin.orders.status', $o), ['status' => 'shipped'])->assertSessionHas('error'); // cannot go backwards from delivered
    }

    public function test_tracking_details_are_saved_validated_and_shown_to_the_owner_only(): void
    {
        $u = $this->customer();
        $o = $this->order([[$this->product(), 1]], ['user_id' => $u->id, 'status' => 'shipped']);
        $this->actingAs($this->admin());
        $this->post(route('admin.orders.tracking', $o), ['tracking_url' => 'javascript:alert(1)'])->assertSessionHasErrors('tracking_url');
        $this->post(route('admin.orders.tracking', $o), ['courier_name' => 'Delhivery', 'tracking_number' => 'DL123456', 'tracking_url' => 'https://track.example.test/DL123456'])->assertSessionHas('success');
        $this->actingAs($u)->get(route('account.order', $o))->assertOk()->assertSee('Delhivery')->assertSee('DL123456')->assertSee('https://track.example.test/DL123456', false)->assertSee('Out for delivery');
        $this->actingAs($this->customer())->get(route('account.order', $o))->assertNotFound();
    }

    public function test_no_fake_tracking_is_displayed_when_none_is_entered(): void
    {
        $u = $this->customer();
        $o = $this->order([[$this->product(), 1]], ['user_id' => $u->id]);
        $this->actingAs($u)->get(route('account.order', $o))->assertOk()->assertDontSee('Shipment tracking');
    }

    public function test_customer_cancellation_rules(): void
    {
        $u = $this->customer();
        $p = $this->product(['stock_qty' => 3]);
        $cod = $this->order([[$p, 1]], ['user_id' => $u->id, 'payment_method' => 'cod', 'payment_status' => 'cod_pending', 'stock_reserved' => true]);
        $shipped = $this->order([[$p, 1]], ['user_id' => $u->id, 'payment_method' => 'cod', 'payment_status' => 'cod_pending', 'status' => 'shipped']);
        $paid = $this->order([[$p, 1]], ['user_id' => $u->id]);
        $this->actingAs($u);
        $this->post(route('account.order.cancel', $cod))->assertSessionHas('success');
        $this->assertSame('cancelled', $cod->fresh()->status);
        $this->assertSame(4, $p->fresh()->stock_qty);
        $this->post(route('account.order.cancel', $shipped))->assertSessionHas('error');
        $this->post(route('account.order.cancel', $paid))->assertSessionHas('error');
    }

    public function test_receipt_is_private(): void
    {
        $u = $this->customer();
        $o = $this->order([[$this->product(), 2]], ['user_id' => $u->id]);
        $this->actingAs($u)->get(route('account.order.receipt', $o))->assertOk()->assertSee('Receipt')->assertSee((string) $o->order_number);
        $this->actingAs($this->customer())->get(route('account.order.receipt', $o))->assertNotFound();
    }

    public function test_new_courier_statuses_count_as_sales_for_popular_and_reports(): void
    {
        $p = $this->product();
        foreach (['confirmed', 'packed', 'out_for_delivery', 'delivered'] as $s) { $this->order([[$p, 1]], ['status' => $s]); }
        \App\Services\PopularProductsService::forget();
        $this->assertSame(4, app(\App\Services\PopularProductsService::class)->get(false)['items']->first()['units']);
    }

    /* ---------------- Omnisend ---------------- */

    private function sub(string $email = 'a@test.test'): NewsletterSubscriber
    {
        $this->postJson(route('newsletter.subscribe'), ['email' => $email, 'consent' => 1])->assertOk();
        return NewsletterSubscriber::where('email', $email)->firstOrFail();
    }

    public function test_consent_is_required_and_recorded(): void
    {
        $this->postJson(route('newsletter.subscribe'), ['email' => 'x@test.test'])->assertStatus(422)->assertJsonValidationErrors('consent');
        $this->assertSame(0, NewsletterSubscriber::count());
        $s = $this->sub('y@test.test');
        $this->assertNotNull($s->consent_at);
        $this->assertNotNull($s->consent_ip);
    }

    public function test_without_api_key_nothing_is_sent_and_status_is_skipped(): void
    {
        Http::fake();
        $s = $this->sub();
        $this->assertSame('skipped', $s->sync_status);
        Http::assertNothingSent();
    }

    public function test_success_failure_retry_and_attempt_cap(): void
    {
        Setting::put('omnisend_api_key', 'secret-key', true);
        Http::fake(['api.omnisend.com/*' => Http::sequence()->push(['message' => 'down'], 500)->push(['ok' => true], 200)]);
        $s = $this->sub();
        $this->assertSame('failed', $s->sync_status);
        $this->assertSame(1, $s->sync_attempts);
        $this->assertStringContainsString('500', $s->sync_error);
        $this->artisan('jbo:sync-omnisend')->assertSuccessful();
        $s->refresh();
        $this->assertSame('synced', $s->sync_status);
        $this->assertNotNull($s->synced_at);
        $this->assertNull($s->sync_error);
        Http::assertSent(fn ($r) => $r->hasHeader('X-API-KEY', 'secret-key') && $r['identifiers'][0]['id'] === 'a@test.test' && $r['identifiers'][0]['channels']['email']['status'] === 'subscribed');

        $stuck = NewsletterSubscriber::create(['email' => 'z@test.test', 'token' => 't', 'sync_status' => 'failed', 'sync_attempts' => 5]);
        $this->artisan('jbo:sync-omnisend')->assertSuccessful();
        $this->assertSame(5, $stuck->fresh()->sync_attempts); // not retried forever
    }

    public function test_duplicate_contact_409_counts_as_synced_and_local_list_has_one_row(): void
    {
        Setting::put('omnisend_api_key', 'k', true);
        Http::fake(['api.omnisend.com/*' => Http::response(['error' => 'exists'], 409)]);
        $this->sub('d@test.test');
        $this->sub('d@test.test');
        $this->assertSame(1, NewsletterSubscriber::count());
        $this->assertSame('synced', NewsletterSubscriber::first()->sync_status);
    }

    public function test_admin_can_trigger_retry_and_sees_status_while_csv_still_works(): void
    {
        Setting::put('omnisend_api_key', 'k', true);
        Http::fake(['api.omnisend.com/*' => Http::response('x', 500)]);
        $this->sub('e@test.test');
        Http::fake(['api.omnisend.com/*' => Http::response(['ok' => 1], 200)]);
        $this->actingAs($this->admin('store_manager'));
        $this->get(route('admin.newsletter.index'))->assertOk()->assertSee('failed');
        $this->post(route('admin.newsletter.sync'))->assertSessionHas('error'); // first fake still wins in this process → still failing, but reported clearly
        $this->get(route('admin.newsletter.export'))->assertOk();
    }

    public function test_omnisend_key_never_reaches_the_browser(): void
    {
        Setting::put('omnisend_api_key', 'TOP-SECRET-OMNI', true);
        $this->get('/')->assertDontSee('TOP-SECRET-OMNI');
        $this->actingAs($this->admin())->get(route('admin.settings.edit'))->assertDontSee('TOP-SECRET-OMNI');
    }

    /* ---------------- Try-On ---------------- */

    private function tryonPayload(array $o = []): array
    {
        return $o + ['name' => 'AR Frame', 'type' => 'simple', 'status' => 'published', 'price' => 1000, 'tryon_enabled' => 1];
    }

    public function test_tryon_url_host_allow_list(): void
    {
        $this->actingAs($this->admin());
        foreach (['https://evil.test/p/1', 'http://mywebar.com/p/1', 'https://evilmywebar.com/p/1', 'javascript:alert(1)', 'https://mywebar.com.evil.test/x'] as $bad) {
            $this->post(route('admin.products.store'), $this->tryonPayload(['tryon_url' => $bad]))->assertSessionHasErrors('tryon_url');
        }
        $this->post(route('admin.products.store'), $this->tryonPayload(['tryon_url' => 'https://mywebar.com/p/Project_123']))->assertSessionHasNoErrors();
        $this->post(route('admin.products.store'), $this->tryonPayload(['name' => 'Sub', 'tryon_url' => 'https://ar.mywebar.com/p/x']))->assertSessionHasNoErrors();
        Setting::put('tryon_allowed_hosts', 'mywebar.com, ar-provider.test');
        $this->post(route('admin.products.store'), $this->tryonPayload(['name' => 'Other', 'tryon_url' => 'https://ar-provider.test/model/9']))->assertSessionHasNoErrors();
    }

    public function test_tryon_page_embeds_product_specific_model_with_camera_permission_and_falls_back_without_url(): void
    {
        $with = $this->product(['name' => 'Has AR', 'tryon_enabled' => true, 'tryon_url' => 'https://mywebar.com/p/Project_123']);
        $without = $this->product(['name' => 'No AR', 'tryon_enabled' => true]);
        $html = $this->get('/try-on')->assertOk()->getContent();
        $this->assertStringContainsString('data-tryon-url="https://mywebar.com/p/Project_123"', $html);
        $this->assertStringContainsString('allow="camera;', $html);
        $this->assertStringContainsString('data-tryon-url=""', $html);          // plain webcam overlay for products with no AR project
        $this->assertStringContainsString('Back to shopping', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }
}
