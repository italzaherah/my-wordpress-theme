<?php
/**
 * تبويب أمان الحساب: تغيير البريد الإلكتروني وكلمة المرور.
 *
 * @package Alzaherah
 * @since   3.6.3
 */

defined( 'ABSPATH' ) || exit;

$alz_user = wp_get_current_user();
?>

<div class="account-sections account-security-sections">
	<section class="account-card">
		<header class="account-card-head">
			<h2><?php esc_html_e( 'البريد الإلكتروني', 'alzaherah' ); ?></h2>
			<p>
				<?php
				printf(
					/* translators: %s: البريد الحالي */
					wp_kses_post( __( 'بريدك الحالي: %s — تصلك عليه تأكيدات التسجيل والفواتير.', 'alzaherah' ) ),
					'<strong dir="ltr">' . esc_html( $alz_user->user_email ) . '</strong>'
				);
				?>
			</p>
		</header>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'alz_change_email', 'alz_change_email_nonce' ); ?>
			<input type="hidden" name="action" value="alz_change_email">

			<div class="account-fields-grid">
				<p class="woocommerce-form-row form-row">
					<label for="alz_new_email"><?php esc_html_e( 'البريد الجديد', 'alzaherah' ); ?> <span class="required">*</span></label>
					<input type="email" class="woocommerce-Input input-text" name="alz_new_email" id="alz_new_email" autocomplete="email" required>
				</p>
				<p class="woocommerce-form-row form-row">
					<label for="alz_new_email_confirm"><?php esc_html_e( 'تأكيد البريد الجديد', 'alzaherah' ); ?> <span class="required">*</span></label>
					<input type="email" class="woocommerce-Input input-text" name="alz_new_email_confirm" id="alz_new_email_confirm" autocomplete="email" required>
				</p>
			</div>

			<p class="woocommerce-form-row form-row">
				<label for="alz_email_password"><?php esc_html_e( 'كلمة المرور الحالية (للتأكيد الأمني)', 'alzaherah' ); ?> <span class="required">*</span></label>
				<input type="password" class="woocommerce-Input input-text" name="alz_email_password" id="alz_email_password" autocomplete="current-password" required>
			</p>

			<p class="account-submit"><button type="submit" class="button btn btn-secondary"><?php esc_html_e( 'تحديث البريد', 'alzaherah' ); ?></button></p>
		</form>
	</section>

	<section class="account-card">
		<header class="account-card-head">
			<h2><?php esc_html_e( 'كلمة المرور', 'alzaherah' ); ?></h2>
			<p><?php esc_html_e( 'ثمانية أحرف على الأقل، ويُفضّل مزيج من الأحرف والأرقام والرموز.', 'alzaherah' ); ?></p>
		</header>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'alz_change_password', 'alz_change_password_nonce' ); ?>
			<input type="hidden" name="action" value="alz_change_password">

			<p class="woocommerce-form-row form-row">
				<label for="alz_current_password"><?php esc_html_e( 'كلمة المرور الحالية', 'alzaherah' ); ?> <span class="required">*</span></label>
				<input type="password" class="woocommerce-Input input-text" name="alz_current_password" id="alz_current_password" autocomplete="current-password" required>
			</p>

			<div class="account-fields-grid">
				<p class="woocommerce-form-row form-row">
					<label for="alz_new_password"><?php esc_html_e( 'كلمة المرور الجديدة', 'alzaherah' ); ?> <span class="required">*</span></label>
					<input type="password" class="woocommerce-Input input-text" name="alz_new_password" id="alz_new_password" autocomplete="new-password" minlength="8" required>
				</p>
				<p class="woocommerce-form-row form-row">
					<label for="alz_new_password_confirm"><?php esc_html_e( 'تأكيد كلمة المرور الجديدة', 'alzaherah' ); ?> <span class="required">*</span></label>
					<input type="password" class="woocommerce-Input input-text" name="alz_new_password_confirm" id="alz_new_password_confirm" autocomplete="new-password" minlength="8" required>
				</p>
			</div>

			<p class="account-submit"><button type="submit" class="button btn btn-secondary"><?php esc_html_e( 'تغيير كلمة المرور', 'alzaherah' ); ?></button></p>
		</form>
	</section>
</div>
