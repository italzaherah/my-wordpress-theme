<?php
/**
 * حالة الدورة + شروط الالتحاق + شريط العروض (وفق كراسة المتطلبات).
 *
 * - حالة الدورة: متاحة / مؤجلة / ملغاة (والمكتملة تُشتق من نفاد المقاعد).
 * - منع التسجيل تلقائيًا في الدورات المؤجلة أو الملغاة.
 * - حقل "شروط ومتطلبات الالتحاق" يظهر في صفحة الدورة.
 * - دوال مساعدة: شارة الحالة، المقاعد المتبقية.
 * - إعدادات "شريط العروض والكوبونات" في تخصيص المظهر.
 *
 * التركيب: ضع الملف في /inc ثم أضف في نهاية functions.php:
 * require_once get_template_directory() . '/inc/course-status.php';
 *
 * @package Alzaherah
 * @since   3.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * حالات الدورة القابلة للتحديد يدويًا.
 *
 * @return array
 */
function alzaherah_course_statuses() {
	if ( function_exists( 'alzaherah_core_owns_courses' ) && alzaherah_core_owns_courses() ) {
		return ALZ_Courses::statuses();
	}
	return array(
		'available' => __( 'متاحة للتسجيل', 'alzaherah' ),
		'full'      => __( 'مكتملة (إغلاق يدوي)', 'alzaherah' ),
		'postponed' => __( 'مؤجلة', 'alzaherah' ),
		'cancelled' => __( 'ملغاة', 'alzaherah' ),
	);
}

/**
 * تنسيق تاريخ الدورة بصياغة عربية واضحة مثل: 31 يوليو 2026.
 *
 * @param string $date تاريخ بصيغة Y-m-d.
 * @return string
 */
function alzaherah_format_course_date( $date ) {
	if ( function_exists( 'alz_core_format_course_date' ) ) {
		return alz_core_format_course_date( $date );
	}
	if ( ! is_string( $date ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts ) ) {
		return '';
	}

	$year  = (int) $parts[1];
	$month = (int) $parts[2];
	$day   = (int) $parts[3];

	if ( ! checkdate( $month, $day, $year ) ) {
		return '';
	}

	$months = array(
		1  => 'يناير',
		2  => 'فبراير',
		3  => 'مارس',
		4  => 'أبريل',
		5  => 'مايو',
		6  => 'يونيو',
		7  => 'يوليو',
		8  => 'أغسطس',
		9  => 'سبتمبر',
		10 => 'أكتوبر',
		11 => 'نوفمبر',
		12 => 'ديسمبر',
	);

	return sprintf( '%1$d %2$s %3$d', $day, $months[ $month ], $year );
}

/**
 * هل انتهى يوم بداية الدورة؟
 *
 * يبقى التسجيل متاحًا طوال يوم بداية الدورة، ويُغلق تلقائيًا عند بداية اليوم التالي.
 * المقارنة تتم وفق المنطقة الزمنية المضبوطة في WordPress.
 *
 * @param WC_Product|int $product الدورة.
 * @return bool
 */
function alzaherah_course_registration_ended( $product ) {
	if ( function_exists( 'alz_core_course_registration_ended' ) ) {
		return alz_core_course_registration_ended( $product );
	}
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product ) {
		return false;
	}

	$date = get_post_meta( $product->get_id(), '_alz_course_date', true );
	if ( ! is_string( $date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return false;
	}

	$timezone = wp_timezone();
	$day      = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $timezone );
	$errors   = DateTimeImmutable::getLastErrors();

	if ( ! $day || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) ) {
		return false;
	}

	$registration_cutoff = $day->modify( '+1 day' );

	return new DateTimeImmutable( 'now', $timezone ) >= $registration_cutoff;
}

/**
 * الحالة الفعلية للدورة (تشمل "مكتملة" المشتقة من المقاعد).
 *
 * @param WC_Product|int $product الدورة.
 * @return string available|ended|full|postponed|cancelled
 */
