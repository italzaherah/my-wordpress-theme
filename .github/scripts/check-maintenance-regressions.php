<?php
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
// Early WordPress bootstrap URL checks; no WordPress API/database is available yet.
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
$theme = rtrim( $argv[1] ?? __DIR__ . '/theme', '/\\' );
$fixture_root = rtrim( sys_get_temp_dir(), '/\\' ) . '/alz-maintenance-regression-' . bin2hex( random_bytes( 8 ) );
$site = $fixture_root . '/site';
$content = $site . '/wp-content';
$owned_dirs = array(); $owned_files = array(); $results = array();
try {
    if ( file_exists( $fixture_root ) ) { throw new RuntimeException( 'Fixture directory unexpectedly exists' ); }
    foreach ( array( $fixture_root, $site, $content, $site . '/wp-admin', $site . '/wp-admin/network' ) as $dir ) {
        if ( ! mkdir( $dir, 0777 ) ) { throw new RuntimeException( 'Cannot create fixture directory' ); }
        $owned_dirs[] = $dir;
    }
    $owned_files[] = $content . '/maintenance.php';
    if ( ! copy( $theme . '/system-templates/maintenance.php', $content . '/maintenance.php' ) ) { throw new RuntimeException( 'Cannot copy maintenance template' ); }
    define( 'ABSPATH', $site . '/' );
    define( 'WP_CONTENT_DIR', $content );
    $cases = array(
        array( '/index.php', 'index.php', '/wp-content/themes/alzaherah-theme-v3' ),
        array( '/portal/index.php', 'index.php', '/portal/wp-content/themes/alzaherah-theme-v3' ),
        array( '/wp-admin/admin.php', 'wp-admin/admin.php', '/wp-content/themes/alzaherah-theme-v3' ),
        array( '/portal/wp-admin/admin.php', 'wp-admin/admin.php', '/portal/wp-content/themes/alzaherah-theme-v3' ),
        array( '/portal/wp-admin/network/index.php', 'wp-admin/network/index.php', '/portal/wp-content/themes/alzaherah-theme-v3' ),
    );
    foreach ( $cases as $case ) {
        $_SERVER['SCRIPT_NAME'] = $case[0];
        $_SERVER['SCRIPT_FILENAME'] = $site . '/' . $case[1];
        if ( ! is_file( $_SERVER['SCRIPT_FILENAME'] ) ) {
            $owned_files[] = $_SERVER['SCRIPT_FILENAME'];
            if ( false === file_put_contents( $_SERVER['SCRIPT_FILENAME'], "<?php\n" ) ) { throw new RuntimeException( 'Cannot create fixture script' ); }
        }
        ob_start(); include $content . '/maintenance.php'; $html = ob_get_clean();
        $results[] = array( substr_count( $html, 'src:url("' . $case[2] . '/assets/fonts/' ) === 2, $case[0] . ' early-bootstrap font root' );
    }
    define( 'WP_CONTENT_URL', 'https://example.test/custom-content' );
    ob_start(); include $content . '/maintenance.php'; $html = ob_get_clean();
    $results[] = array( substr_count( $html, 'src:url("https://example.test/custom-content/themes/alzaherah-theme-v3/assets/fonts/' ) === 2, 'Configured WP_CONTENT_URL is retained' );
    define( 'ALZAHERAH_SYSTEM_THEME_URL', 'https://cdn.example.test/theme' );
    ob_start(); include $content . '/maintenance.php'; $html = ob_get_clean();
    $results[] = array( substr_count( $html, 'src:url("https://cdn.example.test/theme/assets/fonts/' ) === 2, 'Explicit system-theme URL takes precedence' );
} finally {
    // Delete only exact paths this fixture owns, never a recursive unknown tree.
    $resolved_root = realpath( $fixture_root );
    $resolved_temp = realpath( sys_get_temp_dir() );
    if ( $owned_dirs && ( ! $resolved_root || ! $resolved_temp || dirname( $resolved_root ) !== $resolved_temp ) ) { throw new RuntimeException( 'Fixture cleanup root escaped the temporary directory' ); }
    foreach ( array_reverse( $owned_files ) as $file ) {
        if ( ! is_file( $file ) ) { continue; }
        $resolved_file = realpath( $file );
        if ( ! $resolved_file || 0 !== strpos( $resolved_file, $resolved_root . DIRECTORY_SEPARATOR ) ) { throw new RuntimeException( 'Fixture cleanup file escaped its owned root' ); }
        if ( ! unlink( $file ) ) { throw new RuntimeException( 'Cannot clean fixture file' ); }
    }
    foreach ( array_reverse( $owned_dirs ) as $dir ) {
        $resolved_dir = realpath( $dir );
        if ( ! $resolved_dir || ( $resolved_dir !== $resolved_root && 0 !== strpos( $resolved_dir, $resolved_root . DIRECTORY_SEPARATOR ) ) ) { throw new RuntimeException( 'Fixture cleanup directory escaped its owned root' ); }
        if ( ! rmdir( $dir ) ) { throw new RuntimeException( 'Cannot clean fixture directory' ); }
    }
}
$passed = 0;
foreach ( $results as $result ) { $passed += (int) $result[0]; echo ( $result[0] ? 'Passed: ' : 'Failed: ' ) . $result[1] . "\n"; }
echo 'Assertions: ' . $passed . ' passed, ' . ( count( $results ) - $passed ) . " failed\n";
exit( $passed === count( $results ) ? 0 : 1 );