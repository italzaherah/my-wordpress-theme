<?php
/**
 * لوحة إدارة المنصة + تحويل مسميات المتجر إلى مسميات تدريبية.
 *
 * يضيف هذا الملف:
 * ١) إعادة تسمية "المنتجات" إلى "الدورات" في كامل لوحة التحكم.
 * ٢) لوحة "إدارة المنصة": إحصاءات، آخر التسجيلات، وإجراءات سريعة.
 * ٣) تعريب قائمة حساب المتدرب بمسميات تدريبية.
 *
 * التركيب: ضع الملف في مجلد /inc داخل القالب ثم أضف في نهاية functions.php:
 * require_once get_template_directory() . '/inc/platform.php';
 *
 * @package Alzaherah
 * @since   2.3.0
 */

defined( 'ABSPATH' ) || exit;

/* ============================================================
   ١) إعادة تسمية "المنتجات" إلى "الدورات" في لوحة التحكم
============================================================ */

/**
 * تغيير مسميات نوع المحتوى product إلى مسميات الدورات.
 *
 * @param array $args إعدادات نوع المحتوى.
 * @return array
 */
function alzaherah_rename_product_labels( $args ) {
	$args['labels'] = array_merge(
		(array) $args['labels'],
		array(
			'name'               => __( 'الدورات', 'alzaherah' ),
			'singular_name'      => __( 'دورة', 'alzaherah' ),
			'menu_name'          => __( 'الدورات', 'alzaherah' ),
			'all_items'          => __( 'كل الدورات', 'alzaherah' ),
			'add_new'            => __( 'إضافة دورة', 'alzaherah' ),
			'add_new_item'       => __( 'إضافة دورة جديدة', 'alzaherah' ),
			'edit_item'          => __( 'تعديل الدورة', 'alzaherah' ),
			'new_item'           => __( 'دورة جديدة', 'alzaherah' ),
			'view_item'          => __( 'عرض الدورة', 'alzaherah' ),
			'search_items'       => __( 'ابحث في الدورات', 'alzaherah' ),
			'not_found'          => __( 'لا توجد دورات', 'alzaherah' ),
			'not_found_in_trash' => __( 'لا توجد دورات في السلة', 'alzaherah' ),
		)
	);

	return $args;
}
if ( ! ( defined( 'ALZ_CORE_VERSION' ) && version_compare( ALZ_CORE_VERSION, '2.6.0', '>=' ) && class_exists( 'ALZ_Courses' ) ) ) {
	add_filter( 'woocommerce_register_post_type_product', 'alzaherah_rename_product_labels' );
}

/* ============================================================
   ٢) لوحة "إدارة المنصة"
============================================================ */

/**
 * رابط شاشة التسجيلات (الطلبات) مع دعم نظام HPOS الحديث والقديم.
 *
 * @return string
 */
function alzaherah_orders_admin_url() {
	if (
		class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) &&
		\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
	) {
		return admin_url( 'admin.php?page=wc-orders' );
	}
	return admin_url( 'edit.php?post_type=shop_order' );
}

/**
 * إضافة قائمة "إدارة المنصة" في لوحة التحكم.
 */
function alzaherah_platform_menu() {
	if ( defined( 'ALZ_CORE_VERSION' ) && version_compare( ALZ_CORE_VERSION, '2.5.0', '>=' ) ) {
		return;
	}
	add_menu_page(
		__( 'إدارة المنصة', 'alzaherah' ),
		__( 'إدارة المنصة', 'alzaherah' ),
		'alz_access_platform_dashboard',
		'alzaherah-platform',
		'alzaherah_platform_screen',
		'dashicons-welcome-learn-more',
		2
	);
}
add_action( 'admin_menu', 'alzaherah_platform_menu' );

/**
 * شاشة لوحة إدارة المنصة.
 */
