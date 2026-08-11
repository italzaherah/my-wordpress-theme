<?php
/**
 * القالب العام للصفحات الثابتة.
 *
 * يُستخدم تلقائيًا لأي صفحة ليس لها قالب مخصص
 * (سياسة الخصوصية، الإلغاء والاسترجاع، الشروط والأحكام…).
 *
 * @package Alzaherah
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" role="main">

	<?php
	while ( have_posts() ) :
		the_post();
		$alz_page_title = get_the_title();
		if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() ) {
			$alz_page_title = __( 'تسجيل الدخول أو إنشاء حساب متدرب', 'alzaherah' );
		}
		?>

		<!-- ترويسة الصفحة -->
		<section class="section section-soft">
			<div class="container">
				<div class="section-heading">
					<div class="text">
						<span class="eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
						<h1 class="section-title"><?php echo esc_html( $alz_page_title ); ?></h1>

						<?php if ( has_excerpt() ) : ?>
							<p class="section-copy"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>

		<!-- محتوى الصفحة من المحرر -->
		<section class="section">
			<div class="container">
				<article <?php post_class( 'page-content' ); ?>>
					<?php
					the_content();

					wp_link_pages(
						array(
							'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'صفحات المقال', 'alzaherah' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
				</article>
			</div>
		</section>

		<?php
	endwhile;
	?>

	<!-- دعوة ختامية -->
	<section class="section-compact">
		<div class="container">
			<div class="cta">
				<div>
					<h2><?php esc_html_e( 'هل لديك استفسار؟', 'alzaherah' ); ?></h2>
					<p><?php esc_html_e( 'فريقنا جاهز للإجابة عن أسئلتك قبل التسجيل وبعده.', 'alzaherah' ); ?></p>
				</div>
				<a class="btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
					<?php esc_html_e( 'تواصل معنا', 'alzaherah' ); ?>
				</a>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
