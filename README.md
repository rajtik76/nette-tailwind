Nette + Tailwind CSS
====================

[![CI](https://github.com/rajtik76/nette-tailwind/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/rajtik76/nette-tailwind/actions/workflows/ci.yml)

A starter for PHP web applications built on [Nette](https://nette.org) with
[Tailwind CSS 4](https://tailwindcss.com). It is the official
[nette/web-project](https://github.com/nette/web-project) skeleton with Tailwind
wired into its Vite build.

- PHP 8.4+, Nette 3.2, Latte 3
- Tailwind CSS 4 via `@tailwindcss/vite`
- Vite 8 with `@nette/vite-plugin`, assets served by `nette/assets`
- `nette-forms` for client-side form validation


Requirements
------------

- PHP 8.4 or newer
- Composer
- Node.js 22.12+ and npm (`.nvmrc` pins the version used in CI)


Installation
------------

	git clone https://github.com/rajtik76/nette-tailwind.git
	cd nette-tailwind
	composer install
	npm install
	npm run build

Make sure the `temp/` and `log/` directories are writable.

`npm run build` is required. Vite is enabled in `config/common.neon`, so the page
fails with "Failed to read Vite manifest" until the assets are built.


Development
-----------

Run the Vite dev server and a PHP server side by side:

	npm run dev
	php -S localhost:8000 -t www

Then open `http://localhost:8000`. In debug mode, `{asset 'main.js'}` loads assets
from the Vite dev server, so changes to templates and CSS show up immediately.
Without the dev server running, the last `npm run build` output is used.

Laravel Herd / Valet need a custom driver that serves `www/` as the document root.
`wwwDir` is set explicitly in `app/Bootstrap.php`, because these servers boot
`index.php` through their own router script.


Console
-------

`php nette` is the Nette counterpart of `php artisan`, provided by
[rajtik76/nette-console](https://github.com/rajtik76/nette-console):

	php nette list                       # all commands
	php nette make:command SendReport    # app/Console/SendReportCommand.php, runs as app:send-report
	php nette cache:clear                # delete temp/cache
	php nette package:discover           # extensions registered by installed packages

Packages declare their DI extensions in `composer.json` (`extra.nette.extensions`) and
`Bootstrap` registers them automatically, so their commands work right after `composer require`.
Classes ending with `Command` in `app/` are registered by `search` in `services.neon`.

The console runs in debug mode, so config changes apply without clearing the cache.
Set `NETTE_DEBUG=0` in production.


Checks
------

	composer run lint:check    # Latte templates (own engine, warnings fail) + NEON config
	composer run type:check    # PHPStan level 8
	composer run tester        # Nette Tester smoke tests
	composer run ci:check      # all of the above, same as GitHub CI

Tests render the layout, so run `npm run build` first.
CI runs on PHP 8.4 and 8.5, Node version comes from `.nvmrc`.


How Tailwind is wired in
------------------------

- `assets/main.css` imports Tailwind and adds `@source "../app"`. Vite's root is
  `assets/`, so without it Tailwind would not scan the Latte templates in `app/`.
- `assets/main.js` is the Vite entry point and imports `main.css`.
- `vite.config.ts` registers the `tailwindcss()` plugin next to the Nette plugin.
- `app/Presentation/@layout.latte` renders `{asset? 'main.js'}`, which outputs both
  the `<script>` and the `<link rel="stylesheet">` for the bundled CSS.

Use Tailwind classes directly in any `.latte` file under `app/`.


Welcome and error pages
-----------------------

- Colors and the font are defined in `@theme` in `assets/main.css`. The font
  (Instrument Sans) is loaded from [Bunny Fonts](https://fonts.bunny.net) by the
  welcome page only, through the `head` block of `@layout.latte`. Error pages make
  no external requests and fall back to the system font.
- The cube illustrations are inline SVG blocks in `app/Presentation/@cubes.latte`.
- `Error4xx/@layout.latte` wraps every 4xx template in a shared frame, so
  `403.latte`, `404.latte` etc. stay plain markup.
- `500.phtml` and `503.phtml` use neither Tailwind nor the layout, because they
  must render even when the app or the asset build is broken. Their CSS is inline,
  so when you change colors in `main.css`, update them there by hand.


Production
----------

	npm run build

Point the web server's document root to `www/`. Built assets go to `www/assets/`
(ignored by git, so build them on deploy).

**Important:** `app/`, `config/`, `log/` and `temp/` must never be web-accessible.
See the [security warning](https://nette.org/security-warning).
