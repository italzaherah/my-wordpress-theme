<?php
/**
 * واجهات WooCommerce المخصصة لمركز الزاهرة.
 *
 * يعرض صفحة الدورات بتصميم القالب، ويترك صفحة تفاصيل الدورة
 * وواجهات WooCommerce الأخرى للنظام الأساسي داخل غلاف الموقع.
 *
 * @package Alzaherah
 * @since   2.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Run a standard single-product hook without rendering WooCommerce's default
 * presentation a second time.
 *
 * The theme owns the visible title, gallery, price, add-to-cart and content
 * layout. Third-party callbacks still need the canonical WooCommerce hooks,
 * and WC_Structured_Data relies on woocommerce_single_product_summary to build
 * Product JSON-LD. Temporarily removing only WooCommerce's stock renderers
 * keeps those integrations working without duplicating the custom UI.
 *
 * @param string $hook Supported single-product hook.
 */
function alzaherah_render_single_product_hook( $hook ) {
	$default_callbacks = array(
		'woocommerce_before_single_product_summary' => array(
			'woocommerce_show_product_sale_flash',
			'woocommerce_show_product_images',
		),
		'woocommerce_single_product_summary' => array(
			'woocommerce_template_single_title',
			'woocommerce_template_single_rating',
			'woocommerce_template_single_price',
			'woocommerce_template_single_excerpt',
			'woocommerce_template_single_add_to_cart',
			'woocommerce_template_single_meta',
			'woocommerce_template_single_sharing',
		),
		'woocommerce_after_single_product_summary' => array(
			'woocommerce_output_product_data_tabs',
			'woocommerce_upsell_display',
			'woocommerce_output_related_products',
		),
	);

	if ( ! isset( $default_callbacks[ $hook ] ) ) {
		return;
	}

	$removed = array();
	foreach ( $default_callbacks[ $hook ] as $callback ) {
		$priority = has_action( $hook, $callback );
		if ( false === $priority ) {
			continue;
		}
		remove_action( $hook, $callback, $priority );
		$removed[] = array( $callback, $priority );
	}

	ob_start();
	do_action( $hook );
	$extension_markup = ob_get_clean();

	foreach ( $removed as $callback ) {
		add_action( $hook, $callback[0], $callback[1] );
	}

	if ( '' !== trim( $extension_markup ) ) {
		echo '<div class="alz-woocommerce-extension-slot alz-woocommerce-extension-slot--' . esc_attr( sanitize_html_class( $hook ) ) . '">';
		echo $extension_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output comes from registered WooCommerce/plugin callbacks.
		echo '</div>';
	}
}

get_header();

$alz_post_type         = get_query_var( 'post_type' );
$alz_is_course_search  = is_search() && ( 'product' === $alz_post_type || ( is_array( $alz_post_type ) && in_array( 'product', $alz_post_type, true ) ) );

