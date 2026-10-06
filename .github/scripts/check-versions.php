<?php
/**
 * Release metadata consistency checks for the Alzaherah plugin/theme pair.
 *
 * Usage: php check-versions.php <repo-root> [<paired-repo-root>]
 */

$root   = rtrim( $argv[1] ?? '.', '/' ) . '/';
$paired = isset( $argv[2] ) && is_dir( $argv[2] ) ? rtrim( $argv[2], '/' ) . '/' : null;
$errors = array();

function alz_ci_header( $file, $field ) {
	$head = (string) file_get_contents( $file, false, null, 0, 8192 );
	return preg_match( '/^[ \t\/*#@]*' . preg_quote( $field, '/' ) . ':\s*(.+)$/mi', $head, $m ) ? trim( $m[1] ) : '';
}

function alz_ci_define( $file, $name ) {
	$code = (string) file_get_contents( $file );
	return preg_match( "/define\(\s*'" . preg_quote( $name, '/' ) . "'\s*,\s*'([^']+)'/", $code, $m ) ? $m[1] : '';
}

function alz_ci_const( $file, $name ) {
	$code = (string) file_get_contents( $file );
	return preg_match( '/const\s+' . preg_quote( $name, '/' ) . "\s*=\s*'([^']+)'/", $code, $m ) ? $m[1] : '';
}

function alz_ci_expect( &$errors, $label, $actual, $expected ) {
	if ( '' === (string) $actual || (string) $actual !== (string) $expected ) {
		$errors[] = sprintf( '%s: expected "%s", found "%s"', $label, $expected, $actual );
	} else {
		printf( "ok  %s = %s\n", $label, $actual );
	}
}

function alz_ci_plugin( $dir, &$errors ) {
	$main     = $dir . 'alzaherah-platform-core.php';
	$version  = alz_ci_header( $main, 'Version' );
	$manifest = json_decode( (string) file_get_contents( $dir . 'alz-release-manifest.json' ), true );
	if ( ! is_array( $manifest ) ) {
		$errors[] = 'alz-release-manifest.json is missing or invalid JSON';
		return null;
	}
	alz_ci_expect( $errors, 'plugin ALZ_CORE_VERSION', alz_ci_define( $main, 'ALZ_CORE_VERSION' ), $version );
	alz_ci_expect( $errors, 'manifest core_version', $manifest['core_version'] ?? '', $version );
	alz_ci_expect( $errors, 'manifest requires_php', $manifest['requires_php'] ?? '', alz_ci_header( $main, 'Requires PHP' ) );
	$quality = $dir . 'includes/class-alz-release-quality.php';
	if ( is_readable( $quality ) ) {
		alz_ci_expect( $errors, 'ALZ_Release_Quality::EXPECTED_CORE_VERSION', alz_ci_const( $quality, 'EXPECTED_CORE_VERSION' ), $manifest['core_version'] ?? '' );
		alz_ci_expect( $errors, 'ALZ_Release_Quality::EXPECTED_THEME_VERSION', alz_ci_const( $quality, 'EXPECTED_THEME_VERSION' ), $manifest['theme_version'] ?? '' );
	}
	if ( preg_match( '/\$min_theme\s*=\s*\'([^\']+)\'/', (string) file_get_contents( $main ), $m ) ) {
		alz_ci_expect( $errors, 'theme compatibility notice minimum', $m[1], $manifest['min_compatible_theme'] ?? '' );
	}
	if ( version_compare( (string) ( $manifest['min_compatible_core'] ?? '0' ), $version, '>' ) ) {
		$errors[] = 'manifest min_compatible_core is newer than the plugin version';
	}
	return $manifest;
}

function alz_ci_theme( $dir, &$errors ) {
	$version = alz_ci_header( $dir . 'style.css', 'Version' );
	alz_ci_expect( $errors, 'theme ALZAHERAH_THEME_VERSION', alz_ci_define( $dir . 'functions.php', 'ALZAHERAH_THEME_VERSION' ), $version );
	alz_ci_expect( $errors, 'theme Requires PHP', alz_ci_header( $dir . 'style.css', 'Requires PHP' ), '8.0' );
	return $version;
}

$plugin_dir = is_readable( $root . 'alzaherah-platform-core.php' ) ? $root : ( $paired && is_readable( $paired . 'alzaherah-platform-core.php' ) ? $paired : null );
$theme_dir  = is_readable( $root . 'style.css' ) ? $root : ( $paired && is_readable( $paired . 'style.css' ) ? $paired : null );

$manifest      = $plugin_dir ? alz_ci_plugin( $plugin_dir, $errors ) : null;
$theme_version = $theme_dir ? alz_ci_theme( $theme_dir, $errors ) : null;

if ( $manifest && $theme_version ) {
	$min = (string) ( $manifest['min_compatible_theme'] ?? '0' );
	if ( version_compare( $theme_version, $min, '<' ) ) {
		$errors[] = sprintf( 'theme %s is older than the plugin manifest min_compatible_theme %s', $theme_version, $min );
	} else {
		printf( "ok  theme %s satisfies min_compatible_theme %s\n", $theme_version, $min );
	}
	if ( ( $manifest['theme_version'] ?? '' ) !== $theme_version ) {
		printf( "::warning::paired theme is %s but the plugin manifest was generated for %s\n", $theme_version, $manifest['theme_version'] ?? '?' );
	}
} elseif ( ! $plugin_dir || ! $theme_dir ) {
	echo "note: paired repository not available; cross-version checks skipped\n";
}

foreach ( $errors as $error ) {
	echo '::error::' . $error . "\n";
}
exit( $errors ? 1 : 0 );
