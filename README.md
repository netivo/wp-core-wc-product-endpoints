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
        'type' => [type of endpoint], // One of: 'promotion'(sale_price > 0), 'bestseller'(total_sales>0), 'custom'
        'custom_endpoint' => function(WP_Query $query) { return $query; }, //custom modifier for query, works only with 'custom' type 
    )
    ...
);
```