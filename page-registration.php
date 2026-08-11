<?php
/**
 * Template Name: صفحة التسجيل والدفع
 * Template Post Type: page
 *
 * صفحة Checkout مخصصة لتسجيل المتدرب والدفع عبر WooCommerce.
 * تدعم حالات السلة، الدفع، وصفحة تأكيد الطلب بعد نجاح العملية.
 *
 * @package Alzaherah
 * @since   3.4.2
 */

defined( 'ABSPATH' ) || exit;

$alz_shop_url        = function_exists( 'alzaherah_shop_url' ) ? alzaherah_shop_url() : home_url( '/shop/' );
$alz_has_woocommerce = class_exists( 'WooCommerce' );
$alz_cart_ready      = $alz_has_woocommerce && function_exists( 'WC' ) && WC()->cart;
$alz_cart_empty      = ! $alz_cart_ready || WC()->cart->is_empty();
$alz_order_received  = $alz_has_woocommerce && function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' );
$alz_order_pay       = $alz_has_woocommerce && function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' );
$alz_order           = false;
$alz_order_id        = 0;

/*
 * طبقة حماية داخل القالب نفسه: لا تُطبع صفحة Checkout لزائر لديه سلة.
 * تحتفظ الإضافة بالسلة وتعيده إليها بعد تسجيل الدخول، بينما يبقى التحويل
 * الاحتياطي آمنًا إذا كانت نسخة الإضافة القديمة ما زالت مفعلة لحظة التحديث.
 */
if ( ! is_user_logged_in() && ! $alz_cart_empty && ! $alz_order_received && ! $alz_order_pay ) {
	if ( class_exists( 'ALZ_Course_Access' ) ) {
		ALZ_Course_Access::redirect_guest_checkout();
	}
	$alz_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
	wp_safe_redirect(
		add_query_arg(
			array(
				'view'            => 'login',
				'alz_course_auth' => '1',
			),
			$alz_account_url
		)
	);
	exit;
}

/*
 * استرجاع الطلب من رابط تأكيد WooCommerce والتحقق من صلاحية عرضه.
 * بعد نجاح الدفع تصبح السلة فارغة، لذلك لا يجوز الاعتماد على السلة
 * لتحديد محتوى صفحة تأكيد التسجيل.
 */
if ( $alz_order_received && function_exists( 'wc_get_order' ) ) {
	$alz_order_id = absint( get_query_var( 'order-received' ) );

	if ( $alz_order_id > 0 ) {
		$alz_order = wc_get_order( $alz_order_id );
	}

	if ( $alz_order ) {
		$alz_order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
		if ( function_exists( 'alz_core_can_view_order' ) ) {
			if ( ! alz_core_can_view_order( $alz_order, $alz_order_key ) ) {
				$alz_order = false;
			}
		} else {
			$alz_user_id    = get_current_user_id();
			$alz_is_owner   = $alz_user_id > 0 && (int) $alz_order->get_user_id() === $alz_user_id;
			$alz_key_valid  = $alz_order_key && hash_equals( (string) $alz_order->get_order_key(), (string) $alz_order_key );
			$alz_can_manage = current_user_can( 'edit_shop_orders' );
			if ( ! $alz_key_valid && ! $alz_is_owner && ! $alz_can_manage ) {
				$alz_order = false;
			}
		}
	}
}

$alz_is_confirmation = $alz_order_received;
if ( $alz_order && function_exists( 'alz_core_order_registration_is_confirmed' ) ) {
	$alz_registration_complete = alz_core_order_registration_is_confirmed( $alz_order );
} elseif ( $alz_order ) {
	$alz_paid_at = $alz_order->get_date_paid();
	$alz_has_course = function_exists( 'alzaherah_order_has_course_items' ) ? alzaherah_order_has_course_items( $alz_order ) : true;
	$alz_registration_complete = $alz_has_course
		&& $alz_paid_at
		&& $alz_paid_at->getTimestamp() > 0
		&& ! in_array( $alz_order->get_status(), array( 'cancelled', 'refunded', 'failed' ), true );
} else {
	$alz_registration_complete = false;
}

