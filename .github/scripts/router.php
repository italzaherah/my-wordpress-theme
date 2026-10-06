<?php
/**
 * Router for PHP's built-in web server so WordPress pretty permalinks work in CI.
 */
$alz_ci_root = rtrim( (string) $_SERVER['DOCUMENT_ROOT'], '/' );
$alz_ci_path = (string) parse_url( (string) $_SERVER['REQUEST_URI'], PHP_URL_PATH );
if ( '/' !== $alz_ci_path && is_file( $alz_ci_root . $alz_ci_path ) ) {
	return false;
}
$alz_ci_dir = rtrim( $alz_ci_path, '/' );
if ( '' !== $alz_ci_dir && is_dir( $alz_ci_root . $alz_ci_dir ) && is_file( $alz_ci_root . $alz_ci_dir . '/index.php' ) ) {
	chdir( $alz_ci_root . $alz_ci_dir );
	require $alz_ci_root . $alz_ci_dir . '/index.php';
	return;
}
chdir( $alz_ci_root );
require $alz_ci_root . '/index.php';
