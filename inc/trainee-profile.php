<?php
/**
 * ملف المتدرب: عرض الحقول في الحساب ولوحة الإدارة.
 * منطق الحفظ والتحقق في Core عند توفره.
 *
 * @package Alzaherah
 * @since   3.3.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * هل Core يملك حفظ ملف المتدرب؟
 *
 * @return bool
 */
function alzaherah_core_owns_trainee_profile() {
	return class_exists( 'ALZ_Trainee_Profile' )
		&& method_exists( 'ALZ_Trainee_Profile', 'save_account_fields' )
		&& defined( 'ALZ_CORE_VERSION' )
		&& version_compare( ALZ_CORE_VERSION, '2.6.6', '>=' );
}

/**
 * حقول ملف المتدرب (المفتاح => [التسمية، النوع، الخيارات]).
 *
 * @return array
 */
function alzaherah_profile_fields() {
	if ( alzaherah_core_owns_trainee_profile() ) {
		return ALZ_Trainee_Profile::fields();
	}
	return array(
		'alz_father_name'      => array( __( 'اسم الأب', 'alzaherah' ), 'text', array() ),
		'alz_grandfather_name' => array( __( 'اسم الجد', 'alzaherah' ), 'text', array() ),
		'alz_nationality'      => array( __( 'الجنسية', 'alzaherah' ), 'text', array() ),
		'alz_gender'           => array(
			__( 'الجنس', 'alzaherah' ),
			'select',
			array(
				''       => __( '— اختر —', 'alzaherah' ),
				'male'   => __( 'ذكر', 'alzaherah' ),
				'female' => __( 'أنثى', 'alzaherah' ),
			),
		),
		'alz_dob'              => array( __( 'تاريخ الميلاد', 'alzaherah' ), 'date', array() ),
		'alz_qualification'    => array(
			__( 'المؤهل العلمي', 'alzaherah' ),
			'select',
			array(
				''            => __( '— اختر —', 'alzaherah' ),
				'high_school' => __( 'ثانوي فأقل', 'alzaherah' ),
				'diploma'     => __( 'دبلوم', 'alzaherah' ),
				'bachelor'    => __( 'بكالوريوس', 'alzaherah' ),
				'master'      => __( 'ماجستير', 'alzaherah' ),
				'phd'         => __( 'دكتوراه', 'alzaherah' ),
			),
		),
		'alz_job_title'        => array( __( 'المسمى الوظيفي', 'alzaherah' ), 'text', array() ),
	);
}

/**
 * قيمة معروضة لحقل (تحوّل مفاتيح القوائم إلى تسمياتها).
 *
 * @param string $key مفتاح الحقل.
 * @param string $value القيمة المخزنة.
 * @return string
 */
function alzaherah_profile_display_value( $key, $value ) {
	if ( alzaherah_core_owns_trainee_profile() ) {
		return ALZ_Trainee_Profile::display_value( $key, $value );
	}
	$fields = alzaherah_profile_fields();
	if ( isset( $fields[ $key ] ) && 'select' === $fields[ $key ][1] && isset( $fields[ $key ][2][ $value ] ) ) {
		return $fields[ $key ][2][ $value ];
	}
	return $value;
}

/**
 * عرض الحقول في نموذج بيانات الحساب.
 */