function alzaherah_course_effective_status( $product ) {
	if ( function_exists( 'alz_core_course_effective_status' ) ) {
		return alz_core_course_effective_status( $product );
	}
	$product = is_numeric( $product ) ? wc_get_product( $product ) : $product;
	if ( ! $product ) {
		return 'available';
	}

	$manual = get_post_meta( $product->get_id(), '_alz_course_status', true );
	if ( in_array( $manual, array( 'postponed', 'cancelled', 'full' ), true ) ) {
		return $manual;
	}
	if ( alzaherah_course_registration_ended( $product ) ) {
		return 'ended';
	}
	$date = (string) get_post_meta( $product->get_id(), '_alz_course_date', true );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
		return 'undated';
	}
	$parts = array_map( 'absint', explode( '-', $date ) );
	if ( 3 !== count( $parts ) || ! checkdate( $parts[1], $parts[2], $parts[0] ) ) {
		return 'undated';
	}
	if ( ! $product->is_in_stock() ) {
		return 'full';
	}
	return 'available';
}

/**
 * تسمية الحالة الفعلية.
 *
 * @param string $status الحالة.
 * @return string
 */
function alzaherah_course_status_label( $status ) {
	$labels = ( function_exists( 'alzaherah_core_owns_courses' ) && alzaherah_core_owns_courses() )
		? ALZ_Courses::status_labels()
		: array(
			'available' => __( 'متاح للتسجيل', 'alzaherah' ),
			'ended'     => __( 'انتهى التسجيل', 'alzaherah' ),
			'full'      => __( 'اكتملت المقاعد', 'alzaherah' ),
			'postponed' => __( 'مؤجلة', 'alzaherah' ),
			'cancelled' => __( 'ملغاة', 'alzaherah' ),
			'undated'   => __( 'موعد البداية غير محدد', 'alzaherah' ),
		);
	return isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['available'];
}

/**
 * طباعة شارة الحالة.
 *
 * @param WC_Product|int $product الدورة.
 */
function alzaherah_course_status_badge( $product ) {
	$status = alzaherah_course_effective_status( $product );
	printf(
		'<span class="course-status-badge is-%1$s">%2$s</span>',
		esc_attr( $status ),
		esc_html( alzaherah_course_status_label( $status ) )
	);
}

/**
 * هل المنتج دورة مؤكدة؟ يفضّل Helper المنصة؛ وإلا نفس قواعد العلم/الإشارات دون status/sort.
 *
 * @param int $product_id رقم المنتج.
 * @return bool
 */
function alzaherah_is_confirmed_course_product( $product_id ) {
	$product_id = absint( $product_id );
	if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
		return false;
	}
	if ( function_exists( 'alz_core_is_course_product' ) ) {
		return alz_core_is_course_product( $product_id );
	}
	$flag = (string) get_post_meta( $product_id, '_alz_is_course', true );
	if ( 'yes' === $flag ) {
		return true;
	}
	if ( 'no' === $flag ) {
		return false;
	}
	$keys = array(
		'_alz_course_date',
		'_alz_course_mode',
		'_alz_course_start_time',
		'_alz_course_end_time',
		'_alz_course_days',
		'_alz_course_time',
		'_alz_course_field',
		'_alz_trainer',
		'_alz_training_hours',
		'_alz_accreditation_no',
		'_alz_certificate_type',
	);
	foreach ( $keys as $key ) {
		if ( '' !== trim( (string) get_post_meta( $product_id, $key, true ) ) ) {
			return true;
		}
	}
	return false;
}

/**
 * وسائط استعلام الدورات المؤكدة فقط. لا يُستخدم لمتجر المنتجات العام المستقبلي.
 *
 * @param array $args وسائط WP_Query.
 * @return array
 */
function alzaherah_course_query_args( $args = array() ) {
	if ( function_exists( 'alz_core_course_query_args' ) ) {
		return alz_core_course_query_args( $args );
	}
	$args              = (array) $args;
	$args['post_type'] = 'product';
	$existing          = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
	$course_clause     = array(
		'key'   => '_alz_is_course',
		'value' => 'yes',
	);
	if ( $existing ) {
		$relation = isset( $existing['relation'] ) ? $existing['relation'] : 'AND';
		unset( $existing['relation'] );
		$args['meta_query'] = array(
			'relation' => 'AND',
			$course_clause,
			array_merge( array( 'relation' => $relation ), $existing ),
		);
	} else {
		$args['meta_query'] = array( $course_clause );
	}
	return $args;
}

