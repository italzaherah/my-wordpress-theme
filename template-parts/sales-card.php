<?php
/**
 * بطاقة البيع الموحدة — هوية v4 فصل 10.
 *
 * بطاقة واحدة لكل ما يُباع: الدورات بأوضاعها (حضوري/مباشر/مدمج/إلكترونية ذاتية/مسجلة)،
 * المنتجات التدريبية الرقمية، والاختبارات الإلكترونية المستقلة. الهيكل والهوية ثابتان،
 * والاختلاف في نوع المحتوى والبيانات المعروضة فقط.
 *
 * بطاقات الأخبار والمقالات (alz3-content-card) مكوّن مستقل ولا يمر من هنا،
 * وبطاقات الحساب والطلبات خارج نطاق هذه البطاقة.
 *
 * @package Alzaherah
 * @since   4.10.0
 */

defined( 'ABSPATH' ) || exit;

$alz_card_product = isset( $args['product'] ) && $args['product'] instanceof WC_Product
	? $args['product']
	: ( function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null );

if ( ! $alz_card_product || ! $alz_card_product->is_visible() ) {
	return;
}

$alz_card_id      = $alz_card_product->get_id();
$alz_card_heading = isset( $args['heading'] ) && in_array( $args['heading'], array( 'h2', 'h3' ), true ) ? $args['heading'] : 'h2';

/* ---------------------------------------------------------------
 * نوع المحتوى: exam | training_product | course | standard
 * ------------------------------------------------------------- */
$alz_kind_meta = sanitize_key( (string) get_post_meta( $alz_card_id, '_alz_product_kind', true ) );
if ( 'exam' === $alz_kind_meta ) {
	$alz_card_kind = 'exam';
} elseif ( function_exists( 'alz_core_is_training_product' ) && alz_core_is_training_product( $alz_card_id ) ) {
	$alz_card_kind = 'training_product';
} elseif ( function_exists( 'alzaherah_is_confirmed_course_product' ) && alzaherah_is_confirmed_course_product( $alz_card_id ) ) {
	$alz_card_kind = 'course';
} elseif ( ! function_exists( 'alzaherah_is_confirmed_course_product' ) && 'yes' === (string) get_post_meta( $alz_card_id, '_alz_is_course', true ) ) {
	$alz_card_kind = 'course';
} else {
	// خارج أنواع البيع المعتمدة — لا تُعرض بطاقة.
	return;
}

$alz_card_title   = get_the_title( $alz_card_id );
$alz_card_url     = get_permalink( $alz_card_id );
$alz_card_excerpt = get_the_excerpt( $alz_card_id );
if ( ! $alz_card_excerpt ) {
	$alz_card_excerpt = wp_strip_all_tags( get_post_field( 'post_content', $alz_card_id ) );
}

/* ---------------------------------------------------------------
 * وضع الدورة وتسمية النوع
 * ------------------------------------------------------------- */
$alz_course_mode   = 'course' === $alz_card_kind ? sanitize_key( (string) get_post_meta( $alz_card_id, '_alz_course_mode', true ) ) : '';
$alz_is_self_paced = 'course' === $alz_card_kind && (
	function_exists( 'alz_core_course_is_self_paced' )
		? alz_core_course_is_self_paced( $alz_card_id )
		: in_array( $alz_course_mode, array( 'self_paced', 'recorded' ), true )
);

if ( 'course' === $alz_card_kind ) {
	$alz_modes       = function_exists( 'alzaherah_course_modes' ) ? alzaherah_course_modes() : array();
	$alz_kind_label  = isset( $alz_modes[ $alz_course_mode ] ) ? $alz_modes[ $alz_course_mode ] : __( 'دورة تدريبية', 'alzaherah' );
} elseif ( 'training_product' === $alz_card_kind ) {
	$alz_kind_label = __( 'منتج رقمي', 'alzaherah' );
} else {
	$alz_exam_type  = trim( (string) get_post_meta( $alz_card_id, '_alz_exam_type_label', true ) );
	$alz_kind_label = '' !== $alz_exam_type ? $alz_exam_type : __( 'اختبار إلكتروني', 'alzaherah' );
}

