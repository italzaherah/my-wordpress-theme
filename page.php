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

/*
 * Resolve the template mode before opening <main>. Managed policy documents
 * and WooCommerce account need a wide application shell; generic editor
 * content deliberately uses a narrower reading measure.
 */
$alz_is_policy_page  = function_exists( 'alzaherah_is_managed_policy_page' ) && alzaherah_is_managed_policy_page();
$alz_is_account_page = function_exists( 'is_account_page' ) && is_account_page();
$alz_is_exams_page   = function_exists( 'alzaherah_is_exams_catalog_page' ) && alzaherah_is_exams_catalog_page();
$alz_exam_focus      = function_exists( 'alzaherah_is_active_exam_attempt' ) && alzaherah_is_active_exam_attempt();
$alz_main_class      = 'alz-generic-page-main';
if ( $alz_is_policy_page ) {
	$alz_main_class = 'alz-policy-page-main';
} elseif ( $alz_is_account_page ) {
	$alz_main_class = 'alz-account-page-main';
} elseif ( $alz_is_exams_page ) {
	$alz_main_class = 'alz-catalog-page-main alz-exams-catalog-main';
}

get_header();
?>

<main id="main" class="<?php echo esc_attr( $alz_main_class ); ?>" role="main">

	<?php
	while ( have_posts() ) :
		the_post();
		$alz_page_title = get_the_title();
		if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() ) {
			if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'lost-password' ) ) {
				$alz_page_title = __( 'استعادة كلمة المرور', 'alzaherah' );
			} else {
				$alz_view       = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'login';
				$alz_page_title = 'register' === $alz_view ? __( 'إنشاء حساب متدرب', 'alzaherah' ) : __( 'تسجيل الدخول', 'alzaherah' );
			}
		}
		?>

		<?php if ( $alz_exam_focus ) : ?>
		<h1 class="screen-reader-text"><?php echo esc_html( $alz_page_title ); ?></h1>
		<?php elseif ( ! $alz_is_policy_page ) : ?>
		<!-- ترويسة الصفحة -->
		<section class="section section-soft<?php echo $alz_is_account_page ? ' alz-account-page-hero' : ( $alz_is_exams_page ? ' alz-catalog-page-hero' : '' ); ?>">
			<div class="container<?php echo $alz_is_account_page ? ' alz-account-page-container' : ( $alz_is_exams_page ? ' alz-catalog-page-container' : '' ); ?>">
				<div class="section-heading">
					<div class="text">
						<span class="eyebrow"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
						<h1 class="section-title"><?php echo esc_html( $alz_page_title ); ?></h1>

						<?php if ( ! $alz_is_account_page && has_excerpt() ) : ?>
							<p class="section-copy"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<!-- محتوى الصفحة من المحرر -->
		<section class="section<?php echo $alz_is_policy_page ? ' alz-policy-page-section' : ( $alz_is_account_page ? ' alz-account-page-body' : ( $alz_is_exams_page ? ' alz-catalog-page-body' : '' ) ); ?>">
			<div class="container<?php echo $alz_is_policy_page ? ' alz-policy-page-container' : ( $alz_is_account_page ? ' alz-account-page-container' : ( $alz_is_exams_page ? ' alz-catalog-page-container' : '' ) ); ?>">
				<article <?php post_class( $alz_is_policy_page ? 'page-content alz-policy-page-content' : ( $alz_is_account_page ? 'page-content alz-account-page-content' : ( $alz_is_exams_page ? 'page-content alz-catalog-page-content' : 'page-content' ) ) ); ?>>
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

	<?php if ( ! $alz_is_policy_page && ! $alz_is_account_page && ! $alz_is_exams_page ) : ?>
	<!-- دعوة ختامية -->
	<section class="section-compact">
		<div class="container">
			<div class="cta">
				<div>
					<h2><?php esc_html_e( 'هل لديك استفسار؟', 'alzaherah' ); ?></h2>
					<p><?php esc_html_e( 'فريقنا جاهز للإجابة عن استفساراتك قبل الشراء وبعده.', 'alzaherah' ); ?></p>
				</div>
				<a class="btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
					<?php esc_html_e( 'تواصل معنا', 'alzaherah' ); ?>
				</a>
			</div>
		</div>
	</section>
	<?php endif; ?>

</main>

<?php get_footer(); ?>
