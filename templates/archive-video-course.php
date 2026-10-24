<?php
/**
 * Archive template for 'video_course' product type.
 *
 * Loaded by Rewrites::maybe_load_template() when csb_archive=1 and
 * csb_product_type=video_course.
 *
 * Layout, toggles, and per-page count are driven by AppearancePage::get().
 * Card data (instructor, category) comes from CardData helpers.
 * All prices are formatted with wc_price() so WooCommerce currency settings apply.
 *
 * @package BookaClass
 */

defined( 'ABSPATH' ) || exit;

use BookaClass\Admin\AppearancePage;
use BookaClass\Frontend\CardData;

get_header( 'shop' );

$seo_config    = (array) get_option( 'csb_seo_config', [] );
$archive_title = $seo_config['type_video_course']['archive_title'] ?? __( 'ویدیوکلاس‌ها', 'csb' );

$s               = AppearancePage::get();
$archive_width   = in_array( $s['archive_width'], [ 'boxed', 'full' ], true ) ? $s['archive_width'] : 'boxed';
$archive_sidebar = in_array( $s['archive_sidebar'], [ 'none', 'right', 'left' ], true ) ? $s['archive_sidebar'] : 'none';
$archive_layout  = in_array( $s['archive_layout'], [ 'grid', 'list' ], true ) ? $s['archive_layout'] : 'grid';
$archive_columns = in_array( (int) $s['archive_columns'], [ 2, 3, 4 ], true ) ? (int) $s['archive_columns'] : 3;
$card_style      = in_array( $s['card_style'], [ 'classic', 'editorial', 'ticket', 'overlay', 'minimal', 'horizontal' ], true ) ? $s['card_style'] : 'classic';
$show_filters    = ! empty( $s['show_filters'] );
$show_count      = ! empty( $s['show_result_count'] );
$card_cta_label  = esc_html( sanitize_text_field( $s['card_cta_label'] ?: __( 'مشاهده و ثبت‌نام', 'csb' ) ) );