/* ---------------------------------------------------------------
 * التصنيف والبحث (فلاتر الشبكة تعتمد data-*)
 * ------------------------------------------------------------- */
$alz_card_terms    = get_the_terms( $alz_card_id, 'product_cat' );
$alz_card_slugs    = array();
$alz_card_category = '';
$alz_category_url  = '';
if ( $alz_card_terms && ! is_wp_error( $alz_card_terms ) ) {
	$alz_card_slugs = wp_list_pluck( $alz_card_terms, 'slug' );
	foreach ( $alz_card_terms as $alz_card_term ) {
		// التصنيف الافتراضي غير المصنف ليس تصنيف عرض.
		if ( 'uncategorized' === $alz_card_term->slug ) {
			continue;
		}
		$alz_card_category = $alz_card_term->name;
		$alz_category_url  = get_term_link( $alz_card_term );
		break;
	}
}

$alz_trainer_id   = 'course' === $alz_card_kind ? absint( get_post_meta( $alz_card_id, '_alz_trainer_id', true ) ) : 0;
$alz_trainer_name = $alz_trainer_id ? get_the_title( $alz_trainer_id ) : ( 'course' === $alz_card_kind ? get_post_meta( $alz_card_id, '_alz_trainer', true ) : '' );
$alz_card_search  = wp_strip_all_tags( $alz_card_title . ' ' . $alz_trainer_name . ' ' . $alz_kind_label . ' ' . $alz_card_category . ' ' . $alz_card_excerpt );

/* ---------------------------------------------------------------
 * الغلاف
 * ------------------------------------------------------------- */
$alz_has_thumbnail = has_post_thumbnail( $alz_card_id );
if ( 'exam' === $alz_card_kind && $alz_has_thumbnail ) {
	$alz_promo_cover_id = absint( get_post_thumbnail_id( $alz_card_id ) );
	$alz_has_thumbnail = class_exists( 'ALZ_Exam_Promo_Images' )
		? ALZ_Exam_Promo_Images::is_public_image( $alz_promo_cover_id )
		: ( 'attachment' === get_post_type( $alz_promo_cover_id )
			&& ! in_array( get_post_status( $alz_promo_cover_id ), array( 'trash', 'private' ), true )
			&& in_array( get_post_mime_type( $alz_promo_cover_id ), array( 'image/jpeg', 'image/png', 'image/webp' ), true )
			&& ! get_post_meta( $alz_promo_cover_id, '_alz_assess_private', true ) );
}
$alz_card_image_alt = sprintf( __( 'صورة %s', 'alzaherah' ), $alz_card_title );
if ( 'exam' === $alz_card_kind && $alz_has_thumbnail ) {
	$alz_promo_alt = trim( (string) get_post_meta( $alz_promo_cover_id, '_wp_attachment_image_alt', true ) );
	if ( '' !== $alz_promo_alt ) { $alz_card_image_alt = $alz_promo_alt; }
}
$alz_card_image    = $alz_has_thumbnail
	? get_the_post_thumbnail(
		$alz_card_id,
		'exam' === $alz_card_kind ? 'large' : 'medium_large',
		array(
			'loading'  => 'lazy',
			'decoding' => 'async',
			'alt'      => $alz_card_image_alt,
		)
	)
	: '<img class="is-placeholder" loading="lazy" decoding="async" src="' . esc_url( function_exists( 'alzaherah_local_placeholder_image_url' ) ? alzaherah_local_placeholder_image_url() : get_template_directory_uri() . '/assets/logo-mark.png' ) . '" alt="' . esc_attr( sprintf( __( 'صورة بديلة لـ%s', 'alzaherah' ), $alz_card_title ) ) . '">';

