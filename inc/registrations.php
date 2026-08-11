<?php
/**
 * شاشة "التسجيلات": متابعة وإدارة تسجيلات المتدربين.
 *
 * جدول بكل التسجيلات مع فلترة بالحالة وبحث بالرقم أو البريد،
 * الشاشة للعرض والبحث فقط؛ إدارة الدفع والإلغاء موحدة في لوحة المنصة الأمامية.
 *
 * التركيب: ضع الملف في /inc ثم أضف في نهاية functions.php:
 * require_once get_template_directory() . '/inc/registrations.php';
 *
 * @package Alzaherah
 * @since   2.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * تسجيل الشاشة تحت قائمة "إدارة المنصة".
 */
function alzaherah_registrations_menu() {
	if ( defined( 'ALZ_CORE_VERSION' ) && version_compare( ALZ_CORE_VERSION, '2.5.0', '>=' ) ) {
		return;
	}
	add_submenu_page(
		'alzaherah-platform',
		__( 'التسجيلات', 'alzaherah' ),
		__( 'التسجيلات', 'alzaherah' ),
		'alz_view_course_enrollments',
		'alzaherah-registrations',
		'alzaherah_registrations_screen'
	);
}
add_action( 'admin_menu', 'alzaherah_registrations_menu', 15 );

/**
 * تحويل الرابط القديم لشاشة التسجيلات إلى لوحة المنصة عند وجود الـCore.
 */
function alzaherah_redirect_legacy_registrations_screen() {
	if ( ! is_admin() || ! defined( 'ALZ_CORE_VERSION' ) || version_compare( ALZ_CORE_VERSION, '2.5.0', '<' ) ) {
		return;
	}
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'alzaherah-registrations' !== $page ) {
		return;
	}
	if ( ! current_user_can( 'alz_view_course_enrollments' ) && ! current_user_can( 'alz_access_platform_dashboard' ) ) {
		return;
	}
	$section = current_user_can( 'alz_view_course_enrollments' ) ? 'enrollments' : 'overview';
	$url     = class_exists( 'ALZ_Frontend_Dashboard' )
		? add_query_arg( 'section', $section, ALZ_Frontend_Dashboard::page_url() )
		: ( function_exists( 'alz_front_dashboard_url' ) ? alz_front_dashboard_url() : admin_url() );
	wp_safe_redirect( $url );
	exit;
}
add_action( 'admin_init', 'alzaherah_redirect_legacy_registrations_screen', 1 );

/**
 * الحالات المعروضة في الفلتر.
 *
 * @return array
 */
function alzaherah_registration_statuses() {
	return array(
		''              => __( 'كل الحالات', 'alzaherah' ),
		'wc-pending'    => __( 'بانتظار الدفع', 'alzaherah' ),
		'wc-on-hold'    => __( 'قيد المراجعة', 'alzaherah' ),
		'wc-processing' => __( 'مدفوع (قيد التنفيذ)', 'alzaherah' ),
		'wc-completed'  => __( 'مكتمل', 'alzaherah' ),
		'wc-cancelled'  => __( 'ملغي', 'alzaherah' ),
		'wc-refunded'   => __( 'مسترجع', 'alzaherah' ),
		'wc-failed'     => __( 'فشل الدفع', 'alzaherah' ),
	);
}

/**
 * شاشة التسجيلات.
 */
