<?php
/**
 * Archive template for 'class' product type.
 *
 * Loaded by Rewrites::maybe_load_template() when csb_archive=1 and
 * csb_product_type=class.
 *
 * Layout, toggles, and per-page count are driven by AppearancePage::get().
 * Card data (sessions, instructor, category) comes from CardData helpers.
 * All prices are formatted with wc_price() so WooCommerce currency settings apply.
 *
 * @package BookaClass
 */

defined( 'ABSPATH' ) || exit;

use BookaClass\Admin\AppearancePage;
use BookaClass\Frontend\CardData;
use BookaClass\Support\Jalali;

get_header( 'shop' );

$seo_config    = (array) get_option( 'csb_seo_config', [] );
$archive_title = $seo_config['type_class']['archive_title'] ?? __( 'کلاس‌های آموزشی', 'csb' );

$s               = AppearancePage::get();
$archive_width   = in_array( $s['archive_width'], [ 'boxed', 'full' ], true ) ? $s['archive_width'] : 'boxed';
$archive_sidebar = in_array( $s['archive_sidebar'], [ 'none', 'right', 'left' ], true ) ? $s['archive_sidebar'] : 'none';
$archive_layout  = in_array( $s['archive_layout'], [ 'grid', 'list' ], true ) ? $s['archive_layout'] : 'grid';
$archive_columns = in_array( (int) $s['archive_columns'], [ 2, 3, 4 ], true ) ? (int) $s['archive_columns'] : 3;
$card_style      = in_array( $s['card_style'], [ 'classic', 'editorial', 'ticket', 'overlay', 'minimal', 'horizontal' ], true ) ? $s['card_style'] : 'classic';
$show_filters    = ! empty( $s['show_filters'] );
$show_count      = ! empty( $s['show_result_count'] );
$card_cta_label  = esc_html( sanitize_text_field( $s['card_cta_label'] ?: __( 'مشاهده و ثبت‌نام', 'csb' ) ) );

