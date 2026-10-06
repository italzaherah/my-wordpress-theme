<?php
/**
 * صفحة الاختبار الإلكتروني المستقل — هوية v4 بنفس بنية صفحة الدورة.
 *
 * تعرض: الاسم، الوصف، الهدف، السعر، عدد الأسئلة (إن فُعّل)، المدة،
 * المحاولات، طريقة النتيجة، التعليمات، وزر شراء الاختبار.
 *
 * @package Alzaherah
 * @since   4.10.0
 */

defined( 'ABSPATH' ) || exit;

$alz_product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
if ( ! $alz_product ) {
	return;
}

$alz_exam_id  = $alz_product->get_id();
$alz_settings = class_exists( 'ALZ_Exams' ) ? ALZ_Exams::settings( $alz_exam_id ) : array();
$alz_type     = ! empty( $alz_settings['type_label'] ) ? $alz_settings['type_label'] : __( 'اختبار إلكتروني', 'alzaherah' );
$alz_q_count  = class_exists( 'ALZ_Exam_Questions' ) ? ALZ_Exam_Questions::count_for_exam( $alz_exam_id ) : 0;
$alz_can_buy  = $alz_product->is_purchasable() && $alz_product->is_in_stock();
$alz_is_simple = $alz_product->is_type( 'simple' );
$alz_buy_url  = $alz_can_buy && $alz_is_simple
	? ( function_exists( 'alz_course_registration_start_url' )
		? alz_course_registration_start_url( $alz_exam_id )
		: add_query_arg( 'add-to-cart', $alz_exam_id, wc_get_checkout_url() ) )
	: '#exam-registration-options';
$alz_owned    = is_user_logged_in() && class_exists( 'ALZ_Exam_Engine' ) && ALZ_Exam_Engine::active_entitlement( get_current_user_id(), $alz_exam_id );
$alz_my_exams = class_exists( 'ALZ_Exam_Engine' ) ? ALZ_Exam_Engine::endpoint_url() : ( function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'my-exams' ) : home_url( '/my-account/' ) );

