<?php
/**
 * تذييل الموقع: التعريف بالمنصة + الروابط + السياسات + التواصل.
 *
 * @package Alzaherah
 * @since   3.4.1
 */

defined( 'ABSPATH' ) || exit;

$alz_phone     = get_theme_mod( 'alzaherah_phone', '+966553406661' );
$alz_email     = function_exists( 'alzaherah_contact_email' ) ? alzaherah_contact_email() : 'contact@alzaherah.edu.sa';
$alz_location  = get_theme_mod( 'alzaherah_location', __( 'الباحة، المملكة العربية السعودية', 'alzaherah' ) );
$brand_name    = __( 'مركز الزاهرة للتدريب', 'alzaherah' );
$theme_version = wp_get_theme()->get( 'Version' );
$brand_logo_url = add_query_arg( 'ver', $theme_version, get_theme_file_uri( 'assets/logo-mark.png' ) );
$legal_profile = function_exists( 'alzaherah_legal_profile' ) ? alzaherah_legal_profile() : array();
$news_term     = get_category_by_slug( 'alzaherah-news' );
$articles_term = get_category_by_slug( 'alzaherah-articles' );
$news_url      = $news_term ? get_category_link( $news_term->term_id ) : home_url( '/category/alzaherah-news/' );
$articles_url  = $articles_term ? get_category_link( $articles_term->term_id ) : home_url( '/category/alzaherah-articles/' );
$news_url      = is_wp_error( $news_url ) ? home_url( '/category/alzaherah-news/' ) : $news_url;
$articles_url  = is_wp_error( $articles_url ) ? home_url( '/category/alzaherah-articles/' ) : $articles_url;
?>

