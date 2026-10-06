<?php
/**
 * Identity 4.1.3 email presentation. WooCommerce owns triggers, bodies and delivery.
 * The core's shared shell is used when available; the theme-only fallback remains.
 * @package Alzaherah
 */
defined( 'ABSPATH' ) || exit;

function alzaherah_replace_woocommerce_email_shell() {
	if ( ! function_exists( 'WC' ) || ! WC()->mailer() ) { return; }
	$mailer = WC()->mailer();
	remove_action( 'woocommerce_email_header', array( $mailer, 'email_header' ) );
	remove_action( 'woocommerce_email_footer', array( $mailer, 'email_footer' ) );
	add_action( 'woocommerce_email_header', 'alzaherah_render_email_header', 10, 2 );
	add_action( 'woocommerce_email_footer', 'alzaherah_render_email_footer', 10, 1 );
}
add_action( 'woocommerce_init', 'alzaherah_replace_woocommerce_email_shell', 20 );

/** Published branding for theme-only operation; never expose a private draft. */
function alzaherah_email_fallback_brand() {
	$state = get_option( 'alz_site_copy_state', array() );
	$copy = is_array( $state ) && is_array( $state['published'] ?? null ) ? $state['published'] : array();
	$support = $copy['footer_email'] ?? ( function_exists( 'alzaherah_contact_email' ) ? alzaherah_contact_email() : '' );
	return array(
		'name' => trim( (string) ( $copy['footer_name_ar'] ?? '' ) ) ?: 'مركز الزاهرة للتدريب',
		'name_en' => trim( (string) ( $copy['footer_name_en'] ?? '' ) ) ?: 'ALZAHERAH TRAINING CENTER',
		'support' => is_email( $support ) ? $support : '',
		'logo' => is_file( get_theme_file_path( 'assets/logo-mark.png' ) ) ? get_theme_file_uri( 'assets/logo-mark.png' ) : '',
	);
}

