<?php
/**
 * Server-level 500 template. Copy to the host error-document location only
 * during an approved deployment; WordPress does not route 500 responses here.
 * Fonts load from the theme's root-relative URL first because the copied file is
 * served from other paths, where "../assets" would not resolve.
 */
http_response_code( 500 );
header( 'Content-Type: text/html; charset=UTF-8' );
$alz_system_theme_url = defined( 'ALZAHERAH_SYSTEM_THEME_URL' )
	? (string) ALZAHERAH_SYSTEM_THEME_URL
	: ( defined( 'WP_CONTENT_URL' ) ? rtrim( (string) WP_CONTENT_URL, '/' ) . '/themes/alzaherah-theme-v3' : '/wp-content/themes/alzaherah-theme-v3' );
$alz_system_theme_url = htmlspecialchars( rtrim( $alz_system_theme_url, '/' ), ENT_QUOTES, 'UTF-8' );
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>تعذر إكمال الطلب — مركز الزاهرة للتدريب</title>
	<style>
		@font-face{font-family:"Alexandria";font-style:normal;font-weight:100 900;font-display:swap;src:url("<?php echo $alz_system_theme_url; ?>/assets/fonts/alexandria-var-arabic.woff2") format("woff2"),url("../assets/fonts/alexandria-var-arabic.woff2") format("woff2");unicode-range:U+0600-06FF,U+0750-077F,U+0870-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FEFC}
		@font-face{font-family:"Alexandria";font-style:normal;font-weight:100 900;font-display:swap;src:url("<?php echo $alz_system_theme_url; ?>/assets/fonts/alexandria-var-latin.woff2") format("woff2"),url("../assets/fonts/alexandria-var-latin.woff2") format("woff2");unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
		:root{--alz-navy-900:#14213d;--alz-purple-600:#5b2ccb;--alz-line:#e7e9f1;--alz-surface:#fff;--alz-surface-subtle:#f7f8fc;--alz-radius-lg:24px;--alz-radius-sm:12px}
		*{box-sizing:border-box}
		body{margin:0;padding:24px;font-family:"Alexandria",Tahoma,Arial,sans-serif;font-size:16px;font-synthesis-weight:none;line-height:1.85;background:var(--alz-surface-subtle);color:var(--alz-navy-900);display:grid;min-height:100vh;place-items:center}
		.state{width:min(100%,42rem);padding:32px;text-align:center;background:var(--alz-surface);border:1px solid var(--alz-line);border-radius:var(--alz-radius-lg)}
		.code{font-size:4rem;font-weight:700;color:var(--alz-purple-600)}
		a{display:inline-flex;min-height:44px;align-items:center;padding:0 20px;border-radius:var(--alz-radius-sm);background:var(--alz-purple-600);color:var(--alz-surface);text-decoration:none;font-weight:700}
		a:focus-visible{outline:3px solid var(--alz-navy-900);outline-offset:3px}
	</style>
</head>
<body>
	<main class="state">
		<div class="code" aria-hidden="true">500</div>
		<h1>تعذر إكمال الطلب</h1>
		<p>حدث خطأ مؤقت. إذا كنت تُكمل طلبًا أو عملية دفع، راجع حالته في حسابك قبل إعادة المحاولة.</p>
		<a href="/">العودة إلى الرئيسية</a>
	</main>
</body>
</html>