$alz_catalog_kind = function_exists( 'alzaherah_catalog_checkout_kind' )
	? alzaherah_catalog_checkout_kind( $alz_order ? $alz_order : null )
	: 'courses';
$alz_products_url = ( class_exists( 'ALZ_Training_Products' ) && is_callable( array( 'ALZ_Training_Products', 'page_url' ) ) )
	? ALZ_Training_Products::page_url()
	: home_url( '/training-products/' );
$alz_browse_url   = ( 'training' === $alz_catalog_kind ) ? $alz_products_url : $alz_shop_url;

if ( $alz_is_confirmation ) {
	if ( 'training' === $alz_catalog_kind ) {
		$alz_order_paid = $alz_order && function_exists( 'alz_core_order_has_verified_payment' ) && alz_core_order_has_verified_payment( $alz_order );
		$alz_hero_title = $alz_order_paid
			? __( 'تم تأكيد طلبك بنجاح', 'alzaherah' )
			: __( 'تم إنشاء طلبك بنجاح', 'alzaherah' );
		$alz_hero_text = $alz_order_paid
			? __( 'تم تأكيد الدفع. رابط التنزيل متاح من حسابك لمدة 90 يومًا.', 'alzaherah' )
			: __( 'احتفظ برقم الطلب وأرفق إيصال التحويل؛ يصبح التنزيل متاحًا بعد تأكيد الدفع فقط.', 'alzaherah' );
		$alz_kicker = __( 'تأكيد الطلب', 'alzaherah' );
	} elseif ( 'mixed' === $alz_catalog_kind ) {
		$alz_hero_title = $alz_registration_complete
			? __( 'تم تأكيد طلبك بنجاح', 'alzaherah' )
			: __( 'تم إنشاء طلبك بنجاح', 'alzaherah' );
		$alz_hero_text = $alz_registration_complete
			? __( 'تم تأكيد الدفع. راجع ملخص الطلب لمتابعة التسجيل والتنزيل.', 'alzaherah' )
			: __( 'احتفظ برقم الطلب؛ يُعتمد التسجيل والتنزيل بعد تأكيد الدفع.', 'alzaherah' );
		$alz_kicker = __( 'تأكيد الطلب', 'alzaherah' );
	} else {
		$alz_hero_title = $alz_registration_complete
			? __( 'تم تسجيلك في الدورة بنجاح', 'alzaherah' )
			: __( 'تم إنشاء طلب تسجيلك بنجاح', 'alzaherah' );
		$alz_hero_text = $alz_registration_complete
			? __( 'تم تأكيد الدفع والمقعد. احتفظ برقم الطلب الظاهر في ملخص التسجيل.', 'alzaherah' )
			: __( 'احتفظ برقم الطلب وأرفق إيصال التحويل؛ سيؤكد المركز تسجيلك بعد مراجعة الدفع.', 'alzaherah' );
		$alz_kicker = __( 'تأكيد التسجيل', 'alzaherah' );
	}
} elseif ( 'training' === $alz_catalog_kind ) {
	$alz_hero_title = __( 'إتمام الطلب والدفع', 'alzaherah' );
	$alz_hero_text  = __( 'راجع منتجك وبياناتك، واختر طريقة الدفع، ثم أكمل الطلب بأمان.', 'alzaherah' );
	$alz_kicker     = __( 'دفع إلكتروني آمن', 'alzaherah' );
} elseif ( 'mixed' === $alz_catalog_kind ) {
	$alz_hero_title = __( 'إتمام الطلب والدفع', 'alzaherah' );
	$alz_hero_text  = __( 'راجع عناصر طلبك وبياناتك، واختر طريقة الدفع، ثم أكمل العملية بأمان.', 'alzaherah' );
	$alz_kicker     = __( 'دفع إلكتروني آمن', 'alzaherah' );
} else {
	$alz_hero_title = __( 'إتمام التسجيل والدفع', 'alzaherah' );
	$alz_hero_text  = __( 'راجع بياناتك، واختر طريقة الدفع، ثم أرسل طلب التسجيل بأمان.', 'alzaherah' );
	$alz_kicker     = __( 'دفع إلكتروني آمن', 'alzaherah' );
}

