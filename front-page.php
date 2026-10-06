<?php
/**
 * الصفحة الرئيسية — منصة الزاهرة للتدريب.
 *
 * @package Alzaherah
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
get_header();

$shop_url    = function_exists( 'alzaherah_shop_url' ) ? alzaherah_shop_url() : home_url( '/shop/' );
$account_url = function_exists( 'alzaherah_account_url' ) ? alzaherah_account_url() : home_url( '/my-account/' );
$signup_url  = function_exists( 'alzaherah_signup_url' ) ? alzaherah_signup_url() : add_query_arg( 'view', 'register', $account_url );
$exams_url   = function_exists( 'alzaherah_exams_page_url' ) ? alzaherah_exams_page_url() : home_url( '/exams/' );

$hero_badge = get_theme_mod( 'alzaherah_hero_badge', __( 'منصتك نحو الاحتراف', 'alzaherah' ) );
$hero_title = get_theme_mod( 'alzaherah_hero_title', __( 'طوّر مهاراتك مع أفضل الدورات التدريبية', 'alzaherah' ) );
$hero_text  = get_theme_mod( 'alzaherah_hero_text', __( 'سجّل، ادفع بأمان، وابدأ رحلتك التعليمية فورًا عبر تجربة عربية متكاملة وسهلة.', 'alzaherah' ) );

$alz_home_catalog_tax = array(
	array(
		'taxonomy' => 'product_visibility',
		'field'    => 'name',
		'terms'    => array( 'exclude-from-catalog' ),
		'operator' => 'NOT IN',
	),
);

$alz_home_course_args = array(
	'post_status'                 => 'publish',
	'posts_per_page'              => 6,
	'alz_available_courses_first' => 'upcoming',
	'orderby'                     => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	'tax_query'                   => $alz_home_catalog_tax,
	'meta_query'                  => function_exists( 'alzaherah_scheduled_course_mode_meta_query' )
		? alzaherah_scheduled_course_mode_meta_query()
		: array(),
);
$courses = new WP_Query(
	function_exists( 'alzaherah_course_query_args' )
		? alzaherah_course_query_args( $alz_home_course_args )
		: array_merge( array( 'post_type' => 'product' ), $alz_home_course_args )
);

$alz_self_paced_args = array(
	'post_status'    => 'publish',
	'posts_per_page' => 4,
	'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	'no_found_rows'  => true,
	'tax_query'      => $alz_home_catalog_tax,
	'meta_query'     => function_exists( 'alzaherah_self_paced_course_mode_meta_query' )
		? alzaherah_self_paced_course_mode_meta_query()
		: array(),
);
$alz_self_paced = new WP_Query(
	function_exists( 'alzaherah_course_query_args' )
		? alzaherah_course_query_args( $alz_self_paced_args )
		: array_merge( array( 'post_type' => 'product' ), $alz_self_paced_args )
);

$alz_home_shown_course_ids = array_merge(
	wp_list_pluck( $courses->posts, 'ID' ),
	wp_list_pluck( $alz_self_paced->posts, 'ID' )
);
$alz_home_shown_course_ids = array_values( array_filter( array_map( 'absint', $alz_home_shown_course_ids ) ) );

$alz_training_products     = null;
$alz_training_products_url = home_url( '/training-products/' );
if ( class_exists( 'ALZ_Training_Products' ) ) {
	$alz_training_products_url = ALZ_Training_Products::page_url();
	if ( is_callable( array( 'ALZ_Training_Products', 'render_card' ) ) ) {
		$alz_training_products = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 6,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'   => ALZ_Training_Products::KIND_META,
						'value' => ALZ_Training_Products::KIND,
					),
				),
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_visibility',
						'field'    => 'name',
						'terms'    => array( 'exclude-from-catalog' ),
						'operator' => 'NOT IN',
					),
				),
			)
		);
	}
}

$alz_training_products_markup = '';
if ( $alz_training_products instanceof WP_Query && $alz_training_products->have_posts() ) {
	ob_start();
	while ( $alz_training_products->have_posts() ) {
		$alz_training_products->the_post();
		$alz_training_product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
		if ( $alz_training_product ) {
			ALZ_Training_Products::render_card( $alz_training_product, 'h3' );
		}
	}
	$alz_training_products_markup = trim( (string) ob_get_clean() );
	wp_reset_postdata();
}

$categories = function_exists( 'alzaherah_course_category_terms' )
	? alzaherah_course_category_terms(
		array(
			'number'  => 6,
			'orderby' => 'count',
			'order'   => 'DESC',
		)
	)
	: get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => 6,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);

$alz_home_metrics = array(
	'trainees'          => 16450,
	'visitors'          => 4900,
	'completed_courses' => 274,
	'trainers'          => 40,
);

if ( is_callable( array( 'ALZ_Home_Content', 'get_metrics' ) ) ) {
	$alz_saved_metrics = ALZ_Home_Content::get_metrics();
	if ( is_array( $alz_saved_metrics ) ) {
		foreach ( $alz_home_metrics as $alz_metric_key => $alz_metric_default ) {
			if ( isset( $alz_saved_metrics[ $alz_metric_key ] ) ) {
				$alz_home_metrics[ $alz_metric_key ] = min( 999999999, absint( $alz_saved_metrics[ $alz_metric_key ] ) );
			}
		}
	}
}

$alz_home_metric_labels = array(
	'trainees'          => __( 'عدد المتدربين', 'alzaherah' ),
	'visitors'          => __( 'عدد الزوار', 'alzaherah' ),
	'completed_courses' => __( 'عدد الدورات المقامة', 'alzaherah' ),
	'trainers'          => __( 'عدد المدربين', 'alzaherah' ),
);

$alz_testimonials = false;
if ( is_callable( array( 'ALZ_Testimonials', 'approved_query' ) ) ) {
			$alz_testimonial_query = ALZ_Testimonials::approved_query( 12 );
	if ( $alz_testimonial_query instanceof WP_Query ) {
		$alz_testimonials = $alz_testimonial_query;
	}
}

$alz_testimonial_form_markup = '';
if ( is_callable( array( 'ALZ_Testimonials', 'render_public_form' ) ) ) {
	ob_start();
	$alz_testimonial_form_return = ALZ_Testimonials::render_public_form();
	$alz_testimonial_form_output = ob_get_clean();
	if ( is_string( $alz_testimonial_form_output ) && '' !== trim( $alz_testimonial_form_output ) ) {
		$alz_testimonial_form_markup = $alz_testimonial_form_output;
	} elseif ( is_string( $alz_testimonial_form_return ) ) {
		$alz_testimonial_form_markup = $alz_testimonial_form_return;
	}
}

$alz_news = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'category_name'       => 'alzaherah-news',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

$alz_articles = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'category_name'       => 'alzaherah-articles',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

$alz_news_term     = get_category_by_slug( 'alzaherah-news' );
$alz_articles_term = get_category_by_slug( 'alzaherah-articles' );
$alz_news_url      = $alz_news_term ? get_category_link( $alz_news_term->term_id ) : home_url( '/category/alzaherah-news/' );
$alz_articles_url  = $alz_articles_term ? get_category_link( $alz_articles_term->term_id ) : home_url( '/category/alzaherah-articles/' );
$alz_news_url      = is_wp_error( $alz_news_url ) ? home_url( '/category/alzaherah-news/' ) : $alz_news_url;
$alz_articles_url  = is_wp_error( $alz_articles_url ) ? home_url( '/category/alzaherah-articles/' ) : $alz_articles_url;

/**
 * صورة بطاقة المحتوى: مكتبة الوسائط المحلية أو شعار المركز المحلي فقط.
 *
 * @param int $post_id معرف المنشور.
 * @return array{type:string,html:string,url:string}
 */
