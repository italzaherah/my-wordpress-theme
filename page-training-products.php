<?php
/**
 * Template Name: صفحة المنتجات التدريبية
 * Template Post Type: page
 *
 * كتالوج المنتجات الرقمية بنفس هيكل صفحة الدورات (Hero + شبكة كاملة العرض)،
 * دون المرور بقالب الصفحات العامة الضيق الذي يكرر العنوان.
 *
 * @package Alzaherah
 * @since   4.8.5
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main" class="courses-page alz-training-products-page" role="main">

	<section class="courses-hero" aria-labelledby="training-products-hero-title">
		<div class="container courses-hero-inner">
			<div>
				<span class="courses-kicker"><?php esc_html_e( 'ملفات وقوالب رقمية', 'alzaherah' ); ?></span>
				<h1 id="training-products-hero-title"><?php esc_html_e( 'المنتجات التدريبية', 'alzaherah' ); ?></h1>
				<p><?php esc_html_e( 'مواد عملية جاهزة للتنزيل، مع دفع آمن ورابط تنزيل صالح لمدة ثلاثة أشهر بعد تأكيد الدفع.', 'alzaherah' ); ?></p>
			</div>
			<div class="courses-hero-badges" aria-label="<?php esc_attr_e( 'مزايا المنتجات الرقمية', 'alzaherah' ); ?>">
				<span>✓ <?php esc_html_e( 'تنزيل محمي', 'alzaherah' ); ?></span>
				<span>✓ <?php esc_html_e( 'صلاحية 90 يومًا', 'alzaherah' ); ?></span>
				<span>✓ <?php esc_html_e( 'تأكيد الطلب عبر البريد الإلكتروني', 'alzaherah' ); ?></span>
			</div>
		</div>
	</section>

	<section class="courses-content section-soft">
		<div class="container">
			<?php
			while ( have_posts() ) :
				the_post();
				if ( shortcode_exists( 'alz_training_products' ) ) {
					echo do_shortcode( '[alz_training_products shell="content"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted Core shortcode.
				} else {
					the_content();
				}
			endwhile;
			?>
		</div>
	</section>
</main>
<?php
get_footer();