// Promotional product images only. Never discover or expose question media.
$alz_promo_ids = array();
if ( class_exists( 'ALZ_Exam_Promo_Images' ) ) {
	$alz_promo_ids = ALZ_Exam_Promo_Images::image_ids( $alz_exam_id );
} else {
	// Keep the theme compatible while the platform plugin is being updated.
	$alz_promo_candidates = array_unique( array_merge( array( $alz_product->get_image_id() ), $alz_product->get_gallery_image_ids() ) );
	foreach ( $alz_promo_candidates as $alz_promo_id ) {
		$alz_promo_id = absint( $alz_promo_id );
		if ( $alz_promo_id && 'attachment' === get_post_type( $alz_promo_id )
			&& ! in_array( get_post_status( $alz_promo_id ), array( 'trash', 'private' ), true )
			&& in_array( get_post_mime_type( $alz_promo_id ), array( 'image/jpeg', 'image/png', 'image/webp' ), true )
			&& ! get_post_meta( $alz_promo_id, '_alz_assess_private', true ) ) {
			$alz_promo_ids[] = $alz_promo_id;
		}
		if ( count( $alz_promo_ids ) >= 6 ) { break; }
	}
}
?>
<main id="main" <?php wc_product_class( 'course-single-page exam-single-page', $alz_product ); ?> role="main">

	<section class="courses-hero course-single-hero">
		<div class="container">
			<span class="courses-kicker"><?php echo esc_html( $alz_type ); ?></span>
			<h1><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<section class="section section-soft">
		<div class="container course-single-layout">

			<div class="course-single-content">

				<?php do_action( 'woocommerce_before_single_product' ); ?>

				<?php if ( $alz_promo_ids ) : ?>
					<section class="exam-promo-gallery" aria-labelledby="exam-promo-gallery-title">
						<h2 id="exam-promo-gallery-title"><?php esc_html_e( 'صور الاختبار', 'alzaherah' ); ?></h2>
						<p><?php esc_html_e( 'اضغط على الصورة لعرضها بالحجم الكامل في نافذة جديدة.', 'alzaherah' ); ?></p>
						<div class="exam-promo-gallery__items">
							<?php foreach ( $alz_promo_ids as $alz_promo_index => $alz_promo_id ) : ?>
								<?php
								$alz_promo_url = wp_get_attachment_image_url( $alz_promo_id, 'full' );
								if ( ! $alz_promo_url ) { continue; }
								$alz_promo_label = $alz_promo_id === absint( $alz_product->get_image_id() )
									? __( 'صورة الغلاف', 'alzaherah' )
									: sprintf( __( 'صورة توضيحية %s', 'alzaherah' ), number_format_i18n( $alz_promo_index + 1 ) );
								$alz_promo_alt = trim( (string) get_post_meta( $alz_promo_id, '_wp_attachment_image_alt', true ) );
								if ( '' === $alz_promo_alt ) { $alz_promo_alt = $alz_promo_label . ' — ' . get_the_title(); }
								?>
								<figure class="exam-promo-gallery__item">
									<a class="exam-promo-gallery__image" href="<?php echo esc_url( $alz_promo_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( sprintf( __( 'عرض %s بالحجم الكامل (نافذة جديدة)', 'alzaherah' ), $alz_promo_label ) ); ?>">
										<?php echo wp_get_attachment_image( $alz_promo_id, 'large', false, array( 'alt' => $alz_promo_alt, 'loading' => 0 === $alz_promo_index ? 'eager' : 'lazy', 'decoding' => 'async' ) ); ?>
									</a>
									<figcaption><?php echo esc_html( $alz_promo_label ); ?></figcaption>
								</figure>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<article class="course-single-description">
					<h2><?php esc_html_e( 'عن الاختبار', 'alzaherah' ); ?></h2>
					<?php the_content(); ?>
				</article>

				<?php if ( ! empty( $alz_settings['sections'] ) ) : ?>
					<article class="course-single-requirements exam-single-sections">
						<h2><?php esc_html_e( 'أقسام الاختبار', 'alzaherah' ); ?></h2>
						<ul>
							<?php foreach ( $alz_settings['sections'] as $alz_section ) : ?>
								<li><span aria-hidden="true">✓</span> <?php echo esc_html( $alz_section ); ?></li>
							<?php endforeach; ?>
						</ul>
					</article>
				<?php endif; ?>

				<?php if ( ! empty( $alz_settings['instructions'] ) ) : ?>
					<article class="course-single-requirements exam-single-instructions">
						<h2><?php esc_html_e( 'تعليمات الاختبار', 'alzaherah' ); ?></h2>
						<p><?php echo nl2br( esc_html( $alz_settings['instructions'] ) ); ?></p>
					</article>
				<?php endif; ?>

			</div>

			<aside id="exam-registration-options" class="course-single-sidebar">

				<div class="course-info-card">

					<span class="badge kind"><?php echo esc_html( $alz_type ); ?></span>

					<div class="course-info-price"><?php echo wp_kses_post( $alz_product->get_price_html() ); ?></div>

					<ul class="course-info-rows">
						<?php if ( ! empty( $alz_settings['duration'] ) ) : ?>
							<li><span aria-hidden="true">⏱</span><div><strong><?php esc_html_e( 'مدة الاختبار', 'alzaherah' ); ?></strong><small><?php printf( esc_html__( '%s دقيقة', 'alzaherah' ), esc_html( number_format_i18n( $alz_settings['duration'] ) ) ); ?></small></div></li>
						<?php else : ?>
							<li><span aria-hidden="true">⏱</span><div><strong><?php esc_html_e( 'مدة الاختبار', 'alzaherah' ); ?></strong><small><?php esc_html_e( 'بلا مدة محددة', 'alzaherah' ); ?></small></div></li>
						<?php endif; ?>
						<?php if ( ! empty( $alz_settings['show_count'] ) && $alz_q_count > 0 ) : ?>
							<li><span aria-hidden="true">❓</span><div><strong><?php esc_html_e( 'عدد الأسئلة', 'alzaherah' ); ?></strong><small><?php echo esc_html( number_format_i18n( $alz_q_count ) ); ?></small></div></li>
						<?php endif; ?>
						<li><span aria-hidden="true">↻</span><div><strong><?php esc_html_e( 'المحاولات المسموحة', 'alzaherah' ); ?></strong><small><?php echo empty( $alz_settings['attempts'] ) ? esc_html__( 'غير محدودة', 'alzaherah' ) : esc_html( number_format_i18n( $alz_settings['attempts'] ) ); ?></small></div></li>
						<li><span aria-hidden="true">⚡</span><div><strong><?php esc_html_e( 'النتيجة', 'alzaherah' ); ?></strong><small><?php esc_html_e( 'فورية بعد التسليم، مع المستوى إن كان الاختبار يعتمد سلّم مستويات', 'alzaherah' ); ?></small></div></li>
						<li><span aria-hidden="true">🔒</span><div><strong><?php esc_html_e( 'الوصول', 'alzaherah' ); ?></strong><small><?php esc_html_e( 'يُفعَّل فور تأكيد الدفع ويظهر في حسابك ← اختباراتي', 'alzaherah' ); ?></small></div></li>
					</ul>

					<?php if ( $alz_owned ) : ?>
						<div class="course-status-notice is-enrolled"><?php esc_html_e( 'تملك هذا الاختبار ويمكنك بدؤه من حسابك.', 'alzaherah' ); ?></div>
						<a class="btn btn-primary" style="width:100%;text-align:center" href="<?php echo esc_url( $alz_my_exams ); ?>"><?php esc_html_e( 'الذهاب إلى اختباراتي', 'alzaherah' ); ?></a>
					<?php elseif ( $alz_can_buy ) : ?>
						<div class="course-info-cta">
							<a class="btn btn-primary" style="width:100%;text-align:center" href="<?php echo esc_url( $alz_buy_url ); ?>"><?php esc_html_e( 'شراء الاختبار', 'alzaherah' ); ?></a>
						</div>
					<?php else : ?>
						<div class="course-status-notice is-full"><?php esc_html_e( 'هذا الاختبار غير متاح للشراء حاليًا.', 'alzaherah' ); ?></div>
					<?php endif; ?>

				</div>

				<div class="registration-trust-card">
					<h3><?php esc_html_e( 'اختبار موثوق', 'alzaherah' ); ?></h3>
					<ul>
						<li><span>✓</span> <?php esc_html_e( 'دفع آمن عبر وسائل الدفع المعتمدة', 'alzaherah' ); ?></li>
						<li><span>✓</span> <?php esc_html_e( 'إجاباتك تُحفظ تلقائيًا أثناء الاختبار', 'alzaherah' ); ?></li>
						<li><span>✓</span> <?php esc_html_e( 'نتيجتك خاصة بحسابك ولا تظهر لغيرك', 'alzaherah' ); ?></li>
					</ul>
				</div>

			</aside>

		</div>
	</section>

	<div class="course-mobile-sticky" aria-label="<?php esc_attr_e( 'إجراء الشراء السريع', 'alzaherah' ); ?>">
		<div>
			<strong><?php echo wp_kses_post( $alz_product->get_price_html() ); ?></strong>
			<small><?php echo esc_html( $alz_type ); ?></small>
		</div>
		<?php if ( $alz_owned ) : ?>
			<a href="<?php echo esc_url( $alz_my_exams ); ?>"><?php esc_html_e( 'اختباراتي', 'alzaherah' ); ?></a>
		<?php elseif ( $alz_can_buy ) : ?>
			<a href="<?php echo esc_url( $alz_buy_url ); ?>"><?php esc_html_e( 'شراء الاختبار', 'alzaherah' ); ?></a>
		<?php else : ?>
			<a class="is-disabled" href="<?php echo esc_url( function_exists( 'alzaherah_exams_page_url' ) ? alzaherah_exams_page_url() : home_url( '/exams/' ) ); ?>"><?php esc_html_e( 'اختبارات أخرى', 'alzaherah' ); ?></a>
		<?php endif; ?>
	</div>

</main>
<?php do_action( 'woocommerce_after_single_product' ); ?>
