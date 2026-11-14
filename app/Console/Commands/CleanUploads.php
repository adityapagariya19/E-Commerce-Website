<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\HomeBlock;
use App\Models\ProductImage;
use App\Support\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanUploads extends Command
{
    protected $signature = 'jbo:clean-uploads {--dry-run : List files that would be deleted without deleting them}';
    protected $description = 'Prune orphaned and redundant uploaded files not referenced in the database.';

    public function handle(): int
    {
        $this->info('Scanning database for active image references...');

        $activeRefs = collect();

        // 1. Hero slides
        $activeRefs = $activeRefs->merge(HeroSlide::pluck('image')->filter());
        $activeRefs = $activeRefs->merge(HeroSlide::pluck('mobile_image')->filter());

        // 2. Homepage blocks
        $activeRefs = $activeRefs->merge(HomeBlock::pluck('image')->filter());

        // 3. Product images
        $activeRefs = $activeRefs->merge(ProductImage::pluck('path')->filter());

        // 4. Categories
        $activeRefs = $activeRefs->merge(Category::pluck('image')->filter());

        // 5. Brands
        $activeRefs = $activeRefs->merge(Brand::pluck('logo')->filter());

        // Build complete set of all legitimate file paths (including main file and any legacy variants)
        $legitimatePaths = [];
        foreach ($activeRefs as $ref) {
            $cleanRef = preg_replace('#^/?(storage/)?#', '', trim($ref));
            if (str_starts_with($cleanRef, 'uploads/')) {
                $legitimatePaths[$cleanRef] = true;
                foreach (ImageProcessor::allPaths($cleanRef) as $p) {
                    $legitimatePaths[$p] = true;
                }
            }
        }

        $disk = Storage::disk('public');
        $allFiles = $disk->allFiles('uploads');

        $orphans = [];
        $bytesSaved = 0;

        foreach ($allFiles as $file) {
            // Keep .gitkeep
            if (str_ends_with($file, '.gitkeep')) {
                continue;
            }

            if (! isset($legitimatePaths[$file])) {
                $orphans[] = $file;
                $bytesSaved += $disk->size($file);
            }
        }

        if (empty($orphans)) {
            $this->info('No orphaned or redundant upload files found. Storage is 100% clean!');
            return self::SUCCESS;
        }

        $count = count($orphans);
        $kb = round($bytesSaved / 1024, 2);

        if ($this->option('dry-run')) {
            $this->warn("Found {$count} orphaned file(s) ({$kb} KB) that can be pruned:");
            foreach ($orphans as $f) {
                $this->line(" - {$f}");
            }
            return self::SUCCESS;
        }

        $disk->delete($orphans);
        $this->info("Successfully deleted {$count} redundant file(s), freeing {$kb} KB of storage.");

        return self::SUCCESS;
    }
}
