<?php
/**
 * Branded WooCommerce email presentation.
 *
 * The implementation relies on public WooCommerce filters and actions so it
 * remains compatible with WooCommerce updates and other platform extensions.
 *
 * @package Alzaherah
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replace WooCommerce's outer email shell while leaving every email body and
 * extension hook intact.
 *
 * @return void
 */
function alzaherah_replace_woocommerce_email_shell() {
	if ( ! function_exists( 'WC' ) || ! WC()->mailer() ) {
		return;
	}

	$mailer = WC()->mailer();
	remove_action( 'woocommerce_email_header', array( $mailer, 'email_header' ) );
	remove_action( 'woocommerce_email_footer', array( $mailer, 'email_footer' ) );
	add_action( 'woocommerce_email_header', 'alzaherah_render_email_header', 10, 2 );
	add_action( 'woocommerce_email_footer', 'alzaherah_render_email_footer', 10, 1 );
}
add_action( 'woocommerce_init', 'alzaherah_replace_woocommerce_email_shell', 20 );

/**
 * Render a self-contained header using tables and inline styles for Gmail and
 * Outlook compatibility.
 *
 * @param string         $email_heading Email title.
 * @param WC_Email|mixed $email         Email instance.
 * @return void
 */
function alzaherah_render_email_header( $email_heading, $email = null ) {
	$site_name = get_bloginfo( 'name' );
	$logo_url  = add_query_arg(
		'ver',
		wp_get_theme()->get( 'Version' ),
		get_theme_file_uri( 'assets/logo-mark.png' )
	);
	?>
	<!DOCTYPE html>
	<html dir="rtl" lang="ar">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title><?php echo esc_html( wp_strip_all_tags( $email_heading ) ); ?></title>
	</head>
	<body style="margin:0;padding:0;background:#f3f5fa;color:#1d2940;direction:rtl;text-align:right;font-family:Tahoma,Arial,sans-serif;">
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background:#f3f5fa;border-collapse:collapse;">
		<tr>
			<td align="center" style="padding:32px 10px;">
				<table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:680px;background:#ffffff;border:1px solid #e7e9f1;border-radius:18px;border-collapse:separate;overflow:hidden;">
					<tr>
						<td style="background:#14213d;padding:27px 38px;border-radius:18px 18px 0 0;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
								<tr>
									<td width="74" style="width:74px;vertical-align:middle;">
										<img src="<?php echo esc_url( $logo_url ); ?>" width="62" height="63" alt="<?php echo esc_attr( $site_name ); ?>" style="display:block;width:62px;height:auto;border:0;">
									</td>
									<td style="padding-right:14px;vertical-align:middle;color:#ffffff;">
										<div style="font-size:13px;line-height:1.6;color:#78ded9;font-weight:700;"><?php echo esc_html( $site_name ); ?></div>
										<h1 style="margin:4px 0 0;color:#ffffff;font-size:25px;line-height:1.5;font-weight:800;text-align:right;"><?php echo esc_html( $email_heading ); ?></h1>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="padding:34px 42px 12px;background:#ffffff;">
	<?php
}

/**
 * Close the custom email shell.
 *
 * @param WC_Email|mixed $email Email instance.
 * @return void
 */
function alzaherah_render_email_footer( $email = null ) {
	?>
						</td>
					</tr>
					<tr>
						<td style="padding:22px 34px;background:#14213d;color:#d9dfeb;text-align:center;font-size:12px;line-height:1.8;border-radius:0 0 18px 18px;">
							<?php
							printf(
								/* translators: 1: site name, 2: current year. */
								esc_html__( 'هذه رسالة آلية من %1$s. جميع الحقوق محفوظة © %2$s.', 'alzaherah' ),
								esc_html( get_bloginfo( 'name' ) ),
								esc_html( wp_date( 'Y' ) )
							);
							?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
	</body>
	</html>
	<?php
}

/**
 * Add a concise branded introduction at the beginning of every HTML email.
 *
 * @param string         $email_heading Current email heading.
 * @param WC_Email|mixed $email         Email instance.
 * @return void
 */
function alzaherah_email_brand_intro( $email_heading, $email = null ) {
	// Branding and heading are rendered together in the custom shell.
}
add_action( 'woocommerce_email_header', 'alzaherah_email_brand_intro', 20, 2 );

/**
 * Add a consistent assistance panel before the WooCommerce footer.
 *
 * @param WC_Email|mixed $email Email instance.
 * @return void
 */
