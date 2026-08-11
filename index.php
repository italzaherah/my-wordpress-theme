<?php get_header(); ?>
<main id="main" class="section"><div class="container">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
<article <?php post_class(); ?>><h1 class="section-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1><?php the_excerpt(); ?></article>
<?php endwhile; the_posts_pagination(); else: ?><p>لا يوجد محتوى.</p><?php endif; ?>
</div></main>
<?php get_footer(); ?>
