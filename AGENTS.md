# AGENTS.md

This file provides guidance to Codex (Codex.ai/code) when working with code in this repository.

## Project Overview

This is the website for Dynal s.r.o. (https://www.dynal.cz), a Czech manufacturer of plastic and aluminum windows, doors, and winter gardens. It's a traditional PHP brochure site with no framework, no build tools, and no package manager.

## Development

No build step required. To run locally, serve with any PHP-capable web server:

```bash
php -S localhost:8000
```

Deploy by copying files directly to the production web server.

## Architecture

### Template System

Every page includes shared frame components via `include`. The pattern is:

```php
<?php include 'frame/head.php'; ?>
<?php include 'frame/header.php'; ?>
<!-- page content -->
<?php include 'frame/footer.php'; ?>
```

Pages in subdirectories adjust the path: `include '../frame/head.php'`.

### Key frame components

- `frame/head.php` — `<head>` with meta tags, Google Tag Manager (GTM-PZ8VXNSF), Google Fonts, and all CSS/JS includes
- `frame/header.php` — Logo, navigation, mobile menu, phone number
- `frame/footer.php` — Footer links mirroring the product category tree
- `frame/left-menu/menu-plast.php` / `menu-hlinik.php` — Sidebar navigation for plastic vs aluminum product sections
- `frame/service-panel/` — Reusable call-to-action panel, two variants (plastic / aluminum)

### URL Routing

Routing is purely directory-based — no framework or `.htaccess` routing. Each product category is a directory with an `index.php`.

### CSS Strategy

- `css/desktop.css` — Primary stylesheet; contains all layout, component styles, and keyframe animations (including the 30-second homepage slider)
- `css/mobile.css` — Responsive overrides for smaller screens
- `css/header.css` — Header-specific styles
- `css/gallery.css` / `new-gallery.css` / `newgallery.css` — Gallery variants (FancyBox-based)

### JavaScript

- `js/jquery.js` — jQuery 1.4.3
- `js/megas.js` — Smooth scroll and mobile burger menu toggle
- `js/modernizr.custom.js` — Feature detection polyfills
- `js/new-gallery.js` / `newgallery.js` — Gallery lightbox logic
- `js/poptavka/` — Scripts for the quote request form

### Form Processing

`poptavka.php` handles the quote request form. It includes IP logging, proxy detection via `getRealIp()`, and basic hack attempt detection. `odeslano.php` is the post-submit confirmation page.

### Product Directory Structure

Each major product category (e.g., `plastova-okna-a-dvere/`, `hlinikova-okna-a-dvere/`, `zimni-zahrady/`) contains its own `index.php` and any subcategory directories. There are 20+ such directories.
