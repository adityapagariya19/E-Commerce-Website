<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Permanent guard against Blade ParseErrors (e.g. the earlier layouts/store.blade.php newsletter error):
 * every view must compile to syntactically valid PHP, and no view may mix inline @php(...) with @php...@endphp
 * (Blade's block regex would swallow everything between them).
 */
class BladeCompilationTest extends TestCase
{
    public function test_every_view_compiles_to_valid_php(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(resource_path('views')));
        $n = 0;
        foreach ($files as $f) {
            if (! str_ends_with($f->getFilename(), '.blade.php')) { continue; }
            $n++;
            $src = file_get_contents($f->getPathname());
            $name = str_replace(resource_path('views').'/', '', $f->getPathname());
            $this->assertFalse((bool) (preg_match('/@php\s*\(/', $src) && str_contains($src, '@endphp')), "{$name} mixes inline @php() and @php/@endphp blocks");
            $php = Blade::compileString($src);
            try {
                token_get_all('<?php ?>'.$php, TOKEN_PARSE);
            } catch (\ParseError $e) {
                $this->fail("{$name} compiles to invalid PHP: ".$e->getMessage().' on line '.$e->getLine());
            }
        }
        $this->assertGreaterThan(60, $n);
    }
}