function alzaherah_profile_account_fields() {
	$user_id = get_current_user_id();
	?>
	<fieldset class="trainee-profile-fieldset">
		<legend><?php esc_html_e( 'بيانات المتدرب الإضافية (اختيارية)', 'alzaherah' ); ?></legend>
		<p class="trainee-profile-hint"><?php esc_html_e( 'تُستخدم لإصدار الشهادات وتحسين البرامج، وتُعبأ مرة واحدة فقط.', 'alzaherah' ); ?></p>

		<?php foreach ( alzaherah_profile_fields() as $key => $field ) : ?>
			<?php $value = get_user_meta( $user_id, $key, true ); ?>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label>

				<?php if ( 'select' === $field[1] ) : ?>
					<select class="woocommerce-Input input-text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>">
						<?php foreach ( $field[2] as $opt_key => $opt_label ) : ?>
							<option value="<?php echo esc_attr( $opt_key ); ?>" <?php selected( $value, $opt_key ); ?>><?php echo esc_html( $opt_label ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php else : ?>
					<input
						type="<?php echo esc_attr( 'date' === $field[1] ? 'date' : 'text' ); ?>"
						class="woocommerce-Input woocommerce-Input--text input-text"
						name="<?php echo esc_attr( $key ); ?>"
						id="<?php echo esc_attr( $key ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
					>
				<?php endif; ?>
			</p>
		<?php endforeach; ?>
	</fieldset>
	<?php
}
add_action( 'woocommerce_edit_account_form', 'alzaherah_profile_account_fields' );

/**
 * Fallback حفظ الحقول عند غياب Core.
 *
 * @param int $user_id معرف المستخدم.
 */
function alzaherah_profile_account_save( $user_id ) {
	$user_id = absint( $user_id );
	if ( ! $user_id || get_current_user_id() !== $user_id || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	foreach ( alzaherah_profile_fields() as $key => $field ) {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			continue;
		}
		$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( 'select' === $field[1] && '' !== $value && ! isset( $field[2][ $value ] ) ) {
			continue;
		}
		if ( 'date' === $field[1] && '' !== $value ) {
			if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) ) {
				continue;
			}
			$year  = (int) $parts[1];
			$month = (int) $parts[2];
			$day   = (int) $parts[3];
			if ( ! checkdate( $month, $day, $year ) ) {
				continue;
			}
			$normalized = sprintf( '%04d-%02d-%02d', $year, $month, $day );
			if ( $normalized !== $value ) {
				continue;
			}
			if ( 'alz_dob' === $key && $value > wp_date( 'Y-m-d' ) ) {
				continue;
			}
			$value = $normalized;
		}
		update_user_meta( $user_id, $key, $value );
	}
}

/**
 * Fallback تحقق تاريخ الميلاد عند غياب Core.
 *
 * @param WP_Error $errors أخطاء WooCommerce.
 */
function alzaherah_profile_account_validate( $errors ) {
	if ( ! $errors instanceof WP_Error || ! isset( $_POST['alz_dob'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}
	$raw = sanitize_text_field( wp_unslash( $_POST['alz_dob'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( '' === $raw ) {
		return;
	}
	$valid = false;
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $parts ) ) {
		$year  = (int) $parts[1];
		$month = (int) $parts[2];
		$day   = (int) $parts[3];
		if ( checkdate( $month, $day, $year ) ) {
			$normalized = sprintf( '%04d-%02d-%02d', $year, $month, $day );
			if ( $normalized === $raw && $raw <= wp_date( 'Y-m-d' ) ) {
				$valid = true;
			}
		}
	}
	if ( ! $valid ) {
		$errors->add(
			'alz_dob_invalid',
			__( 'تاريخ الميلاد غير صالح. أدخل تاريخًا حقيقيًا بصيغة يوم-شهر-سنة، ولا يمكن أن يكون في المستقبل.', 'alzaherah' )
		);
	}
}

if ( ! alzaherah_core_owns_trainee_profile() ) {
	add_action( 'woocommerce_save_account_details_errors', 'alzaherah_profile_account_validate', 10, 1 );
	add_action( 'woocommerce_save_account_details', 'alzaherah_profile_account_save' );
}

/**
 * عرض الحقول في شاشة ملف المستخدم بلوحة التحكم.
 *
 * @param WP_User $user المستخدم.
 */
function alzaherah_profile_admin_fields( $user ) {
	if ( ! current_user_can( 'alz_manage_trainees' ) ) {
		return;
	}
	?>
	<h2><?php esc_html_e( 'بيانات المتدرب الإضافية', 'alzaherah' ); ?></h2>
	<table class="form-table" role="presentation">
		<?php foreach ( alzaherah_profile_fields() as $key => $field ) : ?>
			<?php $value = get_user_meta( $user->ID, $key, true ); ?>
			<tr>
				<th><?php echo esc_html( $field[0] ); ?></th>
				<td><?php echo esc_html( alzaherah_profile_display_value( $key, $value ) ?: '—' ); ?></td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}
add_action( 'show_user_profile', 'alzaherah_profile_admin_fields', 20 );
add_action( 'edit_user_profile', 'alzaherah_profile_admin_fields', 20 );
