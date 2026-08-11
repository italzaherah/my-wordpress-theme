<?php
/**
 * تفاصيل الدورة + شاشة "إضافة دورة" المبسطة.
 *
 * ١) صندوق "تفاصيل الدورة" في شاشة تعديل الدورة:
 *    التاريخ، الوقت، نمط التقديم، المدرب، المكان.
 * ٢) شاشة "إضافة دورة" تحت قائمة "إدارة المنصة":
 *    نموذج واحد ينشئ الدورة كاملة (سعر، مقاعد، صورة، تفاصيل).
 *
 * التركيب: ضع الملف في /inc ثم أضف في نهاية functions.php:
 * require_once get_template_directory() . '/inc/course-fields.php';
 *
 * @package Alzaherah
 * @since   2.4.0
 */

defined( 'ABSPATH' ) || exit;

function alzaherah_core_owns_courses() {
	return class_exists( 'ALZ_Courses' )
		&& method_exists( 'ALZ_Courses', 'save_details' )
		&& defined( 'ALZ_CORE_VERSION' )
		&& version_compare( ALZ_CORE_VERSION, '2.6.0', '>=' );
}

/**
 * حقول تفاصيل الدورة المشتركة (المفتاح => التسمية).
 *
 * @return array
 */
function alzaherah_course_fields() {
	if ( alzaherah_core_owns_courses() ) {
		return ALZ_Courses::detail_fields();
	}
	return array(
		'_alz_course_date'       => __( 'تاريخ البدء', 'alzaherah' ),
		'_alz_course_start_time' => __( 'وقت البداية', 'alzaherah' ),
		'_alz_course_end_time'   => __( 'وقت النهاية', 'alzaherah' ),
		'_alz_course_days'       => __( 'عدد الأيام', 'alzaherah' ),
		'_alz_course_mode'       => __( 'نمط التقديم', 'alzaherah' ),
		'_alz_trainer'           => __( 'اسم المدرب', 'alzaherah' ),
		'_alz_location'          => __( 'المكان / المنصة', 'alzaherah' ),
		'_alz_accreditation_no'  => __( 'رقم اعتماد الدورة', 'alzaherah' ),
		'_alz_accreditation_by'  => __( 'جهة اعتماد الدورة', 'alzaherah' ),
		'_alz_course_field'      => __( 'مجال الدورة', 'alzaherah' ),
		'_alz_training_hours'    => __( 'عدد الساعات التدريبية', 'alzaherah' ),
		'_alz_trainer_title'     => __( 'صفة / مؤهل المدرب', 'alzaherah' ),
		'_alz_certificate_type'  => __( 'نوع الشهادة', 'alzaherah' ),
		'_alz_certificate_by'    => __( 'الجهة المصدرة للشهادة', 'alzaherah' ),
	);
}

function alzaherah_valid_course_time( $value ) {
	if ( alzaherah_core_owns_courses() ) {
		return ALZ_Courses::is_valid_time( $value );
	}
	return is_string( $value ) && 1 === preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value );
}

/**
 * توحيد الوقت القادم من حقول المتصفح إلى HH:MM.
 *
 * بعض واجهات الوقت المحلية تعيد القيمة المعروضة بصيغة 12 ساعة
 * (01:00 PM) بدل صيغة HTML القياسية (13:00).
 */