function alzaherah_email_support_panel( $email = null ) {
	$support_email = function_exists( 'alzaherah_contact_email' ) ? alzaherah_contact_email() : 'contact@alzaherah.edu.sa';
	$account_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
	?>
	<div class="alz-email-help" role="presentation">
		<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td class="alz-email-help-copy">
					<strong><?php echo esc_html__( 'هل تحتاج إلى مساعدة؟', 'alzaherah' ); ?></strong>
					<span><?php echo esc_html__( 'فريق مركز الزاهرة للتدريب جاهز لخدمتك.', 'alzaherah' ); ?></span>
				</td>
				<td class="alz-email-help-action">
					<a href="<?php echo esc_url( 'mailto:' . $support_email ); ?>"><?php echo esc_html__( 'تواصل معنا', 'alzaherah' ); ?></a>
				</td>
			</tr>
		</table>
		<p class="alz-email-quick-links">
			<a href="<?php echo esc_url( $account_url ); ?>"><?php echo esc_html__( 'حسابي', 'alzaherah' ); ?></a>
			<span aria-hidden="true">•</span>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( 'زيارة الموقع', 'alzaherah' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'woocommerce_email_footer', 'alzaherah_email_support_panel', 5, 1 );

/**
 * Set a useful, branded footer while keeping the text editable through this
 * theme module rather than replacing WooCommerce templates.
 *
 * @param string $footer_text Existing footer text.
 * @return string
 */
function alzaherah_email_footer_text( $footer_text ) {
	return '';
}
add_filter( 'woocommerce_email_footer_text', 'alzaherah_email_footer_text' );

/**
 * Append email-client-friendly CSS to WooCommerce's built-in stylesheet.
 *
 * WooCommerce inlines these rules with its CSS inliner before sending.
 *
 * @param string        $css   Existing WooCommerce email CSS.
 * @param WC_Email|null $email Email instance when supplied by WooCommerce.
 * @return string
 */
function alzaherah_woocommerce_email_styles( $css, $email = null ) {
	$brand_css = <<<'CSS'

html,
body {
	margin: 0 !important;
	padding: 0 !important;
	width: 100% !important;
	background-color: #f3f5fa !important;
	color: #1d2940;
	direction: rtl;
}

body,
table,
td,
p,
a,
h1,
h2,
h3,
h4 {
	font-family: Tahoma, Arial, sans-serif !important;
	direction: rtl;
	text-align: right;
}

#wrapper {
	background-color: #f3f5fa !important;
	padding: 38px 12px !important;
}

#template_container {
	width: 720px !important;
	max-width: 720px !important;
	background-color: #ffffff !important;
	border: 1px solid #e7e9f1 !important;
	border-radius: 20px !important;
	box-shadow: 0 16px 45px rgba(20, 33, 61, 0.09) !important;
	overflow: hidden !important;
}

#template_header_image {
	display: none !important;
	height: 0 !important;
	max-height: 0 !important;
	mso-hide: all !important;
	overflow: hidden !important;
	padding: 0 !important;
}

#template_header {
	background-color: #14213d !important;
	border-top: 6px solid #10b9b2 !important;
	border: 0 !important;
	border-radius: 0 !important;
	color: #ffffff !important;
}

#template_header h1 {
	color: #ffffff !important;
	font-size: 28px !important;
	font-weight: 800 !important;
	line-height: 1.45 !important;
	letter-spacing: 0 !important;
	margin: 0 !important;
	padding: 38px 48px 34px !important;
	text-align: center !important;
	text-shadow: none !important;
}

#body_content {
	background-color: #ffffff !important;
}

#body_content_inner {
	padding: 0 48px 42px !important;
	color: #1d2940 !important;
	font-size: 16px !important;
	line-height: 2 !important;
}

#body_content_inner > p {
	margin: 0 0 18px !important;
}

.alz-email-brand-intro {
	background-color: #f4f2fc !important;
	border: 1px solid #e0daf8 !important;
	border-radius: 14px !important;
	margin: 28px 0 24px !important;
	padding: 16px 18px !important;
}

.alz-email-brand-intro td {
	border: 0 !important;
	padding: 0 !important;
	vertical-align: middle !important;
}

.alz-email-brand-mark {
	text-align: right !important;
}

.alz-email-brand-mark span {
	background-color: #5b2ccb !important;
	border-radius: 12px !important;
	color: #ffffff !important;
	display: inline-block !important;
	font-size: 21px !important;
	font-weight: 900 !important;
	height: 42px !important;
	line-height: 42px !important;
	text-align: center !important;
	width: 42px !important;
}

.alz-email-brand-copy {
	padding-right: 12px !important;
}

.alz-email-brand-copy strong,
.alz-email-brand-copy span {
	display: block !important;
}

.alz-email-brand-copy strong {
	color: #14213d !important;
	font-size: 15px !important;
	font-weight: 900 !important;
	line-height: 1.5 !important;
}

.alz-email-brand-copy span {
	color: #667085 !important;
	font-size: 12px !important;
	line-height: 1.6 !important;
}

#body_content h2,
#body_content h3 {
	color: #14213d !important;
	font-weight: 800 !important;
	line-height: 1.5 !important;
	margin: 28px 0 14px !important;
}

#body_content h2 {
	font-size: 20px !important;
}

#body_content h3 {
	font-size: 17px !important;
}

#body_content a {
	color: #5b2ccb !important;
	font-weight: 700 !important;
}

#body_content_inner > p a {
	background-color: #5b2ccb !important;
	border: 1px solid #5b2ccb !important;
	border-radius: 10px !important;
	color: #ffffff !important;
	display: inline-block !important;
	font-weight: 800 !important;
	margin: 8px 0 12px !important;
	padding: 11px 20px !important;
	text-decoration: none !important;
}

