<?php
/**
 * بطاقة الدورة الموحدة للصفحة الرئيسية ومتجر الدورات.
 *
 * @package Alzaherah
 * @since   3.10.8
 */

defined( 'ABSPATH' ) || exit;

$alz_card_product = isset( $args['product'] ) && $args['product'] instanceof WC_Product
	? $args['product']
	: wc_get_product( get_the_ID() );

if ( ! $alz_card_product || ! $alz_card_product->is_visible() ) {
	return;
}

$alz_card_id = $alz_card_product->get_id();
if ( function_exists( 'alzaherah_is_confirmed_course_product' ) && ! alzaherah_is_confirmed_course_product( $alz_card_id ) ) {
	return;
}
if ( function_exists( 'alz_core_is_course_product' ) && ! alz_core_is_course_product( $alz_card_id ) ) {
	return;
}

$alz_card_index    = isset( $args['index'] ) ? absint( $args['index'] ) : 1;
$alz_card_title    = get_the_title( $alz_card_id );
$alz_card_url      = get_permalink( $alz_card_id );
$alz_card_terms    = get_the_terms( $alz_card_id, 'product_cat' );
$alz_card_slugs    = array();
$alz_card_category = __( 'دورة تدريبية', 'alzaherah' );
$alz_category_url  = '';

if ( $alz_card_terms && ! is_wp_error( $alz_card_terms ) ) {
	$alz_card_slugs    = wp_list_pluck( $alz_card_terms, 'slug' );
	$alz_card_category = $alz_card_terms[0]->name;
	$alz_category_url  = get_term_link( $alz_card_terms[0] );
}

$alz_card_excerpt = get_the_excerpt( $alz_card_id );
if ( ! $alz_card_excerpt ) {
	$alz_card_excerpt = wp_strip_all_tags( get_post_field( 'post_content', $alz_card_id ) );
}

$alz_trainer_id   = absint( get_post_meta( $alz_card_id, '_alz_trainer_id', true ) );
$alz_trainer_name = $alz_trainer_id ? get_the_title( $alz_trainer_id ) : get_post_meta( $alz_card_id, '_alz_trainer', true );
$alz_card_search  = wp_strip_all_tags( $alz_card_title . ' ' . $alz_trainer_name . ' ' . $alz_card_category . ' ' . $alz_card_excerpt );
$alz_card_image  = has_post_thumbnail( $alz_card_id )
	? get_the_post_thumbnail(
		$alz_card_id,
		'medium_large',
		array(
			'loading'  => 'lazy',
			'decoding' => 'async',
			'alt'      => sprintf( __( 'صورة دورة %s', 'alzaherah' ), $alz_card_title ),
		)
	)
	: '<img loading="lazy" decoding="async" src="' . esc_url( get_template_directory_uri() . '/assets/course-' . ( 1 === $alz_card_index % 3 ? 'trainer' : ( 2 === $alz_card_index % 3 ? 'excel' : 'cv' ) ) . '.svg' ) . '" alt="' . esc_attr( sprintf( __( 'صورة دورة %s', 'alzaherah' ), $alz_card_title ) ) . '">';

$alz_card_status  = function_exists( 'alzaherah_course_effective_status' )
	? alzaherah_course_effective_status( $alz_card_product )
	: ( $alz_card_product->is_in_stock() ? 'available' : 'full' );
$alz_is_simple    = $alz_card_product->is_type( 'simple' );
$alz_can_register = 'available' === $alz_card_status && $alz_card_product->is_purchasable() && $alz_card_product->is_in_stock();
$alz_register_url = $alz_is_simple && $alz_can_register
	? ( function_exists( 'alz_course_registration_start_url' )
		? alz_course_registration_start_url( $alz_card_id )
		: add_query_arg( 'add-to-cart', $alz_card_id, wc_get_checkout_url() ) )
	: $alz_card_url;
$alz_seats_left   = function_exists( 'alzaherah_seats_left' ) ? alzaherah_seats_left( $alz_card_product ) : null;
$alz_raw_date     = (string) get_post_meta( $alz_card_id, '_alz_course_date', true );
$alz_date_display = function_exists( 'alzaherah_format_course_date' ) ? alzaherah_format_course_date( $alz_raw_date ) : '';
?>
<article class="course-card catalog-course-card is-status-<?php echo esc_attr( $alz_card_status ); ?>"
	data-status="<?php echo esc_attr( $alz_card_status ); ?>"
	data-category="<?php echo esc_attr( implode( ' ', $alz_card_slugs ) ); ?>"
	data-search="<?php echo esc_attr( $alz_card_search ); ?>">
	<a class="course-cover" href="<?php echo esc_url( $alz_card_url ); ?>">
		<?php echo wp_kses_post( $alz_card_image ); ?>
		<div class="course-card-badges">
			<?php if ( $alz_date_display ) : ?>
				<span class="course-date-chip">📅 <?php echo esc_html( sprintf( __( 'تبدأ %s', 'alzaherah' ), $alz_date_display ) ); ?></span>
			<?php endif; ?>
			<?php if ( function_exists( 'alzaherah_course_status_badge' ) ) : ?>
				<?php alzaherah_course_status_badge( $alz_card_product ); ?>
			<?php else : ?>
				<span class="course-badge <?php echo $alz_card_product->is_in_stock() ? '' : 'is-closed'; ?>">
					<?php echo esc_html( $alz_card_product->is_in_stock() ? __( 'متاح للتسجيل', 'alzaherah' ) : __( 'اكتمل العدد', 'alzaherah' ) ); ?>
				</span>
			<?php endif; ?>
		</div>
		<?php if ( 'available' === $alz_card_status && null !== $alz_seats_left && $alz_seats_left > 0 && $alz_seats_left <= 10 ) : ?>
			<span class="seats-chip">🪑 <?php printf( esc_html__( 'متبقي %s مقاعد', 'alzaherah' ), esc_html( number_format_i18n( $alz_seats_left ) ) ); ?></span>
		<?php endif; ?>
	</a>

	<div class="course-body">
		<div class="course-meta">
			<?php if ( $alz_category_url && ! is_wp_error( $alz_category_url ) ) : ?>
				<a href="<?php echo esc_url( $alz_category_url ); ?>">◉ <?php echo esc_html( $alz_card_category ); ?></a>
			<?php else : ?>
				<span>◉ <?php echo esc_html( $alz_card_category ); ?></span>
			<?php endif; ?>
			<span>✓ <?php esc_html_e( 'تأكيد إلكتروني', 'alzaherah' ); ?></span>
		</div>

		<h2><a href="<?php echo esc_url( $alz_card_url ); ?>"><?php echo esc_html( $alz_card_title ); ?></a></h2>
		<p><?php echo esc_html( wp_trim_words( $alz_card_excerpt, 14 ) ); ?></p>

		<div class="course-footer">
			<div class="price"><?php echo wp_kses_post( $alz_card_product->get_price_html() ); ?></div>
			<a class="course-register<?php echo $alz_can_register ? '' : ' is-closed'; ?><?php echo 'ended' === $alz_card_status ? ' is-ended' : ''; ?>" href="<?php echo esc_url( $alz_register_url ); ?>">
				<?php echo esc_html( $alz_can_register && $alz_is_simple ? __( 'سجّل الآن', 'alzaherah' ) : ( 'ended' === $alz_card_status ? __( 'انتهى التسجيل', 'alzaherah' ) : __( 'عرض التفاصيل', 'alzaherah' ) ) ); ?>
				<?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
		</div>
	</div>
</article>
