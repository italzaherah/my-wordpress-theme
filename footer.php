<?php
/**
 * تذييل الموقع: التعريف بالمنصة + الروابط + السياسات + التواصل.
 *
 * @package Alzaherah
 * @since   3.4.1
 */

defined( 'ABSPATH' ) || exit;

$alz_phone_default    = get_theme_mod( 'alzaherah_phone', '+966553406661' );
$alz_email_default    = function_exists( 'alzaherah_contact_email' ) ? alzaherah_contact_email() : 'contact@alzaherah.edu.sa';
$alz_location_default = get_theme_mod( 'alzaherah_location', __( 'الباحة، المملكة العربية السعودية', 'alzaherah' ) );
$brand_name_default   = __( 'مركز الزاهرة للتدريب', 'alzaherah' );
$brand_name_en_default = 'ALZAHERAH TRAINING CENTER';
$footer_text_default  = __( 'منصة تدريب تجمع نخبة من البرامج الحضورية والإلكترونية، وتمنح الأفراد والمنشآت تجربة تسجيل ودفع متكاملة تحوّل التعلّم إلى أثر ملموس.', 'alzaherah' );
$alz_phone     = alzaherah_copy_value( 'footer_phone', $alz_phone_default );
$alz_email     = alzaherah_copy_value( 'footer_email', $alz_email_default );
$alz_location  = alzaherah_copy_value( 'footer_location', $alz_location_default );
$brand_name    = alzaherah_copy_value( 'footer_name_ar', $brand_name_default );
$brand_name_en = alzaherah_copy_value( 'footer_name_en', $brand_name_en_default );
$footer_text   = alzaherah_copy_value( 'footer_text', $footer_text_default );
$theme_version = wp_get_theme()->get( 'Version' );
$brand_logo_url = add_query_arg( 'ver', $theme_version, get_theme_file_uri( 'assets/logo-mark.png' ) );
$legal_profile = function_exists( 'alzaherah_legal_profile' ) ? alzaherah_legal_profile() : array();
$news_term     = get_category_by_slug( 'alzaherah-news' );
$articles_term = get_category_by_slug( 'alzaherah-articles' );
$news_url      = $news_term ? get_category_link( $news_term->term_id ) : home_url( '/category/alzaherah-news/' );
$articles_url  = $articles_term ? get_category_link( $articles_term->term_id ) : home_url( '/category/alzaherah-articles/' );
$news_url      = is_wp_error( $news_url ) ? home_url( '/category/alzaherah-news/' ) : $news_url;
$articles_url  = is_wp_error( $articles_url ) ? home_url( '/category/alzaherah-articles/' ) : $articles_url;

/*
 * علامات شبكات الدفع التي يدعم تصميم المنصة عرضها. لا تعني هذه القائمة أن
 * كل وسيلة مفعّلة لكل طلب؛ بوابة Checkout تبقى مصدر الحقيقة للوسائل المتاحة
 * فعليًا حسب الجهاز والمبلغ وإعداد مزود الدفع.
 */
$payment_asset_map = array(
	'mada'       => array( 'file' => 'mada.svg', 'label' => __( 'مدى', 'alzaherah' ) ),
	'visa'       => array( 'file' => 'visa.svg', 'label' => 'Visa' ),
	'mastercard' => array( 'file' => 'mastercard.svg', 'label' => 'Mastercard' ),
	'apple-pay'  => array( 'file' => 'apple-pay.svg', 'label' => 'Apple Pay' ),
	'stc-pay'    => array( 'file' => 'stc-pay.svg', 'label' => 'stc pay' ),
);
$payment_marks = (array) apply_filters(
	'alzaherah_supported_payment_marks',
	array( 'mada', 'visa', 'mastercard', 'apple-pay', 'stc-pay' )
);
$payment_marks = array_values( array_intersect( array_keys( $payment_asset_map ), array_map( 'sanitize_key', $payment_marks ) ) );

/* لا تظهر بيانات نظامية ناقصة للزائر. تبقى بوابة الجودة مسؤولة عن تنبيه الإدارة. */
$legal_profile_complete = $legal_profile
	&& ! empty( $legal_profile['commercial_name'] )
	&& ! empty( $legal_profile['commercial_reg'] )
	&& ! empty( $legal_profile['tvtc_license'] )
	&& ( 'yes' !== ( $legal_profile['tax_registered'] ?? 'no' ) || ! empty( $legal_profile['tax_number'] ) );
?>

