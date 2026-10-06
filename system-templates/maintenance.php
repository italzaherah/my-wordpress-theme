<?php
/** WordPress maintenance drop-in candidate. Deploy separately to wp-content/maintenance.php. */
$alz_system_theme_url = defined( 'ALZAHERAH_SYSTEM_THEME_URL' )
	? (string) ALZAHERAH_SYSTEM_THEME_URL
	: ( defined( 'WP_CONTENT_URL' ) ? rtrim( (string) WP_CONTENT_URL, '/' ) . '/themes/alzaherah-theme-v3' : '/wp-content/themes/alzaherah-theme-v3' );
// Early maintenance runs before WordPress defines WP_CONTENT_URL. Map the
// executing script back to ABSPATH so admin paths do not enter the site prefix.
if ( ! defined( 'ALZAHERAH_SYSTEM_THEME_URL' ) && ! defined( 'WP_CONTENT_URL' ) && defined( 'WP_CONTENT_DIR' ) && realpath( __DIR__ ) === realpath( WP_CONTENT_DIR ) ) {
	$alz_system_script_name = str_replace( '\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php' );
	$alz_system_prefix = rtrim( dirname( $alz_system_script_name ), '/.' );
	$alz_system_prefix = preg_replace( '#/wp-admin(?:/.*)?$#', '', $alz_system_prefix );
	$alz_system_script_file = str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '' );
	$alz_system_wp_root = defined( 'ABSPATH' ) ? rtrim( str_replace( '\\', '/', ABSPATH ), '/' ) . '/' : '';
	if ( '' !== $alz_system_wp_root && 0 === strpos( $alz_system_script_file, $alz_system_wp_root ) ) {
		$alz_system_script_suffix = '/' . substr( $alz_system_script_file, strlen( $alz_system_wp_root ) );
		if ( '/' !== $alz_system_script_suffix && substr( $alz_system_script_name, -strlen( $alz_system_script_suffix ) ) === $alz_system_script_suffix ) {
			$alz_system_prefix = substr( $alz_system_script_name, 0, -strlen( $alz_system_script_suffix ) );
		}
	}
	$alz_system_theme_url = $alz_system_prefix . '/wp-content/themes/alzaherah-theme-v3';
}
$alz_system_theme_url = htmlspecialchars( rtrim( $alz_system_theme_url, '/' ), ENT_QUOTES, 'UTF-8' );
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
		@font-face{font-family:"Alexandria";font-style:normal;font-weight:100 900;font-display:swap;src:url("<?php echo $alz_system_theme_url; ?>/assets/fonts/alexandria-var-arabic.woff2") format("woff2"),url("../assets/fonts/alexandria-var-arabic.woff2") format("woff2");unicode-range:U+0600-06FF,U+0750-077F,U+0870-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FEFC}
		@font-face{font-family:"Alexandria";font-style:normal;font-weight:100 900;font-display:swap;src:url("<?php echo $alz_system_theme_url; ?>/assets/fonts/alexandria-var-latin.woff2") format("woff2"),url("../assets/fonts/alexandria-var-latin.woff2") format("woff2");unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
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