// Active category / type filters from URL.
$active_cat  = sanitize_title( (string) ( $_GET['csb_cat']  ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$active_type = sanitize_key(   (string) ( $_GET['csb_type'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// Only product_cat terms that actually have 'class' type products.
$archive_terms = CardData::terms_for_type( 'class' );

// Current archive URL base — must point to /classes/ (not the WC shop).
$_csb_cfg_early   = \BookaClass\Products\ProductTypes::get_config();
$_csb_class_base  = sanitize_title( $_csb_cfg_early['types']['class']['classes_base'] ?? 'classes' );
$archive_base_url = trailingslashit( home_url( '/' . $_csb_class_base ) );
?>

<main id="csb-archive-main" class="csb csb-main csb-archive csb-archive--<?php echo esc_attr( $archive_width ); ?>" dir="rtl">

	<?php
	// ── Breadcrumb nav ────────────────────────────────────────────────────────
	// $_csb_class_base already set above.
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

		<?php
		// Pre-build type-filter URLs, preserving current category selection.
		$_csb_url_type_all  = '' !== $active_cat ? add_query_arg( 'csb_cat', $active_cat, $archive_base_url ) : $archive_base_url;
		$_csb_type_base     = $_csb_url_type_all;
		$_csb_url_in_person = add_query_arg( 'csb_type', 'in_person', $_csb_type_base );
		$_csb_url_online    = add_query_arg( 'csb_type', 'online', $_csb_type_base );
		?>
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
						<a href="<?php echo esc_url( '' !== $active_type ? add_query_arg( 'csb_type', $active_type, $archive_base_url ) : $archive_base_url ); ?>"
							<?php echo '' === $active_cat ? ' aria-current="page"' : ''; ?>>
							<?php esc_html_e( 'همه', 'csb' ); ?>
						</a>
					</li>
					<?php foreach ( $archive_terms as $term ) :
						$_csb_cat_url = add_query_arg( 'csb_cat', $term->slug, $archive_base_url );
						if ( '' !== $active_type ) {
							$_csb_cat_url = add_query_arg( 'csb_type', $active_type, $_csb_cat_url );
						}
					?>
					<li>
						<a href="<?php echo esc_url( $_csb_cat_url ); ?>"
							<?php echo $active_cat === $term->slug ? ' aria-current="page"' : ''; ?>>
							<?php echo esc_html( $term->name ); ?>
							<span class="csb-archive__cat-count">(<?php echo esc_html( (string) $term->count ); ?>)</span>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<div class="csb-archive__sidebar-types">
				<h3 class="csb-archive__sidebar-heading"><?php esc_html_e( 'نوع برگزاری', 'csb' ); ?></h3>
				<ul class="csb-archive__type-list">
					<li>
						<a href="<?php echo esc_url( $_csb_url_type_all ); ?>"
							<?php echo '' === $active_type ? ' aria-current="page"' : ''; ?>>
							<?php esc_html_e( 'همه', 'csb' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( $_csb_url_in_person ); ?>"
							<?php echo 'in_person' === $active_type ? ' aria-current="page"' : ''; ?>>
							<?php esc_html_e( 'حضوری', 'csb' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( $_csb_url_online ); ?>"
							<?php echo 'online' === $active_type ? ' aria-current="page"' : ''; ?>>
							<?php esc_html_e( 'آنلاین', 'csb' ); ?>
						</a>
					</li>
				</ul>
			</div>

		</aside>
		<?php endif; ?>

		<div class="csb-archive__main">

			<?php if ( $show_filters ) : ?>
			<nav class="csb-archive__filters" aria-label="<?php esc_attr_e( 'فیلترها', 'csb' ); ?>">

				<div class="csb-archive__filter-group csb-archive__filter-cats">
					<a class="csb-filter-chip <?php echo '' === $active_cat ? 'csb-filter-chip--active' : ''; ?>"
						href="<?php echo esc_url( '' !== $active_type ? add_query_arg( 'csb_type', $active_type, $archive_base_url ) : $archive_base_url ); ?>"
						<?php echo '' === $active_cat ? 'aria-current="page"' : ''; ?>>
						<?php esc_html_e( 'همه', 'csb' ); ?>
					</a>
					<?php foreach ( $archive_terms as $term ) :
						$_csb_chip_url = add_query_arg( 'csb_cat', $term->slug, $archive_base_url );
						if ( '' !== $active_type ) {
							$_csb_chip_url = add_query_arg( 'csb_type', $active_type, $_csb_chip_url );
						}
					?>
					<a class="csb-filter-chip <?php echo $active_cat === $term->slug ? 'csb-filter-chip--active' : ''; ?>"
						href="<?php echo esc_url( $_csb_chip_url ); ?>"
						<?php echo $active_cat === $term->slug ? 'aria-current="page"' : ''; ?>>
						<?php echo esc_html( $term->name ); ?>
					</a>
					<?php endforeach; ?>
				</div>

				<div class="csb-archive__filter-group csb-archive__filter-types">
					<a class="csb-filter-chip <?php echo '' === $active_type ? 'csb-filter-chip--active' : ''; ?>"
						href="<?php echo esc_url( $_csb_url_type_all ); ?>"
						<?php echo '' === $active_type ? 'aria-current="page"' : ''; ?>>
						<?php esc_html_e( 'همه', 'csb' ); ?>
					</a>
					<a class="csb-filter-chip <?php echo 'in_person' === $active_type ? 'csb-filter-chip--active' : ''; ?>"
						href="<?php echo esc_url( $_csb_url_in_person ); ?>"
						<?php echo 'in_person' === $active_type ? 'aria-current="page"' : ''; ?>>
						<?php esc_html_e( 'حضوری', 'csb' ); ?>
					</a>
					<a class="csb-filter-chip <?php echo 'online' === $active_type ? 'csb-filter-chip--active' : ''; ?>"
						href="<?php echo esc_url( $_csb_url_online ); ?>"
						<?php echo 'online' === $active_type ? 'aria-current="page"' : ''; ?>>
						<?php esc_html_e( 'آنلاین', 'csb' ); ?>
					</a>
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
			<p class="csb-no-results"><?php esc_html_e( 'کلاسی یافت نشد.', 'csb' ); ?></p>
			<?php endif; ?>

		</div><!-- .csb-archive__main -->

	</div><!-- .csb-archive__layout -->

</main><!-- .csb-archive -->

<?php get_footer( 'shop' ); ?>
