# Netivo WP Core WooCommerce Product Endpoints

This module adds product endpoints like sales, promotions, bestsellers, etc.

## Usage

In the main theme directory add config directory and inside create a file: `product-endpoints.config.php`

Which must return array with structure:

```php  
return array(
    '[endpoint_id]' => array (
        'page_title' => [page title],
        'page_title_category' => [page title for category], // add %s for category name
        'default_slug' => [default slug],
        'breadcrumb_title' => [breadcrumb title],
        'type' => [type of endpoint], // One of: 'promotion'(sale_price > 0), 'bestseller'(total_sales>0), 'custom'
        'custom_endpoint' => function(WP_Query $query) { return $query; }, //custom modifier for query, works only with 'custom' type 
    )
    ...
);
```

## Admin settings

Each endpoint's slug (initially set via `default_slug` in the config above) can be overridden per-site
under **Settings -> Permalinks**, in the "Optional" section, right after the WooCommerce base slugs.
Overrides are stored in the `netivo_<endpoint_id>_slug` option and take effect immediately (rewrite
rules are flushed on save).