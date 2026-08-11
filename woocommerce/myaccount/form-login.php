<?php
/**
 * Login and registration forms with password-manager compatible attributes.
 *
 * @package WooCommerce\Templates
 * @version 9.9.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );

$alz_registration_enabled = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
?>

<?php if ( $alz_registration_enabled ) : ?>
	<div class="u-columns col2-set" id="customer_login">
		<div class="u-column1 col-1">
<?php endif; ?>

		<h2><?php esc_html_e( 'تسجيل الدخول', 'alzaherah' ); ?></h2>

		<form class="woocommerce-form woocommerce-form-login login" method="post" autocomplete="on">
			<?php do_action( 'woocommerce_login_form_start' ); ?>

			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="username"><?php esc_html_e( 'اسم المستخدم أو البريد الإلكتروني', 'alzaherah' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?php echo ! empty( $_POST['username'] ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Re-populating the failed WooCommerce login form only. ?>" required aria-required="true">
			</p>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
				<label for="password"><?php esc_html_e( 'كلمة المرور', 'alzaherah' ); ?> <span class="required" aria-hidden="true">*</span></label>
				<input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true">
			</p>

			<?php do_action( 'woocommerce_login_form' ); ?>

			<p class="form-row">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever"> <span><?php esc_html_e( 'تذكرني', 'alzaherah' ); ?></span>
				</label>
				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
				<button type="submit" class="woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'تسجيل الدخول', 'alzaherah' ); ?>"><?php esc_html_e( 'تسجيل الدخول', 'alzaherah' ); ?></button>
			</p>
			<p class="woocommerce-LostPassword lost_password">
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'نسيت كلمة مرورك؟', 'alzaherah' ); ?></a>
			</p>

			<?php do_action( 'woocommerce_login_form_end' ); ?>
		</form>

<?php if ( $alz_registration_enabled ) : ?>
		</div>

		<div class="u-column2 col-2">
			<h2><?php esc_html_e( 'إنشاء حساب', 'alzaherah' ); ?></h2>

			<form method="post" class="woocommerce-form woocommerce-form-register register" autocomplete="on">
				<?php do_action( 'woocommerce_register_form_start' ); ?>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_username"><?php esc_html_e( 'اسم المستخدم', 'alzaherah' ); ?> <span class="required" aria-hidden="true">*</span></label>
						<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?php echo ! empty( $_POST['username'] ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Re-populating the failed WooCommerce registration form only. ?>" required aria-required="true">
					</p>
				<?php endif; ?>

				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
					<label for="reg_email"><?php esc_html_e( 'البريد الإلكتروني', 'alzaherah' ); ?> <span class="required" aria-hidden="true">*</span></label>
					<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ! empty( $_POST['email'] ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Re-populating the failed WooCommerce registration form only. ?>" required aria-required="true">
				</p>

				<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_password"><?php esc_html_e( 'كلمة المرور', 'alzaherah' ); ?> <span class="required" aria-hidden="true">*</span></label>
						<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true">
					</p>
				<?php else : ?>
					<p><?php esc_html_e( 'سيُرسل رابط إنشاء كلمة المرور إلى بريدك الإلكتروني.', 'alzaherah' ); ?></p>
				<?php endif; ?>

				<?php do_action( 'woocommerce_register_form' ); ?>

				<p class="woocommerce-form-row form-row">
					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
					<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'إنشاء حساب', 'alzaherah' ); ?>"><?php esc_html_e( 'إنشاء حساب', 'alzaherah' ); ?></button>
				</p>

				<?php do_action( 'woocommerce_register_form_end' ); ?>
			</form>
		</div>
	</div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
