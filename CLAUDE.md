# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

This is a WordPress/WooCommerce module (a Composer package, not a standalone plugin) that adds
configurable "product endpoint" archive pages to a WooCommerce shop — e.g. sales/promotions,
bestsellers, or custom-filtered product listings, each with its own URL slug, page title, and SEO
metadata. It is consumed by a parent theme via Composer autoloading (PSR-4 root namespace
`Netivo\Module\WooCommerce\ProductEndpoints`, mapped to `src/`).

## Commands

Install dependencies:
```
composer install
```

Run the test suite:
```
composer test
```
which runs `phpunit` directly (equivalent to `vendor/bin/phpunit -d zend.enable_gc=0`). To run a
single test file or method, call phpunit directly, e.g.:
```
vendor/bin/phpunit tests/SomeTest.php
vendor/bin/phpunit --filter test_method_name
```

There is no build step, linter, or bundler configured in this repo.

## Testing setup

Tests use PHPUnit (10.5 or 11.0) with Brain Monkey for mocking WordPress functions/hooks. There is
no full WordPress install — `tests/bootstrap.php` defines `ABSPATH` and provides a minimal
`WP_Query` stub compatible with how `Module`/`Rewrite` use it (`query_vars`, `is_tax()`, `get()`,
`set()`, `parse_tax_query()`, `parse_query()`). Test classes should extend
`Netivo\Module\WooCommerce\ProductEndpoints\Tests\TestCase`, which wires up
`Brain\Monkey\setUp()`/`tearDown()` around PHPUnit's lifecycle. Currently no test files exist yet
beyond the bootstrap — this is the pattern to follow when adding them.

## Architecture

**Entry point and config**: `Module` (`src/Module.php`) is a singleton. `Module::get_instance()->init()`
must be called by the consuming theme/plugin to bootstrap the module (it is not self-initializing).
`init_config()` loads endpoint definitions from the *parent theme's* `config/product-endpoints.config.php`
via `get_stylesheet_directory()` — this file is expected to exist in the site that consumes this
package, not in this repo. `Module::get_config_array()` is the static accessor every other class
uses to read that config; the endpoint config array structure is documented in `README.md`
(`page_title`, `page_title_category`, `default_slug`, `breadcrumb_title`, `type` — one of
`promotion`/`bestseller`/`custom`, and `custom_endpoint` callback for the `custom` type).

**Request flow** — each config entry (keyed by an "endpoint id") becomes a WordPress rewrite
endpoint and a query var:
1. `Rewrite::register_shop_endpoints()` (hooked on `init`) registers 4 rewrite rules per endpoint
   (with/without a product category segment, with/without pagination), mapping the URL to
   `index.php?...&nt_products=<endpoint_id>`. The slug is overridable per-site via the WP option
   `netivo_<endpoint_id>_slug`, falling back to `default_slug` from config.
2. `Rewrite::modify_shop_query()` (hooked on `pre_get_posts`, priority 100) reads the `nt_products`
   query var on the main product-archive query and dispatches on `type` to filter by `_sale_price`
   or `_total_sales` postmeta, or hand off to the config's custom callback.
3. `Archive` (hooked on WooCommerce title/description/breadcrumb filters) rewrites the page title,
   clears the WooCommerce archive description, and splices a breadcrumb entry for the endpoint —
   including special handling to insert the crumb before category crumbs and to preserve a
   trailing pagination crumb.
4. `Archive::__construct()` conditionally instantiates `Integration\Yoast` (if `WPSEO_VERSION` is
   defined) and `Integration\RankMath` (if `RANK_MATH_VERSION` is defined) so each SEO plugin's
   title/description/canonical/prev-next filters stay consistent with the custom endpoint's title
   and URL. These two integration classes are structurally parallel (same public method names,
   same `get_custom_endpoint_url()` helper) — when fixing a bug or adding a field in one, check
   whether the same change applies to the other.
5. `Archive::__construct()` also conditionally instantiates `Integration\WidgetFilters` if the
   sibling Composer package `netivo/wc-widget-filters` is present (checked via
   `class_exists('Netivo\Module\WooCommerce\Filters\Widget\Filters')` — a soft runtime dependency,
   not a `composer.json` requirement). It hooks that module's `netivo/widget/filters/categories`
   and `netivo/widget/filters/parent` filters to rewrite the category filter widget's category,
   subcategory, and "back to parent" links so they point at the current product endpoint instead
   of the plain category archive, mirroring what `Archive::modify_breadcrumbs()` already does for
   breadcrumbs. The `netivo/widget/filters/parent` filter must exist on the `wc-widget-filters`
   side for the parent-link rewrite to take effect — see that module's own CLAUDE.md/docs.

`Module::get_endpoint_url( $var, $category_slug = '', $paged = 0 )` is the single shared helper for
building an endpoint's absolute URL (optionally scoped to a category and/or page number);
`Archive`, `Integration\Yoast`, `Integration\RankMath`, and `Integration\WidgetFilters` all call it
rather than duplicating the slug/permalink-structure logic.

**Everywhere else**, `get_query_var( 'nt_products' )` is the signal that the current request is on
one of these custom endpoints, and `Module::get_config_array()[$var]` is looked up to get that
endpoint's config entry. All classes guard against direct access with the `defined('ABSPATH')`
check at the top of the file, per WordPress convention.
