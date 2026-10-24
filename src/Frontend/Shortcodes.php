<?php
/**
 * Front-end shortcodes for rendering class / video_course card grids anywhere
 * (home page, landing pages, widgets). Reuses the shared product-card partial
 * and the existing .csb-cards styles.
 *
 * Shortcodes:
 *   [bookaclass_classes]  — class products only
 *   [bookaclass_courses]  — video_course products only
 *   [bookaclass_list]     — both (or filtered via type="")
 *
 * Attributes (all optional):
 *   type       class | video_course | video | both   (list default: both)
 *   count      number of items                        (default 6, max 24)
 *   columns    2 | 3 | 4                              (default: appearance setting)
 *   style      classic|editorial|ticket|overlay|minimal|horizontal (default: setting)
 *   category   product_cat slug(s), comma separated
 *   ids        explicit product IDs, comma separated  (overrides query order)
 *   orderby    date|rand|title|menu_order|price|popularity (default date)
 *   order      ASC | DESC                             (default DESC)
 *   title      optional heading shown above the grid
 *   link       optional "view all" URL
 *   link_text  label for the link                     (default «مشاهده همه»)
 *
 * @package BookaClass\Frontend
 */

namespace BookaClass\Frontend;

use BookaClass\Admin\AppearancePage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Shortcodes
 */
class Shortcodes {

	public function init(): void {
		add_shortcode( 'bookaclass_classes', [ $this, 'render_classes' ] );
		add_shortcode( 'bookaclass_courses', [ $this, 'render_courses' ] );
		add_shortcode( 'bookaclass_list',    [ $this, 'render_list' ] );
	}

	/**
	 * @param array<string,mixed>|string $atts
	 */
	public function render_classes( $atts ): string {
		return $this->render( array_merge( (array) $atts, [ 'type' => 'class' ] ) );
	}

	/**
	 * @param array<string,mixed>|string $atts
	 */
	public function render_courses( $atts ): string {
		return $this->render( array_merge( (array) $atts, [ 'type' => 'video_course' ] ) );
	}

	/**
	 * @param array<string,mixed>|string $atts
	 */
	public function render_list( $atts ): string {
		return $this->render( (array) $atts );
	}

	/**
	 * Core renderer.
	 *
	 * @param array<string,mixed> $atts
	 */
	private function render( array $atts ): string {
		$s = AppearancePage::get();

		$a = shortcode_atts(
			[
				'type'      => 'both',
				'count'     => 6,
				'columns'   => (int) ( $s['archive_columns'] ?? 3 ),
				'style'     => (string) ( $s['card_style'] ?? 'classic' ),
				'category'  => '',
				'ids'       => '',
				'orderby'   => 'date',
				'order'     => 'DESC',
				'title'     => '',
				'link'      => '',
				'link_text' => __( 'مشاهده همه', 'csb' ),
			],
			$atts,
			'bookaclass_list'
		);

		// ── Resolve product types ──────────────────────────────────────────────
		$type = 'video' === $a['type'] ? 'video_course' : (string) $a['type'];
		$types = 'both' === $type ? [ 'class', 'video_course' ] : [ $type ];
		$types = array_values( array_intersect( $types, [ 'class', 'video_course' ] ) );
		if ( empty( $types ) ) {
			$types = [ 'class', 'video_course' ];
		}

		// ── Validate presentation ──────────────────────────────────────────────
		$allowed_styles = [ 'classic', 'editorial', 'ticket', 'overlay', 'minimal', 'horizontal' ];
		$style   = in_array( $a['style'], $allowed_styles, true ) ? $a['style'] : 'classic';
		$columns = in_array( (int) $a['columns'], [ 2, 3, 4 ], true ) ? (int) $a['columns'] : 3;
		$count   = max( 1, min( 24, (int) $a['count'] ) );

		// ── Build query ─────────────────────────────────────────────────────────
		$tax_query = [
			[
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $types,
			],
		];

		$category = trim( (string) $a['category'] );
		if ( '' !== $category ) {
			$cats = array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $category ) ) ) );
			if ( ! empty( $cats ) ) {
				$tax_query[] = [
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $cats,
				];
			}
		}
		if ( count( $tax_query ) > 1 ) {
			$tax_query['relation'] = 'AND';
		}

		$allowed_orderby = [ 'date', 'rand', 'title', 'menu_order', 'price', 'popularity' ];
		$orderby = in_array( $a['orderby'], $allowed_orderby, true ) ? $a['orderby'] : 'date';
		$order   = 'ASC' === strtoupper( (string) $a['order'] ) ? 'ASC' : 'DESC';

		$q_args = [
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $count,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'order'               => $order,
		];

		if ( 'price' === $orderby ) {
			$q_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$q_args['orderby']  = 'meta_value_num';
		} elseif ( 'popularity' === $orderby ) {
			$q_args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$q_args['orderby']  = 'meta_value_num';
		} else {
			$q_args['orderby'] = $orderby;
		}

		$ids = array_filter( array_map( 'absint', array_map( 'trim', explode( ',', (string) $a['ids'] ) ) ) );
		if ( ! empty( $ids ) ) {
			$q_args['post__in'] = $ids;
			$q_args['orderby']  = 'post__in';
			unset( $q_args['meta_key'] );
		}

		$query = new \WP_Query( $q_args );
		if ( ! $query->have_posts() ) {
			wp_reset_postdata();
			return '';
		}

		// Guarantee the stylesheet is present even if is_csb_page() missed this page
		// (e.g. the shortcode lives in a widget or a builder-rendered area).
		wp_enqueue_style( 'csb-frontend', CSB_URL . 'assets/css/csb.css', [], CSB_VERSION );

		$has_head = '' !== trim( (string) $a['title'] ) || '' !== trim( (string) $a['link'] );

		ob_start();
		?>
		<section class="csb csb-shortcode" dir="rtl">
			<?php if ( $has_head ) : ?>
			<div class="csb-shortcode__head">
				<?php if ( '' !== trim( (string) $a['title'] ) ) : ?>
					<h2 class="csb-shortcode__title"><?php echo esc_html( $a['title'] ); ?></h2>
				<?php endif; ?>
				<?php if ( '' !== trim( (string) $a['link'] ) ) : ?>
					<a class="csb-shortcode__more" href="<?php echo esc_url( $a['link'] ); ?>">
						<?php echo esc_html( $a['link_text'] ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<div class="csb-cards csb-cards--<?php echo esc_attr( $style ); ?>" data-cols="<?php echo esc_attr( (string) $columns ); ?>">
				<?php
				while ( $query->have_posts() ) {
					$query->the_post();
					$product = wc_get_product( get_the_ID() );
					if ( ! $product ) {
						continue;
					}
					include CSB_PATH . 'templates/partials/product-card.php';
				}
				wp_reset_postdata();
				?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