function alzaherah_platform_screen() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'إدارة المنصة', 'alzaherah' ) . '</h1>';
		echo '<p>' . esc_html__( 'فعّل إضافة WooCommerce لعرض بيانات المنصة.', 'alzaherah' ) . '</p></div>';
		return;
	}

	/* ---------- الإحصاءات ---------- */

	$course_counts     = wp_count_posts( 'product' );
	$published_courses = isset( $course_counts->publish ) ? (int) $course_counts->publish : 0;

	$month_start = gmdate( 'Y-m-01 00:00:00' );

	// تسجيلات الشهر الحالي (الطلبات المدفوعة أو قيد المعالجة أو المكتملة).
	$month_orders = wc_get_orders(
		array(
			'limit'        => 1,
			'paginate'     => true,
			'status'       => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ),
			'date_created' => '>=' . strtotime( $month_start ),
		)
	);
	$month_registrations = is_object( $month_orders ) && isset( $month_orders->total ) ? (int) $month_orders->total : 0;

	// إيراد الشهر (الطلبات المدفوعة فقط).
	$month_revenue = 0.0;
	$orders_page   = 1;
	do {
		$month_paid_orders = wc_get_orders(
			array(
				'limit'        => 200,
				'paged'        => $orders_page,
				'status'       => array( 'wc-processing', 'wc-completed' ),
				'date_created' => '>=' . strtotime( $month_start ),
			)
		);
		foreach ( $month_paid_orders as $paid_order ) {
			$month_revenue += (float) $paid_order->get_total();
		}
		$orders_page++;
	} while ( 200 === count( $month_paid_orders ) );

	// عدد المتدربين (المستخدمون بدور عميل).
	$customer_count = 0;
	$user_counts    = count_users();
	if ( isset( $user_counts['avail_roles']['customer'] ) ) {
		$customer_count = (int) $user_counts['avail_roles']['customer'];
	}

	// آخر ١٠ تسجيلات.
	$recent_orders = wc_get_orders(
		array(
			'limit'   => 10,
			'orderby' => 'date',
			'order'   => 'DESC',
		)
	);

	$orders_url    = alzaherah_orders_admin_url();
	$currency_code = get_woocommerce_currency();
	?>

	<div class="wrap alz-platform">

		<h1><?php esc_html_e( 'إدارة المنصة', 'alzaherah' ); ?></h1>
		<p class="alz-platform-sub"><?php esc_html_e( 'نظرة سريعة على الدورات والتسجيلات، مع اختصارات لأكثر المهام استخدامًا.', 'alzaherah' ); ?></p>

		<!-- الإجراءات السريعة -->
		<div class="alz-actions">
			<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>">
				<?php esc_html_e( '＋ إضافة دورة جديدة', 'alzaherah' ); ?>
			</a>
			<a class="button button-hero" href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>">
				<?php esc_html_e( 'إدارة الدورات', 'alzaherah' ); ?>
			</a>
			<a class="button button-hero" href="<?php echo esc_url( $orders_url ); ?>">
				<?php esc_html_e( 'التسجيلات (الطلبات)', 'alzaherah' ); ?>
			</a>
			<a class="button button-hero" href="<?php echo esc_url( admin_url( 'users.php?role=customer' ) ); ?>">
				<?php esc_html_e( 'المتدربون', 'alzaherah' ); ?>
			</a>
		</div>

		<!-- بطاقات الإحصاءات -->
		<div class="alz-stats">

			<div class="alz-stat">
				<span class="alz-stat-number"><?php echo esc_html( number_format_i18n( $published_courses ) ); ?></span>
				<span class="alz-stat-label"><?php esc_html_e( 'دورة منشورة', 'alzaherah' ); ?></span>
			</div>

			<div class="alz-stat">
				<span class="alz-stat-number"><?php echo esc_html( number_format_i18n( $month_registrations ) ); ?></span>
				<span class="alz-stat-label"><?php esc_html_e( 'تسجيل هذا الشهر', 'alzaherah' ); ?></span>
			</div>

			<div class="alz-stat">
				<span class="alz-stat-number"><?php echo esc_html( number_format_i18n( $month_revenue, 2 ) ); ?> <small><?php echo esc_html( $currency_code ); ?></small></span>
				<span class="alz-stat-label"><?php esc_html_e( 'إيراد هذا الشهر (مدفوع)', 'alzaherah' ); ?></span>
			</div>

			<div class="alz-stat">
				<span class="alz-stat-number"><?php echo esc_html( number_format_i18n( $customer_count ) ); ?></span>
				<span class="alz-stat-label"><?php esc_html_e( 'متدرب مسجل', 'alzaherah' ); ?></span>
			</div>

		</div>

		<!-- آخر التسجيلات -->
		<h2><?php esc_html_e( 'آخر التسجيلات', 'alzaherah' ); ?></h2>

		<?php if ( empty( $recent_orders ) ) : ?>

			<p><?php esc_html_e( 'لا توجد تسجيلات بعد. عند تسجيل أول متدرب سيظهر هنا.', 'alzaherah' ); ?></p>

		<?php else : ?>

			<table class="widefat striped alz-orders-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'رقم التسجيل', 'alzaherah' ); ?></th>
						<th><?php esc_html_e( 'المتدرب', 'alzaherah' ); ?></th>
						<th><?php esc_html_e( 'الدورة', 'alzaherah' ); ?></th>
						<th><?php esc_html_e( 'المبلغ', 'alzaherah' ); ?></th>
						<th><?php esc_html_e( 'الحالة', 'alzaherah' ); ?></th>
						<th><?php esc_html_e( 'التاريخ', 'alzaherah' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent_orders as $order ) : ?>
						<?php
						// أسماء الدورات في الطلب.
						$course_names = array();
						foreach ( $order->get_items() as $item ) {
							$course_names[] = $item->get_name();
						}
						$edit_url = $order->get_edit_order_url();
						?>
						<tr>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>">
									#<?php echo esc_html( $order->get_order_number() ); ?>
								</a>
							</td>
							<td><?php echo esc_html( trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) ); ?></td>
							<td><?php echo esc_html( implode( '، ', $course_names ) ); ?></td>
							<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
							<td><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
							<td><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'Y/m/d — H:i' ) : '—' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

		<?php endif; ?>

		<!-- دليل سريع -->
		<div class="alz-guide">
			<h2><?php esc_html_e( 'دليل سريع', 'alzaherah' ); ?></h2>
			<ul>
				<li><strong><?php esc_html_e( 'إيقاف التسجيل في دورة:', 'alzaherah' ); ?></strong> <?php esc_html_e( 'افتح الدورة ← المخزون ← اجعل الحالة «نفذت الكمية»، أو حوّل الدورة إلى «مسودة» لإخفائها بالكامل.', 'alzaherah' ); ?></li>
				<li><strong><?php esc_html_e( 'إلغاء تسجيل متدرب:', 'alzaherah' ); ?></strong> <?php esc_html_e( 'افتح التسجيل من قائمة التسجيلات ← غيّر الحالة إلى «ملغي»، أو «مسترجع» إن أعدت المبلغ.', 'alzaherah' ); ?></li>
				<li><strong><?php esc_html_e( 'تعديل بيانات متدرب:', 'alzaherah' ); ?></strong> <?php esc_html_e( 'من قائمة المتدربين افتح المستخدم لتعديل بياناته الأساسية، أو عدّل بيانات الفوترة داخل التسجيل نفسه.', 'alzaherah' ); ?></li>
			</ul>
		</div>

	</div>

	<style>
		.alz-platform .alz-platform-sub { color:#646970; margin-top:-8px; }
		.alz-actions { display:flex; flex-wrap:wrap; gap:10px; margin:18px 0 24px; }
		.alz-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:28px; }
		.alz-stat { background:#fff; border:1px solid #dcdcde; border-radius:10px; padding:18px 20px; }
		.alz-stat-number { display:block; font-size:28px; font-weight:700; line-height:1.2; }
		.alz-stat-number small { font-size:14px; font-weight:400; color:#646970; }
		.alz-stat-label { color:#646970; }
		.alz-orders-table { margin-top:8px; }
		.alz-guide { background:#fff; border:1px solid #dcdcde; border-radius:10px; padding:6px 20px 14px; margin-top:28px; max-width:900px; }
		.alz-guide ul { list-style:disc; padding-inline-start:20px; }
		.alz-guide li { margin-bottom:8px; line-height:1.7; }
	</style>

	<?php
}

/* ============================================================
   ٣) تعريب قائمة حساب المتدرب بمسميات تدريبية
============================================================ */

/**
 * إعادة تسمية عناصر قائمة "حسابي" وإخفاء غير اللازم.
 *
 * @param array $items عناصر القائمة.
 * @return array
 */
function alzaherah_account_menu_items( $items ) {

	if ( isset( $items['dashboard'] ) ) {
		$items['dashboard'] = __( 'دوراتي', 'alzaherah' );
	}
	if ( isset( $items['orders'] ) ) {
		$items['orders'] = __( 'طلباتي', 'alzaherah' );
	}
	unset( $items['edit-address'] );
	if ( isset( $items['edit-account'] ) ) {
		$items['edit-account'] = __( 'بيانات الحساب', 'alzaherah' );
	}
	if ( isset( $items['customer-logout'] ) ) {
		$items['customer-logout'] = __( 'تسجيل الخروج', 'alzaherah' );
	}

	if ( isset( $items['downloads'] ) ) {
		if ( class_exists( 'ALZ_Training_Products' ) ) {
			$items['downloads'] = __( 'مشترياتي', 'alzaherah' );
		} else {
			unset( $items['downloads'] );
		}
	}

	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'alzaherah_account_menu_items' );

/**
 * تعريب عنوان قسم "تسجيلاتي" داخل صفحة الحساب.
 *
 * @param string $title العنوان الأصلي.
 * @param string $endpoint نقطة النهاية.
 * @return string
 */
function alzaherah_account_endpoint_titles( $title, $endpoint ) {
	if ( 'orders' === $endpoint ) {
		return __( 'طلباتي', 'alzaherah' );
	}
	return $title;
}
add_filter( 'woocommerce_endpoint_orders_title', 'alzaherah_account_endpoint_titles', 10, 2 );

/** تسمية صفحة تنزيلات WooCommerce بما يناسب المنتجات التدريبية الرقمية. */
function alzaherah_downloads_endpoint_title( $title ) {
	return class_exists( 'ALZ_Training_Products' ) ? __( 'مشترياتي', 'alzaherah' ) : $title;
}
add_filter( 'woocommerce_endpoint_downloads_title', 'alzaherah_downloads_endpoint_title' );

/** Arabic, transaction-oriented labels for the orders table. */
function alzaherah_account_orders_columns( $columns ) {
	$labels = array(
		'order-number'  => __( 'الطلب', 'alzaherah' ),
		'order-date'    => __( 'التاريخ', 'alzaherah' ),
		'order-status'  => __( 'معالجة الطلب', 'alzaherah' ),
		'order-total'   => __( 'الإجمالي', 'alzaherah' ),
		'order-actions' => __( 'الإجراء', 'alzaherah' ),
	);
	foreach ( $labels as $key => $label ) {
		if ( isset( $columns[ $key ] ) ) {
			$columns[ $key ] = $label;
		}
	}
	return $columns;
}
add_filter( 'woocommerce_account_orders_columns', 'alzaherah_account_orders_columns', 20 );

/** Arabic labels for purchased digital files. */
function alzaherah_account_downloads_columns( $columns ) {
	$labels = array(
		'download-product'   => __( 'المنتج', 'alzaherah' ),
		'download-file'      => __( 'ملف التنزيل', 'alzaherah' ),
		'download-remaining' => __( 'التنزيلات المتبقية', 'alzaherah' ),
		'download-expires'   => __( 'انتهاء الصلاحية', 'alzaherah' ),
	);
	foreach ( $labels as $key => $label ) {
		if ( isset( $columns[ $key ] ) ) {
			$columns[ $key ] = $label;
		}
	}
	return $columns;
}
add_filter( 'woocommerce_account_downloads_columns', 'alzaherah_account_downloads_columns', 20 );

/** Explain the semantic boundary between courses, purchases and transactions. */
function alzaherah_before_account_orders() {
	?>
	<header class="alz-account-endpoint-intro">
		<span><?php esc_html_e( 'المعاملات وحالات الدفع', 'alzaherah' ); ?></span>
		<h2><?php esc_html_e( 'طلباتي', 'alzaherah' ); ?></h2>
		<p><?php esc_html_e( 'جميع الطلبات التي أنشأتها، مع حالة معالجة كل طلب وإجراءات عرضه أو إكمال دفعه عند الحاجة.', 'alzaherah' ); ?></p>
	</header>
	<?php
}
add_action( 'woocommerce_before_account_orders', 'alzaherah_before_account_orders', 5 );

function alzaherah_before_account_downloads() {
	?>
	<header class="alz-account-endpoint-intro">
		<span><?php esc_html_e( 'المنتجات الرقمية المملوكة', 'alzaherah' ); ?></span>
		<h2><?php esc_html_e( 'مشترياتي', 'alzaherah' ); ?></h2>
		<p><?php esc_html_e( 'تظهر هنا ملفات المنتجات الرقمية التي ثبت دفعها وأصبحت صلاحية تنزيلها متاحة لحسابك.', 'alzaherah' ); ?></p>
	</header>
	<?php
}
add_action( 'woocommerce_before_account_downloads', 'alzaherah_before_account_downloads', 5 );
