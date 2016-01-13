<?php

/**
 * Plugin Name:       Woo Most Viewed Products
 * Plugin URI:        https://wordpress.org/plugins/woo-most-viewed-products/
 * Description:       Display a list of most viewed products on WooCommerce.
 * Version:           1.0.0
 * Author:            WP Delighter
 * Author URI:        https://wpdelighter.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-most-viewed-products
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Show admin notice & de-activate itself if WooCommerce plugin is not active.
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'wcmvp_has_parent_plugin' ) ) {
	function wcmvp_has_parent_plugin() {
		if ( is_admin() && ( ! class_exists( 'WooCommerce' ) && current_user_can( 'activate_plugins' ) ) ) {
			add_action( 'admin_notices', create_function( null, 'echo \'<div class="error"><p>\' . sprintf( __( \'Activation failed: <strong>WooCommerce</strong> must be activated to use the <strong>WooCommerce Most Viewed Products</strong> plugin. %sVisit your plugins page to install and activate.\', \'woo-most-viewed-products\' ), \'<a href="\' . admin_url( \'plugins.php#woocommerce\' ) . \'">\' ) . \'</a></p></div>\';' ) );

			deactivate_plugins( plugin_basename( __FILE__ ) );

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
	}
}

add_action( 'admin_init', 'wcmvp_has_parent_plugin' );

/**
 * Set view count for a product
 *
 * @param $post_id product id
 *
 * @since 1.0.0
 */
function wcmvp_set_view_count( $post_id ) {
	$count_key = 'wcmvp_product_view_count';
	$count     = get_post_meta( $post_id, $count_key, true );
	if ( $count == '' ) {
		delete_post_meta( $post_id, $count_key );
		update_post_meta( $post_id, $count_key, '1' );
	} else {
		$count ++;
		update_post_meta( $post_id, $count_key, (string) $count );
	}
}

/**
 * Get the view count for a particular product
 *
 * @param $post_id product id
 *
 * @return mixed|string product view count
 *
 * @since 1.0.0
 */
function wcmvp_get_view_count( $post_id ) {
	$count_key = 'wcmvp_product_view_count';
	$count     = get_post_meta( $post_id, $count_key, true );
	if ( empty( $count ) ) {
		delete_post_meta( $post_id, $count_key );
		add_post_meta( $post_id, $count_key, '0' );
		$count = '0';
	}

	return $count;
}

/**
 * Get the WP_Query instance for most viewed products
 *
 * @param int $num_posts number of postst to display
 *
 * @return WP_Query most viewed products query
 *
 * @since 1.0.0
 */
function wcmvp_get_most_viewed_products( $num_posts = 10 ) {
	$count_key                = 'wcmvp_product_view_count';
	$query_args               = array(
		'posts_per_page' => $num_posts,
		'no_found_rows'  => 1,
		'post_status'    => 'publish',
		'post_type'      => 'product',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
		'meta_key'       => $count_key,
	);
	$query_args['meta_query'] = array(
		array(
			'key'     => $count_key,
			'value'   => '0',
			'type'    => 'numeric',
			'compare' => '>',
		),
	);
	$wcmvp_query              = new WP_Query( $query_args );

	return $wcmvp_query;
}

/**
 * Get the product view count html text
 *
 * @param int $product_id
 *
 * @return string product view count html
 *
 * @since 1.0.0
 */
function wcmvp_get_view_count_html( $product_id = 0 ) {
	if ( empty( $product_id ) ) {
		return '';
	}
	$view_count      = wcmvp_get_view_count( $product_id );
	$view_count_html = '<span class="product-views">' . $view_count . ' ' . __( 'Views', 'woo-most-viewed-products' ) . '  </span>';

	return apply_filters( 'wcmvp_view_count_html', $view_count_html, $product_id, $view_count );
}

/**
 * @param int $num_posts
 *
 * @return string
 *
 * @since 1.0.0
 */
function wcmvp_render_most_viewed_products( $num_posts = 10 ) {
	$r = wcmvp_get_most_viewed_products( $num_posts );
	ob_start();
	if ( $r->have_posts() ) {
		echo '<ul class="woo-most-viewed product_list_widget">';
		while ( $r->have_posts() ) {
			$r->the_post();
			global $product;
			?>
			<li>
				<a href="<?php echo esc_url( get_permalink( $product->id ) ); ?>"
				   title="<?php echo esc_attr( $product->get_title() ); ?>">
					<?php echo $product->get_image(); ?>
					<span class="product-title"><?php echo $product->get_title(); ?></span>
				</a>
				<?php echo wcmvp_get_view_count_html( $product->id ); ?>
				<?php echo $product->get_price_html(); ?>
			</li>
			<?php
		}
		echo '</ul>';
	} else {
		echo '<ul class="woo-most-viewed wcmvp-not-found product_list_widget">';
		echo '<li>' . __( 'No products have been viewed yet !!', 'woo-most-viewed-products' ) . '</li>';
		echo '</ul>';
	}
	wp_reset_postdata();
	$content = ob_get_clean();

	return $content;
}

/**
 * Display popular products
 *
 * @param int $num_posts number of products to display
 *
 * @since 1.0.0
 */
function wcmvp_display_most_viewed_products( $num_posts = 10 ) {
	$content = wcmvp_render_most_viewed_products( $num_posts );
	echo $content;
}

/**
 * Set view counts for all products once viewed
 *
 * @since 1.0.0
 */
function wcmvp_set_view_count_products() {
	global $product;
	wcmvp_set_view_count( $product->id );
}

/**
 * Register the widget "Most Viewed Products"
 *
 * @since 1.0.0
 */
function wcmvp_register_widgets() {
	register_widget( 'WCMVP_Widget_Most_Viewed' );
}

/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function wcmvp_load_textdomain() {
	load_plugin_textdomain( 'woo-most-viewed-products', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}

/**
 * Load plugin functionalities
 *
 * @since 1.0.0
 */
function wcmvp_plugin_load() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	wcmvp_load_textdomain();

	require_once( 'woo-most-viewed-products-widget.php' );
	require_once( 'woo-most-viewed-products-shortcode.php' );

	add_action( 'woocommerce_after_single_product', 'wcmvp_set_view_count_products' );
	add_action( 'widgets_init', 'wcmvp_register_widgets' );
}

add_action( 'plugins_loaded', 'wcmvp_plugin_load' );

