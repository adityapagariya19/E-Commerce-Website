<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    public function test_filters_sorting_search_and_visibility(): void
    {
        $cat = $this->category('Sunglasses');
        $a = $this->product(['name' => 'Alpha Round', 'price' => 1000]); $a->categories()->attach($cat);
        $b = $this->product(['name' => 'Beta Square', 'price' => 3000, 'sale_price' => 2000]);
        $this->product(['name' => 'Hidden Draft', 'price' => 500, 'status' => 'draft']);
        $this->product(['name' => 'Future Item', 'price' => 500, 'published_at' => now()->addDay()]);

        $this->get('/shop')->assertOk()->assertSee('Alpha Round')->assertSee('Beta Square')->assertDontSee('Hidden Draft')->assertDontSee('Future Item');
        $this->get('/shop?min_price=1500')->assertSee('Beta Square')->assertDontSee('Alpha Round');          // sale price (2000) is the effective price
        $this->get('/shop?max_price=1200')->assertSee('Alpha Round')->assertDontSee('Beta Square');
        $this->get('/shop?q=square')->assertSee('Beta Square')->assertDontSee('Alpha Round');
        $this->get(route('category', $cat))->assertSee('Alpha Round')->assertDontSee('Beta Square');
        $html = $this->get('/shop?orderby=price-desc')->getContent();
        $this->assertLessThan(strpos($html, 'Alpha Round'), strpos($html, 'Beta Square'));
    }

    public function test_load_more_returns_next_page_json(): void
    {
        for ($i = 0; $i < 8; $i++) { $this->product(['name' => "Item {$i}"]); }
        $this->get('/shop')->assertSee('View more');
        $this->getJson('/shop?page=2')->assertOk()->assertJsonStructure(['html', 'next', 'showing']);
    }

    public function test_draft_product_page_is_404(): void
    {
        $p = $this->product(['status' => 'draft']);
        $this->get(route('product', $p))->assertNotFound();
    }

    public function test_review_requires_rating_and_goes_to_moderation(): void
    {
        $p = $this->product();
        $this->post(route('product.review', $p), ['body' => 'Nice', 'name' => 'A', 'email' => 'a@test.test'])->assertSessionHasErrors('rating');
        $this->post(route('product.review', $p), ['rating' => 5, 'body' => '<b>Nice</b>', 'name' => 'A', 'email' => 'a@test.test'])->assertSessionHas('success');
        $this->assertDatabaseHas('reviews', ['product_id' => $p->id, 'status' => 'pending', 'body' => 'Nice']);
        $this->get(route('product', $p))->assertDontSee('Nice</b>');
    }
}