function alzaherah_registrations_screen() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'التسجيلات', 'alzaherah' ) . '</h1><p>' . esc_html__( 'فعّل WooCommerce أولًا.', 'alzaherah' ) . '</p></div>';
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- قراءة فلاتر عرض فقط.
	$status = isset( $_GET['reg_status'] ) ? sanitize_key( $_GET['reg_status'] ) : '';
	$search = isset( $_GET['reg_search'] ) ? sanitize_text_field( wp_unslash( $_GET['reg_search'] ) ) : '';
	$paged  = isset( $_GET['reg_page'] ) ? max( 1, absint( $_GET['reg_page'] ) ) : 1;
	$msg    = isset( $_GET['alz_msg'] ) ? sanitize_key( $_GET['alz_msg'] ) : '';
	// phpcs:enable

	$per_page = 20;
	$statuses = alzaherah_registration_statuses();

	// بناء الاستعلام.
	$args = array(
		'limit'    => $per_page,
		'paged'    => $paged,
		'paginate' => true,
		'orderby'  => 'date',
		'order'    => 'DESC',
	);

	if ( $status && isset( $statuses[ $status ] ) ) {
		$args['status'] = array( $status );
	}

	if ( '' !== $search ) {
		if ( is_numeric( $search ) ) {
			$args['post__in'] = array( absint( $search ) );
			$args['include']  = array( absint( $search ) );
		} elseif ( is_email( $search ) ) {
			$args['billing_email'] = $search;
		} else {
			$args['search'] = '*' . $search . '*';
		}
	}

	$results = wc_get_orders( $args );
	$orders  = $results->orders;
	$total   = (int) $results->total;
	$pages   = (int) $results->max_num_pages;

	$base_url = admin_url( 'admin.php?page=alzaherah-registrations' );
	?>

	<div class="wrap">
		<h1><?php esc_html_e( 'التسجيلات', 'alzaherah' ); ?></h1>
		<p style="color:#646970"><?php esc_html_e( 'هذه الشاشة للعرض والبحث. لتأكيد دفعة يدوية أو إلغاء طلب غير مدفوع استخدم قسم «الطلبات والمدفوعات» في لوحة المنصة حتى يبقى مسار الاعتماد واحدًا ومسجلًا.', 'alzaherah' ); ?></p>

		<?php if ( 'updated' === $msg ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'تم تحديث حالة التسجيل بنجاح.', 'alzaherah' ); ?></p></div>
		<?php elseif ( 'error' === $msg ) : ?>
			<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'تعذر تحديث التسجيل.', 'alzaherah' ); ?></p></div>
		<?php endif; ?>

		<!-- الفلاتر -->
		<form method="get" style="margin:14px 0 18px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
			<input type="hidden" name="page" value="alzaherah-registrations">

			<select name="reg_status">
				<?php foreach ( $statuses as $status_key => $status_label ) : ?>
					<option value="<?php echo esc_attr( $status_key ); ?>" <?php selected( $status, $status_key ); ?>>
						<?php echo esc_html( $status_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<input type="search" name="reg_search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'رقم التسجيل أو البريد أو الاسم…', 'alzaherah' ); ?>" style="min-width:260px">

			<button class="button"><?php esc_html_e( 'تصفية', 'alzaherah' ); ?></button>

			<?php if ( $status || $search ) : ?>
				<a class="button button-link" href="<?php echo esc_url( $base_url ); ?>"><?php esc_html_e( 'إعادة تعيين', 'alzaherah' ); ?></a>
			<?php endif; ?>

			<span style="margin-inline-start:auto; color:#646970">
				<?php
				printf(
					/* translators: %s: عدد النتائج */
					esc_html__( 'النتائج: %s', 'alzaherah' ),
					esc_html( number_format_i18n( $total ) )
				);
				?>
			</span>
		</form>

		<?php if ( empty( $orders ) ) : ?>

			<p><?php esc_html_e( 'لا توجد تسجيلات مطابقة.', 'alzaherah' ); ?></p>

		<?php else : ?>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'التسجيل', 'alzaherah' ); ?></th>
						<th><?php esc_html_e( 'المتدرب', 'alzaherah' ); ?></th>
						<th><?php esc_html_e( 'الدورة', 'alzaherah' ); ?></th>
						<th><?php esc_html_e( 'المبلغ', 'alzaherah' ); ?></th>
						<th><?php esc_html_e( 'الحالة', 'alzaherah' ); ?></th>
						<th><?php esc_html_e( 'التاريخ', 'alzaherah' ); ?></th>
						<th><?php esc_html_e( 'إجراءات', 'alzaherah' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $orders as $order ) : ?>
						<?php
						$order_id     = $order->get_id();
						$order_status = $order->get_status();

						$courses = array();
						foreach ( $order->get_items() as $item ) {
							$courses[] = $item->get_name();
						}

						$platform_orders_url = class_exists( 'ALZ_Frontend_Dashboard' ) ? add_query_arg( 'section', 'orders', ALZ_Frontend_Dashboard::page_url() ) : '';
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">
									<strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong>
								</a>
							</td>
							<td>
								<?php echo esc_html( trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ); ?><br>
								<small>
									<?php echo esc_html( $order->get_billing_phone() ); ?>
									<?php if ( $order->get_billing_email() ) : ?>
										· <?php echo esc_html( $order->get_billing_email() ); ?>
									<?php endif; ?>
								</small>
							</td>
							<td><?php echo esc_html( implode( '، ', $courses ) ); ?></td>
							<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
							<td><?php echo esc_html( wc_get_order_status_name( $order_status ) ); ?></td>
							<td><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'Y/m/d' ) : '—' ); ?></td>
							<td><?php if ( $platform_orders_url ) : ?><a class="button button-small" href="<?php echo esc_url( $platform_orders_url ); ?>"><?php esc_html_e( 'إدارة من لوحة المنصة', 'alzaherah' ); ?></a><?php else : ?><?php esc_html_e( 'ثبّت إضافة المنصة لإدارة الطلب', 'alzaherah' ); ?><?php endif; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav"><div class="tablenav-pages" style="margin:12px 0">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'reg_page', '%#%', $base_url . ( $status ? '&reg_status=' . $status : '' ) . ( $search ? '&reg_search=' . rawurlencode( $search ) : '' ) ),
								'format'    => '',
								'current'   => $paged,
								'total'     => $pages,
								'prev_text' => __( '‹ السابق', 'alzaherah' ),
								'next_text' => __( 'التالي ›', 'alzaherah' ),
							)
						)
					);
					?>
				</div></div>
			<?php endif; ?>

		<?php endif; ?>
	</div>
	<?php
}

/**
 * تعطيل روابط تغيير الحالة القديمة بعد توحيد الاعتماد في لوحة المنصة.
 */
function alzaherah_handle_set_reg_status() {
	$redirect = class_exists( 'ALZ_Frontend_Dashboard' ) ? add_query_arg( 'section', 'orders', ALZ_Frontend_Dashboard::page_url() ) : admin_url( 'admin.php?page=alzaherah-registrations&alz_msg=error' );
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_alzaherah_set_reg_status', 'alzaherah_handle_set_reg_status' );
