# Visual QA checklist (to do on a running instance)

No screenshot comparison with the reference was possible in the build sandbox. Do this once the app runs:

1. Run `php artisan migrate:fresh --seed`, then `php artisan serve`.
2. Open each page at **1440 px, 1024 px (tablet), 390 px (phone)** and compare with `spectacle_shopping_website_screenshots_s_.pdf` / the video.
3. Check, page by page: header height (84 px) & nav spacing · ticker · hero crop & text position · collage proportions (1240×700, large tile 22 px radius) · product card ratio (1:1) & badge · promo banner · trust row · footer columns & newsletter box · shop sidebar & 3-column grid · PDP gallery (54 px thumbs) · cart/checkout columns · login modal · Help Centre buttons colours (#007bff / grey / green).
4. Sample real colours from the live reference with a colour picker and update the tokens at the top of `public/css/store.css` (`--blue`, `--footer`, …).
5. Confirm the real font (likely Poppins) and replace the inline logo (`layouts/store.blade.php`) with the supplied file.
6. Replace each `PLACEHOLDER` tile/slide in **Admin › Hero slides / Homepage blocks**.
