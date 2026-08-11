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

$hero_badge = get_theme_mod( 'alzaherah_hero_badge', __( 'منصتك نحو الاحتراف', 'alzaherah' ) );
$hero_title = get_theme_mod( 'alzaherah_hero_title', __( 'طوّر مهاراتك مع أفضل الدورات التدريبية', 'alzaherah' ) );
$hero_text  = get_theme_mod( 'alzaherah_hero_text', __( 'سجّل، ادفع بأمان، وابدأ رحلتك التعليمية فورًا عبر تجربة عربية متكاملة وسهلة.', 'alzaherah' ) );

$alz_home_course_args = array(
	'post_status'                 => 'publish',
	'posts_per_page'              => 6,
	'alz_available_courses_first' => 'upcoming',
	'orderby'                     => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	'tax_query'                   => array(
		array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => array( 'exclude-from-catalog' ),
			'operator' => 'NOT IN',
		),
	),
);
$courses = new WP_Query(
	function_exists( 'alzaherah_course_query_args' )
		? alzaherah_course_query_args( $alz_home_course_args )
		: array_merge( array( 'post_type' => 'product' ), $alz_home_course_args )
);

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
			<div class="alz3-badge"><span aria-hidden="true">🎓</span><?php echo esc_html( $hero_badge ); ?></div>
			<h1 id="hero-title"><?php echo wp_kses_post( alzaherah_highlight_hero_title( $hero_title ) ); ?></h1>
			<p><?php echo esc_html( $hero_text ); ?></p>

			<div class="alz3-hero-actions">
				<a class="btn alz3-primary-cta" href="<?php echo esc_url( $shop_url ); ?>">
					<?php esc_html_e( 'تصفّح الدورات', 'alzaherah' ); ?><?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<?php if ( is_user_logged_in() ) : ?>
					<a class="btn alz3-secondary-cta" href="<?php echo esc_url( $account_url ); ?>"><?php esc_html_e( 'فتح لوحة حسابي', 'alzaherah' ); ?></a>
				<?php else : ?>
					<a class="btn alz3-secondary-cta" href="<?php echo esc_url( $signup_url ); ?>"><?php esc_html_e( 'أنشئ حسابك مجانًا', 'alzaherah' ); ?></a>
				<?php endif; ?>
			</div>

		</div>
	</section>

	<div class="alz3-trust-wrap" aria-label="<?php esc_attr_e( 'مزايا المنصة', 'alzaherah' ); ?>">
		<div class="container">
			<div class="alz3-trust-grid">
				<article><span aria-hidden="true">🛡️</span><div><strong><?php esc_html_e( 'دفع آمن ومشفّر', 'alzaherah' ); ?></strong><small><?php esc_html_e( 'مدى، Visa، Mastercard وApple Pay', 'alzaherah' ); ?></small></div></article>
				<article><span aria-hidden="true">🏅</span><div><strong><?php esc_html_e( 'شهادات معتمدة', 'alzaherah' ); ?></strong><small><?php esc_html_e( 'شهادة إتمام لكل دورة مسجلة', 'alzaherah' ); ?></small></div></article>
				<article><span aria-hidden="true">⚡</span><div><strong><?php esc_html_e( 'وصول فوري', 'alzaherah' ); ?></strong><small><?php esc_html_e( 'تأكيد التسجيل والفاتورة بعد الدفع', 'alzaherah' ); ?></small></div></article>
			</div>
		</div>
	</div>

	<?php if ( function_exists( 'alz_render_home_announcements' ) ) : ?>
		<?php alz_render_home_announcements(); ?>
	<?php elseif ( function_exists( 'alzaherah_promo_strip' ) ) : ?>
		<?php alzaherah_promo_strip(); ?>
	<?php endif; ?>

	<section class="section alz3-impact" aria-labelledby="home-impact-title">
		<div class="container">
			<div class="alz3-centered-heading alz3-impact-heading">
				<span class="eyebrow"><?php esc_html_e( 'أثر ينمو بثقتكم', 'alzaherah' ); ?></span>
				<h2 class="section-title" id="home-impact-title"><?php esc_html_e( 'أثرنا بالأرقام', 'alzaherah' ); ?></h2>
				<p class="section-copy"><?php esc_html_e( 'خبرة تدريبية ممتدة نضعها في خدمة الأفراد والمنشآت.', 'alzaherah' ); ?></p>
			</div>

			<dl class="alz3-impact-grid">
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
	</section>

	<section class="alz3-course-section section" aria-labelledby="courses-title">
		<div class="container">
			<div class="section-heading alz3-section-heading">
				<div class="text">
					<span class="eyebrow"><?php esc_html_e( 'ابدأ التعلم الآن', 'alzaherah' ); ?></span>
					<h2 class="section-title" id="courses-title"><?php esc_html_e( 'الدورات المتاحة للتسجيل', 'alzaherah' ); ?></h2>
					<p class="section-copy"><?php esc_html_e( 'اعثر على الدورة المناسبة حسب المجال، السعر، الموعد ونمط الحضور.', 'alzaherah' ); ?></p>
				</div>
				<a class="alz3-view-all" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'عرض جميع الدورات', 'alzaherah' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
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

	<?php if ( '' !== $alz_training_products_markup ) : ?>
		<section class="alz3-course-section alz3-training-products-section section" aria-labelledby="training-products-title">
			<div class="container">
				<div class="section-heading alz3-section-heading">
					<div class="text">
						<span class="eyebrow"><?php esc_html_e( 'مواد عملية جاهزة', 'alzaherah' ); ?></span>
						<h2 class="section-title" id="training-products-title"><?php esc_html_e( 'المنتجات التدريبية الرقمية', 'alzaherah' ); ?></h2>
						<p class="section-copy"><?php esc_html_e( 'ملفات وقوالب تدريبية قابلة للتنزيل، مع عرض السعر الأساسي وسعر التخفيض بوضوح.', 'alzaherah' ); ?></p>
					</div>
					<a class="alz3-view-all" href="<?php echo esc_url( $alz_training_products_url ); ?>"><?php esc_html_e( 'عرض جميع المنتجات', 'alzaherah' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
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
			$alz_top_args = array(
				'post_status'                 => 'publish',
				'posts_per_page'              => 3,
				'alz_available_courses_first' => 'priority',
				'meta_key'                    => 'total_sales',
				'orderby'                     => 'meta_value_num',
				'order'                       => 'DESC',
				'no_found_rows'               => true,
				'meta_query'                  => array(
					array(
						'key'     => 'total_sales',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					),
				),
			);
			$alz_top = new WP_Query(
				function_exists( 'alzaherah_course_query_args' )
					? alzaherah_course_query_args( $alz_top_args )
					: array_merge( array( 'post_type' => 'product' ), $alz_top_args )
			);
			if ( $alz_top->have_posts() ) :
				?>
				<div class="section-heading alz3-section-heading">
					<div class="text">
						<span class="eyebrow"><?php esc_html_e( 'اختيار المتدربين', 'alzaherah' ); ?></span>
						<h2 class="section-title" id="top-title"><?php esc_html_e( 'الدورات الأكثر طلبًا', 'alzaherah' ); ?></h2>
					</div>
				</div>

				<div class="alz3-top-grid">
					<?php
					$alz_rank = 0;
					while ( $alz_top->have_posts() ) :
						$alz_top->the_post();
						$alz_rank++;
						$alz_top_product = wc_get_product( get_the_ID() );
						if ( ! $alz_top_product ) {
							continue;
						}
						?>
						<a class="alz3-top-card" href="<?php the_permalink(); ?>">
							<span class="alz3-top-rank" aria-hidden="true"><?php echo esc_html( number_format_i18n( $alz_rank ) ); ?></span>
							<span class="alz3-top-media">
								<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'thumbnail', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => sprintf( __( 'صورة دورة %s', 'alzaherah' ), get_the_title() ) ) ); } else { echo '<i aria-hidden="true">🎓</i>'; } ?>
							</span>
							<span class="alz3-top-body">
								<strong><?php the_title(); ?></strong>
							</span>
							<span class="alz3-top-price"><?php echo wp_kses_post( $alz_top_product->get_price_html() ); ?></span>
						</a>
					<?php endwhile; wp_reset_postdata(); ?>
				</div>
				<?php
			endif;
			?>
		</div>
	</section>

	<?php if ( ( $alz_testimonials && $alz_testimonials->have_posts() ) || '' !== trim( $alz_testimonial_form_markup ) ) : ?>
	<section class="section alz3-testimonial-section" aria-labelledby="testimonials-title">
		<div class="container">
			<div class="section-heading alz3-section-heading">
				<div class="text">
					<span class="eyebrow"><?php esc_html_e( 'تجارب موثوقة', 'alzaherah' ); ?></span>
					<h2 class="section-title" id="testimonials-title"><?php esc_html_e( 'آراء متدربينا', 'alzaherah' ); ?></h2>
					<p class="section-copy"><?php esc_html_e( 'تجارب يشاركها المتدربون وتظهر بعد مراجعتها واعتمادها من إدارة المنصة.', 'alzaherah' ); ?></p>
				</div>
			</div>

			<?php if ( $alz_testimonials && $alz_testimonials->have_posts() ) : ?>
				<div class="alz3-testimonial-marquee" data-testimonial-marquee>
					<div class="alz3-testimonial-marquee-viewport">
						<div class="alz3-testimonial-marquee-track">
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

	<?php if ( $alz_news->have_posts() ) : ?>
	<section class="section alz3-content-section alz3-home-news" aria-labelledby="home-news-title">
		<div class="container">
			<div class="section-heading alz3-section-heading">
				<div class="text">
					<span class="eyebrow"><?php esc_html_e( 'آخر مستجدات المركز', 'alzaherah' ); ?></span>
					<h2 class="section-title" id="home-news-title"><?php esc_html_e( 'أخبارنا', 'alzaherah' ); ?></h2>
					<p class="section-copy"><?php esc_html_e( 'تابع شراكات المركز وبرامجه ومبادراته التدريبية والمجتمعية.', 'alzaherah' ); ?></p>
				</div>
				<a class="alz3-view-all" href="<?php echo esc_url( $alz_news_url ); ?>"><?php esc_html_e( 'عرض جميع الأخبار', 'alzaherah' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</div>

			<div class="alz3-content-grid">
				<?php while ( $alz_news->have_posts() ) : $alz_news->the_post(); ?>
					<?php $alz_card_image = $alz_home_content_image( get_the_ID() ); ?>
					<article class="alz3-content-card">
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
	</section>
	<?php endif; ?>

	<?php if ( $alz_articles->have_posts() ) : ?>
	<section class="section alz3-content-section alz3-home-articles" aria-labelledby="home-articles-title">
		<div class="container">
			<div class="section-heading alz3-section-heading">
				<div class="text">
					<span class="eyebrow"><?php esc_html_e( 'معرفة قابلة للتطبيق', 'alzaherah' ); ?></span>
					<h2 class="section-title" id="home-articles-title"><?php esc_html_e( 'مقالاتنا', 'alzaherah' ); ?></h2>
					<p class="section-copy"><?php esc_html_e( 'محتوى مهني يساعدك على تطوير مهاراتك واتخاذ قرارات تعلم أوضح.', 'alzaherah' ); ?></p>
				</div>
				<a class="alz3-view-all" href="<?php echo esc_url( $alz_articles_url ); ?>"><?php esc_html_e( 'عرض جميع المقالات', 'alzaherah' ); ?> <?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</div>

			<div class="alz3-content-grid">
				<?php while ( $alz_articles->have_posts() ) : $alz_articles->the_post(); ?>
					<?php $alz_card_image = $alz_home_content_image( get_the_ID() ); ?>
					<article class="alz3-content-card">
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
					<span class="eyebrow"><?php esc_html_e( 'شراكات تصنع الأثر', 'alzaherah' ); ?></span>
					<h2 class="section-title" id="home-partners-title"><?php esc_html_e( 'شركاء النجاح', 'alzaherah' ); ?></h2>
					<p><?php esc_html_e( 'جهات نعتز بالتعاون معها في تطوير التدريب وخدمة المجتمع.', 'alzaherah' ); ?></p>
				</div>
				<div class="alz3-home-partner-actions">
					<a class="btn btn-secondary" href="<?php echo esc_url( home_url( '/partners/' ) ); ?>"><?php esc_html_e( 'عرض جميع الشركاء', 'alzaherah' ); ?></a>
					<a class="btn alz3-primary-cta" href="<?php echo esc_url( home_url( '/partnership-request/' ) ); ?>"><?php esc_html_e( 'تقديم طلب شراكة', 'alzaherah' ); ?></a>
				</div>
			</div>
			<div class="alz3-home-partner-carousel" data-partner-marquee>
				<div class="alz3-home-partner-controls" aria-label="<?php esc_attr_e( 'التنقل بين الشركاء', 'alzaherah' ); ?>">
					<?php
					// أيقونات فيزيائية ثابتة (لا تعتمد على قلب RTL): يسار ← / يمين →
					// previous = عكس اتجاه الحركة التلقائية، next = مع اتجاه الحركة.
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
			<div><span><?php esc_html_e( 'ابدأ اليوم', 'alzaherah' ); ?></span><h2><?php esc_html_e( 'جاهز لتطوير مهاراتك؟', 'alzaherah' ); ?></h2><p><?php esc_html_e( 'أنشئ حسابك واستعرض الدورات المتاحة واحجز مقعدك خلال دقائق.', 'alzaherah' ); ?></p></div>
			<div class="alz3-final-actions"><a class="btn alz3-primary-cta" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'استعرض الدورات', 'alzaherah' ); ?></a><?php if ( ! is_user_logged_in() ) : ?><a class="btn alz3-secondary-cta" href="<?php echo esc_url( $signup_url ); ?>"><?php esc_html_e( 'إنشاء حساب', 'alzaherah' ); ?></a><?php endif; ?></div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
