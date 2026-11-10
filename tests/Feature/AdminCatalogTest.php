<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    private function payload(array $o = []): array
    {
        return $o + ['name' => 'New Frame', 'type' => 'simple', 'status' => 'published', 'price' => 2000, 'sale_price' => 1500, 'stock_qty' => 7, 'manage_stock' => 1, 'is_new_arrival' => 1];
    }

    public function test_create_edit_and_archive_product(): void
    {
        $this->actingAs($this->admin('content_manager'));
        $cat = $this->category('Frames');
        $this->post(route('admin.products.store'), $this->payload(['category_ids' => [$cat->id], 'tags' => 'a, b']))->assertRedirect();
        $p = Product::where('name', 'New Frame')->firstOrFail();
        $this->assertSame('new-frame', $p->slug);
        $this->assertSame(['a', 'b'], $p->tags);
        $this->assertTrue($p->categories->contains($cat));
        $this->assertNotNull($p->published_at);
        $this->put(route('admin.products.update', $p), $this->payload(['name' => 'Renamed', 'price' => 2500, 'sale_price' => '']))->assertRedirect();
        $this->assertSame('Renamed', $p->fresh()->name);
        // with an order → archived, not deleted
        $this->order([[$p->fresh(), 1]]);
        $this->delete(route('admin.products.destroy', $p))->assertRedirect();
        $this->assertSame('archived', $p->fresh()->status);
    }

    public function test_product_validation(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.products.store'), $this->payload(['name' => '', 'sale_price' => 5000]))->assertSessionHasErrors(['name', 'sale_price']);
        $this->post(route('admin.products.store'), $this->payload(['type' => 'variable']))->assertStatus(422); // needs variants
        $this->assertSame(0, Product::count());
    }

    public function test_variable_product_price_is_lowest_variant_price(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.products.store'), $this->payload(['type' => 'variable', 'price' => null, 'sale_price' => null, 'variants' => [
            ['label' => 'M', 'price' => 1200, 'stock_qty' => 3, 'is_active' => 1], ['label' => 'L', 'price' => 900, 'stock_qty' => 2, 'is_active' => 1], ['label' => '', 'price' => '']]]))->assertRedirect();
        $p = Product::firstOrFail();
        $this->assertEquals(900, (float) $p->price);
        $this->assertSame(2, $p->variants()->count());
    }

    public function test_image_upload_accepts_valid_images_and_rejects_unsafe_files(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $this->post(route('admin.products.store'), $this->payload(['new_images' => [UploadedFile::fake()->image('ok.jpg', 400, 400)]]))->assertRedirect();
        $path = Product::firstOrFail()->images()->first()->path;
        $this->assertStringStartsWith('uploads/products/', $path);
        Storage::disk('public')->assertExists($path);

        foreach ([
            UploadedFile::fake()->create('evil.php', 10, 'application/x-php'),
            UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->create('big.jpg', 9000, 'image/jpeg'),
            UploadedFile::fake()->image('tiny.png', 20, 20),
            UploadedFile::fake()->create('fake.jpg', 10, 'text/plain'),
        ] as $bad) {
            $this->post(route('admin.products.store'), $this->payload(['name' => 'Bad '.$bad->getClientOriginalName(), 'new_images' => [$bad]]))->assertSessionHasErrors('new_images.0');
        }
        $this->assertSame(1, Product::count());
    }

    public function test_hero_slide_rejects_javascript_urls_and_replaces_image(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $this->post(route('admin.hero-slides.store'), ['heading' => 'H', 'cta_url' => 'javascript:alert(1)', 'image' => UploadedFile::fake()->image('a.jpg', 800, 300)])->assertSessionHasErrors('cta_url');
        $this->post(route('admin.hero-slides.store'), ['heading' => 'H', 'cta_url' => '/shop', 'is_active' => 1, 'image' => UploadedFile::fake()->image('a.jpg', 800, 300)])->assertRedirect();
        $s = HeroSlide::firstOrFail();
        $old = $s->image;
        $this->put(route('admin.hero-slides.update', $s), ['heading' => 'H2', 'cta_url' => '/shop', 'image' => UploadedFile::fake()->image('b.jpg', 800, 300)])->assertRedirect();
        $this->assertNotSame($old, $s->fresh()->image);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_homepage_content_changes_without_code(): void
    {
        $this->actingAs($this->admin());
        HeroSlide::create(['heading' => 'Original heading', 'image' => 'images/placeholders/hero-eye.svg', 'is_active' => true]);
        $this->get('/')->assertSee('Original heading');
        $this->put(route('admin.hero-slides.update', HeroSlide::first()), ['heading' => 'Changed by admin', 'cta_url' => '/shop', 'is_active' => 1])->assertRedirect();
        $this->get('/')->assertSee('Changed by admin')->assertDontSee('Original heading');
    }
}
