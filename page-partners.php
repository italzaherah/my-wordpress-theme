<?php
/**
 * Template Name: شركاء النجاح
 *
 * @package Alzaherah
 */
defined( 'ABSPATH' ) || exit;
get_header();
$partners = alzaherah_partner_public_query();
?>
<main id="main" class="site-main alz-partners-page" dir="rtl">
	<section class="alz-partners-hero">
		<div class="container">
			<span class="section-kicker">شراكات تصنع الأثر</span>
			<h1>شركاء النجاح</h1>
			<p>نعتز بالجهات التي تشاركنا تطوير التدريب وبناء فرص أكثر أثرًا. لا تظهر في هذه القائمة إلا الجهات التي اعتمدها المركز وتحقق من شعارها.</p>
			<a class="btn btn-primary" href="<?php echo esc_url( home_url( '/partnership-request/' ) ); ?>">تقديم طلب شراكة</a>
		</div>
	</section>

	<?php if ( $partners->have_posts() ) : ?>
	<section class="container alz-partners-directory" aria-labelledby="partners-title">
		<div class="alz-partners-heading">
			<div>
				<span class="section-kicker">علاقات موثوقة</span>
				<h2 id="partners-title">جهات نفخر بالعمل معها</h2>
			</div>
			<p>شراكات تدريبية ومجتمعية وتقنية تسهم في توسيع أثر التدريب وخدمة المستفيدين.</p>
		</div>
		<div class="alz-partners-grid">
			<?php while ( $partners->have_posts() ) : $partners->the_post(); ?>
				<?php $website = get_post_meta( get_the_ID(), '_alz_partner_website', true ); ?>
				<article class="alz-partner-card">
					<?php if ( $website ) : ?><a href="<?php echo esc_url( $website ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( 'زيارة الموقع الرسمي لـ ' . get_the_title() ); ?>"><?php endif; ?>
					<div class="alz-partner-logo">
						<?php the_post_thumbnail( 'medium', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => 'شعار ' . get_the_title() ) ); ?>
					</div>
					<h3><?php the_title(); ?></h3>
					<?php if ( $website ) : ?><span class="alz-partner-link">الموقع الرسمي ↗</span></a><?php endif; ?>
				</article>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="container alz-partners-join-cta" aria-labelledby="partners-join-title">
		<div>
			<span class="section-kicker">شراكة تبدأ بخطوة</span>
			<h2 id="partners-join-title">هل ترغب في الانضمام إلى شركاء النجاح؟</h2>
			<p>خصصنا صفحة مستقلة لاستقبال بيانات الجهة والشعار ومقترح الشراكة ومراجعتها بأمان.</p>
		</div>
		<a class="btn btn-primary" href="<?php echo esc_url( home_url( '/partnership-request/' ) ); ?>">تقديم طلب شراكة</a>
	</section>
</main>
<?php get_footer(); ?>
