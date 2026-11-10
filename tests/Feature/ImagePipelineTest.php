<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\Product;
use App\Support\ImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class ImagePipelineTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs($this->admin());
    }

    private function dims(string $path): array
    {
        $i = getimagesize(Storage::disk('public')->path($path));
        return [$i[0], $i[1], $i['mime']];
    }

    private function jpegWithOrientation(int $w, int $h, int $orientation): UploadedFile
    {
        $im = imagecreatetruecolor($w, $h);
        imagefill($im, 0, 0, imagecolorallocate($im, 200, 30, 30));
        ob_start(); imagejpeg($im, null, 90); $jpg = ob_get_clean();
        // minimal EXIF APP1 segment carrying only the Orientation tag
        $tiff = "MM\x00\x2a\x00\x00\x00\x08\x00\x01\x01\x12\x00\x03\x00\x00\x00\x01".pack('n', $orientation)."\x00\x00\x00\x00\x00\x00";
        $payload = "Exif\x00\x00".$tiff;
        $seg = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;
        return UploadedFile::fake()->createWithContent('phone.jpg', substr($jpg, 0, 2).$seg.substr($jpg, 2));
    }

    private function createProduct(UploadedFile $file): Product
    {
        $this->post(route('admin.products.store'), ['name' => 'Img '.uniqid(), 'type' => 'simple', 'status' => 'published', 'price' => 1000, 'new_images' => [$file]])->assertSessionHasNoErrors();
        return Product::latest('id')->firstOrFail();
    }

    public function test_large_photo_is_resized_converted_and_thumbnailed(): void
    {
        $p = $this->createProduct(UploadedFile::fake()->image('big.jpg', 3000, 2000));
        $path = $p->images()->first()->path;
        [$w, $h] = $this->dims($path);
        $this->assertSame([1200, 800], [$w, $h]);                                   // resized, aspect ratio kept
        $this->assertSame('image/webp', $this->dims(ImageProcessor::webpPath($path))[2]);  // WebP twin
        $this->assertSame(480, $this->dims(ImageProcessor::variantPath($path, 'thumb'))[0]);
        $this->assertSame(480, $this->dims(ImageProcessor::webpPath($path, 'thumb'))[0]);
    }

    public function test_small_image_is_never_upscaled(): void
    {
        $p = $this->createProduct(UploadedFile::fake()->image('small.png', 300, 200));
        $this->assertSame(300, $this->dims($p->images()->first()->path)[0]);
    }

    public function test_phone_photo_orientation_is_corrected(): void
    {
        $p = $this->createProduct($this->jpegWithOrientation(400, 200, 6)); // "rotate 90° clockwise" tag
        [$w, $h] = $this->dims($p->images()->first()->path);
        $this->assertSame([200, 400], [$w, $h]);
    }

    public function test_png_keeps_png_fallback_and_gets_webp(): void
    {
        $p = $this->createProduct(UploadedFile::fake()->image('logo.png', 800, 800));
        $path = $p->images()->first()->path;
        $this->assertStringEndsWith('.png', $path);
        $this->assertSame('image/png', $this->dims($path)[2]);
        Storage::disk('public')->assertExists(ImageProcessor::webpPath($path));
    }

    public function test_replace_removes_every_old_variant_but_nothing_else(): void
    {
        $this->post(route('admin.hero-slides.store'), ['heading' => 'H', 'cta_url' => '/shop', 'is_active' => 1, 'image' => UploadedFile::fake()->image('a.jpg', 2400, 900)])->assertSessionHasNoErrors();
        $s = HeroSlide::firstOrFail();
        $old = $s->image;
        $keeper = $this->createProduct(UploadedFile::fake()->image('keep.jpg', 800, 800))->images()->first()->path;
        $this->put(route('admin.hero-slides.update', $s), ['heading' => 'H', 'cta_url' => '/shop', 'image' => UploadedFile::fake()->image('b.jpg', 2400, 900)])->assertSessionHasNoErrors();
        foreach (ImageProcessor::allPaths($old) as $gone) { Storage::disk('public')->assertMissing($gone); }
        foreach (ImageProcessor::allPaths($keeper) as $kept) { Storage::disk('public')->assertExists($kept); }
        $this->assertNotSame($old, $s->fresh()->image);
        $this->assertSame(1920, $this->dims($s->fresh()->image)[0]); // hero profile
    }

    public function test_editing_a_record_without_a_new_file_keeps_the_existing_image(): void
    {
        $this->post(route('admin.hero-slides.store'), ['heading' => 'H', 'cta_url' => '/shop', 'is_active' => 1, 'image' => UploadedFile::fake()->image('a.jpg', 1600, 600)]);
        $s = HeroSlide::firstOrFail();
        $img = $s->image;
        $this->put(route('admin.hero-slides.update', $s), ['heading' => 'Edited', 'cta_url' => '/shop', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->assertSame($img, $s->fresh()->image);
        Storage::disk('public')->assertExists($img);
    }

    public function test_uploaded_hero_renders_on_homepage_and_the_url_actually_serves(): void
    {
        $this->post(route('admin.hero-slides.store'), ['heading' => 'Uploaded Hero', 'cta_url' => '/shop', 'is_active' => 1, 'image' => UploadedFile::fake()->image('a.jpg', 1600, 600)]);
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('type="image/webp"', $html);
        $this->assertStringContainsString('fetchpriority="high"', $html);                // hero is the LCP element
        $this->assertSame(1, preg_match('#<img class="bg" src="([^"]*/storage/uploads/hero/[^"]+\.jpg)"#', $html, $m), 'hero <img> must point at the upload');
        $res = $this->get($m[1])->assertOk();                                            // served by the /storage fallback route (no symlink in tests)
        $this->assertSame('image/jpeg', $res->headers->get('Content-Type'));
        $this->assertSame(1, preg_match('#srcset="([^"]*\.webp)"#', $html, $w));
        $this->get($w[1])->assertOk();
    }

    public function test_product_card_uses_thumbnail_and_fallback_for_missing_files(): void
    {
        $p = $this->createProduct(UploadedFile::fake()->image('p.jpg', 1500, 1500));
        $html = $this->get('/shop')->getContent();
        $this->assertStringContainsString('-thumb', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        // DB row pointing to a file that no longer exists must not break the page
        $p->images()->update(['path' => 'uploads/products/does-not-exist.jpg']);
        $this->get('/shop')->assertOk();
        $this->assertStringContainsString('/images/placeholders/generic.svg', media_url(null));
    }

    public function test_media_route_is_not_a_file_browser(): void
    {
        Storage::disk('public')->put('secret.txt', 'nope');
        Storage::disk('public')->put('uploads/products/script.php', '<?php echo 1;');
        foreach (['/storage/secret.txt', '/storage/uploads/products/script.php', '/storage/uploads/../secret.txt', '/storage/uploads/%2e%2e/secret.txt', '/storage/uploads/products/missing.jpg'] as $u) {
            $this->get($u)->assertNotFound();
        }
    }

    public function test_corrupt_or_disguised_files_are_rejected(): void
    {
        $this->post(route('admin.products.store'), ['name' => 'Bad', 'type' => 'simple', 'status' => 'draft', 'new_images' => [UploadedFile::fake()->createWithContent('x.jpg', 'definitely not an image')]])->assertSessionHasErrors('new_images.0');
        $this->assertSame(0, Product::count());
    }
}