/* ---------------------------------------------------------------
 * الحالة وشارتها + قابلية الشراء
 * ------------------------------------------------------------- */
$alz_is_simple = $alz_card_product->is_type( 'simple' );

if ( 'course' === $alz_card_kind ) {
	$alz_card_status = function_exists( 'alzaherah_course_effective_status' )
		? alzaherah_course_effective_status( $alz_card_product )
		: ( $alz_card_product->is_in_stock() ? 'available' : 'full' );
} else {
	$alz_card_status = ( $alz_card_product->is_purchasable() && $alz_card_product->is_in_stock() ) ? 'available' : 'full';
}

$alz_can_buy = 'available' === $alz_card_status && $alz_card_product->is_purchasable() && $alz_card_product->is_in_stock();

$alz_status_badge_map = array(
	'available' => 'open',
	'ended'     => 'closed',
	'full'      => 'closed',
	'postponed' => 'closed',
	'cancelled' => 'closed',
	'undated'   => 'info-b',
);
$alz_status_badge = isset( $alz_status_badge_map[ $alz_card_status ] ) ? $alz_status_badge_map[ $alz_card_status ] : 'open';

$alz_instant_kinds = ( 'training_product' === $alz_card_kind || 'exam' === $alz_card_kind || $alz_is_self_paced );
if ( $alz_instant_kinds && 'available' === $alz_card_status ) {
	$alz_status_text = __( 'متاح فورًا', 'alzaherah' );
} elseif ( $alz_instant_kinds && 'full' === $alz_card_status ) {
	$alz_status_text = __( 'غير متاح حاليًا', 'alzaherah' );
} else {
	$alz_status_text = function_exists( 'alzaherah_course_status_label' )
		? alzaherah_course_status_label( $alz_card_status )
		: ( 'available' === $alz_card_status ? __( 'متاح للتسجيل', 'alzaherah' ) : __( 'اكتمل العدد', 'alzaherah' ) );
}

/* ---------------------------------------------------------------
 * رابط الشراء
 * ------------------------------------------------------------- */
$alz_buy_url = $alz_is_simple && $alz_can_buy
	? ( function_exists( 'alz_course_registration_start_url' )
		? alz_course_registration_start_url( $alz_card_id )
		: add_query_arg( 'add-to-cart', $alz_card_id, function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ) ) )
	: $alz_card_url;

/* ---------------------------------------------------------------
 * الخصم والمقاعد
 * ------------------------------------------------------------- */
$alz_sale_label = '';
if ( $alz_card_product->is_on_sale() ) {
	$alz_regular = (float) $alz_card_product->get_regular_price();
	$alz_sale    = (float) $alz_card_product->get_sale_price();
	if ( $alz_regular > 0 && $alz_sale > 0 && $alz_sale < $alz_regular ) {
		$alz_sale_label = sprintf(
			/* translators: %s: خصم بالنسبة المئوية */
			__( 'خصم %s%%', 'alzaherah' ),
			(string) round( ( 1 - ( $alz_sale / $alz_regular ) ) * 100 )
		);
	}
}

$alz_seats_left = ( 'course' === $alz_card_kind && ! $alz_is_self_paced && function_exists( 'alzaherah_seats_left' ) )
	? alzaherah_seats_left( $alz_card_product )
	: null;

/* ---------------------------------------------------------------
 * صفوف المعلومات حسب النوع
 * ------------------------------------------------------------- */
$alz_info_rows = array();

