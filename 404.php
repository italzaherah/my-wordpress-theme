<?php
/**
 * Not-found page.
 *
 * @package Alzaherah
 * @since   4.9.0
 */

defined( 'ABSPATH' ) || exit;
get_header();
?>

<main id="main" class="alz-system-page" role="main">
	<section class="section">
		<div class="container">
			<div class="alz-system-state" role="status">
				<span class="alz-system-state__code" aria-hidden="true">404</span>
				<h1><?php esc_html_e( 'هذه الصفحة غير موجودة', 'alzaherah' ); ?></h1>
				<p><?php esc_html_e( 'قد يكون الرابط قديمًا أو غير مكتمل. يمكنك البحث أو العودة إلى الصفحة الرئيسية.', 'alzaherah' ); ?></p>
				<form class="alz-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label for="alz-404-search"><?php esc_html_e( 'ابحث في المنصة', 'alzaherah' ); ?></label>
					<div class="alz-search-form__row">
						<input id="alz-404-search" name="s" type="search" required>
						<button class="btn btn-primary" type="submit"><?php esc_html_e( 'بحث', 'alzaherah' ); ?></button>
					</div>
				</form>
				<div class="alz-system-state__actions">
					<a class="btn btn-secondary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'العودة للرئيسية', 'alzaherah' ); ?></a>
				</div>
			</div>
		</div>
	</section>
</main>

<?php get_footer(); ?>
