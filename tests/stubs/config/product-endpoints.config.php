<?php
return [
	'promotions_test' => [
		'page_title'          => 'Promotions Test',
		'page_title_category' => 'Promotions in %s',
		'default_slug'        => 'promotions-slug',
		'breadcrumb_title'    => 'Promotions Crumb',
		'type'                => 'promotions',
	],
	'bestsellers_test' => [
		'page_title'          => 'Bestsellers Test',
		'page_title_category' => 'Bestsellers in %s',
		'default_slug'        => 'bestsellers-slug',
		'breadcrumb_title'    => 'Bestsellers Crumb',
		'type'                => 'bestsellers',
	],
	'custom_test' => [
		'page_title'          => 'Custom Test',
		'page_title_category' => 'Custom in %s',
		'default_slug'        => 'custom-slug',
		'breadcrumb_title'    => 'Custom Crumb',
		'type'                => 'custom',
		'custom_endpoint'     => function( $query ) {
			return $query;
		},
	],
];