if ( 'course' === $alz_card_kind && ! $alz_is_self_paced ) {
	$alz_raw_date     = (string) get_post_meta( $alz_card_id, '_alz_course_date', true );
	$alz_date_display = function_exists( 'alzaherah_format_course_date' ) ? alzaherah_format_course_date( $alz_raw_date ) : '';
	if ( $alz_date_display ) {
		$alz_info_rows[] = sprintf( __( 'تبدأ %s', 'alzaherah' ), $alz_date_display );
	}
	$alz_certificate = trim( (string) get_post_meta( $alz_card_id, '_alz_certificate_type', true ) );
	if ( $alz_certificate ) {
		$alz_info_rows[] = $alz_certificate;
	}
} elseif ( 'course' === $alz_card_kind ) {
	$alz_el_stats = function_exists( 'alz_elearning_course_card_stats' ) ? alz_elearning_course_card_stats( $alz_card_id ) : array();
	if ( ! empty( $alz_el_stats['topics'] ) ) {
		$alz_info_rows[] = sprintf( __( '%s وحدات تعليمية', 'alzaherah' ), number_format_i18n( absint( $alz_el_stats['topics'] ) ) );
	}
	if ( ! empty( $alz_el_stats['lessons'] ) ) {
		$alz_info_rows[] = sprintf( __( '%s درسًا', 'alzaherah' ), number_format_i18n( absint( $alz_el_stats['lessons'] ) ) );
	}
	if ( ! $alz_info_rows ) {
		$alz_info_rows[] = __( 'تعلّم ذاتي بوتيرتك الخاصة', 'alzaherah' );
	}
	$alz_certificate = trim( (string) get_post_meta( $alz_card_id, '_alz_certificate_type', true ) );
	if ( $alz_certificate ) {
		$alz_info_rows[] = $alz_certificate;
	}
} elseif ( 'training_product' === $alz_card_kind ) {
	$alz_info_rows[] = __( 'تحميل آمن', 'alzaherah' );
	$alz_info_rows[] = __( 'صلاحية 90 يومًا', 'alzaherah' );
} else {
	$alz_exam_minutes = absint( get_post_meta( $alz_card_id, '_alz_exam_duration_minutes', true ) );
	if ( $alz_exam_minutes > 0 ) {
		$alz_info_rows[] = sprintf( __( 'المدة: %s دقيقة', 'alzaherah' ), number_format_i18n( $alz_exam_minutes ) );
	}
	$alz_exam_attempts = get_post_meta( $alz_card_id, '_alz_exam_attempts_allowed', true );
	if ( '' !== (string) $alz_exam_attempts ) {
		$alz_exam_attempts = absint( $alz_exam_attempts );
		$alz_info_rows[]   = $alz_exam_attempts > 0
			? sprintf( _n( 'محاولة واحدة', '%s محاولات', $alz_exam_attempts, 'alzaherah' ), number_format_i18n( $alz_exam_attempts ) )
			: __( 'محاولات غير محدودة', 'alzaherah' );
	}
	if ( ! $alz_info_rows ) {
		$alz_info_rows[] = __( 'نتيجة ومستوى فور الإكمال', 'alzaherah' );
	}
}

/* ---------------------------------------------------------------
 * زر الإجراء الموحد
 * ------------------------------------------------------------- */
$alz_cta_class = 'course-register btn';
if ( $alz_can_buy ) {
	$alz_cta_class .= ' btn-primary';
} else {
	$alz_cta_class .= ' is-closed';
}
if ( 'ended' === $alz_card_status ) {
	$alz_cta_class .= ' is-ended';
}

