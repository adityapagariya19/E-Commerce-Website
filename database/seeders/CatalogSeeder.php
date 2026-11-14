<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DEMO catalogue. Product names/prices mirror what is visible in the reference screenshots; everything else
 * (descriptions, stock, SKUs) is invented demo data and is labelled as such. Images are labelled placeholders.
 * NOTE: the reference lists both "Spectacle Frame" and "Spectacle Frames"; they are merged into one category here.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $cats = [];
        foreach (['Accessories', 'John Jacobs', 'Kids Spectacles', 'Lens', 'Man', 'Spectacle Frames', 'Spectacles', 'Sunglasses', 'Uncategorized'] as $i => $name) {
            $cats[$name] = Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'position' => $i, 'is_active' => true]);
        }
        $brands = [];
        foreach (['John Jacobs', 'Vincent Chase', 'Air', 'Voyage', 'Aquacolor', 'Villain', 'Generic'] as $b) {
            $brands[$b] = Brand::firstOrCreate(['slug' => Str::slug($b)], ['name' => $b, 'is_active' => true]);
        }

        // [name, brand, price, sale, categories, stock, flags(new,featured,tryon), imagePath, type]
        $rows = [
            ['Equate Streak-Free Lens Cleaner 8 oz Spray Bottle', 'Generic', 169, 149, ['Accessories'], 40, ['new'], 'images/products/lens-cleaner.jpg'],
            ['MR.Siga Microfiber Cleaning Cloths for Lens, Eyeglasses, Gray and Navy 6 Pack, 8 x 7 Inches', 'Generic', 499, 449, ['Accessories'], 60, ['new'], 'images/products/microfiber-cloths.jpg'],
            ['Voyage Folding Spectacle Case', 'Voyage', 199, null, ['Accessories'], 25, ['new'], 'images/products/folding-case.jpg'],
            ['Eyeglass Sunglass Repair with Nose Pads Washer Caps Screwdriver Tiny Micro Screws Assortment Stainless Spectacles Watch', 'Generic', 699, 599, ['Accessories'], 30, ['new'], 'images/products/repair-kit.jpg'],
            ['John Jacobs Transparent Full Rim Cat Eye with Premium Anti-Glare Lenses', 'John Jacobs', 3500, 2500, ['John Jacobs', 'Spectacle Frames', 'Spectacles'], 12, ['featured', 'tryon'], 'images/products/cat-eye.jpg'],
            ['Aquacolor Candypack Misty Gray Zero Power Daily', 'Aquacolor', 179, null, ['Lens'], 80, [], 'images/products/aquacolor.jpg'],
            ['VILLAIN WINGMAN BOTTLE OPENER SUNGLASSES', 'Villain', 599, null, ['Sunglasses', 'Man'], 15, [], 'images/products/villain-wingman.jpg'],
            ['Vincent Chase Online Pink Full Rim Wayfarer', 'Vincent Chase', 800, null, ['Sunglasses'], 18, ['tryon'], 'images/products/vincent-chase-pink.jpg'],
            ['Air Light Blue Square Eyeglasses (TR02MG4541-C6)', 'Air', 2199, 999, ['Spectacle Frames', 'Spectacles', 'Man'], 20, ['featured'], 'images/products/air-light-blue.jpg'],
            ['Air Online Green Full Rim Round with Premium Anti-Glare Lenses', 'Air', 2000, 800, ['Spectacle Frames', 'Spectacles'], 22, ['featured', 'tryon'], 'images/products/air-green-round.jpg'],
            ['Air Transparent Full Rim Round with Premium Anti-Glare Lenses', 'Air', 2000, 1500, ['Spectacle Frames', 'Spectacles'], 14, [], 'images/products/air-transparent-round.jpg'],
            ['Apex Transparent Square Eyeglasses (V62005MG5369-C4)', 'Generic', null, null, ['Spectacle Frames', 'Man'], 0, [], 'images/products/air-transparent-round.jpg'],
            ['Black & Silver Retro Square Sunglasses (MG2970-C1)', 'Generic', 3000, 2299, ['Sunglasses', 'Man'], 9, [], 'images/products/retro-square-sunglasses.jpg'],
            ['John Jacobs Green Full Rim Wayfarer', 'John Jacobs', 4000, 2400, ['John Jacobs', 'Spectacles', 'Sunglasses'], 11, ['featured', 'tryon'], 'images/products/air-green-round.jpg'],
            ['Pink Gradient Black Full Rim Wayfarer', 'Generic', 1800, null, ['Sunglasses'], 10, ['tryon'], 'images/products/vincent-chase-pink.jpg'],
            ['Kids Flexi Round Frame', 'Generic', 1200, 999, ['Kids Spectacles', 'Spectacle Frames'], 16, [], 'images/products/air-green-round.jpg'],
        ];

        foreach ($rows as $i => [$name, $brand, $price, $sale, $catNames, $stock, $flags, $img]) {
            $p = Product::firstOrCreate(['slug' => Str::slug($name)], [
                'brand_id' => $brands[$brand]->id, 'name' => $name, 'sku' => 'JBO-'.str_pad($i + 1, 4, '0', STR_PAD_LEFT), 'type' => 'simple',
                'short_description' => 'Premium optical eyewear designed for everyday comfort, style, and clear vision.',
                'description' => "High-grade optical eyewear crafted with lightweight, durable materials. Engineered for a comfortable fit and optimal vision clarity. Compatible with single vision, bifocal, and progressive prescription lenses.",
                'price' => $price, 'sale_price' => $sale, 'stock_qty' => $stock, 'manage_stock' => true, 'status' => 'published',
                'is_featured' => in_array('featured', $flags), 'is_new_arrival' => in_array('new', $flags), 'tryon_enabled' => in_array('tryon', $flags),
                'specs' => array_filter(['gender' => in_array('Man', $catNames) ? 'Men' : 'Unisex', 'frame_shape' => Str::contains($name, ['Round']) ? 'Round' : (Str::contains($name, ['Square']) ? 'Square' : (Str::contains($name, ['Wayfarer']) ? 'Wayfarer' : null)),
                    'frame_color' => Str::contains($name, ['Green']) ? 'Green' : (Str::contains($name, ['Pink']) ? 'Pink' : (Str::contains($name, ['Transparent']) ? 'Transparent' : null))]),
                'tags' => ['eyewear', 'optical', 'jboptician'], 'published_at' => now()->subDays(30 - $i),
            ]);
            if ($p->wasRecentlyCreated) {
                $p->categories()->sync(collect($catNames)->map(fn ($n) => $cats[$n]->id));
                $imgPath = str_starts_with($img, 'images/') ? $img : "images/placeholders/prod-{$img}.svg";
                $p->images()->create(['path' => $imgPath, 'alt' => $name, 'position' => 0]);
            }
        }

        // One VARIABLE product so variants/stock/pricing paths are exercised.
        $v = Product::firstOrCreate(['slug' => 'vincent-chase-polarized-black-full-rim-wayfarer'], [
            'brand_id' => $brands['Vincent Chase']->id, 'name' => 'Vincent Chase Polarized Black Full Rim Wayfarer', 'sku' => 'JBO-VAR-001', 'type' => 'variable',
            'short_description' => 'Classic polarized full rim wayfarer sunglasses with UV protection.', 'description' => 'Engineered with premium polarized lenses that reduce glare and enhance visual clarity. Features lightweight acetate frames and durable metal hinges for long-lasting comfort.', 'price' => 1499, 'stock_qty' => 0, 'manage_stock' => true,
            'status' => 'published', 'is_featured' => true, 'tryon_enabled' => true, 'specs' => ['gender' => 'Unisex', 'frame_shape' => 'Wayfarer', 'frame_color' => 'Black'],
            'tags' => ['sunglasses', 'polarized', 'jboptician'], 'published_at' => now()->subDays(2),
        ]);
        if ($v->wasRecentlyCreated) {
            $v->categories()->sync([$cats['Sunglasses']->id]);
            $v->images()->create(['path' => 'images/products/villain-wingman.jpg', 'alt' => $v->name, 'position' => 0]);
            $v->variants()->createMany([
                ['label' => 'Medium', 'sku' => 'DEMO-VAR-001-M', 'price' => 1499, 'stock_qty' => 10, 'is_active' => true, 'options' => ['size' => 'Medium']],
                ['label' => 'Large', 'sku' => 'DEMO-VAR-001-L', 'price' => 1599, 'sale_price' => 1399, 'stock_qty' => 6, 'is_active' => true, 'options' => ['size' => 'Large']],
            ]);
        }
    }
}