function alzaherah_normalize_course_time( $value ) {
	if ( alzaherah_core_owns_courses() ) {
		return ALZ_Courses::normalize_time( $value );
	}
	if ( ! is_string( $value ) ) {
		return '';
	}

	$value = trim(
		strtr(
			$value,
			array(
				'٠' => '0',
				'١' => '1',
				'٢' => '2',
				'٣' => '3',
				'٤' => '4',
				'٥' => '5',
				'٦' => '6',
				'٧' => '7',
				'٨' => '8',
				'٩' => '9',
				'۰' => '0',
				'۱' => '1',
				'۲' => '2',
				'۳' => '3',
				'۴' => '4',
				'۵' => '5',
				'۶' => '6',
				'۷' => '7',
				'۸' => '8',
				'۹' => '9',
			)
		)
	);
	$value = preg_replace( '/[\x{00A0}\x{202F}]+/u', ' ', $value );

	if ( preg_match( '/^(\d{1,2}):([0-5]\d)$/', $value, $parts ) ) {
		$hour = (int) $parts[1];
		return $hour <= 23 ? sprintf( '%02d:%02d', $hour, (int) $parts[2] ) : '';
	}

	if ( ! preg_match( '/^(\d{1,2}):([0-5]\d)\s*(a\.?\s*m\.?|p\.?\s*m\.?|ص|م|صباح(?:اً|ا)?|مساء(?:ً|ا)?)$/iu', $value, $parts ) ) {
		return '';
	}

	$hour   = (int) $parts[1];
	$minute = (int) $parts[2];
	if ( $hour < 1 || $hour > 12 ) {
		return '';
	}

	$period = strtolower( preg_replace( '/[\s.]+/u', '', $parts[3] ) );
	$is_pm  = 'pm' === $period || 'م' === $period || 0 === strpos( $period, 'مساء' );
	if ( $is_pm && $hour < 12 ) {
		$hour += 12;
	} elseif ( ! $is_pm && 12 === $hour ) {
		$hour = 0;
	}

	return sprintf( '%02d:%02d', $hour, $minute );
}

function alzaherah_course_time_parts( $post_id ) {
	if ( alzaherah_core_owns_courses() ) {
		return ALZ_Courses::time_parts( $post_id );
	}
	$start = (string) get_post_meta( $post_id, '_alz_course_start_time', true );
	$end   = (string) get_post_meta( $post_id, '_alz_course_end_time', true );
	$start = alzaherah_normalize_course_time( $start );
	$end   = alzaherah_normalize_course_time( $end );
	if ( alzaherah_valid_course_time( $start ) && alzaherah_valid_course_time( $end ) ) {
		return array( $start, $end );
	}
	$legacy = (string) get_post_meta( $post_id, '_alz_course_time', true );
	if ( ! preg_match_all( '/(\d{1,2}):(\d{2})/u', $legacy, $matches, PREG_SET_ORDER ) || count( $matches ) < 2 ) {
		return array( '', '' );
	}
	$is_pm = false !== strpos( $legacy, 'مساء' );
	$is_am = false !== strpos( $legacy, 'صباح' );
	$times = array();
	foreach ( array_slice( $matches, 0, 2 ) as $match ) {
		$hour = (int) $match[1];
		if ( $is_pm && $hour < 12 ) {
			$hour += 12;
		} elseif ( $is_am && 12 === $hour ) {
			$hour = 0;
		}
		$times[] = sprintf( '%02d:%02d', $hour, (int) $match[2] );
	}
	return alzaherah_valid_course_time( $times[0] ) && alzaherah_valid_course_time( $times[1] ) ? $times : array( '', '' );
}

/**
 * خيارات نمط التقديم.
 *
 * @return array
 */
function alzaherah_course_modes() {
	if ( alzaherah_core_owns_courses() ) {
		return ALZ_Courses::modes();
	}
	return array(
		'onsite' => __( 'حضوري', 'alzaherah' ),
		'online' => __( 'عن بُعد (مباشر)', 'alzaherah' ),
		'hybrid' => __( 'مدمج (حضوري + عن بُعد)', 'alzaherah' ),
	);
}

/* ============================================================
   ١) صندوق "تفاصيل الدورة" في شاشة تعديل الدورة
============================================================ */

/**
 * تسجيل الصندوق.
 */
