<?php
/**
 * Shared product-card partial for class & video_course grids.
 *
 * Expects two variables in the including scope:
 *   $product — WC_Product (class or video_course)
 *   $s       — AppearancePage settings array (falls back to AppearancePage::get())
 *
 * Used by archive-class.php, archive-video-course.php and the [bookaclass_*] shortcodes,
 * so the card markup lives in exactly one place.
 *
 * @package BookaClass
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $product ) || ! $product instanceof \WC_Product ) {
	return;
}
if ( ! isset( $s ) || ! is_array( $s ) ) {
	$s = \BookaClass\Admin\AppearancePage::get();
}

$post_id   = $product->get_id();
$permalink = get_permalink( $post_id );
$title     = get_the_title( $post_id );
$is_video  = 'video_course' === $product->get_type();

$card_cta_label = sanitize_text_field( $s['card_cta_label'] ?: __( 'مشاهده و ثبت‌نام', 'csb' ) );

// Image.
$image_id  = (int) get_post_thumbnail_id( $post_id );
$has_image = $image_id > 0;

// Category.
$cat_name = \BookaClass\Frontend\CardData::category_name( $post_id );
$emoji    = \BookaClass\Frontend\CardData::emoji_for_category( $cat_name );

// Instructor / level.
$instructor = \BookaClass\Frontend\CardData::instructor_name( $post_id );
$level      = get_post_meta( $post_id, '_csb_level', true );
$level      = is_string( $level ) ? $level : '';

// Type badge — class: in-person/online ; video: always "ویدیویی".
$class_type       = get_post_meta( $post_id, '_csb_class_type', true );
$class_type       = is_string( $class_type ) ? $class_type : 'in_person';
$type_badge_slug  = $is_video ? 'video' : ( 'online' === $class_type ? 'online' : 'in_person' );
$type_badge_label = $is_video
	? __( 'ویدیویی', 'csb' )
	: ( 'online' === $class_type ? __( 'آنلاین زنده', 'csb' ) : __( 'حضوری', 'csb' ) );

// Class: next session + live seat count.
$session_date = '';
$seats_avail  = -1;
if ( ! $is_video ) {
	$next_session = \BookaClass\Frontend\CardData::next_session( $post_id );
	if ( $next_session ) {
		$session_date = \BookaClass\Support\Jalali::toJalali( $next_session['start_at'], 'Y/m/d' );
		$seats_avail  = \BookaClass\Frontend\CardData::available_seats( $next_session );
	}
}

// Video: lesson count.
$lessons_count = 0;
if ( $is_video && method_exists( $product, 'get_csb_lessons' ) ) {
	$lessons_count = count( $product->get_csb_lessons() );
}

// Rating / excerpt.
$rating_count = (int) $product->get_rating_count();
$avg_rating   = (float) $product->get_average_rating();
$excerpt      = $product->get_short_description() ?: '';

// Price / early-bird.
$regular_price    = (float) $product->get_regular_price();
$earlybird_price  = (float) get_post_meta( $post_id, '_csb_earlybird_price', true );
$earlybird_end    = (string) get_post_meta( $post_id, '_csb_earlybird_end', true );
$earlybird_active = $earlybird_price > 0 && $earlybird_price < $regular_price
	&& ( '' === $earlybird_end || strtotime( $earlybird_end ) > time() );

$schema_type = $is_video ? 'https://schema.org/VideoObject' : 'https://schema.org/Course';
$type_class  = $is_video ? 'csb-card--video' : 'csb-card--class';
?>
<article class="csb-card <?php echo esc_attr( $type_class ); ?>" itemscope itemtype="<?php echo esc_attr( $schema_type ); ?>">

	<?php if ( ! empty( $s['card_show_image'] ) ) : ?>
	<a class="csb-card__media" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( $has_image ) : ?>
			<?php echo wp_get_attachment_image( $image_id, 'medium', false, [
				'loading' => 'lazy',
				'alt'     => esc_attr( $title ),
				'class'   => 'csb-card__img',
			] ); ?>
		<?php else : ?>
			<span class="csb-card__placeholder" data-cat="<?php echo esc_attr( $cat_name ); ?>">
				<?php echo esc_html( $emoji ); ?>
			</span>
		<?php endif; ?>
		<?php if ( ! empty( $s['card_show_type_badge'] ) ) : ?>
			<span class="csb-card__badge csb-card__badge--<?php echo esc_attr( $type_badge_slug ); ?>">
				<?php echo esc_html( $type_badge_label ); ?>
			</span>
		<?php endif; ?>
	</a>
	<?php endif; ?>

	<div class="csb-card__body">

		<?php if ( ! empty( $s['card_show_category'] ) && '' !== $cat_name ) : ?>
		<span class="csb-card__cat"><?php echo esc_html( $cat_name ); ?></span>
		<?php endif; ?>

		<h3 class="csb-card__title" itemprop="name">
			<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
		</h3>

		<?php if ( ! empty( $s['card_show_instructor'] ) && '' !== $instructor ) : ?>
		<div class="csb-card__instructor"><?php echo esc_html( $instructor ); ?></div>
		<?php endif; ?>

		<ul class="csb-card__meta">
			<?php if ( ! $is_video && ! empty( $s['card_show_next_session'] ) && '' !== $session_date ) : ?>
			<li class="csb-card__date"><time><?php echo esc_html( $session_date ); ?></time></li>
			<?php endif; ?>

			<?php if ( ! $is_video && ! empty( $s['card_show_seats'] ) && $seats_avail >= 0 ) :
				$seats_low = $seats_avail > 0 && $seats_avail <= 5; ?>
			<li class="csb-card__seats<?php echo $seats_low ? ' csb-card__seats--low' : ''; ?>">
				<?php if ( $seats_low ) : ?>
					<?php printf( esc_html__( 'تنها %s جای خالی!', 'csb' ), esc_html( (string) $seats_avail ) ); ?>
				<?php else : ?>
					<?php echo esc_html( (string) $seats_avail ); ?> <?php esc_html_e( 'صندلی خالی', 'csb' ); ?>
				<?php endif; ?>
			</li>
			<?php endif; ?>

			<?php if ( $is_video && $lessons_count > 0 ) : ?>
			<li class="csb-card__lessons">
				<?php printf( esc_html( _n( '%d درس', '%d درس', $lessons_count, 'csb' ) ), $lessons_count ); ?>
			</li>
			<?php endif; ?>

			<?php if ( ! empty( $s['card_show_level'] ) && '' !== $level ) : ?>
			<li class="csb-card__level"><?php echo esc_html( $level ); ?></li>
			<?php endif; ?>
		</ul>

		<?php if ( ! empty( $s['card_show_rating'] ) && $rating_count > 0 ) : ?>
		<div class="csb-card__rating"
			itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating"
			aria-label="<?php echo esc_attr( sprintf( __( '%1$.1f از ۵ — %2$d نظر', 'csb' ), $avg_rating, $rating_count ) ); ?>">
			<?php echo wc_get_rating_html( $avg_rating, $rating_count ); ?>
			<span class="csb-card__rating-count">(<?php echo esc_html( (string) $rating_count ); ?>)</span>
			<meta itemprop="ratingValue" content="<?php echo esc_attr( number_format( $avg_rating, 1, '.', '' ) ); ?>">
			<meta itemprop="reviewCount" content="<?php echo esc_attr( (string) $rating_count ); ?>">
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $s['card_show_excerpt'] ) && '' !== $excerpt ) : ?>
		<p class="csb-card__excerpt"><?php echo wp_kses_post( wp_trim_words( $excerpt, 18, '...' ) ); ?></p>
		<?php endif; ?>

	</div><!-- .csb-card__body -->

	<div class="csb-card__footer">

		<?php if ( ! empty( $s['card_show_price'] ) ) : ?>
		<div class="csb-card__price" itemprop="offers" itemscope itemtype="https://schema.org/Offer">
			<?php if ( $earlybird_active && ! empty( $s['card_show_earlybird'] ) ) :
				$disc_pct = $regular_price > 0 ? (int) round( ( 1 - $earlybird_price / $regular_price ) * 100 ) : 0; ?>
				<del class="csb-card__price-regular"><?php echo wp_kses_post( wc_price( $regular_price ) ); ?></del>
				<ins class="csb-card__price-earlybird"><?php echo wp_kses_post( wc_price( $earlybird_price ) ); ?></ins>
				<?php if ( $disc_pct > 0 ) : ?>
					<span class="csb-card__discount"><?php printf( esc_html__( '٪%s تخفیف', 'csb' ), esc_html( (string) $disc_pct ) ); ?></span>
				<?php endif; ?>
			<?php else : ?>
				<?php echo wp_kses_post( $product->get_price_html() ); ?>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<a class="csb-btn csb-btn--primary csb-card__cta" href="<?php echo esc_url( $permalink ); ?>">
			<?php echo esc_html( $card_cta_label ); ?>
		</a>

	</div><!-- .csb-card__footer -->

</article><!-- .csb-card -->
