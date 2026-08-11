<?php
/**
 * قالب صفحة "تواصل معنا".
 *
 * يعمل تلقائيًا لأي صفحة رابطها الدائم (slug) هو: contact
 * ضع كود نموذج التواصل (مثل [contact-form-7]) في محرر الصفحة وسيظهر أسفل بطاقات التواصل.
 *
 * @package Alzaherah
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

$alz_phone    = get_theme_mod( 'alzaherah_phone', '+966553406661' );
$alz_email    = function_exists( 'alzaherah_contact_email' ) ? alzaherah_contact_email() : 'contact@alzaherah.edu.sa';
$alz_location = get_theme_mod( 'alzaherah_location', __( 'الباحة، المملكة العربية السعودية', 'alzaherah' ) );
$alz_tel_link = preg_replace( '/\s+/', '', $alz_phone );
?>

<main id="main" role="main">

	<!-- ترويسة الصفحة -->
	<section class="section section-soft">
		<div class="container">
			<div class="section-heading">
				<div class="text">
					<span class="eyebrow"><?php esc_html_e( 'نسعد بخدمتك', 'alzaherah' ); ?></span>
					<h1 class="section-title"><?php esc_html_e( 'تواصل معنا', 'alzaherah' ); ?></h1>
					<p class="section-copy">
						<?php esc_html_e( 'استفسار عن برنامج؟ طلب تدريب لجهة عمل؟ راسلنا بالطريقة الأنسب لك وسنرد عليك بأقرب وقت.', 'alzaherah' ); ?>
					</p>
				</div>
			</div>
		</div>
	</section>

	<!-- بطاقات التواصل -->
	<section class="section">
		<div class="container">
			<div class="categories">

				<a class="category-card" href="tel:<?php echo esc_attr( $alz_tel_link ); ?>">
					<div class="category-icon" aria-hidden="true">☎</div>
					<h3><?php esc_html_e( 'اتصال مباشر', 'alzaherah' ); ?></h3>
					<p dir="ltr"><?php echo esc_html( $alz_phone ); ?></p>
				</a>

				<a class="category-card" href="https://wa.me/<?php echo esc_attr( ltrim( $alz_tel_link, '+' ) ); ?>" target="_blank" rel="noopener noreferrer">
					<div class="category-icon" aria-hidden="true">💬</div>
					<h3><?php esc_html_e( 'واتساب', 'alzaherah' ); ?></h3>
					<p><?php esc_html_e( 'الرد الأسرع خلال أوقات العمل', 'alzaherah' ); ?></p>
				</a>

				<a class="category-card" href="mailto:<?php echo esc_attr( $alz_email ); ?>">
					<div class="category-icon" aria-hidden="true">✉</div>
					<h3><?php esc_html_e( 'البريد الإلكتروني', 'alzaherah' ); ?></h3>
					<p dir="ltr"><?php echo esc_html( $alz_email ); ?></p>
				</a>

				<div class="category-card">
					<div class="category-icon" aria-hidden="true">📍</div>
					<h3><?php esc_html_e( 'المقر', 'alzaherah' ); ?></h3>
					<p><?php echo esc_html( $alz_location ); ?></p>
				</div>

			</div>
		</div>
	</section>

	<!-- محتوى الصفحة: نموذج التواصل من المحرر -->
	<?php
	while ( have_posts() ) :
		the_post();

		if ( '' !== trim( get_the_content() ) ) :
			?>
			<section class="section section-soft">
				<div class="container">

					<div class="section-heading">
						<div class="text">
							<span class="eyebrow"><?php esc_html_e( 'أو راسلنا مباشرة', 'alzaherah' ); ?></span>
							<h2 class="section-title"><?php esc_html_e( 'أرسل استفسارك', 'alzaherah' ); ?></h2>
						</div>
					</div>

					<article class="page-content">
						<?php the_content(); ?>
					</article>

				</div>
			</section>
			<?php
		endif;

	endwhile;
	?>

	<!-- دعوة ختامية -->
	<section class="section-compact">
		<div class="container">
			<div class="cta">
				<div>
					<h2><?php esc_html_e( 'تبحث عن دورة معينة؟', 'alzaherah' ); ?></h2>
					<p><?php esc_html_e( 'ربما تجد ما تبحث عنه بين برامجنا المتاحة الآن.', 'alzaherah' ); ?></p>
				</div>
				<a class="btn" href="<?php echo esc_url( alzaherah_shop_url() ); ?>">
					<?php esc_html_e( 'تصفّح البرامج', 'alzaherah' ); ?>
				</a>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