function alzaherah_render_email_header( $email_heading, $email = null ) {
	if ( class_exists( 'ALZ_Email_Presentation' ) ) {
		echo ALZ_Email_Presentation::header( $email_heading ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shared renderer escapes its fields.
		return;
	}
	$brand = alzaherah_email_fallback_brand();
	?>
<!doctype html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?php echo esc_html( wp_strip_all_tags( $email_heading ) ); ?></title></head>
<body style="margin:0;padding:0;background:#f7f8fc;color:#1d2940;direction:rtl;font-family:Alexandria, Segoe UI, Tahoma, Arial, sans-serif;">
<table id="wrapper" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" dir="rtl" style="width:100%;border-collapse:collapse;background:#f7f8fc;"><tr><td align="center" class="alz-email-outer" style="padding:24px 12px;">
<!--[if mso]><table role="presentation" width="640" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
<table id="template_container" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" dir="rtl" style="width:100%;max-width:640px;border:1px solid #e7e9f1;border-radius:20px;background:#ffffff;border-collapse:separate;overflow:hidden;text-align:right;">
<tr><td id="template_header" class="alz-email-pad" style="padding:24px;background:#14213d;border-radius:20px 20px 0 0;color:#ffffff;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" dir="rtl"><tr>
<?php if ( $brand['logo'] ) : ?><td width="68" style="width:68px;vertical-align:middle;"><img src="<?php echo esc_url( $brand['logo'] ); ?>" alt="" width="52" height="53" style="display:block;width:52px;height:auto;padding:8px;background:#ffffff;border-radius:12px;border:0;"></td><?php endif; ?>
<td style="padding-right:12px;vertical-align:middle;color:#ffffff;"><strong class="alz-email-brand-name" style="display:block;font-size:17px;line-height:1.7;color:#ffffff;"><?php echo esc_html( $brand['name'] ); ?></strong><span dir="ltr" style="display:block;text-align:right;font-size:10px;line-height:1.7;letter-spacing:1px;color:#ffffff;"><?php echo esc_html( $brand['name_en'] ); ?></span></td>
</tr></table></td></tr><tr><td id="body_content" class="alz-email-pad" style="padding:28px 24px 24px;background:#ffffff;">
<div id="body_content_inner" dir="rtl" style="text-align:right;font-size:16px;line-height:1.85;color:#1d2940;overflow-wrap:anywhere;word-break:break-word;font-family:Alexandria, Segoe UI, Tahoma, Arial, sans-serif;">
<h1 class="alz-email-heading" style="margin:0 0 20px;font-size:24px;line-height:1.5;font-weight:700;color:#14213d;"><?php echo esc_html( $email_heading ); ?></h1>
	<?php
}

function alzaherah_render_email_footer( $email = null ) {
	if ( class_exists( 'ALZ_Email_Presentation' ) ) {
		echo ALZ_Email_Presentation::footer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shared renderer escapes its fields.
		return;
	}
	$brand = alzaherah_email_fallback_brand();
	?>
</div></td></tr><tr><td id="template_footer" class="alz-email-pad" style="padding:20px 24px;border-top:1px solid #e7e9f1;background:#f7f8fc;border-radius:0 0 20px 20px;color:#566277;font-size:12px;line-height:1.85;text-align:right;font-family:Alexandria, Segoe UI, Tahoma, Arial, sans-serif;">
<p style="margin:0 0 8px;">هذه رسالة آلية بشأن حسابك أو طلبك لدى <?php echo esc_html( $brand['name'] ); ?>.</p>
<?php if ( $brand['support'] ) : ?><p style="margin:0;">للمساعدة: <a dir="ltr" href="<?php echo esc_url( 'mailto:' . $brand['support'] ); ?>" style="color:#5b2ccb;text-decoration:underline;"><?php echo esc_html( $brand['support'] ); ?></a></p><?php endif; ?>
</td></tr></table><!--[if mso]></td></tr></table><![endif]--></td></tr></table></body></html>
	<?php
}

/** Legacy callable hooks retained; branding/support now appear once in the shell. */
function alzaherah_email_brand_intro( $email_heading, $email = null ) {}
function alzaherah_email_support_panel( $email = null ) {}
function alzaherah_email_footer_text( $footer_text ) { return ''; }
add_filter( 'woocommerce_email_footer_text', 'alzaherah_email_footer_text' );

/** WooCommerce inlines these rules. Shell IDs match the markup actually rendered. */
function alzaherah_woocommerce_email_styles( $css, $email = null ) {
	$brand_css = <<<'CSS'
html, body { margin:0!important; padding:0!important; width:100%!important; background:#f7f8fc!important; color:#1d2940; }
body, table, td, th, p, a, h1, h2, h3, h4 { font-family:Alexandria, "Segoe UI", Tahoma, Arial, sans-serif!important; }
#wrapper { width:100%!important; background:#f7f8fc!important; padding:0!important; }
#template_container { width:100%!important; max-width:640px!important; background:#ffffff!important; border:1px solid #e7e9f1!important; border-radius:20px!important; box-shadow:none!important; }
#template_header { padding:24px!important; background:#14213d!important; color:#ffffff!important; border:0!important; border-radius:20px 20px 0 0!important; }
#body_content { padding:28px 24px 24px!important; background:#ffffff!important; }
#body_content_inner { padding:0!important; font-size:16px!important; line-height:1.85!important; color:#1d2940!important; text-align:right!important; direction:rtl; }
#body_content p { margin:0 0 16px; }
#body_content h1.alz-email-heading { margin:0 0 20px!important; font-size:24px!important; line-height:1.5!important; color:#14213d!important; font-weight:700!important; text-align:right!important; letter-spacing:0!important; }
#body_content h2, #body_content h3 { color:#14213d!important; font-weight:700!important; line-height:1.5!important; margin:24px 0 12px!important; text-align:right!important; }
#body_content h2 { font-size:20px!important; }
#body_content h3 { font-size:17px!important; }
#body_content a { color:#5b2ccb; overflow-wrap:anywhere; word-break:break-word; }
#body_content a.button, #body_content a.button.alt, #body_content a.email-order-button, #body_content a.alz-email-button { display:inline-block!important; background:#5b2ccb!important; border:1px solid #5b2ccb!important; border-radius:12px!important; color:#ffffff!important; font-weight:600!important; line-height:1.5!important; padding:12px 22px!important; text-decoration:none!important; }
#body_content table.td, #body_content table.order_details, #body_content table.email-order-details { width:100%!important; max-width:100%!important; border:1px solid #e7e9f1!important; border-collapse:collapse!important; border-spacing:0!important; margin:16px 0 24px!important; }
#body_content table.td th, #body_content table.order_details th, #body_content table.email-order-details th { background:#f7f8fc!important; border-color:#e7e9f1!important; color:#14213d!important; font-size:13px!important; font-weight:700!important; padding:12px 10px!important; text-align:right!important; }
#body_content table.td td, #body_content table.order_details td, #body_content table.email-order-details td { border-color:#e7e9f1!important; color:#1d2940!important; font-size:14px!important; line-height:1.7!important; padding:12px 10px!important; vertical-align:top!important; text-align:right!important; overflow-wrap:anywhere; word-break:break-word; }
#body_content table.td thead th:nth-child(n+2), #body_content table.td tbody td:nth-child(n+2), #body_content table.order_details thead th:nth-child(n+2), #body_content table.order_details tbody td:nth-child(n+2), #body_content table.email-order-details thead th:nth-child(n+2), #body_content table.email-order-details tbody td:nth-child(n+2), #body_content table.td tfoot td, #body_content table.order_details tfoot td { white-space:nowrap!important; word-break:normal!important; overflow-wrap:normal!important; font-variant-numeric:tabular-nums; }
#body_content table.td tfoot th, #body_content table.td tfoot td, #body_content table.order_details tfoot th, #body_content table.order_details tfoot td { background:#f7f8fc!important; font-weight:700!important; }
#body_content img { max-width:100%!important; height:auto!important; }
#body_content [dir="ltr"] { direction:ltr!important; unicode-bidi:isolate; }
#addresses { width:100%!important; }
#addresses td { padding:12px!important; text-align:right!important; }
#addresses .address { color:#1d2940!important; border-color:#e7e9f1!important; line-height:1.85!important; overflow-wrap:anywhere; word-break:break-word; }
#template_footer { padding:20px 24px!important; background:#f7f8fc!important; border-top:1px solid #e7e9f1!important; color:#566277!important; font-size:12px!important; line-height:1.85!important; text-align:right!important; }
#template_footer a { color:#5b2ccb!important; text-decoration:underline; }
@media only screen and (max-width:600px) {
 .alz-email-outer { padding:12px 8px!important; }
 #template_header, #body_content, #template_footer { padding:20px 16px!important; }
 #body_content h1.alz-email-heading { font-size:22px!important; }
 .alz-email-brand-name { font-size:15px!important; }
 #body_content table.td th, #body_content table.td td, #body_content table.order_details th, #body_content table.order_details td, #body_content table.email-order-details th, #body_content table.email-order-details td { font-size:12px!important; padding:10px 6px!important; }
 #addresses td { display:block!important; width:auto!important; padding:12px 0!important; }
}
CSS;
	return $css . $brand_css;
}
add_filter( 'woocommerce_email_styles', 'alzaherah_woocommerce_email_styles', 20, 2 );