<?php
$alz_exam_focus = function_exists( 'alzaherah_is_active_exam_attempt' ) && alzaherah_is_active_exam_attempt();
if ( ! $alz_exam_focus ) :
?>
<footer class="site-footer" id="contact">

	<div class="container footer-main">

		<!-- التعريف بالمنصة -->
		<div class="footer-brand">
			<span class="footer-brand-row alz-brand-lockup">
				<img class="brand-mark alz-brand-mark alz-brand-mark--footer" src="<?php echo esc_url( $brand_logo_url ); ?>" alt="<?php esc_attr_e( 'شعار مركز الزاهرة للتدريب', 'alzaherah' ); ?>" width="52" height="53" loading="lazy" decoding="async">
				<span class="brand-text alz-brand-lockup__text"><strong class="alz-brand-lockup__ar" data-alz-copy="footer_name_ar"><?php echo esc_html( $brand_name ); ?></strong><small class="alz-brand-lockup__en" data-alz-copy="footer_name_en"><?php echo esc_html( $brand_name_en ); ?></small></span>
			</span>
			<p data-alz-copy="footer_text"><?php echo esc_html( $footer_text ); ?></p>
		</div>

		<!-- روابط سريعة (من قائمة "روابط التذييل" إن وُجدت، وإلا الروابط الافتراضية) -->
		<div>
			<h3 class="footer-title"><?php alzaherah_copy_e( 'footer_quick_title', 'روابط سريعة' ); ?></h3>

			<?php
			if ( has_nav_menu( 'footer' ) ) {

				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => false,
						'menu_class'     => 'footer-links',
						'depth'          => 1,
					)
				);

			} else {
				?>
				<div class="footer-links">
					<a data-alz-copy-href="footer_link_courses_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_link_courses_url', alzaherah_shop_url() ) ); ?>"><?php alzaherah_copy_e( 'footer_link_courses_label', 'البرامج التدريبية' ); ?></a>
					<a data-alz-copy-href="footer_link_exams_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_link_exams_url', function_exists( 'alzaherah_exams_page_url' ) ? alzaherah_exams_page_url() : home_url( '/exams/' ) ) ); ?>"><?php alzaherah_copy_e( 'footer_link_exams_label', 'الاختبارات' ); ?></a>
					<a data-alz-copy-href="footer_link_about_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_link_about_url', home_url( '/about/' ) ) ); ?>"><?php alzaherah_copy_e( 'footer_link_about_label', 'عن المركز' ); ?></a>
					<a data-alz-copy-href="footer_link_partners_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_link_partners_url', home_url( '/partners/' ) ) ); ?>"><?php alzaherah_copy_e( 'footer_link_partners_label', 'شركاء النجاح' ); ?></a>
					<a data-alz-copy-href="footer_link_news_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_link_news_url', $news_url ) ); ?>"><?php alzaherah_copy_e( 'footer_link_news_label', 'أخبارنا' ); ?></a>
					<a data-alz-copy-href="footer_link_articles_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_link_articles_url', $articles_url ) ); ?>"><?php alzaherah_copy_e( 'footer_link_articles_label', 'مقالاتنا' ); ?></a>
					<a data-alz-copy-href="footer_link_account_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_link_account_url', alzaherah_account_url() ) ); ?>"><?php alzaherah_copy_e( 'footer_link_account_label', 'حساب المتدرب' ); ?></a>
				</div>
				<?php
			}
			?>
		</div>

		<!-- السياسات -->
		<div>
			<h3 class="footer-title"><?php alzaherah_copy_e( 'footer_policies_title', 'السياسات' ); ?></h3>
			<div class="footer-links footer-policy-links">
				<a data-alz-copy-href="footer_policy_privacy_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_policy_privacy_url', function_exists( 'get_privacy_policy_url' ) && get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' ) ) ); ?>">
					<?php alzaherah_copy_e( 'footer_policy_privacy_label', 'سياسة الخصوصية' ); ?>
				</a>
				<a data-alz-copy-href="footer_policy_refund_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_policy_refund_url', home_url( '/refund-policy/' ) ) ); ?>"><?php alzaherah_copy_e( 'footer_policy_refund_label', 'الإلغاء والاسترجاع' ); ?></a>
				<a data-alz-copy-href="footer_policy_terms_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_policy_terms_url', home_url( '/terms/' ) ) ); ?>"><?php alzaherah_copy_e( 'footer_policy_terms_label', 'الشروط والأحكام' ); ?></a>
				<a data-alz-copy-href="footer_policy_complaints_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_policy_complaints_url', home_url( '/complaints-policy/' ) ) ); ?>"><?php alzaherah_copy_e( 'footer_policy_complaints_label', 'الشكاوى والمقترحات' ); ?></a>
				<a data-alz-copy-href="footer_policy_all_url" href="<?php echo esc_url( alzaherah_copy_value( 'footer_policy_all_url', home_url( '/policy-center/' ) ) ); ?>"><?php alzaherah_copy_e( 'footer_policy_all_label', 'جميع السياسات والحقوق' ); ?></a>
			</div>
		</div>

		<!-- التواصل ووسائل الدفع -->
		<div>
			<h3 class="footer-title"><?php alzaherah_copy_e( 'footer_contact_title', 'تواصل معنا' ); ?></h3>

			<div class="footer-contact">
				<a class="footer-contact-link is-phone" data-alz-copy-tel="footer_phone" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $alz_phone ) ); ?>">
					<span aria-hidden="true">☎</span>
					<bdi dir="ltr" data-alz-copy="footer_phone"><?php echo esc_html( $alz_phone ); ?></bdi>
				</a>
				<a class="footer-contact-link is-email" data-alz-copy-mailto="footer_email" href="mailto:<?php echo esc_attr( $alz_email ); ?>">
					<span aria-hidden="true">✉</span>
					<bdi dir="ltr" data-alz-copy="footer_email"><?php echo esc_html( $alz_email ); ?></bdi>
				</a>
				<span class="footer-contact-link"><span aria-hidden="true">📍</span><span data-alz-copy="footer_location"><?php echo esc_html( $alz_location ); ?></span></span>
			</div>

			<p class="payment-intro"><strong><?php esc_html_e( 'وسائل الدفع المدعومة', 'alzaherah' ); ?></strong><span><?php esc_html_e( 'قد تختلف الوسائل المتاحة فعليًا؛ تظهر الخيارات النهائية في صفحة الدفع قبل تأكيد الطلب.', 'alzaherah' ); ?></span></p>
			<?php if ( $payment_marks ) : ?>
				<div class="payment-row" role="list" aria-label="<?php esc_attr_e( 'وسائل الدفع التي يدعمها الموقع', 'alzaherah' ); ?>">
					<?php foreach ( $payment_marks as $payment_mark ) : ?>
						<?php $payment_asset = $payment_asset_map[ $payment_mark ]; ?>
						<span class="payment payment--<?php echo esc_attr( $payment_mark ); ?>" role="listitem"><img src="<?php echo esc_url( add_query_arg( 'ver', $theme_version, get_template_directory_uri() . '/assets/payments/' . $payment_asset['file'] ) ); ?>" alt="<?php echo esc_attr( $payment_asset['label'] ); ?>" width="42" height="24" loading="lazy" decoding="async"></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

	</div>

	<?php if ( $legal_profile_complete ) : ?>
		<div class="container footer-compliance" aria-label="<?php esc_attr_e( 'البيانات النظامية للمنشأة', 'alzaherah' ); ?>">
			<div><span><?php esc_html_e( 'الاسم التجاري', 'alzaherah' ); ?></span><strong><?php echo esc_html( $legal_profile['commercial_name'] ); ?></strong></div>
			<div><span><?php esc_html_e( 'السجل التجاري', 'alzaherah' ); ?></span><strong><?php echo esc_html( $legal_profile['commercial_reg'] ); ?></strong></div>
			<div><span><?php esc_html_e( 'ترخيص التدريب', 'alzaherah' ); ?></span><strong><?php echo esc_html( $legal_profile['tvtc_license'] ); ?></strong></div>
			<?php if ( 'yes' === $legal_profile['tax_registered'] ) : ?>
				<div><span><?php esc_html_e( 'الرقم الضريبي', 'alzaherah' ); ?></span><strong><?php echo esc_html( $legal_profile['tax_number'] ); ?></strong></div>
			<?php endif; ?>
			<?php if ( $legal_profile['service_hours'] ) : ?>
				<div><span><?php esc_html_e( 'خدمة العملاء', 'alzaherah' ); ?></span><strong><?php echo esc_html( $legal_profile['service_hours'] ); ?></strong></div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- الشريط السفلي -->
	<div class="footer-bottom">
		<div class="container footer-bottom-inner">
			<div class="footer-legal-copy">
				<span class="footer-copyright">
					<?php
					printf(
						/* translators: 1: السنة الحالية، 2: اسم الموقع. */
						esc_html__( '© %1$s %2$s. جميع الحقوق محفوظة.', 'alzaherah' ),
						esc_html( wp_date( 'Y' ) ),
						esc_html( $brand_name )
					);
					?>
				</span>
				<span class="footer-platform-note"><?php esc_html_e( 'منصة التدريب الإلكترونية لمركز الزاهرة', 'alzaherah' ); ?></span>
			</div>
		</div>
	</div>

</footer>
<?php endif; ?>

<div class="toast" role="status" aria-live="polite"></div>

<?php wp_footer(); ?>
</body>
</html>
