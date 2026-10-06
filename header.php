<?php
/**
 * ترويسة منصة الزاهرة — هوية واضحة وإجراءات دخول منفصلة.
 *
 * @package Alzaherah
 * @since   3.4.1
 */

defined( 'ABSPATH' ) || exit;

$account_url = function_exists( 'alzaherah_account_url' ) ? alzaherah_account_url() : home_url( '/my-account/' );
$login_url   = function_exists( 'alzaherah_login_url' ) ? alzaherah_login_url() : add_query_arg( 'view', 'login', $account_url );
$signup_url  = function_exists( 'alzaherah_signup_url' ) ? alzaherah_signup_url() : add_query_arg( 'view', 'register', $account_url );
$shop_url    = function_exists( 'alzaherah_shop_url' ) ? alzaherah_shop_url() : home_url( '/shop/' );
$cart_url    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$can_dashboard = function_exists( 'alz_user_can_access_front_dashboard' ) && alz_user_can_access_front_dashboard();
$dashboard_url = function_exists( 'alz_front_dashboard_url' ) ? alz_front_dashboard_url() : home_url( '/platform-dashboard/' );
$alz_exam_focus = function_exists( 'alzaherah_is_active_exam_attempt' ) && alzaherah_is_active_exam_attempt();
$brand_name   = __( 'مركز الزاهرة للتدريب', 'alzaherah' );
$brand_href   = $alz_exam_focus ? '#main' : home_url( '/' );
$brand_label  = $alz_exam_focus ? __( 'العودة إلى الاختبار', 'alzaherah' ) : $brand_name;
$brand_logo_url = add_query_arg(
	'ver',
	wp_get_theme()->get( 'Version' ),
	get_theme_file_uri( 'assets/logo-mark.png' )
);
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main"><?php esc_html_e( 'تجاوز إلى المحتوى', 'alzaherah' ); ?></a>

