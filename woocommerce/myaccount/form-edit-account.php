<?php
/**
 * تجاوز قالب "بيانات الحساب": البيانات الشخصية وبيانات المتدرب فقط.
 * البريد الإلكتروني وكلمة المرور موجودان في تبويب «أمان الحساب» المستقل.
 *
 * المسار داخل القالب: woocommerce/myaccount/form-edit-account.php
 *
 * @package WooCommerce\Templates
 * @version 10.5.0
 * @since   3.4.0
 */

defined( 'ABSPATH' ) || exit;

$alz_user = wp_get_current_user();

do_action( 'woocommerce_before_edit_account_form' );
?>

<div class="account-sections">

	<section class="account-card">

		<header class="account-card-head">
			<h2><?php esc_html_e( 'البيانات الشخصية', 'alzaherah' ); ?></h2>
			<p><?php esc_html_e( 'اسمك كما سيظهر في الشهادات والفواتير، وبيانات التواصل الأساسية.', 'alzaherah' ); ?></p>
		</header>

		<form class="woocommerce-EditAccountForm edit-account" method="post">

			<div class="account-fields-grid">
				<p class="woocommerce-form-row form-row">
					<label for="account_first_name"><?php esc_html_e( 'الاسم الأول', 'alzaherah' ); ?> <span class="required">*</span></label>
					<input type="text" class="woocommerce-Input input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $alz_user->first_name ); ?>" required>
				</p>

				<p class="woocommerce-form-row form-row">
					<label for="account_last_name"><?php esc_html_e( 'اسم العائلة', 'alzaherah' ); ?> <span class="required">*</span></label>
					<input type="text" class="woocommerce-Input input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $alz_user->last_name ); ?>" required>
				</p>
			</div>

			<p class="woocommerce-form-row form-row">
				<label for="account_display_name"><?php esc_html_e( 'اسم العرض', 'alzaherah' ); ?> <span class="required">*</span></label>
				<input type="text" class="woocommerce-Input input-text" name="account_display_name" id="account_display_name" value="<?php echo esc_attr( $alz_user->display_name ); ?>" required>
				<span class="account-hint"><?php esc_html_e( 'الاسم الظاهر في التقييمات والمراسلات.', 'alzaherah' ); ?></span>
			</p>

			<?php
			/*
			 * حقول المتدرب: الهوية والجوال (من نواة المنصة)
			 * والبيانات الإضافية الاختيارية (من القالب).
			 */
			do_action( 'woocommerce_edit_account_form_fields' );
			do_action( 'woocommerce_edit_account_form' );
			?>

			<!-- البريد يُدار من تبويب أمان الحساب؛ نمرر الحالي لووكومرس دون تغييره. -->
			<input type="hidden" name="account_email" value="<?php echo esc_attr( $alz_user->user_email ); ?>">

			<p class="account-submit">
				<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
				<button type="submit" class="woocommerce-Button button btn btn-primary" name="save_account_details" value="<?php esc_attr_e( 'حفظ البيانات', 'alzaherah' ); ?>"><?php esc_html_e( 'حفظ البيانات', 'alzaherah' ); ?></button>
				<input type="hidden" name="action" value="save_account_details">
			</p>

		</form>

	</section>

</div>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