function alzaherah_course_metabox() {
	global $post;
	$post_id = ( $post instanceof WP_Post ) ? (int) $post->ID : 0;
	if ( ! $post_id || ! function_exists( 'alzaherah_is_confirmed_course_product' ) || ! alzaherah_is_confirmed_course_product( $post_id ) ) {
		return;
	}
	add_meta_box(
		'alzaherah_course_details',
		__( 'تفاصيل الدورة', 'alzaherah' ),
		'alzaherah_course_metabox_render',
		'product',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'alzaherah_course_metabox' );

/**
 * عرض حقول الصندوق.
 *
 * @param WP_Post $post الدورة الحالية.
 */
function alzaherah_course_metabox_render( $post ) {

	wp_nonce_field( 'alzaherah_course_details', 'alzaherah_course_nonce' );

	$values = array();
	foreach ( array_keys( alzaherah_course_fields() ) as $key ) {
		$values[ $key ] = get_post_meta( $post->ID, $key, true );
	}
	$modes = alzaherah_course_modes();
	list( $start_time, $end_time ) = alzaherah_course_time_parts( $post->ID );
	?>
	<style>
		.alz-course-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:14px 16px; }
		.alz-course-grid p { margin:0; }
		.alz-course-grid label { display:block; margin-bottom:5px; font-weight:600; }
		.alz-course-grid input, .alz-course-grid select { width:100%; }
		@media (max-width:900px){ .alz-course-grid { grid-template-columns:1fr; } }
	</style>

	<div class="alz-course-grid">

		<p>
			<label for="_alz_course_date"><?php esc_html_e( 'تاريخ البدء', 'alzaherah' ); ?></label>
			<input type="date" id="_alz_course_date" name="_alz_course_date" value="<?php echo esc_attr( $values['_alz_course_date'] ); ?>">
		</p>

		<p><label for="_alz_course_start_time"><?php esc_html_e( 'وقت البداية', 'alzaherah' ); ?></label><input type="time" step="60" id="_alz_course_start_time" name="_alz_course_start_time" value="<?php echo esc_attr( $start_time ); ?>"></p>

		<p><label for="_alz_course_end_time"><?php esc_html_e( 'وقت النهاية', 'alzaherah' ); ?></label><input type="time" step="60" id="_alz_course_end_time" name="_alz_course_end_time" value="<?php echo esc_attr( $end_time ); ?>"></p>

		<p>
			<label for="_alz_course_days"><?php esc_html_e( 'عدد الأيام', 'alzaherah' ); ?></label>
			<input type="number" min="1" id="_alz_course_days" name="_alz_course_days" value="<?php echo esc_attr( $values['_alz_course_days'] ); ?>">
		</p>

		<p>
			<label for="_alz_course_mode"><?php esc_html_e( 'نمط التقديم', 'alzaherah' ); ?></label>
			<select id="_alz_course_mode" name="_alz_course_mode">
				<option value=""><?php esc_html_e( '— اختر —', 'alzaherah' ); ?></option>
				<?php foreach ( $modes as $mode_key => $mode_label ) : ?>
					<option value="<?php echo esc_attr( $mode_key ); ?>" <?php selected( $values['_alz_course_mode'], $mode_key ); ?>>
						<?php echo esc_html( $mode_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<label for="_alz_trainer"><?php esc_html_e( 'اسم المدرب', 'alzaherah' ); ?></label>
			<input type="text" id="_alz_trainer" name="_alz_trainer" value="<?php echo esc_attr( $values['_alz_trainer'] ); ?>">
		</p>

		<p>
			<label for="_alz_location"><?php esc_html_e( 'المكان / المنصة', 'alzaherah' ); ?></label>
			<input type="text" id="_alz_location" name="_alz_location" value="<?php echo esc_attr( $values['_alz_location'] ); ?>" placeholder="<?php esc_attr_e( 'مثال: قاعة المركز — الباحة، أو Zoom', 'alzaherah' ); ?>">
		</p>

		<p><label for="_alz_accreditation_no"><?php esc_html_e( 'رقم اعتماد الدورة', 'alzaherah' ); ?></label><input type="text" id="_alz_accreditation_no" name="_alz_accreditation_no" value="<?php echo esc_attr( $values['_alz_accreditation_no'] ); ?>" placeholder="<?php esc_attr_e( 'لا يترك فارغًا عند وصف الدورة بأنها معتمدة', 'alzaherah' ); ?>"></p>
		<p><label for="_alz_accreditation_by"><?php esc_html_e( 'جهة اعتماد الدورة', 'alzaherah' ); ?></label><input type="text" id="_alz_accreditation_by" name="_alz_accreditation_by" value="<?php echo esc_attr( $values['_alz_accreditation_by'] ); ?>"></p>
		<p><label for="_alz_course_field"><?php esc_html_e( 'مجال الدورة', 'alzaherah' ); ?></label><input type="text" id="_alz_course_field" name="_alz_course_field" value="<?php echo esc_attr( $values['_alz_course_field'] ); ?>"></p>
		<p><label for="_alz_training_hours"><?php esc_html_e( 'عدد الساعات التدريبية', 'alzaherah' ); ?></label><input type="number" min="1" id="_alz_training_hours" name="_alz_training_hours" value="<?php echo esc_attr( $values['_alz_training_hours'] ); ?>"></p>
		<p><label for="_alz_trainer_title"><?php esc_html_e( 'صفة / مؤهل المدرب', 'alzaherah' ); ?></label><input type="text" id="_alz_trainer_title" name="_alz_trainer_title" value="<?php echo esc_attr( $values['_alz_trainer_title'] ); ?>"></p>
		<p><label for="_alz_certificate_type"><?php esc_html_e( 'نوع الشهادة', 'alzaherah' ); ?></label><input type="text" id="_alz_certificate_type" name="_alz_certificate_type" value="<?php echo esc_attr( $values['_alz_certificate_type'] ); ?>"></p>
		<p><label for="_alz_certificate_by"><?php esc_html_e( 'الجهة المصدرة للشهادة', 'alzaherah' ); ?></label><input type="text" id="_alz_certificate_by" name="_alz_certificate_by" value="<?php echo esc_attr( $values['_alz_certificate_by'] ); ?>"></p>

	</div>
	<?php
}

/**
 * حفظ الحقول.
 *
 * @param int $post_id معرف الدورة.
 */
function alzaherah_course_metabox_save( $post_id ) {
	if (
		! isset( $_POST['alzaherah_course_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['alzaherah_course_nonce'] ), 'alzaherah_course_details' )
	) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! function_exists( 'alzaherah_is_confirmed_course_product' ) || ! alzaherah_is_confirmed_course_product( $post_id ) ) {
		return;
	}

	$modes = array_keys( alzaherah_course_modes() );

	foreach ( array_keys( alzaherah_course_fields() ) as $key ) {
		if ( in_array( $key, array( '_alz_course_start_time', '_alz_course_end_time' ), true ) ) {
			continue;
		}
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );

		if ( '_alz_course_mode' === $key && '' !== $value && ! in_array( $value, $modes, true ) ) {
			$value = '';
		}
		if ( '_alz_course_days' === $key && '' !== $value ) {
			$value = (string) max( 1, absint( $value ) );
		}
		if ( '_alz_training_hours' === $key && '' !== $value ) {
			$value = (string) max( 1, absint( $value ) );
		}

		update_post_meta( $post_id, $key, $value );
	}
	$start_time_raw = isset( $_POST['_alz_course_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['_alz_course_start_time'] ) ) : '';
	$end_time_raw   = isset( $_POST['_alz_course_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['_alz_course_end_time'] ) ) : '';
	$start_time     = alzaherah_normalize_course_time( $start_time_raw );
	$end_time       = alzaherah_normalize_course_time( $end_time_raw );
	if ( '' === trim( $start_time_raw ) && '' === trim( $end_time_raw ) ) {
		update_post_meta( $post_id, '_alz_course_start_time', '' );
		update_post_meta( $post_id, '_alz_course_end_time', '' );
		update_post_meta( $post_id, '_alz_course_time', '' );
	} elseif ( alzaherah_valid_course_time( $start_time ) && alzaherah_valid_course_time( $end_time ) && $end_time > $start_time ) {
		update_post_meta( $post_id, '_alz_course_start_time', $start_time );
		update_post_meta( $post_id, '_alz_course_end_time', $end_time );
		update_post_meta( $post_id, '_alz_course_time', $start_time . ' – ' . $end_time );
	}
	$flag = (string) get_post_meta( $post_id, '_alz_is_course', true );
	if ( '' === $flag && function_exists( 'alz_core_product_has_course_signals' ) && function_exists( 'alz_core_mark_product_as_course' ) && alz_core_product_has_course_signals( $post_id ) ) {
		alz_core_mark_product_as_course( $post_id );
	}
}
if ( ! alzaherah_core_owns_courses() ) {
	add_action( 'save_post_product', 'alzaherah_course_metabox_save' );
}

/* ============================================================
   ٢) شاشة "إضافة دورة" المبسطة
============================================================ */

/**
 * تسجيل الشاشة تحت قائمة "إدارة المنصة".
 */
function alzaherah_add_course_menu() {
	add_submenu_page(
		'alzaherah-platform',
		__( 'إضافة دورة', 'alzaherah' ),
		__( 'إضافة دورة', 'alzaherah' ),
		'edit_products',
		'alzaherah-add-course',
		'alzaherah_add_course_screen'
	);
}
if ( ! alzaherah_core_owns_courses() ) {
	add_action( 'admin_menu', 'alzaherah_add_course_menu', 20 );
}

/**
 * شاشة النموذج.
 */
function alzaherah_add_course_screen() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'إضافة دورة', 'alzaherah' ) . '</h1><p>' . esc_html__( 'فعّل WooCommerce أولًا.', 'alzaherah' ) . '</p></div>';
		return;
	}

	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		)
	);
	$modes = alzaherah_course_modes();

	// رسائل النتيجة بعد الإرسال.
	$msg       = isset( $_GET['alz_msg'] ) ? sanitize_key( $_GET['alz_msg'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$new_id    = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>

	<div class="wrap">
		<h1><?php esc_html_e( 'إضافة دورة جديدة', 'alzaherah' ); ?></h1>
		<p style="color:#646970"><?php esc_html_e( 'نموذج واحد يجهّز الدورة كاملة: التفاصيل، السعر، المقاعد، والصورة — وتُنشر مباشرة في صفحة الدورات.', 'alzaherah' ); ?></p>

		<?php if ( 'ok' === $msg && $new_id ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php esc_html_e( 'تم إنشاء الدورة ونشرها بنجاح.', 'alzaherah' ); ?>
					<a href="<?php echo esc_url( get_permalink( $new_id ) ); ?>" target="_blank"><?php esc_html_e( 'عرض الدورة', 'alzaherah' ); ?></a>
					·
					<a href="<?php echo esc_url( get_edit_post_link( $new_id ) ); ?>"><?php esc_html_e( 'تعديلها', 'alzaherah' ); ?></a>
				</p>
			</div>
		<?php elseif ( 'error' === $msg ) : ?>
			<div class="notice notice-error is-dismissible">
				<p><?php esc_html_e( 'تعذر إنشاء الدورة — تأكد من تعبئة الاسم والسعر بشكل صحيح.', 'alzaherah' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" style="max-width:920px">
			<?php wp_nonce_field( 'alzaherah_add_course', 'alzaherah_add_course_nonce' ); ?>
			<input type="hidden" name="action" value="alzaherah_add_course">

			<table class="form-table" role="presentation">

				<tr>
					<th scope="row"><label for="course_title"><?php esc_html_e( 'اسم الدورة *', 'alzaherah' ); ?></label></th>
					<td><input class="regular-text" style="width:100%" type="text" id="course_title" name="course_title" required></td>
				</tr>

				<tr>
					<th scope="row"><label for="course_excerpt"><?php esc_html_e( 'وصف مختصر', 'alzaherah' ); ?></label></th>
					<td>
						<textarea style="width:100%" rows="2" id="course_excerpt" name="course_excerpt" placeholder="<?php esc_attr_e( 'سطر أو سطران يظهران في بطاقة الدورة.', 'alzaherah' ); ?>"></textarea>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="course_description"><?php esc_html_e( 'الوصف الكامل والمحاور', 'alzaherah' ); ?></label></th>
					<td><textarea style="width:100%" rows="7" id="course_description" name="course_description"></textarea></td>
				</tr>

				<tr>
					<th scope="row"><label for="course_price"><?php esc_html_e( 'السعر (ر.س) *', 'alzaherah' ); ?></label></th>
					<td>
						<input type="number" step="0.01" min="0" id="course_price" name="course_price" required>
						&nbsp;&nbsp;
						<label for="course_sale_price"><?php esc_html_e( 'سعر مخفض (اختياري):', 'alzaherah' ); ?></label>
						<input type="number" step="0.01" min="0" id="course_sale_price" name="course_sale_price">
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="course_seats"><?php esc_html_e( 'عدد المقاعد', 'alzaherah' ); ?></label></th>
					<td>
						<input type="number" min="1" id="course_seats" name="course_seats" placeholder="20">
						<p class="description"><?php esc_html_e( 'يتناقص تلقائيًا مع كل تسجيل، ويتوقف التسجيل عند اكتماله. اتركه فارغًا لمقاعد غير محدودة.', 'alzaherah' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="course_date"><?php esc_html_e( 'تاريخ البدء', 'alzaherah' ); ?></label></th>
					<td>
						<input type="date" id="course_date" name="course_date">
						&nbsp;&nbsp;
						<label for="course_start_time"><?php esc_html_e( 'من:', 'alzaherah' ); ?></label>
						<input type="time" step="60" id="course_start_time" name="course_start_time">
						&nbsp;&nbsp;
						<label for="course_end_time"><?php esc_html_e( 'إلى:', 'alzaherah' ); ?></label>
						<input type="time" step="60" id="course_end_time" name="course_end_time">
						&nbsp;&nbsp;
						<label for="course_days"><?php esc_html_e( 'عدد الأيام:', 'alzaherah' ); ?></label>
						<input type="number" min="1" id="course_days" name="course_days" style="width:70px">
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="course_mode"><?php esc_html_e( 'نمط التقديم', 'alzaherah' ); ?></label></th>
					<td>
						<select id="course_mode" name="course_mode">
							<option value=""><?php esc_html_e( '— اختر —', 'alzaherah' ); ?></option>
							<?php foreach ( $modes as $mode_key => $mode_label ) : ?>
								<option value="<?php echo esc_attr( $mode_key ); ?>"><?php echo esc_html( $mode_label ); ?></option>
							<?php endforeach; ?>
						</select>
						&nbsp;&nbsp;
						<label for="course_location"><?php esc_html_e( 'المكان / المنصة:', 'alzaherah' ); ?></label>
						<input class="regular-text" type="text" id="course_location" name="course_location">
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="course_trainer"><?php esc_html_e( 'اسم المدرب', 'alzaherah' ); ?></label></th>
					<td><input class="regular-text" type="text" id="course_trainer" name="course_trainer"></td>
				</tr>

				<tr>
					<th scope="row"><label for="course_category"><?php esc_html_e( 'المجال (التصنيف)', 'alzaherah' ); ?></label></th>
					<td>
						<select id="course_category" name="course_category">
							<option value=""><?php esc_html_e( '— بدون —', 'alzaherah' ); ?></option>
							<?php if ( ! is_wp_error( $categories ) ) : ?>
								<?php foreach ( $categories as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
						&nbsp;&nbsp;
						<label for="course_new_category"><?php esc_html_e( 'أو أنشئ مجالًا جديدًا:', 'alzaherah' ); ?></label>
						<input type="text" id="course_new_category" name="course_new_category" placeholder="<?php esc_attr_e( 'مثال: الذكاء الاصطناعي', 'alzaherah' ); ?>">
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="course_image"><?php esc_html_e( 'صورة الدورة', 'alzaherah' ); ?></label></th>
					<td>
						<input type="file" id="course_image" name="course_image" accept="image/*">
						<p class="description"><?php esc_html_e( 'المقاس المقترح: 1200×800 بكسل.', 'alzaherah' ); ?></p>
					</td>
				</tr>

			</table>

			<?php submit_button( __( 'إنشاء الدورة ونشرها', 'alzaherah' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * معالجة إنشاء الدورة.
 */
function alzaherah_handle_add_course() {
	if (
		! isset( $_POST['alzaherah_add_course_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['alzaherah_add_course_nonce'] ), 'alzaherah_add_course' ) ||
		! current_user_can( 'edit_products' ) ||
		! current_user_can( 'publish_products' ) ||
		! class_exists( 'WooCommerce' )
	) {
		wp_die( esc_html__( 'غير مصرح.', 'alzaherah' ) );
	}

	$redirect = admin_url( 'admin.php?page=alzaherah-add-course' );

	$title = isset( $_POST['course_title'] ) ? sanitize_text_field( wp_unslash( $_POST['course_title'] ) ) : '';
	$price = isset( $_POST['course_price'] ) ? wc_format_decimal( wp_unslash( $_POST['course_price'] ) ) : '';

	if ( '' === $title || '' === $price || (float) $price < 0 ) {
		wp_safe_redirect( add_query_arg( 'alz_msg', 'error', $redirect ) );
		exit;
	}

	$excerpt     = isset( $_POST['course_excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['course_excerpt'] ) ) : '';
	$description = isset( $_POST['course_description'] ) ? wp_kses_post( wp_unslash( $_POST['course_description'] ) ) : '';
	$sale_raw    = isset( $_POST['course_sale_price'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['course_sale_price'] ) ) ) : '';
	$sale_price  = '' !== $sale_raw ? wc_format_decimal( $sale_raw ) : '';
	$seats_raw   = isset( $_POST['course_seats'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['course_seats'] ) ) ) : '';
	$days_raw    = isset( $_POST['course_days'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['course_days'] ) ) ) : '';
	$course_date = isset( $_POST['course_date'] ) ? sanitize_text_field( wp_unslash( $_POST['course_date'] ) ) : '';
	$modes       = array_keys( alzaherah_course_modes() );
	$course_mode = isset( $_POST['course_mode'] ) ? sanitize_key( wp_unslash( $_POST['course_mode'] ) ) : '';
	$start_time_raw = isset( $_POST['course_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['course_start_time'] ) ) : '';
	$end_time_raw   = isset( $_POST['course_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['course_end_time'] ) ) : '';
	$start_time     = alzaherah_normalize_course_time( $start_time_raw );
	$end_time       = alzaherah_normalize_course_time( $end_time_raw );
	if ( ( '' !== $sale_price && ( (float) $sale_price < 0 || (float) $sale_price > (float) $price ) ) || ( '' !== $seats_raw && ! preg_match( '/^\d+$/', $seats_raw ) ) || ( '' !== $days_raw && ( ! preg_match( '/^\d+$/', $days_raw ) || absint( $days_raw ) < 1 ) ) || ( '' !== $course_mode && ! in_array( $course_mode, $modes, true ) ) ) {
		wp_safe_redirect( add_query_arg( 'alz_msg', 'error', $redirect ) );
		exit;
	}
	if ( '' !== $course_date ) {
		$date_parts = array_map( 'absint', explode( '-', $course_date ) );
		if ( 3 !== count( $date_parts ) || ! checkdate( $date_parts[1], $date_parts[2], $date_parts[0] ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $course_date ) ) {
			wp_safe_redirect( add_query_arg( 'alz_msg', 'error', $redirect ) );
			exit;
		}
	}
	if ( ( '' !== trim( $start_time_raw ) || '' !== trim( $end_time_raw ) ) && ( ! alzaherah_valid_course_time( $start_time ) || ! alzaherah_valid_course_time( $end_time ) || $end_time <= $start_time ) ) {
		wp_safe_redirect( add_query_arg( 'alz_msg', 'error', $redirect ) );
		exit;
	}

	// التحقق من التصنيف وإنشاؤه قبل نشر المنتج حتى لا تبقى دورة منشورة جزئيًا عند فشل التصنيف.
	$new_cat_name = isset( $_POST['course_new_category'] ) ? sanitize_text_field( wp_unslash( $_POST['course_new_category'] ) ) : '';
	$cat_id       = isset( $_POST['course_category'] ) ? absint( $_POST['course_category'] ) : 0;
	if ( $cat_id && ! term_exists( $cat_id, 'product_cat' ) ) {
		wp_safe_redirect( add_query_arg( 'alz_msg', 'error', $redirect ) );
		exit;
	}
	if ( '' !== $new_cat_name ) {
		if ( ! current_user_can( 'alz_manage_course_categories' ) ) {
			wp_safe_redirect( add_query_arg( 'alz_msg', 'error', $redirect ) );
			exit;
		}
		$existing_term = term_exists( $new_cat_name, 'product_cat' );
		$new_term      = $existing_term ?: wp_insert_term( $new_cat_name, 'product_cat' );
		if ( is_wp_error( $new_term ) ) {
			wp_safe_redirect( add_query_arg( 'alz_msg', 'error', $redirect ) );
			exit;
		}
		$cat_id = (int) ( is_array( $new_term ) ? $new_term['term_id'] : $new_term );
	}

	// إنشاء الدورة كمنتج افتراضي (لا شحن) يُباع بمقعد واحد لكل طلب.
	$product = new WC_Product_Simple();
	$product->set_name( $title );
	$product->set_status( 'publish' );
	$product->set_short_description( $excerpt );
	$product->set_description( $description );
	$product->set_regular_price( $price );
	if ( '' !== $sale_price ) {
		$product->set_sale_price( $sale_price );
	}
	$product->set_virtual( true );
	$product->set_sold_individually( true );

	if ( '' !== $seats_raw ) {
		$seats = absint( $seats_raw );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( $seats );
		$product->set_stock_status( $seats > 0 ? 'instock' : 'outofstock' );
	}

	$product_id = $product->save();

	if ( ! $product_id ) {
		wp_safe_redirect( add_query_arg( 'alz_msg', 'error', $redirect ) );
		exit;
	}

	if ( $cat_id ) {
		$term_result = wp_set_object_terms( $product_id, array( $cat_id ), 'product_cat' );
		if ( is_wp_error( $term_result ) ) {
			$product->set_status( 'draft' );
			$product->save();
			wp_safe_redirect( add_query_arg( 'alz_msg', 'error', $redirect ) );
			exit;
		}
	}

	// تفاصيل الدورة.
	$details = array(
		'_alz_course_date'       => $course_date,
		'_alz_course_start_time' => $start_time,
		'_alz_course_end_time'   => $end_time,
		'_alz_course_time'       => $start_time && $end_time ? $start_time . ' – ' . $end_time : '',
		'_alz_course_days'       => '' !== $days_raw ? (string) absint( $days_raw ) : '',
		'_alz_course_mode'       => $course_mode,
		'_alz_trainer'           => isset( $_POST['course_trainer'] ) ? sanitize_text_field( wp_unslash( $_POST['course_trainer'] ) ) : '',
		'_alz_location'          => isset( $_POST['course_location'] ) ? sanitize_text_field( wp_unslash( $_POST['course_location'] ) ) : '',
	);
	foreach ( $details as $meta_key => $meta_value ) {
		if ( '' !== $meta_value ) {
			update_post_meta( $product_id, $meta_key, $meta_value );
		}
	}
	if ( function_exists( 'alz_core_mark_product_as_course' ) ) {
		alz_core_mark_product_as_course( $product_id );
	}

	// الصورة البارزة.
	if ( ! empty( $_FILES['course_image']['name'] ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$file    = $_FILES['course_image']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated below.
		$allowed = array( 'image/jpeg', 'image/png', 'image/webp' );
		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		$image   = @getimagesize( $file['tmp_name'] );
		if (
			empty( $file['error'] )
			&& (int) $file['size'] > 0
			&& (int) $file['size'] <= 5 * MB_IN_BYTES
			&& ! empty( $checked['type'] )
			&& in_array( $checked['type'], $allowed, true )
			&& is_array( $image )
			&& ! empty( $image['mime'] )
			&& in_array( $image['mime'], $allowed, true )
		) {
			$attachment_id = media_handle_upload( 'course_image', $product_id );
			if ( ! is_wp_error( $attachment_id ) ) {
				set_post_thumbnail( $product_id, $attachment_id );
			}
		}
	}
	if ( function_exists( 'alz_purge_course_cache' ) ) {
		alz_purge_course_cache( $product_id );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'alz_msg'   => 'ok',
				'course_id' => $product_id,
			),
			$redirect
		)
	);
	exit;
}
if ( ! alzaherah_core_owns_courses() ) {
	add_action( 'admin_post_alzaherah_add_course', 'alzaherah_handle_add_course' );
}
