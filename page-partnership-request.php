<?php
/**
 * Template Name: طلب شراكة
 *
 * @package Alzaherah
 * @since   3.10.3
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="site-main alz-partners-page alz-partnership-request-page" dir="rtl">
	<section class="alz-partnership-request-hero">
		<div class="container">
			<a class="alz-request-back" href="<?php echo esc_url( home_url( '/partners/' ) ); ?>">العودة إلى شركاء النجاح</a>
			<span class="section-kicker">لنصنع أثرًا مشتركًا</span>
			<h1>تقديم طلب شراكة</h1>
			<p>أرسل بيانات الجهة ومقترح الشراكة من خلال النموذج المخصص. لا يظهر أي شعار في الموقع قبل مراجعته واعتماده من إدارة المركز.</p>
		</div>
	</section>

	<section class="container alz-partner-application" aria-labelledby="partner-form-title">
		<div class="alz-partner-form-intro">
			<span class="section-kicker">طلب منظم وآمن</span>
			<h2 id="partner-form-title">طلب الانضمام كشريك نجاح</h2>
			<p>راجع البيانات قبل الإرسال، وتأكد أن الشعار المرفوع رسمي وواضح وأنك مخول باستخدامه وتقديم الطلب باسم الجهة.</p>
			<ul>
				<li>مراجعة هوية الجهة وبيانات التواصل</li>
				<li>فحص جودة الشعار ومصدره وحق استخدامه</li>
				<li>اعتماد يدوي قبل الظهور في الموقع</li>
			</ul>
		</div>
		<div class="alz-partner-form-card" id="alz-partner-form-card">
			<?php if ( isset( $_GET['partner_submitted'] ) && absint( wp_unslash( $_GET['partner_submitted'] ) ) ) : ?>
				<?php $partner_reference = absint( wp_unslash( $_GET['partner_submitted'] ) ); ?>
				<div class="woocommerce-message" role="status">
					<?php
					printf(
						/* translators: %d: saved partner request ID. */
						esc_html__( 'تم حفظ طلب الشراكة بنجاح. رقم الطلب #%d، وسيقوم الفريق بمراجعته والتواصل معكم.', 'alzaherah' ),
						$partner_reference
					);
					?>
				</div>
			<?php elseif ( isset( $_GET['partner_error'] ) ) : ?>
				<?php $partner_error = sanitize_key( wp_unslash( $_GET['partner_error'] ) ); ?>
				<div class="woocommerce-error" role="alert">
					<?php
					echo esc_html(
						'spam' === $partner_error
							? 'تعذر التحقق من النموذج لأن المتصفح ملأ حقل حماية مخفيًا. أوقف الملء التلقائي لهذه الصفحة ثم أعد الإرسال.'
							: ( 'captcha' === $partner_error
							? 'تعذر إكمال التحقق الأمني. حدّث الصفحة ثم حاول مرة أخرى.'
							: ( 'rate' === $partner_error
								? 'تم تجاوز عدد المحاولات المسموح مؤقتًا. انتظر قليلًا ثم أعد المحاولة.'
								: 'تعذر إرسال الطلب. تحقق من الحقول المطلوبة وصيغة البريد والشعار ثم حاول مرة أخرى.' ) )
					);
					?>
				</div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( get_permalink() ); ?>" enctype="multipart/form-data" class="alz-partner-form">
				<?php wp_nonce_field( 'alz_partner_submit', 'alz_partner_nonce' ); ?>
				<input type="hidden" name="alz_partner_action" value="submit">
				<input type="hidden" name="alz_partner_return_url" value="<?php echo esc_url( get_permalink() ); ?>">
				<div class="alz-honeypot" aria-hidden="true">
					<label>حقل تحقق آلي
						<input type="text" name="alz_partner_fax_check" tabindex="-1" autocomplete="one-time-code" data-lpignore="true" data-1p-ignore="true" data-bwignore="true" inputmode="none">
					</label>
				</div>
				<p><label>اسم الجهة الرسمي <span aria-hidden="true">*</span><input type="text" name="organization_name" required maxlength="180" autocomplete="organization"></label></p>
				<p><label>اسم مسؤول التواصل <span aria-hidden="true">*</span><input type="text" name="contact_name" required maxlength="120" autocomplete="name"></label></p>
				<div class="alz-form-grid">
					<p><label>المسمى الوظيفي<input type="text" name="job_title" maxlength="120" autocomplete="organization-title"></label></p>
					<p><label>رقم السجل أو الترخيص<input type="text" name="registration_no" maxlength="80" inputmode="numeric"></label></p>
					<p><label>البريد الإلكتروني الرسمي <span aria-hidden="true">*</span><input type="email" name="email" required maxlength="190" autocomplete="email"></label></p>
					<p><label>رقم الجوال/الهاتف <span aria-hidden="true">*</span><input type="tel" name="phone" required maxlength="30" autocomplete="tel"></label></p>
					<p><label>الموقع الرسمي<input type="url" name="website" maxlength="250" placeholder="https://"></label></p>
					<p><label>نوع الشراكة المقترحة<input type="text" name="partnership_type" maxlength="140" placeholder="تدريبية، مجتمعية، تقنية…"></label></p>
				</div>
				<p><label>تفاصيل الطلب وأهداف الشراكة<textarea name="message" rows="5" maxlength="2500"></textarea></label></p>
				<p class="alz-partner-logo-field"><label>شعار الجهة (PNG أو JPG أو WebP) <span aria-hidden="true">*</span><input type="file" name="partner_logo" required accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"></label><small>سيُعرض الشعار بنسبته الأصلية داخل مساحة موحدة دون قص أو تمديد. الحد الأقصى 2MB، والأبعاد لا تقل عن 200×120 بكسل، ويفضّل PNG بخلفية شفافة.</small></p>
				<p class="alz-partner-consent"><label><input type="checkbox" name="privacy_consent" value="yes" required> أقر بصحة البيانات وبأنني مخول بتقديم الطلب واستخدام الشعار، وأوافق على معالجة البيانات لغرض مراجعة الشراكة وفق <a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>" target="_blank" rel="noopener">سياسة الخصوصية</a>.</label></p>
				<?php if ( function_exists( 'alz_turnstile_render' ) ) { alz_turnstile_render( 'partner_request' ); } ?>
				<button type="submit" class="btn btn-primary alz-partner-submit"><span>إرسال طلب الشراكة</span><?php echo alzaherah_ui_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</form>
		</div>
	</section>
</main>
<?php get_footer(); ?>
