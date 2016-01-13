<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Most Viewed Products Widget
 *
 * @extends  WC_Widget
 *
 * @since 1.0.0
 */
class WCMVP_Widget_Most_Viewed extends WC_Widget {

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->widget_cssclass    = 'woocommerce widget_most_viewed_products';
		$this->widget_description = __( 'Display a list of most viewed products.', 'woo-most-viewed-products' );
		$this->widget_id          = 'woocommerce_widget_most_viewed_products';
		$this->widget_name        = __( 'WooCommerce Most Viewed', 'woo-most-viewed-products' );
		$this->settings           = array(
			'title'  => array(
				'type'  => 'text',
				'std'   => __( 'Most Viewed Products', 'woo-most-viewed-products' ),
				'label' => __( 'Title', 'woo-most-viewed-products' )
			),
			'number' => array(
				'type'  => 'number',
				'step'  => 1,
				'min'   => 1,
				'max'   => '',
				'std'   => 10,
				'label' => __( 'Number of products to show', 'woo-most-viewed-products' )
			)
		);
		parent::__construct();
	}

	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 *
	 * @param array $args
	 * @param array $instance
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function widget( $args, $instance ) {
		if ( $this->get_cached_widget( $args ) ) {
			return;
		}
		ob_start();
		$number                   = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : $this->settings['number']['std'];
		$count_key                = 'wcmvp_product_view_count';
		$query_args               = array(
			'posts_per_page' => $number,
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
		$r                        = new WP_Query( $query_args );
		if ( $r->have_posts() ) {
			$this->widget_start( $args, $instance );
			echo '<ul class="product_list_widget">';
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
			$this->widget_end( $args );
		} else {
			echo '<ul class="product_list_widget">';
			echo '<li>' . __( 'No products have been viewed yet !!', 'woo-most-viewed-products' ) . '</li>';
			echo '</ul>';
		}
		wp_reset_postdata();
		$content = ob_get_clean();
		echo $content;
		$this->cache_widget( $args, $content );
	}
}