#body_content .button,
#body_content a.button,
#body_content a.button.alt,
#body_content .email-order-button {
	display: inline-block !important;
	background-color: #5b2ccb !important;
	border: 1px solid #5b2ccb !important;
	border-radius: 10px !important;
	color: #ffffff !important;
	font-weight: 800 !important;
	line-height: 1.2 !important;
	padding: 13px 22px !important;
	text-decoration: none !important;
}

#body_content table.td,
#body_content table.order_details {
	width: 100% !important;
	border: 1px solid #e7e9f1 !important;
	border-collapse: separate !important;
	border-spacing: 0 !important;
	border-radius: 14px !important;
	overflow: hidden !important;
	margin: 16px 0 26px !important;
}

#body_content table.td th,
#body_content table.order_details th {
	background-color: #f1effc !important;
	border-color: #e7e9f1 !important;
	color: #14213d !important;
	font-size: 13px !important;
	font-weight: 800 !important;
	padding: 13px 14px !important;
}

#body_content table.td td,
#body_content table.order_details td {
	background-color: #ffffff !important;
	border-color: #e7e9f1 !important;
	color: #1d2940 !important;
	font-size: 14px !important;
	line-height: 1.75 !important;
	padding: 15px 14px !important;
	vertical-align: middle !important;
}

#body_content table.td tfoot th,
#body_content table.td tfoot td,
#body_content table.order_details tfoot th,
#body_content table.order_details tfoot td {
	background-color: #f7f8fc !important;
	font-weight: 800 !important;
}

#body_content .product-name a {
	color: #14213d !important;
	font-weight: 800 !important;
	text-decoration: none !important;
}

#body_content .product-image img,
#body_content img {
	border-radius: 10px !important;
	height: auto !important;
}

#addresses,
#addresses table {
	width: 100% !important;
}

#addresses td,
.address {
	background-color: #f7f8fc !important;
	border: 1px solid #e7e9f1 !important;
	border-radius: 14px !important;
	color: #344054 !important;
	line-height: 1.8 !important;
	padding: 18px !important;
}

.alz-email-help {
	background-color: #eefaf9 !important;
	border: 1px solid #ccefeb !important;
	border-radius: 14px !important;
	margin: 4px 0 0 !important;
	padding: 18px 20px !important;
}

.alz-email-help table {
	border: 0 !important;
	margin: 0 !important;
}

.alz-email-help td {
	border: 0 !important;
	padding: 0 !important;
	vertical-align: middle !important;
}

.alz-email-help-copy strong {
	color: #14213d !important;
	display: block !important;
	font-size: 15px !important;
	margin-bottom: 3px !important;
}

.alz-email-help-copy span {
	color: #667085 !important;
	display: block !important;
	font-size: 13px !important;
}

.alz-email-help-action {
	text-align: left !important;
	width: 120px !important;
}

.alz-email-help .alz-email-help-action a {
	background-color: #078d88 !important;
	border: 1px solid #078d88 !important;
	border-radius: 9px !important;
	color: #ffffff !important;
	display: inline-block !important;
	font-size: 13px !important;
	font-weight: 800 !important;
	margin: 0 !important;
	padding: 10px 15px !important;
	text-decoration: none !important;
}

.alz-email-quick-links {
	border-top: 1px solid #ccefeb !important;
	color: #667085 !important;
	font-size: 12px !important;
	margin: 15px 0 0 !important;
	padding-top: 12px !important;
	text-align: center !important;
}

.alz-email-help .alz-email-quick-links a {
	background-color: transparent !important;
	border: 0 !important;
	color: #078d88 !important;
	display: inline !important;
	margin: 0 !important;
	padding: 0 !important;
	text-decoration: none !important;
}

.alz-email-quick-links span {
	padding: 0 8px !important;
}

#template_footer {
	background-color: #14213d !important;
}

#template_footer td {
	padding: 24px 36px !important;
}

#template_footer #credit {
	color: #d9dfeb !important;
	font-size: 12px !important;
	line-height: 1.8 !important;
	padding: 0 !important;
	text-align: center !important;
}

#template_footer #credit a {
	color: #78ded9 !important;
}

@media only screen and (max-width: 700px) {
	#wrapper {
		padding: 14px 6px !important;
	}

	#template_container {
		border-radius: 14px !important;
		width: 100% !important;
	}

	#template_header h1 {
		font-size: 23px !important;
		padding: 30px 24px 27px !important;
	}

	#body_content_inner {
		padding: 0 20px 28px !important;
	}

	#body_content table.td th,
	#body_content table.td td,
	#body_content table.order_details th,
	#body_content table.order_details td {
		font-size: 12px !important;
		padding: 11px 8px !important;
	}

	.alz-email-help td {
		display: block !important;
		text-align: right !important;
		width: 100% !important;
	}

	.alz-email-help-action {
		padding-top: 13px !important;
	}
}
CSS;

	return $css . $brand_css;
}
add_filter( 'woocommerce_email_styles', 'alzaherah_woocommerce_email_styles', 20, 2 );