if ( $alz_can_buy && $alz_is_simple ) {
	if ( 'training_product' === $alz_card_kind ) {
		$alz_cta_label = __( 'أضف إلى السلة', 'alzaherah' );
	} elseif ( 'exam' === $alz_card_kind ) {
		$alz_cta_label = __( 'اشترِ الاختبار', 'alzaherah' );
	} else {
		$alz_cta_label = __( 'سجّل الآن', 'alzaherah' );
	}
} elseif ( 'ended' === $alz_card_status ) {
	$alz_cta_label = __( 'انتهى التسجيل', 'alzaherah' );
} else {
	$alz_cta_label = __( 'عرض التفاصيل', 'alzaherah' );
}
?>
<article class="course-card catalog-course-card commerce-card is-kind-<?php echo esc_attr( $alz_card_kind ); ?><?php echo 'training_product' === $alz_card_kind ? ' is-training-product' : ''; ?><?php echo $alz_is_self_paced ? ' is-self-paced' : ''; ?> is-status-<?php echo esc_attr( $alz_card_status ); ?>"
	data-status="<?php echo esc_attr( $alz_card_status ); ?>"
	data-kind="<?php echo esc_attr( $alz_card_kind ); ?>"
	data-category="<?php echo esc_attr( implode( ' ', $alz_card_slugs ) ); ?>"
	data-search="<?php echo esc_attr( $alz_card_search ); ?>">
	<a class="course-cover course-thumb" href="<?php echo esc_url( $alz_card_url ); ?>">
		<?php echo wp_kses_post( $alz_card_image ); ?>
		<?php if ( 'exam' !== $alz_card_kind ) : ?>
			<span class="badge <?php echo esc_attr( $alz_status_badge ); ?>"><?php echo esc_html( $alz_status_text ); ?></span>
		<?php endif; ?>
	</a>

	<div class="course-body">
		<div class="course-meta">
			<?php if ( 'exam' === $alz_card_kind ) : ?>
				<span class="badge <?php echo esc_attr( $alz_status_badge ); ?>"><?php echo esc_html( $alz_status_text ); ?></span>
			<?php endif; ?>
			<span class="badge kind"><?php echo esc_html( $alz_kind_label ); ?></span>
			<?php if ( $alz_card_category && 'course' === $alz_card_kind ) : ?>
				<?php if ( $alz_category_url && ! is_wp_error( $alz_category_url ) ) : ?>
					<a class="badge cat" href="<?php echo esc_url( $alz_category_url ); ?>"><?php echo esc_html( $alz_card_category ); ?></a>
				<?php else : ?>
					<span class="badge cat"><?php echo esc_html( $alz_card_category ); ?></span>
				<?php endif; ?>
			<?php endif; ?>
			<?php if ( $alz_sale_label ) : ?>
				<span class="badge sale"><?php echo esc_html( $alz_sale_label ); ?></span>
			<?php endif; ?>
			<?php if ( 'available' === $alz_card_status && null !== $alz_seats_left && $alz_seats_left > 0 && $alz_seats_left <= 10 ) : ?>
				<span class="badge low"><?php printf( esc_html__( 'متبقي %s مقاعد', 'alzaherah' ), esc_html( number_format_i18n( $alz_seats_left ) ) ); ?></span>
			<?php endif; ?>
		</div>

		<<?php echo esc_html( $alz_card_heading ); ?>><a href="<?php echo esc_url( $alz_card_url ); ?>"><?php echo esc_html( $alz_card_title ); ?></a></<?php echo esc_html( $alz_card_heading ); ?>>
		<p class="desc"><?php echo esc_html( wp_trim_words( $alz_card_excerpt, 14 ) ); ?></p>

		<?php if ( $alz_info_rows ) : ?>
			<div class="course-info">
				<?php foreach ( $alz_info_rows as $alz_info_row ) : ?>
					<span><?php echo esc_html( $alz_info_row ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

			<div class="price"><?php echo wp_kses_post( 'course' === $alz_card_kind && function_exists( 'alzaherah_course_price_html' ) ? alzaherah_course_price_html( $alz_card_product ) : $alz_card_product->get_price_html() ); ?></div>
		<a class="<?php echo esc_attr( $alz_cta_class ); ?>" href="<?php echo esc_url( $alz_buy_url ); ?>">
			<?php echo esc_html( $alz_cta_label ); ?>
			<?php
			if ( function_exists( 'alzaherah_ui_arrow' ) ) {
				echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted SVG helper.
			}
			?>
		</a>
	</div>
</article>