/**
 * تصنيفات مرتبطة بدورات مؤكدة فقط، دون تغيير روابط product_cat الحالية.
 *
 * @param array $args وسائط get_terms.
 * @return array<int,WP_Term>
 */
function alzaherah_course_category_terms( $args = array() ) {
	if ( function_exists( 'alz_core_course_category_terms' ) ) {
		return alz_core_course_category_terms( $args );
	}
	$args = wp_parse_args(
		$args,
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
		)
	);
	$existing = isset( $args['meta_query'] ) && is_array( $args['meta_query'] ) ? $args['meta_query'] : array();
	$clause   = array(
		'key'   => '_alz_is_course_category',
		'value' => 'yes',
	);
	if ( $existing ) {
		$relation = isset( $existing['relation'] ) ? $existing['relation'] : 'AND';
		unset( $existing['relation'] );
		$args['meta_query'] = array(
			'relation' => 'AND',
			$clause,
			array_merge( array( 'relation' => $relation ), $existing ),
		);
	} else {
		$args['meta_query'] = array( $clause );
	}
	$terms = get_terms( $args );
	return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * مفتاح ترتيب مخزن مسبقًا بدل تنفيذ استعلامات postmeta فرعية لكل صف.
 * يُحدَّث للدورات المؤكدة فقط حتى لا تُلوَّث منتجات WooCommerce العادية.
 *
 * @param int $product_id رقم المنتج.
 */
function alzaherah_update_course_sort_key( $product_id ) {
	if ( function_exists( 'alzaherah_core_owns_courses' ) && alzaherah_core_owns_courses() ) {
		ALZ_Courses::refresh_sort_key( $product_id );
		return;
	}
	static $updating = array();
	$product_id = absint( $product_id );
	if ( ! $product_id || isset( $updating[ $product_id ] ) || 'product' !== get_post_type( $product_id ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}
	if ( ! alzaherah_is_confirmed_course_product( $product_id ) ) {
		return;
	}
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return;
	}
	$updating[ $product_id ] = true;
	$date   = (string) get_post_meta( $product_id, '_alz_course_date', true );
	$date   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '9999-12-31';
	$manual = (string) get_post_meta( $product_id, '_alz_course_status', true );
	$bucket = in_array( $manual, array( 'postponed', 'cancelled', 'full' ), true ) || ! $product->is_in_stock() ? '1' : '0';
	update_post_meta( $product_id, '_alz_course_sort_key', $bucket . '|' . $date . '|' . sprintf( '%010d', $product_id ) );
	unset( $updating[ $product_id ] );
}

function alzaherah_update_course_sort_key_on_save( $post_id ) {
	if ( ! wp_is_post_revision( $post_id ) ) {
		alzaherah_update_course_sort_key( $post_id );
	}
}

function alzaherah_update_course_sort_key_on_meta( $meta_id, $post_id, $meta_key ) {
	unset( $meta_id );
	if ( in_array( $meta_key, array( '_alz_course_date', '_alz_course_status', '_stock_status', '_alz_is_course' ), true ) ) {
		alzaherah_update_course_sort_key( $post_id );
	}
}
if ( ! function_exists( 'alzaherah_core_owns_courses' ) || ! alzaherah_core_owns_courses() ) {
	add_action( 'save_post_product', 'alzaherah_update_course_sort_key_on_save', 30 );
	add_action( 'added_post_meta', 'alzaherah_update_course_sort_key_on_meta', 20, 3 );
	add_action( 'updated_post_meta', 'alzaherah_update_course_sort_key_on_meta', 20, 3 );
	add_action( 'deleted_post_meta', 'alzaherah_update_course_sort_key_on_meta', 20, 3 );
}

/**
 * تعبئة تدريجية version-gated لمفاتيح ترتيب الدورات المؤكدة فقط.
 * بعد اكتمال دفعات الإصدار الحالي لا يُنفَّذ استعلام ثقيل في كل admin_init.
 */