if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() || $alz_is_course_search ) ) :
	if ( $alz_is_course_search ) {
		$alz_current_title = sprintf( __( 'نتائج البحث عن: %s', 'alzaherah' ), get_search_query() );
	} else {
		$alz_current_title = is_product_taxonomy() ? single_term_title( '', false ) : __( 'الدورات التدريبية', 'alzaherah' );
	}
	$alz_categories = function_exists( 'alzaherah_course_category_terms' )
		? alzaherah_course_category_terms(
			array(
				'number' => 10,
			)
		)
		: get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => 10,
			)
		);
	?>
	<main id="main" class="courses-page" role="main">

		<section class="courses-hero">
			<div class="container courses-hero-inner">
				<div>
					<span class="courses-kicker"><?php esc_html_e( 'برامج حضورية وعن بُعد', 'alzaherah' ); ?></span>
					<h1><?php echo esc_html( $alz_current_title ); ?></h1>
					<p><?php esc_html_e( 'اختر البرنامج المناسب، اطّلع على تفاصيله، ثم أكمل التسجيل والدفع إلكترونيًا خلال دقائق.', 'alzaherah' ); ?></p>
				</div>
				<div class="courses-hero-badges" aria-label="<?php esc_attr_e( 'مزايا التسجيل', 'alzaherah' ); ?>">
					<span>✓ <?php esc_html_e( 'تسجيل سريع', 'alzaherah' ); ?></span>
					<span>✓ <?php esc_html_e( 'دفع آمن', 'alzaherah' ); ?></span>
					<span>✓ <?php esc_html_e( 'تأكيد الطلب عبر البريد الإلكتروني', 'alzaherah' ); ?></span>
				</div>
			</div>
		</section>

		<?php if ( function_exists( 'alzaherah_promo_strip' ) ) { alzaherah_promo_strip(); } ?>

		<section class="courses-content section-soft">
			<div class="container">

				<div class="courses-tools">
					<form class="courses-search" role="search" method="get" action="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
						<span aria-hidden="true">⌕</span>
						<label class="screen-reader-text" for="course-catalog-search"><?php esc_html_e( 'ابحث في جميع الدورات', 'alzaherah' ); ?></label>
						<input id="course-catalog-search" name="s" type="search" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'ابحث في جميع الدورات…', 'alzaherah' ); ?>" autocomplete="search">
						<input type="hidden" name="post_type" value="product">
						<button type="submit"><?php esc_html_e( 'بحث', 'alzaherah' ); ?></button>
					</form>

					<?php if ( function_exists( 'woocommerce_catalog_ordering' ) ) : ?>
						<div class="courses-ordering">
							<?php woocommerce_catalog_ordering(); ?>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( ! is_wp_error( $alz_categories ) && $alz_categories ) : ?>
					<div class="filter-tabs" aria-label="<?php esc_attr_e( 'تصفية الدورات حسب المجال', 'alzaherah' ); ?>">
						<a class="filter-tab <?php echo is_product_taxonomy() ? '' : 'active'; ?>" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" <?php echo is_product_taxonomy() ? '' : 'aria-current="page"'; ?>><?php esc_html_e( 'جميع الدورات', 'alzaherah' ); ?></a>
						<?php foreach ( $alz_categories as $alz_category ) : ?>
							<?php $alz_category_active = is_product_category( $alz_category->slug ); ?>
							<a class="filter-tab <?php echo $alz_category_active ? 'active' : ''; ?>" href="<?php echo esc_url( get_term_link( $alz_category ) ); ?>" <?php echo $alz_category_active ? 'aria-current="page"' : ''; ?>>
								<?php echo esc_html( $alz_category->name ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="courses-result-row">
					<strong><?php esc_html_e( 'الدورات المتاحة للتسجيل', 'alzaherah' ); ?></strong>
					<?php if ( function_exists( 'woocommerce_result_count' ) ) : ?>
						<div class="courses-result-count"><?php woocommerce_result_count(); ?></div>
					<?php endif; ?>
				</div>

				<?php if ( woocommerce_product_loop() ) : ?>
					<div class="courses-grid courses-catalog-grid" id="courses-grid">
						<?php
						$alz_index = 0;
						while ( have_posts() ) :
							the_post();
							global $product;
							$alz_index++;

							if ( ! $product || ! $product->is_visible() ) {
								continue;
							}
							get_template_part(
								'template-parts/course',
								'card',
								array(
									'product' => $product,
									'index'   => $alz_index,
								)
							);
							?>
						<?php endwhile; ?>
					</div>

					<div class="courses-no-results" id="courses-no-results" hidden>
						<span aria-hidden="true">⌕</span>
						<h2><?php esc_html_e( 'لا توجد دورة مطابقة', 'alzaherah' ); ?></h2>
						<p><?php esc_html_e( 'جرّب كلمة بحث مختلفة أو اختر «جميع الدورات».', 'alzaherah' ); ?></p>
					</div>

					<div class="courses-pagination">
						<?php woocommerce_pagination(); ?>
					</div>
				<?php else : ?>
					<div class="courses-empty">
						<span aria-hidden="true">🗓️</span>
						<h2><?php esc_html_e( 'سيتم الإعلان عن الدورات قريبًا', 'alzaherah' ); ?></h2>
						<p><?php esc_html_e( 'لا توجد دورات منشورة حاليًا. تابع الموقع أو تواصل معنا لمعرفة البرامج القادمة.', 'alzaherah' ); ?></p>
						<a class="btn btn-primary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'تواصل معنا', 'alzaherah' ); ?></a>
					</div>
				<?php endif; ?>

			</div>
		</section>
	</main>
	<?php
elseif ( function_exists( 'is_product' ) && is_product() && function_exists( 'alz_core_is_exam_product' ) && alz_core_is_exam_product( get_queried_object_id() ) ) :

	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/product', 'exam' );
	endwhile;

elseif ( function_exists( 'is_product' ) && is_product() && function_exists( 'alzaherah_is_training_product_id' ) && alzaherah_is_training_product_id( get_queried_object_id() ) ) :

	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/product', 'digital' );
	endwhile;

elseif ( function_exists( 'is_product' ) && is_product() && ( ! function_exists( 'alzaherah_is_confirmed_course_product' ) || alzaherah_is_confirmed_course_product( get_queried_object_id() ) ) ) :

	while ( have_posts() ) :
		the_post();
		$alz_product = wc_get_product( get_the_ID() );
		if ( ! $alz_product ) {
			continue;
		}

		$alz_date     = get_post_meta( get_the_ID(), '_alz_course_date', true );
		$alz_date_display = function_exists( 'alzaherah_format_course_date' ) ? alzaherah_format_course_date( $alz_date ) : ( is_string( $alz_date ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $alz_date ) ? $alz_date : '' );
		$alz_date_label   = $alz_date_display ? $alz_date_display : __( 'موعد البداية غير محدد', 'alzaherah' );
		$alz_time     = get_post_meta( get_the_ID(), '_alz_course_time', true );
		$alz_days     = get_post_meta( get_the_ID(), '_alz_course_days', true );
		$alz_mode_key = get_post_meta( get_the_ID(), '_alz_course_mode', true );
		$alz_modes    = function_exists( 'alzaherah_course_modes' ) ? alzaherah_course_modes() : array();
		$alz_mode     = isset( $alz_modes[ $alz_mode_key ] ) ? $alz_modes[ $alz_mode_key ] : '';
		$alz_trainer_id = absint( get_post_meta( get_the_ID(), '_alz_trainer_id', true ) );
		$alz_trainer  = $alz_trainer_id ? get_the_title( $alz_trainer_id ) : get_post_meta( get_the_ID(), '_alz_trainer', true );
		$alz_location = get_post_meta( get_the_ID(), '_alz_location', true );
		$alz_reqs     = get_post_meta( get_the_ID(), '_alz_requirements', true );
		$alz_accreditation_no = get_post_meta( get_the_ID(), '_alz_accreditation_no', true );
		$alz_accreditation_by = get_post_meta( get_the_ID(), '_alz_accreditation_by', true );
		$alz_course_field     = get_post_meta( get_the_ID(), '_alz_course_field', true );
		$alz_training_hours   = get_post_meta( get_the_ID(), '_alz_training_hours', true );
		$alz_trainer_title    = get_post_meta( get_the_ID(), '_alz_trainer_title', true );
		$alz_certificate_type = get_post_meta( get_the_ID(), '_alz_certificate_type', true );
		$alz_certificate_by   = get_post_meta( get_the_ID(), '_alz_certificate_by', true );
		$alz_status   = function_exists( 'alzaherah_course_effective_status' ) ? alzaherah_course_effective_status( $alz_product ) : 'available';
		$alz_is_self_paced = function_exists( 'alz_core_course_is_self_paced' ) && alz_core_course_is_self_paced( $alz_product );
		$alz_el_linked     = $alz_is_self_paced && function_exists( 'alz_elearning_product_is_linked' ) && alz_elearning_product_is_linked( get_the_ID() );
		$alz_el_enrolled   = $alz_el_linked && is_user_logged_in() && function_exists( 'alz_elearning_user_enrolled' ) && alz_elearning_user_enrolled( get_the_ID() );
		$alz_el_stats      = $alz_el_linked && function_exists( 'alz_elearning_course_card_stats' ) ? alz_elearning_course_card_stats( get_the_ID() ) : array();
		$alz_el_outline    = $alz_el_linked && function_exists( 'alz_elearning_course_outline' ) ? alz_elearning_course_outline( get_the_ID() ) : array();
		$alz_el_progress   = $alz_el_enrolled && function_exists( 'alz_elearning_progress_percent' ) ? alz_elearning_progress_percent( get_the_ID() ) : null;
		$alz_el_learn_url  = $alz_el_enrolled && function_exists( 'alz_elearning_learning_url' ) ? alz_elearning_learning_url( get_the_ID() ) : '';
		$alz_can_register = 'available' === $alz_status && $alz_product->is_purchasable() && $alz_product->is_in_stock();
		$alz_is_simple = $alz_product->is_type( 'simple' );
		$alz_register_url = $alz_can_register && $alz_is_simple
			? ( function_exists( 'alz_course_registration_start_url' )
				? alz_course_registration_start_url( $alz_product->get_id() )
				: add_query_arg( 'add-to-cart', $alz_product->get_id(), wc_get_checkout_url() ) )
			: '#course-registration-options';
		$alz_left     = function_exists( 'alzaherah_seats_left' ) ? alzaherah_seats_left( $alz_product ) : null;
		$alz_terms    = get_the_terms( get_the_ID(), 'product_cat' );
		$alz_cat      = ( $alz_terms && ! is_wp_error( $alz_terms ) ) ? $alz_terms[0]->name : __( 'دورة تدريبية', 'alzaherah' );
		?>
		<main id="main" <?php wc_product_class( 'course-single-page', $alz_product ); ?> role="main">

			<section class="courses-hero course-single-hero">
				<div class="container">
					<span class="courses-kicker"><?php echo esc_html( $alz_cat ); ?></span>
					<h1><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?>
						<p><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
				</div>
			</section>

			<section class="section section-soft">
				<div class="container course-single-layout">

					<div class="course-single-content">

						<?php do_action( 'woocommerce_before_single_product' ); // إشعارات السلة والأخطاء. ?>
						<?php alzaherah_render_single_product_hook( 'woocommerce_before_single_product_summary' ); ?>

						<?php if ( has_post_thumbnail() ) : ?>
							<div class="course-single-image">
								<?php the_post_thumbnail( 'large', array( 'alt' => sprintf( __( 'صورة دورة %s', 'alzaherah' ), get_the_title() ), 'decoding' => 'async' ) ); ?>
							</div>
						<?php endif; ?>

						<section class="course-mobile-quick-enroll" aria-label="<?php esc_attr_e( 'التسجيل في الدورة', 'alzaherah' ); ?>">
							<div>
								<?php if ( function_exists( 'alzaherah_course_status_badge' ) ) { alzaherah_course_status_badge( $alz_product ); } ?>
								<strong class="course-mobile-price"><?php echo wp_kses_post( function_exists( 'alzaherah_course_price_html' ) ? alzaherah_course_price_html( $alz_product ) : $alz_product->get_price_html() ); ?></strong>
								<small><?php echo esc_html( $alz_is_self_paced ? __( 'تعلّم ذاتي — يبدأ فور تأكيد الدفع', 'alzaherah' ) : ( $alz_date_display ? sprintf( __( 'تبدأ في %s', 'alzaherah' ), $alz_date_display ) : $alz_date_label ) ); ?></small>
							</div>
							<?php if ( $alz_can_register ) : ?>
								<a class="btn btn-primary" href="<?php echo esc_url( $alz_register_url ); ?>"><?php esc_html_e( 'سجّل الآن', 'alzaherah' ); ?></a>
							<?php else : ?>
								<span class="course-mobile-closed"><?php echo esc_html( function_exists( 'alzaherah_course_registration_message' ) ? alzaherah_course_registration_message( $alz_product ) : __( 'التسجيل غير متاح حاليًا.', 'alzaherah' ) ); ?></span>
							<?php endif; ?>
						</section>

						<article class="course-single-description">
							<h2><?php esc_html_e( 'عن الدورة', 'alzaherah' ); ?></h2>
							<?php the_content(); ?>
						</article>

						<?php if ( $alz_el_outline ) : ?>
							<article class="course-single-requirements course-elearning-outline">
								<h2><?php esc_html_e( 'محتوى الدورة', 'alzaherah' ); ?></h2>
								<?php if ( $alz_el_enrolled && null !== $alz_el_progress ) : ?>
									<div class="course-elearning-progress" role="status">
										<div class="course-elearning-progress-head">
											<strong><?php esc_html_e( 'نسبة إنجازك', 'alzaherah' ); ?></strong>
											<span><?php echo esc_html( number_format_i18n( round( $alz_el_progress ) ) ); ?>%</span>
										</div>
										<div class="course-elearning-progress-bar"><span style="width:<?php echo esc_attr( round( $alz_el_progress ) ); ?>%"></span></div>
									</div>
								<?php endif; ?>
								<ol class="course-elearning-units">
									<?php foreach ( $alz_el_outline as $alz_el_topic ) : ?>
										<li class="course-elearning-unit">
											<strong><?php echo esc_html( $alz_el_topic['title'] ); ?></strong>
											<?php if ( ! empty( $alz_el_topic['items'] ) ) : ?>
												<ul>
													<?php foreach ( $alz_el_topic['items'] as $alz_el_item ) : ?>
														<li>
															<span aria-hidden="true"><?php echo 'quiz' === $alz_el_item['type'] ? '✎' : '▶'; ?></span>
															<?php echo esc_html( $alz_el_item['title'] ); ?>
															<?php if ( 'quiz' === $alz_el_item['type'] ) : ?>
																<em class="course-elearning-tag"><?php esc_html_e( 'اختبار قصير', 'alzaherah' ); ?></em>
															<?php endif; ?>
														</li>
													<?php endforeach; ?>
												</ul>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ol>
							</article>
						<?php endif; ?>

						<?php if ( $alz_reqs ) : ?>
							<article class="course-single-requirements">
								<h2><?php esc_html_e( 'شروط ومتطلبات الالتحاق', 'alzaherah' ); ?></h2>
								<ul>
									<?php foreach ( array_filter( array_map( 'trim', explode( "\n", $alz_reqs ) ) ) as $alz_req ) : ?>
										<li><span aria-hidden="true">✓</span> <?php echo esc_html( $alz_req ); ?></li>
									<?php endforeach; ?>
								</ul>
							</article>
						<?php endif; ?>

						<article class="course-single-requirements course-regulatory-details">
							<h2><?php esc_html_e( 'بيانات البرنامج والاعتماد', 'alzaherah' ); ?></h2>
							<dl>
								<div><dt><?php esc_html_e( 'المنشأة المقدمة', 'alzaherah' ); ?></dt><dd><?php esc_html_e( 'مركز الزاهرة للتدريب', 'alzaherah' ); ?></dd></div>
								<div><dt><?php esc_html_e( 'رقم اعتماد الدورة', 'alzaherah' ); ?></dt><dd><?php echo $alz_accreditation_no ? esc_html( $alz_accreditation_no ) : esc_html__( 'لم يُذكر اعتماد لهذه الدورة', 'alzaherah' ); ?></dd></div>
								<div><dt><?php esc_html_e( 'جهة الاعتماد', 'alzaherah' ); ?></dt><dd><?php echo $alz_accreditation_by ? esc_html( $alz_accreditation_by ) : esc_html__( 'غير محددة', 'alzaherah' ); ?></dd></div>
								<div><dt><?php esc_html_e( 'مجال الدورة', 'alzaherah' ); ?></dt><dd><?php echo $alz_course_field ? esc_html( $alz_course_field ) : esc_html( $alz_cat ); ?></dd></div>
								<div><dt><?php esc_html_e( 'الساعات التدريبية', 'alzaherah' ); ?></dt><dd><?php echo $alz_training_hours ? esc_html( $alz_training_hours ) : esc_html__( 'تستكمل قبل فتح التسجيل', 'alzaherah' ); ?></dd></div>
								<div><dt><?php esc_html_e( 'المدرب وصفته', 'alzaherah' ); ?></dt><dd><?php echo esc_html( trim( $alz_trainer . ( $alz_trainer_title ? ' — ' . $alz_trainer_title : '' ) ) ?: __( 'تستكمل قبل فتح التسجيل', 'alzaherah' ) ); ?></dd></div>
								<div><dt><?php esc_html_e( 'الشهادة', 'alzaherah' ); ?></dt><dd><?php echo esc_html( trim( $alz_certificate_type . ( $alz_certificate_by ? ' — ' . $alz_certificate_by : '' ) ) ?: __( 'توضح قبل فتح التسجيل', 'alzaherah' ) ); ?></dd></div>
							</dl>
							<p><a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'شروط التسجيل والحضور', 'alzaherah' ); ?></a> · <a href="<?php echo esc_url( home_url( '/refund-policy/' ) ); ?>"><?php esc_html_e( 'سياسة الإلغاء والاسترجاع', 'alzaherah' ); ?></a></p>
						</article>

						<?php alzaherah_render_single_product_hook( 'woocommerce_single_product_summary' ); ?>

					</div>

					<aside id="course-registration-options" class="course-single-sidebar">

						<div class="course-info-card">

							<?php if ( function_exists( 'alzaherah_course_status_badge' ) ) { alzaherah_course_status_badge( $alz_product ); } ?>

							<div class="course-info-price"><?php echo wp_kses_post( function_exists( 'alzaherah_course_price_html' ) ? alzaherah_course_price_html( $alz_product ) : $alz_product->get_price_html() ); ?></div>

							<ul class="course-info-rows">
								<?php if ( $alz_is_self_paced ) : ?>
									<li><span aria-hidden="true">⚡</span><div><strong><?php esc_html_e( 'بداية التعلم', 'alzaherah' ); ?></strong><small><?php esc_html_e( 'فورًا بعد تأكيد الدفع', 'alzaherah' ); ?></small></div></li>
									<?php if ( ! empty( $alz_el_stats['topics'] ) || ! empty( $alz_el_stats['lessons'] ) ) : ?>
										<li><span aria-hidden="true">▤</span><div><strong><?php esc_html_e( 'المحتوى', 'alzaherah' ); ?></strong><small><?php
											$alz_el_parts = array();
											if ( ! empty( $alz_el_stats['topics'] ) ) {
												$alz_el_parts[] = sprintf( __( '%s وحدات', 'alzaherah' ), number_format_i18n( absint( $alz_el_stats['topics'] ) ) );
											}
											if ( ! empty( $alz_el_stats['lessons'] ) ) {
												$alz_el_parts[] = sprintf( __( '%s درسًا', 'alzaherah' ), number_format_i18n( absint( $alz_el_stats['lessons'] ) ) );
											}
											echo esc_html( implode( ' · ', $alz_el_parts ) );
										?></small></div></li>
									<?php endif; ?>
								<?php else : ?>
									<li><span aria-hidden="true">📅</span><div><strong><?php esc_html_e( 'تاريخ البدء', 'alzaherah' ); ?></strong><small><?php echo esc_html( $alz_date_label ); ?></small></div></li>
									<?php if ( $alz_time || $alz_days ) : ?>
										<li><span aria-hidden="true">🕘</span><div><strong><?php esc_html_e( 'الوقت والمدة', 'alzaherah' ); ?></strong><small><?php echo esc_html( trim( $alz_time . ( $alz_days ? ' · ' . sprintf( _n( 'يوم واحد', '%s أيام', (int) $alz_days, 'alzaherah' ), number_format_i18n( (int) $alz_days ) ) : '' ), ' ·' ) ); ?></small></div></li>
									<?php endif; ?>
								<?php endif; ?>
								<?php if ( $alz_mode ) : ?>
									<li><span aria-hidden="true">💻</span><div><strong><?php esc_html_e( 'نمط التقديم', 'alzaherah' ); ?></strong><small><?php echo esc_html( $alz_mode ); ?></small></div></li>
								<?php endif; ?>
								<?php if ( $alz_trainer ) : ?>
									<li><span aria-hidden="true">👤</span><div><strong><?php esc_html_e( 'المدرب', 'alzaherah' ); ?></strong><small><?php echo esc_html( $alz_trainer ); ?></small></div></li>
								<?php endif; ?>
								<?php if ( $alz_location ) : ?>
									<li><span aria-hidden="true">📍</span><div><strong><?php esc_html_e( 'المكان / المنصة', 'alzaherah' ); ?></strong><small><?php echo esc_html( $alz_location ); ?></small></div></li>
								<?php endif; ?>
								<?php if ( null !== $alz_left ) : ?>
									<li><span aria-hidden="true">🪑</span><div><strong><?php esc_html_e( 'المقاعد المتبقية', 'alzaherah' ); ?></strong><small><?php echo esc_html( number_format_i18n( $alz_left ) ); ?></small></div></li>
								<?php endif; ?>
							</ul>

							<?php if ( $alz_el_enrolled && $alz_el_learn_url ) : ?>

								<div class="course-status-notice is-enrolled"><?php esc_html_e( 'أنت مسجل في هذه الدورة ويمكنك متابعة التعلم في أي وقت.', 'alzaherah' ); ?></div>
								<a class="btn btn-primary" style="width:100%;text-align:center" href="<?php echo esc_url( $alz_el_learn_url ); ?>"><?php echo esc_html( null !== $alz_el_progress && $alz_el_progress > 0 ? __( 'متابعة التعلم', 'alzaherah' ) : __( 'ابدأ التعلم الآن', 'alzaherah' ) ); ?></a>

							<?php elseif ( in_array( $alz_status, array( 'postponed', 'cancelled' ), true ) ) : ?>

								<div class="course-status-notice is-<?php echo esc_attr( $alz_status ); ?>">
									<?php echo esc_html( 'postponed' === $alz_status ? __( 'هذه الدورة مؤجلة حاليًا وسيُعلن عن موعدها الجديد. تواصل معنا لأولوية الحجز.', 'alzaherah' ) : __( 'أُلغيت هذه الدورة. تصفّح بقية الدورات المتاحة أو تواصل معنا.', 'alzaherah' ) ); ?>
								</div>
								<a class="btn btn-secondary" style="width:100%;text-align:center" href="<?php echo esc_url( function_exists( 'alzaherah_shop_url' ) ? alzaherah_shop_url() : home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'تصفّح دورات أخرى', 'alzaherah' ); ?></a>

							<?php elseif ( 'ended' === $alz_status ) : ?>

								<div class="course-status-notice is-ended"><?php esc_html_e( 'انتهى التسجيل في هذه الدورة بعد انتهاء يوم بدايتها. تبقى تفاصيل الدورة متاحة ويمكنك تصفّح المواعيد القادمة.', 'alzaherah' ); ?></div>
								<a class="btn btn-secondary" style="width:100%;text-align:center" href="<?php echo esc_url( function_exists( 'alzaherah_shop_url' ) ? alzaherah_shop_url() : home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'تصفّح الدورات المتاحة', 'alzaherah' ); ?></a>

							<?php elseif ( 'full' === $alz_status ) : ?>

								<div class="course-status-notice is-full"><?php esc_html_e( 'اكتملت مقاعد هذه الدورة. تواصل معنا للانضمام لقائمة الانتظار أو لمعرفة الموعد القادم.', 'alzaherah' ); ?></div>
								<a class="btn btn-secondary" style="width:100%;text-align:center" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'تواصل معنا', 'alzaherah' ); ?></a>

							<?php elseif ( 'undated' === $alz_status ) : ?>

								<div class="course-status-notice is-undated"><?php esc_html_e( 'موعد بداية هذه الدورة غير محدد، لذلك لا يمكن التسجيل والدفع حتى يحدد المركز تاريخًا صالحًا.', 'alzaherah' ); ?></div>
								<a class="btn btn-secondary" style="width:100%;text-align:center" href="<?php echo esc_url( function_exists( 'alzaherah_shop_url' ) ? alzaherah_shop_url() : home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'تصفّح دورات أخرى', 'alzaherah' ); ?></a>

							<?php else : ?>

								<div class="course-info-cta">
									<?php woocommerce_template_single_add_to_cart(); ?>
								</div>

							<?php endif; ?>

						</div>

						<div class="registration-trust-card">
							<h3><?php esc_html_e( 'تسجيلك محمي', 'alzaherah' ); ?></h3>
							<ul>
								<li><span>✓</span> <?php esc_html_e( 'تظهر وسيلة الدفع المفعّلة وتفاصيلها قبل تأكيد الطلب', 'alzaherah' ); ?></li>
								<li><span>✓</span> <?php esc_html_e( 'تأكيد المقعد عبر البريد فور إتمام الدفع', 'alzaherah' ); ?></li>
								<li><span>✓</span> <?php esc_html_e( 'إلغاء واسترجاع وفق سياسة معلنة وواضحة', 'alzaherah' ); ?></li>
							</ul>
						</div>

					</aside>

				</div>
			</section>

			<div class="container">
				<?php alzaherah_render_single_product_hook( 'woocommerce_after_single_product_summary' ); ?>
			</div>

			<div class="course-mobile-sticky" aria-label="<?php esc_attr_e( 'إجراء التسجيل السريع', 'alzaherah' ); ?>">
				<div>
					<strong><?php echo wp_kses_post( function_exists( 'alzaherah_course_price_html' ) ? alzaherah_course_price_html( $alz_product ) : $alz_product->get_price_html() ); ?></strong>
					<small><?php echo esc_html( function_exists( 'alzaherah_course_status_label' ) ? alzaherah_course_status_label( $alz_status ) : __( 'حالة التسجيل', 'alzaherah' ) ); ?></small>
				</div>
				<?php if ( $alz_el_enrolled && $alz_el_learn_url ) : ?>
					<a href="<?php echo esc_url( $alz_el_learn_url ); ?>"><?php esc_html_e( 'متابعة التعلم', 'alzaherah' ); ?></a>
				<?php elseif ( $alz_can_register ) : ?>
					<a href="<?php echo esc_url( $alz_register_url ); ?>"><?php esc_html_e( 'سجّل الآن', 'alzaherah' ); ?></a>
				<?php else : ?>
					<a class="is-disabled" href="<?php echo esc_url( function_exists( 'alzaherah_shop_url' ) ? alzaherah_shop_url() : home_url( '/shop/' ) ); ?>"><?php echo esc_html( 'ended' === $alz_status ? __( 'انتهى التسجيل', 'alzaherah' ) : __( 'دورات أخرى', 'alzaherah' ) ); ?></a>
				<?php endif; ?>
			</div>

		</main>
		<?php do_action( 'woocommerce_after_single_product' ); ?>
		<?php
	endwhile;

else :
	?>
	<main id="main" class="woocommerce-main section" role="main">
		<div class="container">
			<?php woocommerce_content(); ?>
		</div>
	</main>
	<?php
endif;

get_footer();
