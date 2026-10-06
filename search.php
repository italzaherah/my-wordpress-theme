<?php
/**
 * Site-wide search results and the empty-results state.
 *
 * @package Alzaherah
 * @since   4.9.0
 */

defined( 'ABSPATH' ) || exit;

$alz_query = get_search_query();
get_header();
?>

<main id="main" class="alz-search-page" role="main">
	<section class="section section-soft" aria-labelledby="alz-search-title">
		<div class="container">
			<span class="eyebrow"><?php esc_html_e( 'البحث في المنصة', 'alzaherah' ); ?></span>
			<h1 id="alz-search-title" class="section-title">
				<?php
				printf(
					/* translators: %s: search query. */
					esc_html__( 'نتائج البحث عن: %s', 'alzaherah' ),
					'<span>' . esc_html( $alz_query ) . '</span>'
				);
				?>
			</h1>
			<form class="alz-search-form" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label for="alz-site-search"><?php esc_html_e( 'عبارة البحث', 'alzaherah' ); ?></label>
				<div class="alz-search-form__row">
					<input id="alz-site-search" name="s" type="search" value="<?php echo esc_attr( $alz_query ); ?>" required>
					<button class="btn btn-primary" type="submit"><?php esc_html_e( 'بحث', 'alzaherah' ); ?></button>
				</div>
			</form>
		</div>
	</section>

	<section class="section" aria-label="<?php esc_attr_e( 'نتائج البحث', 'alzaherah' ); ?>">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<div class="alz-search-results" aria-live="polite">
					<?php while ( have_posts() ) : the_post(); ?>
						<article <?php post_class( 'alz-search-result' ); ?>>
							<p class="alz-search-result__type"><?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ?? __( 'محتوى', 'alzaherah' ) ); ?></p>
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 32, '…' ) ); ?></p>
							<a class="alz-text-link" href="<?php the_permalink(); ?>"><?php esc_html_e( 'عرض التفاصيل', 'alzaherah' ); ?></a>
						</article>
					<?php endwhile; ?>
				</div>
				<?php the_posts_pagination( array( 'screen_reader_text' => __( 'التنقل بين صفحات نتائج البحث', 'alzaherah' ) ) ); ?>
			<?php else : ?>
				<div class="alz-system-state" role="status" aria-live="polite">
					<span class="alz-system-state__code" aria-hidden="true">0</span>
					<h2><?php esc_html_e( 'لا توجد نتائج مطابقة', 'alzaherah' ); ?></h2>
					<p><?php esc_html_e( 'جرّب كلمة أقصر، أو تحقق من الكتابة، أو تصفح الدورات والمنتجات مباشرة.', 'alzaherah' ); ?></p>
					<div class="alz-system-state__actions">
						<a class="btn btn-primary" href="<?php echo esc_url( function_exists( 'alzaherah_shop_url' ) ? alzaherah_shop_url() : home_url( '/shop/' ) ); ?>"><?php esc_html_e( 'تصفح الدورات', 'alzaherah' ); ?></a>
						<a class="btn btn-secondary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'العودة للرئيسية', 'alzaherah' ); ?></a>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php get_footer(); ?>
