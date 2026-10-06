<?php
/** WordPress maintenance drop-in candidate. Deploy separately to wp-content/maintenance.php (fonts then load from the root-relative theme URL). */
$protocol = isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
header( $protocol . ' 503 Service Unavailable', true, 503 );
header( 'Retry-After: 900' );
header( 'Content-Type: text/html; charset=UTF-8' );
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>صيانة مجدولة — مركز الزاهرة للتدريب</title>
	<style>
		@font-face{font-family:"Alexandria";font-style:normal;font-weight:100 900;font-display:swap;src:url("/wp-content/themes/alzaherah-theme-v3/assets/fonts/alexandria-var-arabic.woff2") format("woff2"),url("../assets/fonts/alexandria-var-arabic.woff2") format("woff2")}
		@font-face{font-family:"Alexandria";font-style:normal;font-weight:100 900;font-display:swap;src:url("/wp-content/themes/alzaherah-theme-v3/assets/fonts/alexandria-var-latin.woff2") format("woff2"),url("../assets/fonts/alexandria-var-latin.woff2") format("woff2")}
		:root{--alz-navy-950:#0d1730;--alz-navy-900:#14213d;--alz-teal-500:#10b9b2;--alz-teal-300:#5ad5cf;--alz-surface:#fff;--alz-radius-pill:999px}
		*{box-sizing:border-box}
		body{margin:0;padding:24px;font-family:"Alexandria",Tahoma,Arial,sans-serif;font-size:16px;font-synthesis-weight:none;line-height:1.85;background:var(--alz-navy-950);color:var(--alz-surface);display:grid;min-height:100vh;place-items:center}
		.state{width:min(100%,42rem);padding:32px;text-align:center}
		.tag{display:inline-block;padding:6px 13px;border-radius:var(--alz-radius-pill);background:var(--alz-teal-500);color:var(--alz-navy-900);font-weight:700}
		p{color:var(--alz-teal-300)}
	</style>
</head>
<body>
	<main class="state">
		<span class="tag">صيانة مجدولة</span>
		<h1>نُحسّن المنصة الآن</h1>
		<p>سنعود خلال وقت قصير. لا يلزم إعادة إرسال أي طلب أو عملية دفع.</p>
	</main>
</body>
</html>