$alz_home_content_image = static function ( $post_id ) {
	$thumbnail_id = get_post_thumbnail_id( $post_id );
	if ( $thumbnail_id ) {
		return array(
			'type' => 'local',
			'html' => (string) wp_get_attachment_image(
				$thumbnail_id,
				'medium_large',
				false,
				array(
					'alt'      => '',
					'loading'  => 'lazy',
					'decoding' => 'async',
				)
			),
			'url'  => '',
		);
	}

	return array(
		'type' => 'placeholder',
		'html' => '',
		'url'  => function_exists( 'alzaherah_local_placeholder_image_url' ) ? alzaherah_local_placeholder_image_url() : ( get_template_directory_uri() . '/assets/logo-mark.png' ),
	);
};
?>

<main id="main" class="alz-home" role="main">

	<section class="alz3-hero" aria-labelledby="hero-title">
		<div class="alz3-hero-pattern" aria-hidden="true"></div>
		<div class="container alz3-hero-inner">
			<div class="alz3-badge"><span aria-hidden="true">🎓</span><?php alzaherah_copy_e( 'hero_badge', $hero_badge ); ?></div>
			<h1 id="hero-title" data-alz-copy="hero_title" data-alz-copy-highlight="hero"><?php echo wp_kses_post( alzaherah_highlight_hero_title( alzaherah_copy_value( 'hero_title', $hero_title ) ) ); ?></h1>
			<p><?php alzaherah_copy_e( 'hero_text', $hero_text ); ?></p>

			<div class="alz3-hero-actions">
				<a class="btn alz3-primary-cta" data-alz-copy-href="hero_courses_url" href="<?php echo esc_url( alzaherah_copy_value( 'hero_courses_url', $shop_url ) ); ?>">
					<?php alzaherah_copy_e( 'hero_courses_label', 'تصفّح الدورات' ); ?><?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<a class="btn alz3-secondary-cta" data-alz-copy-href="hero_exams_url" href="<?php echo esc_url( alzaherah_copy_value( 'hero_exams_url', $exams_url ) ); ?>">
					<?php alzaherah_copy_e( 'hero_exams_label', 'الاختبارات' ); ?>
				</a>
				<?php if ( is_user_logged_in() ) : ?>
					<a class="btn alz3-secondary-cta" data-alz-copy-href="hero_account_url" href="<?php echo esc_url( alzaherah_copy_value( 'hero_account_url', $account_url ) ); ?>"><?php alzaherah_copy_e( 'hero_account_label', 'فتح لوحة حسابي' ); ?></a>
				<?php else : ?>
					<a class="btn alz3-secondary-cta" data-alz-copy-href="hero_signup_url" href="<?php echo esc_url( alzaherah_copy_value( 'hero_signup_url', $signup_url ) ); ?>"><?php alzaherah_copy_e( 'hero_signup_label', 'أنشئ حسابك مجانًا' ); ?></a>
				<?php endif; ?>
			</div>
			<nav class="alz3-hero-paths" aria-label="<?php esc_attr_e( 'ماذا يقدم المركز', 'alzaherah' ); ?>">
				<a data-alz-copy-href="hero_products_url" href="<?php echo esc_url( alzaherah_copy_value( 'hero_products_url', $alz_training_products_url ) ); ?>"><?php alzaherah_copy_e( 'hero_products_label', 'المنتجات التدريبية' ); ?></a>
				<a data-alz-copy-href="hero_contact_url" href="<?php echo esc_url( alzaherah_copy_value( 'hero_contact_url', home_url( '/contact/' ) ) ); ?>"><?php alzaherah_copy_e( 'hero_contact_label', 'تواصل معنا' ); ?></a>
			</nav>

		</div>
	</section>

	<div class="alz3-trust-wrap alz3-trust-impact" aria-label="<?php esc_attr_e( 'ثقة المركز وأثره', 'alzaherah' ); ?>">
		<div class="container">
			<div class="alz3-trust-grid">
				<article><span aria-hidden="true">🛡️</span><div><strong><?php alzaherah_copy_e( 'trust_0_title', 'دفع عبر قناة محمية' ); ?></strong><small><?php alzaherah_copy_e( 'trust_0_text', 'تظهر الوسائل المفعّلة قبل تأكيد الطلب' ); ?></small></div></article>
				<article><span aria-hidden="true">🏅</span><div><strong><?php alzaherah_copy_e( 'trust_1_title', 'متطلبات واضحة' ); ?></strong><small><?php alzaherah_copy_e( 'trust_1_text', 'نوع الشهادة والاعتماد — إن وُجدا — موضحان في صفحة الدورة' ); ?></small></div></article>
				<article><span aria-hidden="true">⚡</span><div><strong><?php alzaherah_copy_e( 'trust_2_title', 'تأكيد موثّق' ); ?></strong><small><?php alzaherah_copy_e( 'trust_2_text', 'يُرسل التأكيد بعد ثبوت حالة الدفع أو اعتماده' ); ?></small></div></article>
			</div>
			<dl class="alz3-impact-grid alz3-impact-grid--compact">
				<?php foreach ( $alz_home_metric_labels as $alz_metric_key => $alz_metric_label ) : ?>
					<?php $alz_metric_value = $alz_home_metrics[ $alz_metric_key ]; ?>
					<div class="alz3-impact-card">
						<dt><?php echo esc_html( $alz_metric_label ); ?></dt>
						<dd aria-label="<?php echo esc_attr( sprintf( __( 'أكثر من %1$s، %2$s', 'alzaherah' ), number_format_i18n( $alz_metric_value ), $alz_metric_label ) ); ?>">
							<bdi dir="ltr" class="alz3-metric-value" data-counter="<?php echo esc_attr( $alz_metric_value ); ?>" data-prefix="+">+<?php echo esc_html( number_format_i18n( $alz_metric_value ) ); ?></bdi>
						</dd>
					</div>
				<?php endforeach; ?>
			</dl>
		</div>
	</div>

	<?php if ( function_exists( 'alz_render_home_announcements' ) ) : ?>
		<?php alz_render_home_announcements(); ?>
	<?php elseif ( function_exists( 'alzaherah_promo_strip' ) ) : ?>
		<?php alzaherah_promo_strip(); ?>
	<?php endif; ?>

	<section class="alz3-course-section section" aria-labelledby="courses-title">
		<div class="container">
			<div class="section-heading alz3-section-heading">
				<div class="text">
					<span class="eyebrow"><?php alzaherah_copy_e( 'courses_eyebrow', 'برامج بمقاعد وموعد' ); ?></span>
					<h2 class="section-title" id="courses-title"><?php alzaherah_copy_e( 'courses_title', 'الدورات المتاحة للتسجيل' ); ?></h2>
					<p class="section-copy"><?php alzaherah_copy_e( 'courses_text', 'دورات حضورية أو عن بُعد أو مدمجة. اختر المجال والموعد ثم أكمل التسجيل.' ); ?></p>
				</div>
				<a class="alz3-view-all" data-alz-copy-href="courses_url" href="<?php echo esc_url( alzaherah_copy_value( 'courses_url', $shop_url ) ); ?>"><?php alzaherah_copy_e( 'courses_label', 'عرض جميع الدورات' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</div>

			<div class="alz3-filter-shell">
				<form class="alz3-search" role="search" method="get" action="<?php echo esc_url( $shop_url ); ?>"><span aria-hidden="true">⌕</span><label class="screen-reader-text" for="course-search"><?php esc_html_e( 'ابحث في جميع الدورات', 'alzaherah' ); ?></label><input id="course-search" name="s" type="search" data-search-mode="remote" placeholder="<?php esc_attr_e( 'ابحث في جميع الدورات...', 'alzaherah' ); ?>" autocomplete="search"><input type="hidden" name="post_type" value="product"><button type="submit"><?php esc_html_e( 'بحث', 'alzaherah' ); ?></button></form>
				<div class="filter-tabs" aria-label="<?php esc_attr_e( 'تصفية الدورات', 'alzaherah' ); ?>">
					<button class="filter-tab active" type="button" data-category="all" aria-pressed="true"><?php esc_html_e( 'الكل', 'alzaherah' ); ?></button>
					<?php if ( ! is_wp_error( $categories ) ) : ?>
						<?php foreach ( $categories as $category ) : ?>
							<button class="filter-tab" type="button" data-category="<?php echo esc_attr( $category->slug ); ?>" aria-pressed="false"><?php echo esc_html( $category->name ); ?></button>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="courses-grid courses-catalog-grid alz3-home-courses-grid">
				<?php if ( $courses->have_posts() ) : ?>
					<?php while ( $courses->have_posts() ) : $courses->the_post(); ?>
						<?php
						$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
						if ( ! $product ) {
							continue;
						}
						get_template_part(
							'template-parts/course',
							'card',
							array(
								'product' => $product,
								'index'   => $courses->current_post + 1,
							)
						);
						?>
					<?php endwhile; wp_reset_postdata(); ?>
				<?php else : ?>
					<div class="alz3-empty-courses">
						<span aria-hidden="true">📚</span>
						<h3><?php esc_html_e( 'ستظهر الدورات هنا فور إضافتها', 'alzaherah' ); ?></h3>
						<p><?php esc_html_e( 'أضف أول دورة من لوحة التحكم ثم حدّد السعر والموعد وعدد المقاعد.', 'alzaherah' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
			<p id="courses-no-results" class="alz3-no-results" hidden><?php esc_html_e( 'لا توجد دورات مطابقة للبحث الحالي.', 'alzaherah' ); ?></p>
		</div>
	</section>

	<?php if ( $alz_self_paced->have_posts() ) : ?>
		<section class="alz3-course-section alz3-self-paced-section section" aria-labelledby="self-paced-title">
			<div class="container">
				<div class="section-heading alz3-section-heading">
					<div class="text">
						<span class="eyebrow"><?php alzaherah_copy_e( 'self_paced_eyebrow', 'ابدأ فور تأكيد الدفع' ); ?></span>
						<h2 class="section-title" id="self-paced-title"><?php alzaherah_copy_e( 'self_paced_title', 'التعلم الذاتي' ); ?></h2>
						<p class="section-copy"><?php alzaherah_copy_e( 'self_paced_text', 'دورات إلكترونية ذاتية أو مسجّلة تصل إلى حسابك مباشرة بعد ثبوت الدفع، بلا موعد حضور.' ); ?></p>
					</div>
					<a class="alz3-view-all" data-alz-copy-href="self_paced_url" href="<?php echo esc_url( alzaherah_copy_value( 'self_paced_url', $shop_url ) ); ?>"><?php alzaherah_copy_e( 'self_paced_label', 'عرض جميع الدورات' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>
				<div class="courses-grid courses-catalog-grid alz3-home-courses-grid">
					<?php
					while ( $alz_self_paced->have_posts() ) :
						$alz_self_paced->the_post();
						$alz_self_paced_product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
						if ( ! $alz_self_paced_product ) {
							continue;
						}
						get_template_part(
							'template-parts/course',
							'card',
							array(
								'product' => $alz_self_paced_product,
								'heading' => 'h3',
							)
						);
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$alz_home_exams = array();
	if ( class_exists( 'ALZ_Exams' ) && function_exists( 'wc_get_product' ) ) {
		$alz_home_exam_ids = get_posts(
			ALZ_Exams::query_args(
				array(
					'post_status'    => 'publish',
					'posts_per_page' => 3,
					'orderby'        => 'menu_order title',
					'order'          => 'ASC',
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			)
		);
		foreach ( $alz_home_exam_ids as $alz_home_exam_id ) {
			$alz_home_exam_product = wc_get_product( $alz_home_exam_id );
			if ( $alz_home_exam_product && $alz_home_exam_product->is_visible() ) {
				$alz_home_exams[] = $alz_home_exam_product;
			}
		}
	}
	if ( $alz_home_exams ) :
		?>
		<section class="alz3-course-section alz3-exams-section section" aria-labelledby="home-exams-title">
			<div class="container">
				<div class="section-heading alz3-section-heading">
					<div class="text">
						<span class="eyebrow"><?php alzaherah_copy_e( 'exams_eyebrow', 'قِس مستواك الآن' ); ?></span>
						<h2 class="section-title" id="home-exams-title"><?php alzaherah_copy_e( 'exams_title', 'الاختبارات المتاحة' ); ?></h2>
						<p class="section-copy"><?php alzaherah_copy_e( 'exams_text', 'اختبارات إلكترونية مستقلة بنتيجة فورية، تبدأ من حسابك بعد تأكيد الدفع.' ); ?></p>
					</div>
					<a class="alz3-view-all" data-alz-copy-href="exams_url" href="<?php echo esc_url( alzaherah_copy_value( 'exams_url', $exams_url ) ); ?>"><?php alzaherah_copy_e( 'exams_label', 'عرض جميع الاختبارات' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>

				<div class="courses-grid courses-catalog-grid alz3-home-courses-grid">
					<?php foreach ( $alz_home_exams as $alz_home_exam_product ) : ?>
						<?php
						get_template_part(
							'template-parts/sales',
							'card',
							array(
								'product' => $alz_home_exam_product,
								'heading' => 'h3',
							)
						);
						?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( '' !== $alz_training_products_markup ) : ?>
		<section class="alz3-course-section alz3-training-products-section section" aria-labelledby="training-products-title">
			<div class="container">
				<div class="section-heading alz3-section-heading">
					<div class="text">
						<span class="eyebrow"><?php alzaherah_copy_e( 'products_eyebrow', 'مواد عملية جاهزة' ); ?></span>
						<h2 class="section-title" id="training-products-title"><?php alzaherah_copy_e( 'products_title', 'المنتجات التدريبية الرقمية' ); ?></h2>
						<p class="section-copy"><?php alzaherah_copy_e( 'products_text', 'ملفات وقوالب جاهزة للتنزيل بعد ثبوت الدفع، مع صلاحية تنزيل محددة في حسابك.' ); ?></p>
					</div>
					<a class="alz3-view-all" data-alz-copy-href="products_url" href="<?php echo esc_url( alzaherah_copy_value( 'products_url', $alz_training_products_url ) ); ?>"><?php alzaherah_copy_e( 'products_label', 'عرض جميع المنتجات' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>

				<div class="courses-grid courses-catalog-grid alz3-home-courses-grid alz3-home-training-products-grid">
					<?php echo $alz_training_products_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup is generated by the trusted Core renderer. ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="section alz3-top-section" aria-labelledby="top-title">
		<div class="container">
			<?php
			$alz_top_base = array(
				'post_status'    => 'publish',
				'posts_per_page' => 3,
				'meta_key'       => 'total_sales',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'no_found_rows'  => true,
				'tax_query'      => $alz_home_catalog_tax,
				'meta_query'     => array(
					array(
						'key'     => 'total_sales',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			);
			if ( $alz_home_shown_course_ids ) {
				$alz_top_base['post__not_in'] = $alz_home_shown_course_ids;
			}
			$alz_top = new WP_Query(
				function_exists( 'alzaherah_course_query_args' )
					? alzaherah_course_query_args( $alz_top_base )
					: array_merge( array( 'post_type' => 'product' ), $alz_top_base )
			);
			if ( ! $alz_top->have_posts() && $alz_home_shown_course_ids ) {
				unset( $alz_top_base['post__not_in'] );
				$alz_top = new WP_Query(
					function_exists( 'alzaherah_course_query_args' )
						? alzaherah_course_query_args( $alz_top_base )
						: array_merge( array( 'post_type' => 'product' ), $alz_top_base )
				);
			}
			?>
				<div class="section-heading alz3-section-heading">
					<div class="text">
						<span class="eyebrow"><?php alzaherah_copy_e( 'popular_eyebrow', 'حسب عدد التسجيلات' ); ?></span>
						<h2 class="section-title" id="top-title"><?php alzaherah_copy_e( 'popular_title', 'الدورات الأكثر طلبًا' ); ?></h2>
						<p class="section-copy"><?php alzaherah_copy_e( 'popular_text', 'مرتبة وفق إجمالي التسجيلات المكتملة على المنصة، لا وفق ترتيب العرض في قسم الدورات المتاحة.' ); ?></p>
					</div>
					<a class="alz3-view-all" data-alz-copy-href="popular_url" href="<?php echo esc_url( alzaherah_copy_value( 'popular_url', $shop_url ) ); ?>"><?php alzaherah_copy_e( 'popular_label', 'عرض جميع الدورات' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
				</div>

				<?php if ( $alz_top->have_posts() ) : ?>
				<div class="alz3-top-grid">
					<?php
					$alz_top_rank = 0;
					while ( $alz_top->have_posts() ) :
						$alz_top->the_post();
						$alz_top_product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
						if ( ! $alz_top_product ) {
							continue;
						}
						++$alz_top_rank;
						$alz_top_thumb = $alz_top_product->get_image(
							'woocommerce_thumbnail',
							array(
								'alt'      => '',
								'loading'  => 'lazy',
								'decoding' => 'async',
							)
						);
						?>
						<a class="alz3-top-card" href="<?php echo esc_url( $alz_top_product->get_permalink() ); ?>">
							<span class="alz3-top-rank"><?php echo esc_html( (string) $alz_top_rank ); ?></span>
							<span class="alz3-top-media"><?php echo $alz_top_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce image markup. ?></span>
							<span class="alz3-top-body">
								<strong><?php echo esc_html( $alz_top_product->get_name() ); ?></strong>
								<small><?php esc_html_e( 'حسب التسجيلات المكتملة', 'alzaherah' ); ?></small>
							</span>
							<span class="alz3-top-price"><?php echo wp_kses_post( function_exists( 'alzaherah_course_price_html' ) ? alzaherah_course_price_html( $alz_top_product ) : $alz_top_product->get_price_html() ); ?></span>
						</a>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</div>
				<?php else : ?>
				<div class="alz3-empty-courses alz3-top-empty">
					<h3><?php esc_html_e( 'ستظهر هنا الدورات الأعلى تسجيلاً', 'alzaherah' ); ?></h3>
					<p><?php esc_html_e( 'يُحدَّث الترتيب تلقائيًا بعد اكتمال تسجيلات حقيقية، وليس وفق ترتيب العرض في قسم الدورات المتاحة.', 'alzaherah' ); ?></p>
				</div>
				<?php endif; ?>
		</div>
	</section>

	<?php if ( ( $alz_testimonials && $alz_testimonials->have_posts() ) || '' !== trim( $alz_testimonial_form_markup ) ) : ?>
	<section class="section alz3-testimonial-section" aria-labelledby="testimonials-title">
		<div class="container">
			<div class="section-heading alz3-section-heading">
				<div class="text">
					<span class="eyebrow"><?php alzaherah_copy_e( 'testimonials_eyebrow', 'تجارب موثوقة' ); ?></span>
					<h2 class="section-title" id="testimonials-title"><?php alzaherah_copy_e( 'testimonials_title', 'آراء متدربينا' ); ?></h2>
					<p class="section-copy"><?php alzaherah_copy_e( 'testimonials_text', 'تجارب يشاركها المتدربون وتظهر بعد مراجعتها واعتمادها من إدارة المنصة.' ); ?></p>
				</div>
			</div>

			<?php if ( $alz_testimonials && $alz_testimonials->have_posts() ) : ?>
				<div class="alz3-testimonial-marquee" data-testimonial-marquee role="region" aria-roledescription="<?php esc_attr_e( 'عارض', 'alzaherah' ); ?>" aria-label="<?php esc_attr_e( 'آراء المتدربين', 'alzaherah' ); ?>">
					<div class="alz3-testimonial-controls" aria-label="<?php esc_attr_e( 'التنقل بين آراء المتدربين', 'alzaherah' ); ?>">
						<button type="button" data-marquee-direction="previous" aria-controls="alz-home-testimonial-track" aria-label="<?php esc_attr_e( 'الرأي السابق', 'alzaherah' ); ?>"><?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						<button type="button" data-marquee-direction="next" aria-controls="alz-home-testimonial-track" aria-label="<?php esc_attr_e( 'الرأي التالي', 'alzaherah' ); ?>"><?php echo alzaherah_ui_arrow( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
					</div>
					<div class="alz3-testimonial-marquee-viewport">
						<div class="alz3-testimonial-marquee-track" id="alz-home-testimonial-track">
							<?php while ( $alz_testimonials->have_posts() ) : $alz_testimonials->the_post(); ?>
								<?php
								$alz_testimonial_id     = get_the_ID();
								$alz_testimonial_name   = sanitize_text_field( (string) get_post_meta( $alz_testimonial_id, '_alz_testimonial_display_name', true ) );
								$alz_testimonial_name   = $alz_testimonial_name ? $alz_testimonial_name : get_the_title();
								$alz_testimonial_rating = absint( get_post_meta( $alz_testimonial_id, '_alz_testimonial_rating', true ) );
								$alz_testimonial_rating = $alz_testimonial_rating >= 1 && $alz_testimonial_rating <= 5 ? $alz_testimonial_rating : 0;
								$alz_testimonial_course       = absint( get_post_meta( $alz_testimonial_id, '_alz_testimonial_course_id', true ) );
								$alz_testimonial_course_label = $alz_testimonial_course ? get_the_title( $alz_testimonial_course ) : sanitize_text_field( (string) get_post_meta( $alz_testimonial_id, '_alz_testimonial_course_label', true ) );
								$alz_testimonial_text   = wp_trim_words( wp_strip_all_tags( get_the_content() ), 42, '…' );
								?>
								<article class="alz3-testimonial-card">
									<header>
										<?php if ( $alz_testimonial_rating ) : ?>
											<bdi dir="ltr" class="alz3-testimonial-rating" aria-label="<?php echo esc_attr( sprintf( __( 'التقييم %1$d من %2$d', 'alzaherah' ), $alz_testimonial_rating, 5 ) ); ?>"><?php echo esc_html( $alz_testimonial_rating ); ?>/5</bdi>
										<?php endif; ?>
									</header>
									<blockquote><p><?php echo esc_html( $alz_testimonial_text ); ?></p></blockquote>
									<footer>
										<cite><?php echo esc_html( $alz_testimonial_name ); ?></cite>
										<?php if ( $alz_testimonial_course_label ) : ?>
											<span><?php echo esc_html( $alz_testimonial_course_label ); ?></span>
										<?php endif; ?>
									</footer>
								</article>
							<?php endwhile; wp_reset_postdata(); ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( '' !== trim( $alz_testimonial_form_markup ) ) : ?>
				<div class="alz3-testimonial-form-shell" aria-label="<?php esc_attr_e( 'إرسال تقييم جديد', 'alzaherah' ); ?>">
					<?php echo $alz_testimonial_form_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted markup rendered by the platform plugin. ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $alz_news->have_posts() || $alz_articles->have_posts() ) : ?>
	<section class="section alz3-content-section alz3-home-center" aria-labelledby="home-center-title">
		<div class="container">
			<div class="section-heading alz3-section-heading">
				<div class="text">
					<span class="eyebrow"><?php alzaherah_copy_e( 'news_articles_eyebrow', 'من المركز' ); ?></span>
					<h2 class="section-title" id="home-center-title"><?php alzaherah_copy_e( 'news_articles_title', 'أخبارنا ومقالاتنا' ); ?></h2>
					<p class="section-copy"><?php alzaherah_copy_e( 'news_articles_text', 'مستجدات المركز في الأخبار، ومعرفة مهنية قابلة للتطبيق في المقالات.' ); ?></p>
				</div>
			</div>
			<div class="alz3-center-columns">
	<?php if ( $alz_news->have_posts() ) : ?>
	<div class="alz3-home-news">
			<div class="alz3-center-column-head">
				<p class="alz3-center-kicker" id="home-news-title"><?php alzaherah_copy_e( 'news_title', 'أخبارنا' ); ?></p>
				<a class="alz3-view-all" data-alz-copy-href="news_url" href="<?php echo esc_url( alzaherah_copy_value( 'news_url', $alz_news_url ) ); ?>"><?php alzaherah_copy_e( 'news_label', 'عرض جميع الأخبار' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</div>

			<div class="alz3-content-grid">
				<?php while ( $alz_news->have_posts() ) : $alz_news->the_post(); ?>
					<?php $alz_card_image = $alz_home_content_image( get_the_ID() ); ?>
					<article class="alz3-content-card news-card">
						<a class="alz3-content-card-link" href="<?php the_permalink(); ?>">
							<span class="alz3-content-media" aria-hidden="true">
								<?php if ( 'local' === $alz_card_image['type'] ) : ?>
									<?php echo $alz_card_image['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated by wp_get_attachment_image(). ?>
								<?php else : ?>
									<span class="alz3-content-placeholder"><img src="<?php echo esc_url( $alz_card_image['url'] ); ?>" alt="" width="80" height="81" loading="lazy" decoding="async"><span><?php esc_html_e( 'مركز الزاهرة للتدريب', 'alzaherah' ); ?></span></span>
								<?php endif; ?>
							</span>
							<span class="alz3-content-body">
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'Y/m/d' ) ); ?></time>
								<h3><?php the_title(); ?></h3>
								<span class="alz3-content-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 23, '…' ) ); ?></span>
								<span class="alz3-content-more"><?php esc_html_e( 'قراءة الخبر', 'alzaherah' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</span>
						</a>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
	</div>
	<?php endif; ?>

	<?php if ( $alz_articles->have_posts() ) : ?>
	<div class="alz3-home-articles">
			<div class="alz3-center-column-head">
				<p class="alz3-center-kicker" id="home-articles-title"><?php alzaherah_copy_e( 'articles_title', 'مقالاتنا' ); ?></p>
				<a class="alz3-view-all" data-alz-copy-href="articles_url" href="<?php echo esc_url( alzaherah_copy_value( 'articles_url', $alz_articles_url ) ); ?>"><?php alzaherah_copy_e( 'articles_label', 'عرض جميع المقالات' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</div>

			<div class="alz3-content-grid">
				<?php while ( $alz_articles->have_posts() ) : $alz_articles->the_post(); ?>
					<?php $alz_card_image = $alz_home_content_image( get_the_ID() ); ?>
					<article class="alz3-content-card news-card">
						<a class="alz3-content-card-link" href="<?php the_permalink(); ?>">
							<span class="alz3-content-media" aria-hidden="true">
								<?php if ( 'local' === $alz_card_image['type'] ) : ?>
									<?php echo $alz_card_image['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated by wp_get_attachment_image(). ?>
								<?php else : ?>
									<span class="alz3-content-placeholder"><img src="<?php echo esc_url( $alz_card_image['url'] ); ?>" alt="" width="80" height="81" loading="lazy" decoding="async"><span><?php esc_html_e( 'مركز الزاهرة للتدريب', 'alzaherah' ); ?></span></span>
								<?php endif; ?>
							</span>
							<span class="alz3-content-body">
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'Y/m/d' ) ); ?></time>
								<h3><?php the_title(); ?></h3>
								<span class="alz3-content-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 23, '…' ) ); ?></span>
								<span class="alz3-content-more"><?php esc_html_e( 'قراءة المقال', 'alzaherah' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</span>
						</a>
					</article>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
	</div>
	<?php endif; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php
	$alz_home_partners = function_exists( 'alzaherah_partner_public_query' ) ? alzaherah_partner_public_query() : false;
	if ( $alz_home_partners && $alz_home_partners->have_posts() ) :
	?>
	<section class="section alz3-home-partners" aria-labelledby="home-partners-title">
		<div class="container">
			<div class="alz3-section-head">
				<div>
					<span class="eyebrow"><?php alzaherah_copy_e( 'partners_eyebrow', 'شراكات تصنع الأثر' ); ?></span>
					<h2 class="section-title" id="home-partners-title"><?php alzaherah_copy_e( 'partners_title', 'شركاء النجاح' ); ?></h2>
					<p><?php alzaherah_copy_e( 'partners_text', 'جهات نعتز بالتعاون معها في تطوير التدريب وخدمة المجتمع.' ); ?></p>
				</div>
				<div class="alz3-home-partner-actions">
					<a class="btn btn-secondary" data-alz-copy-href="partners_all_url" href="<?php echo esc_url( alzaherah_copy_value( 'partners_all_url', home_url( '/partners/' ) ) ); ?>"><?php alzaherah_copy_e( 'partners_all_label', 'عرض جميع الشركاء' ); ?></a>
					<a class="btn alz3-primary-cta" data-alz-copy-href="partners_request_url" href="<?php echo esc_url( alzaherah_copy_value( 'partners_request_url', home_url( '/partnership-request/' ) ) ); ?>"><?php alzaherah_copy_e( 'partners_request_label', 'تقديم طلب شراكة' ); ?></a>
				</div>
			</div>
			<div class="alz3-home-partner-carousel" data-partner-marquee role="region" aria-roledescription="<?php esc_attr_e( 'عارض', 'alzaherah' ); ?>" aria-label="<?php esc_attr_e( 'شركاء النجاح', 'alzaherah' ); ?>">
				<div class="alz3-home-partner-controls" aria-label="<?php esc_attr_e( 'التنقل بين الشركاء', 'alzaherah' ); ?>">
					<?php
					// أيقونات فيزيائية ثابتة (لا تعتمد على قلب RTL): يسار ← / يمين →
					// الأسهم فيزيائية ثابتة؛ العرض يدوي بالكامل بالسحب أو الأزرار.
					?>
					<button type="button" data-partner-direction="previous" aria-controls="alz-home-partner-track" aria-label="<?php esc_attr_e( 'السابق', 'alzaherah' ); ?>"><?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- forward path = chevron pointing left. ?></button>
					<button type="button" data-partner-direction="next" aria-controls="alz-home-partner-track" aria-label="<?php esc_attr_e( 'التالي', 'alzaherah' ); ?>"><?php echo alzaherah_ui_arrow( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- back path = chevron pointing right. ?></button>
				</div>
				<div class="alz3-home-partner-marquee-viewport">
					<div class="alz3-home-partner-marquee-track" id="alz-home-partner-track" aria-label="<?php esc_attr_e( 'شركاء النجاح', 'alzaherah' ); ?>">
					<?php while ( $alz_home_partners->have_posts() ) : $alz_home_partners->the_post(); ?>
						<article class="alz3-home-partner">
							<div><?php the_post_thumbnail( 'medium', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => 'شعار ' . get_the_title() ) ); ?></div>
							<h3><?php the_title(); ?></h3>
						</article>
					<?php endwhile; wp_reset_postdata(); ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<section class="alz3-final-cta">
		<div class="container alz3-final-cta-inner">
			<div>
				<span><?php alzaherah_copy_e( 'final_eyebrow', 'ابدأ اليوم' ); ?></span>
				<h2><?php alzaherah_copy_e( 'final_title', 'جاهز لتطوير مهاراتك؟' ); ?></h2>
				<p><?php alzaherah_copy_e( 'final_text', 'اختر دورتك، أو اختبارك، أو منتجك التدريبي، ثم أكمل التسجيل من حسابك خلال دقائق.' ); ?></p>
			</div>
			<div class="alz3-final-actions">
				<a class="btn alz3-primary-cta" data-alz-copy-href="final_courses_url" href="<?php echo esc_url( alzaherah_copy_value( 'final_courses_url', $shop_url ) ); ?>"><?php alzaherah_copy_e( 'final_courses_label', 'استعرض الدورات' ); ?></a>
				<a class="btn alz3-secondary-cta" data-alz-copy-href="final_exams_url" href="<?php echo esc_url( alzaherah_copy_value( 'final_exams_url', $exams_url ) ); ?>"><?php alzaherah_copy_e( 'final_exams_label', 'استعرض الاختبارات' ); ?></a>
				<?php if ( ! is_user_logged_in() ) : ?>
					<a class="btn alz3-secondary-cta" data-alz-copy-href="final_signup_url" href="<?php echo esc_url( alzaherah_copy_value( 'final_signup_url', $signup_url ) ); ?>"><?php alzaherah_copy_e( 'final_signup_label', 'إنشاء حساب' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