$alz_progress_label = $alz_is_confirmation
	? __( 'مراحل الطلب', 'alzaherah' )
	: ( 'training' === $alz_catalog_kind || 'mixed' === $alz_catalog_kind ? __( 'مراحل الطلب', 'alzaherah' ) : __( 'مراحل التسجيل', 'alzaherah' ) );
$alz_step_one_label = ( 'training' === $alz_catalog_kind || 'mixed' === $alz_catalog_kind )
	? __( 'بيانات الطلب', 'alzaherah' )
	: __( 'بيانات التسجيل', 'alzaherah' );

get_header();
?>

<main id="main" class="registration-page<?php echo $alz_is_confirmation ? ' is-confirmation' : ''; ?><?php echo $alz_catalog_kind ? ' is-kind-' . esc_attr( $alz_catalog_kind ) : ''; ?>" role="main">
	<section class="registration-hero" aria-labelledby="registration-title">
		<div class="container registration-hero-inner">
			<div>
				<span class="registration-kicker">
					<span class="registration-kicker-icon" aria-hidden="true"></span>
					<?php echo esc_html( $alz_kicker ); ?>
				</span>
				<h1 id="registration-title"><?php echo esc_html( $alz_hero_title ); ?></h1>
				<p><?php echo esc_html( $alz_hero_text ); ?></p>
			</div>

			<div class="registration-progress" aria-label="<?php echo esc_attr( $alz_progress_label ); ?>">
				<span class="registration-progress-compact">
					<?php echo esc_html( $alz_is_confirmation ? __( 'الخطوة 3 من 3 — التأكيد', 'alzaherah' ) : __( 'الخطوة 2 من 3 — الدفع', 'alzaherah' ) ); ?>
				</span>
				<ol class="registration-progress-list">
					<li class="registration-step is-complete"><span class="registration-step-indicator" aria-hidden="true"><bdi>1</bdi></span><strong><?php echo esc_html( $alz_step_one_label ); ?></strong></li>
					<li class="registration-step <?php echo $alz_is_confirmation ? 'is-complete' : 'is-current'; ?>"<?php echo $alz_is_confirmation ? '' : ' aria-current="step"'; ?>><span class="registration-step-indicator" aria-hidden="true"><bdi>2</bdi></span><strong><?php esc_html_e( 'الدفع', 'alzaherah' ); ?></strong></li>
					<li class="registration-step <?php echo $alz_is_confirmation ? 'is-current' : ''; ?>"<?php echo $alz_is_confirmation ? ' aria-current="step"' : ''; ?>><span class="registration-step-indicator" aria-hidden="true"><bdi>3</bdi></span><strong><?php esc_html_e( 'التأكيد', 'alzaherah' ); ?></strong></li>
				</ol>
			</div>
		</div>
	</section>

	<section class="registration-content section-soft">
		<div class="container">
			<?php if ( ! $alz_has_woocommerce ) : ?>
				<div class="registration-empty">
					<span class="registration-empty-icon" aria-hidden="true">!</span>
					<h2><?php esc_html_e( 'WooCommerce غير مفعّل', 'alzaherah' ); ?></h2>
					<p><?php esc_html_e( 'فعّل إضافة WooCommerce حتى تعمل صفحة التسجيل والدفع.', 'alzaherah' ); ?></p>
				</div>

			<?php elseif ( $alz_is_confirmation ) : ?>
				<div class="registration-layout registration-confirmation-layout">
					<div class="registration-checkout-card registration-confirmation-card">
						<div class="registration-confirmation-heading">
							<span class="registration-confirmation-icon" aria-hidden="true">✓</span>
							<div>
								<?php
								if ( 'training' === $alz_catalog_kind ) {
									$alz_order_paid = $alz_order && function_exists( 'alz_core_order_has_verified_payment' ) && alz_core_order_has_verified_payment( $alz_order );
									$alz_confirm_h2 = $alz_order_paid ? __( 'تم تأكيد طلبك بنجاح', 'alzaherah' ) : __( 'تم إنشاء طلبك بنجاح', 'alzaherah' );
								} elseif ( 'mixed' === $alz_catalog_kind ) {
									$alz_confirm_h2 = $alz_registration_complete ? __( 'تم تأكيد طلبك بنجاح', 'alzaherah' ) : __( 'تم إنشاء طلبك بنجاح', 'alzaherah' );
								} else {
									$alz_confirm_h2 = $alz_registration_complete ? __( 'تم تسجيلك في الدورة بنجاح', 'alzaherah' ) : __( 'تم إنشاء طلب تسجيلك بنجاح', 'alzaherah' );
								}
								?>
								<h2><?php echo esc_html( $alz_confirm_h2 ); ?></h2>
								<p>
									<?php
									if ( $alz_order ) {
										if ( 'training' === $alz_catalog_kind ) {
											$alz_order_paid = function_exists( 'alz_core_order_has_verified_payment' ) && alz_core_order_has_verified_payment( $alz_order );
											if ( $alz_order_paid ) {
												printf(
													/* translators: %s: order number. */
													esc_html__( 'اكتمل الدفع. رقم طلبك هو %s، وروابط التنزيل متاحة من حسابك لمدة 90 يومًا.', 'alzaherah' ),
													esc_html( $alz_order->get_order_number() )
												);
											} else {
												printf(
													/* translators: %s: order number. */
													esc_html__( 'رقم طلبك هو %s. أرفق الإيصال أدناه؛ يصبح التنزيل متاحًا بعد تأكيد الدفع فقط.', 'alzaherah' ),
													esc_html( $alz_order->get_order_number() )
												);
											}
										} elseif ( $alz_registration_complete ) {
											printf(
												/* translators: %s: order number. */
												esc_html__( 'اكتمل تسجيلك وتأكيد الدفع. رقم طلبك هو %s.', 'alzaherah' ),
												esc_html( $alz_order->get_order_number() )
											);
										} else {
											printf(
												/* translators: %s: order number. */
												esc_html__( 'رقم طلبك هو %s. أرفق الإيصال أدناه، وسيؤكد المركز الطلب بعد مراجعة التحويل.', 'alzaherah' ),
												esc_html( $alz_order->get_order_number() )
											);
										}
									} else {
										esc_html_e( 'راجع تفاصيل الطلب أدناه، ويمكنك الرجوع إلى حسابك لمتابعة الحالة.', 'alzaherah' );
									}
									?>
								</p>
							</div>
						</div>

						<div class="registration-checkout registration-thankyou-output">
							<?php
							if ( $alz_order ) {
								$alz_payment_method  = sanitize_key( $alz_order->get_payment_method() );
								$alz_receipt_methods = class_exists( 'ALZ_Bank_Transfer' ) ? ALZ_Bank_Transfer::receipt_payment_method_ids() : array( 'bacs' );

								// بوابات الدفع الإلكترونية قد تضيف تعليمات خاصة بها بعد العودة الناجحة.
								if ( $alz_payment_method && ! in_array( $alz_payment_method, $alz_receipt_methods, true ) ) {
									do_action( 'woocommerce_thankyou_' . $alz_payment_method, $alz_order->get_id() );
								}

								// تشغيل تكاملات التحليلات العامة دون تكرار جدول الطلب الذي يعرضه القالب في الجانب.
								$alz_details_priority = has_action( 'woocommerce_thankyou', 'woocommerce_order_details_table' );
								if ( false !== $alz_details_priority ) {
									remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', $alz_details_priority );
								}
								do_action( 'woocommerce_thankyou', $alz_order->get_id() );
								if ( false !== $alz_details_priority ) {
									add_action( 'woocommerce_thankyou', 'woocommerce_order_details_table', $alz_details_priority );
								}

								// للتحويل البنكي: الحساب ظهر قبل التأكيد، وهنا يظهر رفع الإيصال فقط.
								if ( class_exists( 'ALZ_Bank_Transfer' ) ) {
									ALZ_Bank_Transfer::render_customer_section( $alz_order );
								}
							}
							?>
						</div>
					</div>

					<aside class="registration-sidebar confirmation-sidebar" aria-label="<?php esc_attr_e( 'ملخص الطلب', 'alzaherah' ); ?>">
						<?php if ( $alz_order ) : ?>
							<div class="registration-summary-card confirmation-summary-card">
								<div class="confirmation-order-header">
									<span><?php esc_html_e( 'ملخص الطلب', 'alzaherah' ); ?></span>
									<strong>#<?php echo esc_html( $alz_order->get_order_number() ); ?></strong>
								</div>

								<div class="confirmation-status-row">
									<span><?php esc_html_e( 'حالة الطلب', 'alzaherah' ); ?></span>
									<strong class="confirmation-status status-<?php echo esc_attr( $alz_order->get_status() ); ?>">
										<?php echo esc_html( wc_get_order_status_name( $alz_order->get_status() ) ); ?>
									</strong>
								</div>

								<div class="registration-products confirmation-products">
									<?php foreach ( $alz_order->get_items() as $alz_item ) : ?>
										<?php
										$alz_product = $alz_item->get_product();
										$alz_product_id = $alz_product ? $alz_product->get_id() : absint( $alz_item->get_product_id() );
										?>
										<div class="registration-product confirmation-product">
											<div class="registration-product-image">
												<?php
												if ( $alz_product ) {
													echo wp_kses_post( $alz_product->get_image( 'thumbnail' ) );
												} else {
													echo '<span aria-hidden="true">📦</span>';
												}
												?>
											</div>
											<div>
												<strong><?php echo esc_html( $alz_item->get_name() ); ?></strong>
												<?php
												if ( function_exists( 'alzaherah_render_checkout_line_meta' ) ) {
													alzaherah_render_checkout_line_meta( $alz_product_id, (int) $alz_item->get_quantity(), $alz_product );
												}
												?>
											</div>
										</div>
									<?php endforeach; ?>
								</div>

								<div class="confirmation-info-list">
									<?php $alz_payment_title = $alz_order->get_payment_method_title(); ?>
									<div><span><?php esc_html_e( 'طريقة الدفع', 'alzaherah' ); ?></span><strong><?php echo esc_html( $alz_payment_title ? $alz_payment_title : __( 'غير محددة', 'alzaherah' ) ); ?></strong></div>
									<div><span><?php esc_html_e( 'البريد الإلكتروني', 'alzaherah' ); ?></span><strong><?php echo esc_html( $alz_order->get_billing_email() ); ?></strong></div>
									<div><span><?php esc_html_e( 'رقم الجوال', 'alzaherah' ); ?></span><strong dir="ltr"><?php echo esc_html( $alz_order->get_billing_phone() ); ?></strong></div>
								</div>

								<div class="registration-total confirmation-total">
									<span><?php esc_html_e( 'إجمالي الطلب', 'alzaherah' ); ?></span>
									<strong><?php echo wp_kses_post( $alz_order->get_formatted_order_total() ); ?></strong>
								</div>
							</div>

							<div class="registration-trust-card confirmation-actions-card">
								<h3><?php esc_html_e( 'الخطوة التالية', 'alzaherah' ); ?></h3>
								<p>
									<?php
									echo esc_html(
										'training' === $alz_catalog_kind
											? __( 'احتفظ برقم الطلب. سيصل التأكيد وروابط التنزيل إلى بريدك بعد تحديث حالة الدفع.', 'alzaherah' )
											: __( 'احتفظ برقم الطلب. سيصل تأكيد التسجيل والفاتورة إلى بريدك الإلكتروني بعد تحديث حالة الدفع.', 'alzaherah' )
									);
									?>
								</p>

								<?php if ( $alz_order->needs_payment() ) : ?>
									<a class="btn btn-primary confirmation-action" href="<?php echo esc_url( $alz_order->get_checkout_payment_url() ); ?>">
										<?php esc_html_e( 'إكمال الدفع', 'alzaherah' ); ?>
									</a>
								<?php elseif ( is_user_logged_in() && (int) $alz_order->get_user_id() === get_current_user_id() ) : ?>
									<a class="btn btn-primary confirmation-action" href="<?php echo esc_url( $alz_order->get_view_order_url() ); ?>">
										<?php esc_html_e( 'عرض تفاصيل الطلب', 'alzaherah' ); ?>
									</a>
								<?php endif; ?>

								<a class="btn btn-secondary confirmation-action" href="<?php echo esc_url( $alz_browse_url ); ?>">
									<?php echo esc_html( 'training' === $alz_catalog_kind ? __( 'استعراض منتجات أخرى', 'alzaherah' ) : __( 'استعراض دورات أخرى', 'alzaherah' ) ); ?>
								</a>
							</div>
						<?php else : ?>
							<div class="registration-trust-card confirmation-actions-card">
								<h3><?php esc_html_e( 'تعذّر تحميل ملخص الطلب', 'alzaherah' ); ?></h3>
								<p><?php esc_html_e( 'قد يكون رابط التأكيد منتهيًا أو لا يخص الحساب الحالي. راجع طلباتك من صفحة حسابي.', 'alzaherah' ); ?></p>
								<a class="btn btn-primary confirmation-action" href="<?php echo esc_url( function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'orders' ) : home_url( '/my-account/orders/' ) ); ?>">
									<?php esc_html_e( 'عرض طلباتي', 'alzaherah' ); ?>
								</a>
							</div>
						<?php endif; ?>
					</aside>
				</div>

			<?php elseif ( $alz_order_pay ) : ?>
				<?php
				$alz_pay_order_id = absint( get_query_var( 'order-pay' ) );
				$alz_pay_order    = ( $alz_pay_order_id && function_exists( 'wc_get_order' ) ) ? wc_get_order( $alz_pay_order_id ) : false;
				if ( $alz_pay_order ) {
					$alz_pay_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
					if ( function_exists( 'alz_core_can_view_order' ) ) {
						if ( ! alz_core_can_view_order( $alz_pay_order, $alz_pay_key ) ) {
							$alz_pay_order = false;
						}
					} else {
						$alz_pay_user_id    = get_current_user_id();
						$alz_pay_is_owner   = $alz_pay_user_id > 0 && (int) $alz_pay_order->get_user_id() === $alz_pay_user_id;
						$alz_pay_key_valid  = $alz_pay_key && hash_equals( (string) $alz_pay_order->get_order_key(), (string) $alz_pay_key );
						$alz_pay_can_manage = current_user_can( 'edit_shop_orders' );
						if ( ! $alz_pay_key_valid && ! $alz_pay_is_owner && ! $alz_pay_can_manage ) {
							$alz_pay_order = false;
						}
					}
				}
				if ( $alz_pay_order && function_exists( 'alzaherah_catalog_checkout_kind' ) ) {
					$alz_catalog_kind = alzaherah_catalog_checkout_kind( $alz_pay_order );
					$alz_browse_url   = ( 'training' === $alz_catalog_kind ) ? $alz_products_url : $alz_shop_url;
				}
				?>
				<div class="registration-layout registration-payment-layout">
					<div class="registration-checkout-card">
						<div class="registration-section-heading">
							<span class="registration-section-number">2</span>
							<div>
								<h2><?php esc_html_e( 'إكمال دفع الطلب', 'alzaherah' ); ?></h2>
								<p><?php esc_html_e( 'اختر وسيلة الدفع المناسبة وأكمل العملية بأمان.', 'alzaherah' ); ?></p>
							</div>
						</div>
						<div class="registration-checkout">
							<?php echo do_shortcode( '[woocommerce_checkout]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
					<?php if ( $alz_pay_order ) : ?>
						<aside class="registration-sidebar" aria-label="<?php esc_attr_e( 'ملخص الطلب', 'alzaherah' ); ?>">
							<div class="registration-summary-card">
								<span class="registration-summary-label"><?php echo esc_html( 'training' === $alz_catalog_kind ? __( 'المنتج المختار', 'alzaherah' ) : __( 'عناصر الطلب', 'alzaherah' ) ); ?></span>
								<div class="registration-products">
									<?php foreach ( $alz_pay_order->get_items() as $alz_pay_item ) : ?>
										<?php
										$alz_pay_product    = $alz_pay_item->get_product();
										$alz_pay_product_id = $alz_pay_product ? $alz_pay_product->get_id() : absint( $alz_pay_item->get_product_id() );
										?>
										<div class="registration-product">
											<div class="registration-product-image">
												<?php
												if ( $alz_pay_product ) {
													echo wp_kses_post( $alz_pay_product->get_image( 'thumbnail' ) );
												} else {
													echo '<span aria-hidden="true">📦</span>';
												}
												?>
											</div>
											<div>
												<strong><?php echo esc_html( $alz_pay_item->get_name() ); ?></strong>
												<?php
												if ( function_exists( 'alzaherah_render_checkout_line_meta' ) ) {
													alzaherah_render_checkout_line_meta( $alz_pay_product_id, (int) $alz_pay_item->get_quantity(), $alz_pay_product );
												}
												?>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
								<div class="registration-total">
									<span><?php esc_html_e( 'الإجمالي', 'alzaherah' ); ?></span>
									<strong><?php echo wp_kses_post( $alz_pay_order->get_formatted_order_total() ); ?></strong>
								</div>
							</div>
						</aside>
					<?php endif; ?>
				</div>

			<?php elseif ( $alz_cart_empty ) : ?>
				<div class="registration-empty">
					<span class="registration-empty-icon" aria-hidden="true">🗓️</span>
					<h2><?php esc_html_e( 'سلتك فارغة', 'alzaherah' ); ?></h2>
					<p><?php esc_html_e( 'اختر دورة أو منتجًا رقميًا ثم عد إلى هنا لإكمال الدفع.', 'alzaherah' ); ?></p>
					<div class="registration-empty-actions">
						<a class="btn btn-primary" href="<?php echo esc_url( $alz_shop_url ); ?>">
							<?php esc_html_e( 'استعراض الدورات', 'alzaherah' ); ?>
							<?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
						<a class="btn btn-secondary" href="<?php echo esc_url( $alz_products_url ); ?>">
							<?php esc_html_e( 'استعراض المنتجات', 'alzaherah' ); ?>
						</a>
					</div>
				</div>

			<?php else : ?>
				<div class="registration-layout">
					<div class="registration-checkout-card">
						<div class="registration-section-heading">
							<span class="registration-section-number">2</span>
							<div>
								<h2>
									<?php
									echo esc_html(
										'training' === $alz_catalog_kind || 'mixed' === $alz_catalog_kind
											? __( 'مراجعة الطلب', 'alzaherah' )
											: __( 'مراجعة التسجيل', 'alzaherah' )
									);
									?>
								</h2>
								<p>
									<?php
									echo esc_html(
										'training' === $alz_catalog_kind
											? __( 'تحقق من بياناتك وملخص المنتج ثم اختر طريقة الدفع المناسبة.', 'alzaherah' )
											: __( 'تحقق من بيانات التسجيل ثم اختر طريقة الدفع المناسبة.', 'alzaherah' )
									);
									?>
								</p>
							</div>
						</div>
						<div class="registration-checkout">
							<?php echo do_shortcode( '[woocommerce_checkout]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>

					<aside class="registration-sidebar" aria-label="<?php esc_attr_e( 'ملخص الطلب', 'alzaherah' ); ?>">
						<div class="registration-summary-card">
							<span class="registration-summary-label">
								<?php
								echo esc_html(
									'training' === $alz_catalog_kind
										? __( 'المنتج المختار', 'alzaherah' )
										: ( 'mixed' === $alz_catalog_kind ? __( 'عناصر الطلب', 'alzaherah' ) : __( 'الدورة المختارة', 'alzaherah' ) )
								);
								?>
							</span>
							<div class="registration-products">
								<?php foreach ( WC()->cart->get_cart() as $alz_cart_item ) : ?>
									<?php
									$alz_product = isset( $alz_cart_item['data'] ) ? $alz_cart_item['data'] : false;
									if ( ! $alz_product || ! $alz_product->exists() ) {
										continue;
									}
									?>
									<div class="registration-product">
										<div class="registration-product-image"><?php echo wp_kses_post( $alz_product->get_image( 'thumbnail' ) ); ?></div>
										<div>
											<strong><?php echo esc_html( $alz_product->get_name() ); ?></strong>
											<?php
											if ( function_exists( 'alzaherah_render_checkout_line_meta' ) ) {
												alzaherah_render_checkout_line_meta( $alz_product->get_id(), (int) $alz_cart_item['quantity'], $alz_product );
											}
											?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
							<?php if ( WC()->cart->get_coupons() ) : ?>
								<div class="registration-discount-list">
									<?php foreach ( WC()->cart->get_coupons() as $alz_code => $alz_coupon ) : ?>
										<div class="registration-discount-row">
											<span><?php echo esc_html( sprintf( __( 'كوبون: %s', 'alzaherah' ), $alz_code ) ); ?></span>
											<strong>-<?php echo wp_kses_post( WC()->cart->get_coupon_discount_amount( $alz_code, WC()->cart->display_cart_ex_tax ) ? wc_price( WC()->cart->get_coupon_discount_amount( $alz_code, WC()->cart->display_cart_ex_tax ) ) : '' ); ?></strong>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
							<div class="registration-total">
								<span><?php esc_html_e( 'الإجمالي', 'alzaherah' ); ?></span>
								<strong><?php echo wp_kses_post( WC()->cart->get_total() ); ?></strong>
							</div>
						</div>

						<div class="registration-trust-card">
							<h3><?php echo esc_html( 'training' === $alz_catalog_kind ? __( 'شراء موثوق وآمن', 'alzaherah' ) : __( 'تسجيل موثوق وآمن', 'alzaherah' ) ); ?></h3>
							<ul>
								<li><span aria-hidden="true">✓</span> <?php esc_html_e( 'حماية بيانات الحساب والدفع', 'alzaherah' ); ?></li>
								<li><span aria-hidden="true">✓</span> <?php echo esc_html( 'training' === $alz_catalog_kind ? __( 'تنزيل آمن بعد تأكيد الدفع لمدة 90 يومًا', 'alzaherah' ) : __( 'تأكيد التسجيل عبر البريد الإلكتروني', 'alzaherah' ) ); ?></li>
								<li><span aria-hidden="true">✓</span> <?php esc_html_e( 'فاتورة إلكترونية بعد نجاح الدفع', 'alzaherah' ); ?></li>
							</ul>
						</div>

						<a class="registration-back-link" href="<?php echo esc_url( $alz_browse_url ); ?>">
							<?php echo alzaherah_ui_arrow( 'back' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo esc_html( 'training' === $alz_catalog_kind ? __( 'العودة إلى المنتجات', 'alzaherah' ) : __( 'العودة إلى الدورات', 'alzaherah' ) ); ?>
						</a>
					</aside>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php get_footer(); ?>
