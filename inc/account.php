<?php
/**
 * لوحة حساب المتدرب: محتوى "نظرة عامة" مخصص.
 *
 * يستبدل ترحيب WooCommerce الافتراضي الفارغ بلوحة فعلية:
 * ترحيب باسم المتدرب + إحصاءاته + آخر تسجيلاته + إجراءات سريعة.
 *
 * @package Alzaherah
 * @since   2.3.0
 */

defined( 'ABSPATH' ) || exit;

// إزالة الترحيب الافتراضي (سطرا "مرحبا فلان" وروابط اللوحة).
remove_action( 'woocommerce_account_dashboard', 'woocommerce_account_dashboard' );

/**
 * هل الطلب يحتوي عنصر دورة؟ Theme fallback عند غياب Core.
 *
 * @param WC_Order $order الطلب.
 * @return bool
 */
function alzaherah_order_has_course_items( $order ) {
	if ( function_exists( 'alz_core_order_has_course_items' ) ) {
		return alz_core_order_has_course_items( $order );
	}
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return false;
	}
	foreach ( $order->get_items( 'line_item' ) as $item ) {
		$product_id = absint( $item->get_product_id() );
		if ( $product_id && function_exists( 'alzaherah_is_confirmed_course_product' ) && alzaherah_is_confirmed_course_product( $product_id ) ) {
			return true;
		}
		if ( $product_id && 'yes' === (string) get_post_meta( $product_id, '_alz_is_course', true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * هل التسجيل مؤكد حاليًا؟ Theme fallback: دورة + date_paid + ليس cancelled/refunded/failed.
 *
 * @param WC_Order $order الطلب.
 * @return bool
 */
function alzaherah_order_registration_is_confirmed( $order ) {
	if ( function_exists( 'alz_core_order_registration_is_confirmed' ) ) {
		return alz_core_order_registration_is_confirmed( $order );
	}
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return false;
	}
	if ( ! alzaherah_order_has_course_items( $order ) ) {
		return false;
	}
	if ( in_array( $order->get_status(), array( 'cancelled', 'refunded', 'failed' ), true ) ) {
		return false;
	}
	$paid_at = $order->get_date_paid();
	return $paid_at && $paid_at->getTimestamp() > 0;
}

/**
 * تسمية حالة التسجيل في لوحة المتدرب.
 *
 * @param WC_Order $order الطلب.
 * @return array{0:string,1:bool} label + is_confirmed.
 */
function alzaherah_account_registration_status_label( $order ) {
	$status = $order->get_status();
	if ( 'refunded' === $status ) {
		return array( __( 'مسترجع', 'alzaherah' ), false );
	}
	if ( 'cancelled' === $status ) {
		return array( __( 'ملغي', 'alzaherah' ), false );
	}
	if ( 'failed' === $status ) {
		return array( __( 'فشل الدفع', 'alzaherah' ), false );
	}
	if ( alzaherah_order_registration_is_confirmed( $order ) ) {
		return array( __( 'تسجيل مؤكد', 'alzaherah' ), true );
	}
	if ( in_array( $status, array( 'pending', 'on-hold' ), true ) ) {
		return array( __( 'بانتظار الدفع / المراجعة', 'alzaherah' ), false );
	}
	return array( wc_get_order_status_name( $status ), false );
}

/**
 * محتوى لوحة المتدرب.
 */
function alzaherah_account_dashboard() {

	$user = wp_get_current_user();
	if ( ! $user || 0 === $user->ID ) {
		return;
	}

	$display_name = $user->first_name ? $user->first_name : $user->display_name;

	if ( function_exists( 'alz_core_count_customer_course_registrations' ) ) {
		$total_registrations = alz_core_count_customer_course_registrations( $user->ID );
	} else {
		$total_registrations = 0;
		$page                = 1;
		do {
			$batch = wc_get_orders(
				array(
					'customer_id' => $user->ID,
					'limit'       => 100,
					'paged'       => $page,
					'return'      => 'objects',
					'orderby'     => 'date',
					'order'       => 'DESC',
				)
			);
			if ( ! $batch ) {
				break;
			}
			foreach ( $batch as $order ) {
				if ( alzaherah_order_has_course_items( $order ) ) {
					$total_registrations++;
				}
			}
			$page++;
		} while ( 100 === count( $batch ) );
	}

	if ( function_exists( 'alz_core_count_customer_confirmed_registrations' ) ) {
		$active_registrations = alz_core_count_customer_confirmed_registrations( $user->ID );
	} else {
		$active_registrations = 0;
		$page                 = 1;
		do {
			$batch = wc_get_orders(
				array(
					'customer_id' => $user->ID,
					'limit'       => 100,
					'paged'       => $page,
					'return'      => 'objects',
					'orderby'     => 'date',
					'order'       => 'DESC',
				)
			);
			if ( ! $batch ) {
				break;
			}
			foreach ( $batch as $order ) {
				if ( alzaherah_order_has_course_items( $order ) && alzaherah_order_registration_is_confirmed( $order ) ) {
					$active_registrations++;
				}
			}
			$page++;
		} while ( 100 === count( $batch ) );
	}

	if ( function_exists( 'alz_core_get_customer_recent_course_orders' ) ) {
		$recent = alz_core_get_customer_recent_course_orders( $user->ID, 3 );
	} else {
		$recent = array();
		$page   = 1;
		do {
			$batch = wc_get_orders(
				array(
					'customer_id' => $user->ID,
					'limit'       => 50,
					'paged'       => $page,
					'return'      => 'objects',
					'orderby'     => 'date',
					'order'       => 'DESC',
				)
			);
			if ( ! $batch ) {
				break;
			}
			foreach ( $batch as $order ) {
				if ( alzaherah_order_has_course_items( $order ) ) {
					$recent[] = $order;
					if ( count( $recent ) >= 3 ) {
						break 2;
					}
				}
			}
			$page++;
		} while ( 50 === count( $batch ) );
	}
	?>

	<div class="trainee-dashboard">

		<!-- الترحيب -->
		<div class="trainee-welcome">
			<h2>
				<?php
				printf(
					/* translators: %s: اسم المتدرب */
					esc_html__( 'أهلًا %s 👋', 'alzaherah' ),
					esc_html( $display_name )
				);
				?>
			</h2>
			<p><?php esc_html_e( 'من لوحتك تتابع تسجيلاتك وحالة كل دورة، وتحدّث بياناتك في أي وقت.', 'alzaherah' ); ?></p>
		</div>

		<!-- الإحصاءات -->
		<div class="trainee-stats">
			<div class="trainee-stat">
				<strong><?php echo esc_html( number_format_i18n( $total_registrations ) ); ?></strong>
				<span><?php esc_html_e( 'إجمالي التسجيلات', 'alzaherah' ); ?></span>
			</div>
			<div class="trainee-stat">
				<strong><?php echo esc_html( number_format_i18n( $active_registrations ) ); ?></strong>
				<span><?php esc_html_e( 'تسجيلات مؤكدة', 'alzaherah' ); ?></span>
			</div>
		</div>

		<?php if ( ! empty( $recent ) ) : ?>

			<!-- آخر التسجيلات -->
			<div class="trainee-recent">
				<div class="trainee-recent-head">
					<h3><?php esc_html_e( 'آخر تسجيلاتك', 'alzaherah' ); ?></h3>
					<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'orders' ) ); ?>">
						<?php esc_html_e( 'عرض الكل', 'alzaherah' ); ?>
					</a>
				</div>

				<?php foreach ( $recent as $order ) : ?>
					<?php
					if ( function_exists( 'alz_core_order_course_item_names' ) ) {
						$course_names = alz_core_order_course_item_names( $order );
					} else {
						$course_names = array();
						foreach ( $order->get_items( 'line_item' ) as $item ) {
							$product_id = absint( $item->get_product_id() );
							if ( $product_id && function_exists( 'alzaherah_is_confirmed_course_product' ) && alzaherah_is_confirmed_course_product( $product_id ) ) {
								$course_names[] = $item->get_name();
							} elseif ( $product_id && 'yes' === (string) get_post_meta( $product_id, '_alz_is_course', true ) ) {
								$course_names[] = $item->get_name();
							}
						}
					}
					if ( ! $course_names ) {
						continue;
					}
					list( $status_label, $confirmed ) = alzaherah_account_registration_status_label( $order );
					$status_key = $order->get_status();
					?>
					<a class="trainee-order" href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
						<div class="trainee-order-main">
							<strong><?php echo esc_html( implode( '، ', $course_names ) ); ?></strong>
							<small>
								#<?php echo esc_html( $order->get_order_number() ); ?>
								·
								<?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( 'Y/m/d' ) : '' ); ?>
							</small>
						</div>
						<span class="trainee-order-status status-<?php echo esc_attr( $status_key ); ?><?php echo $confirmed ? ' is-confirmed' : ''; ?>">
							<?php echo esc_html( $status_label ); ?>
						</span>
					</a>
				<?php endforeach; ?>
			</div>

		<?php else : ?>

			<!-- لا توجد تسجيلات بعد -->
			<div class="trainee-empty">
				<h3><?php esc_html_e( 'لم تسجّل في أي دورة بعد', 'alzaherah' ); ?></h3>
				<p><?php esc_html_e( 'ابدأ رحلتك الآن — تصفّح البرامج المتاحة واحجز مقعدك خلال دقائق.', 'alzaherah' ); ?></p>
			</div>

		<?php endif; ?>

		<!-- إجراءات سريعة -->
		<div class="trainee-actions">
			<a class="btn btn-primary" href="<?php echo esc_url( alzaherah_shop_url() ); ?>">
				<?php esc_html_e( 'تصفّح الدورات', 'alzaherah' ); ?>
			</a>
			<a class="btn btn-secondary" href="<?php echo esc_url( wc_get_account_endpoint_url( 'edit-account' ) ); ?>">
				<?php esc_html_e( 'تحديث بياناتي', 'alzaherah' ); ?>
			</a>
		</div>

	</div>

	<?php
}
add_action( 'woocommerce_account_dashboard', 'alzaherah_account_dashboard' );

