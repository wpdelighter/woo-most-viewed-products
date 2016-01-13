<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode
 *
 * @param $atts attributes
 *
 * @return string rendered products output
 *
 * @since 1.0.0
 */
function wcmvp_most_viewed_products_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'product_count' => '10',
		),
		$atts
	);

	$content = wcmvp_render_most_viewed_products( $atts['product_count'] );

	return $content;
}

add_shortcode( 'wcmvp', 'wcmvp_most_viewed_products_shortcode' );