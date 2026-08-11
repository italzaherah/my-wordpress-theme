<?php
/**
 * Partners directory and partnership approval workflow.
 *
 * @package Alzaherah
 * @since   3.10.2
 */

defined( 'ABSPATH' ) || exit;

function alzaherah_core_owns_partners() {
	return class_exists( 'ALZ_Partners' ) && method_exists( 'ALZ_Partners', 'review_partner' );
}

function alzaherah_register_partner_post_type() {
	if ( alzaherah_core_owns_partners() || post_type_exists( 'alz_partner' ) ) {
		return;
	}
	$capabilities = array_fill_keys(
		array(
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'publish_posts',
			'read_private_posts',
			'delete_posts',
			'delete_private_posts',
			'delete_published_posts',
			'delete_others_posts',
			'edit_private_posts',
			'edit_published_posts',
			'create_posts',
		),
		'alz_manage_partners'
	);
	register_post_type(
		'alz_partner',
		array(
			'labels' => array(
				'name'               => 'شركاء النجاح',
				'singular_name'      => 'شريك',
				'add_new'            => 'إضافة شريك',
				'add_new_item'       => 'إضافة شريك جديد',
				'edit_item'          => 'مراجعة بيانات الشريك',
				'search_items'       => 'بحث في الشركاء',
				'not_found'          => 'لا توجد طلبات أو جهات مطابقة',
				'menu_name'          => 'الشركاء',
				'all_items'          => 'جميع الشركاء والطلبات',
				'featured_image'     => 'شعار الجهة',
				'set_featured_image' => 'رفع أو اختيار الشعار',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-groups',
			'menu_position'       => 26,
			'supports'            => array( 'title', 'thumbnail' ),
			'capability_type'     => 'alz_partner',
			'capabilities'        => $capabilities,
			'map_meta_cap'        => false,
			'exclude_from_search' => true,
			'show_in_rest'        => false,
		)
	);
}

function alzaherah_partner_statuses() {
	if ( alzaherah_core_owns_partners() ) {
		return ALZ_Partners::statuses();
	}
	return array(
		'pending'  => 'بانتظار المراجعة',
		'approved' => 'معتمد ومنشور',
		'rejected' => 'مرفوض',
	);
}

function alzaherah_partner_meta_boxes() {
	if ( alzaherah_core_owns_partners() ) {
		return;
	}
	add_meta_box( 'alz-partner-details', 'تفاصيل طلب الشراكة', 'alzaherah_partner_details_box', 'alz_partner', 'normal', 'high' );
	add_meta_box( 'alz-partner-review', 'الاعتماد والتحقق', 'alzaherah_partner_review_box', 'alz_partner', 'side', 'high' );
}

function alzaherah_partner_details_box( $post ) {
	wp_nonce_field( 'alzaherah_save_partner', 'alzaherah_partner_nonce' );
	$fields = array(
		'legal_name'       => array( 'الاسم القانوني للجهة', 'text' ),
		'contact_name'     => array( 'اسم مسؤول التواصل', 'text' ),
		'job_title'        => array( 'المسمى الوظيفي', 'text' ),
		'email'            => array( 'البريد الإلكتروني', 'email' ),
		'phone'            => array( 'رقم الجوال/الهاتف', 'text' ),
		'website'          => array( 'الموقع الرسمي', 'url' ),
		'registration_no'  => array( 'رقم السجل/الترخيص', 'text' ),
		'partnership_type' => array( 'نوع الشراكة المقترحة', 'text' ),
		'message'          => array( 'تفاصيل الطلب وأهداف الشراكة', 'textarea' ),
	);
	echo '<table class="form-table alz-partner-admin-fields" role="presentation"><tbody>';
	foreach ( $fields as $key => $config ) {
		$value = get_post_meta( $post->ID, '_alz_partner_' . $key, true );
		echo '<tr><th><label for="alz-partner-' . esc_attr( $key ) . '">' . esc_html( $config[0] ) . '</label></th><td>';
		if ( 'textarea' === $config[1] ) {
			echo '<textarea class="large-text" rows="6" id="alz-partner-' . esc_attr( $key ) . '" name="alz_partner[' . esc_attr( $key ) . ']">' . esc_textarea( $value ) . '</textarea>';
		} else {
			echo '<input class="regular-text" type="' . esc_attr( $config[1] ) . '" id="alz-partner-' . esc_attr( $key ) . '" name="alz_partner[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '">';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';

	$submitted = get_post_meta( $post->ID, '_alz_partner_submitted_at', true );
	$ip_hash   = get_post_meta( $post->ID, '_alz_partner_ip_hash', true );
	if ( $submitted ) {
		echo '<p><strong>وقت تقديم الطلب:</strong> ' . esc_html( $submitted ) . '</p>';
	}
	if ( $ip_hash ) {
		echo '<p><strong>مرجع أمني للطلب:</strong> <code>' . esc_html( substr( $ip_hash, 0, 16 ) ) . '…</code> (لا يتم حفظ عنوان IP الخام)</p>';
	}
}

function alzaherah_partner_review_box( $post ) {
	$status          = get_post_meta( $post->ID, '_alz_partner_status', true );
	$status          = $status ? $status : 'pending';
	$logo_verified   = get_post_meta( $post->ID, '_alz_partner_logo_verified', true );
	$source_url      = get_post_meta( $post->ID, '_alz_partner_logo_source', true );
	$review_notes    = get_post_meta( $post->ID, '_alz_partner_review_notes', true );
	$reviewed_at     = get_post_meta( $post->ID, '_alz_partner_reviewed_at', true );
	$reviewed_by     = absint( get_post_meta( $post->ID, '_alz_partner_reviewed_by', true ) );
	$statuses        = alzaherah_partner_statuses();
	$thumbnail_id    = get_post_thumbnail_id( $post );

	echo '<p><label for="alz-partner-status"><strong>حالة الطلب</strong></label></p>';
	echo '<select class="widefat" id="alz-partner-status" name="alz_partner[status]">';
	foreach ( $statuses as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
	echo '<p><label><input type="checkbox" name="alz_partner[logo_verified]" value="yes" ' . checked( $logo_verified, 'yes', false ) . '> تحققت يدويًا من صحة الشعار وحق استخدامه</label></p>';
	echo '<p><label for="alz-partner-logo-source"><strong>المصدر الرسمي للشعار</strong></label></p>';
	echo '<input class="widefat" type="url" id="alz-partner-logo-source" name="alz_partner[logo_source]" value="' . esc_attr( $source_url ) . '" placeholder="https://official.example/logo">';
	if ( $thumbnail_id ) {
		$file = get_attached_file( $thumbnail_id );
		$meta = wp_get_attachment_metadata( $thumbnail_id );
		echo '<div style="margin:14px 0;padding:12px;border:1px solid #dcdcde;border-radius:8px;background:#fff;text-align:center">';
		echo wp_get_attachment_image( $thumbnail_id, 'medium', false, array( 'style' => 'max-width:100%;height:120px;object-fit:contain' ) );
		echo '<p style="margin-bottom:0"><strong>فحص الملف:</strong><br>';
		echo esc_html( get_post_mime_type( $thumbnail_id ) );
		if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
			echo ' — ' . esc_html( $meta['width'] . '×' . $meta['height'] . ' px' );
		}
		if ( $file && file_exists( $file ) ) {
			echo '<br>' . esc_html( size_format( filesize( $file ) ) );
		}
		echo '</p></div>';
	} else {
		echo '<p class="description" style="color:#b32d2e">لا يوجد شعار مرفوع. لن يظهر هذا الشريك للعامة حتى رفع شعار والتحقق منه.</p>';
	}
	echo '<p><label for="alz-partner-review-notes"><strong>ملاحظات المراجع</strong></label></p>';
	echo '<textarea class="widefat" rows="5" id="alz-partner-review-notes" name="alz_partner[review_notes]">' . esc_textarea( $review_notes ) . '</textarea>';
	if ( $reviewed_at ) {
		$user = get_userdata( $reviewed_by );
		echo '<p class="description">آخر مراجعة: ' . esc_html( $reviewed_at ) . ( $user ? ' — ' . esc_html( $user->display_name ) : '' ) . '</p>';
	}
	echo '<p class="description"><strong>قاعدة النشر:</strong> لا يظهر الشريك إلا إذا كانت الحالة «معتمد»، والشعار موجود، وخيار التحقق محدد.</p>';
}

function alzaherah_save_partner_meta( $post_id ) {
	if ( alzaherah_core_owns_partners() ) {
		return;
	}
	if ( ! isset( $_POST['alzaherah_partner_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['alzaherah_partner_nonce'] ) ), 'alzaherah_save_partner' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'alz_manage_partners' ) || 'alz_partner' !== get_post_type( $post_id ) ) {
		return;
	}
	$data = isset( $_POST['alz_partner'] ) && is_array( $_POST['alz_partner'] ) ? wp_unslash( $_POST['alz_partner'] ) : array();
	$text_fields = array( 'legal_name', 'contact_name', 'job_title', 'phone', 'registration_no', 'partnership_type' );
	foreach ( $text_fields as $key ) {
		update_post_meta( $post_id, '_alz_partner_' . $key, sanitize_text_field( isset( $data[ $key ] ) ? $data[ $key ] : '' ) );
	}
	update_post_meta( $post_id, '_alz_partner_email', sanitize_email( isset( $data['email'] ) ? $data['email'] : '' ) );
	update_post_meta( $post_id, '_alz_partner_website', esc_url_raw( isset( $data['website'] ) ? $data['website'] : '' ) );
	update_post_meta( $post_id, '_alz_partner_message', sanitize_textarea_field( isset( $data['message'] ) ? $data['message'] : '' ) );
	$logo_source = esc_url_raw( isset( $data['logo_source'] ) ? $data['logo_source'] : '' );
	if ( function_exists( 'alz_core_sanitize_non_legacy_url' ) ) {
		$logo_source = alz_core_sanitize_non_legacy_url( $logo_source );
	} elseif ( preg_match( '#alzaherah\.edu\.sa/(?:storage|article)(?:/|$)#i', $logo_source ) ) {
		$logo_source = '';
	}
	update_post_meta( $post_id, '_alz_partner_logo_source', $logo_source );
	update_post_meta( $post_id, '_alz_partner_review_notes', sanitize_textarea_field( isset( $data['review_notes'] ) ? $data['review_notes'] : '' ) );
	$logo_verified = ! empty( $data['logo_verified'] ) && has_post_thumbnail( $post_id ) ? 'yes' : 'no';
	update_post_meta( $post_id, '_alz_partner_logo_verified', $logo_verified );
	$status = isset( $data['status'] ) && array_key_exists( $data['status'], alzaherah_partner_statuses() ) ? $data['status'] : 'pending';
	if ( 'approved' === $status && ( 'yes' !== $logo_verified || ! has_post_thumbnail( $post_id ) ) ) {
		$status = 'pending';
	}
	update_post_meta( $post_id, '_alz_partner_status', $status );
	update_post_meta( $post_id, '_alz_partner_reviewed_at', current_time( 'mysql' ) );
	update_post_meta( $post_id, '_alz_partner_reviewed_by', get_current_user_id() );
}

function alzaherah_partner_columns( $columns ) {
	if ( alzaherah_core_owns_partners() ) {
		return $columns;
	}
	return array(
		'cb'         => $columns['cb'],
		'logo'       => 'الشعار',
		'title'      => 'الجهة',
		'status'     => 'الحالة',
		'contact'    => 'مسؤول التواصل',
		'verification' => 'التحقق',
		'date'       => 'تاريخ الطلب',
	);
}

function alzaherah_partner_column_content( $column, $post_id ) {
	if ( alzaherah_core_owns_partners() ) {
		return;
	}
	if ( 'logo' === $column ) {
		echo get_the_post_thumbnail( $post_id, array( 72, 52 ), array( 'style' => 'width:72px;height:52px;object-fit:contain;background:#fff;border:1px solid #ddd;border-radius:6px', 'alt' => sprintf( 'شعار %s', get_the_title( $post_id ) ) ) );
	} elseif ( 'status' === $column ) {
		$status = get_post_meta( $post_id, '_alz_partner_status', true );
		$labels = alzaherah_partner_statuses();
		echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['pending'] );
	} elseif ( 'contact' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_alz_partner_contact_name', true ) );
		$email = get_post_meta( $post_id, '_alz_partner_email', true );
		if ( $email ) {
			echo '<br><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
		}
	} elseif ( 'verification' === $column ) {
		echo 'yes' === get_post_meta( $post_id, '_alz_partner_logo_verified', true ) ? '<span style="color:#008a20">✓ شعار متحقق</span>' : '<span style="color:#b32d2e">يحتاج تحقق</span>';
	}
}

function alzaherah_partner_upload_mimes( $mimes ) {
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}

function alzaherah_handle_partner_submission() {
	// The plugin is the single business owner. Keep this only as a safe theme fallback.
	if ( class_exists( 'ALZ_Partners' ) ) {
		return;
	}
	$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : '';
	if ( 'POST' !== $request_method ) {
		return;
	}
	if ( empty( $_POST['alz_partner_action'] ) || 'submit' !== sanitize_key( wp_unslash( $_POST['alz_partner_action'] ) ) ) {
		return;
	}
	$redirect = isset( $_POST['alz_partner_return_url'] )
		? wp_validate_redirect( esc_url_raw( wp_unslash( $_POST['alz_partner_return_url'] ) ), '' )
		: '';
	$home_host     = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
	$redirect_host = strtolower( (string) wp_parse_url( $redirect, PHP_URL_HOST ) );
	if ( $redirect && $redirect_host && ! hash_equals( $home_host, $redirect_host ) ) {
		$redirect = '';
	}
	if ( ! $redirect ) {
		$current_page_id = get_queried_object_id();
		$redirect        = $current_page_id && 'page' === get_post_type( $current_page_id )
			? get_permalink( $current_page_id )
			: ( wp_get_referer() ? wp_get_referer() : home_url( '/partners/' ) );
	}
	$redirect = preg_replace( '/#.*$/', '', (string) $redirect );
	if ( ! isset( $_POST['alz_partner_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['alz_partner_nonce'] ) ), 'alz_partner_submit' ) ) {
		wp_safe_redirect( add_query_arg( 'partner_error', 'security', $redirect ) );
		exit;
	}
	if ( ! empty( $_POST['alz_partner_fax_check'] ) || ! empty( $_POST['company_website_confirm'] ) ) {
		wp_safe_redirect( add_query_arg( 'partner_error', 'spam', $redirect ) );
		exit;
	}
	$ip          = function_exists( 'alz_core_client_ip' ) ? alz_core_client_ip() : ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
	$rate_key    = 'alz_partner_rate_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
	$rate_count  = absint( get_transient( $rate_key ) );
	if ( $rate_count >= 3 ) {
		wp_safe_redirect( add_query_arg( 'partner_error', 'rate', $redirect ) );
		exit;
	}
	if ( function_exists( 'alz_turnstile_verify' ) ) {
		if ( function_exists( 'alz_turnstile_is_enabled' ) && alz_turnstile_is_enabled( 'partner_request' ) ) {
			$captcha_rate_key   = 'alz_partner_captcha_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 32 );
			$captcha_rate_count = absint( get_transient( $captcha_rate_key ) );
			if ( $captcha_rate_count >= 12 ) {
				wp_safe_redirect( add_query_arg( 'partner_error', 'rate', $redirect ) );
				exit;
			}
			set_transient( $captcha_rate_key, $captcha_rate_count + 1, 15 * MINUTE_IN_SECONDS );
		}
		$captcha_result = alz_turnstile_verify( 'partner_request' );
		if ( is_wp_error( $captcha_result ) ) {
			wp_safe_redirect( add_query_arg( 'partner_error', 'captcha', $redirect ) );
			exit;
		}
	}
	$name    = sanitize_text_field( isset( $_POST['organization_name'] ) ? wp_unslash( $_POST['organization_name'] ) : '' );
	$contact = sanitize_text_field( isset( $_POST['contact_name'] ) ? wp_unslash( $_POST['contact_name'] ) : '' );
	$email   = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' );
	$phone   = sanitize_text_field( isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '' );
	if ( ! $name || ! $contact || ! is_email( $email ) || ! $phone || empty( $_POST['privacy_consent'] ) ) {
		wp_safe_redirect( add_query_arg( 'partner_error', 'required', $redirect ) );
		exit;
	}
	if ( empty( $_FILES['partner_logo']['name'] ) || ! empty( $_FILES['partner_logo']['error'] ) ) {
		wp_safe_redirect( add_query_arg( 'partner_error', 'logo', $redirect ) );
		exit;
	}
	$logo_file = $_FILES['partner_logo'];
	$allowed   = array( 'image/png', 'image/jpeg', 'image/webp' );
	$checked   = wp_check_filetype_and_ext( $logo_file['tmp_name'], $logo_file['name'] );
	$image_info = @getimagesize( $logo_file['tmp_name'] );
	if ( ! in_array( $checked['type'], $allowed, true ) || (int) $logo_file['size'] > 2 * MB_IN_BYTES || ! $image_info || $image_info[0] < 200 || $image_info[1] < 120 ) {
		wp_safe_redirect( add_query_arg( 'partner_error', 'logo', $redirect ) );
		exit;
	}
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'alz_partner',
			'post_status' => 'publish',
			'post_title'  => $name,
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		wp_safe_redirect( add_query_arg( 'partner_error', 'save', $redirect ) );
		exit;
	}
	$map = array(
		'legal_name'       => $name,
		'contact_name'     => $contact,
		'job_title'        => sanitize_text_field( isset( $_POST['job_title'] ) ? wp_unslash( $_POST['job_title'] ) : '' ),
		'email'            => $email,
		'phone'            => $phone,
		'website'          => esc_url_raw( isset( $_POST['website'] ) ? wp_unslash( $_POST['website'] ) : '' ),
		'registration_no'  => sanitize_text_field( isset( $_POST['registration_no'] ) ? wp_unslash( $_POST['registration_no'] ) : '' ),
		'partnership_type' => sanitize_text_field( isset( $_POST['partnership_type'] ) ? wp_unslash( $_POST['partnership_type'] ) : '' ),
		'message'          => sanitize_textarea_field( isset( $_POST['message'] ) ? wp_unslash( $_POST['message'] ) : '' ),
	);
	foreach ( $map as $key => $value ) {
		update_post_meta( $post_id, '_alz_partner_' . $key, $value );
	}
	update_post_meta( $post_id, '_alz_partner_status', 'pending' );
	update_post_meta( $post_id, '_alz_partner_logo_verified', 'no' );
	update_post_meta( $post_id, '_alz_partner_submitted_at', current_time( 'mysql' ) );
	update_post_meta( $post_id, '_alz_partner_ip_hash', hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ) );

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$attachment_id = media_handle_upload( 'partner_logo', $post_id );
	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_post( $post_id, true );
		wp_safe_redirect( add_query_arg( 'partner_error', 'logo', $redirect ) );
		exit;
	}
	set_post_thumbnail( $post_id, $attachment_id );
	set_transient( $rate_key, $rate_count + 1, HOUR_IN_SECONDS );

	$admin_email = get_option( 'admin_email' );
	wp_mail(
		$admin_email,
		'طلب شراكة جديد: ' . $name,
		"وصل طلب شراكة جديد من {$name}.\nمسؤول التواصل: {$contact}\nالبريد: {$email}\n\nراجع الطلب من لوحة التحكم ← الشركاء."
	);
	nocache_headers();
	wp_safe_redirect( add_query_arg( 'partner_submitted', (int) $post_id, $redirect ) . '#alz-partner-form-card', 303, 'Alzaherah Partner Submission' );
	exit;
}

function alzaherah_partner_public_query() {
	if ( alzaherah_core_owns_partners() ) {
		return new WP_Query( ALZ_Partners::public_query_args() );
	}
	return new WP_Query(
		array(
			'post_type'      => 'alz_partner',
			'post_status'    => 'publish',
			'posts_per_page' => 60,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'meta_query'     => array(
				'relation' => 'AND',
				array( 'key' => '_alz_partner_status', 'value' => 'approved' ),
				array( 'key' => '_alz_partner_logo_verified', 'value' => 'yes' ),
				array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ),
			),
		)
	);
}

function alzaherah_prepare_partners_page() {
	$version = '1.1';
	if ( $version === get_option( 'alzaherah_partners_setup_version' ) ) {
		return;
	}

	$pages = array(
		'partners' => array(
			'title'    => 'شركاء النجاح',
			'template' => 'page-partners.php',
		),
		'partnership-request' => array(
			'title'    => 'تقديم طلب شراكة',
			'template' => 'page-partnership-request.php',
		),
	);

	foreach ( $pages as $slug => $config ) {
		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			$page_id = wp_insert_post(
				array(
					'post_title'  => $config['title'],
					'post_name'   => $slug,
					'post_status' => 'publish',
					'post_type'   => 'page',
				)
			);
		} else {
			$page_id = $page->ID;
		}
		if ( $page_id && ! is_wp_error( $page_id ) ) {
			update_post_meta( $page_id, '_wp_page_template', $config['template'] );
		}
	}

	flush_rewrite_rules( false );
	update_option( 'alzaherah_partners_setup_version', $version );
	do_action( 'litespeed_purge_all' );
}
add_action( 'admin_init', 'alzaherah_prepare_partners_page' );

/**
 * Purge page caches once after a partner-section UI release.
 *
 * Theme assets already use the theme version as their cache key, but a full-page
 * cache can continue serving old HTML and old asset URLs after the theme update.
 */
function alzaherah_partner_ui_upgrade() {
	$version = '3.10.14';
	if ( $version === get_option( 'alzaherah_partner_ui_version' ) ) {
		return;
	}

	update_option( 'alzaherah_partner_ui_version', $version );
	do_action( 'litespeed_purge_all' );
}
add_action( 'admin_init', 'alzaherah_partner_ui_upgrade', 99 );

/**
 * Attach a bundled local partner logo without any remote fetch.
 *
 * @param int    $post_id   Partner ID.
 * @param string $title     Partner title.
 * @param string $logo_path Absolute local path.
 */
function alzaherah_attach_local_partner_logo( $post_id, $title, $logo_path ) {
	$post_id = absint( $post_id );
	if ( ! $post_id || has_post_thumbnail( $post_id ) || ! is_readable( $logo_path ) ) {
		return;
	}
	$upload = wp_upload_bits( 'partner-' . $post_id . '.' . pathinfo( $logo_path, PATHINFO_EXTENSION ), null, file_get_contents( $logo_path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
		return;
	}
	$type          = wp_check_filetype( basename( $upload['file'] ) );
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => isset( $type['type'] ) ? $type['type'] : '',
			'post_title'     => $title . ' — شعار محلي',
			'post_status'    => 'inherit',
		),
		$upload['file'],
		$post_id
	);
	if ( ! $attachment_id || is_wp_error( $attachment_id ) ) {
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	set_post_thumbnail( $post_id, $attachment_id );
}

/**
 * One-time local partner snapshot. Never re-runs after any seed lock is stored,
 * even if this function is edited later. Does not fetch or discover old-site URLs.
 */
function alzaherah_seed_partners() {
	if ( '' !== (string) get_option( 'alzaherah_partner_seed_version', '' ) ) {
		return;
	}
	$partners = array(
		array( 'جمعية بني ظبيان الخيرية', 'KUF7OJqhEgpL06CPRDtGK7xnZvEXheju9enQ5azr.jpg' ),
		array( 'لجنة التنمية الاجتماعية الأهلية بناوان', '' ),
		array( 'لجنة شؤون الأسرة', '' ),
		array( 'أمانة الباحة', 'baha-official.png' ),
		array( 'مجلس شباب منطقة الباحة', 'TJcqkafDMOuWqw5XzIB6ZlvBNNIqxwd17gAntsYM.jpg' ),
		array( 'المركز الوطني للتعليم الإلكتروني', 'nelc-official.png' ),
		array( 'الهيئة الاستشارية الصحية بمنطقة الباحة', 'gV77R3RgoCwVJhRyhC8Ea6xaeXDeMrBhIjUWIO5I.jpg' ),
		array( 'جمعية البر الخيرية بالباحة', 'ewKio1RdCNIk6GbgbknGzWKXayeGPqeZ5VHGv4jp.jpg' ),
		array( 'جمعية أكناف لرعاية الأيتام بمنطقة الباحة', 'UlReWWbK4NL0wS3nlKiLFi1ZfM5lZ1H1ixGWKgL3.png' ),
		array( 'منصة منار', 'uLTHBHMJggrKHBweJBx6eR1SAi7ed8XqYYnwsPKK.png' ),
		array( 'المؤسسة العامة للتدريب التقني والمهني', 'PZ6lNQ4iRaOFqKBHEZzo1AW8ANXb10YCPPeQVaKC.png' ),
		array( 'لجنة التنمية الاجتماعية الأهلية بمركز شرى', 'hoWElR75C6pto83YnS3PGMTTJfklDNED9RcTrhM6.png' ),
		array( 'صحة الباحة', 'Hld2bwvTt4GzgbCygOXFLtjYJnmCyqpqbLwVnMAq.png' ),
		array( 'لجنة التنمية الاجتماعية الأهلية برغدان', '' ),
		array( 'جمعية أبواب للعمل التطوعي', '' ),
		array( 'جمعية تعاطف الخيرية للخدمات الصحية', 'H1a6uym47RQWdH8fz4RhJ7SMQRVTm5WeRn43v7OM.png' ),
	);

	$use_core = class_exists( 'ALZ_Partners' ) && method_exists( 'ALZ_Partners', 'import_legacy_partner' );
	if ( alzaherah_core_owns_partners() && ! $use_core ) {
		return;
	}

	foreach ( $partners as $partner ) {
		$logo_path = ( ! empty( $partner[1] ) && file_exists( get_template_directory() . '/assets/partners/' . $partner[1] ) )
			? get_template_directory() . '/assets/partners/' . $partner[1]
			: '';

		if ( $use_core ) {
			ALZ_Partners::import_legacy_partner(
				array(
					'title'      => $partner[0],
					'legal_name' => $partner[0],
					'logo_path'  => $logo_path,
				)
			);
			continue;
		}

		$existing = get_page_by_title( $partner[0], OBJECT, 'alz_partner' );
		if ( $existing ) {
			if ( '' === (string) get_post_meta( $existing->ID, '_alz_partner_legal_name', true ) ) {
				update_post_meta( $existing->ID, '_alz_partner_legal_name', $partner[0] );
			}
			$existing_source = (string) get_post_meta( $existing->ID, '_alz_partner_logo_source', true );
			if ( function_exists( 'alz_core_is_legacy_site_asset_url' ) && alz_core_is_legacy_site_asset_url( $existing_source ) ) {
				delete_post_meta( $existing->ID, '_alz_partner_logo_source' );
			}
			update_post_meta( $existing->ID, '_alz_partner_imported', 'yes' );
			if ( $logo_path && ! has_post_thumbnail( $existing->ID ) ) {
				alzaherah_attach_local_partner_logo( $existing->ID, $partner[0], $logo_path );
			}
			continue;
		}

		$post_id = wp_insert_post( array( 'post_type' => 'alz_partner', 'post_status' => 'publish', 'post_title' => $partner[0] ) );
		if ( ! $post_id || is_wp_error( $post_id ) ) {
			continue;
		}
		update_post_meta( $post_id, '_alz_partner_legal_name', $partner[0] );
		update_post_meta( $post_id, '_alz_partner_imported', 'yes' );
		if ( $logo_path ) {
			alzaherah_attach_local_partner_logo( $post_id, $partner[0], $logo_path );
		}
		if ( has_post_thumbnail( $post_id ) ) {
			update_post_meta( $post_id, '_alz_partner_status', 'approved' );
			update_post_meta( $post_id, '_alz_partner_logo_verified', 'yes' );
			update_post_meta( $post_id, '_alz_partner_review_notes', 'مستورد من قائمة شركاء النجاح المنشورة في الموقع السابق، ويمكن للإدارة إعادة مراجعته أو إيقافه من لوحة المنصة.' );
		} else {
			update_post_meta( $post_id, '_alz_partner_status', 'pending' );
			update_post_meta( $post_id, '_alz_partner_logo_verified', 'no' );
			update_post_meta( $post_id, '_alz_partner_review_notes', 'الجهة منشورة في الموقع السابق دون شعار. يلزم رفع شعار واضح والتحقق منه قبل الاعتماد.' );
		}
	}
	update_option( 'alzaherah_partner_seed_version', 'final-legacy-snapshot' );
}
add_action( 'init', 'alzaherah_seed_partners', 60 );

if ( ! alzaherah_core_owns_partners() ) {
	add_action( 'init', 'alzaherah_register_partner_post_type' );
	add_action( 'add_meta_boxes', 'alzaherah_partner_meta_boxes' );
	add_action( 'save_post_alz_partner', 'alzaherah_save_partner_meta' );
	add_filter( 'manage_alz_partner_posts_columns', 'alzaherah_partner_columns' );
	add_action( 'manage_alz_partner_posts_custom_column', 'alzaherah_partner_column_content', 10, 2 );
	add_action( 'template_redirect', 'alzaherah_handle_partner_submission', 1 );
}

/**
 * إنشاء قائمة السياسات الكاملة في رأس الموقع.
 *
 * @return string
 */
function alzaherah_policy_navigation_markup() {
	if ( ! function_exists( 'alzaherah_policy_definitions' ) ) {
		return '';
	}

	$items = '';
	foreach ( alzaherah_policy_definitions() as $slug => $policy ) {
		if ( 'policy-center' === $slug ) {
			continue;
		}
		$items .= '<li class="menu-item"><a href="' . esc_url( home_url( '/' . $slug . '/' ) ) . '">' . esc_html( $policy['title'] ) . '</a></li>';
	}

	return '<li class="menu-item menu-item-has-children alz-policy-menu"><a href="' . esc_url( home_url( '/policy-center/' ) ) . '">'
		. esc_html__( 'السياسات', 'alzaherah' )
		. '<span class="alz-submenu-caret" aria-hidden="true">⌄</span></a><ul class="sub-menu">' . $items . '</ul></li>';
}

function alzaherah_add_partners_menu_link( $items, $args ) {
	if ( empty( $args->theme_location ) || ! in_array( $args->theme_location, array( 'primary', 'footer' ), true ) ) {
		return $items;
	}
	$url = home_url( '/partners/' );
	if ( false === strpos( $items, $url ) ) {
		$items .= '<li class="menu-item menu-item-partners"><a href="' . esc_url( $url ) . '">' . esc_html__( 'شركاء النجاح', 'alzaherah' ) . '</a></li>';
	}
	return $items;
}
add_filter( 'wp_nav_menu_items', 'alzaherah_add_partners_menu_link', 10, 2 );

/**
 * تثبيت ترتيب القائمة الرئيسية حسب أولوية رحلة الزائر.
 *
 * @param string   $items عناصر القائمة القادمة من ووردبريس.
 * @param stdClass $args  إعدادات القائمة.
 * @return string
 */
function alzaherah_order_primary_navigation( $items, $args ) {
	if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
		return $items;
	}

	$shop_url = function_exists( 'alzaherah_shop_url' ) ? alzaherah_shop_url() : home_url( '/courses/' );
	$training_products_url = class_exists( 'ALZ_Training_Products' ) ? ALZ_Training_Products::page_url() : home_url( '/training-products/' );

	$ordered  = '<li class="menu-item menu-item-home"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'الرئيسية', 'alzaherah' ) . '</a></li>';
	$ordered .= '<li class="menu-item menu-item-courses"><a href="' . esc_url( $shop_url ) . '">' . esc_html__( 'الدورات', 'alzaherah' ) . '</a></li>';
	$ordered .= '<li class="menu-item menu-item-training-products"><a href="' . esc_url( $training_products_url ) . '">' . esc_html__( 'المنتجات التدريبية', 'alzaherah' ) . '</a></li>';
	$ordered .= alzaherah_policy_navigation_markup();
	$ordered .= '<li class="menu-item menu-item-partners"><a href="' . esc_url( home_url( '/partners/' ) ) . '">' . esc_html__( 'شركاء النجاح', 'alzaherah' ) . '</a></li>';
	$ordered .= '<li class="menu-item menu-item-about"><a href="' . esc_url( home_url( '/about/' ) ) . '">' . esc_html__( 'عن المركز', 'alzaherah' ) . '</a></li>';
	$ordered .= '<li class="menu-item menu-item-contact"><a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'تواصل معنا', 'alzaherah' ) . '</a></li>';

	return $ordered;
}
add_filter( 'wp_nav_menu_items', 'alzaherah_order_primary_navigation', 1000, 2 );