<header class="site-header alz-header<?php echo $alz_exam_focus ? ' alz-header--exam-focus' : ''; ?>">
	<div class="container alz-navbar">

		<div class="brand alz-brand">
			<a class="brand-link alz-brand-lockup" href="<?php echo esc_url( $brand_href ); ?>" aria-label="<?php echo esc_attr( $brand_label ); ?>">
				<img
					class="brand-mark alz-brand-mark"
					src="<?php echo esc_url( $brand_logo_url ); ?>"
					alt=""
					width="56"
					height="57"
					fetchpriority="high"
					decoding="async"
				>
				<span class="brand-text alz-brand-lockup__text">
					<strong class="alz-brand-lockup__ar"><?php echo esc_html( $brand_name ); ?></strong>
					<small class="alz-brand-lockup__en"><?php esc_html_e( 'ALZAHERAH TRAINING CENTER', 'alzaherah' ); ?></small>
				</span>
			</a>
		</div>

		<?php if ( ! $alz_exam_focus ) : ?>
		<nav class="alz-main-navigation" aria-label="<?php esc_attr_e( 'القائمة الرئيسية', 'alzaherah' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'nav-links alz-nav-links',
					'fallback_cb'    => 'alzaherah_fallback_menu',
				)
			);
			?>
		</nav>

		<div class="nav-actions alz-nav-actions">
			<?php if ( is_user_logged_in() ) : ?>
				<?php if ( $can_dashboard ) : ?>
					<a class="alz-account-link alz-dashboard-link" href="<?php echo esc_url( $dashboard_url ); ?>" aria-label="<?php esc_attr_e( 'فتح لوحة التحكم', 'alzaherah' ); ?>">
						<span class="alz-action-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" focusable="false"><path d="M4 6.5h7M15 6.5h5M4 12h3M11 12h9M4 17.5h10M18 17.5h2"/><circle cx="13" cy="6.5" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="16" cy="17.5" r="2"/></svg>
						</span>
						<span><?php esc_html_e( 'لوحة التحكم', 'alzaherah' ); ?></span>
					</a>
				<?php else : ?>
					<a class="alz-account-link" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php esc_attr_e( 'فتح حسابي', 'alzaherah' ); ?>">
						<span class="alz-action-icon" aria-hidden="true">
							<svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="8" r="3.5"/><path d="M5.5 20c.5-4 2.65-6 6.5-6s6 2 6.5 6"/></svg>
						</span>
						<span><?php esc_html_e( 'حسابي', 'alzaherah' ); ?></span>
					</a>
				<?php endif; ?>
			<?php else : ?>
				<a class="alz-login-link" href="<?php echo esc_url( $login_url ); ?>">
					<?php esc_html_e( 'تسجيل الدخول', 'alzaherah' ); ?>
				</a>
				<a class="btn btn-primary btn-sm alz-signup-link" href="<?php echo esc_url( $signup_url ); ?>">
					<?php esc_html_e( 'إنشاء حساب', 'alzaherah' ); ?>
				</a>
			<?php endif; ?>

			<a class="alz-cart-link" href="<?php echo esc_url( $cart_url ); ?>" aria-label="<?php esc_attr_e( 'سلة المشتريات', 'alzaherah' ); ?>">
				<span class="alz-cart-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" focusable="false"><path d="M3 4h2.2l1.6 9.1a2 2 0 0 0 2 1.65h7.9a2 2 0 0 0 1.95-1.58L20 7H6"/><circle cx="9" cy="19" r="1.5"/><circle cx="17" cy="19" r="1.5"/></svg>
				</span>
				<?php echo function_exists( 'alzaherah_cart_count_markup' ) ? alzaherah_cart_count_markup() : '<small class="alz-cart-count" hidden></small>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>

			<button class="menu-toggle" type="button" aria-label="<?php esc_attr_e( 'فتح القائمة', 'alzaherah' ); ?>" aria-expanded="false" aria-controls="mobile-drawer">
				<span class="alz-menu-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M4 7h16M4 12h16M4 17h16"/></svg></span>
			</button>
		</div>
		<?php else : ?>
			<div class="alz-exam-focus-context" aria-label="<?php esc_attr_e( 'بيانات الاختبار النشط', 'alzaherah' ); ?>">
				<span class="alz-exam-focus-kicker"><?php esc_html_e( 'اختبار نشط', 'alzaherah' ); ?></span>
				<strong class="alz-exam-focus-title" data-alz-exam-site-title aria-live="polite" aria-atomic="true">
					<?php esc_html_e( 'جاري تحميل بيانات الاختبار', 'alzaherah' ); ?>
				</strong>
			</div>

			<div class="alz-exam-focus-actions">
				<div class="alz-exam-focus-states" role="status" aria-live="polite" aria-atomic="false">
					<span class="alz-exam-focus-state alz-exam-focus-connection" data-alz-exam-site-connection data-state="checking">
						<span class="alz-exam-focus-state__indicator" aria-hidden="true"></span>
						<span data-alz-exam-site-connection-label><?php esc_html_e( 'التحقق من الاتصال', 'alzaherah' ); ?></span>
					</span>
					<span id="alz-exam-focus-save-state" class="alz-exam-focus-state alz-exam-focus-save-state" data-alz-exam-site-save-state data-state="idle">
						<?php esc_html_e( 'جاهز للحفظ', 'alzaherah' ); ?>
					</span>
				</div>

				<button
					type="button"
					class="button alz-exam-focus-save-exit"
					data-alz-exam-site-save-exit
					aria-describedby="alz-exam-focus-save-state"
					aria-disabled="true"
					disabled
				>
					<?php esc_html_e( 'حفظ وخروج', 'alzaherah' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>
</header>

<?php if ( ! $alz_exam_focus ) : ?>
<div id="mobile-drawer" class="mobile-drawer" aria-hidden="true">
	<div class="drawer-backdrop"></div>
	<aside class="drawer-panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'قائمة التنقل', 'alzaherah' ); ?>" tabindex="-1">
		<div class="drawer-header">
			<a class="brand-link alz-brand-lockup" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( $brand_name ); ?>">
				<img class="brand-mark alz-brand-mark" src="<?php echo esc_url( $brand_logo_url ); ?>" alt="" width="44" height="45" decoding="async">
				<span class="brand-text alz-brand-lockup__text"><strong class="alz-brand-lockup__ar"><?php echo esc_html( $brand_name ); ?></strong></span>
			</a>
			<button class="drawer-close" type="button" aria-label="<?php esc_attr_e( 'إغلاق القائمة', 'alzaherah' ); ?>"><span aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M6 6l12 12M18 6L6 18"/></svg></span></button>
		</div>

		<nav aria-label="<?php esc_attr_e( 'قائمة الجوال', 'alzaherah' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'drawer-links',
					'fallback_cb'    => 'alzaherah_fallback_menu',
				)
			);
			?>
		</nav>

		<div class="alz-drawer-actions">
			<?php if ( is_user_logged_in() ) : ?>
				<a class="btn btn-primary" href="<?php echo esc_url( $can_dashboard ? $dashboard_url : $account_url ); ?>">
					<?php echo $can_dashboard ? esc_html__( 'فتح لوحة التحكم', 'alzaherah' ) : esc_html__( 'فتح حسابي', 'alzaherah' ); ?>
				</a>
			<?php else : ?>
				<a class="btn btn-primary" href="<?php echo esc_url( $signup_url ); ?>">
					<?php esc_html_e( 'إنشاء حساب جديد', 'alzaherah' ); ?>
				</a>
				<a class="btn btn-secondary" href="<?php echo esc_url( $login_url ); ?>">
					<?php esc_html_e( 'تسجيل الدخول', 'alzaherah' ); ?>
				</a>
			<?php endif; ?>
			<a class="btn btn-secondary" href="<?php echo esc_url( $shop_url ); ?>">
				<?php esc_html_e( 'تصفح الدورات', 'alzaherah' ); ?>
			</a>
		</div>
	</aside>
</div>
<?php endif; ?>
