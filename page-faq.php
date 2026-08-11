<?php
/**
 * قالب صفحة "الأسئلة الشائعة".
 *
 * يعمل تلقائيًا لأي صفحة رابطها الدائم (slug) هو: faq
 * - أكورديون أصلي (details/summary) يعمل دون أي جافاسكربت.
 * - بيانات FAQPage المنظمة (Schema.org) لتحسين الظهور في نتائج جوجل.
 *
 * @package Alzaherah
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

/*
 * الأسئلة والأجوبة الافتراضية.
 * لتعديلها لاحقًا من لوحة التحكم يمكن نقلها إلى إضافة ACF أو حقول مخصصة —
 * حاليًا تُعدَّل من هذا الملف مباشرة وكلها قابلة للترجمة.
 */
$alz_faqs = array(
	array(
		'q' => __( 'كيف أسجّل في دورة؟', 'alzaherah' ),
		'a' => __( 'افتح صفحة الدورة، راجع الموعد والرسوم، ثم اضغط زر التسجيل وأكمل بياناتك والدفع. يصلك تأكيد المقعد والفاتورة على بريدك فور نجاح العملية.', 'alzaherah' ),
	),
	array(
		'q' => __( 'ما وسائل الدفع المتاحة؟', 'alzaherah' ),
		'a' => __( 'نقبل الدفع عبر مدى وبطاقات فيزا وماستركارد وApple Pay وSTC Pay من خلال بوابة دفع إلكترونية آمنة ومشفّرة.', 'alzaherah' ),
	),
	array(
		'q' => __( 'هل الدورات حضورية أم عن بُعد؟', 'alzaherah' ),
		'a' => __( 'لدينا النوعان. نمط التقديم موضح بشكل واضح في صفحة كل دورة قبل التسجيل، وبعض البرامج تتيح الخيارين معًا.', 'alzaherah' ),
	),
	array(
		'q' => __( 'هل أحصل على شهادة بعد إتمام الدورة؟', 'alzaherah' ),
		'a' => __( 'نعم، تُمنح شهادة إتمام للمتدربين المستوفين لشروط الحضور، وتفاصيل الشهادة مذكورة في وصف كل برنامج.', 'alzaherah' ),
	),
	array(
		'q' => __( 'هل يمكنني إلغاء التسجيل واسترجاع الرسوم؟', 'alzaherah' ),
		'a' => __( 'نعم وفق سياسة الإلغاء والاسترجاع المعتمدة لدينا. راجع صفحة «الإلغاء والاسترجاع» لمعرفة المدد والشروط والحالات المشمولة.', 'alzaherah' ),
	),
	array(
		'q' => __( 'هل تقدمون برامج خاصة لجهات العمل؟', 'alzaherah' ),
		'a' => __( 'نعم، نصمم برامج تدريبية مخصصة للجهات الحكومية والخاصة حسب احتياجها. تواصل معنا عبر صفحة «تواصل معنا» وسنعود إليك بعرض مفصل.', 'alzaherah' ),
	),
	array(
		'q' => __( 'لم تصلني رسالة التأكيد، ماذا أفعل؟', 'alzaherah' ),
		'a' => __( 'تحقق أولًا من مجلد الرسائل غير المرغوبة (Spam). إن لم تجدها خلال ١٥ دقيقة من الدفع فتواصل معنا برقم الطلب وسنعالج الأمر فورًا.', 'alzaherah' ),
	),
);
?>

<main id="main" role="main">

	<!-- ترويسة الصفحة -->
	<section class="section section-soft">
		<div class="container">
			<div class="section-heading">
				<div class="text">
					<span class="eyebrow"><?php esc_html_e( 'مركز المساعدة', 'alzaherah' ); ?></span>
					<h1 class="section-title"><?php esc_html_e( 'الأسئلة الشائعة', 'alzaherah' ); ?></h1>
					<p class="section-copy">
						<?php esc_html_e( 'جمعنا لك إجابات أكثر ما يصلنا من استفسارات حول التسجيل والدفع والشهادات.', 'alzaherah' ); ?>
					</p>
				</div>
			</div>
		</div>
	</section>

	<!-- محتوى إضافي من المحرر (اختياري) -->
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

	<!-- الأسئلة -->
	<section class="section">
		<div class="container">
			<div class="faq-list">

				<?php foreach ( $alz_faqs as $alz_index => $alz_faq ) : ?>

					<details class="faq-item" <?php echo ( 0 === $alz_index ) ? 'open' : ''; ?>>
						<summary class="faq-question">
							<?php echo esc_html( $alz_faq['q'] ); ?>
						</summary>
						<div class="faq-answer">
							<p><?php echo esc_html( $alz_faq['a'] ); ?></p>
						</div>
					</details>

				<?php endforeach; ?>

			</div>
		</div>
	</section>

	<!-- دعوة ختامية -->
	<section class="section-compact">
		<div class="container">
			<div class="cta">
				<div>
					<h2><?php esc_html_e( 'لم تجد إجابتك؟', 'alzaherah' ); ?></h2>
					<p><?php esc_html_e( 'راسلنا مباشرة وسنجيبك بأسرع وقت ممكن.', 'alzaherah' ); ?></p>
				</div>
				<a class="btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
					<?php esc_html_e( 'تواصل معنا', 'alzaherah' ); ?>
				</a>
			</div>
		</div>
	</section>

</main>

<?php
/*
 * بيانات FAQPage المنظمة — تُمكّن جوجل من عرض الأسئلة
 * مباشرة في نتائج البحث أسفل رابط الموقع.
 */
$alz_faq_schema = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => array(),
);

foreach ( $alz_faqs as $alz_faq ) {
	$alz_faq_schema['mainEntity'][] = array(
		'@type'          => 'Question',
		'name'           => $alz_faq['q'],
		'acceptedAnswer' => array(
			'@type' => 'Answer',
			'text'  => $alz_faq['a'],
		),
	);
}
?>
<script type="application/ld+json"><?php echo wp_json_encode( $alz_faq_schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>

<?php get_footer(); ?>