<footer class="site-footer" id="contact">

	<div class="container footer-main">

		<!-- التعريف بالمنصة -->
		<div class="footer-brand">
			<span class="footer-brand-row">
				<img class="brand-mark" src="<?php echo esc_url( $brand_logo_url ); ?>" alt="<?php esc_attr_e( 'شعار مركز الزاهرة للتدريب', 'alzaherah' ); ?>" width="56" height="57" loading="lazy">
				<span class="brand-text"><strong><?php echo esc_html( $brand_name ); ?></strong><small><?php esc_html_e( 'ALZAHERAH TRAINING CENTER', 'alzaherah' ); ?></small></span>
			</span>
			<p>
				<?php esc_html_e( 'منصة تدريب تجمع نخبة من البرامج الحضورية والإلكترونية، وتمنح الأفراد والمنشآت تجربة تسجيل ودفع متكاملة تحوّل التعلّم إلى أثر ملموس.', 'alzaherah' ); ?>
			</p>
		</div>

		<!-- روابط سريعة (من قائمة "روابط التذييل" إن وُجدت، وإلا الروابط الافتراضية) -->
		<div>
			<h3 class="footer-title"><?php esc_html_e( 'روابط سريعة', 'alzaherah' ); ?></h3>

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
					<a href="<?php echo esc_url( alzaherah_shop_url() ); ?>"><?php esc_html_e( 'البرامج التدريبية', 'alzaherah' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'عن المركز', 'alzaherah' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/partners/' ) ); ?>"><?php esc_html_e( 'شركاء النجاح', 'alzaherah' ); ?></a>
					<a href="<?php echo esc_url( $news_url ); ?>"><?php esc_html_e( 'أخبارنا', 'alzaherah' ); ?></a>
					<a href="<?php echo esc_url( $articles_url ); ?>"><?php esc_html_e( 'مقالاتنا', 'alzaherah' ); ?></a>
					<a href="<?php echo esc_url( alzaherah_account_url() ); ?>"><?php esc_html_e( 'حساب المتدرب', 'alzaherah' ); ?></a>
				</div>
				<?php
			}
			?>
		</div>

		<!-- السياسات -->
		<div>
			<h3 class="footer-title"><?php esc_html_e( 'السياسات', 'alzaherah' ); ?></h3>
			<div class="footer-links footer-policy-links">
				<a href="<?php echo esc_url( function_exists( 'get_privacy_policy_url' ) && get_privacy_policy_url() ? get_privacy_policy_url() : home_url( '/privacy-policy/' ) ); ?>">
					<?php esc_html_e( 'سياسة الخصوصية', 'alzaherah' ); ?>
				</a>
				<a href="<?php echo esc_url( home_url( '/refund-policy/' ) ); ?>"><?php esc_html_e( 'الإلغاء والاسترجاع', 'alzaherah' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'الشروط والأحكام', 'alzaherah' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/complaints-policy/' ) ); ?>"><?php esc_html_e( 'الشكاوى والمقترحات', 'alzaherah' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/policy-center/' ) ); ?>"><?php esc_html_e( 'جميع السياسات والحقوق', 'alzaherah' ); ?></a>
			</div>
		</div>

		<!-- التواصل ووسائل الدفع -->
		<div>
			<h3 class="footer-title"><?php esc_html_e( 'تواصل معنا', 'alzaherah' ); ?></h3>

			<div class="footer-contact">
				<a class="footer-contact-link is-phone" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $alz_phone ) ); ?>">
					<span aria-hidden="true">☎</span>
					<bdi dir="ltr"><?php echo esc_html( $alz_phone ); ?></bdi>
				</a>
				<a class="footer-contact-link is-email" href="mailto:<?php echo esc_attr( $alz_email ); ?>">
					<span aria-hidden="true">✉</span>
					<bdi dir="ltr"><?php echo esc_html( $alz_email ); ?></bdi>
				</a>
				<span class="footer-contact-link"><span aria-hidden="true">📍</span><span><?php echo esc_html( $alz_location ); ?></span></span>
			</div>

			<p class="payment-intro"><?php esc_html_e( 'نقبل الدفع الإلكتروني الآمن عبر:', 'alzaherah' ); ?></p>
			<div class="payment-row" role="list" aria-label="<?php esc_attr_e( 'وسائل الدفع المقبولة: مدى، فيزا، ماستركارد، Apple Pay وstc pay', 'alzaherah' ); ?>">
				<span class="payment payment--mada" role="listitem"><img src="<?php echo esc_url( add_query_arg( 'ver', $theme_version, get_template_directory_uri() . '/assets/payments/mada.svg' ) ); ?>" alt="<?php esc_attr_e( 'مدى', 'alzaherah' ); ?>" width="72" height="30" loading="lazy" decoding="async"></span>
				<span class="payment payment--visa" role="listitem"><img src="<?php echo esc_url( add_query_arg( 'ver', $theme_version, get_template_directory_uri() . '/assets/payments/visa.svg' ) ); ?>" alt="Visa" width="72" height="30" loading="lazy" decoding="async"></span>
				<span class="payment payment--mastercard" role="listitem"><img src="<?php echo esc_url( add_query_arg( 'ver', $theme_version, get_template_directory_uri() . '/assets/payments/mastercard.svg' ) ); ?>" alt="Mastercard" width="72" height="30" loading="lazy" decoding="async"></span>
				<span class="payment payment--apple-pay" role="listitem"><img src="<?php echo esc_url( add_query_arg( 'ver', $theme_version, get_template_directory_uri() . '/assets/payments/apple-pay.svg' ) ); ?>" alt="Apple Pay" width="72" height="30" loading="lazy" decoding="async"></span>
				<span class="payment payment--stc-pay" role="listitem"><img src="<?php echo esc_url( add_query_arg( 'ver', $theme_version, get_template_directory_uri() . '/assets/payments/stc-pay.svg' ) ); ?>" alt="stc pay" width="72" height="30" loading="lazy" decoding="async"></span>
			</div>
		</div>

	</div>

	<?php if ( $legal_profile ) : ?>
		<div class="container footer-compliance" aria-label="<?php esc_attr_e( 'البيانات النظامية للمنشأة', 'alzaherah' ); ?>">
			<div><span><?php esc_html_e( 'الاسم التجاري', 'alzaherah' ); ?></span><strong><?php echo esc_html( $legal_profile['commercial_name'] ); ?></strong></div>
			<div><span><?php esc_html_e( 'السجل التجاري', 'alzaherah' ); ?></span><strong><?php echo $legal_profile['commercial_reg'] ? esc_html( $legal_profile['commercial_reg'] ) : esc_html__( 'يستكمل قبل الإطلاق', 'alzaherah' ); ?></strong></div>
			<div><span><?php esc_html_e( 'ترخيص التدريب', 'alzaherah' ); ?></span><strong><?php echo $legal_profile['tvtc_license'] ? esc_html( $legal_profile['tvtc_license'] ) : esc_html__( 'يستكمل قبل الإطلاق', 'alzaherah' ); ?></strong></div>
			<?php if ( 'yes' === $legal_profile['tax_registered'] ) : ?>
				<div><span><?php esc_html_e( 'الرقم الضريبي', 'alzaherah' ); ?></span><strong><?php echo $legal_profile['tax_number'] ? esc_html( $legal_profile['tax_number'] ) : esc_html__( 'يستكمل قبل الإطلاق', 'alzaherah' ); ?></strong></div>
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
						/* translators: 1: السنة الحالية، 2: اسم الموقع */
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

<div class="toast" role="status" aria-live="polite"></div>

<?php wp_footer(); ?>
</body>
</html>