/**
 * تنبيه واضح للعميل عند فتح طلب ملغي، بصرف النظر عن طريقة الإلغاء.
 *
 * @param int $order_id رقم الطلب.
 */
function alzaherah_cancelled_order_customer_notice( $order_id ) {
	$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : false;
	if ( ! $order || 'cancelled' !== $order->get_status() ) {
		return;
	}
	?>
	<section class="alz-order-status-alert is-cancelled" role="alert" aria-labelledby="alz-cancelled-order-title">
		<h2 id="alz-cancelled-order-title"><?php esc_html_e( 'حالة الطلب: ملغي', 'alzaherah' ); ?></h2>
		<p><?php esc_html_e( 'تم إلغاء هذا الطلب، ولذلك لا يُعد التسجيل أو المقعد مؤكدًا. إذا كنت تعتقد أن الإلغاء حدث بالخطأ أو تحتاج إلى معرفة السبب، تواصل مع فريق الدعم واذكر رقم الطلب.', 'alzaherah' ); ?></p>
		<div class="alz-order-status-alert__actions">
			<a class="button" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'التواصل مع الدعم', 'alzaherah' ); ?></a>
			<a href="mailto:contact@alzaherah.edu.sa">contact@alzaherah.edu.sa</a>
		</div>
	</section>
	<?php
}
add_action( 'woocommerce_view_order', 'alzaherah_cancelled_order_customer_notice', 1 );