// Active category filter from URL.
$active_cat  = sanitize_title( (string) ( $_GET['csb_cat'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Only product_cat terms that actually have 'video_course' type products.
$archive_terms = CardData::terms_for_type( 'video_course' );

// Current archive URL base — must point to /courses/ (not the WC shop).
$_csb_cfg_early   = \BookaClass\Products\ProductTypes::get_config();
$_csb_video_base  = sanitize_title( $_csb_cfg_early['types']['video_course']['classes_base'] ?? 'courses' );
$archive_base_url = trailingslashit( home_url( '/' . $_csb_video_base ) );
?>

<main id="csb-archive-main" class="csb csb-main csb-archive csb-archive--<?php echo esc_attr( $archive_width ); ?>" dir="rtl">

	<?php
	// ── Breadcrumb nav ────────────────────────────────────────────────────────
	// $_csb_video_base already set above.
	?>
	<nav class="csb-breadcrumb" aria-label="<?php esc_attr_e( 'مسیر صفحه', 'csb' ); ?>" dir="rtl">
		<ol class="csb-breadcrumb__list" role="list">
			<li class="csb-breadcrumb__item">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'خانه', 'csb' ); ?></a>
			</li>
			<li class="csb-breadcrumb__item csb-breadcrumb__item--current" aria-current="page">
				<?php echo esc_html( $archive_title ); ?>
			</li>
		</ol>
	</nav>

	<header class="csb-archive__header">
		<h1 class="csb-archive__title"><?php echo esc_html( $archive_title ); ?></h1>
		<?php if ( $show_count && have_posts() ) : ?>
			<p class="csb-archive__count">
				<?php
				$found = $GLOBALS['wp_query']->found_posts;
				/* translators: %d: course count */
				printf( esc_html( _n( '%d دوره', '%d دوره', $found, 'csb' ) ), (int) $found );
				?>
			</p>
		<?php endif; ?>
	</header>

	<div class="csb-archive__layout" data-sidebar="<?php echo esc_attr( $archive_sidebar ); ?>">

		<?php if ( 'none' !== $archive_sidebar ) : ?>
		<aside class="csb-archive__sidebar" aria-label="<?php esc_attr_e( 'فیلتر و دسته‌بندی', 'csb' ); ?>">

			<?php if ( is_active_sidebar( 'csb-archive-sidebar' ) ) : ?>
				<div class="csb-archive__sidebar-widgets">
					<?php dynamic_sidebar( 'csb-archive-sidebar' ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $archive_terms ) ) : ?>
			<div class="csb-archive__sidebar-cats">
				<h3 class="csb-archive__sidebar-heading"><?php esc_html_e( 'دسته‌بندی', 'csb' ); ?></h3>
				<ul class="csb-archive__cat-list">
					<li>
						<a href="<?php echo esc_url( $archive_base_url ); ?>"
							<?php echo '' === $active_cat ? ' aria-current="page"' : ''; ?>>
							<?php esc_html_e( 'همه', 'csb' ); ?>
						</a>
					</li>
					<?php foreach ( $archive_terms as $term ) : ?>
					<li>
						<a href="<?php echo esc_url( add_query_arg( 'csb_cat', $term->slug, $archive_base_url ) ); ?>"
							<?php echo $active_cat === $term->slug ? ' aria-current="page"' : ''; ?>>
							<?php echo esc_html( $term->name ); ?>
							<span class="csb-archive__cat-count">(<?php echo esc_html( (string) $term->count ); ?>)</span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

		</aside>
		<?php endif; ?>

		<div class="csb-archive__main">

			<?php if ( $show_filters ) : ?>
			<nav class="csb-archive__filters" aria-label="<?php esc_attr_e( 'فیلترها', 'csb' ); ?>">

				<div class="csb-archive__filter-group csb-archive__filter-cats">
					<a class="csb-filter-chip <?php echo '' === $active_cat ? 'csb-filter-chip--active' : ''; ?>"
						href="<?php echo esc_url( $archive_base_url ); ?>"
						<?php echo '' === $active_cat ? 'aria-current="page"' : ''; ?>>
						<?php esc_html_e( 'همه', 'csb' ); ?>
					</a>
					<?php foreach ( $archive_terms as $term ) : ?>
					<a class="csb-filter-chip <?php echo $active_cat === $term->slug ? 'csb-filter-chip--active' : ''; ?>"
						href="<?php echo esc_url( add_query_arg( 'csb_cat', $term->slug, $archive_base_url ) ); ?>"
						<?php echo $active_cat === $term->slug ? 'aria-current="page"' : ''; ?>>
						<?php echo esc_html( $term->name ); ?>
					</a>
					<?php endforeach; ?>
				</div>

			</nav>
			<?php endif; ?>

			<?php if ( have_posts() ) : ?>

			<div class="csb-cards csb-cards--<?php echo esc_attr( $card_style ); ?>"
				data-cols="<?php echo esc_attr( (string) $archive_columns ); ?>"
				data-layout="<?php echo esc_attr( $archive_layout ); ?>">

				<?php while ( have_posts() ) : the_post();
					$product = wc_get_product( get_the_ID() );
					if ( ! $product ) {
						continue;
					}
					include CSB_PATH . 'templates/partials/product-card.php';
				endwhile; ?>

			</div><!-- .csb-cards -->

			<?php
			$csb_total_pages = (int) $GLOBALS['wp_query']->max_num_pages;
			if ( $csb_total_pages > 1 ) :
				$csb_links = paginate_links( [
					'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999, false ) ) ),
					'format'    => '',
					'current'   => max( 1, (int) get_query_var( 'paged' ) ),
					'total'     => $csb_total_pages,
					'type'      => 'list',
					'end_size'  => 1,
					'mid_size'  => 1,
					'prev_text' => esc_html__( '« قبلی', 'csb' ),
					'next_text' => esc_html__( 'بعدی »', 'csb' ),
				] );
				if ( $csb_links ) {
					printf(
						'<nav class="woocommerce-pagination" aria-label="%s">%s</nav>',
						esc_attr__( 'صفحه‌بندی', 'csb' ),
						$csb_links // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- paginate_links output; base URL escaped above
					);
				}
			endif;
			?>

			<?php else : ?>
			<p class="csb-no-results"><?php esc_html_e( 'ویدیوکلاسی یافت نشد.', 'csb' ); ?></p>
			<?php endif; ?>

		</div><!-- .csb-archive__main -->

	</div><!-- .csb-archive__layout -->

</main><!-- .csb-archive -->

<?php get_footer( 'shop' ); ?>