function alzaherah_backfill_course_sort_keys() {
	if ( ! is_admin() || ! current_user_can( 'edit_products' ) || ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	$version      = defined( 'ALZAHERAH_THEME_VERSION' ) ? (string) ALZAHERAH_THEME_VERSION : (string) wp_get_theme()->get( 'Version' );
	$done_option  = 'alz_theme_course_sort_backfill_done_version';
	$after_option = 'alz_theme_course_sort_backfill_after_id';
	$pending_opt  = 'alz_theme_course_sort_backfill_pending_version';

	// توافق مع مفتاح الإصدار السابق إن وُجد.
	$legacy_done = (string) get_option( 'alz_theme_course_sort_backfill_done', '' );
	if ( '' === (string) get_option( $done_option, '' ) && '' !== $legacy_done ) {
		update_option( $done_option, $legacy_done, false );
		delete_option( 'alz_theme_course_sort_backfill_done' );
	}

	if ( (string) get_option( $done_option, '' ) === $version ) {
		return;
	}

	if ( (string) get_option( $pending_opt, '' ) !== $version ) {
		update_option( $pending_opt, $version, false );
		delete_option( $after_option );
	}

	$after_id   = absint( get_option( $after_option, 0 ) );
	$limit      = 50;
	$meta_query = array(
		'relation' => 'AND',
		array(
			'key'     => '_alz_course_sort_key',
			'compare' => 'NOT EXISTS',
		),
		// استبعاد صريح لـ _alz_is_course=no.
		array(
			'relation' => 'OR',
			array(
				'key'     => '_alz_is_course',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_alz_is_course',
				'value'   => 'no',
				'compare' => '!=',
			),
		),
		array(
			'relation' => 'OR',
			array(
				'key'   => '_alz_is_course',
				'value' => 'yes',
			),
			array(
				'key'     => '_alz_course_date',
				'value'   => '',
				'compare' => '!=',
			),
		),
	);

	$where_filter = static function ( $where ) use ( $after_id ) {
		global $wpdb;
		if ( $after_id > 0 ) {
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.ID > %d", $after_id );
		}
		return $where;
	};
	add_filter( 'posts_where', $where_filter );
	$product_ids = get_posts(
		array(
			'post_type'              => 'product',
			'post_status'            => array( 'publish', 'draft', 'private' ),
			'posts_per_page'         => $limit,
			'fields'                 => 'ids',
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'meta_query'             => $meta_query,
		)
	);
	remove_filter( 'posts_where', $where_filter );

	if ( ! $product_ids ) {
		update_option( $done_option, $version, false );
		delete_option( $after_option );
		return;
	}

	foreach ( $product_ids as $product_id ) {
		$product_id = absint( $product_id );
		if ( $product_id && alzaherah_is_confirmed_course_product( $product_id ) ) {
			alzaherah_update_course_sort_key( $product_id );
		}
	}

	update_option( $after_option, (int) max( $product_ids ), false );

	if ( count( $product_ids ) < $limit ) {
		update_option( $done_option, $version, false );
		delete_option( $after_option );
	}
}
add_action( 'admin_init', 'alzaherah_backfill_course_sort_keys', 30 );

/**
 * تمييز استعلام متجر WooCommerce لتقديم الدورات المتاحة على غير المتاحة.
 *
 * @param WP_Query $query استعلام المنتجات.
 */
function alzaherah_prioritize_available_catalog_courses( $query ) {
	if ( $query instanceof WP_Query ) {
		$has_requested_order = isset( $_GET['orderby'] ) && '' !== sanitize_text_field( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$query->set( 'alz_available_courses_first', $has_requested_order ? 'priority' : 'upcoming' );
	}
}
add_action( 'woocommerce_product_query', 'alzaherah_prioritize_available_catalog_courses', 20 );

/**
 * كتالوج/بحث/تصنيف الدورات فقط: استبعاد منتجات WooCommerce العادية.
 *
 * Store prerequisite: future educational product catalog must opt out explicitly.
 * Set `$query->set( 'alz_skip_course_catalog_filter', true )` or an equivalent
 * context before this hook; otherwise a regular product archive will inherit
 * the course-only filter. Do not build that store in this round.
 *
 * @param WP_Query $query استعلام منتجات WooCommerce.
 */
function alzaherah_restrict_course_catalog_query( $query ) {
	if ( ! $query instanceof WP_Query || $query->get( 'alz_skip_course_catalog_filter' ) ) {
		return;
	}
	$meta_query = $query->get( 'meta_query' );
	$wrapped    = alzaherah_course_query_args(
		array(
			'meta_query' => is_array( $meta_query ) ? $meta_query : array(),
		)
	);
	$query->set( 'meta_query', $wrapped['meta_query'] );
}
add_action( 'woocommerce_product_query', 'alzaherah_restrict_course_catalog_query', 15 );

/**
 * ترتيب استعلامات الدورات بواسطة LEFT JOIN واحد إلى مفتاح الترتيب المخزن.
 *
 * @param array    $clauses أجزاء الاستعلام.
 * @param WP_Query $query استعلام WordPress.
 * @return array
 */
function alzaherah_available_courses_clauses( $clauses, $query ) {
	$sort_mode = $query->get( 'alz_available_courses_first' );
	if ( ! $sort_mode || false !== strpos( $clauses['join'], 'alz_course_sort_pm' ) ) {
		return $clauses;
	}

	global $wpdb;
	$today = esc_sql( current_time( 'Y-m-d' ) );
	$clauses['join'] .= $wpdb->prepare(
		" LEFT JOIN {$wpdb->postmeta} AS alz_course_sort_pm ON ({$wpdb->posts}.ID = alz_course_sort_pm.post_id AND alz_course_sort_pm.meta_key = %s)",
		'_alz_course_sort_key'
	);
	$priority = "/* alz_available_courses_priority */ CASE
		WHEN alz_course_sort_pm.meta_value IS NULL THEN 2
		WHEN LEFT(alz_course_sort_pm.meta_value, 1) = '1' THEN 1
		WHEN SUBSTRING(alz_course_sort_pm.meta_value, 3, 10) <> '9999-12-31'
			AND SUBSTRING(alz_course_sort_pm.meta_value, 3, 10) < '{$today}' THEN 1
		ELSE 0
	END ASC";
	if ( 'upcoming' === $sort_mode ) {
		$priority .= ', alz_course_sort_pm.meta_value ASC';
	}
	$clauses['orderby'] = $priority . ( ! empty( $clauses['orderby'] ) ? ', ' . $clauses['orderby'] : '' );
	return $clauses;
}
add_filter( 'posts_clauses', 'alzaherah_available_courses_clauses', 20, 2 );

/**
 * المقاعد المتبقية (null إذا كانت المقاعد غير محدودة).
 *
 * @param WC_Product $product الدورة.
 * @return int|null
 */
function alzaherah_seats_left( $product ) {
	if ( ! $product || ! $product->managing_stock() ) {
		return null;
	}
	$qty = $product->get_stock_quantity();
	return null === $qty ? null : max( 0, (int) $qty );
}

/**
 * منع شراء الدورات المؤجلة أو الملغاة.
 *
 * @param bool       $purchasable قابلية الشراء.
 * @param WC_Product $product الدورة.
 * @return bool
 */
function alzaherah_block_unavailable_courses( $purchasable, $product ) {
	if ( ! $product || ! is_a( $product, 'WC_Product' ) || ! alzaherah_is_confirmed_course_product( $product->get_id() ) ) {
		return $purchasable;
	}
	if ( 'available' !== alzaherah_course_effective_status( $product ) ) {
		return false;
	}
	return $purchasable;
}
// Plugin owns purchasability when Platform Core is active.
if ( ! function_exists( 'alz_core_block_unavailable_courses' ) ) {
	add_filter( 'woocommerce_is_purchasable', 'alzaherah_block_unavailable_courses', 10, 2 );
}

/**
 * منع الإضافة المباشرة إلى السلة بعد انتهاء التسجيل أو إغلاق الدورة.
 *
 * @param bool $passed نتيجة تحقق WooCommerce.
 * @param int  $product_id رقم الدورة.
 * @return bool
 */
function alzaherah_validate_course_add_to_cart( $passed, $product_id ) {
	$product = wc_get_product( $product_id );
	if ( $product && alzaherah_is_confirmed_course_product( $product->get_id() ) && 'available' !== alzaherah_course_effective_status( $product ) ) {
		wc_add_notice( alzaherah_course_registration_message( $product ), 'error' );
		return false;
	}

	return $passed;
}
if ( ! function_exists( 'alz_core_validate_course_add_to_cart' ) ) {
	add_filter( 'woocommerce_add_to_cart_validation', 'alzaherah_validate_course_add_to_cart', 10, 2 );
}

/**
 * رسالة موحدة لحالة التسجيل المغلق.
 *
 * @param WC_Product|int $product الدورة.
 * @return string
 */
function alzaherah_course_registration_message( $product ) {
	$status = alzaherah_course_effective_status( $product );

	if ( 'ended' === $status ) {
		return __( 'انتهى التسجيل في هذه الدورة بعد انتهاء يوم بدايتها. يمكنك الاطلاع على تفاصيلها أو اختيار دورة أخرى.', 'alzaherah' );
	}
	if ( 'full' === $status ) {
		return __( 'اكتملت مقاعد هذه الدورة ولم يعد التسجيل متاحًا.', 'alzaherah' );
	}
	if ( 'postponed' === $status ) {
		return __( 'هذه الدورة مؤجلة حاليًا ولا تستقبل تسجيلات جديدة.', 'alzaherah' );
	}
	if ( 'cancelled' === $status ) {
		return __( 'أُلغيت هذه الدورة ولا تستقبل تسجيلات جديدة.', 'alzaherah' );
	}
	if ( 'undated' === $status ) {
		return __( 'لا يمكن التسجيل في هذه الدورة لأن موعد البداية غير محدد.', 'alzaherah' );
	}

	return __( 'التسجيل غير متاح حاليًا.', 'alzaherah' );
}

/**
 * فحص السلة عند فتحها أو الانتقال للدفع لمنع إتمام طلب قديم بعد انتهاء الموعد.
 */
function alzaherah_validate_courses_in_cart() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : false;
		if ( $product && alzaherah_is_confirmed_course_product( $product->get_id() ) && 'available' !== alzaherah_course_effective_status( $product ) ) {
			wc_add_notice(
				sprintf(
					/* translators: 1: course title, 2: reason. */
					__( 'تعذر متابعة التسجيل في «%1$s»: %2$s', 'alzaherah' ),
					$product->get_name(),
					alzaherah_course_registration_message( $product )
				),
				'error'
			);
		}
	}
}
if ( ! function_exists( 'alz_core_validate_courses_in_cart' ) ) {
	add_action( 'woocommerce_check_cart_items', 'alzaherah_validate_courses_in_cart' );
}

