<?php
/**
 * قالب صفحة "من نحن".
 *
 * يعمل تلقائيًا لأي صفحة رابطها الدائم (slug) هو: about
 *
 * @package Alzaherah
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

$alz_about_metrics = array(
	'trainees'          => 16450,
	'visitors'          => 4900,
	'completed_courses' => 274,
	'trainers'          => 40,
);

if ( is_callable( array( 'ALZ_Home_Content', 'get_metrics' ) ) ) {
	$alz_saved_metrics = ALZ_Home_Content::get_metrics();
	if ( is_array( $alz_saved_metrics ) ) {
		foreach ( $alz_about_metrics as $alz_metric_key => $alz_metric_default ) {
			if ( isset( $alz_saved_metrics[ $alz_metric_key ] ) ) {
				$alz_about_metrics[ $alz_metric_key ] = min( 999999999, absint( $alz_saved_metrics[ $alz_metric_key ] ) );
			}
		}
	}
}

$alz_about_metric_labels = array(
	'trainees'          => __( 'عدد المتدربين', 'alzaherah' ),
	'visitors'          => __( 'عدد الزوار', 'alzaherah' ),
	'completed_courses' => __( 'عدد الدورات المقامة', 'alzaherah' ),
	'trainers'          => __( 'عدد المدربين', 'alzaherah' ),
);

get_header();
?>

<main id="main" role="main">

	<!-- ترويسة الصفحة -->
	<section class="section section-soft">
		<div class="container">
			<div class="section-heading">
				<div class="text">
					<span class="eyebrow"><?php esc_html_e( 'تعرّف علينا', 'alzaherah' ); ?></span>
					<h1 class="section-title"><?php esc_html_e( 'شريكك في رحلة التطوير المهني', 'alzaherah' ); ?></h1>
					<p class="section-copy">
						<?php esc_html_e( 'منصة تدريب سعودية تجمع بين خبرة المدربين المعتمدين وتجربة تسجيل رقمية متكاملة، لنجعل التعلّم أقرب وأسهل للأفراد والمنشآت.', 'alzaherah' ); ?>
					</p>
				</div>
			</div>
		</div>
	</section>

	<!-- محتوى الصفحة من المحرر (اختياري: قصة المركز، الرخص، الاعتمادات…) -->
	<?php
	while ( have_posts() ) :
		the_post();

		if ( '' !== trim( get_the_content() ) ) :
			?>
			<section class="section">
				<div class="container">
					<article class="page-content">
						<?php the_content(); ?>
					</article>
				</div>
			</section>
			<?php
		endif;

	endwhile;
	?>

	<!-- أثر المركز بالأرقام: مصدر واحد مشترك مع الصفحة الرئيسية ولوحة المنصة. -->
	<section class="section alz3-impact alz3-about-impact" aria-labelledby="about-impact-title">
		<div class="container">
			<div class="alz3-centered-heading alz3-impact-heading">
				<span class="eyebrow"><?php esc_html_e( 'أثر مستمر', 'alzaherah' ); ?></span>
				<h2 class="section-title" id="about-impact-title"><?php esc_html_e( 'المركز بالأرقام', 'alzaherah' ); ?></h2>
			</div>

			<dl class="alz3-impact-grid">
				<?php foreach ( $alz_about_metric_labels as $alz_metric_key => $alz_metric_label ) : ?>
					<?php $alz_metric_value = $alz_about_metrics[ $alz_metric_key ]; ?>
					<div class="alz3-impact-card">
						<dt><?php echo esc_html( $alz_metric_label ); ?></dt>
						<dd aria-label="<?php echo esc_attr( sprintf( __( 'أكثر من %1$s، %2$s', 'alzaherah' ), number_format_i18n( $alz_metric_value ), $alz_metric_label ) ); ?>">
							<bdi dir="ltr" class="alz3-metric-value" data-counter="<?php echo esc_attr( $alz_metric_value ); ?>" data-prefix="+">+<?php echo esc_html( number_format_i18n( $alz_metric_value ) ); ?></bdi>
						</dd>
					</div>
				<?php endforeach; ?>
			</dl>
		</div>
	</section>

	<!-- رسالتنا وقيمنا -->
	<section class="section">
		<div class="container">

			<div class="section-heading">
				<div class="text">
					<span class="eyebrow"><?php esc_html_e( 'لماذا نحن؟', 'alzaherah' ); ?></span>
					<h2 class="section-title"><?php esc_html_e( 'مبادئ لا نتنازل عنها', 'alzaherah' ); ?></h2>
				</div>
			</div>

			<div class="categories">

				<div class="category-card">
					<div class="category-icon" aria-hidden="true">◎</div>
					<h3><?php esc_html_e( 'جودة المحتوى', 'alzaherah' ); ?></h3>
					<p><?php esc_html_e( 'برامج مبنية على احتياج فعلي، يقدمها مدربون معتمدون بخبرة ميدانية.', 'alzaherah' ); ?></p>
				</div>

				<div class="category-card">
					<div class="category-icon" aria-hidden="true">◈</div>
					<h3><?php esc_html_e( 'شفافية كاملة', 'alzaherah' ); ?></h3>
					<p><?php esc_html_e( 'الموعد والرسوم والمحاور واضحة قبل الدفع، دون أي رسوم خفية.', 'alzaherah' ); ?></p>
				</div>

				<div class="category-card">
					<div class="category-icon" aria-hidden="true">✦</div>
					<h3><?php esc_html_e( 'تجربة رقمية', 'alzaherah' ); ?></h3>
					<p><?php esc_html_e( 'تسجيل ودفع وفوترة إلكترونية تكتمل من الجوال في دقائق.', 'alzaherah' ); ?></p>
				</div>

				<div class="category-card">
					<div class="category-icon" aria-hidden="true">✓</div>
					<h3><?php esc_html_e( 'أثر يُقاس', 'alzaherah' ); ?></h3>
					<p><?php esc_html_e( 'نقيس رضا المتدربين ونطور برامجنا بناء على تقييماتهم.', 'alzaherah' ); ?></p>
				</div>

			</div>

		</div>
	</section>

	<!-- دعوة ختامية -->
	<section class="section-compact">
		<div class="container">
			<div class="cta">
				<div>
					<h2><?php esc_html_e( 'انضم إلى آلاف المتدربين', 'alzaherah' ); ?></h2>
					<p><?php esc_html_e( 'اطّلع على البرامج المتاحة واحجز مقعدك اليوم.', 'alzaherah' ); ?></p>
				</div>
				<a class="btn" href="<?php echo esc_url( alzaherah_shop_url() ); ?>">
					<?php esc_html_e( 'تصفّح البرامج', 'alzaherah' ); ?>
				</a>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
