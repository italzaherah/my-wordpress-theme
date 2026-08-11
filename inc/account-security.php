<?php
/**
 * أمان الحساب: Presentation + Fallback فقط.
 * منطق التغيير والحماية في Core عند توفره.
 *
 * @package Alzaherah
 * @since   3.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * هل Core يملك أمان الحساب؟
 *
 * @return bool
 */
function alzaherah_core_owns_account_security() {
	return class_exists( 'ALZ_Account_Security' )
		&& method_exists( 'ALZ_Account_Security', 'handle_change_email' )
		&& defined( 'ALZ_CORE_VERSION' )
		&& version_compare( ALZ_CORE_VERSION, '2.6.6', '>=' );
}

if ( alzaherah_core_owns_account_security() ) {
	return;
}

/**
 * تسجيل مسار «أمان الحساب» داخل صفحة حساب WooCommerce.
 */
function alzaherah_register_account_security_endpoint() {
	add_rewrite_endpoint( 'account-security', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'alzaherah_register_account_security_endpoint' );

/**
 * تنظيف قواعد الروابط مرة واحدة عند تثبيت هذا الإصدار أو استبداله فوق إصدار سابق.
 */
function alzaherah_maybe_flush_account_security_endpoint() {
	$schema_version = '1.0.0';
	if ( get_option( 'alzaherah_account_security_endpoint_version' ) === $schema_version ) {
		return;
	}

	alzaherah_register_account_security_endpoint();
	flush_rewrite_rules( false );
	update_option( 'alzaherah_account_security_endpoint_version', $schema_version, false );
}
add_action( 'admin_init', 'alzaherah_maybe_flush_account_security_endpoint' );
add_action( 'after_switch_theme', 'alzaherah_maybe_flush_account_security_endpoint' );

/**
 * إضافة التبويب بعد بيانات الحساب وقبل تسجيل الخروج.
 *
 * @param array $items عناصر قائمة الحساب.
 * @return array
 */
function alzaherah_account_security_menu_item( $items ) {
	$ordered = array();
	$added   = false;
	foreach ( $items as $key => $label ) {
		$ordered[ $key ] = $label;
		if ( 'edit-account' === $key ) {
			$ordered['account-security'] = __( 'أمان الحساب', 'alzaherah' );

			$added = true;
		}
	}
	if ( ! $added ) {
		$ordered['account-security'] = __( 'أمان الحساب', 'alzaherah' );
	}
	return $ordered;
}
add_filter( 'woocommerce_account_menu_items', 'alzaherah_account_security_menu_item', 30 );

/**
 * عنوان وناتج مسار أمان الحساب.
 */
function alzaherah_account_security_endpoint_title() {
	return __( 'أمان الحساب', 'alzaherah' );
}
add_filter( 'woocommerce_endpoint_account-security_title', 'alzaherah_account_security_endpoint_title' );

/**
 * تحميل قالب تبويب أمان الحساب من القالب النشط أو القالب الابن.
 */
function alzaherah_render_account_security_endpoint() {
	$template = locate_template( 'woocommerce/myaccount/account-security.php' );
	if ( $template ) {
		include $template;
	}
}
add_action( 'woocommerce_account_account-security_endpoint', 'alzaherah_render_account_security_endpoint' );

/**
 * إعادة التوجيه لصفحة بيانات الحساب مع إشعار.
 *
 * @param string $message نص الإشعار.
 * @param string $type نوعه: success|error.
 */
function alzaherah_account_redirect( $message, $type = 'success' ) {
	if ( function_exists( 'wc_add_notice' ) ) {
		wc_add_notice( $message, $type );
	}
	wp_safe_redirect( wc_get_account_endpoint_url( 'account-security' ) );
	exit;
}

/**
 * تغيير البريد الإلكتروني.
 */
function alzaherah_handle_change_email() {

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url() );
		exit;
	}

	$user = wp_get_current_user();

	if (
		! isset( $_POST['alz_change_email_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['alz_change_email_nonce'] ), 'alz_change_email' )
	) {
		alzaherah_account_redirect( __( 'انتهت صلاحية الطلب، أعد المحاولة.', 'alzaherah' ), 'error' );
	}

	$new_email = isset( $_POST['alz_new_email'] ) ? sanitize_email( wp_unslash( $_POST['alz_new_email'] ) ) : '';
	$confirm   = isset( $_POST['alz_new_email_confirm'] ) ? sanitize_email( wp_unslash( $_POST['alz_new_email_confirm'] ) ) : '';
	$password  = isset( $_POST['alz_email_password'] ) ? (string) wp_unslash( $_POST['alz_email_password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
		alzaherah_account_redirect( __( 'كلمة المرور الحالية غير صحيحة.', 'alzaherah' ), 'error' );
	}

	if ( ! is_email( $new_email ) ) {
		alzaherah_account_redirect( __( 'صيغة البريد الجديد غير صحيحة.', 'alzaherah' ), 'error' );
	}
	if ( ! hash_equals( strtolower( $new_email ), strtolower( $confirm ) ) ) {
		alzaherah_account_redirect( __( 'حقلا البريد غير متطابقين.', 'alzaherah' ), 'error' );
	}
	if ( strtolower( $new_email ) === strtolower( $user->user_email ) ) {
		alzaherah_account_redirect( __( 'البريد الجديد مطابق للحالي.', 'alzaherah' ), 'error' );
	}
	$existing = email_exists( $new_email );
	if ( $existing && (int) $existing !== (int) $user->ID ) {
		alzaherah_account_redirect( __( 'هذا البريد مستخدم لحساب آخر.', 'alzaherah' ), 'error' );
	}

	$old_email = $user->user_email;

	$updated = wp_update_user(
		array(
			'ID'         => $user->ID,
			'user_email' => $new_email,
		)
	);
	if ( is_wp_error( $updated ) ) {
		alzaherah_account_redirect( __( 'تعذر تحديث البريد، حاول لاحقًا.', 'alzaherah' ), 'error' );
	}

	update_user_meta( $user->ID, 'billing_email', $new_email );

	wp_mail(
		$old_email,
		sprintf( /* translators: %s: اسم الموقع */ __( '[%s] تم تغيير بريد حسابك', 'alzaherah' ), get_bloginfo( 'name' ) ),
		sprintf(
			/* translators: 1: البريد الجديد، 2: بريد الدعم */
			__( "تم تغيير البريد الإلكتروني لحسابك إلى: %1\$s\n\nإن لم تكن أنت من قام بذلك فتواصل معنا فورًا: %2\$s", 'alzaherah' ),
			$new_email,
			function_exists( 'alzaherah_contact_email' ) ? alzaherah_contact_email() : 'contact@alzaherah.edu.sa'
		)
	);
	if ( function_exists( 'alz_audit_log' ) ) {
		alz_audit_log(
			'account_email_changed',
			sprintf( 'تغيير البريد الإلكتروني للمستخدم #%d', $user->ID ),
			array(
				'actor_id'    => $user->ID,
				'object_type' => 'user',
				'object_id'   => $user->ID,
				'severity'    => 'notice',
				'context'     => array(
					'old_email' => $old_email,
					'new_email' => $new_email,
				),
			)
		);
	}

	alzaherah_account_redirect( __( 'تم تحديث بريدك الإلكتروني بنجاح.', 'alzaherah' ) );
}
add_action( 'admin_post_alz_change_email', 'alzaherah_handle_change_email' );

/**
 * تغيير كلمة المرور.
 */
function alzaherah_handle_change_password() {

	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url() );
		exit;
	}

	$user = wp_get_current_user();

	if (
		! isset( $_POST['alz_change_password_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( $_POST['alz_change_password_nonce'] ), 'alz_change_password' )
	) {
		alzaherah_account_redirect( __( 'انتهت صلاحية الطلب، أعد المحاولة.', 'alzaherah' ), 'error' );
	}

	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$current = isset( $_POST['alz_current_password'] ) ? (string) wp_unslash( $_POST['alz_current_password'] ) : '';
	$new     = isset( $_POST['alz_new_password'] ) ? (string) wp_unslash( $_POST['alz_new_password'] ) : '';
	$confirm = isset( $_POST['alz_new_password_confirm'] ) ? (string) wp_unslash( $_POST['alz_new_password_confirm'] ) : '';
	// phpcs:enable

	if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
		alzaherah_account_redirect( __( 'كلمة المرور الحالية غير صحيحة.', 'alzaherah' ), 'error' );
	}
	if ( strlen( $new ) < 8 ) {
		alzaherah_account_redirect( __( 'كلمة المرور الجديدة قصيرة — ثمانية أحرف على الأقل.', 'alzaherah' ), 'error' );
	}
	if ( ! hash_equals( $new, $confirm ) ) {
		alzaherah_account_redirect( __( 'حقلا كلمة المرور الجديدة غير متطابقين.', 'alzaherah' ), 'error' );
	}
	if ( hash_equals( $new, $current ) ) {
		alzaherah_account_redirect( __( 'كلمة المرور الجديدة مطابقة للحالية.', 'alzaherah' ), 'error' );
	}

	wp_set_password( $new, $user->ID );
	if ( function_exists( 'alz_audit_log' ) ) {
		alz_audit_log(
			'account_password_changed',
			sprintf( 'تغيير كلمة مرور المستخدم #%d', $user->ID ),
			array(
				'actor_id'    => $user->ID,
				'object_type' => 'user',
				'object_id'   => $user->ID,
				'severity'    => 'notice',
			)
		);
	}

	wp_set_auth_cookie( $user->ID, false, is_ssl() );

	alzaherah_account_redirect( __( 'تم تغيير كلمة المرور بنجاح.', 'alzaherah' ) );
}
add_action( 'admin_post_alz_change_password', 'alzaherah_handle_change_password' );
