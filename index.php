<?php
/**
 * Generic archive fallback with one page-level heading.
 *
 * @package Alzaherah
 */

get_header();
?>
<main id="main" class="section">
	<div class="container">
		<header class="section-heading">
			<h1 class="section-title"><?php esc_html_e( 'أحدث المحتوى', 'alzaherah' ); ?></h1>
		</header>
		<?php if ( have_posts() ) : ?>
			<div class="archive-list">
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>
					<article <?php post_class( 'archive-list-item' ); ?>>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php the_excerpt(); ?>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<div class="empty-state"><h2><?php esc_html_e( 'لا يوجد محتوى منشور حاليًا', 'alzaherah' ); ?></h2></div>
		<?php endif; ?>
	</div>
</main>
<?php get_footer(); ?>