/* ============================================================
   صندوق جانبي في شاشة تعديل الدورة: الحالة + شروط الالتحاق
============================================================ */

/**
 * تسجيل الصندوق — للدورات المؤكدة فقط حتى لا تُلوَّث منتجات WooCommerce العادية.
 */
function alzaherah_status_metabox() {
	global $post;
	$post_id = ( $post instanceof WP_Post ) ? (int) $post->ID : 0;
	if ( ! $post_id || ! alzaherah_is_confirmed_course_product( $post_id ) ) {
		return;
	}
	add_meta_box(
		'alzaherah_course_status',
		__( 'حالة الدورة وشروط الالتحاق', 'alzaherah' ),
		'alzaherah_status_metabox_render',
		'product',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'alzaherah_status_metabox' );

/**
 * عرض الصندوق.
 *
 * @param WP_Post $post الدورة.
 */
function alzaherah_status_metabox_render( $post ) {
	wp_nonce_field( 'alzaherah_course_status', 'alzaherah_status_nonce' );

	$status       = get_post_meta( $post->ID, '_alz_course_status', true );
	$requirements = get_post_meta( $post->ID, '_alz_requirements', true );
	?>
	<p>
		<label for="_alz_course_status"><strong><?php esc_html_e( 'حالة الدورة', 'alzaherah' ); ?></strong></label>
		<select id="_alz_course_status" name="_alz_course_status" style="width:100%">
			<?php foreach ( alzaherah_course_statuses() as $status_key => $status_label ) : ?>
				<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status ?: 'available', $status_key ); ?>>
					<?php echo esc_html( $status_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<span class="description"><?php esc_html_e( 'المؤجلة والملغاة تُغلق التسجيل تلقائيًا مع بقاء الدورة ظاهرة. «مكتملة» تُشتق تلقائيًا من نفاد المقاعد.', 'alzaherah' ); ?></span>
	</p>
	<p>
		<label for="_alz_requirements"><strong><?php esc_html_e( 'شروط ومتطلبات الالتحاق', 'alzaherah' ); ?></strong></label>
		<textarea id="_alz_requirements" name="_alz_requirements" rows="4" style="width:100%" placeholder="<?php esc_attr_e( 'كل شرط في سطر مستقل…', 'alzaherah' ); ?>"><?php echo esc_textarea( $requirements ); ?></textarea>
	</p>
	<?php
}

/**
 * حفظ الصندوق — فقط لمنتج مؤكد كدورة (بعد حفظ تفاصيل الدورة في نفس الطلب إن وُجدت).
 *
 * @param int $post_id معرف الدورة.
 */
function alzaherah_status_metabox_save( $post_id ) {
	if (
		! isset( $_POST['alzaherah_status_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['alzaherah_status_nonce'] ), 'alzaherah_course_status' ) ||
		( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
		! current_user_can( 'edit_post', $post_id )
	) {
		return;
	}
	if ( ! alzaherah_is_confirmed_course_product( $post_id ) ) {
		return;
	}

	$status = isset( $_POST['_alz_course_status'] ) ? sanitize_key( $_POST['_alz_course_status'] ) : 'available';
	if ( ! array_key_exists( $status, alzaherah_course_statuses() ) ) {
		$status = 'available';
	}
	update_post_meta( $post_id, '_alz_course_status', $status );

	$requirements = isset( $_POST['_alz_requirements'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_alz_requirements'] ) ) : '';
	update_post_meta( $post_id, '_alz_requirements', $requirements );
}
if ( ! function_exists( 'alzaherah_core_owns_courses' ) || ! alzaherah_core_owns_courses() ) {
	add_action( 'save_post_product', 'alzaherah_status_metabox_save', 25 );
}

/* ============================================================
   شريط العروض والكوبونات — إعدادات تخصيص المظهر
============================================================ */

/**
 * إعدادات الشريط الترويجي.
 *
 * @param WP_Customize_Manager $wp_customize كائن التخصيص.
 */
function alzaherah_promo_customizer( $wp_customize ) {

	$wp_customize->add_section(
		'alzaherah_promo',
		array(
			'title'    => __( 'شريط العروض والكوبونات', 'alzaherah' ),
			'priority' => 27,
		)
	);

	$wp_customize->add_setting(
		'alzaherah_promo_text',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'alzaherah_promo_text',
		array(
			'label'       => __( 'نص العرض', 'alzaherah' ),
			'description' => __( 'مثال: خصم ٢٠٪ على دورات الذكاء الاصطناعي حتى نهاية الشهر. اتركه فارغًا لإخفاء الشريط.', 'alzaherah' ),
			'section'     => 'alzaherah_promo',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'alzaherah_promo_code',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
	$wp_customize->add_control(
		'alzaherah_promo_code',
		array(
			'label'       => __( 'رمز الكوبون (اختياري)', 'alzaherah' ),
			'description' => __( 'أنشئ الكوبون من: التسويق ← الكوبونات، ثم ضع رمزه هنا ليظهر للزوار.', 'alzaherah' ),
			'section'     => 'alzaherah_promo',
			'type'        => 'text',
		)
	);
}
add_action( 'customize_register', 'alzaherah_promo_customizer' );

/**
 * طباعة الشريط الترويجي (يُستدعى من القوالب).
 */
function alzaherah_promo_strip() {
	$text = get_theme_mod( 'alzaherah_promo_text', '' );
	if ( '' === $text ) {
		return;
	}
	$code = get_theme_mod( 'alzaherah_promo_code', '' );
	?>
	<div class="promo-strip" role="note">
		<div class="container promo-strip-inner">
			<span class="promo-strip-icon" aria-hidden="true">🎁</span>
			<p><?php echo esc_html( $text ); ?></p>
			<?php if ( $code ) : ?>
				<span class="promo-strip-code" title="<?php esc_attr_e( 'استخدم الرمز عند الدفع', 'alzaherah' ); ?>"><?php echo esc_html( $code ); ?></span>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
